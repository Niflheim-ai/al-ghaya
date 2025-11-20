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

$userQuery = $conn->prepare("SELECT fname, lname, email FROM user WHERE userID = ?");
$userQuery->bind_param("i", $_SESSION['userID']);
$userQuery->execute();
$userInfo = $userQuery->get_result()->fetch_assoc();
$userInitials = strtoupper(substr($userInfo['fname'], 0, 1) . substr($userInfo['lname'], 0, 1));
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

<!-- Program Review Modal -->
<div id="programReviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg max-w-5xl w-full max-h-[90vh] overflow-hidden shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold" id="modalProgramTitle">Program Review</h2>
        <p class="text-blue-100 text-sm mt-1" id="modalProgramTeacher"></p>
      </div>
      <button onclick="closeReviewModal()" class="text-white hover:text-gray-200 text-3xl leading-none">&times;</button>
    </div>

    <!-- Modal Content -->
    <div class="overflow-y-auto max-h-[calc(90vh-200px)] p-6" id="modalContent">
      <div class="text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="text-gray-600 mt-4">Loading program details...</p>
      </div>
    </div>

    <!-- Modal Footer with Actions -->
    <div class="bg-gray-50 px-6 py-4 flex justify-end border-t">
      <button onclick="closeReviewModal()" class="px-4 py-2 bg-red-600 rounded rounded-md text-white hover:text-gray-800 hover:bg-red-400">
        Go Back
      </button>
    </div>
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

let lastViewedTeacherId = null;

