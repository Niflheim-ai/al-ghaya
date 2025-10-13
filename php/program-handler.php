<?php
/**
 * Enhanced Program Handler - Compatible with Existing Schema
 * Processes all program-related operations including creation, updates, chapters, stories, quizzes, etc.
 */

session_start();
require_once 'dbConnection.php';
require_once 'functions.php';
require_once 'program-helpers.php';

// Set JSON content type for all responses
header('Content-Type: application/json');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['userID'];
$teacher_id = getTeacherIdFromSession($conn, $user_id);

if (!$teacher_id) {
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
        
        // ==================== PROGRAM OPERATIONS ====================
        
        case 'create_program':
            $data = [
                'teacherID' => $teacher_id,
                'title' => $_POST['title'] ?? 'New Program',
                'description' => $_POST['description'] ?? '',
                'category' => $_POST['category'] ?? 'beginner',
                'price' => floatval($_POST['price'] ?? 0),
                'status' => $_POST['status'] ?? 'draft',
                'thumbnail' => 'default-thumbnail.jpg'
            ];
            
            // Handle thumbnail upload
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $uploaded_thumbnail = uploadThumbnail($_FILES['thumbnail']);
                if ($uploaded_thumbnail) {
                    $data['thumbnail'] = $uploaded_thumbnail;
                }
            }
            
            $program_id = createProgram($conn, $data);
            if ($program_id) {
                echo json_encode([
                    'success' => true, 
                    'program_id' => $program_id,
                    'message' => 'Program created successfully'
                ]);
            } else {
                throw new Exception('Failed to create program');
            }
            break;
            
        case 'update_program':
            $program_id = intval($_POST['program_id'] ?? 0);
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                throw new Exception('Invalid program or no permission');
            }
            
            $data = [
                'teacherID' => $teacher_id,
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category' => $_POST['category'] ?? 'beginner',
                'price' => floatval($_POST['price'] ?? 0),
                'status' => $_POST['status'] ?? 'draft'
            ];
            
            if (updateProgram($conn, $program_id, $data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Program updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update program');
            }
            break;
            
        case 'get_program':
            $program_id = intval($_POST['program_id'] ?? 0);
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                throw new Exception('Invalid program or no permission');
            }
            
            $program = getProgram($conn, $program_id, $teacher_id);
            if ($program) {
                echo json_encode([
                    'success' => true,
                    'program' => $program
                ]);
            } else {
                throw new Exception('Program not found');
            }
            break;
            
        case 'get_draft_programs':
            $programs = getDraftPrograms($conn, $teacher_id);
            echo json_encode(['success' => true, 'programs' => $programs]);
            break;
            
        case 'submit_for_publishing':
            $program_ids = $_POST['program_ids'] ?? [];
            
            if (empty($program_ids)) {
                throw new Exception('No programs selected');
            }
            
            if (submitForPublishing($conn, $program_ids, $teacher_id)) {
                echo json_encode(['success' => true, 'message' => 'Programs submitted for review successfully']);
            } else {
                throw new Exception('Failed to submit programs for publishing');
            }
            break;
            
        // ==================== CHAPTER OPERATIONS ====================
        
        case 'add_chapter':
            $program_id = intval($_POST['program_id'] ?? 0);
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                throw new Exception('Invalid program or no permission');
            }
            
            $title = $_POST['chapter_title'] ?? 'New Chapter';
            $content = $_POST['chapter_content'] ?? '';
            $question = $_POST['chapter_question'] ?? '';
            
            $chapter_id = addChapter($conn, $program_id, $title, $content, $question);
            if ($chapter_id) {
                $chapters = getChapters($conn, $program_id);
                echo json_encode([
                    'success' => true,
                    'chapter_id' => $chapter_id,
                    'chapters' => $chapters,
                    'message' => 'Chapter added successfully'
                ]);
            } else {
                throw new Exception('Failed to add chapter');
            }
            break;
            
        case 'update_chapter':
            $chapter_id = intval($_POST['chapter_id'] ?? 0);
            $program_id = intval($_POST['program_id'] ?? 0);
            
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                throw new Exception('Invalid program or no permission');
            }
            
            $title = $_POST['chapter_title'] ?? '';
            $content = $_POST['chapter_content'] ?? '';
            $question = $_POST['chapter_question'] ?? '';
            
            if (updateChapter($conn, $chapter_id, $title, $content, $question)) {
                $chapters = getChapters($conn, $program_id);
                echo json_encode([
                    'success' => true,
                    'chapters' => $chapters,
                    'message' => 'Chapter updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update chapter');
            }
            break;
            
        case 'delete_chapter':
            $chapter_id = intval($_POST['chapter_id'] ?? 0);
            $program_id = intval($_POST['program_id'] ?? 0);
            
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                throw new Exception('Invalid program or no permission');
            }
            
            if (deleteChapter($conn, $chapter_id)) {
                $chapters = getChapters($conn, $program_id);
                echo json_encode([
                    'success' => true,
                    'chapters' => $chapters,
                    'message' => 'Chapter deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete chapter');
            }
            break;
            
        case 'get_chapters':
            $program_id = intval($_POST['program_id'] ?? 0);
            if (!$program_id || !verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                throw new Exception('Invalid program or no permission');
            }
            
            $chapters = getChapters($conn, $program_id);
            echo json_encode([
                'success' => true,
                'chapters' => $chapters
            ]);
            break;
            
        case 'get_chapter':
            $chapter_id = intval($_POST['chapter_id'] ?? 0);
            if (!$chapter_id) {
                throw new Exception('Invalid chapter ID');
            }
            
            $chapter = getChapter($conn, $chapter_id);
            if ($chapter) {
                // Verify ownership through program
                if (!verifyProgramOwnership($conn, $chapter['program_id'], $teacher_id)) {
                    throw new Exception('No permission to access this chapter');
                }
                
                echo json_encode([
                    'success' => true,
                    'chapter' => $chapter
                ]);
            } else {
                throw new Exception('Chapter not found');
            }
            break;
            
        // ==================== VALIDATION OPERATIONS ====================
        
        case 'validate_youtube_url':
            $url = $_POST['url'] ?? '';
            $is_valid = validateYouTubeUrl($url);
            $video_id = $is_valid ? getYouTubeVideoId($url) : null;
            
            echo json_encode([
                'success' => true,
                'is_valid' => $is_valid,
                'video_id' => $video_id
            ]);
            break;
            
        // ==================== STORY OPERATIONS ====================
        
        case 'add_story':
            $chapter_id = intval($_POST['chapter_id'] ?? 0);
            if (!$chapter_id) {
                throw new Exception('Invalid chapter ID');
            }
            
            // Verify chapter ownership through program
            $chapter = getChapter($conn, $chapter_id);
            if (!$chapter || !verifyProgramOwnership($conn, $chapter['program_id'], $teacher_id)) {
                throw new Exception('No permission to modify this chapter');
            }
            
            // For now, return success (stories table may not exist yet)
            echo json_encode([
                'success' => true,
                'message' => 'Story functionality will be available soon'
            ]);
            break;
            
        // ==================== QUIZ OPERATIONS ====================
        
        case 'add_quiz':
            $chapter_id = intval($_POST['chapter_id'] ?? 0);
            if (!$chapter_id) {
                throw new Exception('Invalid chapter ID');
            }
            
            // Verify chapter ownership through program
            $chapter = getChapter($conn, $chapter_id);
            if (!$chapter || !verifyProgramOwnership($conn, $chapter['program_id'], $teacher_id)) {
                throw new Exception('No permission to modify this chapter');
            }
            
            // For now, return success (quiz table may not exist yet)
            echo json_encode([
                'success' => true,
                'message' => 'Quiz functionality will be available soon'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("Program Handler Error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Get draft programs for a teacher
 */
function getDraftPrograms($conn, $teacher_id) {
    $stmt = $conn->prepare("SELECT programID, title, price, category FROM programs WHERE teacherID = ? AND status = 'draft' ORDER BY dateCreated DESC");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $programs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $programs;
}

/**
 * Submit programs for publishing
 */
function submitForPublishing($conn, $program_ids, $teacher_id) {
    $conn->begin_transaction();
    
    try {
        foreach ($program_ids as $program_id) {
            // Verify ownership before updating
            if (!verifyProgramOwnership($conn, $program_id, $teacher_id)) {
                throw new Exception('No permission to publish program ID: ' . $program_id);
            }
            
            $stmt = $conn->prepare("UPDATE programs SET status = 'published', dateUpdated = NOW() WHERE programID = ? AND teacherID = ?");
            if (!$stmt) {
                throw new Exception('Database prepare failed');
            }
            
            $stmt->bind_param("ii", $program_id, $teacher_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update program ' . $program_id);
            }
            $stmt->close();
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>