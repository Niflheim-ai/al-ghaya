<?php
/**
 * PROGRAM CORE (Complete Unified)
 * All program functions, handlers, and HTTP endpoints in one file
 * With explicit status enforcement and diagnostic logging
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/functions.php';

// Access control
function validateTeacherAccess() { return isset($_SESSION['userID']) && (($_SESSION['role'] ?? '') === 'teacher'); }
function validateAdminAccess() { return isset($_SESSION['userID']) && (($_SESSION['role'] ?? '') === 'admin'); }

// Teacher identity
function getTeacherIdFromSession($conn, $user_id) {
    $stmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ? AND isActive = 1");
    if (!$stmt) { return null; }
    $stmt->bind_param("i", $user_id); $stmt->execute(); $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) { $row = $res->fetch_assoc(); $stmt->close(); return (int)$row['teacherID']; }
    $stmt->close();
    // Auto-create teacher profile if user exists but no teacher record
    $userStmt = $conn->prepare("SELECT email, fname, lname FROM user WHERE userID = ? AND role = 'teacher' AND isActive = 1");
    if (!$userStmt) { return null; }
    $userStmt->bind_param("i", $user_id); $userStmt->execute(); $userResult = $userStmt->get_result();
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc(); $userStmt->close();
        $insertStmt = $conn->prepare("INSERT INTO teacher (userID, email, username, fname, lname, dateCreated, isActive) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
        if ($insertStmt) {
            $username = $user['email'];
            $insertStmt->bind_param("issss", $user_id, $user['email'], $username, $user['fname'], $user['lname']);
            if ($insertStmt->execute()) { $teacher_id = $insertStmt->insert_id; $insertStmt->close(); return $teacher_id; }
            $insertStmt->close();
        }
    } else { $userStmt->close(); }
    return null;
}

// Status normalization
function normalize_status($status) { 
    $status = strtolower(trim($status ?? '')); 
    if ($status === 'ready_for_review') $status = 'pending_review'; 
    $allowed=['draft','pending_review','published','archived']; 
    return in_array($status,$allowed,true)?$status:'draft'; 
}

// Status enforcement - ensures status is NEVER NULL/empty after write
function enforce_program_status($conn, $program_id, $intended_status) {
    error_log("STATUS ENFORCE: programID=$program_id intended='$intended_status'");
    
    // Explicit UPDATE to force the intended status
    $enforceStmt = $conn->prepare("UPDATE programs SET status = ? WHERE programID = ?");
    if ($enforceStmt) {
        $enforceStmt->bind_param("si", $intended_status, $program_id);
        $enforceStmt->execute();
        $enforceStmt->close();
    }
    
    // Readback and verify what's actually stored
    $readStmt = $conn->prepare("SELECT status FROM programs WHERE programID = ?");
    if ($readStmt) {
        $readStmt->bind_param("i", $program_id);
        $readStmt->execute();
        $readResult = $readStmt->get_result();
        $row = $readResult->fetch_assoc();
        $stored_status = $row['status'] ?? 'NULL';
        $readStmt->close();
        
        error_log("STATUS READBACK: programID=$program_id intended='$intended_status' stored='$stored_status'");
        
        if ($stored_status !== $intended_status) {
            error_log("STATUS MISMATCH ALERT: programID=$program_id - external process overwrote status after our handler!");
            // One more attempt to force it
            $forceStmt = $conn->prepare("UPDATE programs SET status = ? WHERE programID = ?");
            if ($forceStmt) {
                $forceStmt->bind_param("si", $intended_status, $program_id);
                $forceStmt->execute();
                $forceStmt->close();
                error_log("STATUS FORCE APPLIED: programID=$program_id forced to '$intended_status'");
            }
        }
    }
}

function mapDifficultyToCategory($difficulty_level) { 
    switch ($difficulty_level) { 
        case 'Student': return 'beginner'; 
        case 'Aspiring': return 'intermediate'; 
        case 'Master': return 'advanced'; 
        default: return 'beginner'; 
    } 
}

function uploadThumbnail($file) {
    $upload_dir = __DIR__ . '/../uploads/thumbnails/'; 
    if (!file_exists($upload_dir)) { if (!mkdir($upload_dir, 0755, true)) { return false; } }
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) { return false; }
    if ($file['size'] > 5 * 1024 * 1024) { return false; }
    $allowed = ['image/jpeg','image/png','image/gif','image/webp']; 
    $finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo); 
    if (!in_array($mime, $allowed)) { return false; }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); 
    $filename = uniqid('thumb_', true) . '.' . $ext; 
    $dest = $upload_dir . $filename; 
    return move_uploaded_file($file['tmp_name'], $dest) ? $filename : false;
}

// ... rest of the code and HTTP handler unchanged ...
