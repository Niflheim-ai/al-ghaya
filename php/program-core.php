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

// Core program CRUD
function program_create($conn, $data) {
    $sql = "INSERT INTO programs (teacherID, title, description, difficulty_label, category, price, thumbnail, status, overview_video_url, dateCreated, dateUpdated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql); if (!$stmt) { return false; }
    $stmt->bind_param("issssdsss", $data['teacherID'], $data['title'], $data['description'], $data['difficulty_label'], $data['category'], $data['price'], $data['thumbnail'], $data['status'], $data['overview_video_url']);
    if ($stmt->execute()) { $id = $stmt->insert_id; $stmt->close(); return $id; } $stmt->close(); return false;
}

function program_update($conn, $program_id, $data) {
    $sql = "UPDATE programs SET title = ?, description = ?, difficulty_label = ?, category = ?, price = ?, status = ?, overview_video_url = ?, dateUpdated = NOW() WHERE programID = ?";
    $stmt = $conn->prepare($sql); if (!$stmt) { return false; }
    $stmt->bind_param("sssssdsi", $data['title'], $data['description'], $data['difficulty_label'], $data['category'], $data['price'], $data['status'], $data['overview_video_url'], $program_id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function program_getById($conn, $program_id, $teacher_id = null) { 
    $stmt=$conn->prepare("SELECT * FROM programs WHERE programID = ?"); 
    if(!$stmt){return null;} 
    $stmt->bind_param("i",$program_id); $stmt->execute(); $res=$stmt->get_result(); 
    $row=$res?$res->fetch_assoc():null; $stmt->close(); return $row?:null; 
}

function program_getByTeacher($conn, $teacher_id, $sortBy='dateCreated'){ 
    $allowed=['dateCreated','dateUpdated','title','price']; 
    if(!in_array($sortBy,$allowed,true)){$sortBy='dateCreated';} 
    $stmt=$conn->prepare("SELECT * FROM programs WHERE teacherID = ? ORDER BY $sortBy DESC"); 
    if(!$stmt){return [];} 
    $stmt->bind_param("i",$teacher_id); $stmt->execute(); $res=$stmt->get_result(); 
    $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $stmt->close(); return $rows; 
}

// Admin functions
function program_approve($conn, $program_id) {
    $stmt = $conn->prepare("UPDATE programs SET status = 'published', dateUpdated = NOW() WHERE programID = ?");
    if (!$stmt) { return false; }
    $stmt->bind_param("i", $program_id); $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function program_reject($conn, $program_id) {
    $stmt = $conn->prepare("UPDATE programs SET status = 'draft', dateUpdated = NOW() WHERE programID = ?");
    if (!$stmt) { return false; }
    $stmt->bind_param("i", $program_id); $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function program_bulkApprove($conn, $program_ids) {
    if (empty($program_ids)) { return 0; }
    $in = implode(',', array_fill(0, count($program_ids), '?'));
    $types = str_repeat('i', count($program_ids));
    $sql = "UPDATE programs SET status = 'published', dateUpdated = NOW() WHERE programID IN ($in)";
    $stmt = $conn->prepare($sql); if (!$stmt) { return 0; }
    $stmt->bind_param($types, ...array_map('intval', $program_ids)); $stmt->execute();
    $affected = $stmt->affected_rows; $stmt->close(); return $affected;
}

// Chapters
function chapter_add($conn,$program_id,$title,$content='',$question=''){ 
    $stmt=$conn->prepare("SELECT MAX(chapter_order) FROM program_chapters WHERE programID = ?"); 
    if(!$stmt){return false;} 
    $stmt->bind_param("i",$program_id); $stmt->execute(); $max=$stmt->get_result()->fetch_array()[0]; $stmt->close(); 
    $order=$max?$max+1:1; 
    $stmt=$conn->prepare("INSERT INTO program_chapters (programID,title,content,question,chapter_order) VALUES (?,?,?,?,?)"); 
    if(!$stmt){return false;} 
    $stmt->bind_param("isssi",$program_id,$title,$content,$question,$order); 
    $ok=$stmt->execute(); $id=$stmt->insert_id; $stmt->close(); return $ok?$id:false; 
}

function chapter_update($conn,$chapter_id,$title,$content,$question){ 
    $stmt=$conn->prepare("UPDATE program_chapters SET title=?, content=?, question=? WHERE chapter_id=?"); 
    if(!$stmt){return false;} 
    $stmt->bind_param("sssi",$title,$content,$question,$chapter_id); 
    $ok=$stmt->execute(); $stmt->close(); return $ok; 
}

function chapter_delete($conn,$chapter_id){ 
    $conn->begin_transaction(); 
    try{ 
        $stories=chapter_getStories($conn,$chapter_id); 
        foreach($stories as $s){ story_deleteInteractiveSections($conn,$s['story_id']); } 
        $stmt1=$conn->prepare("DELETE FROM chapter_stories WHERE chapter_id=?"); 
        if($stmt1){ $stmt1->bind_param("i",$chapter_id); $stmt1->execute(); $stmt1->close(); } 
        $stmt2=$conn->prepare("DELETE FROM chapter_quizzes WHERE chapter_id=?"); 
        if($stmt2){ $stmt2->bind_param("i",$chapter_id); $stmt2->execute(); $stmt2->close(); } 
        $stmt=$conn->prepare("DELETE FROM program_chapters WHERE chapter_id=?"); 
        if(!$stmt) throw new Exception('prepare fail'); 
        $stmt->bind_param("i",$chapter_id); 
        if(!$stmt->execute()) throw new Exception('exec fail'); 
        $affected=$stmt->affected_rows; $stmt->close(); $conn->commit(); return $affected>0; 
    }catch(Exception $e){ $conn->rollback(); return false; } 
}

function chapter_getByProgram($conn,$program_id){ 
    $stmt=$conn->prepare("SELECT * FROM program_chapters WHERE programID = ? ORDER BY chapter_order"); 
    if(!$stmt){return [];} 
    $stmt->bind_param("i",$program_id); $stmt->execute(); $res=$stmt->get_result(); 
    $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $stmt->close(); return $rows; 
}

function chapter_getById($conn,$chapter_id){ 
    $stmt=$conn->prepare("SELECT * FROM program_chapters WHERE chapter_id = ?"); 
    if(!$stmt){return null;} 
    $stmt->bind_param("i",$chapter_id); $stmt->execute(); $res=$stmt->get_result(); 
    $row=$res?$res->fetch_assoc():null; $stmt->close(); return $row?:null; 
}

function chapter_getStories($conn, $chapter_id) { 
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_stories'"); 
    if ($tableCheck->num_rows == 0) { return []; } 
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE chapter_id = ? ORDER BY story_order ASC"); 
    if (!$stmt) { return []; } 
    $stmt->bind_param("i", $chapter_id); $stmt->execute(); $res = $stmt->get_result(); 
    $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); return $rows; 
}

function chapter_getQuiz($conn, $chapter_id) { 
    $tableCheck = $conn->query("SHOW TABLES LIKE 'chapter_quizzes'"); 
    if ($tableCheck->num_rows == 0) return null; 
    $stmt = $conn->prepare("SELECT * FROM chapter_quizzes WHERE chapter_id = ? LIMIT 1"); 
    if (!$stmt) { return null; } 
    $stmt->bind_param("i", $chapter_id); $stmt->execute(); $res = $stmt->get_result(); 
    $row = $res->fetch_assoc(); $stmt->close(); return $row; 
}

// Stories
function story_create($conn,$data){ 
    $check=$conn->query("SHOW TABLES LIKE 'chapter_stories'"); 
    if(!$check||$check->num_rows==0){ return false; } 
    $orderStmt=$conn->prepare("SELECT COALESCE(MAX(story_order),0)+1 FROM chapter_stories WHERE chapter_id=?"); 
    if(!$orderStmt){ return false; } 
    $orderStmt->bind_param("i",$data['chapter_id']); $orderStmt->execute(); 
    $next=$orderStmt->get_result()->fetch_array()[0]; $orderStmt->close(); 
    $stmt=$conn->prepare("INSERT INTO chapter_stories (chapter_id,title,synopsis_arabic,synopsis_english,video_url,story_order,dateCreated) VALUES (?,?,?,?,?, ?, NOW())"); 
    if(!$stmt){ return false; } 
    $stmt->bind_param("issssi",$data['chapter_id'],$data['title'],$data['synopsis_arabic'],$data['synopsis_english'],$data['video_url'],$next); 
    $ok=$stmt->execute(); $id=$stmt->insert_id; $stmt->close(); return $ok?$id:false; 
}

function story_update($conn,$story_id,$data){ 
    $stmt=$conn->prepare("UPDATE chapter_stories SET title=?, synopsis_arabic=?, synopsis_english=?, video_url=? WHERE story_id=?"); 
    if(!$stmt){ return false; } 
    $stmt->bind_param("ssssi",$data['title'],$data['synopsis_arabic'],$data['synopsis_english'],$data['video_url'],$story_id); 
    $ok=$stmt->execute(); $stmt->close(); return $ok; 
}

function story_delete($conn,$story_id){ 
    story_deleteInteractiveSections($conn,$story_id); 
    $stmt=$conn->prepare("DELETE FROM chapter_stories WHERE story_id=?"); 
    if(!$stmt){ return false; } 
    $stmt->bind_param("i",$story_id); $ok=$stmt->execute(); 
    $aff=$stmt->affected_rows; $stmt->close(); return $ok&&$aff>0; 
}

function story_getById($conn,$story_id){ 
    $stmt=$conn->prepare("SELECT * FROM chapter_stories WHERE story_id=?"); 
    if(!$stmt){ return null; } 
    $stmt->bind_param("i",$story_id); $stmt->execute(); $res=$stmt->get_result(); 
    $row=$res?$res->fetch_assoc():null; $stmt->close(); return $row?:null; 
}

function story_getInteractiveSections($conn,$story_id){ 
    $check=$conn->query("SHOW TABLES LIKE 'story_interactive_sections'"); 
    if(!$check||$check->num_rows==0){ return []; } 
    $stmt=$conn->prepare("SELECT * FROM story_interactive_sections WHERE story_id=? ORDER BY section_order ASC"); 
    if(!$stmt){ return []; } 
    $stmt->bind_param("i",$story_id); $stmt->execute(); $res=$stmt->get_result(); 
    $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $stmt->close(); return $rows; 
}

function story_deleteInteractiveSections($conn,$story_id){ 
    $sections=story_getInteractiveSections($conn,$story_id); 
    foreach($sections as $sec){ 
        $s1=$conn->prepare("DELETE FROM interactive_questions WHERE section_id=?"); 
        if($s1){ $s1->bind_param("i",$sec['section_id']); $s1->execute(); $s1->close(); } 
        $s2=$conn->prepare("DELETE FROM story_interactive_sections WHERE section_id=?"); 
        if($s2){ $s2->bind_param("i",$sec['section_id']); $s2->execute(); $s2->close(); } 
    } 
    return true; 
}

// Section questions stub
function section_getQuestions($conn, $section_id) {
    $check = $conn->query("SHOW TABLES LIKE 'interactive_questions'");
    if (!$check || $check->num_rows == 0) { return []; }
    $stmt = $conn->prepare("SELECT * FROM interactive_questions WHERE section_id = ? ORDER BY question_order ASC");
    if (!$stmt) { return []; }
    $stmt->bind_param("i", $section_id); $stmt->execute(); $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : []; $stmt->close(); return $rows;
}

// Toolbar publish endpoints
function get_draft_programs($conn, $teacher_id) { 
    $stmt = $conn->prepare("SELECT programID, title, price, category FROM programs WHERE teacherID = ? AND (status IS NULL OR status = 'draft') ORDER BY dateUpdated DESC LIMIT 100"); 
    if (!$stmt) { return []; } 
    $stmt->bind_param("i", $teacher_id); $stmt->execute(); $res = $stmt->get_result(); 
    $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); return $rows; 
}

function mark_pending_review($conn, $teacher_id, $program_ids) { 
    if (empty($program_ids)) { return 0; } 
    $in = implode(',', array_fill(0, count($program_ids), '?')); 
    $types = str_repeat('i', count($program_ids) + 1); 
    $sql = "UPDATE programs SET status = 'pending_review', dateUpdated = NOW() WHERE teacherID = ? AND programID IN ($in)"; 
    $stmt = $conn->prepare($sql); if (!$stmt) { return 0; } 
    $params = array_merge([$teacher_id], array_map('intval', $program_ids)); 
    $stmt->bind_param($types, ...$params); $stmt->execute(); $affected = $stmt->affected_rows; $stmt->close(); return $affected; 
}

// Published library
function getPublishedPrograms($conn){ 
    $sql="SELECT programID,title,description,price,category,thumbnail,status,dateCreated,teacherID FROM programs WHERE status='published' ORDER BY dateCreated DESC LIMIT 100"; 
    $res=$conn->query($sql); if(!$res){ return []; } return $res->fetch_all(MYSQLI_ASSOC); 
}

// Legacy wrappers kept for compatibility
function getTeacherPrograms($conn,$teacher_id,$sortBy='dateCreated'){ return program_getByTeacher($conn,$teacher_id,$sortBy); }
function getProgram($conn,$program_id,$teacher_id=null){ return program_getById($conn,$program_id,$teacher_id); }
function addChapter($conn,$program_id,$title,$content='',$question=''){ return chapter_add($conn,$program_id,$title,$content,$question); }
function getChapters($conn,$program_id){ return chapter_getByProgram($conn,$program_id); }
function getProgramChapters($conn,$program_id){ return chapter_getByProgram($conn,$program_id); }
function getChapter($conn,$chapter_id){ return chapter_getById($conn,$chapter_id); }
function getchapter_stories($conn,$chapter_id){ return chapter_getStories($conn,$chapter_id); }
function getChapterQuiz($conn,$chapter_id){ return chapter_getQuiz($conn,$chapter_id); }
function getStoryInteractiveSections($conn,$story_id){ return story_getInteractiveSections($conn,$story_id); }
function getSectionQuestions($conn,$section_id){ return section_getQuestions($conn,$section_id); }
function verifyProgramOwnership($conn,$program_id,$teacher_id){ return true; }
function createProgram($conn,$data){ return program_create($conn,$data); }
function updateProgram($conn,$program_id,$data){ return program_update($conn,$program_id,$data); }
function deleteChapter($conn,$chapter_id){ return chapter_delete($conn,$chapter_id); }
function updateChapter($conn,$chapter_id,$title,$content,$question){ return chapter_update($conn,$chapter_id,$title,$content,$question); }
function getStory($conn,$story_id){ return story_getById($conn,$story_id); }

// HTTP Handler for all POST/GET endpoints - only run when directly accessed as program-core.php
if (basename($_SERVER['PHP_SELF']) === 'program-core.php') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Admin-only actions
    if (in_array($action, ['approve_program', 'reject_program', 'bulk_approve_programs'])) {
        if (!validateAdminAccess()) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Admin access required']);
            exit;
        }
    } 
    // Teacher-only actions
    else if (in_array($action, ['create_program','update_program','create_story','update_story','delete_program','delete_chapter','delete_story','archive_program','get_draft_programs','submit_for_publishing'])) {
        if (!validateTeacherAccess()) {
            if (in_array($action, ['create_program','update_program','create_story','update_story','delete_program','archive_program'])) { 
                $_SESSION['error_message'] = 'Unauthorized access'; 
                header('Location: ../pages/teacher/teacher-programs.php'); exit; 
            }
            http_response_code(403); echo json_encode(['success'=>false,'message'=>'Teacher access required']); exit;
        }
        
        $user_id = $_SESSION['userID']; 
        $teacher_id = getTeacherIdFromSession($conn, $user_id); 
        if (!$teacher_id) { 
            if (in_array($action, ['create_program','update_program','create_story','update_story','delete_program','archive_program'])) { 
                $_SESSION['error_message'] = 'Teacher profile not found'; 
                header('Location: ../pages/teacher/teacher-programs.php'); exit; 
            }
            http_response_code(403); echo json_encode(['success'=>false,'message'=>'Teacher profile not found']); exit; 
        }
    }
    
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) { 
        $input = json_decode(file_get_contents('php://input'), true); 
        if ($input) { $action = $input['action'] ?? $action; $_POST = array_merge($_POST, $input); } 
    }

    try {
        switch ($action) {
            // Admin actions
            case 'approve_program':
                header('Content-Type: application/json');
                $program_id = intval($_POST['programID'] ?? 0);
                if (!$program_id) { echo json_encode(['success'=>false,'message'=>'Program ID required']); exit; }
                echo json_encode(program_approve($conn, $program_id) ? ['success'=>true,'message'=>'Program approved and published'] : ['success'=>false,'message'=>'Failed to approve program']); exit;
            
            case 'reject_program':
                header('Content-Type: application/json');
                $program_id = intval($_POST['programID'] ?? 0);
                if (!$program_id) { echo json_encode(['success'=>false,'message'=>'Program ID required']); exit; }
                echo json_encode(program_reject($conn, $program_id) ? ['success'=>true,'message'=>'Program rejected and sent back to draft'] : ['success'=>false,'message'=>'Failed to reject program']); exit;
            
            case 'bulk_approve_programs':
                header('Content-Type: application/json');
                $program_ids = json_decode($_POST['program_ids'] ?? '[]', true);
                $count = program_bulkApprove($conn, $program_ids);
                echo json_encode(['success'=> $count > 0, 'approved'=>$count, 'message'=>"$count programs approved"]); exit;
                
            // Teacher actions with status enforcement
            case 'create_program':
                $incoming_status = $_POST['status'] ?? 'MISSING';
                $status = normalize_status($incoming_status);
                if (!$status || empty(trim($status))) { $status = 'draft'; }
                error_log("CREATE PROGRAM: incoming='$incoming_status' normalized='$status'");
                
                $data = [ 'teacherID'=>$teacher_id, 'title'=>trim($_POST['title'] ?? ''), 'description'=>trim($_POST['description'] ?? ''), 'difficulty_label'=>$_POST['difficulty_level'] ?? 'Student', 'category'=>mapDifficultyToCategory($_POST['difficulty_level'] ?? 'Student'), 'price'=>floatval($_POST['price'] ?? 0), 'status'=>$status, 'thumbnail'=>'default-thumbnail.jpg', 'overview_video_url'=>trim($_POST['overview_video_url'] ?? '') ];
                
                if (empty($data['title']) || strlen($data['title']) < 3) { $_SESSION['error_message'] = 'Program title must be at least 3 characters long'; header('Location: ../pages/teacher/teacher-programs.php?action=create'); exit; }
                if (empty($data['description']) || strlen($data['description']) < 10) { $_SESSION['error_message'] = 'Program description must be at least 10 characters long'; header('Location: ../pages/teacher/teacher-programs.php?action=create'); exit; }
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) { $t = uploadThumbnail($_FILES['thumbnail']); if ($t) $data['thumbnail'] = $t; }
                
                $program_id = program_create($conn, $data);
                if ($program_id) { 
                    enforce_program_status($conn, $program_id, $status);
                    chapter_add($conn, $program_id, 'Introduction', 'Welcome to this program!', ''); 
                    $_SESSION['success_message']='Program created successfully!'; 
                    header('Location: ../pages/teacher/teacher-programs.php?action=create&program_id=' . $program_id); exit; 
                }
                $_SESSION['error_message']='Failed to create program. Please try again.'; header('Location: ../pages/teacher/teacher-programs.php?action=create'); exit;
                
            case 'update_program':
                $program_id = intval($_POST['programID'] ?? 0);
                if (!$program_id) { $_SESSION['error_message']='Program ID is required.'; header('Location: ../pages/teacher/teacher-programs.php'); exit; }
                $incoming_status = $_POST['status'] ?? 'MISSING';
                $status = normalize_status($incoming_status);
                if (!$status || empty(trim($status))) { $status = 'draft'; }
                error_log("UPDATE PROGRAM: programID=$program_id incoming='$incoming_status' normalized='$status'");
                
                $data = [ 'title'=>trim($_POST['title'] ?? ''), 'description'=>trim($_POST['description'] ?? ''), 'difficulty_label'=>$_POST['difficulty_level'] ?? 'Student', 'category'=>mapDifficultyToCategory($_POST['difficulty_level'] ?? 'Student'), 'price'=>floatval($_POST['price'] ?? 0), 'status'=>$status, 'overview_video_url'=>trim($_POST['overview_video_url'] ?? '') ];
                
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) { $t = uploadThumbnail($_FILES['thumbnail']); if ($t) { $thumbStmt = $conn->prepare("UPDATE programs SET thumbnail = ?, dateUpdated = NOW() WHERE programID = ?"); if ($thumbStmt) { $thumbStmt->bind_param("si", $t, $program_id); $thumbStmt->execute(); $thumbStmt->close(); } } }
                
                if (program_update($conn, $program_id, $data)) { 
                    enforce_program_status($conn, $program_id, $status);
                    $_SESSION['success_message']='Program updated successfully!'; 
                } else { $_SESSION['error_message']='No changes made or error updating program.'; }
                header('Location: ../pages/teacher/teacher-programs.php?action=create&program_id=' . $program_id); exit;

            case 'archive_program':
                $program_id = intval($_POST['programID'] ?? 0);
                if (!$program_id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Program ID required']); exit; }
                $stmt = $conn->prepare("UPDATE programs SET status='archived', dateUpdated=NOW() WHERE programID=? AND teacherID=?");
                if ($stmt) { $stmt->bind_param("ii", $program_id, $teacher_id); $stmt->execute(); $stmt->close(); }
                echo json_encode(['success'=>true]); exit;

            case 'delete_program':
                $program_id = intval($_POST['programID'] ?? 0);
                if (!$program_id) { http_response_code(400); echo 'Program ID required'; exit; }
                $chapters = chapter_getByProgram($conn, $program_id);
                foreach ($chapters as $c) { chapter_delete($conn, (int)$c['chapter_id']); }
                $del = $conn->prepare("DELETE FROM programs WHERE programID = ? AND teacherID = ?");
                if ($del) { $del->bind_param("ii", $program_id, $teacher_id); $del->execute(); $del->close(); }
                echo 'OK'; exit;
                
            case 'create_story':
                $program_id = intval($_POST['programID'] ?? 0); $chapter_id = intval($_POST['chapter_id'] ?? 0);
                $title = trim($_POST['title'] ?? ''); $synopsis_arabic = trim($_POST['synopsis_arabic'] ?? ''); 
                $synopsis_english = trim($_POST['synopsis_english'] ?? ''); $video_url = trim($_POST['video_url'] ?? '');
                if (empty($title) || empty($synopsis_arabic) || empty($synopsis_english) || empty($video_url)) { 
                    $_SESSION['error_message']='All fields are required for the story.'; 
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit; 
                }
                if (!$program_id) { $_SESSION['error_message']='Program ID is required.'; header('Location: ../pages/teacher/teacher-programs.php'); exit; }
                $existingStories = chapter_getStories($conn, $chapter_id); 
                if (count($existingStories) >= 3) { 
                    $_SESSION['error_message']='Maximum of 3 stories per chapter allowed.'; 
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit; 
                }
                $story_id = story_create($conn, ['chapter_id'=>$chapter_id,'title'=>$title,'synopsis_arabic'=>$synopsis_arabic,'synopsis_english'=>$synopsis_english,'video_url'=>$video_url]);
                if ($story_id) { $_SESSION['success_message']='Story created successfully!'; header('Location: ../pages/teacher/teacher-programs.php?action=edit_chapter&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit; }
                $_SESSION['error_message']='Failed to save story. Please try again.'; header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit;
                
            case 'update_story':
                $program_id = intval($_POST['programID'] ?? 0); $chapter_id = intval($_POST['chapter_id'] ?? 0); $story_id = intval($_POST['story_id'] ?? 0);
                $title = trim($_POST['title'] ?? ''); $synopsis_arabic = trim($_POST['synopsis_arabic'] ?? ''); $synopsis_english = trim($_POST['synopsis_english'] ?? ''); $video_url = trim($_POST['video_url'] ?? '');
                if (!$story_id || empty($title) || empty($synopsis_arabic) || empty($synopsis_english) || empty($video_url)) { $_SESSION['error_message']='All fields are required for the story update.'; header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id); exit; }
                $data = ['title'=>$title,'synopsis_arabic'=>$synopsis_arabic,'synopsis_english'=>$synopsis_english,'video_url'=>$video_url];
                if (story_update($conn, $story_id, $data)) { $_SESSION['success_message']='Story updated successfully!'; header('Location: ../pages/teacher/teacher-programs.php?action=edit_chapter&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit; }
                $_SESSION['error_message']='Failed to update story. Please try again.'; header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id); exit;

            case 'delete_story':
                header('Content-Type: application/json'); 
                $story_id = intval($_POST['story_id'] ?? 0);
                if (!$story_id) { echo json_encode(['success'=>false,'message'=>'Story ID is required']); exit; }
                echo json_encode(story_delete($conn, $story_id) ? ['success'=>true,'message'=>'Story deleted successfully'] : ['success'=>false,'message'=>'Failed to delete story']); exit;

            case 'create_chapter':
                header('Content-Type: application/json'); 
                $program_id = intval($_POST['programID'] ?? 0); $title = trim($_POST['title'] ?? 'New Chapter');
                if (!$program_id) { echo json_encode(['success'=>false,'message'=>'Program ID is required']); exit; }
                $chapter_id = chapter_add($conn, $program_id, $title); 
                echo json_encode($chapter_id ? ['success'=>true,'chapter_id'=>$chapter_id,'programID'=>$program_id,'message'=>'Chapter created successfully'] : ['success'=>false,'message'=>'Failed to create chapter']); exit;
                
            case 'update_chapter':
                $chapter_id = intval($_POST['chapter_id'] ?? 0); $title = trim($_POST['title'] ?? ''); $content = trim($_POST['content'] ?? ''); $question = trim($_POST['question'] ?? '');
                if (!$chapter_id) { $_SESSION['error_message']='Chapter ID is required.'; header('Location: ../pages/teacher/teacher-programs.php'); exit; }
                if (chapter_update($conn, $chapter_id, $title, $content, $question)) { $_SESSION['success_message']='Chapter updated successfully!'; } else { $_SESSION['error_message']='Failed to update chapter.'; }
                $chapter = chapter_getById($conn, $chapter_id); $program_id = $chapter ? $chapter['programID'] : 0;
                header('Location: ../pages/teacher/teacher-programs.php?action=edit_chapter&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit;
                
            case 'delete_chapter':
                header('Content-Type: application/json'); 
                $chapter_id = intval($_POST['chapter_id'] ?? 0);
                if (!$chapter_id) { echo json_encode(['success'=>false,'message'=>'Chapter ID is required']); exit; }
                echo json_encode(chapter_delete($conn, $chapter_id) ? ['success'=>true,'message'=>'Chapter deleted successfully'] : ['success'=>false,'message'=>'Failed to delete chapter']); exit;

            case 'get_draft_programs':
                header('Content-Type: application/json');
                $rows = get_draft_programs($conn, $teacher_id);
                echo json_encode(['success'=>true,'programs'=>$rows]); exit;

            case 'submit_for_publishing':
                header('Content-Type: application/json');
                $ids = array_map('intval', $_POST['program_ids'] ?? []);
                $count = mark_pending_review($conn, $teacher_id, $ids);
                echo json_encode(['success'=> $count > 0, 'updated'=>$count]); exit;

            default:
                if (in_array($action, ['create_program','update_program','create_story','update_story','delete_program','delete_chapter','delete_story','archive_program','approve_program','reject_program','bulk_approve_programs'])) { 
                    $_SESSION['error_message']='Invalid action: ' . $action; 
                    header('Location: ../pages/teacher/teacher-programs.php'); exit; 
                }
                header('Content-Type: application/json'); 
                echo json_encode(['success'=>false,'message'=>'Invalid action: ' . $action]); exit;
        }
    } catch (Exception $e) {
        error_log("Program Core Handler Error: " . $e->getMessage());
        if (in_array($action, ['create_program','update_program','create_story','update_story','delete_program'])) { 
            $_SESSION['error_message']='Server error: ' . $e->getMessage(); 
            header('Location: ../pages/teacher/teacher-programs.php'); exit; 
        }
        header('Content-Type: application/json'); http_response_code(500); 
        echo json_encode(['success'=>false,'message'=>'Server error: ' . $e->getMessage()]); exit;
    }
}