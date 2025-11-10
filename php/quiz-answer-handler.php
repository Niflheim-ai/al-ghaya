<?php
/**
 * Quiz Answer Handler - Al-Ghaya LMS
 * Handles student quiz answer validation and progression logic
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'dbConnection.php';
require_once __DIR__ . '/quiz-handler.php';
require_once __DIR__ . '/student-progress.php';

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

// GET STUDENT ID FROM SESSION - ADD THIS!
$studentID = isset($_SESSION['userID']) ? (int)$_SESSION['userID'] : 0;

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? '';

// Override POST with JSON data
if ($data) {
    $_POST = array_merge($_POST, $data);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'check_answer':
            // ... (Unchanged logic above)
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
            $review = [];
            
            foreach ($questionIDs as $i => $qid) {
                $selected = intval($answers[$i]);
                $stmt = $conn->prepare("SELECT qq.question_text, qo.quiz_option_id, qo.option_text, qo.is_correct FROM quiz_questions qq JOIN quiz_question_options qo ON qq.quiz_question_id = qo.quiz_question_id WHERE qq.quiz_question_id = ?");
                $stmt->bind_param("i", $qid);
                $stmt->execute();
                $options = [];
                $correct_option = null;
                $user_is_correct = false;
                $qtext = '';
                
                foreach ($stmt->get_result() as $row) {
                    $options[] = [
                        'id' => (int)$row['quiz_option_id'],
                        'text' => $row['option_text'],
                        'is_correct' => (bool)$row['is_correct'],
                        'user_selected' => ((int)$row['quiz_option_id'] === $selected)
                    ];
                    if ($row['is_correct'] && ((int)$row['quiz_option_id'] === $selected)) {
                        $correct++;
                        $user_is_correct = true;
                    }
                    if ($row['is_correct']) $correct_option = (int)$row['quiz_option_id'];
                    $qtext = $row['question_text'];
                }
                $stmt->close();
                
                $review[] = [
                    'question_id' => $qid,
                    'question_text' => $qtext,
                    'options' => $options,
                    'user_selected' => $selected,
                    'correct_option' => $correct_option,
                    'is_correct' => $user_is_correct
                ];
            }
            
            $passed = $total > 0 && ($correct / $total) >= 0.7;
            
            // Log the quiz attempt
            $logStmt = $conn->prepare("INSERT INTO student_quiz_attempts (student_id, quiz_id, score, max_score, is_passed, attempt_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->bind_param("iiiii", $student_id, $quiz_id, $correct, $total, $passed);
            $logStmt->execute();
            $logStmt->close();
            
            // Store each answer (FIXED!)
            foreach ($questionIDs as $i => $qid) {
                $optid = intval($answers[$i]);
                
                // Check if this option is correct
                $checkStmt = $conn->prepare("SELECT is_correct FROM quiz_question_options WHERE quiz_option_id = ?");
                $checkStmt->bind_param("i", $optid);
                $checkStmt->execute();
                $optResult = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
                $is_correct = $optResult ? (int)$optResult['is_correct'] : 0;
                
                // Insert with correct field names matching your DB
                $stmt = $conn->prepare("INSERT INTO student_quiz_answers (student_id, quiz_question_id, quiz_option_id, is_correct) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiii", $student_id, $qid, $optid, $is_correct);
                $stmt->execute();
                $stmt->close();
            }
            
            echo json_encode([
                'success' => true,
                'message' => $passed
                    ? 'Quiz passed! You can now proceed to the next chapter.'
                    : 'Quiz failed. You scored ' . $correct . '/' . $total . '. Please review the chapter and try again.',
                'score' => $correct,
                'total' => $total,
                'passed' => $passed,
                'review' => $review
            ]);
            break;
        case 'check_interactive_answer':
            $question_id = intval($_POST['question_id'] ?? 0);
            $option_id = intval($_POST['option_id'] ?? 0);
            $story_id = intval($_POST['story_id'] ?? 0);
            
            if (!$question_id || !$option_id || !$story_id) {
                echo json_encode(['success' => false, 'correct' => false, 'message' => 'Invalid input']);
                exit;
            }
            
            // Check if answer is correct
            $stmt = $conn->prepare("SELECT is_correct FROM question_options WHERE option_id = ?");
            $stmt->bind_param("i", $option_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $isCorrect = $result && $result['is_correct'] == 1;
            
            if ($isCorrect) {
                // Mark story as completed
                studentStoryProgress_markCompleted($conn, $studentID, $story_id);
                echo json_encode([
                    'success' => true,
                    'correct' => true,
                    'message' => 'Correct! You can proceed to the next story.'
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'correct' => false,
                    'message' => 'Incorrect. Please try again.'
                ]);
            }
            exit;
        }
} catch (Exception $e) {
    error_log("Quiz Answer Handler Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
exit;
