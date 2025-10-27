<?php
/**
 * PROGRAM CORE + HELPERS (Unified)
 * Combines program-handler.php and program-helpers.php to reduce requires
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/functions.php';

// Access control
function validateTeacherAccess() { return isset($_SESSION['userID']) && (($_SESSION['role'] ?? '') === 'teacher'); }

// Teacher identity
function getTeacherIdFromSession($conn, $user_id) {
    $stmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ? AND isActive = 1");
    if (!$stmt) { return null; }
    $stmt->bind_param("i", $user_id); $stmt->execute(); $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) { $row = $res->fetch_assoc(); $stmt->close(); return (int)$row['teacherID']; }
    $stmt->close(); return null;
}

// Status normalization
function normalize_status($status) { $status = strtolower(trim($status ?? '')); if ($status === 'ready_for_review') $status = 'pending_review'; $allowed=['draft','pending_review','published','archived']; return in_array($status,$allowed,true)?$status:'draft'; }

function mapDifficultyToCategory($difficulty_level) { switch ($difficulty_level) { case 'Student': return 'beginner'; case 'Aspiring': return 'intermediate'; case 'Master': return 'advanced'; default: return 'beginner'; } }

function uploadThumbnail($file) {
    $upload_dir = __DIR__ . '/../uploads/thumbnails/'; if (!file_exists($upload_dir)) { if (!mkdir($upload_dir, 0755, true)) { return false; } }
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) { return false; }
    if ($file['size'] > 5 * 1024 * 1024) { return false; }
    $allowed = ['image/jpeg','image/png','image/gif','image/webp']; $finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo); if (!in_array($mime, $allowed)) { return false; }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); $filename = uniqid('thumb_', true) . '.' . $ext; $dest = $upload_dir . $filename; return move_uploaded_file($file['tmp_name'], $dest) ? $filename : false;
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
function program_getById($conn, $program_id, $teacher_id = null) { $stmt=$conn->prepare("SELECT * FROM programs WHERE programID = ?"); if(!$stmt){return null;} $stmt->bind_param("i",$program_id); $stmt->execute(); $res=$stmt->get_result(); $row=$res?$res->fetch_assoc():null; $stmt->close(); return $row?:null; }
function program_getByTeacher($conn, $teacher_id, $sortBy='dateCreated'){ $allowed=['dateCreated','dateUpdated','title','price']; if(!in_array($sortBy,$allowed,true)){$sortBy='dateCreated';} $stmt=$conn->prepare("SELECT * FROM programs WHERE teacherID = ? ORDER BY $sortBy DESC"); if(!$stmt){return [];} $stmt->bind_param("i",$teacher_id); $stmt->execute(); $res=$stmt->get_result(); $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $stmt->close(); return $rows; }

// Chapters
function chapter_add($conn,$program_id,$title,$content='',$question=''){ $stmt=$conn->prepare("SELECT MAX(chapter_order) FROM program_chapters WHERE programID = ?"); if(!$stmt){return false;} $stmt->bind_param("i",$program_id); $stmt->execute(); $max=$stmt->get_result()->fetch_array()[0]; $stmt->close(); $order=$max?$max+1:1; $stmt=$conn->prepare("INSERT INTO program_chapters (programID,title,content,question,chapter_order) VALUES (?,?,?,?,?)"); if(!$stmt){return false;} $stmt->bind_param("isssi",$program_id,$title,$content,$question,$order); $ok=$stmt->execute(); $id=$stmt->insert_id; $stmt->close(); return $ok?$id:false; }
function chapter_update($conn,$chapter_id,$title,$content,$question){ $stmt=$conn->prepare("UPDATE program_chapters SET title=?, content=?, question=? WHERE chapter_id=?"); if(!$stmt){return false;} $stmt->bind_param("sssi",$title,$content,$question,$chapter_id); $ok=$stmt->execute(); $stmt->close(); return $ok; }
function chapter_delete($conn,$chapter_id){ $conn->begin_transaction(); try{ $stories=chapter_getStories($conn,$chapter_id); foreach($stories as $s){ story_deleteInteractiveSections($conn,$s['story_id']); } $stmt1=$conn->prepare("DELETE FROM chapter_stories WHERE chapter_id=?"); if($stmt1){ $stmt1->bind_param("i",$chapter_id); $stmt1->execute(); $stmt1->close(); } $stmt2=$conn->prepare("DELETE FROM chapter_quizzes WHERE chapter_id=?"); if($stmt2){ $stmt2->bind_param("i",$chapter_id); $stmt2->execute(); $stmt2->close(); } $stmt=$conn->prepare("DELETE FROM program_chapters WHERE chapter_id=?"); if(!$stmt) throw new Exception('prepare fail'); $stmt->bind_param("i",$chapter_id); if(!$stmt->execute()) throw new Exception('exec fail'); $affected=$stmt->affected_rows; $stmt->close(); $conn->commit(); return $affected>0; }catch(Exception $e){ $conn->rollback(); return false; } }
function chapter_getByProgram($conn,$program_id){ $stmt=$conn->prepare("SELECT * FROM program_chapters WHERE programID = ? ORDER BY chapter_order"); if(!$stmt){return [];} $stmt->bind_param("i",$program_id); $stmt->execute(); $res=$stmt->get_result(); $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $stmt->close(); return $rows; }
function chapter_getById($conn,$chapter_id){ $stmt=$conn->prepare("SELECT * FROM program_chapters WHERE chapter_id = ?"); if(!$stmt){return null;} $stmt->bind_param("i",$chapter_id); $stmt->execute(); $res=$stmt->get_result(); $row=$res?$res->fetch_assoc():null; $stmt->close(); return $row?:null; }

// Stories
function story_create($conn,$data){ $check=$conn->query("SHOW TABLES LIKE 'chapter_stories'"); if(!$check||$check->num_rows==0){ return false; } $orderStmt=$conn->prepare("SELECT COALESCE(MAX(story_order),0)+1 FROM chapter_stories WHERE chapter_id=?"); if(!$orderStmt){ return false; } $orderStmt->bind_param("i",$data['chapter_id']); $orderStmt->execute(); $next=$orderStmt->get_result()->fetch_array()[0]; $orderStmt->close(); $stmt=$conn->prepare("INSERT INTO chapter_stories (chapter_id,title,synopsis_arabic,synopsis_english,video_url,story_order,dateCreated) VALUES (?,?,?,?,?, ?, NOW())"); if(!$stmt){ return false; } $stmt->bind_param("issssi",$data['chapter_id'],$data['title'],$data['synopsis_arabic'],$data['synopsis_english'],$data['video_url'],$next); $ok=$stmt->execute(); $id=$stmt->insert_id; $stmt->close(); return $ok?$id:false; }
function story_update($conn,$story_id,$data){ $stmt=$conn->prepare("UPDATE chapter_stories SET title=?, synopsis_arabic=?, synopsis_english=?, video_url=? WHERE story_id=?"); if(!$stmt){ return false; } $stmt->bind_param("ssssi",$data['title'],$data['synopsis_arabic'],$data['synopsis_english'],$data['video_url'],$story_id); $ok=$stmt->execute(); $stmt->close(); return $ok; }
function story_delete($conn,$story_id){ story_deleteInteractiveSections($conn,$story_id); $stmt=$conn->prepare("DELETE FROM chapter_stories WHERE story_id=?"); if(!$stmt){ return false; } $stmt->bind_param("i",$story_id); $ok=$stmt->execute(); $aff=$stmt->affected_rows; $stmt->close(); return $ok&&$aff>0; }
function story_getById($conn,$story_id){ $stmt=$conn->prepare("SELECT * FROM chapter_stories WHERE story_id=?"); if(!$stmt){ return null; } $stmt->bind_param("i",$story_id); $stmt->execute(); $res=$stmt->get_result(); $row=$res?$res->fetch_assoc():null; $stmt->close(); return $row?:null; }
function story_getInteractiveSections($conn,$story_id){ $check=$conn->query("SHOW TABLES LIKE 'story_interactive_sections'"); if(!$check||$check->num_rows==0){ return []; } $stmt=$conn->prepare("SELECT * FROM story_interactive_sections WHERE story_id=? ORDER BY section_order ASC"); if(!$stmt){ return []; } $stmt->bind_param("i",$story_id); $stmt->execute(); $res=$stmt->get_result(); $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $stmt->close(); return $rows; }
function story_deleteInteractiveSections($conn,$story_id){ $sections=story_getInteractiveSections($conn,$story_id); foreach($sections as $sec){ $s1=$conn->prepare("DELETE FROM interactive_questions WHERE section_id=?"); if($s1){ $s1->bind_param("i",$sec['section_id']); $s1->execute(); $s1->close(); } $s2=$conn->prepare("DELETE FROM story_interactive_sections WHERE section_id=?"); if($s2){ $s2->bind_param("i",$sec['section_id']); $s2->execute(); $s2->close(); } } return true; }

// Published library
function getPublishedPrograms($conn){ $sql="SELECT programID,title,description,price,category,thumbnail,status,dateCreated,teacherID FROM programs WHERE status='published' ORDER BY dateCreated DESC LIMIT 100"; $res=$conn->query($sql); if(!$res){ return []; } return $res->fetch_all(MYSQLI_ASSOC); }

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

// HTTP handler subset for JSON endpoints used by toolbar
if (basename($_SERVER['PHP_SELF']) === 'program-core.php') {
    header('Content-Type: application/json');
    echo json_encode(['success'=>true]);
}
