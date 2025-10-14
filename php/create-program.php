<?php
// Enhanced create-program.php - Fixed version without duplicate function issues
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/program-helpers.php'; // This now contains all program-related functions
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    if (($_GET['flow'] ?? '') === 'redirect') {
        header("Location: ../pages/login.php");
        exit();
    }
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

$user_id = $_SESSION['userID'];

// Get teacher ID using the consolidated function
$teacher_id = getTeacherIdFromSession($conn, $user_id);

if (!$teacher_id) {
    if (($_GET['flow'] ?? '') === 'redirect') {
        $_SESSION['error_message'] = 'Teacher profile could not be created or found. Please contact administrator.';
        header("Location: ../pages/teacher/teacher-programs.php");
        exit();
    }
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Teacher profile not found or could not be created']);
    exit();
}

// Initialize temp chapters if not set
if (!isset($_SESSION['temp_chapters'])) {
    $_SESSION['temp_chapters'] = [];
}

// Handle redirect flow for Quick Access button
if (($_GET['flow'] ?? '') === 'redirect') {
    // Create simple draft program and redirect to builder
    $data = [
        'teacherID' => $teacher_id,
        'title' => 'New Program',
        'description' => 'Program description will be added here.',
        'category' => 'beginner',
        'price' => 0.00,
        'status' => 'draft',
        'thumbnail' => 'default-thumbnail.jpg'
    ];
    
    $program_id = createProgram($conn, $data);
    if ($program_id) {
        header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
        exit();
    } else {
        $_SESSION['error_message'] = 'Failed to create program. Please try again.';
        header("Location: ../pages/teacher/teacher-programs.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Create new program
    if (isset($_POST['create_program'])) {
        $data = [
            'teacherID' => $teacher_id,
            'title' => trim($_POST['title'] ?? 'New Program'),
            'description' => trim($_POST['description'] ?? ''),
            'category' => $_POST['category'] ?? 'beginner',
            'price' => floatval($_POST['price'] ?? 0.00),
            'status' => $_POST['status'] ?? 'draft',
            'thumbnail' => 'default-thumbnail.jpg'
        ];

        // Validate inputs
        if (empty($data['title']) || strlen($data['title']) < 3) {
            $_SESSION['error_message'] = "Program title must be at least 3 characters long.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create");
            exit();
        }

        if (empty($data['description']) || strlen($data['description']) < 10) {
            $_SESSION['error_message'] = "Program description must be at least 10 characters long.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create");
            exit();
        }

        // Process thumbnail upload
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploaded_thumbnail = uploadThumbnail($_FILES['thumbnail']);
            if ($uploaded_thumbnail) {
                $data['thumbnail'] = $uploaded_thumbnail;
            }
        }

        $program_id = createProgram($conn, $data);
        if ($program_id) {
            // Add all temp chapters using consolidated function
            foreach ($_SESSION['temp_chapters'] as $chapter) {
                addChapter($conn, $program_id, $chapter['title'], $chapter['content'], $chapter['question']);
            }
            unset($_SESSION['temp_chapters']); // Clear temp chapters
            $_SESSION['success_message'] = "Program created successfully! You can now add chapters and content.";
            
            header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            exit();
        } else {
            $_SESSION['error_message'] = "Error creating program. Please check your input and try again.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create");
            exit();
        }
    }
    
    // Update existing program
    elseif (isset($_POST['update_program'])) {
        $program_id = intval($_POST['program_id'] ?? 0);
        
        if (!$program_id) {
            $_SESSION['error_message'] = "Invalid program ID.";
            header("Location: ../pages/teacher/teacher-programs.php");
            exit();
        }
        
        // Verify ownership
        if (!verifyProgramOwnership($conn, $program_id, $teacher_id)) {
            $_SESSION['error_message'] = "You don't have permission to edit this program.";
            header("Location: ../pages/teacher/teacher-programs.php");
            exit();
        }

        $data = [
            'teacherID' => $teacher_id,
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category' => $_POST['category'] ?? 'beginner',
            'price' => floatval($_POST['price'] ?? 0.00),
            'status' => $_POST['status'] ?? 'draft'
        ];

        // Validate inputs
        if (empty($data['title']) || strlen($data['title']) < 3) {
            $_SESSION['error_message'] = "Program title must be at least 3 characters long.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            exit();
        }

        if (empty($data['description']) || strlen($data['description']) < 10) {
            $_SESSION['error_message'] = "Program description must be at least 10 characters long.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            exit();
        }

        $updated = updateProgram($conn, $program_id, $data);
        if ($updated) {
            $_SESSION['success_message'] = "Program updated successfully!";
        } else {
            $_SESSION['error_message'] = "No changes made or error updating program.";
        }
        
        header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
        exit();
    }
    
    // Add chapter
    elseif (isset($_POST['add_chapter'])) {
        $program_id = intval($_POST['program_id'] ?? 0);
        $chapter_title = trim($_POST['chapter_title'] ?? 'New Chapter');
        $chapter_content = trim($_POST['chapter_content'] ?? '');
        $chapter_question = trim($_POST['chapter_question'] ?? '');

        header('Content-Type: application/json');
        
        if ($program_id && verifyProgramOwnership($conn, $program_id, $teacher_id)) {
            $chapter_id = addChapter($conn, $program_id, $chapter_title, $chapter_content, $chapter_question);
            if ($chapter_id) {
                $chapters = getChapters($conn, $program_id);
                echo json_encode([
                    'success' => true,
                    'message' => "Chapter added successfully!",
                    'chapter_id' => $chapter_id,
                    'chapters' => $chapters
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Error adding chapter. Please try again."
                ]);
            }
        } else {
            // Store in temp chapters
            if (empty($chapter_title) || strlen($chapter_title) < 3) {
                echo json_encode([
                    'success' => false,
                    'message' => "Chapter title must be at least 3 characters long."
                ]);
                exit();
            }

            $chapter = [
                'title' => $chapter_title,
                'content' => $chapter_content,
                'question' => $chapter_question,
            ];
            $_SESSION['temp_chapters'][] = $chapter;
            echo json_encode([
                'success' => true,
                'message' => "Chapter added!",
                'chapters' => $_SESSION['temp_chapters']
            ]);
        }
        exit();
    }
    
    // Update chapter
    elseif (isset($_POST['update_chapter'])) {
        $chapter_id = intval($_POST['chapter_id'] ?? 0);
        $program_id = intval($_POST['program_id'] ?? 0);
        $chapter_title = trim($_POST['chapter_title'] ?? '');
        $chapter_content = trim($_POST['chapter_content'] ?? '');
        $chapter_question = trim($_POST['chapter_question'] ?? '');

        header('Content-Type: application/json');
        
        if (!verifyProgramOwnership($conn, $program_id, $teacher_id)) {
            echo json_encode(['success' => false, 'message' => "You don't have permission to edit chapters in this program."]);
            exit();
        }

        if (empty($chapter_title) || strlen($chapter_title) < 3) {
            echo json_encode(['success' => false, 'message' => "Chapter title must be at least 3 characters long."]);
            exit();
        }

        $updated = updateChapter($conn, $chapter_id, $chapter_title, $chapter_content, $chapter_question);
        if ($updated) {
            $chapters = getChapters($conn, $program_id);
            echo json_encode(['success' => true, 'message' => "Chapter updated successfully!", 'chapters' => $chapters]);
        } else {
            echo json_encode(['success' => false, 'message' => "No changes made or error updating chapter."]);
        }
        exit();
    }
    
    // Delete chapter
    elseif (isset($_POST['delete_chapter'])) {
        $chapter_index = $_POST['delete_chapter'];
        $program_id = intval($_POST['program_id'] ?? 0);

        header('Content-Type: application/json');
        
        if ($program_id && verifyProgramOwnership($conn, $program_id, $teacher_id)) {
            $deleted = deleteChapter($conn, $chapter_index);
            if ($deleted) {
                $chapters = getChapters($conn, $program_id);
                echo json_encode(['success' => true, 'message' => "Chapter deleted successfully!", 'chapters' => $chapters]);
            } else {
                echo json_encode(['success' => false, 'message' => "Error deleting chapter."]);
            }
        } else {
            // Remove from temp chapters
            if (isset($_SESSION['temp_chapters'][$chapter_index])) {
                array_splice($_SESSION['temp_chapters'], $chapter_index, 1);
                echo json_encode(['success' => true, 'message' => "Chapter deleted successfully!", 'chapters' => $_SESSION['temp_chapters']]);
            } else {
                echo json_encode(['success' => false, 'message' => "Error deleting chapter."]);
            }
        }
        exit();
    }
}

// Handle AJAX request to create simple program (for New Program button)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'create_simple') {
    header('Content-Type: application/json');
    
    try {
        $data = [
            'teacherID' => $teacher_id,
            'title' => 'New Program',
            'description' => 'Program description will be added here.',
            'category' => 'beginner',
            'price' => 0.00,
            'status' => 'draft',
            'thumbnail' => 'default-thumbnail.jpg'
        ];
        
        $program_id = createProgram($conn, $data);
        if ($program_id) {
            echo json_encode([
                'success' => true,
                'program_id' => $program_id,
                'message' => 'Program created successfully'
            ]);
        } else {
            throw new Exception('Failed to create program');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// Handle back button
if (isset($_GET['back'])) {
    header("Location: ../pages/teacher/teacher-programs.php");
    exit();
}
?>