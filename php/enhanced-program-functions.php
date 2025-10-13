<?php
/**
 * Enhanced Program Management Functions
 * Handles programs, chapters, stories, quizzes, and interactive sections
 */

// ==================== PROGRAM FUNCTIONS ====================

/**
 * Get all programs for a specific teacher
 */
function getTeacherPrograms($conn, $teacher_id, $status = null) {
    $sql = "SELECT * FROM programs WHERE teacherID = ?";
    $params = [$teacher_id];
    $types = "i";
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $sql .= " ORDER BY dateCreated DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all published programs (for Program Library)
 */
function getAllPublishedPrograms($conn, $exclude_teacher_id = null) {
    $sql = "SELECT p.*, u.fname, u.lname 
            FROM programs p 
            LEFT JOIN user u ON p.teacherID = u.userID 
            WHERE p.status = 'published'";
    $params = [];
    $types = "";
    
    if ($exclude_teacher_id) {
        $sql .= " AND p.teacherID != ?";
        $params[] = $exclude_teacher_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY p.datePublished DESC";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get a specific program by ID and teacher ID
 */
function getProgram($conn, $program_id, $teacher_id) {
    $stmt = $conn->prepare("SELECT * FROM programs WHERE programID = ? AND teacherID = ?");
    $stmt->bind_param("ii", $program_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Create a new program
 */
function createProgram($conn, $data) {
    $sql = "INSERT INTO programs (teacherID, title, description, category, price, thumbnail, overview_video_url, difficulty_level, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssdsss", 
        $data['teacherID'],
        $data['title'],
        $data['description'],
        $data['category'],
        $data['price'],
        $data['thumbnail'],
        $data['overview_video_url'],
        $data['difficulty_level'],
        $data['status']
    );
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

/**
 * Update an existing program
 */
function updateProgram($conn, $program_id, $data) {
    $sql = "UPDATE programs SET title = ?, description = ?, category = ?, price = ?, overview_video_url = ?, difficulty_level = ?, status = ?
            WHERE programID = ? AND teacherID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdssii", 
        $data['title'],
        $data['description'],
        $data['category'],
        $data['price'],
        $data['overview_video_url'],
        $data['difficulty_level'],
        $data['status'],
        $program_id,
        $data['teacherID']
    );
    
    return $stmt->execute();
}

/**
 * Delete a program and all associated data
 */
function deleteProgram($conn, $program_id, $teacher_id) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete all associated data (cascading deletes should handle most of this)
        $stmt = $conn->prepare("DELETE FROM programs WHERE programID = ? AND teacherID = ?");
        $stmt->bind_param("ii", $program_id, $teacher_id);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// ==================== CHAPTER FUNCTIONS ====================

/**
 * Get all chapters for a program
 */
function getProgramChapters($conn, $program_id) {
    $stmt = $conn->prepare("SELECT * FROM program_chapters WHERE program_id = ? ORDER BY chapter_order");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get a specific chapter
 */
function getChapter($conn, $chapter_id) {
    $stmt = $conn->prepare("SELECT * FROM program_chapters WHERE chapter_id = ?");
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Create a new chapter
 */
function createChapter($conn, $program_id, $title, $order = null) {
    if ($order === null) {
        // Get the next order number
        $stmt = $conn->prepare("SELECT MAX(chapter_order) as max_order FROM program_chapters WHERE program_id = ?");
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $order = ($result['max_order'] ?? 0) + 1;
    }
    
    $stmt = $conn->prepare("INSERT INTO program_chapters (program_id, title, chapter_order) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $program_id, $title, $order);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

/**
 * Update a chapter
 */
function updateChapter($conn, $chapter_id, $title) {
    $stmt = $conn->prepare("UPDATE program_chapters SET title = ? WHERE chapter_id = ?");
    $stmt->bind_param("si", $title, $chapter_id);
    return $stmt->execute();
}

/**
 * Delete a chapter and all associated content
 */
function deleteChapter($conn, $chapter_id) {
    $stmt = $conn->prepare("DELETE FROM program_chapters WHERE chapter_id = ?");
    $stmt->bind_param("i", $chapter_id);
    return $stmt->execute();
}

// ==================== STORY FUNCTIONS ====================

/**
 * Get all stories for a chapter
 */
function getChapterStories($conn, $chapter_id) {
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE chapter_id = ? ORDER BY story_order");
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get a specific story
 */
function getStory($conn, $story_id) {
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE story_id = ?");
    $stmt->bind_param("i", $story_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Create a new story
 */
function createStory($conn, $data) {
    // Get the next order number
    $stmt = $conn->prepare("SELECT MAX(story_order) as max_order FROM chapter_stories WHERE chapter_id = ?");
    $stmt->bind_param("i", $data['chapter_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $order = ($result['max_order'] ?? 0) + 1;
    
    $sql = "INSERT INTO chapter_stories (chapter_id, title, synopsis_arabic, synopsis_english, video_url, story_order) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", 
        $data['chapter_id'],
        $data['title'],
        $data['synopsis_arabic'],
        $data['synopsis_english'],
        $data['video_url'],
        $order
    );
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

/**
 * Update a story
 */
function updateStory($conn, $story_id, $data) {
    $sql = "UPDATE chapter_stories SET title = ?, synopsis_arabic = ?, synopsis_english = ?, video_url = ? 
            WHERE story_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", 
        $data['title'],
        $data['synopsis_arabic'],
        $data['synopsis_english'],
        $data['video_url'],
        $story_id
    );
    
    return $stmt->execute();
}

/**
 * Delete a story and all interactive sections
 */
function deleteStory($conn, $story_id) {
    $stmt = $conn->prepare("DELETE FROM chapter_stories WHERE story_id = ?");
    $stmt->bind_param("i", $story_id);
    return $stmt->execute();
}

// ==================== INTERACTIVE SECTION FUNCTIONS ====================

/**
 * Get all interactive sections for a story
 */
function getStoryInteractiveSections($conn, $story_id) {
    $stmt = $conn->prepare("
        SELECT sis.*, 
               COUNT(iq.question_id) as question_count
        FROM story_interactive_sections sis
        LEFT JOIN interactive_questions iq ON sis.section_id = iq.section_id
        WHERE sis.story_id = ? 
        GROUP BY sis.section_id 
        ORDER BY sis.section_order
    ");
    $stmt->bind_param("i", $story_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create a new interactive section
 */
function createInteractiveSection($conn, $story_id) {
    // Get the next order number
    $stmt = $conn->prepare("SELECT MAX(section_order) as max_order FROM story_interactive_sections WHERE story_id = ?");
    $stmt->bind_param("i", $story_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $order = ($result['max_order'] ?? 0) + 1;
    
    // Check if we can add more sections (max 3)
    if ($order > 3) {
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO story_interactive_sections (story_id, section_order) VALUES (?, ?)");
    $stmt->bind_param("ii", $story_id, $order);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

// ==================== QUESTION FUNCTIONS ====================

/**
 * Get all questions for an interactive section
 */
function getSectionQuestions($conn, $section_id) {
    $stmt = $conn->prepare("
        SELECT iq.*, 
               COUNT(qo.option_id) as option_count
        FROM interactive_questions iq
        LEFT JOIN question_options qo ON iq.question_id = qo.question_id
        WHERE iq.section_id = ? 
        GROUP BY iq.question_id 
        ORDER BY iq.question_order
    ");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get question options
 */
function getQuestionOptions($conn, $question_id) {
    $stmt = $conn->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY option_order");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create a new question
 */
function createQuestion($conn, $section_id, $question_text, $question_type) {
    // Get the next order number
    $stmt = $conn->prepare("SELECT MAX(question_order) as max_order FROM interactive_questions WHERE section_id = ?");
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $order = ($result['max_order'] ?? 0) + 1;
    
    $stmt = $conn->prepare("INSERT INTO interactive_questions (section_id, question_text, question_type, question_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $section_id, $question_text, $question_type, $order);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

/**
 * Add option to a question
 */
function addQuestionOption($conn, $question_id, $option_text, $is_correct = false) {
    // Get the next order number
    $stmt = $conn->prepare("SELECT MAX(option_order) as max_order FROM question_options WHERE question_id = ?");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $order = ($result['max_order'] ?? 0) + 1;
    
    $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isii", $question_id, $option_text, $is_correct ? 1 : 0, $order);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

/**
 * Set correct answer for a question (unsets all others first)
 */
function setCorrectAnswer($conn, $question_id, $option_id) {
    $conn->begin_transaction();
    
    try {
        // First, unset all correct answers for this question
        $stmt = $conn->prepare("UPDATE question_options SET is_correct = 0 WHERE question_id = ?");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        
        // Then set the correct answer
        $stmt = $conn->prepare("UPDATE question_options SET is_correct = 1 WHERE option_id = ?");
        $stmt->bind_param("i", $option_id);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// ==================== QUIZ FUNCTIONS ====================

/**
 * Get or create chapter quiz
 */
function getChapterQuiz($conn, $chapter_id) {
    $stmt = $conn->prepare("SELECT * FROM chapter_quizzes WHERE chapter_id = ?");
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quiz = $result->fetch_assoc();
    
    if (!$quiz) {
        // Create quiz if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO chapter_quizzes (chapter_id, title) VALUES (?, ?)");
        $title = "Chapter Quiz";
        $stmt->bind_param("is", $chapter_id, $title);
        if ($stmt->execute()) {
            $quiz_id = $stmt->insert_id;
            $stmt = $conn->prepare("SELECT * FROM chapter_quizzes WHERE quiz_id = ?");
            $stmt->bind_param("i", $quiz_id);
            $stmt->execute();
            $quiz = $stmt->get_result()->fetch_assoc();
        }
    }
    
    return $quiz;
}

/**
 * Get quiz questions
 */
function getQuizQuestions($conn, $quiz_id) {
    $stmt = $conn->prepare("
        SELECT qq.*, 
               COUNT(qqo.quiz_option_id) as option_count
        FROM quiz_questions qq
        LEFT JOIN quiz_question_options qqo ON qq.quiz_question_id = qqo.quiz_question_id
        WHERE qq.quiz_id = ? 
        GROUP BY qq.quiz_question_id 
        ORDER BY qq.question_order
    ");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get quiz question options
 */
function getQuizQuestionOptions($conn, $quiz_question_id) {
    $stmt = $conn->prepare("SELECT * FROM quiz_question_options WHERE quiz_question_id = ? ORDER BY option_order");
    $stmt->bind_param("i", $quiz_question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Add quiz question
 */
function addQuizQuestion($conn, $quiz_id, $question_text) {
    // Check if we can add more questions (max 30)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM quiz_questions WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] >= 30) {
        return false;
    }
    
    // Get the next order number
    $stmt = $conn->prepare("SELECT MAX(question_order) as max_order FROM quiz_questions WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $order = ($result['max_order'] ?? 0) + 1;
    
    $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_order) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $quiz_id, $question_text, $order);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

/**
 * Add quiz question option
 */
function addQuizQuestionOption($conn, $quiz_question_id, $option_text, $is_correct = false) {
    // Get the next order number
    $stmt = $conn->prepare("SELECT MAX(option_order) as max_order FROM quiz_question_options WHERE quiz_question_id = ?");
    $stmt->bind_param("i", $quiz_question_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $order = ($result['max_order'] ?? 0) + 1;
    
    $stmt = $conn->prepare("INSERT INTO quiz_question_options (quiz_question_id, option_text, is_correct, option_order) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isii", $quiz_question_id, $option_text, $is_correct ? 1 : 0, $order);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

// ==================== PUBLISHING FUNCTIONS ====================

/**
 * Submit program for publishing
 */
function submitForPublishing($conn, $program_ids, $teacher_id) {
    $conn->begin_transaction();
    
    try {
        foreach ($program_ids as $program_id) {
            // Update program status
            $stmt = $conn->prepare("UPDATE programs SET status = 'pending_review' WHERE programID = ? AND teacherID = ?");
            $stmt->bind_param("ii", $program_id, $teacher_id);
            $stmt->execute();
            
            // Create publish request
            $stmt = $conn->prepare("INSERT INTO program_publish_requests (program_id, teacher_id, status) VALUES (?, ?, 'pending')");
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

/**
 * Get programs that are drafts (for publishing)
 */
function getDraftPrograms($conn, $teacher_id) {
    $stmt = $conn->prepare("SELECT programID, title, price, category FROM programs WHERE teacherID = ? AND status = 'draft'");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ==================== UTILITY FUNCTIONS ====================

/**
 * Upload thumbnail image
 */
function uploadThumbnail($file) {
    $upload_dir = '../../uploads/thumbnails/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return false;
    }
    
    $filename = uniqid() . '_' . time() . '.' . $file_ext;
    $destination = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    
    return false;
}

/**
 * Validate YouTube URL
 */
function validateYouTubeUrl($url) {
    $pattern = '/^(https?\:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/';
    return preg_match($pattern, $url);
}

/**
 * Extract YouTube video ID from URL
 */
function getYouTubeVideoId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return false;
}
?>