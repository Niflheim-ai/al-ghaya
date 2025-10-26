<?php
// Helpers for teacher pages - ph_ proxies and legacy wrappers
// Ensure canonical functions are available
require_once __DIR__ . '/program-handler.php';

// Guarded fallback if program-handler wasn't loaded for some reason
if (!function_exists('getTeacherIdFromSession')) {
    function getTeacherIdFromSession($conn, $user_id) {
        $stmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ? AND isActive = 1");
        if (!$stmt) { return null; }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? (int)$row['teacherID'] : null;
    }
}

function ph_getTeacherIdFromSession($conn, $user_id) { 
    return getTeacherIdFromSession($conn, $user_id); 
}

// Guarded ph_ proxies with fallbacks for resilience
function ph_getProgram($conn, $program_id, $teacher_id = null) {
    if (function_exists('program_getById')) {
        return program_getById($conn, $program_id, $teacher_id);
    }
    $stmt = $conn->prepare("SELECT * FROM programs WHERE programID = ?");
    if (!$stmt) { return null; }
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function ph_getTeacherPrograms($conn, $teacher_id, $sortBy = 'dateCreated') {
    if (function_exists('program_getByTeacher')) {
        return program_getByTeacher($conn, $teacher_id, $sortBy);
    }
    $allowed = ['dateCreated','dateUpdated','title','price'];
    if (!in_array($sortBy, $allowed, true)) { $sortBy = 'dateCreated'; }
    $sql = "SELECT * FROM programs WHERE teacherID = ? ORDER BY $sortBy DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { return []; }
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function ph_getChapter($conn, $chapter_id) { return chapter_getById($conn, $chapter_id); }
function ph_getStory($conn, $story_id) { return story_getById($conn, $story_id); }
function ph_getChapterQuiz($conn, $chapter_id) { return chapter_getQuiz($conn, $chapter_id); }

function ph_getChapters($conn, $program_id) {
    if (function_exists('chapter_getByProgram')) {
        return chapter_getByProgram($conn, $program_id);
    }
    // Fallback: detect foreign key column name and query
    $col = 'program_id';
    $check = $conn->query("SHOW COLUMNS FROM program_chapters LIKE 'program_id'");
    if (!$check || $check->num_rows === 0) { $col = 'programID'; }
    $sql = "SELECT * FROM program_chapters WHERE $col = ? ORDER BY chapter_order";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { return []; }
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function ph_getChapterStories($conn, $chapter_id) { return chapter_getStories($conn, $chapter_id); }
function ph_getStoryInteractiveSections($conn, $story_id) { return story_getInteractiveSections($conn, $story_id); }

// Utility for published programs
function getPublishedPrograms($conn) {
    $sql = "SELECT programID, title, description, price, category, thumbnail, status, dateCreated, teacherID FROM programs WHERE status = 'published' ORDER BY dateCreated DESC LIMIT 100";
    $res = $conn->query($sql);
    if (!$res) { return []; }
    return $res->fetch_all(MYSQLI_ASSOC);
}

// Legacy compatibility wrappers
function getTeacherPrograms($conn, $teacher_id, $sortBy = 'dateCreated') { return ph_getTeacherPrograms($conn, $teacher_id, $sortBy); }
function getProgram($conn, $program_id, $teacher_id = null) { return ph_getProgram($conn, $program_id, $teacher_id); }
function getChapter($conn, $chapter_id) { return ph_getChapter($conn, $chapter_id); }
function getStory($conn, $story_id) { return ph_getStory($conn, $story_id); }
function getChapterQuiz($conn, $chapter_id) { return ph_getChapterQuiz($conn, $chapter_id); }
function getChapters($conn, $program_id) { return ph_getChapters($conn, $program_id); }
function getChapterStories($conn, $chapter_id) { return ph_getChapterStories($conn, $chapter_id); }
function getProgramChapters($conn, $program_id) { return ph_getChapters($conn, $program_id); }
function getStoryInteractiveSections($conn, $story_id) { return ph_getStoryInteractiveSections($conn, $story_id); }
