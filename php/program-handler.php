<?php
/**
 * Enhanced Program Handler
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

$teacher_id = $_SESSION['userID'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle JSON requests
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
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
                'category' => $input['difficulty_level'] ?? '',
                'price' => floatval($input['price'] ?? 0),
                'overview_video_url' => $input['overview_video_url'] ?? '',
                'difficulty_level' => $input['difficulty_level'] ?? '',
                'status' => $input['status'] ?? 'draft',
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
            
        case 'update_program':
            $program_id = intval($input['program_id'] ?? 0);
            
            // Verify ownership
            $existing_program = getProgram($conn, $program_id, $teacher_id);
            if (!$existing_program) {
                throw new Exception('Program not found or access denied');
            }
            
            $data = [
                'teacherID' => $teacher_id,
                'title' => $input['title'] ?? '',
                'description' => $input['description'] ?? '',
                'category' => $input['difficulty_level'] ?? '',
                'price' => floatval($input['price'] ?? 0),
                'overview_video_url' => $input['overview_video_url'] ?? '',
                'difficulty_level' => $input['difficulty_level'] ?? '',
                'status' => $input['status'] ?? 'draft'
            ];
            
            if (updateProgram($conn, $program_id, $data)) {
                $_SESSION['success_message'] = 'Program updated successfully!';
                if (isset($_POST['action'])) {
                    header("Location: ../pages/teacher/teacher-programs-enhanced.php?action=create&program_id={$program_id}");
                    exit();
                }
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to update program');
            }
            break;
            
        case 'delete_program':
            $program_id = intval($input['program_id'] ?? 0);
            
            if (deleteProgram($conn, $program_id, $teacher_id)) {
                echo json_encode(['success' => true, 'message' => 'Program deleted successfully']);
            } else {
                throw new Exception('Failed to delete program');
            }
            break;
            
        // ==================== CHAPTER OPERATIONS ====================
        
        case 'create_chapter':
            $program_id = intval($input['program_id'] ?? 0);
            $title = $input['title'] ?? '';
            
            // Verify program ownership
            $program = getProgram($conn, $program_id, $teacher_id);
            if (!$program) {
                throw new Exception('Program not found or access denied');
            }
            
            $chapter_id = createChapter($conn, $program_id, $title);
            if ($chapter_id) {
                echo json_encode(['success' => true, 'chapter_id' => $chapter_id]);
            } else {
                throw new Exception('Failed to create chapter');
            }
            break;
            
        case 'update_chapter':
            $chapter_id = intval($input['chapter_id'] ?? 0);
            $title = $input['title'] ?? '';
            
            if (updateChapter($conn, $chapter_id, $title)) {
                echo json_encode(['success' => true, 'message' => 'Chapter updated successfully']);
            } else {
                throw new Exception('Failed to update chapter');
            }
            break;
            
        case 'delete_chapter':
            $chapter_id = intval($input['chapter_id'] ?? 0);
            
            if (deleteChapter($conn, $chapter_id)) {
                echo json_encode(['success' => true, 'message' => 'Chapter deleted successfully']);
            } else {
                throw new Exception('Failed to delete chapter');
            }
            break;
            
        // ==================== STORY OPERATIONS ====================
        
        case 'create_story':
            $data = [
                'chapter_id' => intval($input['chapter_id'] ?? 0),
                'title' => $input['title'] ?? '',
                'synopsis_arabic' => $input['synopsis_arabic'] ?? '',
                'synopsis_english' => $input['synopsis_english'] ?? '',
                'video_url' => $input['video_url'] ?? ''
            ];
            
            // Validate YouTube URL
            if (!validateYouTubeUrl($data['video_url'])) {
                throw new Exception('Please provide a valid YouTube URL');
            }
            
            $story_id = createStory($conn, $data);
            if ($story_id) {
                $_SESSION['success_message'] = 'Story created successfully!';
                if (isset($_POST['action'])) {
                    $program_id = $input['program_id'];
                    $chapter_id = $input['chapter_id'];
                    header("Location: ../pages/teacher/teacher-programs-enhanced.php?action=add_story&program_id={$program_id}&chapter_id={$chapter_id}&story_id={$story_id}");
                    exit();
                }
                echo json_encode(['success' => true, 'story_id' => $story_id]);
            } else {
                throw new Exception('Failed to create story');
            }
            break;
            
        case 'update_story':
            $story_id = intval($input['story_id'] ?? 0);
            $data = [
                'title' => $input['title'] ?? '',
                'synopsis_arabic' => $input['synopsis_arabic'] ?? '',
                'synopsis_english' => $input['synopsis_english'] ?? '',
                'video_url' => $input['video_url'] ?? ''
            ];
            
            // Validate YouTube URL
            if (!validateYouTubeUrl($data['video_url'])) {
                throw new Exception('Please provide a valid YouTube URL');
            }
            
            if (updateStory($conn, $story_id, $data)) {
                $_SESSION['success_message'] = 'Story updated successfully!';
                if (isset($_POST['action'])) {
                    $program_id = $input['program_id'];
                    $chapter_id = $input['chapter_id'];
                    header("Location: ../pages/teacher/teacher-programs-enhanced.php?action=edit_chapter&program_id={$program_id}&chapter_id={$chapter_id}");
                    exit();
                }
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to update story');
            }
            break;
            
        case 'delete_story':
            $story_id = intval($input['story_id'] ?? 0);
            
            if (deleteStory($conn, $story_id)) {
                echo json_encode(['success' => true, 'message' => 'Story deleted successfully']);
            } else {
                throw new Exception('Failed to delete story');
            }
            break;
            
        // ==================== INTERACTIVE SECTION OPERATIONS ====================
        
        case 'create_interactive_section':
            $story_id = intval($input['story_id'] ?? 0);
            
            $section_id = createInteractiveSection($conn, $story_id);
            if ($section_id) {
                echo json_encode(['success' => true, 'section_id' => $section_id]);
            } else {
                throw new Exception('Failed to create interactive section or maximum limit reached');
            }
            break;
            
        case 'delete_interactive_section':
            $section_id = intval($input['section_id'] ?? 0);
            
            $stmt = $conn->prepare("DELETE FROM story_interactive_sections WHERE section_id = ?");
            $stmt->bind_param("i", $section_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Interactive section deleted successfully']);
            } else {
                throw new Exception('Failed to delete interactive section');
            }
            break;
            
        // ==================== QUESTION OPERATIONS ====================
        
        case 'create_question':
            $section_id = intval($input['section_id'] ?? 0);
            $question_text = $input['question_text'] ?? '';
            $question_type = $input['question_type'] ?? '';
            $options = $input['options'] ?? [];
            
            if (empty($question_text) || empty($question_type)) {
                throw new Exception('Question text and type are required');
            }
            
            $question_id = createQuestion($conn, $section_id, $question_text, $question_type);
            if ($question_id) {
                // Add options if provided
                if (!empty($options)) {
                    foreach ($options as $option_text) {
                        if (!empty(trim($option_text))) {
                            addQuestionOption($conn, $question_id, trim($option_text));
                        }
                    }
                }
                echo json_encode(['success' => true, 'question_id' => $question_id]);
            } else {
                throw new Exception('Failed to create question');
            }
            break;
            
        case 'delete_question':
            $question_id = intval($input['question_id'] ?? 0);
            
            $stmt = $conn->prepare("DELETE FROM interactive_questions WHERE question_id = ?");
            $stmt->bind_param("i", $question_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
            } else {
                throw new Exception('Failed to delete question');
            }
            break;
            
        case 'set_correct_answer':
            $question_id = intval($input['question_id'] ?? 0);
            $correct_option_index = intval($input['correct_option_index'] ?? 0);
            
            // Get all options for this question
            $options = getQuestionOptions($conn, $question_id);
            if (isset($options[$correct_option_index])) {
                $option_id = $options[$correct_option_index]['option_id'];
                if (setCorrectAnswer($conn, $question_id, $option_id)) {
                    echo json_encode(['success' => true, 'message' => 'Answer key set successfully']);
                } else {
                    throw new Exception('Failed to set answer key');
                }
            } else {
                throw new Exception('Invalid option index');
            }
            break;
            
        // ==================== QUIZ OPERATIONS ====================
        
        case 'add_quiz_question':
            $quiz_id = intval($input['quiz_id'] ?? 0);
            $question_text = $input['question_text'] ?? '';
            $options = $input['options'] ?? [];
            
            if (empty($question_text)) {
                throw new Exception('Question text is required');
            }
            
            $question_id = addQuizQuestion($conn, $quiz_id, $question_text);
            if ($question_id) {
                // Add options
                if (!empty($options)) {
                    foreach ($options as $option_data) {
                        $option_text = $option_data['text'] ?? '';
                        $is_correct = $option_data['is_correct'] ?? false;
                        
                        if (!empty(trim($option_text))) {
                            addQuizQuestionOption($conn, $question_id, trim($option_text), $is_correct);
                        }
                    }
                }
                echo json_encode(['success' => true, 'question_id' => $question_id]);
            } else {
                throw new Exception('Failed to create quiz question or maximum limit reached');
            }
            break;
            
        case 'delete_quiz_question':
            $quiz_question_id = intval($input['quiz_question_id'] ?? 0);
            
            $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_question_id = ?");
            $stmt->bind_param("i", $quiz_question_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Quiz question deleted successfully']);
            } else {
                throw new Exception('Failed to delete quiz question');
            }
            break;
            
        // ==================== PUBLISHING OPERATIONS ====================
        
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
            
        // ==================== UTILITY OPERATIONS ====================
        
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
?>