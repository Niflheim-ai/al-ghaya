<?php
/**
 * Story Interactive Sections Handler
 * Processes interactive sections when creating or updating stories
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/quiz-handler.php';

function saveStoryInteractiveSections($conn, $story_id, $sections_data) {
    // Log for debugging
    error_log("Saving interactive sections for story_id: $story_id");
    error_log("Sections data: " . print_r($sections_data, true));
    
    if (empty($sections_data) || !is_array($sections_data)) {
        error_log("No sections data provided");
        return false;
    }
    
    try {
        // Delete all existing interactive sections for this story
        $existingSections = story_getInteractiveSections($conn, $story_id);
        foreach ($existingSections as $sec) {
            // Delete questions first
            $stmt1 = $conn->prepare("DELETE FROM question_options WHERE question_id IN (SELECT question_id FROM interactive_questions WHERE section_id = ?)");
            if ($stmt1) {
                $stmt1->bind_param("i", $sec['section_id']);
                $stmt1->execute();
                $stmt1->close();
            }
            
            $stmt2 = $conn->prepare("DELETE FROM interactive_questions WHERE section_id = ?");
            if ($stmt2) {
                $stmt2->bind_param("i", $sec['section_id']);
                $stmt2->execute();
                $stmt2->close();
            }
            
            // Delete section
            $stmt3 = $conn->prepare("DELETE FROM story_interactive_sections WHERE section_id = ?");
            if ($stmt3) {
                $stmt3->bind_param("i", $sec['section_id']);
                $stmt3->execute();
                $stmt3->close();
            }
        }
        
        // Create new interactive sections
        foreach ($sections_data as $section_index => $section_data) {
            $section_order = intval($section_data['section_order'] ?? ($section_index + 1));
            
            error_log("Creating section $section_index with order $section_order");
            
            // Create section
            $section_id = interactiveSection_create($conn, $story_id);
            if (!$section_id) {
                error_log("Failed to create interactive section");
                throw new Exception('Failed to create interactive section');
            }
            
            error_log("Created section_id: $section_id");
            
            // Create questions for this section
            $questions = $section_data['questions'] ?? [];
            foreach ($questions as $q_index => $question_data) {
                $question_text = trim($question_data['text'] ?? '');
                $question_type = 'multiple_choice'; // All questions are multiple choice now
                
                if (empty($question_text)) {
                    error_log("Skipping empty question at index $q_index");
                    continue;
                }
                
                error_log("Creating question: $question_text");
                
                $question_id = interactiveQuestion_create($conn, $section_id, $question_text, $question_type, $q_index + 1);
                if (!$question_id) {
                    error_log("Failed to create question");
                    throw new Exception('Failed to create question');
                }
                
                error_log("Created question_id: $question_id");
                
                // Create options
                $options = $question_data['options'] ?? [];
                foreach ($options as $opt_index => $option_data) {
                    $option_text = trim($option_data['text'] ?? '');
                    // Check if checkbox was checked (value will be '1' or 'on')
                    $is_correct = isset($option_data['is_correct']) && $option_data['is_correct'] ? 1 : 0;
                    
                    if (empty($option_text)) {
                        error_log("Skipping empty option at index $opt_index");
                        continue;
                    }
                    
                    error_log("Creating option: $option_text (correct: $is_correct)");
                    
                    if (!questionOption_create($conn, $question_id, $option_text, $is_correct, $opt_index + 1)) {
                        error_log("Failed to create option");
                        throw new Exception('Failed to create option');
                    }
                }
            }
        }
        
        error_log("Successfully saved all interactive sections");
        return true;
        
    } catch (Exception $e) {
        error_log("Error saving interactive sections: " . $e->getMessage());
        return false;
    }
}

function story_getInteractiveSections($conn, $story_id) {
    $check = $conn->query("SHOW TABLES LIKE 'story_interactive_sections'");
    if (!$check || $check->num_rows == 0) {
        return [];
    }
    
    $stmt = $conn->prepare("SELECT * FROM story_interactive_sections WHERE story_id = ? ORDER BY section_order ASC");
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param("i", $story_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    
    return $rows;
}
