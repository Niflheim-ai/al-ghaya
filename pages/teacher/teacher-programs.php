<?php
session_start();
$current_page = "teacher-programs";
$page_title = "My Programs";

// Enable debugging - set to false in production
$debug_mode = false;

if ($debug_mode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    $_SESSION['error_message'] = 'Please log in to access this page.';
    header("Location: ../login.php");
    exit();
}

// Check if user has teacher role
if (($_SESSION['role'] ?? '') !== 'teacher') {
    $_SESSION['error_message'] = 'Access denied. Teacher role required.';
    header("Location: ../login.php");
    exit();
}

// Include required files with proper error handling
try {
    require_once '../../php/dbConnection.php';
    require_once '../../php/functions.php';
    require_once '../../php/program-helpers.php';
    
} catch (Exception $e) {
    if ($debug_mode) {
        die('Error including required files: ' . $e->getMessage());
    } else {
        $_SESSION['error_message'] = 'System error. Please contact administrator.';
        header("Location: ../teacher-dashboard.php");
        exit();
    }
}

$user_id = (int)$_SESSION['userID'];
$action = $_GET['action'] ?? 'list';
$program_id = $_GET['program_id'] ?? null;
$chapter_id = $_GET['chapter_id'] ?? null;
$story_id = $_GET['story_id'] ?? null;

// Enhanced teacher ID retrieval with auto-creation
function getOrCreateTeacherId($conn, $user_id, $debug_mode = false) {
    // First, try to get existing teacher ID
    $stmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ? AND isActive = 1");
    if (!$stmt) {
        if ($debug_mode) {
            echo "<div class='debug-error'>Prepare statement failed: " . $conn->error . "</div>";
        }
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['teacherID'];
    }
    
    $stmt->close();
    
    if ($debug_mode) {
        echo "<div class='debug-info'>No teacher record found for user ID: {$user_id}. Attempting to create...</div>";
    }
    
    // If teacher record doesn't exist, create one
    $userStmt = $conn->prepare("SELECT email, fname, lname FROM user WHERE userID = ? AND role = 'teacher' AND isActive = 1");
    if (!$userStmt) {
        if ($debug_mode) {
            echo "<div class='debug-error'>User query prepare failed: " . $conn->error . "</div>";
        }
        return null;
    }
    
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $userStmt->close();
        
        if ($debug_mode) {
            echo "<div class='debug-info'>Found user record: " . $user['email'] . ". Creating teacher record...</div>";
        }
        
        // Create teacher record
        $insertStmt = $conn->prepare("INSERT INTO teacher (userID, email, username, fname, lname, dateCreated, isActive) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
        if (!$insertStmt) {
            if ($debug_mode) {
                echo "<div class='debug-error'>Insert statement prepare failed: " . $conn->error . "</div>";
            }
            return null;
        }
        
        $username = $user['email']; // Use email as username
        $insertStmt->bind_param("issss", $user_id, $user['email'], $username, $user['fname'], $user['lname']);
        
        if ($insertStmt->execute()) {
            $teacher_id = $insertStmt->insert_id;
            $insertStmt->close();
            
            if ($debug_mode) {
                echo "<div class='debug-success'>✅ Teacher record created successfully! Teacher ID: {$teacher_id}</div>";
            }
            
            return $teacher_id;
        } else {
            if ($debug_mode) {
                echo "<div class='debug-error'>Insert execution failed: " . $insertStmt->error . "</div>";
            }
            $insertStmt->close();
        }
    } else {
        if ($debug_mode) {
            echo "<div class='debug-error'>No user found with ID: {$user_id} and role 'teacher'</div>";
        }
    }
    
    $userStmt->close();
    return null;
}

// Get teacher ID with better error handling
$actual_teacher_id = getOrCreateTeacherId($conn, $user_id, $debug_mode);

if (!$actual_teacher_id) {
    $error_details = '';
    if ($debug_mode) {
        $error_details = " (User ID: {$user_id}, Role: {$_SESSION['role']})";
        echo '<div style="background: #ffe6e6; padding: 20px; margin: 20px; border: 2px solid #ff0000; border-radius: 5px;">';
        echo '<h3>Authentication Error</h3>';
        echo '<p><strong>Error:</strong> Teacher profile not found or could not be created.' . $error_details . '</p>';
        echo '<p><strong>Possible solutions:</strong></p>';
        echo '<ul>';
        echo '<li>Run the fix script: <a href="../../sql/fix-teacher-mapping.php">../../sql/fix-teacher-mapping.php</a></li>';
        echo '<li>Check if your user account has role = "teacher" in the user table</li>';
        echo '<li>Verify database connection and table structure</li>';
        echo '</ul>';
        echo '<p><a href="../../sql/fix-teacher-mapping.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">→ Run Fix Script</a></p>';
        echo '</div>';
        exit();
    } else {
        $_SESSION['error_message'] = 'Teacher profile not found or inactive.' . $error_details;
        header("Location: ../teacher-dashboard.php");
        exit();
    }
}

