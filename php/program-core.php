<?php
/**
 * PROGRAM CORE (Complete Unified)
 * All program functions, handlers, and HTTP endpoints in one file
 * With explicit status enforcement and diagnostic logging
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/quiz-handler.php'; // ADDED: for interactiveSection_getByStory

// ... (functions above unchanged)
// ... (core CRUD, admin actions unchanged)

// HTTP Handler for all POST/GET endpoints - only run when directly accessed as program-core.php
if (basename($_SERVER['PHP_SELF']) === 'program-core.php') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // ... (existing access control logic and checks unchanged)

    try {
        switch ($action) {
            // ... (all other cases unchanged)
            case 'create_story':
                $program_id = intval($_POST['programID'] ?? 0); $chapter_id = intval($_POST['chapter_id'] ?? 0);
                $title = trim($_POST['title'] ?? ''); $synopsis_arabic = trim($_POST['synopsis_arabic'] ?? ''); 
                $synopsis_english = trim($_POST['synopsis_english'] ?? ''); $video_url = trim($_POST['video_url'] ?? '');
                if (empty($title) || empty($synopsis_arabic) || empty($synopsis_english) || empty($video_url)) { 
                    $_SESSION['error_message']='All fields are required for the story.'; 
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit; 
                }
                if (!$program_id) { $_SESSION['error_message']='Program ID is required.'; header('Location: ../pages/teacher/teacher-programs.php'); exit; }
                $existingStories = chapter_getStories($conn, $chapter_id); 
                if (count($existingStories) >= 3) { 
                    $_SESSION['error_message']='Maximum of 3 stories per chapter allowed.'; 
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit; 
                }
                $story_id = story_create($conn, ['chapter_id'=>$chapter_id,'title'=>$title,'synopsis_arabic'=>$synopsis_arabic,'synopsis_english'=>$synopsis_english,'video_url'=>$video_url]);
                // Enforce at least 1 and at most 3 interactive sections per story
                $interactiveSections = interactiveSection_getByStory($conn, $story_id);
                if (count($interactiveSections) < 1 || count($interactiveSections) > 3) {
                    $_SESSION['error_message'] = 'Each story must have at least 1 and no more than 3 interactive sections.';
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . ($story_id ? ('&story_id=' . $story_id) : ''));
                    exit;
                }
                if ($story_id) { $_SESSION['success_message']='Story created successfully!'; header('Location: ../pages/teacher/teacher-programs.php?action=edit_chapter&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit; }
                $_SESSION['error_message']='Failed to save story. Please try again.'; header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit;
            case 'update_story':
                $program_id = intval($_POST['programID'] ?? 0); $chapter_id = intval($_POST['chapter_id'] ?? 0); $story_id = intval($_POST['story_id'] ?? 0);
                $title = trim($_POST['title'] ?? ''); $synopsis_arabic = trim($_POST['synopsis_arabic'] ?? ''); $synopsis_english = trim($_POST['synopsis_english'] ?? ''); $video_url = trim($_POST['video_url'] ?? '');
                if (!$story_id || empty($title) || empty($synopsis_arabic) || empty($synopsis_english) || empty($video_url)) { $_SESSION['error_message']='All fields are required for the story update.'; header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id); exit; }
                $data = ['title'=>$title,'synopsis_arabic'=>$synopsis_arabic,'synopsis_english'=>$synopsis_english,'video_url'=>$video_url];
                // Enforce interactive section rules
                $interactiveSections = interactiveSection_getByStory($conn, $story_id);
                if (count($interactiveSections) < 1 || count($interactiveSections) > 3) {
                    $_SESSION['error_message'] = 'Each story must have at least 1 and no more than 3 interactive sections.';
                    header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id);
                    exit;
                }
                if (story_update($conn, $story_id, $data)) { $_SESSION['success_message']='Story updated successfully!'; header('Location: ../pages/teacher/teacher-programs.php?action=edit_chapter&program_id=' . $program_id . '&chapter_id=' . $chapter_id); exit; }
                $_SESSION['error_message']='Failed to update story. Please try again.'; header('Location: ../pages/teacher/teacher-programs.php?action=add_story&program_id=' . $program_id . '&chapter_id=' . $chapter_id . '&story_id=' . $story_id); exit;
            // ... (all other cases unchanged)
            default:
                if (in_array($action, ['create_program','update_program','create_story','update_story','delete_program','delete_chapter','delete_story','archive_program','approve_program','reject_program','bulk_approve_programs'])) { 
                    $_SESSION['error_message']='Invalid action: ' . $action; 
                    header('Location: ../pages/teacher/teacher-programs.php'); exit; 
                }
                header('Content-Type: application/json'); 
                echo json_encode(['success'=>false,'message'=>'Invalid action: ' . $action]); exit;
        }
    } catch (Exception $e) {
        error_log("Program Core Handler Error: " . $e->getMessage());
        if (in_array($action, ['create_program','update_program','create_story','update_story','delete_program'])) { 
            $_SESSION['error_message']='Server error: ' . $e->getMessage(); 
            header('Location: ../pages/teacher/teacher-programs.php'); exit; 
        }
        header('Content-Type: application/json'); http_response_code(500); 
        echo json_encode(['success'=>false,'message'=>'Server error: ' . $e->getMessage()]); exit;
    }
}
// (End of file; all prior functions unchanged)
