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
            foreach ($questionIDs as $i => $qid) {
                $optid = intval($answers[$i]);
                $checkStmt = $conn->prepare("SELECT is_correct FROM quiz_question_options WHERE quiz_option_id = ?");
                $checkStmt->bind_param("i", $optid);
                $checkStmt->execute();
                $optResult = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
                $is_correct = $optResult ? (int)$optResult['is_correct'] : 0;
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
            $section_id = intval($_POST['section_id'] ?? 0);
            
            if (!$question_id || !$option_id || !$story_id) {
                echo json_encode(['success' => false, 'correct' => false, 'message' => 'Invalid input']);
                exit;
            }
            
            // Check if the submitted answer is correct
            $stmt = $conn->prepare("SELECT is_correct FROM question_options WHERE option_id = ?");
            $stmt->bind_param("i", $option_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $isCorrect = $result && $result['is_correct'] == 1;
            
            if ($isCorrect) {
                // Record the correct answer in student_interactive_answers table
                // First check if answer already exists
                $checkStmt = $conn->prepare("SELECT answer_id FROM student_interactive_answers WHERE student_id = ? AND question_id = ?");
                $checkStmt->bind_param("ii", $student_id, $question_id);
                $checkStmt->execute();
                $existingAnswer = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
                
                if ($existingAnswer) {
                    // Update existing answer
                    $updateStmt = $conn->prepare("UPDATE student_interactive_answers SET option_id = ?, is_correct = 1, answer_date = NOW() WHERE answer_id = ?");
                    $updateStmt->bind_param("ii", $option_id, $existingAnswer['answer_id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    // Insert new answer
                    $insertStmt = $conn->prepare("INSERT INTO student_interactive_answers (student_id, question_id, option_id, is_correct, answer_date) VALUES (?, ?, ?, 1, NOW())");
                    $insertStmt->bind_param("iii", $student_id, $question_id, $option_id);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
                
                // NOW CHECK: Are ALL sections and ALL questions in this story answered correctly?
                // Get all interactive sections for this story
                $sectionsStmt = $conn->prepare("SELECT section_id FROM story_interactive_sections WHERE story_id = ?");
                $sectionsStmt->bind_param("i", $story_id);
                $sectionsStmt->execute();
                $sections = $sectionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $sectionsStmt->close();
                
                $allQuestionsCorrect = true;
                $totalQuestions = 0;
                $correctAnswers = 0;
                
                foreach ($sections as $section) {
                    $sectionId = $section['section_id'];
                    
                    // Get all questions for this section
                    $questionsStmt = $conn->prepare("SELECT question_id FROM interactive_questions WHERE section_id = ?");
                    $questionsStmt->bind_param("i", $sectionId);
                    $questionsStmt->execute();
                    $questions = $questionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $questionsStmt->close();
                    
                    foreach ($questions as $question) {
                        $totalQuestions++;
                        $qid = $question['question_id'];
                        
                        // Check if student has answered this question correctly
                        $answerCheckStmt = $conn->prepare("SELECT is_correct FROM student_interactive_answers WHERE student_id = ? AND question_id = ?");
                        $answerCheckStmt->bind_param("ii", $student_id, $qid);
                        $answerCheckStmt->execute();
                        $answerResult = $answerCheckStmt->get_result()->fetch_assoc();
                        $answerCheckStmt->close();
                        
                        if (!$answerResult || $answerResult['is_correct'] != 1) {
                            $allQuestionsCorrect = false;
                        } else {
                            $correctAnswers++;
                        }
                    }
                }
                
                // Only mark story as complete if ALL questions are answered correctly
                $sectionCompleted = false;
                if ($allQuestionsCorrect && $totalQuestions > 0) {
                    studentStoryProgress_markCompleted($conn, $student_id, $story_id);
                    $sectionCompleted = true;
                    
                    echo json_encode([
                        'success' => true,
                        'correct' => true,
                        'sectionCompleted' => true,
                        'message' => 'Congratulations! You have completed all sections of this story. You can now proceed to the next story.'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'correct' => true,
                        'sectionCompleted' => false,
                        'message' => 'Correct! Continue with the remaining questions. (' . $correctAnswers . '/' . $totalQuestions . ' completed)',
                        'progress' => [
                            'answered' => $correctAnswers,
                            'total' => $totalQuestions
                        ]
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'correct' => false,
                    'sectionCompleted' => false,
                    'message' => 'Incorrect. Please try again.'
                ]);
            }
            exit;
        case 'get_progress':
            $program_id = isset($data['program_id']) ? (int)$data['program_id'] : 0;
            $student_id = $_SESSION['userID'] ?? 0;
            
            if ($program_id <= 0 || $student_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }
            
            // ✅ Calculate TRUE progress: stories + quizzes + exam
            
            // Stories
            $totalStoriesStmt = $conn->prepare("
                SELECT COUNT(DISTINCT cs.story_id) as total
                FROM program_chapters pc
                INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
                WHERE pc.programID = ?
            ");
            $totalStoriesStmt->bind_param("i", $program_id);
            $totalStoriesStmt->execute();
            $totalStories = $totalStoriesStmt->get_result()->fetch_assoc()['total'] ?? 0;
            $totalStoriesStmt->close();

            $completedStoriesStmt = $conn->prepare("
                SELECT COUNT(DISTINCT ssp.story_id) as completed
                FROM program_chapters pc
                INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
                INNER JOIN student_story_progress ssp ON cs.story_id = ssp.story_id
                WHERE pc.programID = ? AND ssp.student_id = ? AND ssp.is_completed = 1
            ");
            $completedStoriesStmt->bind_param("ii", $program_id, $student_id);
            $completedStoriesStmt->execute();
            $completedStories = $completedStoriesStmt->get_result()->fetch_assoc()['completed'] ?? 0;
            $completedStoriesStmt->close();

            // Quizzes
            $totalQuizzesStmt = $conn->prepare("
                SELECT COUNT(DISTINCT cq.quiz_id) as total
                FROM chapter_quizzes cq
                INNER JOIN program_chapters pc ON cq.chapter_id = pc.chapter_id
                WHERE pc.programID = ?
            ");
            $totalQuizzesStmt->bind_param("i", $program_id);
            $totalQuizzesStmt->execute();
            $totalQuizzes = $totalQuizzesStmt->get_result()->fetch_assoc()['total'] ?? 0;
            $totalQuizzesStmt->close();

            $passedQuizzesStmt = $conn->prepare("
                SELECT COUNT(DISTINCT sqa.quiz_id) AS passed
                FROM student_quiz_attempts sqa
                INNER JOIN chapter_quizzes cq ON sqa.quiz_id = cq.quiz_id
                INNER JOIN program_chapters pc ON cq.chapter_id = pc.chapter_id
                WHERE pc.programID = ? AND sqa.student_id = ? AND sqa.is_passed = 1
            ");
            $passedQuizzesStmt->bind_param("ii", $program_id, $student_id);
            $passedQuizzesStmt->execute();
            $passedQuizzes = $passedQuizzesStmt->get_result()->fetch_assoc()['passed'] ?? 0;
            $passedQuizzesStmt->close();

            // Final Exam/Certificate
            $examTotal = 0;
            $examDone = 0;
            $certCheckStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM student_program_certificates WHERE program_id = ?");
            $certCheckStmt->bind_param("i", $program_id);
            $certCheckStmt->execute();
            $hasCert = $certCheckStmt->get_result()->fetch_assoc()['cnt'] > 0;
            $certCheckStmt->close();
            
            if ($hasCert) {
                $examTotal = 1;
                $certDoneStmt = $conn->prepare("SELECT 1 FROM student_program_certificates WHERE program_id = ? AND student_id = ? LIMIT 1");
                $certDoneStmt->bind_param("ii", $program_id, $student_id);
                $certDoneStmt->execute();
                if ($certDoneStmt->get_result()->fetch_assoc()) {
                    $examDone = 1;
                }
                $certDoneStmt->close();
            }

            $complete = $completedStories + $passedQuizzes + $examDone;
            $total = $totalStories + $totalQuizzes + $examTotal;
            $completion_percentage = $total > 0 ? round(($complete / $total) * 100, 1) : 0.0;

            // ✅ Also update the database for consistency
            $updateStmt = $conn->prepare("
                UPDATE student_program_enrollments 
                SET completion_percentage = ?, last_accessed = NOW() 
                WHERE student_id = ? AND program_id = ?
            ");
            $updateStmt->bind_param("dii", $completion_percentage, $student_id, $program_id);
            $updateStmt->execute();
            $updateStmt->close();

            echo json_encode([
                'success' => true,
                'completion_percentage' => $completion_percentage,
                'details' => [
                    'completed_stories' => $completedStories,
                    'total_stories' => $totalStories,
                    'passed_quizzes' => $passedQuizzes,
                    'total_quizzes' => $totalQuizzes,
                    'exam_completed' => $examDone,
                    'exam_available' => $examTotal
                ]
            ]);
        exit;
    }
} catch (Exception $e) {
    error_log("Quiz Answer Handler Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
exit;
