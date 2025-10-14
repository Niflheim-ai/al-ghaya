<?php
/**
 * CENTRALIZED PROGRAM HANDLER - Al-Ghaya LMS
 * Single source of truth for all program, chapter, story, and interactive section operations
 * Prevents function redeclaration errors and ensures database schema compatibility
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbConnection.php';
require_once 'functions.php';

// ==================== AUTHENTICATION & SETUP ====================

/**
 * Check if user is logged in and is a teacher
 */
function validateTeacherAccess() {
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
        return false;
    }
    return true;
}

/**
 * Get teacher ID from session with auto-creation
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
        
        $username = $user['email'];
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

// ==================== PROGRAM FUNCTIONS ====================

/**
 * Create a new program
 * @param object $conn Database connection
 * @param array $data Program data
 * @return int|false Program ID on success, false on failure
 */
function program_create($conn, $data) {
    $sql = "INSERT INTO programs (teacherID, title, description, difficulty_label, category, price, thumbnail, status, overview_video_url, dateCreated, dateUpdated) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("program_create prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issssdsss", 
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
    
    error_log("program_create execute failed: " . $stmt->error);
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
function program_update($conn, $program_id, $data) {
    $sql = "UPDATE programs SET title = ?, description = ?, difficulty_label = ?, category = ?, price = ?, status = ?, overview_video_url = ?, dateUpdated = NOW()
            WHERE programID = ? AND teacherID = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("program_update prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ssssdsiii", 
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
        $success = $stmt->affected_rows >= 0;
        $stmt->close();
        return $success;
    }
    
    error_log("program_update execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Get a program by ID with teacher ownership verification
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param int $teacher_id Teacher ID for ownership verification
 * @return array|null Program data or null if not found
 */
function program_getById($conn, $program_id, $teacher_id) {
    $stmt = $conn->prepare("SELECT * FROM programs WHERE programID = ? AND teacherID = ?");
    if (!$stmt) {
        error_log("program_getById prepare failed: " . $conn->error);
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
 * Get all programs for a teacher
 * @param object $conn Database connection
 * @param int $teacher_id Teacher ID
 * @param string $sortBy Sort by field
 * @return array Array of programs
 */
function program_getByTeacher($conn, $teacher_id, $sortBy = 'dateCreated') {
    $allowedSorts = ['dateCreated', 'dateUpdated', 'title', 'price'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'dateCreated';
    }

    $stmt = $conn->prepare("SELECT * FROM programs WHERE teacherID = ? ORDER BY $sortBy DESC");
    if (!$stmt) {
        error_log("program_getByTeacher prepare failed: " . $conn->error);
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
 * Verify program ownership by teacher
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param int $teacher_id Teacher ID
 * @return bool True if teacher owns program, false otherwise
 */
function program_verifyOwnership($conn, $program_id, $teacher_id) {
    $stmt = $conn->prepare("SELECT 1 FROM programs WHERE programID = ? AND teacherID = ?");
    if (!$stmt) {
        error_log("program_verifyOwnership prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $program_id, $teacher_id);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

// ==================== CHAPTER FUNCTIONS ====================

/**
 * Add a chapter to a program
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @param string $title Chapter title
 * @param string $content Chapter content
 * @param string $question Chapter question
 * @return int|false Chapter ID on success, false on failure
 */
function chapter_add($conn, $program_id, $title, $content = '', $question = '') {
    // Get next chapter order
    $stmt = $conn->prepare("SELECT MAX(chapter_order) FROM program_chapters WHERE program_id = ?");
    if (!$stmt) {
        error_log("chapter_add order query prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $max_order = $stmt->get_result()->fetch_array()[0];
    $chapter_order = $max_order ? $max_order + 1 : 1;
    $stmt->close();

    // Insert chapter
    $stmt = $conn->prepare("INSERT INTO program_chapters (program_id, title, content, question, chapter_order) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("chapter_add insert prepare failed: " . $conn->error);
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
        error_log("chapter_add execute failed: " . $error);
        return false;
    }
}

/**
 * Update a chapter
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @param string $title Chapter title
 * @param string $content Chapter content
 * @param string $question Chapter question
 * @return bool True on success, false on failure
 */
function chapter_update($conn, $chapter_id, $title, $content, $question) {
    $stmt = $conn->prepare("UPDATE program_chapters SET title = ?, content = ?, question = ? WHERE chapter_id = ?");
    if (!$stmt) {
        error_log("chapter_update prepare failed: " . $conn->error);
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
        error_log("chapter_update execute failed: " . $error);
        return false;
    }
}

/**
 * Delete a chapter and all its related content
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @return bool True on success, false on failure
 */
function chapter_delete($conn, $chapter_id) {
    $conn->begin_transaction();
    
    try {
        // Get all stories in this chapter
        $stories = chapter_getStories($conn, $chapter_id);
        
        // Delete all interactive sections for each story
        foreach ($stories as $story) {
            story_deleteInteractiveSections($conn, $story['story_id']);
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
        
        // Delete the chapter itself
        $stmt = $conn->prepare("DELETE FROM program_chapters WHERE chapter_id = ?");
        if (!$stmt) {
            throw new Exception("chapter_delete prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $chapter_id);
        
        if (!$stmt->execute()) {
            throw new Exception("chapter_delete execute failed: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        $conn->commit();
        return $affected > 0;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("chapter_delete transaction failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get chapters for a program
 * @param object $conn Database connection
 * @param int $program_id Program ID
 * @return array Array of chapters
 */
function chapter_getByProgram($conn, $program_id) {
    $stmt = $conn->prepare("SELECT * FROM program_chapters WHERE program_id = ? ORDER BY chapter_order");
    if (!$stmt) {
        error_log("chapter_getByProgram prepare failed: " . $conn->error);
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
 * Get a specific chapter by ID
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @return array|null Chapter data or null if not found
 */
function chapter_getById($conn, $chapter_id) {
    $stmt = $conn->prepare("SELECT * FROM program_chapters WHERE chapter_id = ?");
    if (!$stmt) {
        error_log("chapter_getById prepare failed: " . $conn->error);
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
 * Get stories for a specific chapter
 * @param object $conn Database connection
 * @param int $chapter_id Chapter ID
 * @return array Array of stories
 */
function chapter_getStories($conn, $chapter_id) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_stories'");
    if ($tableCheck->num_rows == 0) {
        return [];
    }
    
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE chapter_id = ? ORDER BY story_order ASC");
    if (!$stmt) {
        error_log("chapter_getStories prepare failed: " . $conn->error);
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
function chapter_getQuiz($conn, $chapter_id) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_quizzes'");
    if ($tableCheck->num_rows == 0) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM chapter_quizzes WHERE chapter_id = ? LIMIT 1");
    if (!$stmt) {
        error_log("chapter_getQuiz prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quiz = $result->fetch_assoc();
    $stmt->close();
    
    return $quiz;
}

// ==================== STORY FUNCTIONS ====================

/**
 * Create a new story record
 * @param object $conn Database connection
 * @param array $data Story data
 * @return int|false Story ID on success, false on failure
 */
function story_create($conn, $data) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_stories'");
    if ($tableCheck->num_rows == 0) {
        error_log("chapter_stories table does not exist");
        return false;
    }
    
    // Get the next story order for this chapter
    $orderQuery = "SELECT COALESCE(MAX(story_order), 0) + 1 as next_order FROM chapter_stories WHERE chapter_id = ?";
    $orderStmt = $conn->prepare($orderQuery);
    if (!$orderStmt) {
        error_log("story_create order query prepare failed: " . $conn->error);
        return false;
    }
    
    $orderStmt->bind_param("i", $data['chapter_id']);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $next_order = $orderResult->fetch_assoc()['next_order'];
    $orderStmt->close();
    
    // Insert the story
    $sql = "INSERT INTO chapter_stories (chapter_id, title, synopsisarabic, synopsisenglish, videourl, story_order, dateCreated) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("story_create prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issssi", 
        $data['chapter_id'],
        $data['title'],
        $data['synopsis_arabic'],
        $data['synopsis_english'],
        $data['video_url'],
        $next_order
    );
    
    if ($stmt->execute()) {
        $story_id = $stmt->insert_id;
        $stmt->close();
        return $story_id;
    }
    
    error_log("story_create execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Delete a story record
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return bool True on success, false on failure
 */
function story_delete($conn, $story_id) {
    // First delete all related interactive sections
    story_deleteInteractiveSections($conn, $story_id);
    
    $stmt = $conn->prepare("DELETE FROM chapter_stories WHERE story_id = ?");
    if (!$stmt) {
        error_log("story_delete prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $story_id);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }
    
    error_log("story_delete execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Get story by ID
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return array|null Story data or null if not found
 */
function story_getById($conn, $story_id) {
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE story_id = ?");
    if (!$stmt) {
        error_log("story_getById prepare failed: " . $conn->error);
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
 * Get interactive sections for a story
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return array Array of interactive sections
 */
function story_getInteractiveSections($conn, $story_id) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'storyinteractivesections'");
    if ($tableCheck->num_rows == 0) {
        return [];
    }
    
    $stmt = $conn->prepare("SELECT * FROM storyinteractivesections WHERE story_id = ? ORDER BY sectionorder ASC");
    if (!$stmt) {
        error_log("story_getInteractiveSections prepare failed: " . $conn->error);
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
 * Delete all interactive sections for a story
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return bool True on success, false on failure
 */
function story_deleteInteractiveSections($conn, $story_id) {
    $sections = story_getInteractiveSections($conn, $story_id);
    
    foreach ($sections as $section) {
        // Delete all questions in this section
        $stmt1 = $conn->prepare("DELETE FROM interactivequestions WHERE sectionid = ?");
        if ($stmt1) {
            $stmt1->bind_param("i", $section['sectionid']);
            $stmt1->execute();
            $stmt1->close();
        }
        
        // Delete the section
        $stmt2 = $conn->prepare("DELETE FROM storyinteractivesections WHERE sectionid = ?");
        if ($stmt2) {
            $stmt2->bind_param("i", $section['sectionid']);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    
    return true;
}

// ==================== INTERACTIVE SECTION FUNCTIONS ====================

/**
 * Create interactive section for a story
 * @param object $conn Database connection
 * @param int $story_id Story ID
 * @return int|false Section ID on success, false on failure
 */
function section_create($conn, $story_id) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'storyinteractivesections'");
    if ($tableCheck->num_rows == 0) {
        error_log("storyinteractivesections table does not exist");
        return false;
    }
    
    // Get the next section order
    $orderQuery = "SELECT COALESCE(MAX(sectionorder), 0) + 1 as next_order FROM storyinteractivesections WHERE story_id = ?";
    $orderStmt = $conn->prepare($orderQuery);
    if (!$orderStmt) {
        error_log("section_create order query prepare failed: " . $conn->error);
        return false;
    }
    
    $orderStmt->bind_param("i", $story_id);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $next_order = $orderResult->fetch_assoc()['next_order'];
    $orderStmt->close();
    
    // Insert the section
    $sql = "INSERT INTO storyinteractivesections (story_id, sectionorder, dateCreated) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("section_create prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $story_id, $next_order);
    
    if ($stmt->execute()) {
        $section_id = $stmt->insert_id;
        $stmt->close();
        return $section_id;
    }
    
    error_log("section_create execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Delete interactive section
 * @param object $conn Database connection
 * @param int $section_id Section ID
 * @return bool True on success, false on failure
 */
function section_delete($conn, $section_id) {
    // First delete all questions in this section
    $stmt1 = $conn->prepare("DELETE FROM interactivequestions WHERE sectionid = ?");
    if ($stmt1) {
        $stmt1->bind_param("i", $section_id);
        $stmt1->execute();
        $stmt1->close();
    }
    
    // Then delete the section
    $stmt = $conn->prepare("DELETE FROM storyinteractivesections WHERE sectionid = ?");
    if (!$stmt) {
        error_log("section_delete prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $section_id);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }
    
    error_log("section_delete execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

/**
 * Get questions for a specific interactive section
 * @param object $conn Database connection
 * @param int $section_id Section ID
 * @return array Array of section questions
 */
function section_getQuestions($conn, $section_id) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'interactivequestions'");
    if ($tableCheck->num_rows == 0) {
        return [];
    }
    
    $stmt = $conn->prepare("SELECT * FROM interactivequestions WHERE sectionid = ? ORDER BY questionorder ASC");
    if (!$stmt) {
        error_log("section_getQuestions prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $questions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $questions;
}

// ==================== UTILITY FUNCTIONS ====================

/**
 * Map difficulty level to category
 * @param string $difficulty_level Difficulty level (Student, Aspiring, Master)
 * @return string Category (beginner, intermediate, advanced)
 */
function mapDifficultyToCategory($difficulty_level) {
    switch ($difficulty_level) {
        case 'Student':
            return 'beginner';
        case 'Aspiring':
            return 'intermediate';
        case 'Master':
            return 'advanced';
        default:
            return 'beginner';
    }
}

/**
 * Upload thumbnail image
 * @param array $file Uploaded file array from $_FILES
 * @return string|false Filename on success, false on failure
 */
function uploadThumbnail($file) {
    $upload_dir = __DIR__ . '/../uploads/thumbnails/';
    
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("Failed to create upload directory: " . $upload_dir);
            return false;
        }
    }
    
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error: " . ($file['error'] ?? 'Unknown error'));
        return false;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("File too large: " . $file['size']);
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        error_log("Invalid file type: " . $mime_type);
        return false;
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('thumb_', true) . '.' . $file_ext;
    $destination = $upload_dir . $filename;
    
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
        return true;
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

// ==================== REQUEST HANDLER ====================

// Only process requests if this file is called directly (not included)
if (basename($_SERVER['PHP_SELF']) === 'program-handler.php') {
    
    // Validate teacher access
    if (!validateTeacherAccess()) {
        if (isset($_POST['action']) && in_array($_POST['action'], ['create_program', 'update_program', 'create_story'])) {
            $_SESSION['error_message'] = 'Unauthorized access';
            header('Location: ../pages/teacher/teacher-programs.php');
            exit;
        }
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    $user_id = $_SESSION['userID'];
    $teacher_id = getTeacherIdFromSession($conn, $user_id);

    if (!$teacher_id) {
        if (isset($_POST['action']) && in_array($_POST['action'], ['create_program', 'update_program', 'create_story'])) {
            $_SESSION['error_message'] = 'Teacher profile not found';
            header('Location: ../pages/teacher/teacher-programs.php');
            exit;
        }
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Teacher profile not found']);
        exit;
    }

    // Get action from request
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // Handle JSON requests
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $action = $input['action'] ?? $action;
            $_POST = array_merge($_POST, $input);
        }
    }

    try {
        switch ($action) {
            
            // ==================== PROGRAM OPERATIONS ====================
            
            case 'create_program':
                $data = [
                    'teacherID' => $teacher_id,
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'difficulty_label' => $_POST['difficulty_level'] ?? 'Student',
                    'category' => mapDifficultyToCategory($_POST['difficulty_level'] ?? 'Student'),
                    'price' => floatval($_POST['price'] ?? 0),
                    'status' => $_POST['status'] ?? 'draft',
                    'thumbnail' => 'default-thumbnail.jpg',
                    'overview_video_url' => trim($_POST['overview_video_url'] ?? '')
                ];
                
                if (empty($data['title']) || strlen($data['title']) < 3) {
                    $_SESSION['error_message'] = 'Program title must be at least 3 characters long';
                    header('Location: ../pages/teacher/teacher-programs.php?action=create');
                    exit;
                }
                
                if (empty($data['description']) || strlen($data['description']) < 10) {
                    $_SESSION['error_message'] = 'Program description must be at least 10 characters long';
                    header('Location: ../pages/teacher/teacher-programs.php?action=create');
                    exit;
                }
                
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $uploaded_thumbnail = uploadThumbnail($_FILES['thumbnail']);
                    if ($uploaded_thumbnail) {
                        $data['thumbnail'] = $uploaded_thumbnail;
                    }
                }
                
                $program_id = program_create($conn, $data);
                if ($program_id) {
                    chapter_add($conn, $program_id, 'Introduction', 'Welcome to this program!', '');
                    $_SESSION['success_message'] = 'Program created successfully!';
                    header('Location: ../pages/teacher/teacher-programs.php?action=create&program_id=' . $program_id);
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Failed to create program. Please try again.';
                    header('Location: ../pages/teacher/teacher-programs.php?action=create');
                    exit;
                }
                break;
                
            case 'update_program':
                $program_id = intval($_POST['programID'] ?? 0);
                if (!$program_id || !program_verifyOwnership($conn, $program_id, $teacher_id)) {
                    $_SESSION['error_message'] = 'Invalid program or access denied.';
                    header('Location: ../pages/teacher/teacher-programs.php');
                    exit;
                }
                
                $data = [
                    'teacherID' => $teacher_id,
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'difficulty_label' => $_POST['difficulty_level'] ?? 'Student',
                    'category' => mapDifficultyToCategory($_POST['difficulty_level'] ?? 'Student'),
                    'price' => floatval($_POST['price'] ?? 0),
                    'status' => $_POST['status'] ?? 'draft',
                    'overview_video_url' => trim($_POST['overview_video_url'] ?? '')
                ];
                
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $uploaded_thumbnail = uploadThumbnail($_FILES['thumbnail']);
                    if ($uploaded_thumbnail) {
                        $data['thumbnail'] = $uploaded_thumbnail;
                    }
                }
                
                if (program_update($conn, $program_id, $data)) {
                    $_SESSION['success_message'] = 'Program updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'No changes made or error updating program.';
                }
                
                header('Location: ../pages/teacher/teacher-programs.php?action=create&program_id=' . $program_id);
                exit;
                break;
                
            case 'create_story':
                $program_id = intval($_POST['programID'] ?? 0);
                $chapter_id = intval($_POST['chapter_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $synopsis_arabic = trim($_POST['synopsis_arabic'] ?? '');
                $synopsis_english = trim($_POST['synopsis_english'] ?? '');
                $video_url = trim($_POST['video_url'] ?? '');
                
                if (empty($title) || empty($synopsis_arabic) || empty($synopsis_english) || empty($video_url)) {
                    $_SESSION['error_message'] = 'All fields are required for the story.';
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
                    exit;
                }
                
                if (!program_verifyOwnership($conn, $program_id, $teacher_id)) {
                    $_SESSION['error_message'] = 'Access denied to this program.';
                    header('Location: ../pages/teacher/teacher-programs.php');
                    exit;
                }
                
                $existingStories = chapter_getStories($conn, $chapter_id);
                if (count($existingStories) >= 3) {
                    $_SESSION['error_message'] = 'Maximum of 3 stories per chapter allowed.';
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
                    exit;
                }
                
                $story_id = story_create($conn, [
                    'chapter_id' => $chapter_id,
                    'title' => $title,
                    'synopsis_arabic' => $synopsis_arabic,
                    'synopsis_english' => $synopsis_english,
                    'video_url' => $video_url
                ]);
                
                if ($story_id) {
                    $_SESSION['success_message'] = 'Story created successfully!';
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id);
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Failed to save story. Please try again.';
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id);
                    exit;
                }
                break;
                
            // ==================== AJAX OPERATIONS ====================
            
            case 'create_chapter':
                header('Content-Type: application/json');
                $program_id = intval($_POST['programID'] ?? 0);
                $title = trim($_POST['title'] ?? 'New Chapter');
                
                if (!$program_id || !program_verifyOwnership($conn, $program_id, $teacher_id)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid program or no permission']);
                    exit;
                }
                
                $chapter_id = chapter_add($conn, $program_id, $title);
                if ($chapter_id) {
                    echo json_encode([
                        'success' => true,
                        'chapter_id' => $chapter_id,
                        'programID' => $program_id,
                        'message' => 'Chapter created successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create chapter']);
                }
                exit;
                break;
                
            case 'delete_chapter':
                header('Content-Type: application/json');
                $chapter_id = intval($_POST['chapter_id'] ?? 0);
                $program_id = intval($_POST['programID'] ?? 0);
                
                if (!$program_id || !program_verifyOwnership($conn, $program_id, $teacher_id)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid program or no permission']);
                    exit;
                }
                
                if (chapter_delete($conn, $chapter_id)) {
                    echo json_encode(['success' => true, 'message' => 'Chapter deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete chapter']);
                }
                exit;
                break;
                
            case 'delete_story':
                header('Content-Type: application/json');
                $story_id = intval($_POST['story_id'] ?? 0);
                
                if (!$story_id) {
                    echo json_encode(['success' => false, 'message' => 'Story ID required']);
                    exit;
                }
                
                $story = story_getById($conn, $story_id);
                if (!$story) {
                    echo json_encode(['success' => false, 'message' => 'Story not found']);
                    exit;
                }
                
                $chapter = chapter_getById($conn, $story['chapter_id']);
                if (!$chapter || !program_verifyOwnership($conn, $chapter['programid'], $teacher_id)) {
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    exit;
                }
                
                $existingStories = chapter_getStories($conn, $story['chapter_id']);
                if (count($existingStories) <= 1) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete the last story. Each chapter must have at least 1 story.']);
                    exit;
                }
                
                if (story_delete($conn, $story_id)) {
                    echo json_encode(['success' => true, 'message' => 'Story deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete story']);
                }
                exit;
                break;
                
            case 'create_interactive_section':
                header('Content-Type: application/json');
                $story_id = intval($_POST['story_id'] ?? 0);
                
                if (!$story_id) {
                    echo json_encode(['success' => false, 'message' => 'Story ID required']);
                    exit;
                }
                
                $story = story_getById($conn, $story_id);
                if (!$story) {
                    echo json_encode(['success' => false, 'message' => 'Story not found']);
                    exit;
                }
                
                $chapter = chapter_getById($conn, $story['chapter_id']);
                if (!$chapter || !program_verifyOwnership($conn, $chapter['programid'], $teacher_id)) {
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    exit;
                }
                
                $existingSections = story_getInteractiveSections($conn, $story_id);
                if (count($existingSections) >= 3) {
                    echo json_encode(['success' => false, 'message' => 'Maximum of 3 interactive sections per story allowed']);
                    exit;
                }
                
                $section_id = section_create($conn, $story_id);
                if ($section_id) {
                    echo json_encode([
                        'success' => true,
                        'section_id' => $section_id,
                        'message' => 'Interactive section created successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create interactive section']);
                }
                exit;
                break;
                
            case 'validate_youtube_url':
                header('Content-Type: application/json');
                $url = $_POST['url'] ?? '';
                $is_valid = validateYouTubeUrl($url);
                $video_id = $is_valid ? getYouTubeVideoId($url) : null;
                
                echo json_encode([
                    'success' => true,
                    'is_valid' => $is_valid,
                    'video_id' => $video_id
                ]);
                exit;
                break;
                
            case 'get_chapters':
                header('Content-Type: application/json');
                $program_id = intval($_POST['programID'] ?? 0);
                if (!$program_id || !program_verifyOwnership($conn, $program_id, $teacher_id)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid program or no permission']);
                    exit;
                }
                
                $chapters = chapter_getByProgram($conn, $program_id);
                echo json_encode(['success' => true, 'chapters' => $chapters]);
                exit;
                break;
                
            default:
                if (in_array($action, ['create_program', 'update_program', 'create_story'])) {
                    $_SESSION['error_message'] = 'Invalid action: ' . $action;
                    header('Location: ../pages/teacher/teacher-programs.php');
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
                    exit;
                }
        }
        
    } catch (Exception $e) {
        error_log("Program Handler Error: " . $e->getMessage());
        
        if (in_array($action, ['create_program', 'update_program', 'create_story'])) {
            $_SESSION['error_message'] = 'Server error: ' . $e->getMessage();
            header('Location: ../pages/teacher/teacher-programs.php');
            exit;
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    }
}

// ==================== LEGACY COMPATIBILITY FUNCTIONS ====================

// Provide backward compatibility for existing code
function getTeacherPrograms($conn, $teacher_id, $sortBy = 'dateCreated') {
    return program_getByTeacher($conn, $teacher_id, $sortBy);
}

function getProgram($conn, $program_id, $teacher_id) {
    return program_getById($conn, $program_id, $teacher_id);
}

function addChapter($conn, $program_id, $title, $content = '', $question = '') {
    return chapter_add($conn, $program_id, $title, $content, $question);
}

function getChapters($conn, $program_id) {
    return chapter_getByProgram($conn, $program_id);
}

function getProgramChapters($conn, $program_id) {
    return chapter_getByProgram($conn, $program_id);
}

function getChapter($conn, $chapter_id) {
    return chapter_getById($conn, $chapter_id);
}

function getchapter_stories($conn, $chapter_id) {
    return chapter_getStories($conn, $chapter_id);
}

function getChapterQuiz($conn, $chapter_id) {
    return chapter_getQuiz($conn, $chapter_id);
}

function getStoryInteractiveSections($conn, $story_id) {
    return story_getInteractiveSections($conn, $story_id);
}

function getSectionQuestions($conn, $section_id) {
    return section_getQuestions($conn, $section_id);
}

function verifyProgramOwnership($conn, $program_id, $teacher_id) {
    return program_verifyOwnership($conn, $program_id, $teacher_id);
}

function createProgram($conn, $data) {
    return program_create($conn, $data);
}

function updateProgram($conn, $program_id, $data) {
    return program_update($conn, $program_id, $data);
}

function deleteChapter($conn, $chapter_id) {
    return chapter_delete($conn, $chapter_id);
}

function updateChapter($conn, $chapter_id, $title, $content, $question) {
    return chapter_update($conn, $chapter_id, $title, $content, $question);
}

?>