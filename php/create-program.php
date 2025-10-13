<?php
// Enhanced create-program.php - Compatible with existing schema
require '../php/dbConnection.php';
require '../php/functions.php';
require '../php/enhanced-program-functions.php';
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    // Handle AJAX requests differently
    if (isset($_POST['ajax_create']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authorized. Please log in as a teacher.']);
        exit();
    } else {
        header("Location: ../pages/login.php");
        exit();
    }
}

$user_id = $_SESSION['userID'];

// Get teacher ID from user ID
$teacher_id = getTeacherIdFromSession($conn, $user_id);
if (!$teacher_id) {
    $_SESSION['error_message'] = 'Teacher profile not found.';
    header("Location: ../pages/teacher/teacher-programs.php");
    exit();
}

// Initialize temp chapters if not set
if (!isset($_SESSION['temp_chapters'])) {
    $_SESSION['temp_chapters'] = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new program
    if (isset($_POST['create_program'])) {
        $data = [
            'teacherID' => $teacher_id,
            'title' => $_POST['title'] ?? 'New Program',
            'description' => $_POST['description'] ?? '',
            'category' => $_POST['category'] ?? 'beginner',
            'difficulty_level' => $_POST['difficulty_level'] ?? 'Student',
            'price' => floatval($_POST['price'] ?? 500.00),
            'overview_video_url' => $_POST['overview_video_url'] ?? $_POST['video_link'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
            'currency' => 'PHP',
            'thumbnail' => 'default-thumbnail.jpg'
        ];

        // Process thumbnail upload
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploaded_thumbnail = uploadThumbnail($_FILES['thumbnail']);
            if ($uploaded_thumbnail) {
                $data['thumbnail'] = $uploaded_thumbnail;
            }
        }

        $program_id = createProgram($conn, $data);
        if ($program_id) {
            // Add all temp chapters using existing function
            foreach ($_SESSION['temp_chapters'] as $chapter) {
                addChapter($conn, $program_id, $chapter['title'], $chapter['content'], $chapter['question']);
            }
            unset($_SESSION['temp_chapters']); // Clear temp chapters
            $_SESSION['success_message'] = "Program and chapters created successfully!";
            
            // Redirect to enhanced programs page if it exists, otherwise use existing page
            if (file_exists('../pages/teacher/teacher-programs.php')) {
                header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            } else {
                header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            }
            exit();
        } else {
            $_SESSION['error_message'] = "Error creating program.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create");
            exit();
        }
    }
    // Update existing program
    elseif (isset($_POST['update_program'])) {
        $program_id = $_POST['program_id'];
        
        // Verify ownership
        if (!verifyProgramOwnership($conn, $program_id, $teacher_id)) {
            $_SESSION['error_message'] = "You don't have permission to edit this program.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            exit();
        }

        $data = [
            'teacherID' => $teacher_id,
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'category' => $_POST['category'],
            'difficulty_level' => $_POST['difficulty_level'] ?? mapCategoryToDifficulty($_POST['category']),
            'price' => floatval($_POST['price']),
            'overview_video_url' => $_POST['overview_video_url'] ?? $_POST['video_link'] ?? '',
            'status' => $_POST['status']
        ];

        $updated = updateProgram($conn, $program_id, $data);
        if ($updated) {
            $_SESSION['success_message'] = "Program updated successfully!";
            header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            exit();
        } else {
            $_SESSION['error_message'] = "No changes made or error updating program.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            exit();
        }
    }
    // Add chapter
    elseif (isset($_POST['add_chapter'])) {
        $program_id = $_POST['program_id'];
        $chapter_title = $_POST['chapter_title'];
        $chapter_content = $_POST['chapter_content'];
        $chapter_question = $_POST['chapter_question'];

        if ($program_id && verifyProgramOwnership($conn, $program_id, $teacher_id)) {
            $chapter_id = addChapter($conn, $program_id, $chapter_title, $chapter_content, $chapter_question);
            if ($chapter_id) {
                $chapters = getChapters($conn, $program_id);
                echo json_encode([
                    'success' => true,
                    'message' => "Chapter added successfully!",
                    'chapters' => $chapters
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Error adding chapter."
                ]);
            }
        } else {
            // Store in temp chapters
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
        $chapter_id = $_POST['chapter_id'];
        $program_id = $_POST['program_id'];
        $chapter_title = $_POST['chapter_title'];
        $chapter_content = $_POST['chapter_content'];
        $chapter_question = $_POST['chapter_question'];

        if (!verifyProgramOwnership($conn, $program_id, $teacher_id)) {
            echo json_encode(['success' => false, 'message' => "You don't have permission to edit chapters in this program."]);
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
        $program_id = $_POST['program_id'];

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
            'description' => 'Program description',
            'category' => 'beginner',
            'difficulty_level' => 'Student',
            'price' => 500.00,
            'overview_video_url' => '',
            'status' => 'draft',
            'currency' => 'PHP',
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

/**
 * Helper function to map category to difficulty level
 */
function mapCategoryToDifficulty($category) {
    switch ($category) {
        case 'beginner':
            return 'Student';
        case 'intermediate':
            return 'Aspiring';
        case 'advanced':
            return 'Master';
        default:
            return 'Student';
    }
}
?>