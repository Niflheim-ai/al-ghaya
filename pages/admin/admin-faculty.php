<?php
session_start();
include('../../php/dbConnection.php');
require_once '../../php/mail-config.php';
require_once '../../php/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$current_page = "admin-faculty";
$page_title = "Faculty Management";

// Function to generate random password
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_faculty') {
        $email = trim($_POST['email'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        
        // Validation
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required.']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
            exit;
        }
        
        // Check if email already exists in user table
        $checkEmail = $conn->prepare("SELECT userID FROM user WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();
        
        if ($checkEmail->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
            $checkEmail->close();
            exit;
        }
        $checkEmail->close();
        
        // Generate random password
        $tempPassword = generateRandomPassword(12);
        $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Default values for teacher
        $role = 'teacher';
        $level = 1;
        $points = 0;
        $proficiency = 'advanced';
        $isActive = 1;
        
        $conn->begin_transaction();
        
        try {
            // Insert into user table
            $insertUser = $conn->prepare("INSERT INTO user (email, password, fname, lname, role, level, points, proficiency, isActive, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $insertUser->bind_param("sssssiisi", $email, $hashedPassword, $firstName, $lastName, $role, $level, $points, $proficiency, $isActive);
            $insertUser->execute();
            $newUserID = $conn->insert_id;
            $insertUser->close();
            
            // Insert into teacher table
            $username = $email;
            $insertTeacher = $conn->prepare("INSERT INTO teacher (userID, email, username, password, fname, lname, dateCreated, isActive) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)");
            $insertTeacher->bind_param("isssss", $newUserID, $email, $username, $hashedPassword, $firstName, $lastName);
            $insertTeacher->execute();
            $insertTeacher->close();
            
            $conn->commit();
            
            // Send email with login credentials using centralized mailer
            try {
                $emailResult = sendTeacherWelcomeEmail($email, $tempPassword, $firstName, $lastName);
                
                if ($emailResult['success']) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Faculty account created successfully! Login credentials have been sent to ' . $email . '.'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Faculty account created but email failed to send. Please provide credentials manually.',
                        'credentials' => [
                            'email' => $email,
                            'password' => $tempPassword
                        ],
                        'email_error' => $emailResult['message'] ?? 'Email delivery failed'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Faculty account created but email system error occurred.',
                    'credentials' => [
                        'email' => $email,
                        'password' => $tempPassword
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Faculty creation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create faculty account. Database error.']);
        }
        
        exit;
    }
    
    if ($_POST['action'] === 'archive_teacher') {
        $userID = intval($_POST['userID'] ?? 0);
        $archive = intval($_POST['archive'] ?? 1); // 1 = archive, 0 = unarchive
        
        if (!$userID) {
            echo json_encode(['success' => false, 'message' => 'Invalid teacher ID.']);
            exit;
        }
        
        $newStatus = $archive ? 0 : 1;
        $action_text = $archive ? 'archived' : 'restored';
        
        $conn->begin_transaction();
        
        try {
            // Update user table
            $updateUser = $conn->prepare("UPDATE user SET isActive = ? WHERE userID = ? AND role = 'teacher'");
            $updateUser->bind_param("ii", $newStatus, $userID);
            $updateUser->execute();
            $userAffected = $updateUser->affected_rows;
            $updateUser->close();
            
            // Update teacher table
            $updateTeacher = $conn->prepare("UPDATE teacher SET isActive = ? WHERE userID = ?");
            $updateTeacher->bind_param("ii", $newStatus, $userID);
            $updateTeacher->execute();
            $updateTeacher->close();
            
            if ($userAffected > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => "Teacher successfully {$action_text}."]);    
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Teacher not found or no changes made.']);
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Archive teacher error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        }
        
        exit;
    }
    
    if ($_POST['action'] === 'get_teachers') {
        $search = trim($_POST['search'] ?? '');
        $sortBy = $_POST['sortBy'] ?? 'recent';
        $filterDate = $_POST['filterDate'] ?? '';
        $showArchived = intval($_POST['showArchived'] ?? 0);
        
        // Base query
        $sql = "SELECT u.userID, u.fname, u.lname, u.email, u.dateCreated, u.lastLogin, u.isActive, 
                       t.teacherID, t.specialization,
                       (SELECT COUNT(*) FROM programs p WHERE p.teacherID = t.teacherID) as program_count
                FROM user u 
                LEFT JOIN teacher t ON u.userID = t.userID 
                WHERE u.role = 'teacher'";
        
        $params = [];
        $types = '';
        
        // Filter by active/archived status
        if ($showArchived) {
            $sql .= " AND u.isActive = 0";
        } else {
            $sql .= " AND u.isActive = 1";
        }
        
        // Search filter
        if (!empty($search)) {
            $sql .= " AND (u.fname LIKE ? OR u.lname LIKE ? OR u.email LIKE ? OR CONCAT(u.fname, ' ', u.lname) LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            $types .= 'ssss';
        }
        
        // Date filter
        if (!empty($filterDate)) {
            $sql .= " AND DATE(u.dateCreated) = ?";
            $params[] = $filterDate;
            $types .= 's';
        }
        
        // Sorting
        switch ($sortBy) {
            case 'alphabetical_asc':
                $sql .= " ORDER BY u.fname ASC, u.lname ASC";
                break;
            case 'alphabetical_desc':
                $sql .= " ORDER BY u.fname DESC, u.lname DESC";
                break;
            case 'oldest':
                $sql .= " ORDER BY u.dateCreated ASC";
                break;
            case 'recent':
            default:
                $sql .= " ORDER BY u.dateCreated DESC";
                break;
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $teachers = [];
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }
        
        echo json_encode(['success' => true, 'teachers' => $teachers]);
        exit;
    }
}

// Get faculty statistics
$stats = [
    'total' => 0,
    'archived' => 0,
    'publish_requests' => 0,
    'update_requests' => 0
];

// Get total active teachers
$totalQuery = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'teacher' AND isActive = 1");
if ($totalQuery) {
    $stats['total'] = $totalQuery->fetch_assoc()['count'];
}

// Get archived teachers
$archivedQuery = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'teacher' AND isActive = 0");
if ($archivedQuery) {
    $stats['archived'] = $archivedQuery->fetch_assoc()['count'];
}

// Get publish requests (draft and pending programs)
$publishQuery = $conn->query("SELECT COUNT(*) as count FROM programs p INNER JOIN teacher t ON p.teacherID = t.teacherID INNER JOIN user u ON t.userID = u.userID WHERE p.status IN ('draft', 'pending_review') AND u.isActive = 1");
if ($publishQuery) {
    $stats['publish_requests'] = $publishQuery->fetch_assoc()['count'];
}

// Get update requests - you can customize this based on your update request logic
$stats['update_requests'] = 0; // Placeholder
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<div class="page-container">
    <div class="page-content">
        <section class="content-section">
            <h1 class="section-title">Faculty Management</h1>
            
            <!-- Statistics Cards -->
            <div class="w-full flex gap-[10px] mb-6">
                <!-- Add Faculty Account -->
                <button type="button" onclick="openAddFacultyModal()" class="group flex flex-grow p-[25px] gap-[10px] rounded-[10px] text-company_white bg-secondary flex flex-col items-center justify-center hover:bg-company_black transition-all duration-200">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph ph-user-plus text-[40px] group-hover:hidden"></i>
                        <i class="ph-duotone ph-user-plus text-[40px] hidden group-hover:block"></i>
                    </div>
                    <p>Add Faculty Account</p>
                </button>

                <!-- Total Teachers -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
                    <div class="text-tertiary flex items-center gap-[10px]">
                        <i class="ph-duotone ph-chalkboard-simple text-[40px]"></i>
                        <p class="sub-header"><?= sprintf('%02d', $stats['total']) ?></p>
                    </div>
                    <p>Active Teachers</p>
                </div>

                <!-- Publish Requests -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_green flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-seal-check text-[40px]"></i>
                        <p class="sub-header"><?= sprintf('%02d', $stats['publish_requests']) ?></p>
                    </div>
                    <p>Publish Requests</p>
                </div>

                <!-- Update Requests -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_orange flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-traffic-cone text-[40px]"></i>
                        <p class="sub-header"><?= sprintf('%02d', $stats['update_requests']) ?></p>
                    </div>
                    <p>Update Requests</p>
                </div>

                <!-- Archived Teachers -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_red flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-archive text-[40px]"></i>
                        <p class="sub-header"><?= sprintf('%02d', $stats['archived']) ?></p>
                    </div>
                    <p>Archived Teachers</p>
                </div>
            </div>
            
            <!-- Teachers Management Table -->
            <div class="section-card flex-col">
                <!-- Controls Header -->
                <div class="w-full flex items-center justify-between mb-6">
                    <!-- Left: Sort and Filter Controls -->
                    <div class="flex flex-col items-start gap-[20px]">
                        <div class="flex gap-[10px] items-center">
                            <i class="ph ph-arrows-down-up text-[24px]"></i>
                            <p class="body-text2-semibold">Sort & Filter</p>
                        </div>
                        <div class="flex gap-[10px] items-center flex-wrap">
                            <label class="inline-flex items-center">
                                <input type="radio" name="sort-teachers" value="recent" class="form-radio h-4 w-4 text-primary" checked>
                                <span class="ml-2">Recent</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="sort-teachers" value="alphabetical_asc" class="form-radio h-4 w-4 text-primary">
                                <span class="ml-2">A-Z</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="sort-teachers" value="alphabetical_desc" class="form-radio h-4 w-4 text-primary">
                                <span class="ml-2">Z-A</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="sort-teachers" value="oldest" class="form-radio h-4 w-4 text-primary">
                                <span class="ml-2">Oldest</span>
                            </label>
                            <label class="inline-flex items-center ml-4">
                                <input type="checkbox" id="showArchived" class="form-checkbox h-4 w-4 text-primary">
                                <span class="ml-2 text-red-600">Show Archived</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Right: Search and Download -->
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-[10px]">
                            <input type="date" id="filterDate" class="border border-gray-300 rounded-lg px-3 py-2" title="Filter by creation date">
                        </div>
                        <button type="button" onclick="downloadFacultyRecords()" class="group btn-secondary">
                            <i class="ph ph-download text-[20px] group-hover:hidden"></i>
                            <i class="ph-duotone ph-download text-[20px] hidden group-hover:block"></i>
                            <p class="font-medium">Download</p>
                        </button>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="w-full flex items-center gap-[10px] mb-6">
                    <i class="ph ph-magnifying-glass text-[30px]"></i>
                    <input type="text" id="searchTeachers" placeholder="Search teachers by name or email..." class="w-[500px] h-[40px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary">
                </div>
                
                <!-- Teachers Table -->
                <div class="w-full overflow-x-auto">
                    <table class="w-full bg-white border border-gray-200 rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programs</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Join Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teachersTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Dynamic content loaded via AJAX -->
                        </tbody>
                    </table>
                    <div id="noTeachersMessage" class="w-full h-[200px] flex items-center justify-center text-gray-500 hidden">
                        <div class="text-center">
                            <i class="ph ph-user-x text-[48px] mb-2"></i>
                            <p>No teachers found</p>
                        </div>
                    </div>
                    <div id="loadingTeachers" class="w-full h-[200px] flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-2"></div>
                            <p>Loading teachers...</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Add Faculty Modal -->
<div id="addFacultyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-lg mx-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Add Faculty Account</h2>
            <button onclick="closeAddFacultyModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ph ph-x text-[24px]"></i>
            </button>
        </div>
        
        <form id="addFacultyForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" id="firstName" name="first_name" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="John">
                </div>
                <div>
                    <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input type="text" id="lastName" name="last_name"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="Doe">
                </div>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                    placeholder="teacher@example.com">
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="ph ph-info text-blue-600 text-[20px] mr-2"></i>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">Automatic Setup</p>
                        <p class="text-xs text-blue-600">A secure password will be generated and sent to the teacher's email using your configured email system.</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4">
                <button type="button" onclick="closeAddFacultyModal()" 
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-red-400 hover:text-white font-medium transition duration-200 hover:cursor-pointer">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-6 py-3 bg-company_blue hover:bg-company_blue/80 hover:cursor-pointer text-white rounded-lg font-medium transition duration-200 flex items-center">
                    <i class="ph ph-user-plus text-[18px] mr-2"></i>
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-60">
    <div class="bg-white rounded-lg p-8 flex items-center space-x-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        <p class="text-gray-700">Processing request...</p>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentTeachers = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadTeachers();
    
    // Search functionality
    document.getElementById('searchTeachers').addEventListener('input', debounce(loadTeachers, 300));
    
    // Sort functionality
    document.querySelectorAll('input[name="sort-teachers"]').forEach(radio => {
        radio.addEventListener('change', loadTeachers);
    });
    
    // Archive toggle
    document.getElementById('showArchived').addEventListener('change', loadTeachers);
    
    // Date filter
    document.getElementById('filterDate').addEventListener('change', loadTeachers);
});

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function loadTeachers() {
    const search = document.getElementById('searchTeachers').value;
    const sortBy = document.querySelector('input[name="sort-teachers"]:checked').value;
    const showArchived = document.getElementById('showArchived').checked ? 1 : 0;
    const filterDate = document.getElementById('filterDate').value;
    
    document.getElementById('loadingTeachers').style.display = 'flex';
    document.getElementById('noTeachersMessage').style.display = 'none';
    
    const formData = new FormData();
    formData.append('action', 'get_teachers');
    formData.append('search', search);
    formData.append('sortBy', sortBy);
    formData.append('showArchived', showArchived);
    formData.append('filterDate', filterDate);
    
    fetch('admin-faculty.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingTeachers').style.display = 'none';
        
        if (data.success) {
            currentTeachers = data.teachers;
            displayTeachers(data.teachers);
        } else {
            console.error('Failed to load teachers');
            document.getElementById('noTeachersMessage').style.display = 'flex';
        }
    })
    .catch(error => {
        document.getElementById('loadingTeachers').style.display = 'none';
        document.getElementById('noTeachersMessage').style.display = 'flex';
        console.error('Error loading teachers:', error);
    });
}

