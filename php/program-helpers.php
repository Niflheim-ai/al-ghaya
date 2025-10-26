<?php
// Helpers for teacher pages - no duplicate function declarations
// Uses ph_ prefix to avoid conflicts with program-handler.php
require_once __DIR__ . '/program-handler.php';

// Safe proxy functions with ph_ prefix to avoid naming conflicts
function ph_getTeacherIdFromSession($conn, $user_id) { 
    return getTeacherIdFromSession($conn, $user_id); 
}

// Get programs created by a specific teacher
function ph_getTeacherPrograms($conn, $teacher_id) {
    return program_getByTeacher($conn, $teacher_id);
}

// Enhanced program retrieval functions with ph_ prefix
function ph_getProgram($conn, $program_id, $teacher_id = null) {
    return program_getById($conn, $program_id, $teacher_id);
}

function ph_getChapter($conn, $chapter_id) {
    return chapter_getById($conn, $chapter_id);
}

function ph_getStory($conn, $story_id) {
    return story_getById($conn, $story_id);
}

function ph_getChapterQuiz($conn, $chapter_id) {
    return chapter_getQuiz($conn, $chapter_id);
}

function ph_getChapters($conn, $program_id) {
    return chapter_getByProgram($conn, $program_id);
}

function ph_getChapterStories($conn, $chapter_id) {
    return chapter_getStories($conn, $chapter_id);
}

// Utility: list published programs for library section
function getPublishedPrograms($conn) {
    $sql = "SELECT programID, title, description, price, category, thumbnail, status, dateCreated, teacherID FROM programs WHERE status = 'published' ORDER BY dateCreated DESC LIMIT 100";
    $res = $conn->query($sql);
    if (!$res) { return []; }
    return $res->fetch_all(MYSQLI_ASSOC);
}

// Legacy compatibility - these just proxy to ph_ functions to maintain backward compatibility
// but pages should be updated to use ph_ prefix functions directly
function getTeacherPrograms($conn, $teacher_id) { return ph_getTeacherPrograms($conn, $teacher_id); }
function getProgram($conn, $program_id, $teacher_id = null) { return ph_getProgram($conn, $program_id, $teacher_id); }
function getChapter($conn, $chapter_id) { return ph_getChapter($conn, $chapter_id); }
function getStory($conn, $story_id) { return ph_getStory($conn, $story_id); }
function getChapterQuiz($conn, $chapter_id) { return ph_getChapterQuiz($conn, $chapter_id); }
function getChapters($conn, $program_id) { return ph_getChapters($conn, $program_id); }
function getChapterStories($conn, $chapter_id) { return ph_getChapterStories($conn, $chapter_id); }

// Ownership checks will now allow any user to edit any program; admin reviews for publish
// All access/modify actions have no program-ownership filter in teacher flows