function viewTeacher(userID) {
  lastViewedTeacherId = userID;

  Swal.fire({
    title: '<span style="display:flex;align-items:center;gap:16px;"><i class="ph ph-chalkboard-simple" style="color:#2563eb;font-size:2em"></i> Teacher Details</span>',
    width: 800,
    allowOutsideClick: false,
    showConfirmButton: false,
    showCloseButton: false,
    background: '#f9fafb',
    customClass: { popup: 'swal2-popup-minimal' },
    didOpen: () => { Swal.showLoading(); }
  });

  fetch('../../php/admin-get-teacher-details.php?teacherId=' + encodeURIComponent(userID))
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        Swal.fire('Error', data.message || 'Could not load teacher details.', 'error');
        return;
      }
      let teacher = data.teacher;
      let programs = data.programs;

      let progList = programs.map(p =>
        `<tr>
          <td>
            <a href="#" 
               onclick="Swal.close(); openReviewModal(${p.programID}, ${userID}); return false;" 
               style="color:#2563eb;text-decoration:none;font-weight:500;transition:color .15s;"
               onmouseover="this.style.color='#334155'" 
               onmouseout="this.style.color='#2563eb'">
              ${escapeHtml(p.title)}
            </a>
          </td>
          <td style="text-align:right">${p.enrollee_count}</td>
        </tr>`
      ).join('');

      let progTableAndChart = `
        <div style="display:flex;gap:48px;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;">
          <div style="flex:1 1 320px;min-width:260px;">
            <div style="font-weight:600;font-size:16px;color:#475569;margin-bottom:15px;">Programs</div>
            <table style="width:100%;background:#fff;border-radius:12px;overflow:hidden;">
              <thead>
                <tr>
                  <th style="padding:10px 8px;text-align:left;color:#64748b;font-size:13px;font-weight:500;background:#f3f5f7;">Name</th>
                  <th style="padding:10px 8px;text-align:right;color:#64748b;font-size:13px;font-weight:500;background:#f3f5f7;">Enrollees</th>
                </tr>
              </thead>
              <tbody>
                ${progList || '<tr><td colspan="2" style="padding:14px 0;text-align:center;color:#aaa;font-size:15px;">No programs found</td></tr>'}
              </tbody>
            </table>
          </div>
          <div style="flex:1 1 340px;min-width:220px;padding-left:16px;">
            <div style="font-weight:600;font-size:16px;color:#475569;margin-bottom:14px;letter-spacing:.3px;">
              Enrollees per Program
            </div>
            <div style="padding:8px 0 0;border-radius:16px;background:#fff;">
              <canvas id="teacherProgramsChart" height="180" style="max-width:100%;background:#fff;border-radius:14px;"></canvas>
            </div>
          </div>
        </div>
      `;

      Swal.fire({
        html: `
          <div style="text-align:left">
            <div style="margin-bottom:22px;display:flex;align-items:center;gap:16px;">
              <img src="../../images/male.svg" style="width:50px;height:50px;border-radius:50%;background:#e5e7eb;border:2px solid #f3f5f7;" />
              <div>
                <div style="font-size:1.4em;color:#2563eb;font-weight:700;">${escapeHtml(teacher.fname + ' ' + teacher.lname)}</div>
                <div style="font-size:1em;color:#888;margin-bottom:4px;">Faculty Member</div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:13px;color:#475569;">
                  <span style="padding:3px 12px;background:#e0e4ea;border-radius:8px;font-weight:500;">
                    ${escapeHtml(teacher.specialization || 'No Specialization')}
                  </span>
                  <span style="padding:3px 12px;background:#fee2b3;border-radius:8px;font-weight:500;">
                    Programs: ${data.total_programs}
                  </span>
                  <span style="padding:3px 12px;background:#d2f3e3;border-radius:8px;color:#15803d;font-weight:500;">
                    Enrollees: ${data.total_enrollees}
                  </span>
                </div>
              </div>
            </div>
            ${progTableAndChart}
            <div style="margin-top:24px;text-align:right">
              <button onclick="Swal.close()" style="background:#2563eb;color:#fff;font-size:15px;padding:7px 22px;border:none;border-radius:8px;font-weight:500;cursor:pointer;">
                Close
              </button>
            </div>
          </div>
        `,
        width: 800,
        showCloseButton: false,
        showConfirmButton: false,
        background: '#f9fafb',
        customClass: { popup: 'swal2-popup-minimal' },
        didOpen: () => {
          if (programs.length) {
            let ctx = document.getElementById('teacherProgramsChart').getContext('2d');
            new Chart(ctx, {
              type: 'bar',
              data: {
                labels: programs.map(p => p.title),
                datasets: [{
                  label: 'Enrollees',
                  data: programs.map(p => p.enrollee_count),
                  backgroundColor: '#2563eb',
                  barPercentage: 0.8,
                  categoryPercentage: 0.55,
                  borderRadius: 5,
                  borderSkipped: false
                }]
              },
              options: {
                indexAxis: 'y',
                plugins: {
                  legend: { display: false },
                  tooltip: { enabled: true }
                },
                scales: {
                  x: {
                    grid: { color: "#f3f5f7" },
                    ticks: { color: "#64748b", font: { size: 13 } }
                  },
                  y: {
                    grid: { color: "#f3f5f7" },
                    ticks: { color: "#64748b", font: { size: 13 } }
                  },
                }
              }
            });
          }
        }
      });
    });
}

