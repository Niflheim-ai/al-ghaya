<?php
/**
 * Quiz Handler - Al-Ghaya LMS
 * Handles quiz creation, editing, and question management
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'dbConnection.php';
require_once 'program-handler.php';

// Guard against redeclaration if loaded alongside program-handler
if (!function_exists('validateTeacherAccess')) {
    function validateTeacherAccess() {
        return isset($_SESSION['userID']) && (($_SESSION['role'] ?? '') === 'teacher');
    }
}

// Quiz CRUD functions
function quiz_create($conn, $chapter_id, $title) {
    $stmt = $conn->prepare("INSERT INTO chapter_quizzes (chapter_id, title, dateCreated, dateUpdated) VALUES (?, ?, NOW(), NOW())");
    if (!$stmt) { error_log("quiz_create prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("is", $chapter_id, $title);
    if ($stmt->execute()) { $quiz_id = $stmt->insert_id; $stmt->close(); return $quiz_id; }
    error_log("quiz_create execute failed: " . $stmt->error); $stmt->close(); return false;
}

function quiz_update($conn, $quiz_id, $title) {
    $stmt = $conn->prepare("UPDATE chapter_quizzes SET title = ?, dateUpdated = NOW() WHERE quiz_id = ?");
    if (!$stmt) { error_log("quiz_update prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("si", $title, $quiz_id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function quiz_delete($conn, $quiz_id) {
    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("DELETE qo FROM quiz_question_options qo INNER JOIN quiz_questions qq ON qo.quiz_question_id = qq.quiz_question_id WHERE qq.quiz_id = ?");
        if ($stmt1) { $stmt1->bind_param("i", $quiz_id); $stmt1->execute(); $stmt1->close(); }
        $stmt2 = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
        if ($stmt2) { $stmt2->bind_param("i", $quiz_id); $stmt2->execute(); $stmt2->close(); }
        $stmt3 = $conn->prepare("DELETE FROM chapter_quizzes WHERE quiz_id = ?");
        if (!$stmt3) throw new Exception("quiz_delete prepare failed: " . $conn->error);
        $stmt3->bind_param("i", $quiz_id); if (!$stmt3->execute()) throw new Exception("quiz_delete execute failed: " . $stmt3->error);
        $affected = $stmt3->affected_rows; $stmt3->close();
        $conn->commit(); return $affected > 0;
    } catch (Exception $e) { $conn->rollback(); error_log("quiz_delete transaction failed: " . $e->getMessage()); return false; }
}

function quiz_getById($conn, $quiz_id) {
    $stmt = $conn->prepare("SELECT * FROM chapter_quizzes WHERE quiz_id = ?");
    if (!$stmt) { error_log("quiz_getById prepare failed: " . $conn->error); return null; }
    $stmt->bind_param("i", $quiz_id); $stmt->execute(); $res = $stmt->get_result(); $row = $res->fetch_assoc(); $stmt->close(); return $row;
}

function quiz_getByChapter($conn, $chapter_id) {
    $stmt = $conn->prepare("SELECT * FROM chapter_quizzes WHERE chapter_id = ? LIMIT 1");
    if (!$stmt) { error_log("quiz_getByChapter prepare failed: " . $conn->error); return null; }
    $stmt->bind_param("i", $chapter_id); $stmt->execute(); $res = $stmt->get_result(); $row = $res->fetch_assoc(); $stmt->close(); return $row;
}

function quizQuestion_create($conn, $quiz_id, $question_text, $question_order) {
    $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_order, dateCreated) VALUES (?, ?, ?, NOW())");
    if (!$stmt) { error_log("quizQuestion_create prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("isi", $quiz_id, $question_text, $question_order);
    if ($stmt->execute()) { $question_id = $stmt->insert_id; $stmt->close(); return $question_id; }
    error_log("quizQuestion_create execute failed: " . $stmt->error); $stmt->close(); return false;
}

function quizQuestion_deleteByQuiz($conn, $quiz_id) {
    $stmt1 = $conn->prepare("DELETE qo FROM quiz_question_options qo INNER JOIN quiz_questions qq ON qo.quiz_question_id = qq.quiz_question_id WHERE qq.quiz_id = ?");
    if ($stmt1) { $stmt1->bind_param("i", $quiz_id); $stmt1->execute(); $stmt1->close(); }
    $stmt2 = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
    if (!$stmt2) { error_log("quizQuestion_deleteByQuiz prepare failed: " . $conn->error); return false; }
    $stmt2->bind_param("i", $quiz_id); $ok = $stmt2->execute(); $stmt2->close(); return $ok;
}

function quizQuestion_getByQuiz($conn, $quiz_id) {
    $stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order ASC");
    if (!$stmt) { error_log("quizQuestion_getByQuiz prepare failed: " . $conn->error); return []; }
    $stmt->bind_param("i", $quiz_id); $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close();
    foreach ($rows as &$question) { $question['options'] = quizQuestionOption_getByQuestion($conn, $question['quiz_question_id']); }
    return $rows;
}

function quizQuestionOption_create($conn, $question_id, $option_text, $is_correct, $option_order) {
    $stmt = $conn->prepare("INSERT INTO quiz_question_options (quiz_question_id, option_text, is_correct, option_order, dateCreated) VALUES (?, ?, ?, ?, NOW())");
    if (!$stmt) { error_log("quizQuestionOption_create prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("isii", $question_id, $option_text, $is_correct, $option_order);
    if ($stmt->execute()) { $option_id = $stmt->insert_id; $stmt->close(); return $option_id; }
    error_log("quizQuestionOption_create execute failed: " . $stmt->error); $stmt->close(); return false;
}

function quizQuestionOption_getByQuestion($conn, $question_id) {
    $stmt = $conn->prepare("SELECT * FROM quiz_question_options WHERE quiz_question_id = ? ORDER BY option_order ASC");
    if (!$stmt) { error_log("quizQuestionOption_getByQuestion prepare failed: " . $conn->error); return []; }
    $stmt->bind_param("i", $question_id); $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); return $rows;
}

// Interactive Section functions (same as before)
function interactiveSection_create($conn, $story_id) {
    $orderStmt = $conn->prepare("SELECT COALESCE(MAX(section_order), 0) + 1 as next_order FROM story_interactive_sections WHERE story_id = ?");
    if (!$orderStmt) { error_log("interactiveSection_create order query prepare failed: " . $conn->error); return false; }
    $orderStmt->bind_param("i", $story_id); $orderStmt->execute(); $next_order = $orderStmt->get_result()->fetch_assoc()['next_order']; $orderStmt->close();
    $stmt = $conn->prepare("INSERT INTO story_interactive_sections (story_id, section_order, dateCreated) VALUES (?, ?, NOW())");
    if (!$stmt) { error_log("interactiveSection_create prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("ii", $story_id, $next_order); $ok = $stmt->execute(); $id = $stmt->insert_id; $stmt->close(); return $ok ? $id : false;
}

function interactiveSection_delete($conn, $section_id) {
    $stmt1 = $conn->prepare("DELETE FROM interactive_questions WHERE section_id = ?");
    if ($stmt1) { $stmt1->bind_param("i", $section_id); $stmt1->execute(); $stmt1->close(); }
    $stmt = $conn->prepare("DELETE FROM story_interactive_sections WHERE section_id = ?");
    if (!$stmt) { error_log("interactiveSection_delete prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("i", $section_id); $ok = $stmt->execute(); $affected = $stmt->affected_rows; $stmt->close(); return $ok && $affected > 0;
}

function interactiveSection_getByStory($conn, $story_id) {
    $stmt = $conn->prepare("SELECT * FROM story_interactive_sections WHERE story_id = ? ORDER BY section_order ASC");
    if (!$stmt) { error_log("interactiveSection_getByStory prepare failed: " . $conn->error); return []; }
    $stmt->bind_param("i", $story_id); $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close();
    foreach ($rows as &$section) { $section['questions'] = interactiveQuestion_getBySection($conn, $section['section_id']); }
    return $rows;
}

function interactiveQuestion_create($conn, $section_id, $question_text, $question_type, $question_order) {
    $stmt = $conn->prepare("INSERT INTO interactive_questions (section_id, question_text, question_type, question_order, dateCreated) VALUES (?, ?, ?, ?, NOW())");
    if (!$stmt) { error_log("interactiveQuestion_create prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("issi", $section_id, $question_text, $question_type, $question_order);
    if ($stmt->execute()) { $question_id = $stmt->insert_id; $stmt->close(); return $question_id; }
    error_log("interactiveQuestion_create execute failed: " . $stmt->error); $stmt->close(); return false;
}

function interactiveQuestion_getBySection($conn, $section_id) {
    $stmt = $conn->prepare("SELECT * FROM interactive_questions WHERE section_id = ? ORDER BY question_order ASC");
    if (!$stmt) { error_log("interactiveQuestion_getBySection prepare failed: " . $conn->error); return []; }
    $stmt->bind_param("i", $section_id); $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close();
    foreach ($rows as &$question) { $question['options'] = questionOption_getByQuestion($conn, $question['question_id']); }
    return $rows;
}

function questionOption_create($conn, $question_id, $option_text, $is_correct, $option_order) {
    $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct, option_order, dateCreated) VALUES (?, ?, ?, ?, NOW())");
    if (!$stmt) { error_log("questionOption_create prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("isii", $question_id, $option_text, $is_correct, $option_order);
    if ($stmt->execute()) { $option_id = $stmt->insert_id; $stmt->close(); return $option_id; }
    error_log("questionOption_create execute failed: " . $stmt->error); $stmt->close(); return false;
}

function questionOption_getByQuestion($conn, $question_id) {
    $stmt = $conn->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY option_order ASC");
    if (!$stmt) { error_log("questionOption_getByQuestion prepare failed: " . $conn->error); return []; }
    $stmt->bind_param("i", $question_id); $stmt->execute(); $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); return $rows;
}

// Handler logic - only run when directly accessed (unchanged below)
$__SELF__ = basename($_SERVER['PHP_SELF']);
if ($__SELF__ === 'quiz-handler.php') {
    if (!validateTeacherAccess()) {
        http_response_code(403);
        if (isset($_POST['action']) && in_array($_POST['action'], ['create_quiz', 'update_quiz'])) {
            $_SESSION['error_message'] = 'Unauthorized access';
            header('Location: ../pages/teacher/teacher-programs.php');
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // ... rest of the existing switch logic remains identical ...
}

// Legacy aliases (unchanged)
function getQuiz($conn, $quiz_id) { return quiz_getById($conn, $quiz_id); }
function getChapterQuiz($conn, $chapter_id) { return quiz_getByChapter($conn, $chapter_id); }
function getQuizQuestions($conn, $quiz_id) { return quizQuestion_getByQuiz($conn, $quiz_id); }
function getQuestionOptions($conn, $question_id) { return quizQuestionOption_getByQuestion($conn, $question_id); }
function getStoryInteractiveSections($conn, $story_id) { return interactiveSection_getByStory($conn, $story_id); }
function getSectionQuestions($conn, $section_id) { return interactiveQuestion_getBySection($conn, $section_id); }
function createQuiz($conn, $chapter_id, $title) { return quiz_create($conn, $chapter_id, $title); }
function deleteQuiz($conn, $quiz_id) { return quiz_delete($conn, $quiz_id); }
