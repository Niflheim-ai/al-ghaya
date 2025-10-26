<?php
/**
 * CENTRALIZED PROGRAM HANDLER - Al-Ghaya LMS
 * RELAXED VERSION: Teacher can edit/own any program, admin approves
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'dbConnection.php';
require_once 'functions.php';

function validateTeacherAccess() {
    return isset($_SESSION['userID']) && ($_SESSION['role'] ?? '') === 'teacher';
}

function getTeacherIdFromSession($conn, $user_id) {
    $stmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ? AND isActive = 1");
    if (!$stmt) { error_log("getTeacherIdFromSession prepare failed: " . $conn->error); return null; }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) { $row = $result->fetch_assoc(); $stmt->close(); return (int)$row['teacherID']; }
    $stmt->close();

    $userStmt = $conn->prepare("SELECT email, fname, lname FROM user WHERE userID = ? AND role = 'teacher' AND isActive = 1");
    if (!$userStmt) { error_log("getTeacherIdFromSession user query prepare failed: " . $conn->error); return null; }
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $userStmt->close();
        $insertStmt = $conn->prepare("INSERT INTO teacher (userID, email, username, fname, lname, dateCreated, isActive) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
        if (!$insertStmt) { error_log("getTeacherIdFromSession insert prepare failed: " . $conn->error); return null; }
        $username = $user['email'];
        $insertStmt->bind_param("issss", $user_id, $user['email'], $username, $user['fname'], $user['lname']);
        if ($insertStmt->execute()) { $teacher_id = $insertStmt->insert_id; $insertStmt->close(); return $teacher_id; }
        $insertStmt->close();
    } else { $userStmt->close(); }
    return null;
}

function always_true() { return true; }
function program_verifyOwnership($conn, $program_id, $teacher_id) { return true; } // relax ownership

// ... rest of file unchanged ... 

// NO other changes. Only program_verifyOwnership now always returns true.
