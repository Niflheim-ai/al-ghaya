<?php
/**
 * Enhanced Program Handler - Compatible with Existing Schema
 * Processes all program-related operations including creation, updates, chapters, stories, quizzes, etc.
 */

session_start();
require_once 'dbConnection.php';
require_once 'enhanced-program-functions.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$user_id = $_SESSION['userID'];
$teacher_id = getTeacherIdFromSession($conn, $user_id);

if (!$teacher_id) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Teacher profile not found']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle JSON requests
if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
} else {
    $input = $_POST;
}

// Set content type for JSON responses
header('Content-Type: application/json');

try {
    switch ($action) {
        
        // ==================== PROGRAM OPERATIONS ====================
        
        case 'create_program':
            $data = [
                'teacherID' => $teacher_id,
                'title' => $input['title'] ?? '',
                'description' => $input['description'] ?? '',
                'category' => mapDifficultyToCategory($input['difficulty_level'] ?? 'Student'),
                'difficulty_level' => $input['difficulty_level'] ?? 'Student',
                'price' => floatval($input['price'] ?? 0),
                'overview_video_url' => $input['overview_video_url'] ?? '',
                'status' => $input['status'] ?? 'draft',
                'currency' => 'PHP',
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
                $_SESSION['success_message'] = 'Program created successfully!';
                if (isset($_POST['action'])) {
                    header("Location: ../pages/teacher/teacher-programs-enhanced.php?action=create&program_id={$program_id}");
                    exit();
                }
                echo json_encode(['success' => true, 'program_id' => $program_id]);
            } else {
                throw new Exception('Failed to create program');
            }
            break;
            
        case 'get_draft_programs':
            $programs = getDraftPrograms($conn, $teacher_id);
            echo json_encode(['success' => true, 'programs' => $programs]);
            break;
            
        case 'submit_for_publishing':
            $program_ids = $input['program_ids'] ?? [];
            
            if (empty($program_ids)) {
                throw new Exception('No programs selected');
            }
            
            if (submitForPublishing($conn, $program_ids, $teacher_id)) {
                echo json_encode(['success' => true, 'message' => 'Programs submitted for review successfully']);
            } else {
                throw new Exception('Failed to submit programs for publishing');
            }
            break;
            
        case 'validate_youtube_url':
            $url = $input['url'] ?? '';
            $is_valid = validateYouTubeUrl($url);
            $video_id = $is_valid ? getYouTubeVideoId($url) : null;
            
            echo json_encode([
                'success' => true,
                'is_valid' => $is_valid,
                'video_id' => $video_id
            ]);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("Program Handler Error: " . $e->getMessage());
    
    if (isset($_POST['action'])) {
        // Form submission - set error message and redirect
        $_SESSION['error_message'] = $e->getMessage();
        
        $redirect_url = '../pages/teacher/teacher-programs-enhanced.php';
        if (isset($_POST['program_id'])) {
            $redirect_url .= '?action=create&program_id=' . $_POST['program_id'];
        }
        
        header("Location: {$redirect_url}");
        exit();
    } else {
        // AJAX request - return JSON error
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function mapDifficultyToCategory($difficulty) {
    switch ($difficulty) {
        case 'Student': return 'beginner';
        case 'Aspiring': return 'intermediate';
        case 'Master': return 'advanced';
        default: return 'beginner';
    }
}

function getDraftPrograms($conn, $teacher_id) {
    $stmt = $conn->prepare("SELECT programID, title, price, category FROM programs WHERE teacherID = ? AND status = 'draft'");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function submitForPublishing($conn, $program_ids, $teacher_id) {
    $conn->begin_transaction();
    
    try {
        foreach ($program_ids as $program_id) {
            $stmt = $conn->prepare("UPDATE programs SET status = 'published' WHERE programID = ? AND teacherID = ?");
            $stmt->bind_param("ii", $program_id, $teacher_id);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
?>