if ($debug_mode) {
    echo '<div style="background: #e6ffe6; padding: 10px; margin: 10px; border: 1px solid #00aa00; border-radius: 5px;">';
    echo "<p><strong>✅ Authentication successful!</strong></p>";
    echo "<p>User ID: {$user_id} | Teacher ID: {$actual_teacher_id} | Action: {$action}</p>";
    echo '</div>';
}

// Handle different actions with enhanced error handling
switch ($action) {
    case 'create':
        $pageContent = 'program_details';
        $program = null;
        
        if ($program_id) {
            try {
                $program = getProgram($conn, $program_id, $actual_teacher_id);
                
                if (!$program && $debug_mode) {
                    echo '<div style="background: #fff3cd; padding: 10px; margin: 10px; border: 1px solid #ffc107; border-radius: 5px;">';
                    echo "<p><strong>⚠️ Program not found or access denied</strong></p>";
                    echo "<p>Program ID: {$program_id} | Teacher ID: {$actual_teacher_id}</p>";
                    echo "<p>This could mean the program doesn't exist or doesn't belong to you.</p>";
                    echo '</div>';
                }
            } catch (Exception $e) {
                if ($debug_mode) {
                    echo '<div style="background: #ffe6e6; padding: 10px; margin: 10px; border: 1px solid #ff0000; border-radius: 5px;">';
                    echo "<p><strong>Error loading program:</strong> " . $e->getMessage() . "</p>";
                    echo '</div>';
                }
                $program = null;
            }
        }
        break;
        
    case 'edit_chapter':
        $pageContent = 'chapter_content';
        $program = $program_id ? getProgram($conn, $program_id, $actual_teacher_id) : null;
        $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
        break;
        
    case 'add_story':
        $pageContent = 'story_form';
        $program = $program_id ? getProgram($conn, $program_id, $actual_teacher_id) : null;
        $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
        $story = $story_id ? getStory($conn, $story_id) : null;
        break;
        
    case 'add_quiz':
        $pageContent = 'quiz_form';
        $program = $program_id ? getProgram($conn, $program_id, $actual_teacher_id) : null;
        $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
        $quiz = $chapter_id ? getChapterQuiz($conn, $chapter_id) : null;
        break;
        
    default:
        $pageContent = 'programs_list';
        try {
            $myPrograms = getTeacherPrograms($conn, $actual_teacher_id);
            $allPrograms = getPublishedPrograms($conn);
        } catch (Exception $e) {
            if ($debug_mode) {
                echo '<div style="background: #ffe6e6; padding: 10px; margin: 10px; border: 1px solid #ff0000; border-radius: 5px;">';
                echo "<p><strong>Error loading programs:</strong> " . $e->getMessage() . "</p>";
                echo '</div>';
            }
            $myPrograms = [];
            $allPrograms = [];
        }
        break;
}

// Handle success/error messages
$success = $_SESSION['success_message'] ?? null;
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>

<!-- SweetAlert2 and other dependencies -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">

