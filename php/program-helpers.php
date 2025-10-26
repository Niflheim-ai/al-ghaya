<?php
// Helpers for teacher pages - no duplicate function declarations
require_once __DIR__ . '/program-handler.php';

// Safe proxy to avoid naming conflicts
function ph_getTeacherIdFromSession($conn, $user_id) { 
    return getTeacherIdFromSession($conn, $user_id); 
}

// Get programs created by a specific teacher
function getTeacherPrograms($conn, $teacher_id) {
    return program_getByTeacher($conn, $teacher_id);
}

// Utility: list published programs for library section
function getPublishedPrograms($conn) {
    $sql = "SELECT programID, title, description, price, category, thumbnail, status, dateCreated, teacherID FROM programs WHERE status = 'published' ORDER BY dateCreated DESC LIMIT 100";
    $res = $conn->query($sql);
    if (!$res) { return []; }
    return $res->fetch_all(MYSQLI_ASSOC);
}

// Enhanced program retrieval functions
function getProgram($conn, $program_id, $teacher_id = null) {
    return program_getById($conn, $program_id, $teacher_id);
}

function getChapter($conn, $chapter_id) {
    return chapter_getById($conn, $chapter_id);
}

function getStory($conn, $story_id) {
    return story_getById($conn, $story_id);
}

function getChapterQuiz($conn, $chapter_id) {
    return chapter_getQuiz($conn, $chapter_id);
}

// Ownership checks will now allow any user to edit any program; admin reviews for publish
// All access/modify actions have no program-ownership filter in teacher flows
