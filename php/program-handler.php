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
    if (isset($_POST['action']) && in_array($_POST['action'], ['create_program', 'update_program', 'create_story'])) {
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
    if (isset($_POST['action']) && in_array($_POST['action'], ['create_program', 'update_program', 'create_story'])) {
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
                'difficulty_label' => $_POST['difficulty_level'] ?? 'Student',
                'category' => mapDifficultyToCategory($_POST['difficulty_level'] ?? 'Student'),
                'price' => floatval($_POST['price'] ?? 0),
                'status' => $_POST['status'] ?? 'draft',
                'thumbnail' => 'default-thumbnail.jpg',
                'overview_video_url' => trim($_POST['overview_video_url'] ?? '')
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
                'difficulty_label' => $_POST['difficulty_level'] ?? 'Student',
                'category' => mapDifficultyToCategory($_POST['difficulty_level'] ?? 'Student'),
                'price' => floatval($_POST['price'] ?? 0),
                'status' => $_POST['status'] ?? 'draft',
                'overview_video_url' => trim($_POST['overview_video_url'] ?? '')
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
            
            // Check story count limit (1-3 stories per chapter)
            $existingStories = getChapterStories($conn, $chapter_id);
            if (count($existingStories) >= 3) {
                $_SESSION['error_message'] = 'Maximum of 3 stories per chapter allowed.';
                header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
                exit;
            }
            
            // Create story record using the chapter_stories table (from database schema)
            $story_id = createStoryRecord($conn, [
                'chapter_id' => $chapter_id,
                'program_id' => $program_id,
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
                    'program_id' => $program_id,
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
            
            // Verify story ownership through chapter and program
            $story = getStoryById($conn, $story_id);
            if (!$story) {
                echo json_encode(['success' => false, 'message' => 'Story not found']);
                exit;
            }
            
            $chapter = getChapter($conn, $story['chapter_id']);
            if (!$chapter || !verifyProgramOwnership($conn, $chapter['program_id'], $teacher_id)) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }
            
            // Check minimum story requirement (at least 1 story per chapter)
            $existingStories = getChapterStories($conn, $story['chapter_id']);
            if (count($existingStories) <= 1) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete the last story. Each chapter must have at least 1 story.']);
                exit;
            }
            
            if (deleteStoryRecord($conn, $story_id)) {
                echo json_encode(['success' => true, 'message' => 'Story deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete story']);
            }
            exit;
            break;
            
        case 'create_interactive_section':
            header('Content-Type: application/json');
            $story_id = intval($_POST['story_id'] ?? 0);
            
            if (!$story_id) {
                echo json_encode(['success' => false, 'message' => 'Story ID required']);
                exit;
            }
            
            // Verify story ownership
            $story = getStoryById($conn, $story_id);
            if (!$story) {
                echo json_encode(['success' => false, 'message' => 'Story not found']);
                exit;
            }
            
            $chapter = getChapter($conn, $story['chapter_id']);
            if (!$chapter || !verifyProgramOwnership($conn, $chapter['program_id'], $teacher_id)) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }
            
            // Check section limit (max 3 per story)
            $existingSections = getStoryInteractiveSections($conn, $story_id);
            if (count($existingSections) >= 3) {
                echo json_encode(['success' => false, 'message' => 'Maximum of 3 interactive sections per story allowed']);
                exit;
            }
            
            $section_id = createInteractiveSection($conn, $story_id);
            if ($section_id) {
                echo json_encode([
                    'success' => true,
                    'section_id' => $section_id,
                    'message' => 'Interactive section created successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create interactive section']);
            }
            exit;
            break;
            
        case 'delete_interactive_section':
            header('Content-Type: application/json');
            $section_id = intval($_POST['section_id'] ?? 0);
            
            if (!$section_id) {
                echo json_encode(['success' => false, 'message' => 'Section ID required']);
                exit;
            }
            
            // Implementation for deleting interactive section
            if (deleteInteractiveSection($conn, $section_id)) {
                echo json_encode(['success' => true, 'message' => 'Interactive section deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete interactive section']);
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
 * Map difficulty level to category
 * @param string $difficulty_level Difficulty level (Student, Aspiring, Master)
 * @return string Category (beginner, intermediate, advanced)
 */
function mapDifficultyToCategory($difficulty_level) {
    switch ($difficulty_level) {
        case 'Student':
            return 'beginner';
        case 'Aspiring':
            return 'intermediate';
        case 'Master':
            return 'advanced';
        default:
            return 'beginner';
    }
}

/**
 * Create a new story record using chapter_stories table (from database schema)
 * @param object $conn Database connection
 * @param array $data Story data
 * @return int|false Story ID on success, false on failure
 */
function createStoryRecord($conn, $data) {
    // Check if chapter_stories table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_stories'");
    if ($tableCheck->num_rows == 0) {
        error_log("chapter_stories table does not exist");
        return false;
    }
    
    // Get the next story order for this chapter
    $orderQuery = "SELECT COALESCE(MAX(story_order), 0) + 1 as next_order FROM chapter_stories WHERE chapter_id = ?";
    $orderStmt = $conn->prepare($orderQuery);
    if (!$orderStmt) {
        error_log("createStoryRecord order query prepare failed: " . $conn->error);
        return false;
    }
    
    $orderStmt->bind_param("i", $data['chapter_id']);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $next_order = $orderResult->fetch_assoc()['next_order'];
    $orderStmt->close();
    
    // Insert the story using the actual database schema
    $sql = "INSERT INTO chapter_stories (chapter_id, title, synopsis_arabic, synopsis_english, video_url, story_order, dateCreated) 
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
 * Delete a story record from chapter_stories table
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return bool True on success, false on failure
 */
function deleteStoryRecord($conn, $story_id) {
    // First delete all related interactive sections
    deleteStoryInteractiveSections($conn, $story_id);
    
    $stmt = $conn->prepare("DELETE FROM chapter_stories WHERE story_id = ?");
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

/**
 * Get story by ID from chapter_stories table
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return array|null Story data or null if not found
 */
function getStoryById($conn, $story_id) {
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE story_id = ?");
    if (!$stmt) {
        error_log("getStoryById prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $story_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $story = $result->fetch_assoc();
    $stmt->close();
    
    return $story;
}

/**
 * Create interactive section for a story
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return int|false Section ID on success, false on failure
 */
function createInteractiveSection($conn, $story_id) {
    // Check if story_interactive_sections table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'story_interactive_sections'");
    if ($tableCheck->num_rows == 0) {
        error_log("story_interactive_sections table does not exist");
        return false;
    }
    
    // Get the next section order
    $orderQuery = "SELECT COALESCE(MAX(section_order), 0) + 1 as next_order FROM story_interactive_sections WHERE story_id = ?";
    $orderStmt = $conn->prepare($orderQuery);
    if (!$orderStmt) {
        error_log("createInteractiveSection order query prepare failed: " . $conn->error);
        return false;
    }
    
    $orderStmt->bind_param("i", $story_id);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $next_order = $orderResult->fetch_assoc()['next_order'];
    $orderStmt->close();
    
    // Insert the section
    $sql = "INSERT INTO story_interactive_sections (story_id, section_order, dateCreated) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("createInteractiveSection prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $story_id, $next_order);
    
    if ($stmt->execute()) {
        $section_id = $stmt->insert_id;
        $stmt->close();
        return $section_id;
    }
    
    error_log("createInteractiveSection execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Delete interactive section
 * @param object $conn Database connection
 * @param int $section_id Section ID
 * @return bool True on success, false on failure
 */
function deleteInteractiveSection($conn, $section_id) {
    // First delete all questions in this section
    $stmt1 = $conn->prepare("DELETE FROM interactive_questions WHERE section_id = ?");
    if ($stmt1) {
        $stmt1->bind_param("i", $section_id);
        $stmt1->execute();
        $stmt1->close();
    }
    
    // Then delete the section
    $stmt = $conn->prepare("DELETE FROM story_interactive_sections WHERE section_id = ?");
    if (!$stmt) {
        error_log("deleteInteractiveSection prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $section_id);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }
    
    error_log("deleteInteractiveSection execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Delete all interactive sections for a story
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return bool True on success, false on failure
 */
function deleteStoryInteractiveSections($conn, $story_id) {
    // Get all sections for this story
    $sections = getStoryInteractiveSections($conn, $story_id);
    
    foreach ($sections as $section) {
        deleteInteractiveSection($conn, $section['section_id']);
    }
    
    return true;
}

/**
 * Update program with support for new fields
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param array $data Program data
 * @return bool True on success, false on failure
 */
function updateProgram($conn, $program_id, $data) {
    $sql = "UPDATE programs SET title = ?, description = ?, difficulty_label = ?, category = ?, price = ?, status = ?, overview_video_url = ?, dateUpdated = NOW()
            WHERE programID = ? AND teacherID = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("updateProgram prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ssssdssii", 
        $data['title'],
        $data['description'],
        $data['difficulty_label'],
        $data['category'],
        $data['price'],
        $data['status'],
        $data['overview_video_url'],
        $program_id,
        $data['teacherID']
    );
    
    if ($stmt->execute()) {
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
    
    error_log("updateProgram execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Create program with support for new fields
 * @param object $conn Database connection
 * @param array $data Program data
 * @return int|false Program ID on success, false on failure
 */
function createProgram($conn, $data) {
    $sql = "INSERT INTO programs (teacherID, title, description, difficulty_label, category, price, thumbnail, status, overview_video_url, dateCreated, dateUpdated) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("createProgram prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issssddss", 
        $data['teacherID'],
        $data['title'],
        $data['description'],
        $data['difficulty_label'],
        $data['category'],
        $data['price'],
        $data['thumbnail'],
        $data['status'],
        $data['overview_video_url']
    );
    
    if ($stmt->execute()) {
        $program_id = $stmt->insert_id;
        $stmt->close();
        return $program_id;
    }
    
    error_log("createProgram execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

?>