<?php
    include('dbConnection.php');

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/mail-config.php';

    // Create Account - SINGLE DECLARATION
    function createAccount($conn) { /* ... unchanged ... */ }

    // Existing functions (fetchEnrolledPrograms, fetchPublishedPrograms, enrollStudentInProgram, getProgramDetails, fetchProgramData, createTeacherAccount)
    // ... keep previous content ...

    // NEW: Fetch a single program by ID
    function fetchProgram($conn, $programID) {
        try {
            $stmt = $conn->prepare("SELECT programID, title, description, category, image, thumbnail, status, dateCreated, datePublished FROM programs WHERE programID = ? AND status IN ('published','pending_review','draft')");
            $stmt->bind_param("i", $programID);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc() ?: null;
        } catch (Exception $e) {
            error_log('Error fetching program: ' . $e->getMessage());
            return null;
        }
    }

    // NEW: Fetch chapters for a program
    function fetchChapters($conn, $programID) {
        try {
            $stmt = $conn->prepare("SELECT chapter_id, title, content, has_quiz, quiz_question_count, question, answer_options FROM program_chapters WHERE programID = ? ORDER BY chapter_order ASC");
            $stmt->bind_param("i", $programID);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching chapters: ' . $e->getMessage());
            return [];
        }
    }
?>