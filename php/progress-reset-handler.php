<?php
// progress-reset-handler.php
session_start();
require_once 'dbConnection.php';

header('Content-Type: application/json');
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success'=>false,'message'=>'Student session required']);
    exit;
}

$student_id = (int)$_SESSION['userID'];
$program_id = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;

if ($program_id <= 0) {
    $raw = json_decode(file_get_contents('php://input'), true);
    $program_id = isset($raw['program_id']) ? (int)$raw['program_id'] : 0;
}

if (!$program_id) {
    echo json_encode(['success'=>false,'message'=>'Missing program_id']);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'unenroll' || (isset($raw['action']) && $raw['action'] === 'unenroll')) {
    // Remove from student_program_enrollments
    $conn->query("DELETE FROM student_program_enrollments WHERE student_id={$student_id} AND program_id={$program_id}");
    $conn->query("DELETE FROM student_story_progress WHERE student_id={$student_id} AND story_id IN (SELECT cs.story_id FROM chapter_stories cs JOIN program_chapters pc ON cs.chapter_id=pc.chapter_id WHERE pc.programID={$program_id})");
    $conn->query("DELETE FROM student_chapter_progress WHERE studentID={$student_id} AND programID={$program_id}");
    $conn->query("DELETE FROM student_quiz_attempts WHERE student_id={$student_id} AND quiz_id IN (SELECT cq.quiz_id FROM chapter_quizzes cq JOIN program_chapters pc ON cq.chapter_id=pc.chapter_id WHERE pc.programID={$program_id})");
    $conn->query("DELETE FROM student_final_exam_attempts WHERE student_id={$student_id} AND program_id={$program_id}");
    $conn->query("DELETE FROM student_program_certificates WHERE student_id={$student_id} AND program_id={$program_id}");
    echo json_encode(['success'=>true,'message'=>'Unenrolled and progress reset for development/testing.']);
    exit;
}

// Default: Reset Progress (but keep enrollment)
$conn->query("DELETE FROM student_story_progress WHERE student_id={$student_id} AND story_id IN (SELECT cs.story_id FROM chapter_stories cs JOIN program_chapters pc ON cs.chapter_id=pc.chapter_id WHERE pc.programID={$program_id})");
$conn->query("DELETE FROM student_chapter_progress WHERE studentID={$student_id} AND programID={$program_id}");
$conn->query("DELETE FROM student_quiz_attempts WHERE student_id={$student_id} AND quiz_id IN (SELECT cq.quiz_id FROM chapter_quizzes cq JOIN program_chapters pc ON cq.chapter_id=pc.chapter_id WHERE pc.programID={$program_id})");
$conn->query("DELETE FROM student_final_exam_attempts WHERE student_id={$student_id} AND program_id={$program_id}");
$conn->query("DELETE FROM student_program_certificates WHERE student_id={$student_id} AND program_id={$program_id}");
$conn->query("UPDATE student_program_enrollments SET completion_percentage=0 WHERE student_id={$student_id} AND program_id={$program_id}");
echo json_encode(['success'=>true,'message'=>'Progress reset for testing. Enrollment kept.']);
exit;
