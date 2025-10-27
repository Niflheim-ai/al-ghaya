<?php
/**
 * CENTRALIZED PROGRAM HANDLER - Al-Ghaya LMS
 * RELAXED VERSION + STATUS WORKFLOW NORMALIZATION + PUBLISH FLOW
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'dbConnection.php';
require_once 'functions.php';

function validateTeacherAccess() { return isset($_SESSION['userID']) && (($_SESSION['role'] ?? '') === 'teacher'); }

function getTeacherIdFromSession($conn, $user_id) {
    $stmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ? AND isActive = 1");
    if (!$stmt) { error_log("getTeacherIdFromSession prepare failed: " . $conn->error); return null; }
    $stmt->bind_param("i", $user_id); $stmt->execute(); $result = $stmt->get_result();
    if ($result->num_rows > 0) { $row = $result->fetch_assoc(); $stmt->close(); return (int)$row['teacherID']; }
    $stmt->close();
    return null;
}

function normalize_status($status) { $status = strtolower(trim($status ?? '')); if ($status === 'ready_for_review') { $status = 'pending_review'; } $allowed = ['draft','pending_review','published','archived']; return in_array($status, $allowed, true) ? $status : 'draft'; }

function program_create($conn, $data) { $sql = "INSERT INTO programs (teacherID, title, description, difficulty_label, category, price, thumbnail, status, overview_video_url, dateCreated, dateUpdated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"; $stmt = $conn->prepare($sql); if (!$stmt) { return false; } $stmt->bind_param("issssdsss", $data['teacherID'], $data['title'], $data['description'], $data['difficulty_label'], $data['category'], $data['price'], $data['thumbnail'], $data['status'], $data['overview_video_url']); if ($stmt->execute()) { $id = $stmt->insert_id; $stmt->close(); return $id; } $stmt->close(); return false; }
function program_update($conn, $program_id, $data) { $sql = "UPDATE programs SET title = ?, description = ?, difficulty_label = ?, category = ?, price = ?, status = ?, overview_video_url = ?, dateUpdated = NOW() WHERE programID = ?"; $stmt = $conn->prepare($sql); if (!$stmt) { return false; } $stmt->bind_param("sssssdsi", $data['title'], $data['description'], $data['difficulty_label'], $data['category'], $data['price'], $data['status'], $data['overview_video_url'], $program_id); $ok = $stmt->execute(); $stmt->close(); return $ok; }

function get_draft_programs($conn, $teacher_id) { $stmt = $conn->prepare("SELECT programID, title, price, category FROM programs WHERE teacherID = ? AND (status IS NULL OR status = 'draft') ORDER BY dateUpdated DESC LIMIT 100"); if (!$stmt) { return []; } $stmt->bind_param("i", $teacher_id); $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); return $rows; }
function mark_pending_review($conn, $teacher_id, $program_ids) { if (empty($program_ids)) { return 0; } $in = implode(',', array_fill(0, count($program_ids), '?')); $types = str_repeat('i', count($program_ids) + 1); $sql = "UPDATE programs SET status = 'pending_review', dateUpdated = NOW() WHERE teacherID = ? AND programID IN ($in)"; $stmt = $conn->prepare($sql); if (!$stmt) { return 0; } $params = array_merge([$teacher_id], array_map('intval', $program_ids)); $stmt->bind_param($types, ...$params); $stmt->execute(); $affected = $stmt->affected_rows; $stmt->close(); return $affected; }

// Existing handler switch below ...
if (basename($_SERVER['PHP_SELF']) === 'program-handler.php') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) { $input = json_decode(file_get_contents('php://input'), true); if ($input) { $action = $input['action'] ?? $action; $_POST = array_merge($_POST, $input); } }

    if ($action === 'get_draft_programs' || $action === 'submit_for_publishing') {
        if (!validateTeacherAccess()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
        $teacher_id = getTeacherIdFromSession($conn, $_SESSION['userID']); if (!$teacher_id) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'No teacher profile']); exit; }
        header('Content-Type: application/json');
        if ($action === 'get_draft_programs') {
            $rows = get_draft_programs($conn, $teacher_id);
            echo json_encode(['success'=>true,'programs'=>$rows]); exit;
        }
        if ($action === 'submit_for_publishing') {
            $ids = array_map('intval', $_POST['program_ids'] ?? []);
            $count = mark_pending_review($conn, $teacher_id, $ids);
            echo json_encode(['success'=> $count > 0, 'updated'=>$count]); exit;
        }
    }
}
