<?php
/**
 * Student Progress & Certification Handler - Al-Ghaya LMS
 * Handles student enrollment, progress tracking, and certificate generation
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'dbConnection.php';

// Student Enrollment functions
function student_enroll($conn, $student_id, $program_id) {
    // Check if already enrolled
    $checkStmt = $conn->prepare("SELECT enrollment_id FROM student_enrollments WHERE student_id = ? AND program_id = ?");
    if ($checkStmt) {
        $checkStmt->bind_param("ii", $student_id, $program_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            $checkStmt->close();
            return false; // Already enrolled
        }
        $checkStmt->close();
    }
    
    $stmt = $conn->prepare("INSERT INTO student_enrollments (student_id, program_id, enrollment_date, completion_percentage, last_accessed) VALUES (?, ?, NOW(), 0.00, NOW())");
    if (!$stmt) { error_log("student_enroll prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("ii", $student_id, $program_id);
    if ($stmt->execute()) { $enrollment_id = $stmt->insert_id; $stmt->close(); return $enrollment_id; }
    error_log("student_enroll execute failed: " . $stmt->error); $stmt->close(); return false;
}

function student_updateProgress($conn, $student_id, $program_id) {
    // Get all stories in the program
    $totalStoriesStmt = $conn->prepare("
        SELECT COUNT(cs.story_id) as total_stories
        FROM chapter_stories cs
        INNER JOIN program_chapters pc ON cs.chapter_id = pc.chapter_id
        WHERE pc.program_id = ?
    ");
    if (!$totalStoriesStmt) return false;
    $totalStoriesStmt->bind_param("i", $program_id);
    $totalStoriesStmt->execute();
    $totalStories = $totalStoriesStmt->get_result()->fetch_assoc()['total_stories'];
    $totalStoriesStmt->close();
    
    if ($totalStories == 0) return false;
    
    // Get completed stories
    $completedStoriesStmt = $conn->prepare("
        SELECT COUNT(ssp.story_id) as completed_stories
        FROM student_story_progress ssp
        INNER JOIN chapter_stories cs ON ssp.story_id = cs.story_id
        INNER JOIN program_chapters pc ON cs.chapter_id = pc.chapter_id
        WHERE ssp.student_id = ? AND pc.program_id = ? AND ssp.is_completed = 1
    ");
    if (!$completedStoriesStmt) return false;
    $completedStoriesStmt->bind_param("ii", $student_id, $program_id);
    $completedStoriesStmt->execute();
    $completedStories = $completedStoriesStmt->get_result()->fetch_assoc()['completed_stories'];
    $completedStoriesStmt->close();
    
    // Calculate percentage
    $percentage = ($completedStories / $totalStories) * 100;
    
    // Update enrollment progress
    $updateStmt = $conn->prepare("UPDATE student_enrollments SET completion_percentage = ?, last_accessed = NOW() WHERE student_id = ? AND program_id = ?");
    if (!$updateStmt) return false;
    $updateStmt->bind_param("dii", $percentage, $student_id, $program_id);
    $ok = $updateStmt->execute();
    $updateStmt->close();
    
    return $ok ? $percentage : false;
}

function student_getEnrollments($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT se.*, p.title, p.description, p.thumbnail, p.category, p.difficulty_level,
               u.fname as teacher_fname, u.lname as teacher_lname
        FROM student_enrollments se
        INNER JOIN programs p ON se.program_id = p.programID
        INNER JOIN teacher t ON p.teacherID = t.teacherID
        INNER JOIN user u ON t.userID = u.userID
        WHERE se.student_id = ?
        ORDER BY se.enrollment_date DESC
    ");
    if (!$stmt) { error_log("student_getEnrollments prepare failed: " . $conn->error); return []; }
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function student_getEnrollment($conn, $student_id, $program_id) {
    $stmt = $conn->prepare("SELECT * FROM student_enrollments WHERE student_id = ? AND program_id = ?");
    if (!$stmt) { error_log("student_getEnrollment prepare failed: " . $conn->error); return null; }
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row;
}

// Student Story Progress functions
function studentStoryProgress_markCompleted($conn, $student_id, $story_id) {
    // Check if progress already exists
    $checkStmt = $conn->prepare("SELECT progress_id FROM student_story_progress WHERE student_id = ? AND story_id = ?");
    if ($checkStmt) {
        $checkStmt->bind_param("ii", $student_id, $story_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            $checkStmt->close();
            // Update existing progress
            $updateStmt = $conn->prepare("UPDATE student_story_progress SET is_completed = 1, completion_date = NOW(), last_accessed = NOW() WHERE student_id = ? AND story_id = ?");
            if (!$updateStmt) return false;
            $updateStmt->bind_param("ii", $student_id, $story_id);
            $ok = $updateStmt->execute();
            $updateStmt->close();
            return $ok;
        }
        $checkStmt->close();
    }
    
    // Insert new progress
    $stmt = $conn->prepare("INSERT INTO student_story_progress (student_id, story_id, is_completed, completion_date, last_accessed) VALUES (?, ?, 1, NOW(), NOW())");
    if (!$stmt) { error_log("studentStoryProgress_markCompleted prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("ii", $student_id, $story_id);
    if ($stmt->execute()) { $progress_id = $stmt->insert_id; $stmt->close(); return $progress_id; }
    error_log("studentStoryProgress_markCompleted execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

function studentStoryProgress_updateAccess($conn, $student_id, $story_id) {
    // Check if progress already exists
    $checkStmt = $conn->prepare("SELECT progress_id FROM student_story_progress WHERE student_id = ? AND story_id = ?");
    if ($checkStmt) {
        $checkStmt->bind_param("ii", $student_id, $story_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows > 0) {
            $checkStmt->close();
            // Update last accessed
            $updateStmt = $conn->prepare("UPDATE student_story_progress SET last_accessed = NOW() WHERE student_id = ? AND story_id = ?");
            if (!$updateStmt) return false;
            $updateStmt->bind_param("ii", $student_id, $story_id);
            $ok = $updateStmt->execute();
            $updateStmt->close();
            return $ok;
        }
        $checkStmt->close();
    }
    
    // Insert new access record
    $stmt = $conn->prepare("INSERT INTO student_story_progress (student_id, story_id, is_completed, last_accessed) VALUES (?, ?, 0, NOW())");
    if (!$stmt) { error_log("studentStoryProgress_updateAccess prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("ii", $student_id, $story_id);
    if ($stmt->execute()) { $progress_id = $stmt->insert_id; $stmt->close(); return $progress_id; }
    $stmt->close();
    return false;
}

function studentStoryProgress_getByProgram($conn, $student_id, $program_id) {
    $stmt = $conn->prepare("
        SELECT ssp.*, cs.title as story_title, pc.title as chapter_title, pc.chapter_order
        FROM student_story_progress ssp
        INNER JOIN chapter_stories cs ON ssp.story_id = cs.story_id
        INNER JOIN program_chapters pc ON cs.chapter_id = pc.chapter_id
        WHERE ssp.student_id = ? AND pc.program_id = ?
        ORDER BY pc.chapter_order ASC, cs.story_order ASC
    ");
    if (!$stmt) { error_log("studentStoryProgress_getByProgram prepare failed: " . $conn->error); return []; }
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Quiz Attempt functions
function studentQuizAttempt_create($conn, $student_id, $quiz_id, $score, $max_score) {
    // Get attempt number
    $countStmt = $conn->prepare("SELECT COUNT(*) as attempt_count FROM student_quiz_attempts WHERE student_id = ? AND quiz_id = ?");
    if (!$countStmt) return false;
    $countStmt->bind_param("ii", $student_id, $quiz_id);
    $countStmt->execute();
    $attempt_number = $countStmt->get_result()->fetch_assoc()['attempt_count'] + 1;
    $countStmt->close();
    
    // Calculate if passed (70% or higher)
    $percentage = ($score / $max_score) * 100;
    $is_passed = ($percentage >= 70) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO student_quiz_attempts (student_id, quiz_id, score, max_score, is_passed, attempt_number, attempt_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) { error_log("studentQuizAttempt_create prepare failed: " . $conn->error); return false; }
    $stmt->bind_param("iiddii", $student_id, $quiz_id, $score, $max_score, $is_passed, $attempt_number);
    if ($stmt->execute()) { $attempt_id = $stmt->insert_id; $stmt->close(); return $attempt_id; }
    error_log("studentQuizAttempt_create execute failed: " . $stmt->error);
    $stmt->close();
    return false;
}

function studentQuizAttempt_getBestScore($conn, $student_id, $quiz_id) {
    $stmt = $conn->prepare("SELECT * FROM student_quiz_attempts WHERE student_id = ? AND quiz_id = ? ORDER BY score DESC, attempt_date DESC LIMIT 1");
    if (!$stmt) { error_log("studentQuizAttempt_getBestScore prepare failed: " . $conn->error); return null; }
    $stmt->bind_param("ii", $student_id, $quiz_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row;
}

function studentQuizAttempt_getByProgram($conn, $student_id, $program_id) {
    $stmt = $conn->prepare("
        SELECT sqa.*, cq.title as quiz_title, pc.title as chapter_title
        FROM student_quiz_attempts sqa
        INNER JOIN chapter_quizzes cq ON sqa.quiz_id = cq.quiz_id
        INNER JOIN program_chapters pc ON cq.chapter_id = pc.chapter_id
        WHERE sqa.student_id = ? AND pc.program_id = ?
        ORDER BY sqa.attempt_date DESC
    ");
    if (!$stmt) { error_log("studentQuizAttempt_getByProgram prepare failed: " . $conn->error); return []; }
    $stmt->bind_param("ii", $student_id, $program_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Certificate Generation functions
function certificate_isEligible($conn, $student_id, $program_id) {
    // Check if student completed the program (100% completion)
    $enrollment = student_getEnrollment($conn, $student_id, $program_id);
    if (!$enrollment || $enrollment['completion_percentage'] < 100.0) {
        return false;
    }
    
    // Check if all quizzes are passed (70% or higher)
    $quizzesStmt = $conn->prepare("
        SELECT cq.quiz_id
        FROM chapter_quizzes cq
        INNER JOIN program_chapters pc ON cq.chapter_id = pc.chapter_id
        WHERE pc.program_id = ?
    ");
    if (!$quizzesStmt) return false;
    $quizzesStmt->bind_param("i", $program_id);
    $quizzesStmt->execute();
    $quizzes = $quizzesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $quizzesStmt->close();
    
    foreach ($quizzes as $quiz) {
        $bestAttempt = studentQuizAttempt_getBestScore($conn, $student_id, $quiz['quiz_id']);
        if (!$bestAttempt || !$bestAttempt['is_passed']) {
            return false;
        }
    }
    
    return true;
}

function certificate_generate($conn, $student_id, $program_id) {
    if (!certificate_isEligible($conn, $student_id, $program_id)) {
        return false;
    }
    
    // Get student and program information
    $infoStmt = $conn->prepare("
        SELECT u.fname, u.lname, p.title as program_title, p.category as difficulty_level,
               t_user.fname as teacher_fname, t_user.lname as teacher_lname
        FROM user u
        CROSS JOIN programs p
        INNER JOIN teacher t ON p.teacherID = t.teacherID
        INNER JOIN user t_user ON t.userID = t_user.userID
        WHERE u.userID = ? AND p.programID = ?
    ");
    if (!$infoStmt) return false;
    $infoStmt->bind_param("ii", $student_id, $program_id);
    $infoStmt->execute();
    $info = $infoStmt->get_result()->fetch_assoc();
    $infoStmt->close();
    
    if (!$info) return false;
    
    // Generate certificate data
    $certificate = [
        'student_name' => $info['fname'] . ' ' . $info['lname'],
        'program_title' => $info['program_title'],
        'difficulty_level' => $info['difficulty_level'],
        'teacher_name' => $info['teacher_fname'] . ' ' . $info['teacher_lname'],
        'completion_date' => date('F j, Y'),
        'certificate_id' => 'AG-' . strtoupper(substr(md5($student_id . $program_id . time()), 0, 8)),
        'issue_date' => date('Y-m-d H:i:s')
    ];
    
    return $certificate;
}

// Utility functions
function getStudentIdFromSession($conn, $user_id) {
    $stmt = $conn->prepare("SELECT studentID FROM student WHERE userID = ? AND isActive = 1");
    if (!$stmt) { error_log("getStudentIdFromSession prepare failed: " . $conn->error); return null; }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)$row['studentID'];
    }
    $stmt->close();
    return null;
}

function validateStudentAccess() {
    return isset($_SESSION['userID']) && ($_SESSION['role'] ?? '') === 'student';
}

// Handler logic - only run when directly accessed
if (basename($_SERVER['PHP_SELF']) === 'student-progress.php') {
    if (!validateStudentAccess()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    $user_id = $_SESSION['userID'];
    $student_id = getStudentIdFromSession($conn, $user_id);
    if (!$student_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Student profile not found']);
        exit;
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $action = $input['action'] ?? $action;
            $_POST = array_merge($_POST, $input);
        }
    }

    try {
        switch ($action) {
            case 'enroll_program':
                header('Content-Type: application/json');
                $program_id = intval($_POST['program_id'] ?? 0);
                if (!$program_id) {
                    echo json_encode(['success' => false, 'message' => 'Program ID required']);
                    exit;
                }
                
                $enrollment_id = student_enroll($conn, $student_id, $program_id);
                echo json_encode($enrollment_id ? 
                    ['success' => true, 'enrollment_id' => $enrollment_id, 'message' => 'Enrolled successfully'] : 
                    ['success' => false, 'message' => 'Failed to enroll or already enrolled']
                );
                exit;
                
            case 'complete_story':
                header('Content-Type: application/json');
                $story_id = intval($_POST['story_id'] ?? 0);
                $program_id = intval($_POST['program_id'] ?? 0);
                
                if (!$story_id || !$program_id) {
                    echo json_encode(['success' => false, 'message' => 'Story ID and Program ID required']);
                    exit;
                }
                
                $progress_id = studentStoryProgress_markCompleted($conn, $student_id, $story_id);
                if ($progress_id) {
                    $completion_percentage = student_updateProgress($conn, $student_id, $program_id);
                    echo json_encode([
                        'success' => true, 
                        'progress_id' => $progress_id,
                        'completion_percentage' => $completion_percentage,
                        'message' => 'Story completed successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to mark story as completed']);
                }
                exit;
                
            case 'submit_quiz':
                header('Content-Type: application/json');
                $quiz_id = intval($_POST['quiz_id'] ?? 0);
                $score = floatval($_POST['score'] ?? 0);
                $max_score = floatval($_POST['max_score'] ?? 0);
                
                if (!$quiz_id || !$max_score) {
                    echo json_encode(['success' => false, 'message' => 'Quiz ID and max score required']);
                    exit;
                }
                
                $attempt_id = studentQuizAttempt_create($conn, $student_id, $quiz_id, $score, $max_score);
                if ($attempt_id) {
                    $percentage = ($score / $max_score) * 100;
                    $passed = $percentage >= 70;
                    echo json_encode([
                        'success' => true,
                        'attempt_id' => $attempt_id,
                        'score' => $score,
                        'max_score' => $max_score,
                        'percentage' => round($percentage, 1),
                        'passed' => $passed,
                        'message' => $passed ? 'Quiz passed!' : 'Quiz completed. You need 70% to pass.'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to submit quiz']);
                }
                exit;
                
            case 'generate_certificate':
                header('Content-Type: application/json');
                $program_id = intval($_POST['program_id'] ?? 0);
                
                if (!$program_id) {
                    echo json_encode(['success' => false, 'message' => 'Program ID required']);
                    exit;
                }
                
                $certificate = certificate_generate($conn, $student_id, $program_id);
                echo json_encode($certificate ? 
                    ['success' => true, 'certificate' => $certificate, 'message' => 'Certificate generated successfully'] : 
                    ['success' => false, 'message' => 'Not eligible for certificate. Complete all stories and pass all quizzes.']
                );
                exit;
                
            case 'get_progress':
                header('Content-Type: application/json');
                $program_id = intval($_GET['program_id'] ?? 0);
                
                if (!$program_id) {
                    echo json_encode(['success' => false, 'message' => 'Program ID required']);
                    exit;
                }
                
                $enrollment = student_getEnrollment($conn, $student_id, $program_id);
                $story_progress = studentStoryProgress_getByProgram($conn, $student_id, $program_id);
                $quiz_attempts = studentQuizAttempt_getByProgram($conn, $student_id, $program_id);
                $certificate_eligible = certificate_isEligible($conn, $student_id, $program_id);
                
                echo json_encode([
                    'success' => true,
                    'enrollment' => $enrollment,
                    'story_progress' => $story_progress,
                    'quiz_attempts' => $quiz_attempts,
                    'certificate_eligible' => $certificate_eligible
                ]);
                exit;
                
            default:
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
                exit;
        }
    } catch (Exception $e) {
        error_log("Student Progress Handler Error: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}
