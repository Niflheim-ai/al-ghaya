<?php
/**
 * Program Helper Functions - Updated for Al-Ghaya Database Schema
 * Contains all program-related functions compatible with chapter_stories table
 * Compatible with existing al-ghaya database schema
 */

require_once 'dbConnection.php';

/**
 * Enhanced Teacher ID retrieval with auto-creation
 * This is the MAIN function for getting teacher IDs - no duplicate in functions.php
 * @param object $conn Database connection
 * @param int $user_id User ID from session
 * @return int|null Teacher ID or null if not found
 */
function getTeacherIdFromSession($conn, $user_id) {
    // First, try to get existing teacher ID
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
    
    // If teacher record doesn't exist, create one automatically
    $userStmt = $conn->prepare("SELECT email, fname, lname FROM user WHERE userID = ? AND role = 'teacher' AND isActive = 1");
    if (!$userStmt) {
        error_log("getTeacherIdFromSession user query prepare failed: " . $conn->error);
        return null;
    }
    
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $userStmt->close();
        
        // Create teacher record
        $insertStmt = $conn->prepare("INSERT INTO teacher (userID, email, username, fname, lname, dateCreated, isActive) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
        if (!$insertStmt) {
            error_log("getTeacherIdFromSession insert prepare failed: " . $conn->error);
            return null;
        }
        
        $username = $user['email']; // Use email as username
        $insertStmt->bind_param("issss", $user_id, $user['email'], $username, $user['fname'], $user['lname']);
        
        if ($insertStmt->execute()) {
            $teacher_id = $insertStmt->insert_id;
            $insertStmt->close();
            return $teacher_id;
        }
        
        $insertStmt->close();
    }
    
    $userStmt->close();
    return null;
}

/**
 * Get all programs for a teacher
 * @param object $conn Database connection
 * @param int $teacher_id Teacher ID
 * @param string $sortBy Sort by field
 * @return array Array of programs
 */
