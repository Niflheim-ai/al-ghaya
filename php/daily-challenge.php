<?php
/**
 * Daily Challenge System
 * Provides a random quiz question each day for students
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'dbConnection.php';

// ✅ DEBUG MODE - Set to true to allow unlimited retries
define('DEBUG_MODE', false); // Change to false in production!

/**
 * Get daily challenge question for a student
 */
function getDailyChallenge($conn, $user_id) {
    $today = date('Y-m-d');
    
    // ✅ Skip attempt check in debug mode
    if (!DEBUG_MODE) {
        // Check if user already attempted today
        $attemptStmt = $conn->prepare("
            SELECT dca.*, qq.question_text, 
                   p.title as program_title, p.programID
            FROM daily_challenge_attempts dca
            INNER JOIN quiz_questions qq ON dca.question_id = qq.quiz_question_id
            INNER JOIN chapter_quizzes cq ON qq.quiz_id = cq.quiz_id
            INNER JOIN program_chapters pc ON cq.chapter_id = pc.chapter_id
            INNER JOIN programs p ON pc.programID = p.programID
            WHERE dca.user_id = ? AND dca.attempt_date = ?
        ");
        $attemptStmt->bind_param("is", $user_id, $today);
        $attemptStmt->execute();
        $attempt = $attemptStmt->get_result()->fetch_assoc();
        $attemptStmt->close();
        
        if ($attempt) {
            // User already attempted today
            return [
                'question_id' => $attempt['question_id'],
                'question' => $attempt['question_text'],
                'program_title' => $attempt['program_title'],
                'program_id' => $attempt['programID'],
                'attempted' => true,
                'is_correct' => $attempt['is_correct'],
                'points_awarded' => $attempt['points_awarded']
            ];
        }
    }
    
    // Generate new daily challenge (always in debug mode)
    $dateSeed = DEBUG_MODE ? rand(1, 100000) : strtotime($today);
    
    $questionStmt = $conn->prepare("
        SELECT qq.quiz_question_id, qq.question_text, qq.quiz_id,
               p.title as program_title, p.programID
        FROM quiz_questions qq
        INNER JOIN chapter_quizzes cq ON qq.quiz_id = cq.quiz_id
        INNER JOIN program_chapters pc ON cq.chapter_id = pc.chapter_id
        INNER JOIN programs p ON pc.programID = p.programID
        WHERE p.status = 'published'
        ORDER BY RAND(?)
        LIMIT 1
    ");
    $questionStmt->bind_param("i", $dateSeed);
    $questionStmt->execute();
    $question = $questionStmt->get_result()->fetch_assoc();
    $questionStmt->close();
    
    if (!$question) {
        return null;
    }
    
    // Get options for this question
    $optionsStmt = $conn->prepare("
        SELECT quiz_option_id, option_text, is_correct 
        FROM quiz_question_options 
        WHERE quiz_question_id = ? 
        ORDER BY quiz_option_id
    ");
    $optionsStmt->bind_param("i", $question['quiz_question_id']);
    $optionsStmt->execute();
    $optionsResult = $optionsStmt->get_result();
    $options = [];
    while ($opt = $optionsResult->fetch_assoc()) {
        $options[] = $opt;
    }
    $optionsStmt->close();
    
    return [
        'question_id' => $question['quiz_question_id'],
        'question' => $question['question_text'],
        'options' => json_encode(array_column($options, 'option_text')),
        'program_title' => $question['program_title'],
        'program_id' => $question['programID'],
        'attempted' => false,
        'debug_mode' => DEBUG_MODE // ✅ Flag to show debug indicator
    ];
}

/**
 * Submit daily challenge answer
 */
function submitDailyChallenge($conn, $user_id, $question_id, $user_answer) {
    $today = date('Y-m-d');
    
    // ✅ In debug mode, delete previous attempt to allow retry
    if (DEBUG_MODE) {
        $deleteStmt = $conn->prepare("DELETE FROM daily_challenge_attempts WHERE user_id = ? AND attempt_date = ?");
        $deleteStmt->bind_param("is", $user_id, $today);
        $deleteStmt->execute();
        $deleteStmt->close();
    } else {
        // Check if already attempted today (production mode only)
        $checkStmt = $conn->prepare("SELECT attempt_id FROM daily_challenge_attempts WHERE user_id = ? AND attempt_date = ?");
        $checkStmt->bind_param("is", $user_id, $today);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'message' => 'You have already attempted today\'s challenge'];
        }
        $checkStmt->close();
    }
    
    // Get correct answer option
    $correctStmt = $conn->prepare("
        SELECT option_text 
        FROM quiz_question_options 
        WHERE quiz_question_id = ? AND is_correct = 1
    ");
    $correctStmt->bind_param("i", $question_id);
    $correctStmt->execute();
    $correctResult = $correctStmt->get_result()->fetch_assoc();
    $correctStmt->close();
    
    if (!$correctResult) {
        return ['success' => false, 'message' => 'Question not found'];
    }
    
    // Check if answer is correct
    $is_correct = (trim(strtolower($user_answer)) === trim(strtolower($correctResult['option_text'])));
    $points = $is_correct ? 10 : 0; // ✅ FIXED: No penalty for wrong answers, just 0 points
    
    // Record attempt (will be fresh in debug mode due to delete above)
    $insertStmt = $conn->prepare("
        INSERT INTO daily_challenge_attempts (user_id, question_id, is_correct, points_awarded, attempt_date) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertStmt->bind_param("iiiis", $user_id, $question_id, $is_correct, $points, $today);
    $insertStmt->execute();
    $insertStmt->close();
    
    // ✅ FIXED: Only update points if correct (no penalty for wrong answers)
    if ($is_correct && $points > 0) {
        $updateStmt = $conn->prepare("UPDATE user SET points = points + ? WHERE userID = ?");
        $updateStmt->bind_param("ii", $points, $user_id);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Create transaction record for activity feed
        $activityType = 'daily_challenge_correct';
        $description = 'Completed daily challenge correctly';
        
        $transactionStmt = $conn->prepare("
            INSERT INTO point_transactions (userID, points, activity_type, description, dateCreated) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $transactionStmt->bind_param("iiss", $user_id, $points, $activityType, $description);
        $transactionStmt->execute();
        $transactionStmt->close();
    }
    
    $debugMsg = DEBUG_MODE ? ' [DEBUG MODE - Retry Available]' : '';
    
    return [
        'success' => true,
        'is_correct' => $is_correct,
        'points_awarded' => $points,
        'message' => $is_correct ? 
            "Correct! You earned {$points} points!{$debugMsg}" : 
            "Incorrect. Try again tomorrow!{$debugMsg}"
    ];
}

/**
 * Get recommended programs (not enrolled)
 */
function getRecommendedPrograms($conn, $user_id, $limit = 6) {
    $stmt = $conn->prepare("
        SELECT p.programID, p.title, p.description, p.thumbnail as image, p.category, p.difficulty_level
        FROM programs p
        WHERE p.status = 'published'
        AND p.programID NOT IN (
            SELECT program_id FROM student_program_enrollments WHERE student_id = ?
        )
        ORDER BY p.dateCreated DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $programs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $programs;
}

// Handler for AJAX requests
if (basename($_SERVER['PHP_SELF']) === 'daily-challenge.php') {
    if (!isset($_SESSION['userID'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $user_id = $_SESSION['userID'];
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($action === 'submit_challenge') {
        header('Content-Type: application/json');
        $question_id = intval($_POST['question_id'] ?? 0);
        $user_answer = $_POST['answer'] ?? '';
        
        if (!$question_id || !$user_answer) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }
        
        $result = submitDailyChallenge($conn, $user_id, $question_id, $user_answer);
        echo json_encode($result);
        exit;
    }
}
?>