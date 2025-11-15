<?php
session_start();
require_once 'dbConnection.php';
require_once 'youtube-embed-helper.php';

// Check if user is admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$program_id = intval($_GET['program_id'] ?? 0);

if (!$program_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid program ID']);
    exit;
}

try {
    // Get program details
    $stmt = $conn->prepare("
        SELECT p.*, 
               u.fname as teacher_fname, 
               u.lname as teacher_lname, 
               u.email as teacher_email,
               CONCAT(u.fname, ' ', u.lname) as teacher_name
        FROM programs p
        INNER JOIN user u ON p.teacherID = u.userID
        WHERE p.programID = ?
    ");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$program) {
        echo json_encode(['success' => false, 'message' => 'Program not found']);
        exit;
    }
    
    // Get chapters with all details
    $chaptersStmt = $conn->prepare("
        SELECT chapter_id, title, content, chapter_order, 
               has_quiz, story_count, quiz_question_count,
               video_url, audio_url,
               question, question_type, correct_answer, answer_options, points_reward
        FROM program_chapters
        WHERE programID = ?
        ORDER BY chapter_order ASC
    ");
    $chaptersStmt->bind_param("i", $program_id);
    $chaptersStmt->execute();
    $chaptersResult = $chaptersStmt->get_result();
    $chapters = [];
    
    while ($chapter = $chaptersResult->fetch_assoc()) {
        // Convert chapter video URL to embed format
        if ($chapter['video_url']) {
            $chapter['video_url_embed'] = toYouTubeEmbedUrl($chapter['video_url']);
        } else {
            $chapter['video_url_embed'] = null;
        }
        
        // Parse answer options if JSON
        if ($chapter['answer_options']) {
            $decoded = json_decode($chapter['answer_options'], true);
            $chapter['answer_options_parsed'] = $decoded ?: [];
        } else {
            $chapter['answer_options_parsed'] = [];
        }
        
        // Get stories for each chapter
        $storiesStmt = $conn->prepare("
            SELECT story_id, title, synopsis_arabic, synopsis_english, 
                   video_url, story_order
            FROM chapter_stories
            WHERE chapter_id = ?
            ORDER BY story_order ASC
        ");
        $storiesStmt->bind_param("i", $chapter['chapter_id']);
        $storiesStmt->execute();
        $storiesResult = $storiesStmt->get_result();
        
        $stories = [];
        while ($story = $storiesResult->fetch_assoc()) {
            // Convert story video URL to embed format
            if ($story['video_url']) {
                $story['video_url_embed'] = toYouTubeEmbedUrl($story['video_url']);
            } else {
                $story['video_url_embed'] = null;
            }
            $stories[] = $story;
        }
        $chapter['stories'] = $stories;
        $storiesStmt->close();
        
        // Get chapter quiz if exists (has_quiz = 1)
        $chapter['quiz_questions'] = [];
        if ($chapter['has_quiz'] == 1) {
            // Get quiz from chapter_quizzes table
            $quizStmt = $conn->prepare("SELECT quiz_id FROM chapter_quizzes WHERE chapter_id = ? LIMIT 1");
            $quizStmt->bind_param("i", $chapter['chapter_id']);
            $quizStmt->execute();
            $quizResult = $quizStmt->get_result()->fetch_assoc();
            $quizStmt->close();
            
            if ($quizResult && isset($quizResult['quiz_id'])) {
                // Get quiz questions with options
                $questionsStmt = $conn->prepare("
                    SELECT quiz_question_id, question_text
                    FROM quiz_questions
                    WHERE quiz_id = ?
                    ORDER BY quiz_question_id ASC
                ");
                $questionsStmt->bind_param("i", $quizResult['quiz_id']);
                $questionsStmt->execute();
                $questionsResult = $questionsStmt->get_result();
                
                $quiz_questions = [];
                while ($question = $questionsResult->fetch_assoc()) {
                    // Get options for this question
                    $optionsStmt = $conn->prepare("
                        SELECT quiz_option_id, option_text, is_correct
                        FROM quiz_question_options
                        WHERE quiz_question_id = ?
                        ORDER BY quiz_option_id ASC
                    ");
                    $optionsStmt->bind_param("i", $question['quiz_question_id']);
                    $optionsStmt->execute();
                    $optionsResult = $optionsStmt->get_result();
                    $question['options'] = $optionsResult->fetch_all(MYSQLI_ASSOC);
                    $optionsStmt->close();
                    
                    $quiz_questions[] = $question;
                }
                $questionsStmt->close();
                
                $chapter['quiz_questions'] = $quiz_questions;
            }
        }

        $chapters[] = $chapter;
    }
    $chaptersStmt->close();
    
    $program['chapters'] = $chapters;
    
    // Return response
    echo json_encode([
        'success' => true,
        'program' => $program
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("admin-get-program-details error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}
?>
