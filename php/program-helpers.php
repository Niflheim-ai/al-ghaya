<?php
// Helper functions specific to program listing and teacher helper
require_once __DIR__ . '/program-handler.php';

function ph_getTeacherIdFromSession($conn, $user_id) {
    return getTeacherIdFromSession($conn, $user_id);
}

function getPublishedPrograms($conn) {
    $sql = "SELECT programID, title, description, price, category, thumbnail, status, dateCreated FROM programs WHERE status = 'published' ORDER BY dateCreated DESC LIMIT 50";
    $res = $conn->query($sql);
    if (!$res) { return []; }
    return $res->fetch_all(MYSQLI_ASSOC);
}
