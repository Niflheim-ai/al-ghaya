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

    // Create Account - SINGLE DECLARATION (original)
    function createAccount($conn) {
        if (isset($_POST['create-account'])) {
            if (!empty($_POST['first-name']) && !empty($_POST['last-name']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['confirm-password'])) {
                $inputFirstName = trim($_POST['first-name']);
                $inputLastName = trim($_POST['last-name']);
                $inputEmail = trim($_POST['email']);
                $inputPassword = $_POST['password'];
                $inputConfirmPassword = $_POST['confirm-password'];

                if (!filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
                    echo '<script>Swal.fire({title:"Error",text:"Invalid email format",icon:"error"}).then(()=>window.location.href="register.php")</script>';
                    return;
                }
                if ($inputPassword !== $inputConfirmPassword) {
                    echo '<script>Swal.fire({title:"Error",text:"Passwords do not match",icon:"error"}).then(()=>window.location.href="register.php")</script>';
                    return;
                }

                $checkStmt = $conn->prepare("SELECT 1 FROM user WHERE email = ? LIMIT 1");
                $checkStmt->bind_param("s", $inputEmail);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows < 1) {
                    $hashedPassword = password_hash($inputPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                    $role = 'student'; $level = 1; $points = 0; $proficiency = 'beginner';
                    $saveRecord = $conn->prepare("INSERT INTO user (email, password, fname, lname, role, level, points, proficiency, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $saveRecord->bind_param("sssssiis", $inputEmail, $hashedPassword, $inputFirstName, $inputLastName, $role, $level, $points, $proficiency);
                    if (!$saveRecord->execute()) {
                        echo '<script>Swal.fire({title:"Error",text:"Account creation failed",icon:"error"}).then(()=>window.location.href="register.php")</script>';
                    } else {
                        echo '<script>Swal.fire({title:"Success",text:"Account successfully created",icon:"success"}).then(()=>window.location.href="register.php")</script>';
                        $saveRecord->close();
                        $conn->close();
                    }
                } else {
                    echo '<script>Swal.fire({title:"Error",text:"Email is already in use",icon:"error"}).then(()=>window.location.href="register.php")</script>';
                }
                $checkStmt->close();
            } else {
                echo '<script>Swal.fire({title:"Error",text:"All fields are required",icon:"error"}).then(()=>window.location.href="register.php")</script>';
            }
        }
    }

    // Restored functions
    function fetchEnrolledPrograms($conn, $studentId, $difficulty = 'all', $status = 'all', $search = '') {
        $sql = "
            SELECT p.programID, p.title, p.description, p.image, p.thumbnail, 
                p.category, p.difficulty_level, p.price, p.currency,
                p.dateCreated,           -- ✅ ADDED
                p.dateUpdated,           -- ✅ ADDED
                spe.enrollment_date,
                spe.last_accessed,
                'enrolled' AS enrollment_status,
                (SELECT COUNT(DISTINCT cs.story_id)
                    FROM program_chapters pc
                    INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
                    WHERE pc.programID = p.programID) AS total_stories,
                (SELECT COUNT(DISTINCT ssp.story_id)
                    FROM program_chapters pc
                    INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
                    INNER JOIN student_story_progress ssp ON cs.story_id = ssp.story_id
                    WHERE pc.programID = p.programID AND ssp.student_id = spe.student_id AND ssp.is_completed = 1) AS completed_stories
            FROM programs p
            INNER JOIN student_program_enrollments spe ON p.programID = spe.program_id
            WHERE spe.student_id = ?
        ";
        
        $params = [$studentId];
        $types = 'i';
        
        // Add difficulty filter
        if ($difficulty !== 'all') {
            $sql .= " AND p.category = ?";
            $params[] = $difficulty;
            $types .= 's';
        }
        
        // Add search filter
        if (!empty($search)) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        $sql .= " ORDER BY spe.last_accessed DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $programs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Calculate completion percentage for each program
        foreach ($programs as &$program) {
            if ($program['total_stories'] > 0) {
                $program['completion_percentage'] = ($program['completed_stories'] / $program['total_stories']) * 100;
            } else {
                $program['completion_percentage'] = 0;
            }
        }
        
        // ✅ Filter by status AFTER calculating completion percentage
        if ($status !== 'all') {
            $programs = array_filter($programs, function($program) use ($status) {
                $completion = $program['completion_percentage'];
                if ($status === 'in-progress') {
                    return $completion > 0 && $completion < 100;
                } elseif ($status === 'completed') {
                    return $completion >= 100;
                }
                return true;
            });
            $programs = array_values($programs); // Re-index array
        }
        
        return $programs;
    }

    function fetchPublishedPrograms($conn, $studentId, $difficulty = 'all', $status = 'all', $search = '') {
        $sql = "
            SELECT p.programID, p.title, p.description, p.image, p.thumbnail, 
                p.category, p.difficulty_level, p.price, p.currency,
                p.dateCreated, p.dateUpdated,
                p.version_number, p.parent_program_id,
                'not-enrolled' AS enrollment_status
            FROM programs p
            WHERE p.status = 'published'
            AND p.is_latest_version = 1  -- ✅ Only show latest versions
            AND p.programID NOT IN (
                SELECT program_id 
                FROM student_program_enrollments 
                WHERE student_id = ?
            )
        ";
        
        $params = [$studentId];
        $types = 'i';
        
        // Add difficulty filter
        if ($difficulty !== 'all') {
            $sql .= " AND p.category = ?";
            $params[] = $difficulty;
            $types .= 's';
        }
        
        // Add search filter
        if (!empty($search)) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        $sql .= " ORDER BY p.dateCreated DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $programs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $programs;
    }

    function enrollStudentInProgram($conn, $studentID, $programID) {
        try {
            $checkStmt = $conn->prepare("SELECT enrollment_id FROM student_program_enrollments WHERE student_id = ? AND program_id = ?");
            $checkStmt->bind_param("ii", $studentID, $programID); $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) { return false; }
            $enrollStmt = $conn->prepare("INSERT INTO student_program_enrollments (student_id, program_id, completion_percentage, enrollment_date, last_accessed) VALUES (?, ?, 0, NOW(), NOW())");
            $enrollStmt->bind_param("ii", $studentID, $programID); return $enrollStmt->execute();
        } catch (Exception $e) { error_log("enrollStudentInProgram: ".$e->getMessage()); return false; }
    }

    function getProgramDetails($conn, $programID, $studentID = null) {
        try {
            $sql = "SELECT p.*, CASE WHEN spe.student_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled, COALESCE(spe.completion_percentage, 0) as completion_percentage, spe.enrollment_date, spe.last_accessed FROM programs p LEFT JOIN student_program_enrollments spe ON p.programID = spe.program_id AND spe.student_id = ? WHERE p.programID = ?";
            $stmt = $conn->prepare($sql); $stmt->bind_param("ii", $studentID, $programID); $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) { error_log("getProgramDetails: ".$e->getMessage()); return null; }
    }

    function fetchProgramData($conn) {
        if (isset($_GET['title']) && isset($_GET['category'])) {
            $title = trim($_GET['title']); $category = trim($_GET['category']);
            $programQuery = $conn->prepare("SELECT image, title, description, category FROM programs WHERE title = ? AND category = ?");
            $programQuery->bind_param("ss", $title, $category); $programQuery->execute(); $programResult = $programQuery->get_result();
            if ($programResult && $programResult->num_rows > 0) {
                while($row = $programResult->fetch_assoc()) {
                    $program_title = $row['title']; $program_level = $row['category']; $program_description = $row['description']; $program_image = $row['image'];
                    $levelClass = '';
                    switch (strtolower($program_level)) {
                        case 'beginner': $levelClass = 'text-white bg-black px-3 rounded-lg font-semibold'; break;
                        case 'intermediate': $levelClass = 'text-white bg-[#10375B] px-3 rounded-lg font-semibold'; break;
                        case 'advanced': $levelClass = 'text-white bg-[#A58618] px-3 rounded-lg font-semibold'; break;
                        default: $levelClass = 'text-gray-600';
                    }
                    echo "<div class='container mx-auto'><div class='max-w-4xl mx-auto bg-white shadow-md rounded-b-lg overflow-hidden'><img src='../images/$program_image.png' alt='$program_title' class='w-full h-80 object-cover'></div><div class='mx-auto max-w-[70%] lg:max-w-[65%] md:max-w-[60%] text-justify mt-15 lg:text-left'><h1 class='text-4xl font-bold mb-2 text-[#10375B] text-center lg:text-left'>$program_title <span class='text-lg mb-4 ml-2 $levelClass'>$program_level</span></h1><p class='text-gray-700 leading-relaxed'>$program_description</p></div></div>";
                }
            } else { header("Location: 404.php"); exit(); }
            $programQuery->close();
        }
    }

    function createTeacherAccount($teacherEmail, $fname = null, $lname = null, $adminID = null) {
        $length = 12; $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; $password = '';
        for ($i = 0; $i < $length; $i++) { $password .= $chars[random_int(0, strlen($chars) - 1)]; }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $conn = getDbConnection(); if (!$conn) { return false; }
        $username = $teacherEmail; $stmt = $conn->prepare("INSERT INTO teacher (email, username, password, dateCreated) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $teacherEmail, $username, $hashedPassword); $stmt->execute(); $stmt->close();
        try { $result = sendTeacherWelcomeEmail($teacherEmail, $password, $fname ?? '', $lname ?? ''); return $result['success'] ? true : $result['message']; }
        catch (Exception $e) { error_log('Teacher welcome email failed: ' . $e->getMessage()); return false; }
    }

    // NEW: Fetch a single program by ID
    function fetchProgram($conn, $programID) {
        try {
            $stmt = $conn->prepare("SELECT programID, title, description, category, image, thumbnail, status, dateCreated, datePublished FROM programs WHERE programID = ? AND status IN ('published','pending_review','draft')");
            $stmt->bind_param("i", $programID); $stmt->execute(); $result = $stmt->get_result();
            return $result->fetch_assoc() ?: null;
        } catch (Exception $e) { error_log('Error fetching program: ' . $e->getMessage()); return null; }
    }

    // NEW: Fetch chapters for a program
    function fetchChapters($conn, $programID) {
        try {
            $stmt = $conn->prepare("SELECT chapter_id, title, content, has_quiz, quiz_question_count, question, answer_options FROM program_chapters WHERE programID = ? ORDER BY chapter_order ASC");
            $stmt->bind_param("i", $programID); $stmt->execute(); $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) { error_log('Error fetching chapters: ' . $e->getMessage()); return []; }
    }

    // ===============================
    // ENHANCED PROGRAM VIEW FUNCTIONS
    // ===============================

    // Fetch chapters with stories and progress tracking
    function fetchChaptersWithStories($conn, $programID) {
        try {
            // Get chapters
            $chaptersStmt = $conn->prepare("
                SELECT 
                    pc.chapter_id, 
                    pc.title, 
                    pc.content, 
                    pc.video_url, 
                    pc.question, 
                    pc.question_type, 
                    pc.answer_options, 
                    pc.correct_answer, 
                    pc.chapter_order,
                    pc.is_required
                FROM program_chapters pc 
                WHERE pc.programID = ? 
                ORDER BY pc.chapter_order ASC
            ");
            $chaptersStmt->bind_param("i", $programID);
            $chaptersStmt->execute();
            $chapters = $chaptersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // For each chapter, get stories (using content as stories for now)
            foreach ($chapters as &$chapter) {
                $chapter['stories'] = [];
                $chapter['is_unlocked'] = true; // For now, all chapters are unlocked
                $chapter['is_completed'] = false; // TODO: Implement completion tracking
                
                // Create mock stories from chapter content if available
                if (!empty($chapter['content'])) {
                    $chapter['stories'][] = [
                        'story_id' => $chapter['chapter_id'] . '01', // Mock story ID
                        'title' => 'Story: ' . $chapter['title'],
                        'content' => $chapter['content'],
                        'video_url' => $chapter['video_url'],
                        'is_unlocked' => true,
                        'is_completed' => false
                    ];
                }
            }

            return $chapters;
        } catch (Exception $e) {
            error_log('Error fetching chapters with stories: ' . $e->getMessage());
            return [];
        }
    }

    // Get teacher information for a program
    function getTeacherInfo($conn, $programID) {
        try {
            $stmt = $conn->prepare("
                SELECT t.teacherID, t.fname, t.lname, t.specialization, t.profile_picture 
                FROM teacher t 
                JOIN programs p ON t.teacherID = p.teacherID 
                WHERE p.programID = ?
            ");
            $stmt->bind_param("i", $programID);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log('Error fetching teacher info: ' . $e->getMessage());
            return null;
        }
    }

    // Get student progress for a program
    function getStudentProgress($conn, $studentID, $programID) {
        try {
            $stmt = $conn->prepare("
                SELECT 
                    spe.completion_percentage,
                    spe.enrollment_date,
                    spe.last_accessed
                FROM student_program_enrollments spe 
                WHERE spe.student_id = ? AND spe.program_id = ?
            ");
            $stmt->bind_param("ii", $studentID, $programID);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            return $result ?: [
                'completion_percentage' => 0,
                'enrollment_date' => date('Y-m-d H:i:s'),
                'last_accessed' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log('Error fetching student progress: ' . $e->getMessage());
            return ['completion_percentage' => 0, 'enrollment_date' => date('Y-m-d H:i:s'), 'last_accessed' => date('Y-m-d H:i:s')];
        }
    }

    // Get story content with progress tracking
    function getStoryContent($conn, $storyID, $studentID) {
        // For now, treating story as chapter content since we don't have separate stories table
        $chapterID = intval($storyID / 100); // Extract chapter ID from mock story ID
        return getChapterContent($conn, $chapterID, $studentID, 'story');
    }

    // Get chapter content with progress tracking
    function getChapterContent($conn, $chapterID, $studentID, $type = 'chapter') {
        try {
            $stmt = $conn->prepare("
                SELECT 
                    pc.chapter_id as id,
                    pc.title,
                    pc.content,
                    pc.video_url,
                    pc.question,
                    pc.question_type,
                    pc.answer_options,
                    pc.correct_answer
                FROM program_chapters pc 
                WHERE pc.chapter_id = ?
            ");
            $stmt->bind_param("i", $chapterID);
            $stmt->execute();
            $content = $stmt->get_result()->fetch_assoc();
            
            if ($content) {
                $content['type'] = $type;
                $content['video_watched'] = true; // TODO: Implement video tracking
                $content['interactive_completed'] = false; // TODO: Implement interactive tracking
                $content['can_proceed'] = true; // TODO: Implement security checks
                $content['next_content'] = null; // TODO: Implement next content logic
            }
            
            return $content;
        } catch (Exception $e) {
            error_log('Error fetching chapter content: ' . $e->getMessage());
            return null;
        }
    }

    // Get first available content for a program
    function getFirstAvailableContent($conn, $programID, $studentID) {
        try {
            $stmt = $conn->prepare("
                SELECT chapter_id 
                FROM program_chapters 
                WHERE programID = ? 
                ORDER BY chapter_order ASC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $programID);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result) {
                return getChapterContent($conn, $result['chapter_id'], $studentID);
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Error fetching first available content: ' . $e->getMessage());
            return null;
        }
    }

    // Mark video as watched
    function markVideoWatched($conn, $studentID, $contentID, $contentType) {
        try {
            // TODO: Implement video watching tracking in database
            // For now, just return success
            return true;
        } catch (Exception $e) {
            error_log('Error marking video watched: ' . $e->getMessage());
            return false;
        }
    }

    // Submit interactive answers
    function submitInteractiveAnswers($conn, $studentID, $contentID, $answers) {
        try {
            // Get the correct answer
            $stmt = $conn->prepare("SELECT correct_answer, question_type FROM program_chapters WHERE chapter_id = ?");
            $stmt->bind_param("i", $contentID);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!$result) {
                return ['correct' => false, 'message' => 'Content not found.'];
            }
            
            $correctAnswer = $result['correct_answer'];
            $questionType = $result['question_type'];
            $submittedAnswer = $answers['answer'] ?? '';
            
            $isCorrect = false;
            
            switch ($questionType) {
                case 'multiple_choice':
                    $isCorrect = ($submittedAnswer == $correctAnswer);
                    break;
                case 'true_false':
                    $isCorrect = (strtolower($submittedAnswer) == strtolower($correctAnswer));
                    break;
                case 'short_answer':
                case 'essay':
                    // For text answers, do a simple comparison (can be enhanced)
                    $isCorrect = (strtolower(trim($submittedAnswer)) == strtolower(trim($correctAnswer)));
                    break;
            }
            
            // TODO: Save the attempt to database
            
            return [
                'correct' => $isCorrect,
                'message' => $isCorrect ? 'Great job!' : 'Please review the content and try again.'
            ];
        } catch (Exception $e) {
            error_log('Error submitting interactive answers: ' . $e->getMessage());
            return ['correct' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
?>