function getTeacherPrograms($conn, $teacher_id, $sortBy = 'dateCreated') {
    // Validate $sortBy to prevent SQL injection
    $allowedSorts = ['dateCreated', 'dateUpdated', 'title', 'price'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'dateCreated'; // Default
    }

    $stmt = $conn->prepare("SELECT * FROM programs WHERE teacherID = ? ORDER BY $sortBy DESC");
    if (!$stmt) {
        error_log("getTeacherPrograms prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $programs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $programs;
}

/**
 * Get a Program by ID (for teacher access)
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param int $teacher_id Teacher ID for ownership verification
 * @return array|null Program data or null if not found
 */
function getProgram($conn, $program_id, $teacher_id) {
    $stmt = $conn->prepare("SELECT * FROM programs WHERE programID = ? AND teacherID = ?");
    if (!$stmt) {
        error_log("getProgram prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("ii", $program_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $program = $result->fetch_assoc();
    $stmt->close();
    return $program;
}

/**
 * Add a Chapter to a Program
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param string $title Chapter title
 * @param string $content Chapter content
 * @param string $question Chapter question
 * @return int|false Chapter ID on success, false on failure
 */
function addChapter($conn, $program_id, $title, $content, $question) {
    // Get next chapter order
    $stmt = $conn->prepare("SELECT MAX(chapter_order) FROM program_chapters WHERE program_id = ?");
    if (!$stmt) {
        error_log("addChapter order query prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $max_order = $stmt->get_result()->fetch_array()[0];
    $chapter_order = $max_order ? $max_order + 1 : 1;
    $stmt->close();

    // Insert chapter
    $stmt = $conn->prepare("INSERT INTO program_chapters (program_id, title, content, question, chapter_order)
                        VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("addChapter insert prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isssi", $program_id, $title, $content, $question, $chapter_order);

    if ($stmt->execute()) {
        $chapter_id = $stmt->insert_id;
        $stmt->close();
        return $chapter_id;
    } else {
        $error = $stmt->error;
        $stmt->close();
        error_log("addChapter execute failed: " . $error);
        return false;
    }
}

/**
 * Update a Chapter
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @param string $title Chapter title
 * @param string $content Chapter content
 * @param string $question Chapter question
 * @return bool True on success, false on failure
 */
function updateChapter($conn, $chapter_id, $title, $content, $question) {
    $stmt = $conn->prepare("UPDATE program_chapters SET title = ?, content = ?, question = ?
                        WHERE chapter_id = ?");
    if (!$stmt) {
        error_log("updateChapter prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("sssi", $title, $content, $question, $chapter_id);

    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    } else {
        $error = $stmt->error;
        $stmt->close();
        error_log("updateChapter execute failed: " . $error);
        return false;
    }
}

/**
 * Delete a Chapter and all its related stories and interactive sections
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @return bool True on success, false on failure
 */
function deleteChapter($conn, $chapter_id) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, get all stories in this chapter
        $stories = getChapterStories($conn, $chapter_id);
        
        // Delete all interactive sections for each story
        foreach ($stories as $story) {
            deleteStoryInteractiveSections($conn, $story['story_id']);
        }
        
        // Delete all stories in this chapter
        $stmt1 = $conn->prepare("DELETE FROM chapter_stories WHERE chapter_id = ?");
        if ($stmt1) {
            $stmt1->bind_param("i", $chapter_id);
            $stmt1->execute();
            $stmt1->close();
        }
        
        // Delete all quizzes in this chapter
        $stmt2 = $conn->prepare("DELETE FROM chapter_quizzes WHERE chapter_id = ?");
        if ($stmt2) {
            $stmt2->bind_param("i", $chapter_id);
            $stmt2->execute();
            $stmt2->close();
        }
        
        // Finally, delete the chapter itself
        $stmt = $conn->prepare("DELETE FROM program_chapters WHERE chapter_id = ?");
        if (!$stmt) {
            throw new Exception("deleteChapter prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $chapter_id);
        
        if (!$stmt->execute()) {
            throw new Exception("deleteChapter execute failed: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        $conn->commit();
        return $affected > 0;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("deleteChapter transaction failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete all interactive sections for a story
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return bool True on success, false on failure
 */
function deleteStoryInteractiveSections($conn, $story_id) {
    // Get all sections for this story
    $sections = getStoryInteractiveSections($conn, $story_id);
    
    foreach ($sections as $section) {
        // Delete all questions in this section
        $stmt1 = $conn->prepare("DELETE FROM interactive_questions WHERE section_id = ?");
        if ($stmt1) {
            $stmt1->bind_param("i", $section['section_id']);
            $stmt1->execute();
            $stmt1->close();
        }
        
        // Delete the section
        $stmt2 = $conn->prepare("DELETE FROM story_interactive_sections WHERE section_id = ?");
        if ($stmt2) {
            $stmt2->bind_param("i", $section['section_id']);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    
    return true;
}

/**
 * Get Chapters for a Program
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @return array Array of chapters
 */
function getChapters($conn, $program_id) {
    $stmt = $conn->prepare("SELECT * FROM program_chapters WHERE program_id = ? ORDER BY chapter_order");
    if (!$stmt) {
        error_log("getChapters prepare failed: " . $conn->error);
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
 * Get stories for a specific chapter using chapter_stories table
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @return array Array of stories
 */
function getChapterStories($conn, $chapter_id) {
    // Check if chapter_stories table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_stories'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE chapter_id = ? ORDER BY story_order ASC");
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
    // Check if story_interactive_sections table exists (updated table name from database)
    $tableCheck = $conn->query("SHOW TABLES LIKE 'story_interactive_sections'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM story_interactive_sections WHERE story_id = ? ORDER BY section_order ASC");
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
 * Get chapters for a specific program (alternative name to getChapters)
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @return array Array of program chapters
 */
function getProgramChapters($conn, $program_id) {
    return getChapters($conn, $program_id);
}

/**
 * Get questions for a specific interactive section
 * @param object $conn Database connection
 * @param int $section_id Section ID (updated from interaction_id)
 * @return array Array of section questions
 */
function getSectionQuestions($conn, $section_id) {
    // Check if interactive_questions table exists (updated table name from database)
    $tableCheck = $conn->query("SHOW TABLES LIKE 'interactive_questions'");
    if ($tableCheck->num_rows == 0) {
        return []; // Return empty array if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM interactive_questions WHERE section_id = ? ORDER BY question_order ASC");
    if (!$stmt) {
        error_log("getSectionQuestions prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $section_id);
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
 * Get a specific story by ID from chapter_stories table
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return array|null Story data or null if not found
 */
function getStory($conn, $story_id) {
    // Check if chapter_stories table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_stories'");
    if ($tableCheck->num_rows == 0) {
        return null; // Return null if table doesn't exist
    }
    
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE story_id = ?");
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
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/ | .*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return false;
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
 * Create a new program with support for new fields
 * @param object $conn Database connection
 * @param array $data Program data
 * @return int|false Program ID on success, false on failure
 */
function createProgram($conn, $data) {
    $sql = "INSERT INTO programs (teacherID, title, description, difficulty_label, category, price, thumbnail, status, overview_video_url, dateCreated, dateUpdated) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("createProgram prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issssdss", 
        $data['teacherID'],
        $data['title'],
        $data['description'],
        $data['difficulty_label'],
        $data['category'],
        $data['price'],
        $data['thumbnail'],
        $data['status'],
        $data['overview_video_url']
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
 * Update an existing program with support for new fields
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param array $data Program data
 * @return bool True on success, false on failure
 */
function updateProgram($conn, $program_id, $data) {
    $sql = "UPDATE programs SET title = ?, description = ?, difficulty_label = ?, category = ?, price = ?, status = ?, overview_video_url = ?, dateUpdated = NOW()
            WHERE programID = ? AND teacherID = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("updateProgram prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ssssdsii", 
        $data['title'],
        $data['description'],
        $data['difficulty_label'],
        $data['category'],
        $data['price'],
        $data['status'],
        $data['overview_video_url'],
        $program_id,
        $data['teacherID']
    );
    
    if ($stmt->execute()) {
        $success = $stmt->affected_rows > 0 || $stmt->affected_rows === 0; // Include 0 for no changes made
        $stmt->close();
        return $success;
    }
    
    error_log("updateProgram execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

// Legacy function aliases for backward compatibility
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