<?php if ($debug_mode): ?>
<style>
.debug-info { background: #e6f3ff; border: 1px solid #007cba; padding: 10px; margin: 5px 0; border-radius: 3px; }
.debug-success { background: #e6ffe6; border: 1px solid #00aa00; padding: 10px; margin: 5px 0; border-radius: 3px; }
.debug-error { background: #ffe6e6; border: 1px solid #ff0000; padding: 10px; margin: 5px 0; border-radius: 3px; }
</style>
<?php endif; ?>

<div class="page-container">
    <div class="page-content">
        
        <?php if ($pageContent === 'programs_list'): ?>
            <!-- MAIN PROGRAMS LIST VIEW -->
            <section class="content-section">
                <h1 class="section-title md:text-2xl font-bold">My Programs</h1>
                
                <!-- Quick Access Toolbar -->
                <?php include '../../components/quick-access.php'; ?>
                
                <!-- My Programs Section -->
                <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start">
                    <div class="w-full flex gap-[25px] items-center justify-start">
                        <div class="flex items-center gap-[10px] p-[10px] text-company_orange">
                            <i class="ph ph-user-circle text-[24px]"></i>
                            <p class="body-text2-semibold">My Programs</p>
                        </div>
                    </div>
                    
                    <?php if (empty($myPrograms)): ?>
                        <div class="w-full text-center py-8">
                            <p class="text-gray-500">No programs created yet. Create your first program!</p>
                            <a href="?action=create" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                Create New Program
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($myPrograms as $program): ?>
                                <div class="program-card bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <div class="relative">
                                        <img src="<?= !empty($program['thumbnail']) && $program['thumbnail'] !== 'default-thumbnail.jpg' ? '../../uploads/thumbnails/' . htmlspecialchars($program['thumbnail']) : '../../images/default-program.jpg' ?>" 
                                             alt="<?= htmlspecialchars($program['title']) ?>" 
                                             class="w-full h-48 object-cover rounded-t-lg">
                                        <span class="absolute top-2 right-2 px-2 py-1 text-xs rounded-full 
                                            <?= $program['status'] === 'published' ? 'bg-green-100 text-green-800' : 
                                               ($program['status'] === 'pending_review' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                                            <?= ucfirst(str_replace('_', ' ', $program['status'])) ?>
                                        </span>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-lg mb-2"><?= htmlspecialchars($program['title']) ?></h3>
                                        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?= htmlspecialchars(substr($program['description'] ?? '', 0, 100)) ?>...</p>
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="text-lg font-bold text-blue-600">₱<?= number_format($program['price'], 2) ?></span>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"><?= htmlspecialchars($program['category']) ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-gray-500"><?= date('M d, Y', strtotime($program['dateCreated'])) ?></span>
                                            <div class="flex gap-2">
                                                <a href="?action=create&program_id=<?= $program['programID'] ?>" 
                                                   class="text-blue-500 hover:text-blue-700 text-sm">
                                                    <i class="ph ph-pencil-simple"></i> Edit
                                                </a>
                                                <button onclick="viewProgram(<?= $program['programID'] ?>)" 
                                                        class="text-green-500 hover:text-green-700 text-sm">
                                                    <i class="ph ph-eye"></i> View
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Program Library Section -->
                <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start mt-8">
                    <div class="w-full flex gap-[25px] items-center justify-start">
                        <div class="flex items-center gap-[10px] p-[10px] text-company_blue">
                            <i class="ph ph-books text-[24px]"></i>
                            <p class="body-text2-semibold">Program Library</p>
                        </div>
                    </div>
                    
                    <?php if (empty($allPrograms)): ?>
                        <div class="w-full text-center py-8">
                            <p class="text-gray-500">No programs available in the library yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <?php foreach ($allPrograms as $program): ?>
                                <div class="program-card-small bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <img src="<?= !empty($program['thumbnail']) && $program['thumbnail'] !== 'default-thumbnail.jpg' ? '../../uploads/thumbnails/' . htmlspecialchars($program['thumbnail']) : '../../images/default-program.jpg' ?>" 
                                         alt="<?= htmlspecialchars($program['title']) ?>" 
                                         class="w-full h-32 object-cover rounded-t-lg">
                                    <div class="p-3">
                                        <h4 class="font-medium text-sm mb-1"><?= htmlspecialchars($program['title']) ?></h4>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-bold text-blue-600">₱<?= number_format($program['price'], 2) ?></span>
                                            <span class="px-1 py-0.5 bg-gray-100 text-gray-600 text-xs rounded"><?= htmlspecialchars($program['category']) ?></span>
                                        </div>
                                        <button onclick="viewProgram(<?= $program['programID'] ?>)" 
                                                class="mt-2 w-full text-center text-blue-500 hover:text-blue-700 text-xs">
                                            <i class="ph ph-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php elseif ($pageContent === 'program_details'): ?>
        <?php else: ?>
            <!-- OTHER CONTENT SECTIONS -->
            <section class="content-section">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Feature Coming Soon</h2>
                    <p class="text-gray-600 mb-6">This feature is under development and will be available soon.</p>
                    <a href="?action=list" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Back to Programs
                    </a>
                </div>
            </section>
        <?php endif; ?>
        
    </div>
</div>

<!-- Back to Top Button -->
<button type="button" onclick="scrollToTop()" 
        class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition z-50" 
        id="scroll-to-top">
    <i class="ph ph-arrow-up text-xl"></i>
</button>

<!-- Include JavaScript files -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../components/navbar.js"></script>

<script>
// Set global variables for JS
const currentProgramId = <?= json_encode($program_id) ?>;
const currentAction = <?= json_encode($action) ?>;

// Display success/error messages
<?php if ($success): ?>
    Swal.fire({
        title: 'Success!',
        text: '<?= addslashes($success) ?>',
        icon: 'success'
    });
<?php endif; ?>

<?php if ($error): ?>
    Swal.fire({
        title: 'Error!',
        text: '<?= addslashes($error) ?>',
        icon: 'error'
    });
<?php endif; ?>

// Thumbnail preview function
function previewThumbnail() {
    const fileInput = document.getElementById('thumbnail');
    const preview = document.getElementById('thumbnailPreview');
    
    if (fileInput && preview && fileInput.files && fileInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(fileInput.files[0]);
    }
}

// Scroll to top functionality
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('scroll', function() {
    const btn = document.getElementById('scroll-to-top');
    if (btn) {
        if (window.pageYOffset > 300) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    }
});

// View program details
function viewProgram(programId) {
    // For now, redirect to edit page
    window.location.href = `?action=create&program_id=${programId}`;
}
</script>

</body>
</html>