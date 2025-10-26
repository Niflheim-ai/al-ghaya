<?php
// Helpers for teacher pages - no duplicate function declarations
require_once __DIR__ . '/program-handler.php';

// Safe proxy to avoid naming conflicts
function ph_getTeacherIdFromSession($conn, $user_id) { return getTeacherIdFromSession($conn, $user_id); }

// Utility: list published programs for library section
function getPublishedPrograms($conn) {
    $sql = "SELECT programID, title, description, price, category, thumbnail, status, dateCreated FROM programs WHERE status = 'published' ORDER BY dateCreated DESC LIMIT 50";
    $res = $conn->query($sql);
    if (!$res) { return []; }
    return $res->fetch_all(MYSQLI_ASSOC);
}

// Note: Legacy aliases like getChapter/getStory/getChapterQuiz/getSectionQuestions
// are defined in program-handler.php. Do not redeclare them here to avoid fatal errors.
