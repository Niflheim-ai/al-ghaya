<?php
/**
 * Enhanced Program Handler - Compatible with New Components
 * Processes all program-related operations including creation, updates, chapters, stories, quizzes, etc.
 */

session_start();
require_once 'dbConnection.php';
require_once 'functions.php';
require_once 'program-helpers.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    if (isset($_POST['action']) && in_array($_POST['action'], ['create_program', 'update_program'])) {
        $_SESSION['error_message'] = 'Unauthorized access';
        header('Location: ../pages/teacher/teacher-programs.php');
        exit;
    }
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['userID'];
$teacher_id = getTeacherIdFromSession($conn, $user_id);

if (!$teacher_id) {
    if (isset($_POST['action']) && in_array($_POST['action'], ['create_program', 'update_program'])) {
        $_SESSION['error_message'] = 'Teacher profile not found';
        header('Location: ../pages/teacher/teacher-programs.php');
        exit;
    }
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Teacher profile not found']);
    exit;
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle JSON requests
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $action = $input['action'] ?? $action;
        $_POST = array_merge($_POST, $input);
    }
}

try {
    switch ($action) {
        
        // ==================== PROGRAM OPERATIONS (FORM SUBMISSIONS) ====================
        
        case 'create_program':
            // Handle form-based program creation
            $data = [
                'teacherID' => $teacher_id,
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => $_POST['difficulty_level'] ?? 'Student', // Map difficulty_level to category
                'price' => floatval($_POST['price'] ?? 0),
                'status' => $_POST['status'] ?? 'draft',
                'thumbnail' => 'default-thumbnail.jpg'
            ];
            
            // Validate required fields
            if (empty($data['title']) || strlen($data['title']) < 3) {
                $_SESSION['error_message'] = 'Program title must be at least 3 characters long';
                header('Location: ../pages/teacher/teacher-programs.php?action=create');
                exit;
            }
            
            if (empty($data['description']) || strlen($data['description']) < 10) {
                $_SESSION['error_message'] = 'Program description must be at least 10 characters long';
                header('Location: ../pages/teacher/teacher-programs.php?action=create');
                exit;
            }
            
            // Handle thumbnail upload
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $uploaded_thumbnail = uploadThumbnail($_FILES['thumbnail']);
                if ($uploaded_thumbnail) {
                    $data['thumbnail'] = $uploaded_thumbnail;
                }
            }
            
            $program_id = createProgram($conn, $data);
            if ($program_id) {
                // Create initial chapter
                addChapter($conn, $program_id, 'Introduction', 'Welcome to this program!', '');
                
                $_SESSION['success_message'] = 'Program created successfully!';
                header('Location: ../pages/teacher/teacher-programs.php?action=create&program_id=' . $program_id);
                exit;
            } else {
                $_SESSION['error_message'] = 'Failed to create program. Please try again.';
                header('Location: ../pages/teacher/teacher-programs.php?action=create');
                exit;
            }
            break;
            
        case 'update_program':
            // Handle form-based program updates
            $program_id = intval($_POST['program_id'] ?? 0);
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                $_SESSION['error_message'] = 'Invalid program or access denied.';
                header('Location: ../pages/teacher/teacher-programs.php');
                exit;
            }
            
            $data = [
                'teacherID' => $teacher_id,
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => $_POST['difficulty_level'] ?? 'Student',
                'price' => floatval($_POST['price'] ?? 0),
                'status' => $_POST['status'] ?? 'draft'
            ];
            
            // Validate required fields
            if (empty($data['title']) || strlen($data['title']) < 3) {
                $_SESSION['error_message'] = 'Program title must be at least 3 characters long';
                header('Location: ../pages/teacher/teacher-programs.php?action=create&program_id=' . $program_id);
                exit;
            }
            
            if (empty($data['description']) || strlen($data['description']) < 10) {
                $_SESSION['error_message'] = 'Program description must be at least 10 characters long';
                header('Location: ../pages/teacher/teacher-programs.php?action=create&program_id=' . $program_id);
                exit;
            }
            
            // Handle thumbnail upload
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $uploaded_thumbnail = uploadThumbnail($_FILES['thumbnail']);
                if ($uploaded_thumbnail) {
                    $data['thumbnail'] = $uploaded_thumbnail;
                }
            }
            
            if (updateProgram($conn, $program_id, $data)) {
                $_SESSION['success_message'] = 'Program updated successfully!';
            } else {
                $_SESSION['error_message'] = 'No changes made or error updating program.';
            }
            
            header('Location: ../pages/teacher/teacher-programs.php?action=create&program_id=' . $program_id);
            exit;
            break;
            
        case 'create_story':
            // Handle form-based story creation
            $program_id = intval($_POST['program_id'] ?? 0);
            $chapter_id = intval($_POST['chapter_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $synopsis_arabic = trim($_POST['synopsis_arabic'] ?? '');
            $synopsis_english = trim($_POST['synopsis_english'] ?? '');
            $video_url = trim($_POST['video_url'] ?? '');
            
            // Validate required fields
            if (empty($title) || empty($synopsis_arabic) || empty($synopsis_english) || empty($video_url)) {
                $_SESSION['error_message'] = 'All fields are required for the story.';
                header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
                exit;
            }
            
            if (!$program_id || !$chapter_id) {
                $_SESSION['error_message'] = 'Invalid program or chapter ID.';
                header('Location: ../pages/teacher/teacher-programs.php');
                exit;
            }
            
            // Verify ownership
            if (!verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                $_SESSION['error_message'] = 'Access denied to this program.';
                header('Location: ../pages/teacher/teacher-programs.php');
                exit;
            }
            
            // Create story record (need to add this table if it doesn't exist)
            $story_id = createStoryRecord($conn, [
                'chapter_id' => $chapter_id,
                'title' => $title,
                'synopsis_arabic' => $synopsis_arabic,
                'synopsis_english' => $synopsis_english,
                'video_url' => $video_url
            ]);
            
            if ($story_id) {
                $_SESSION['success_message'] = 'Story created successfully!';
                header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id);
                exit;
            } else {
                $_SESSION['error_message'] = 'Failed to save story. Please try again.';
                header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
                exit;
            }
            break;
            
        // ==================== AJAX OPERATIONS (JSON RESPONSES) ====================
        
        case 'create_chapter':
            header('Content-Type: application/json');
            $program_id = intval($_POST['program_id'] ?? 0);
            $title = trim($_POST['title'] ?? 'New Chapter');
            
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                echo json_encode(['success' => false, 'message' => 'Invalid program or no permission']);
                exit;
            }
            
            $chapter_id = addChapter($conn, $program_id, $title, '', '');
            if ($chapter_id) {
                echo json_encode([
                    'success' => true,
                    'chapter_id' => $chapter_id,
                    'message' => 'Chapter created successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create chapter']);
            }
            exit;
            break;
            
        case 'delete_chapter':
            header('Content-Type: application/json');
            $chapter_id = intval($_POST['chapter_id'] ?? 0);
            $program_id = intval($_POST['program_id'] ?? 0);
            
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                echo json_encode(['success' => false, 'message' => 'Invalid program or no permission']);
                exit;
            }
            
            if (deleteChapter($conn, $chapter_id)) {
                echo json_encode(['success' => true, 'message' => 'Chapter deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete chapter']);
            }
            exit;
            break;
            
        case 'delete_story':
            header('Content-Type: application/json');
            $story_id = intval($_POST['story_id'] ?? 0);
            
            if (!$story_id) {
                echo json_encode(['success' => false, 'message' => 'Story ID required']);
                exit;
            }
            
            if (deleteStoryRecord($conn, $story_id)) {
                echo json_encode(['success' => true, 'message' => 'Story deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete story']);
            }
            exit;
            break;
            
        case 'validate_youtube_url':
            header('Content-Type: application/json');
            $url = $_POST['url'] ?? '';
            $is_valid = validateYouTubeUrl($url);
            $video_id = $is_valid ? getYouTubeVideoId($url) : null;
            
            echo json_encode([
                'success' => true,
                'is_valid' => $is_valid,
                'video_id' => $video_id
            ]);
            exit;
            break;
            
        case 'get_chapters':
            header('Content-Type: application/json');
            $program_id = intval($_POST['program_id'] ?? 0);
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                echo json_encode(['success' => false, 'message' => 'Invalid program or no permission']);
                exit;
            }
            
            $chapters = getChapters($conn, $program_id);
            echo json_encode(['success' => true, 'chapters' => $chapters]);
            exit;
            break;
            
        default:
            if (in_array($action, ['create_program', 'update_program', 'create_story'])) {
                $_SESSION['error_message'] = 'Invalid action: ' . $action;
                header('Location: ../pages/teacher/teacher-programs.php');
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
                exit;
            }
    }
    
} catch (Exception $e) {
    error_log("Program Handler Error: " . $e->getMessage());
    
    if (in_array($action, ['create_program', 'update_program', 'create_story'])) {
        $_SESSION['error_message'] = 'Server error: ' . $e->getMessage();
        header('Location: ../pages/teacher/teacher-programs.php');
        exit;
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

/**
 * Create a new story record
 */
function createStoryRecord($conn, $data) {
    // Check if program_stories table exists, create if needed
    $tableCheck = $conn->query("SHOW TABLES LIKE 'program_stories'");
    if ($tableCheck->num_rows == 0) {
        $createTable = "
            CREATE TABLE program_stories (
                story_id INT PRIMARY KEY AUTO_INCREMENT,
                chapter_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                synopsis_arabic TEXT,
                synopsis_english TEXT,
                video_url VARCHAR(500),
                story_order INT DEFAULT 1,
                dateCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (chapter_id) REFERENCES program_chapters(chapter_id) ON DELETE CASCADE
            )
        ";
        
        if (!$conn->query($createTable)) {
            error_log("Failed to create program_stories table: " . $conn->error);
            return false;
        }
    }
    
    // Get the next story order for this chapter
    $orderQuery = "SELECT COALESCE(MAX(story_order), 0) + 1 as next_order FROM program_stories WHERE chapter_id = ?";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->bind_param("i", $data['chapter_id']);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $next_order = $orderResult->fetch_assoc()['next_order'];
    $orderStmt->close();
    
    // Insert the story
    $sql = "INSERT INTO program_stories (chapter_id, title, synopsis_arabic, synopsis_english, video_url, story_order, dateCreated) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("createStoryRecord prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issssi", 
        $data['chapter_id'],
        $data['title'],
        $data['synopsis_arabic'],
        $data['synopsis_english'],
        $data['video_url'],
        $next_order
    );
    
    if ($stmt->execute()) {
        $story_id = $stmt->insert_id;
        $stmt->close();
        return $story_id;
    }
    
    error_log("createStoryRecord execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Delete a story record
 */
function deleteStoryRecord($conn, $story_id) {
    $stmt = $conn->prepare("DELETE FROM program_stories WHERE story_id = ?");
    if (!$stmt) {
        error_log("deleteStoryRecord prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $story_id);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }
    
    error_log("deleteStoryRecord execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}
?>