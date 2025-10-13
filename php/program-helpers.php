<?php
/**
 * Program Helper Functions
 * Contains all the missing functions referenced in components and pages
 * Compatible with existing al-ghaya database schema
 */

require_once 'dbConnection.php';

/**
 * Get stories for a specific chapter
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @return array Array of stories
 */
function getChapterStories($conn, $chapter_id) {
    // Check if program_stories table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'program_stories'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM program_stories WHERE chapter_id = ? ORDER BY story_order ASC");
    if (!$stmt) {
        error_log("getChapterStories prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stories = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $stories;
}

/**
 * Get quiz for a specific chapter
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @return array|null Quiz data or null if not found
 */
function getChapterQuiz($conn, $chapter_id) {
    // Check if chapter_quizzes table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_quizzes'");
    if ($tableCheck->num_rows == 0) {
        return null; // Return null if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM chapter_quizzes WHERE chapter_id = ? LIMIT 1");
    if (!$stmt) {
        error_log("getChapterQuiz prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quiz = $result->fetch_assoc();
    $stmt->close();
    
    return $quiz;
}

/**
 * Get interactive sections for a specific story
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return array Array of interactive sections
 */
function getStoryInteractiveSections($conn, $story_id) {
    // Check if story_interactions table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'story_interactions'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM story_interactions WHERE story_id = ? ORDER BY section_order ASC");
    if (!$stmt) {
        error_log("getStoryInteractiveSections prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $story_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sections = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $sections;
}

/**
 * Get questions for a specific quiz
 * @param object $conn Database connection
 * @param int $quiz_id Quiz ID
 * @return array Array of quiz questions
 */
function getQuizQuestions($conn, $quiz_id) {
    // Check if quiz_questions table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'quiz_questions'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order ASC");
    if (!$stmt) {
        error_log("getQuizQuestions prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $questions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $questions;
}

/**
 * Get chapters for a specific program
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @return array Array of program chapters
 */
function getProgramChapters($conn, $program_id) {
    // Check if program_chapters table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'program_chapters'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM program_chapters WHERE program_id = ? ORDER BY chapter_order ASC");
    if (!$stmt) {
        error_log("getProgramChapters prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chapters = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $chapters;
}

/**
 * Get questions for a specific interactive section
 * @param object $conn Database connection
 * @param int $interaction_id Interaction ID
 * @return array Array of section questions
 */
function getSectionQuestions($conn, $interaction_id) {
    // Check if interaction_questions table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'interaction_questions'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM interaction_questions WHERE interaction_id = ? ORDER BY question_order ASC");
    if (!$stmt) {
        error_log("getSectionQuestions prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $interaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $questions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $questions;
}

/**
 * Get options for a specific question
 * @param object $conn Database connection
 * @param int $question_id Question ID
 * @return array Array of question options
 */
function getQuestionOptions($conn, $question_id) {
    // Check if question_options table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'question_options'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY option_order ASC");
    if (!$stmt) {
        error_log("getQuestionOptions prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $options = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $options;
}

/**
 * Get a specific chapter by ID
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @return array|null Chapter data or null if not found
 */
function getChapter($conn, $chapter_id) {
    $stmt = $conn->prepare("SELECT * FROM program_chapters WHERE chapter_id = ?");
    if (!$stmt) {
        error_log("getChapter prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chapter = $result->fetch_assoc();
    $stmt->close();
    
    return $chapter;
}

/**
 * Get a specific story by ID
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return array|null Story data or null if not found
 */
function getStory($conn, $story_id) {
    // Check if program_stories table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'program_stories'");
    if ($tableCheck->num_rows == 0) {
        return null; // Return null if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM program_stories WHERE story_id = ?");
    if (!$stmt) {
        error_log("getStory prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $story_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $story = $result->fetch_assoc();
    $stmt->close();
    
    return $story;
}

/**
 * Upload thumbnail image
 * @param array $file Uploaded file array from $_FILES
 * @return string|false Filename on success, false on failure
 */
function uploadThumbnail($file) {
    // Determine upload directory based on current script location
    $upload_dir = __DIR__ . '/../uploads/thumbnails/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("Failed to create upload directory: " . $upload_dir);
            return false;
        }
    }
    
    // Validate file
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error: " . ($file['error'] ?? 'Unknown error'));
        return false;
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("File too large: " . $file['size']);
        return false;
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        error_log("Invalid file type: " . $mime_type);
        return false;
    }
    
    // Generate unique filename
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('thumb_', true) . '.' . $file_ext;
    $destination = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    
    error_log("Failed to move uploaded file to: " . $destination);
    return false;
}

/**
 * Validate YouTube URL
 * @param string $url YouTube URL to validate
 * @return bool True if valid YouTube URL, false otherwise
 */
function validateYouTubeUrl($url) {
    if (empty($url)) {
        return true; // Allow empty URLs
    }
    
    $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/|v\/)|youtu\.be\/)([\w\-_]{11})(\S*)?$/';
    return preg_match($pattern, $url) === 1;
}

/**
 * Extract YouTube video ID from URL
 * @param string $url YouTube URL
 * @return string|false Video ID on success, false on failure
 */
function getYouTubeVideoId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return false;
}

/**
 * Get teacher ID from session (wrapper for existing function)
 * @param object $conn Database connection
 * @param int $user_id User ID from session
 * @return int|null Teacher ID or null if not found
 */
function getTeacherIdFromSession($conn, $user_id) {
    $stmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ? AND isActive = 1");
    if (!$stmt) {
        error_log("getTeacherIdFromSession prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['teacherID'];
    }
    
    $stmt->close();
    return null;
}

/**
 * Verify program ownership by teacher
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param int $teacher_id Teacher ID
 * @return bool True if teacher owns program, false otherwise
 */
function verifyProgramOwnership($conn, $program_id, $teacher_id) {
    $stmt = $conn->prepare("SELECT 1 FROM programs WHERE programID = ? AND teacherID = ?");
    if (!$stmt) {
        error_log("verifyProgramOwnership prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $program_id, $teacher_id);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Create a new program
 * @param object $conn Database connection
 * @param array $data Program data
 * @return int|false Program ID on success, false on failure
 */
function createProgram($conn, $data) {
    $sql = "INSERT INTO programs (teacherID, title, description, category, price, thumbnail, status, dateCreated, dateUpdated) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("createProgram prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isssdss", 
        $data['teacherID'],
        $data['title'],
        $data['description'],
        $data['category'],
        $data['price'],
        $data['thumbnail'],
        $data['status']
    );
    
    if ($stmt->execute()) {
        $program_id = $stmt->insert_id;
        $stmt->close();
        return $program_id;
    }
    
    error_log("createProgram execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Update an existing program
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param array $data Program data
 * @return bool True on success, false on failure
 */
function updateProgram($conn, $program_id, $data) {
    $sql = "UPDATE programs SET title = ?, description = ?, category = ?, price = ?, status = ?, dateUpdated = NOW()
            WHERE programID = ? AND teacherID = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("updateProgram prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("sssdii", 
        $data['title'],
        $data['description'],
        $data['category'],
        $data['price'],
        $data['status'],
        $program_id,
        $data['teacherID']
    );
    
    if ($stmt->execute()) {
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
    
    error_log("updateProgram execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Safe wrapper functions for component compatibility
 * These handle cases where functions are called without connection parameter
 */

// Global connection wrapper functions for backward compatibility
if (!function_exists('getChapterStories_wrapper')) {
    function getChapterStories_wrapper($chapter_id) {
        global $conn;
        return getChapterStories($conn, $chapter_id);
    }
}

if (!function_exists('getChapterQuiz_wrapper')) {
    function getChapterQuiz_wrapper($chapter_id) {
        global $conn;
        return getChapterQuiz($conn, $chapter_id);
    }
}

if (!function_exists('getProgramChapters_wrapper')) {
    function getProgramChapters_wrapper($program_id) {
        global $conn;
        return getProgramChapters($conn, $program_id);
    }
}

?>