function escapeHtml(text) {
  let div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
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

<!-- Open View Program Modal -->
<script>
    let currentProgramId = null;

    // Open review modal and load program details
    function openReviewModal(programId) {
        currentProgramId = programId;
        document.getElementById('programReviewModal').classList.remove('hidden');
        loadProgramDetails(programId);
    }

    // Close modal
    function closeReviewModal() {
        const reviewModal = document.getElementById('programReviewModal');
        if (reviewModal) reviewModal.classList.add('hidden');

        // Show the last-viewed teacher modal again
        if (lastViewedTeacherId !== null) {
            // Use your actual modal show function for viewTeacher
            viewTeacher(lastViewedTeacherId);
        }
        currentProgramId = null;
    }

    function loadProgramDetails(programId) {
    const modalContent = document.getElementById('modalContent');
    
    console.log('Loading program ID:', programId);
    
    fetch(`../../php/admin-get-program-details.php?program_id=${programId}`)
        .then(response => response.json())
        .then(data => {
        console.log('Full response:', data); // ✅ Debug
        if (data.success) {
            console.log('Program chapters:', data.program.chapters); // ✅ Debug
            if (data.program.chapters && data.program.chapters.length > 0) {
            console.log('First chapter:', data.program.chapters[0]); // ✅ Debug
            console.log('Has quiz?', data.program.chapters[0].has_quiz); // ✅ Debug
            console.log('Question?', data.program.chapters[0].question); // ✅ Debug
            }
            renderProgramDetails(data.program);
        } else {
            modalContent.innerHTML = `<div class="text-center py-8 text-red-600">Error loading program: ${data.message}</div>`;
        }
        })
        .catch(error => {
        modalContent.innerHTML = `<div class="text-center py-8 text-red-600">Failed to load program details.</div>`;
        console.error('Error:', error);
        });
    }

    // Render program details in modal
    function renderProgramDetails(program) {
    const modalContent = document.getElementById('modalContent');
    document.getElementById('modalProgramTitle').textContent = program.title;
    document.getElementById('modalProgramTeacher').textContent = `Teacher: ${program.teacher_name} (${program.teacher_email})`;
    
    let chaptersHtml = '';
    if (program.chapters && program.chapters.length > 0) {
        program.chapters.forEach((chapter, idx) => {
        // Stories section
        let storiesHtml = '';
        if (chapter.stories && chapter.stories.length > 0) {
            chapter.stories.forEach((story, sIdx) => {
            // Interactive sections for this story
            let interactiveSectionsHtml = '';
            if (story.interactive_sections && story.interactive_sections.length > 0) {
                interactiveSectionsHtml = `
                <div class="mt-4 space-y-3">
                    <p class="text-xs font-semibold text-purple-800 mb-2">
                    <i class="ph ph-magic-wand text-purple-600 mr-1"></i>
                    Interactive Sections (${story.interactive_sections.length})
                    </p>
                    ${story.interactive_sections.map((section, secIdx) => {
                    // Render questions for this section
                    let questionsHtml = '';
                    if (section.questions && section.questions.length > 0) {
                        questionsHtml = section.questions.map((question, qIdx) => {
                        // Render options
                        let optionsHtml = question.options.map(opt => {
                            const isCorrect = opt.is_correct == 1;
                            return `
                            <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                                ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-1"></i>' : ''}
                                ${escapeHtml(opt.option_text)}
                                ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct)</span>' : ''}
                            </div>
                            `;
                        }).join('');
                        
                        return `
                            <div class="mb-3">
                            <p class="font-medium text-gray-800 mb-2">${qIdx + 1}. ${escapeHtml(question.question_text)}</p>
                            <p class="text-xs text-gray-500 mb-2">Type: ${question.question_type}</p>
                            <div class="space-y-1 ml-3">
                                ${optionsHtml}
                            </div>
                            </div>
                        `;
                        }).join('');
                    }
                    
                    return `
                        <div class="p-3 bg-purple-50 border-l-4 border-purple-400 rounded">
                        <p class="text-xs font-semibold text-purple-900 mb-3">Section ${secIdx + 1}</p>
                        ${questionsHtml || '<p class="text-xs text-gray-500 italic">No questions in this section</p>'}
                        </div>
                    `;
                    }).join('')}
                </div>
                `;
            }
            
            storiesHtml += `
                <div class="ml-6 mb-4 p-4 bg-white border border-gray-300 rounded-lg shadow-sm">
                <h5 class="font-bold text-blue-900 mb-2 flex items-center gap-2">
                    <i class="ph ph-book-open text-blue-600"></i>
                    Story ${sIdx + 1}: ${escapeHtml(story.title)}
                </h5>
                
                <!-- Arabic Synopsis -->
                <div class="mb-3 p-3 bg-amber-50 border-l-4 border-amber-400 rounded">
                    <p class="text-xs font-semibold text-amber-800 mb-1">Arabic Synopsis:</p>
                    <p class="text-sm text-gray-800 arabic leading-relaxed">${escapeHtml(story.synopsis_arabic)}</p>
                </div>
                
                <!-- English Synopsis -->
                <div class="mb-3 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
                    <p class="text-xs font-semibold text-blue-800 mb-1">English Synopsis:</p>
                    <p class="text-sm text-gray-800 leading-relaxed">${escapeHtml(story.synopsis_english)}</p>
                </div>
                
                <!-- Video Player -->
                ${story.video_url_embed ? `
                    <div class="mt-3">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Video Content:</p>
                    <div class="relative" style="padding-bottom: 56.25%; height: 0;">
                        <iframe 
                        src="${escapeHtml(story.video_url_embed)}" 
                        class="absolute top-0 left-0 w-full h-full rounded-lg"
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                        allowfullscreen>
                        </iframe>
                    </div>
                    </div>
                ` : (story.video_url ? `<p class="text-xs text-gray-500 italic">Video URL: ${escapeHtml(story.video_url)}</p>` : '<p class="text-xs text-gray-500 italic">No video for this story</p>')}
                
                <!-- Interactive Sections -->
                ${interactiveSectionsHtml}
                </div>
            `;
            });
        } else {
            storiesHtml = '<p class="ml-6 text-sm text-gray-500 italic">No stories in this chapter</p>';
        }
        
        // Chapter Interactive Question (if exists)
        let chapterQuestionHtml = '';
        if (chapter.question && chapter.question_type) {
            let optionsHtml = '';
            if (chapter.question_type === 'multiple_choice' && chapter.answer_options_parsed) {
            optionsHtml = chapter.answer_options_parsed.map(option => {
                const isCorrect = option === chapter.correct_answer;
                return `
                <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                    ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                    ${escapeHtml(option)}
                    ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
                </div>
                `;
            }).join('');
            } else if (chapter.question_type === 'true_false') {
            optionsHtml = `
                <div class="p-2 rounded border ${chapter.correct_answer === 'True' ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                ${chapter.correct_answer === 'True' ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                True
                ${chapter.correct_answer === 'True' ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
                </div>
                <div class="p-2 rounded border ${chapter.correct_answer === 'False' ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                ${chapter.correct_answer === 'False' ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                False
                ${chapter.correct_answer === 'False' ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
                </div>
            `;
            } else {
            optionsHtml = `<div class="p-2 bg-green-100 border border-green-500 rounded"><strong>Correct Answer:</strong> ${escapeHtml(chapter.correct_answer)}</div>`;
            }
            
            chapterQuestionHtml = `
            <div class="ml-6 mt-4 p-4 bg-purple-50 border-2 border-purple-400 rounded-lg">
                <h6 class="font-semibold text-purple-900 mb-2 flex items-center gap-2">
                <i class="ph ph-question text-purple-600"></i>
                Chapter Interactive Question (${chapter.points_reward} points)
                </h6>
                <p class="text-sm font-medium text-gray-800 mb-3">${escapeHtml(chapter.question)}</p>
                <div class="space-y-2">
                ${optionsHtml}
                </div>
            </div>
            `;
        }
        
        // Chapter Media
        let chapterMediaHtml = '';
        if (chapter.video_url_embed || chapter.audio_url) {
            chapterMediaHtml = '<div class="ml-6 mt-3 space-y-3">';
            if (chapter.video_url_embed) {
            chapterMediaHtml += `
                <div>
                <p class="text-xs font-semibold text-gray-700 mb-2">Chapter Video:</p>
                <div class="relative" style="padding-bottom: 56.25%; height: 0;">
                    <iframe 
                    src="${escapeHtml(chapter.video_url_embed)}" 
                    class="absolute top-0 left-0 w-full h-full rounded-lg"
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                    </iframe>
                </div>
                </div>
            `;
            }
            if (chapter.audio_url) {
            chapterMediaHtml += `
                <div>
                <p class="text-xs font-semibold text-gray-700 mb-2">Chapter Audio:</p>
                <audio controls class="w-full">
                    <source src="${escapeHtml(chapter.audio_url)}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                </div>
            `;
            }
            chapterMediaHtml += '</div>';
        }
        
        // Chapter Quiz
        let quizHtml = '';
        if (chapter.has_quiz && chapter.quiz_questions && chapter.quiz_questions.length > 0) {
            let questionsHtml = chapter.quiz_questions.map((q, qIdx) => {
            let optionsListHtml = q.options.map(opt => {
                const isCorrect = opt.is_correct == 1;
                return `
                <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                    ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                    ${escapeHtml(opt.option_text)}
                    ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct)</span>' : ''}
                </div>
                `;
            }).join('');
            
            return `
                <div class="mb-3 p-3 bg-white border border-gray-300 rounded">
                <p class="font-medium text-gray-800 mb-2">${qIdx + 1}. ${escapeHtml(q.question_text)}</p>
                <div class="space-y-1 ml-4">
                    ${optionsListHtml}
                </div>
                </div>
            `;
            }).join('');
            
            quizHtml = `
            <div class="ml-6 mt-4 p-4 bg-green-50 border-2 border-green-400 rounded-lg">
                <h6 class="font-semibold text-green-900 mb-3 flex items-center gap-2">
                <i class="ph ph-exam text-green-600"></i>
                Chapter Quiz (${chapter.quiz_questions.length} questions)
                </h6>
                ${questionsHtml}
            </div>
            `;
        }
        
        chaptersHtml += `
            <div class="mb-6 border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xl font-bold text-gray-900">
                <i class="ph ph-book-bookmark text-blue-600 mr-2"></i>
                Chapter ${chapter.chapter_order}: ${escapeHtml(chapter.title)}
                </h4>
                <div class="flex gap-2 text-xs">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded font-semibold">${chapter.story_count} stories</span>
                ${chapter.has_quiz ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded font-semibold">Has Quiz</span>` : ''}
                </div>
            </div>
            ${chapter.content ? `<div class="mb-4 p-3 bg-white border-l-4 border-blue-500 rounded"><p class="text-sm text-gray-700">${escapeHtml(chapter.content)}</p></div>` : ''}
            ${chapterMediaHtml}
            ${storiesHtml}
            ${chapterQuestionHtml}
            ${quizHtml}
            </div>
        `;
        });
    } else {
        chaptersHtml = '<p class="text-gray-500 italic">No chapters found in this program</p>';
    }
    
    modalContent.innerHTML = `
        <div class="space-y-6">
        <!-- Program Overview -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-300 rounded-lg p-5 shadow-sm">
            <h3 class="text-xl font-bold text-blue-900 mb-4">📋 Program Overview</h3>
            
            <div class="grid grid-cols-2 gap-4 text-sm mb-4">
            <div class="bg-white p-2 rounded"><strong>Difficulty:</strong> <span class="capitalize">${escapeHtml(program.category)}</span></div>
            <div class="bg-white p-2 rounded"><strong>Price:</strong> ₱${parseFloat(program.price).toFixed(2)}</div>
            </div>
            <div class="bg-white p-3 rounded">
            <strong class="text-gray-900">Description:</strong>
            <p class="text-gray-700 mt-1">${escapeHtml(program.description)}</p>
            </div>
            ${program.prerequisites ? `
            <div class="bg-white p-3 rounded mt-3">
                <strong class="text-gray-900">Prerequisites:</strong>
                <p class="text-gray-700 mt-1">${escapeHtml(program.prerequisites)}</p>
            </div>
            ` : ''} 
            ${program.learning_objectives ? `
            <div class="bg-white p-3 rounded mt-3">
                <strong class="text-gray-900">Learning Objectives:</strong>
                <p class="text-gray-700 mt-1">${escapeHtml(program.learning_objectives)}</p>
            </div>
            ` : ''}
        </div>

        <!-- Overview Video -->
            ${program.overview_video_url_embed ? `
            <div class="mb-4">
                <p class="text-sm font-semibold text-blue-900 mb-2">
                <i class="ph ph-play-circle text-blue-600 mr-1"></i>
                Program Introduction Video
                </p>
                <div class="relative bg-white rounded-lg overflow-hidden" style="padding-bottom: 56.25%; height: 0;">
                <iframe 
                    src="${escapeHtml(program.overview_video_url_embed)}" 
                    class="absolute top-0 left-0 w-full h-full"
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                </iframe>
                </div>
            </div>
            ` : ''}

        <!-- Chapters and Content -->
        <div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">📚 Program Content (${program.chapters ? program.chapters.length : 0} Chapters)</h3>
            ${chaptersHtml}
        </div>
        </div>
    `;
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReviewModal();
    }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include '../../components/footer.php'; ?>
</body>
</html>