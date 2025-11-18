<?php
session_start();
require_once 'dbConnection.php';
require_once 'youtube-embed-helper.php';
require_once 'program-core.php'; // For convertToEmbedUrl function

// Check if user is admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$program_id = intval($_GET['program_id'] ?? 0);

error_log("DEBUG: Received program_id = " . $program_id);

if (!$program_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid program ID']);
    exit;
}

try {
    // Get program details
    $stmt = $conn->prepare("
        SELECT p.*, 
               t.fname as teacher_fname, 
               t.lname as teacher_lname, 
               t.email as teacher_email,
               CONCAT(t.fname, ' ', t.lname) as teacher_name
        FROM programs p
        LEFT JOIN teacher t ON p.teacherID = t.teacherID
        WHERE p.programID = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $program = $result->fetch_assoc();
    $stmt->close();
    
    if (!$program) {
        echo json_encode(['success' => false, 'message' => 'Program not found']);
        exit;
    }
    
    // Convert overview video URL to embed format
    if (!empty($program['overview_video_url'])) {
        $program['overview_video_url_embed'] = function_exists('convertToEmbedUrl') 
            ? convertToEmbedUrl($program['overview_video_url']) 
            : toYouTubeEmbedUrl($program['overview_video_url']);
    } else {
        $program['overview_video_url_embed'] = null;
    }
    
    // Get chapters
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
        // Convert chapter video URL
        if ($chapter['video_url']) {
            $chapter['video_url_embed'] = function_exists('convertToEmbedUrl') 
                ? convertToEmbedUrl($chapter['video_url']) 
                : toYouTubeEmbedUrl($chapter['video_url']);
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
            $story_id = $story['story_id'];
            
            // Convert story video URL
            if ($story['video_url']) {
                $story['video_url_embed'] = function_exists('convertToEmbedUrl') 
                    ? convertToEmbedUrl($story['video_url']) 
                    : toYouTubeEmbedUrl($story['video_url']);
            } else {
                $story['video_url_embed'] = null;
            }
            
            // ✅ GET INTERACTIVE SECTIONS FOR THIS STORY
            $sectionsStmt = $conn->prepare("
                SELECT section_id, section_order
                FROM story_interactive_sections
                WHERE story_id = ?
                ORDER BY section_order ASC
            ");
            $sectionsStmt->bind_param("i", $story_id);
            $sectionsStmt->execute();
            $sectionsResult = $sectionsStmt->get_result();
            
            $interactive_sections = [];
            while ($section = $sectionsResult->fetch_assoc()) {
                $section_id = $section['section_id'];
                
                // Get questions for this section
                $questionsStmt = $conn->prepare("
                    SELECT question_id, question_text, question_type, question_order
                    FROM interactive_questions
                    WHERE section_id = ?
                    ORDER BY question_order ASC
                ");
                $questionsStmt->bind_param("i", $section_id);
                $questionsStmt->execute();
                $questionsResult = $questionsStmt->get_result();
                
                $questions = [];
                while ($question = $questionsResult->fetch_assoc()) {
                    $question_id = $question['question_id'];
                    
                    // Get options for this question
                    $optionsStmt = $conn->prepare("
                        SELECT option_id, option_text, is_correct, option_order
                        FROM question_options
                        WHERE question_id = ?
                        ORDER BY option_order ASC
                    ");
                    $optionsStmt->bind_param("i", $question_id);
                    $optionsStmt->execute();
                    $optionsResult = $optionsStmt->get_result();
                    $question['options'] = $optionsResult->fetch_all(MYSQLI_ASSOC);
                    $optionsStmt->close();
                    
                    $questions[] = $question;
                }
                $questionsStmt->close();
                
                $section['questions'] = $questions;
                $interactive_sections[] = $section;
            }
            $sectionsStmt->close();
            
            $story['interactive_sections'] = $interactive_sections;
            $stories[] = $story;
        }
        $chapter['stories'] = $stories;
        $storiesStmt->close();
        
        // Get chapter quiz if exists
        $chapter['quiz_questions'] = [];
        if ($chapter['has_quiz'] == 1) {
            $quizStmt = $conn->prepare("SELECT quiz_id FROM chapter_quizzes WHERE chapter_id = ? LIMIT 1");
            $quizStmt->bind_param("i", $chapter['chapter_id']);
            $quizStmt->execute();
            $quizResult = $quizStmt->get_result()->fetch_assoc();
            $quizStmt->close();
            
            if ($quizResult && isset($quizResult['quiz_id'])) {
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
    
    echo json_encode([
        'success' => true,
        'program' => $program
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    error_log("admin-get-program-details error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>