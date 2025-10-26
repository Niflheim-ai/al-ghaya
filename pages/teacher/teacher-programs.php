<?php
session_start();
$current_page = "teacher-programs";
$page_title = "My Programs";
$debug_mode = false;

if (!isset($_SESSION['userID']) || (($_SESSION['role'] ?? '') !== 'teacher')) { $_SESSION['error_message']='Access denied'; header('Location: ../login.php'); exit(); }

require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';
require_once '../../php/program-helpers.php';

$user_id = (int)$_SESSION['userID'];
$action = $_GET['action'] ?? 'list';
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : null;
$story_id = isset($_GET['story_id']) ? (int)$_GET['story_id'] : null;

// Get teacherID using consolidated helper from program-handler via namespaced proxy
$teacher_id = ph_getTeacherIdFromSession($conn, $user_id);
if (!$teacher_id) { $_SESSION['error_message']='Teacher profile not found or inactive.'; header('Location: ../teacher/teacher-dashboard.php'); exit(); }

switch ($action) {
  case 'create':
    $pageContent='program_details';
    $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
    break;
  case 'edit_chapter':
    $pageContent='chapter_content';
    $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
    if (!$chapter) { $_SESSION['error_message']='Failed to get chapter.'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    // Ownership check
    if (!$program || (int)($program['programID']??0) !== (int)($chapter['programID']??-1)) { $_SESSION['error_message']='Access denied to chapter.'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    break;
  case 'add_story':
    $pageContent='story_form';
    $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
    if (!$program || !$chapter || (int)($program['programID']??0)!==(int)($chapter['programID']??-1)) { $_SESSION['error_message']='Invalid program or no permission'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    $story = $story_id ? getStory($conn, $story_id) : null;
    break;
  case 'add_quiz':
    $pageContent='quiz_form';
    $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
    if (!$program || !$chapter || (int)($program['programID']??0)!==(int)($chapter['programID']??-1)) { $_SESSION['error_message']='Invalid program or no permission'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    $quiz = $chapter_id ? getChapterQuiz($conn, $chapter_id) : null;
    break;
  default:
    $pageContent='programs_list';
    $myPrograms = getTeacherPrograms($conn, $teacher_id);
    $allPrograms = getPublishedPrograms($conn);
}

$success = $_SESSION['success_message'] ?? null;
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>
<!-- rest of file unchanged below -->