function displayTeachers(teachers) {
    const tbody = document.getElementById('teachersTableBody');
    const noMessage = document.getElementById('noTeachersMessage');
    
    if (teachers.length === 0) {
        tbody.innerHTML = '';
        noMessage.style.display = 'flex';
        return;
    }
    
    noMessage.style.display = 'none';
    
    tbody.innerHTML = teachers.map(teacher => {
        const fullName = `${teacher.fname || ''} ${teacher.lname || ''}`.trim() || 'Unnamed';
        const joinDate = new Date(teacher.dateCreated).toLocaleDateString();
        const lastLogin = teacher.lastLogin ? new Date(teacher.lastLogin).toLocaleDateString() : 'Never';
        const isActive = parseInt(teacher.isActive);
        const programCount = parseInt(teacher.program_count) || 0;
        
        const statusBadge = isActive 
            ? '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Active</span>'
            : '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Archived</span>';
        
        const archiveButton = isActive
            ? `<button onclick="archiveTeacher(${teacher.userID}, '${fullName}', 1)" class="text-red-600 hover:text-red-800 transition-colors" title="Archive Teacher">
                 <i class="ph ph-archive text-[18px]"></i>
               </button>`
            : `<button onclick="archiveTeacher(${teacher.userID}, '${fullName}', 0)" class="text-green-600 hover:text-green-800 transition-colors" title="Restore Teacher">
                 <i class="ph ph-arrow-counter-clockwise text-[18px]"></i>
               </button>`;
        
        return `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center font-medium">
                                ${(teacher.fname ? teacher.fname.charAt(0) : '') + (teacher.lname ? teacher.lname.charAt(0) : '')}
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${fullName}</div>
                            <div class="text-sm text-gray-500">Teacher ID: ${teacher.teacherID || 'N/A'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${teacher.email}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${programCount}</div>
                    <div class="text-xs text-gray-500">${programCount === 1 ? 'program' : 'programs'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${joinDate}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${lastLogin}</td>
                <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center space-x-3">
                        <button onclick="viewTeacher(${teacher.userID})" class="text-primary hover:text-secondary transition-colors" title="View Details">
                            <i class="ph ph-eye text-[18px]"></i>
                        </button>
                        ${archiveButton}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function archiveTeacher(userID, teacherName, archive) {
    const actionText = archive ? 'archive' : 'restore';
    const actionColor = archive ? '#ef4444' : '#10b981';
    
    Swal.fire({
        title: `${archive ? 'Archive' : 'Restore'} Teacher?`,
        text: `Are you sure you want to ${actionText} ${teacherName}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${actionText}`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'archive_teacher');
            formData.append('userID', userID);
            formData.append('archive', archive);
            
            document.getElementById('loadingOverlay').classList.remove('hidden');
            
            fetch('admin-faculty.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadTeachers(); // Reload the table
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to process request. Please try again.'
                });
            });
        }
    });
}

function viewTeacher(userID) {
    // Implement teacher details view - you can create a modal or redirect to a details page
    console.log('View teacher details for userID:', userID);
    Swal.fire({
        icon: 'info',
        title: 'Teacher Details',
        text: 'Teacher details view will be implemented here.',
        confirmButtonText: 'OK'
    });
}

function downloadFacultyRecords() {
    const teachers = currentTeachers;
    if (teachers.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Data',
            text: 'No teacher records to download.'
        });
        return;
    }
    
    // Create CSV content
    const headers = ['Name', 'Email', 'Programs', 'Join Date', 'Last Login', 'Status'];
    const csvContent = [
        headers.join(','),
        ...teachers.map(teacher => {
            const fullName = `"${(teacher.fname || '') + ' ' + (teacher.lname || '')}".trim()`;
            const email = `"${teacher.email}"`;
            const programs = teacher.program_count || 0;
            const joinDate = new Date(teacher.dateCreated).toLocaleDateString();
            const lastLogin = teacher.lastLogin ? new Date(teacher.lastLogin).toLocaleDateString() : 'Never';
            const status = teacher.isActive ? 'Active' : 'Archived';
            
            return [fullName, email, programs, joinDate, lastLogin, status].join(',');
        })
    ].join('\n');
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `faculty_records_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    Swal.fire({
        icon: 'success',
        title: 'Downloaded!',
        text: 'Faculty records have been downloaded.',
        timer: 2000,
        showConfirmButton: false
    });
}

// Add Faculty Modal Functions
function openAddFacultyModal() {
    document.getElementById('addFacultyModal').classList.remove('hidden');
    document.getElementById('email').focus();
}

function closeAddFacultyModal() {
    document.getElementById('addFacultyModal').classList.add('hidden');
    document.getElementById('addFacultyForm').reset();
}

// Handle form submission
document.getElementById('addFacultyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_faculty');
    
    const email = formData.get('email').trim();
    if (!email) {
        Swal.fire({
            icon: 'error',
            title: 'Email Required',
            text: 'Please enter the teacher\'s email address.'
        });
        return;
    }
    
    // Show loading overlay
    document.getElementById('loadingOverlay').classList.remove('hidden');
    document.getElementById('addFacultyModal').classList.add('hidden');
    
    // Send AJAX request
    fetch('admin-faculty.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingOverlay').classList.add('hidden');
        
        if (data.success) {
            if (data.credentials && data.email_error) {
                // Account created but email failed
                Swal.fire({
                    icon: 'warning',
                    title: 'Account Created - Email Failed',
                    html: `
                        <p>Faculty account has been created but failed to send email.</p>
                        <br>
                        <div class="bg-gray-100 p-4 rounded text-left">
                            <p><strong>Please provide these credentials manually:</strong></p>
                            <p><strong>Email:</strong> ${data.credentials.email}</p>
                            <p><strong>Password:</strong> <code style="background: #e5e7eb; padding: 2px 8px; border-radius: 4px; font-family: monospace;">${data.credentials.password}</code></p>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                }).then(() => {
                    loadTeachers(); // Refresh table
                });
            } else {
                // Success
                Swal.fire({
                    icon: 'success',
                    title: 'Faculty Account Created!',
                    text: data.message,
                    showConfirmButton: true,
                    timer: 4000
                }).then(() => {
                    loadTeachers(); // Refresh table
                });
            }
        } else {
            // Error
            Swal.fire({
                icon: 'error',
                title: 'Failed to Create Account',
                text: data.message
            });
        }
        
        // Reset form
        document.getElementById('addFacultyForm').reset();
    })
    .catch(error => {
        document.getElementById('loadingOverlay').classList.add('hidden');
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Failed to create faculty account. Please try again.'
        });
    });
});

// Email validation
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        this.setCustomValidity('Please enter a valid email address');
        this.classList.add('border-red-500');
        this.classList.remove('border-gray-300');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-500');
        this.classList.add('border-gray-300');
    }
});

// Close modal when clicking outside
document.getElementById('addFacultyModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddFacultyModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddFacultyModal();
    }
});
</script>

</body>
</html>