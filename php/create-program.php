<?php
// create-program.php
require '../php/dbConnection.php';
require '../php/functions.php';
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../pages/login.php");
    exit();
}

$teacher_id = $_SESSION['userID'];

// Initialize temp chapters if not set
if (!isset($_SESSION['temp_chapters'])) {
    $_SESSION['temp_chapters'] = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new program
    if (isset($_POST['create_program'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $video_link = $_POST['video_link'];
        $price = $_POST['price'];
        $status = $_POST['status'];

        // Process thumbnail upload
        $image = 'default-thumbnail.jpg';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/program_thumbnails/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png'];
            if (in_array($file_ext, $allowed_types)) {
                $filename = uniqid() . '.' . $file_ext;
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $destination)) {
                    $image = $filename;
                }
            }
        }

        $program_id = createProgram($conn, $teacher_id, $title, $description, $category, $video_link, $price, $image, $status);
        if ($program_id) {
            // Add all temp chapters
            foreach ($_SESSION['temp_chapters'] as $chapter) {
                addChapter($conn, $program_id, $chapter['title'], $chapter['content'], $chapter['question']);
            }
            unset($_SESSION['temp_chapters']); // Clear temp chapters
            $_SESSION['success_message'] = "Program and chapters created successfully!";
            header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
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
        $title = $_POST['title'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $video_link = $_POST['video_link'];
        $price = $_POST['price'];
        $status = $_POST['status'];

        // Verify ownership
        if (!verifyProgramOwnership($conn, $program_id, $teacher_id)) {
            $_SESSION['error_message'] = "You don't have permission to edit this program.";
            header("Location: ../pages/teacher/teacher-programs.php?action=create&program_id=" . $program_id);
            exit();
        }

        $updated = updateProgram($conn, $program_id, $teacher_id, $title, $description, $category, $video_link, $price, $status);
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

// Handle back button
if (isset($_GET['back'])) {
    header("Location: ../pages/teacher/teacher-programs.php");
    exit();
}
?>
