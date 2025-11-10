<?php
// exam-answer-handler.php
session_start();
require_once 'dbConnection.php';
require_once 'program-core.php';

// POST: action, answers, questionIDs, program_id
header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success'=>false,'message'=>'Student session required']);
    exit;
}

$student_id = (int)$_SESSION['userID'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'submit_final_exam') {
    $answers = $input['answers'];
    $qidList = $input['questionIDs'];
    $program_id = (int)($input['program_id'] ?? 0);
    if (!$answers || !$qidList || !$program_id) {
        echo json_encode(['success'=>false,'message'=>'Invalid submission']);
        exit;
    }

    $num_questions = count($qidList);
    $num_correct = 0;
    // For each question, get correct answer
    $sql = "SELECT qq.quiz_question_id, qq.question_text, qo.quiz_option_id, qo.is_correct
            FROM quiz_questions qq
            JOIN quiz_question_options qo ON qq.quiz_question_id = qo.quiz_question_id
            WHERE qq.quiz_question_id IN (" . implode(",", array_map('intval', $qidList)) . ")
            AND qo.is_correct=1";
    $result = $conn->query($sql);
    $correctMap = [];
    while ($row = $result->fetch_assoc()) {
        $correctMap[$row['quiz_question_id']] = $row['quiz_option_id'];
    }
    // Score
    foreach ($answers as $idx => $picked) {
        $qid = $qidList[$idx] ?? null;
        if (!$qid) continue;
        if (isset($correctMap[$qid]) && $picked == $correctMap[$qid]) {
            $num_correct++;
        }
    }
    $score_percent = $num_questions > 0 ? round($num_correct / $num_questions * 100, 2) : 0;
    $is_passed = $score_percent >= 75.0 ? 1 : 0;
    // Log attempt
    $stmt = $conn->prepare("INSERT INTO student_final_exam_attempts (student_id, program_id, score_percent, is_passed) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iidi", $student_id, $program_id, $score_percent, $is_passed);
    $stmt->execute();
    $stmt->close();

    if ($is_passed) {
        // Create certificate record pointing to certificate.php
        $certificateUrl = "certificate.php?program_id={$program_id}";
        
        $certStmt = $conn->prepare("
            INSERT INTO student_program_certificates (student_id, program_id, certificate_url, issue_date) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE certificate_url = VALUES(certificate_url), issue_date = NOW()
        ");
        $certStmt->bind_param("iis", $student_id, $program_id, $certificateUrl);
        $certStmt->execute();
        $certStmt->close();
        
        echo json_encode([
            'success' => true,
            'passed' => true,
            'message' => 'Congratulations! You passed the exam and earned your certificate!',
            'certificate_url' => $certificateUrl
        ]);
        exit;
    } else {
      // Reset all progress!
      $conn->query("DELETE FROM student_story_progress WHERE student_id={$student_id} AND story_id IN (SELECT cs.story_id FROM chapter_stories cs JOIN program_chapters pc ON cs.chapter_id=pc.chapter_id WHERE pc.programID={$program_id})");
      $conn->query("DELETE FROM student_quiz_attempts WHERE student_id={$student_id} AND quiz_id IN (SELECT cq.quiz_id FROM chapter_quizzes cq JOIN program_chapters pc ON cq.chapter_id=pc.chapter_id WHERE pc.programID={$program_id})");
      $conn->query("DELETE FROM student_chapter_progress WHERE studentID={$student_id} AND programID={$program_id}");
      $conn->query("UPDATE student_program_enrollments SET completion_percentage=0 WHERE student_id={$student_id} AND program_id={$program_id}");
      echo json_encode(['success'=>true, 'passed'=>false, 'score'=>$score_percent]);
    }
    exit;
}
echo json_encode(['success'=>false,'message'=>'Invalid action']);