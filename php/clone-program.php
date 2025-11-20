<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'dbConnection.php';
require_once 'program-core.php'; // Make sure to include your core functions

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit();
}

$programId = intval($_POST['programId'] ?? 0);
$userId = intval($_SESSION['userID']);
if ($programId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid program.']); exit();
}

// New CLONE function, based on your core logic and relationship tree
function cloneProgram($conn, $originalProgramId, $userId) {
    // 1. Get original published program
    $prog = program_getById($conn, $originalProgramId);
    if (!$prog || strtolower($prog['status']) !== 'published') return false;

    $conn->begin_transaction();

    try {
        // 2. Insert new program as draft (reference original_program_id if you want lineage)
        $data = [
            'teacherID' => $userId,
            'title' => $prog['title'],
            'description' => $prog['description'],
            'difficultylabel' => $prog['difficulty_label'],
            'category' => $prog['category'],
            'price' => $prog['price'],
            'thumbnail' => $prog['thumbnail'],
            'status' => 'draft',
            'overviewvideourl' => $prog['overview_vide_ourl']
        ];
        // You can add 'original_program_id' if your schema supports it.
        $newProgramId = program_create($conn, $data);
        if (!$newProgramId) throw new Exception('Could not clone program metadata');

        // 3. Clone all chapters for this program
        $chapters = chapter_getByProgram($conn, $originalProgramId);
        $oldChapterToNew = [];
        foreach ($chapters as $chapter) {
            $chapterId = chapter_add($conn, $newProgramId, $chapter['title'], $chapter['content'], $chapter['question']);
            if (!$chapterId) throw new Exception('Failed to clone chapter');
            $oldChapterToNew[$chapter['chapter_id']] = $chapterId;

            // 4. Clone stories for this chapter
            $stories = chapter_getStories($conn, $chapter['chapter_id']);
            foreach ($stories as $story) {
                // Core: synopses, title, video, etc.
                $storyData = [
                    'chapterid' => $chapterId,
                    'title' => $story['title'],
                    'synopsisarabic' => $story['synopsisarabic'],
                    'synopsisenglish' => $story['synopsisenglish'],
                    'videourl' => $story['videourl']
                ];
                $storyId = story_create($conn, $storyData);
                if (!$storyId) throw new Exception('Failed to clone story');

                // 5. Clone interactive sections and their questions
                $sections = story_getInteractiveSections($conn, $story['storyid']);
                foreach ($sections as $section) {
                    // Insert interactive section
                    $sectionOrder = $section['sectionorder'];
                    $sectionStmt = $conn->prepare(
                        "INSERT INTO story_interactive_sections (storyid, title, sectionorder) VALUES (?, ?, ?)"
                    );
                    $sectionStmt->bind_param("isi", $storyId, $section['title'], $sectionOrder);
                    $sectionStmt->execute();
                    $newSectionId = $sectionStmt->insert_id;
                    $sectionStmt->close();

                    // Clone questions for this section
                    $questions = section_getQuestions($conn, $section['sectionid']);
                    foreach ($questions as $question) {
                        $q = $conn->prepare(
                            "INSERT INTO interactivequestions (sectionid, questionorder, questiontext, options, answer, explanation) VALUES (?, ?, ?, ?, ?, ?)"
                        );
                        $q->bind_param(
                            "iissss", $newSectionId, $question['questionorder'], $question['questiontext'],
                            $question['options'], $question['answer'], $question['explanation']
                        );
                        $q->execute();
                        $q->close();
                    }
                }
            }
        }

        $conn->commit();
        return $newProgramId;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Program clone error: " . $e->getMessage());
        return false;
    }
}

$newId = cloneProgram($conn, $programId, $userId);
if ($newId) {
    echo json_encode(['success' => true, 'newProgramId' => $newId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clone program and content.']);
}
?>