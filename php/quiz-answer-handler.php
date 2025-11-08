<?php
/**
 * Quiz Answer Handler - Al-Ghaya LMS
 * Handles student quiz answer validation and progression logic
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'dbConnection.php';
require_once 'quiz-handler.php';

// Guard: Students only
if (!isset($_SESSION['userID']) || ($_SESSION['role'] ?? '') !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Student access required']);
    exit;
}

$student_id = (int)$_SESSION['userID'];

// Parse JSON input
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $_POST = array_merge($_POST, $input);
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'check_answer':
            $question_id = intval($_POST['question_id'] ?? 0);
            $selected_option_id = intval($_POST['option_id'] ?? 0);
            $story_id = intval($_POST['story_id'] ?? 0);
            
            if (!$question_id || !$selected_option_id) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            // Get the selected option
            $optionStmt = $conn->prepare("
                SELECT is_correct 
                FROM quiz_question_options 
                WHERE quiz_option_id = ? AND quiz_question_id = ?
            ");
            $optionStmt->bind_param("ii", $selected_option_id, $question_id);
            $optionStmt->execute();
            $optionResult = $optionStmt->get_result()->fetch_assoc();
            $optionStmt->close();
            
            if (!$optionResult) {
                echo json_encode([
                    'success' => false, 
                    'correct' => false,
                    'message' => 'Invalid answer option'
                ]);
                exit;
            }
            
            $is_correct = (bool)$optionResult['is_correct'];
            
            // Log the attempt (optional - for analytics)
            $logStmt = $conn->prepare("
                INSERT INTO student_quiz_attempts 
                (student_id, quiz_id, score, max_score, is_passed, attempt_date) 
                SELECT ?, cq.quiz_id, ?, 1, ?, NOW()
                FROM quiz_questions qq
                JOIN chapter_quizzes cq ON qq.quiz_id = cq.quiz_id
                WHERE qq.quiz_question_id = ?
            ");
            $score = $is_correct ? 1 : 0;
            $passed = $is_correct ? 1 : 0;
            $logStmt->bind_param("idii", $student_id, $score, $passed, $question_id);
            $logStmt->execute();
            $logStmt->close();
            
            // Update story progress if correct
            if ($is_correct && $story_id > 0) {
                $progressStmt = $conn->prepare("
                    INSERT INTO student_story_progress 
                    (student_id, story_id, is_completed, completion_date, last_accessed) 
                    VALUES (?, ?, 1, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                    is_completed = 1, 
                    completion_date = NOW(),
                    last_accessed = NOW()
                ");
                $progressStmt->bind_param("ii", $student_id, $story_id);
                $progressStmt->execute();
                $progressStmt->close();
            }
            
            echo json_encode([
                'success' => true,
                'correct' => $is_correct,
                'message' => $is_correct 
                    ? 'Excellent! You answered correctly. You may now proceed to the next story.' 
                    : 'That\'s not quite right. Review the story content and try again.'
            ]);
            break;
            
        case 'chapter_quiz_submit':
            $quiz_id = intval($_POST['quiz_id'] ?? 0);
            $answers = $_POST['answers'] ?? [];
            $questionIDs = $_POST['questionIDs'] ?? [];
            
            if (!$quiz_id || empty($answers) || empty($questionIDs) || count($answers) !== count($questionIDs)) {
                echo json_encode(['success' => false, 'message' => 'Quiz ID, answers, and question IDs required']);
                exit;
            }
            
            $correct = 0;
            $total = count($answers);
            
            foreach ($questionIDs as $i => $qid) {
                $selected = intval($answers[$i]);
                $stmt = $conn->prepare("SELECT is_correct FROM quiz_question_options WHERE quiz_option_id = ? AND quiz_question_id = ?");
                $stmt->bind_param("ii", $selected, $qid);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($result && $result['is_correct']) $correct++;
            }
            
            $passed = $total > 0 && ($correct / $total) >= 0.7;
            
            // Log the quiz attempt
            $logStmt = $conn->prepare("
                INSERT INTO student_quiz_attempts 
                (student_id, quiz_id, score, max_score, is_passed, attempt_date) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->bind_param("iiiii", $student_id, $quiz_id, $correct, $total, $passed);
            $logStmt->execute();
            $logStmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => $passed
                    ? 'Quiz passed! You can now proceed to the next chapter.'
                    : 'Quiz failed. You scored ' . $correct . '/' . $total . '. Please review the chapter and try again.',
                'score' => $correct,
                'total' => $total,
                'passed' => $passed
            ]);
            break;
            
        case 'mark_story_complete':
            $story_id = intval($_POST['story_id'] ?? 0);
            
            if (!$story_id) {
                echo json_encode(['success' => false, 'message' => 'Story ID is required']);
                exit;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO student_story_progress 
                (student_id, story_id, is_completed, completion_date, last_accessed) 
                VALUES (?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                is_completed = 1,
                completion_date = NOW(),
                last_accessed = NOW()
            ");
            $stmt->bind_param("ii", $student_id, $story_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Story marked as complete']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
            }
            $stmt->close();
            break;
            
        case 'get_progress':
            $program_id = intval($_POST['program_id'] ?? 0);
            
            if (!$program_id) {
                echo json_encode(['success' => false, 'message' => 'Program ID is required']);
                exit;
            }
            
            // Calculate completion percentage based on completed stories
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT cs.story_id) as total_stories,
                    COUNT(DISTINCT ssp.story_id) as completed_stories
                FROM chapter_stories cs
                JOIN program_chapters pc ON cs.chapter_id = pc.chapter_id
                LEFT JOIN student_story_progress ssp ON cs.story_id = ssp.story_id AND ssp.student_id = ?
                WHERE pc.programID = ?
            ");
            $stmt->bind_param("ii", $student_id, $program_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $total = intval($result['total_stories'] ?? 0);
            $completed = intval($result['completed_stories'] ?? 0);
            $percentage = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            
            // Update enrollment completion percentage
            $updateStmt = $conn->prepare("
                UPDATE student_program_enrollments 
                SET completion_percentage = ?, last_accessed = NOW() 
                WHERE student_id = ? AND program_id = ?
            ");
            $updateStmt->bind_param("dii", $percentage, $student_id, $program_id);
            $updateStmt->execute();
            $updateStmt->close();
            
            echo json_encode([
                'success' => true,
                'total_stories' => $total,
                'completed_stories' => $completed,
                'completion_percentage' => $percentage
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log("Quiz Answer Handler Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

exit;
