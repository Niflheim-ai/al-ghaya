<?php
// Helpers for teacher pages - no duplicate function declarations
require_once __DIR__ . '/program-handler.php';

// Safe proxy to avoid naming conflicts
function ph_getTeacherIdFromSession($conn, $user_id) { 
    return getTeacherIdFromSession($conn, $user_id); 
}

// Utility: list published programs for library section
function getPublishedPrograms($conn) {
    $sql = "SELECT programID, title, description, price, category, thumbnail, status, dateCreated, teacherID FROM programs ORDER BY dateCreated DESC LIMIT 100";
    $res = $conn->query($sql);
    if (!$res) { return []; }
    return $res->fetch_all(MYSQLI_ASSOC);
}
// Ownership checks will now allow any user to edit any program; admin reviews for publish
// All access/modify actions have no program-ownership filter in teacher flows
