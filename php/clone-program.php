<?php
    session_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    require_once 'dbConnection.php';
    require_once 'program-core.php';

    if (!isset($_SESSION['userID'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']); 
        exit();
    }

    $programId = intval($_POST['programId'] ?? 0);
    $userId = intval($_SESSION['userID']);
    $isUpdate = isset($_POST['isUpdate']) && $_POST['isUpdate'] === 'true'; // ✅ NEW: Flag to indicate if this is an update

    $teacherId = getTeacherIdFromSession($conn, $userId);
    if (!$teacherId) {
        echo json_encode(['success' => false, 'message' => 'No teacher record found for this user.']);
        exit();
    }
    if ($programId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid program.']); 
        exit();
    }

    /**
     * Clone a program with all its content
     * @param bool $isUpdate - If true, marks the old version as outdated and links versions
     */
    function cloneProgram($conn, $originalProgramId, $teacherId, $isUpdate = false) {
        // 1. Get original published program
        $prog = program_getById($conn, $originalProgramId);
        error_log("CLONE DEBUG: program data = " . print_r($prog, true));
        if (!$prog || strtolower($prog['status']) !== 'published') return false;

        $conn->begin_transaction();

        try {
            // ✅ 2. Determine versioning info
            $parentId = null;
            $versionNumber = 1;
            $newStatus = 'draft';
            
            if ($isUpdate) {
                // This is an update - mark old version as outdated
                $parentId = $prog['parent_program_id'] ?? $originalProgramId;
                $versionNumber = ($prog['version_number'] ?? 1) + 1;
                $newStatus = 'draft'; // Teacher can review before publishing
                
                // Mark the original program as outdated
                $updateStmt = $conn->prepare("
                    UPDATE programs 
                    SET is_latest_version = 0, 
                        replaced_at = NOW() 
                    WHERE programID = ?
                ");
                $updateStmt->bind_param("i", $originalProgramId);
                $updateStmt->execute();
                $updateStmt->close();
            }

            // ✅ 3. Insert new program with version tracking
            $insertProgramStmt = $conn->prepare("
                INSERT INTO programs (
                    teacherID, title, description, difficulty_label, category, 
                    price, thumbnail, status, overview_video_url,
                    parent_program_id, version_number, is_latest_version, dateCreated
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $insertProgramStmt->bind_param(
                "issssdssiii",
                $teacherId,
                $prog['title'],
                $prog['description'],
                $prog['difficulty_label'],
                $prog['category'],
                $prog['price'],
                $prog['thumbnail'],
                $newStatus,
                $prog['overview_video_url'],
                $parentId,
                $versionNumber
            );
            
            if (!$insertProgramStmt->execute()) {
                throw new Exception('Could not clone program metadata: ' . $insertProgramStmt->error);
            }
            
            $newProgramId = $insertProgramStmt->insert_id;
            $insertProgramStmt->close();

            // 4. Clone all chapters for this program
            $chapters = chapter_getByProgram($conn, $originalProgramId);
            $oldChapterToNew = [];
            
            foreach ($chapters as $chapter) {
                $chapterId = chapter_add($conn, $newProgramId, $chapter['title'], $chapter['content'], $chapter['question']);
                if (!$chapterId) throw new Exception('Failed to clone chapter');
                $oldChapterToNew[$chapter['chapter_id']] = $chapterId;

                // 4.1 Clone CHAPTER QUIZ (if exists)
                $quiz = quiz_getByChapter($conn, $chapter['chapter_id']);
                if ($quiz) {
                    $quizTitle = $quiz['title'];
                    $quizMaxQuestions = isset($quiz['max_questions']) ? $quiz['max_questions'] : 30;
                    $quizDateCreated = date('Y-m-d H:i:s');

                    $quizInsert = $conn->prepare(
                        "INSERT INTO chapter_quizzes (chapter_id, program_id, title, max_questions, dateCreated) 
                        VALUES (?, ?, ?, ?, ?)"
                    );
                    if (!$quizInsert) { 
                        throw new Exception('Failed to prepare quiz insert: ' . $conn->error); 
                    }
                    $quizInsert->bind_param("iisis", $chapterId, $newProgramId, $quizTitle, $quizMaxQuestions, $quizDateCreated);
                    if (!$quizInsert->execute()) { 
                        throw new Exception('Failed to insert quiz: ' . $quizInsert->error); 
                    }
                    $newQuizId = $quizInsert->insert_id;
                    $quizInsert->close();

                    // Clone each quiz question
                    $quizQuestions = quizQuestion_getByQuiz($conn, $quiz['quiz_id']);
                    if (!empty($quizQuestions)) {
                        foreach ($quizQuestions as $qq) {
                            $qqText = $qq['question_text'];
                            $qqOrder = isset($qq['question_order']) ? $qq['question_order'] : 1;

                            $quizQInsert = $conn->prepare(
                                "INSERT INTO quiz_questions (quiz_id, question_text, question_order) 
                                VALUES (?, ?, ?)"
                            );
                            if (!$quizQInsert) { 
                                throw new Exception('Failed to prepare quiz_question insert: ' . $conn->error); 
                            }
                            $quizQInsert->bind_param("isi", $newQuizId, $qqText, $qqOrder);
                            if (!$quizQInsert->execute()) { 
                                throw new Exception('Failed to insert quiz_question: ' . $quizQInsert->error); 
                            }
                            $newQuizQId = $quizQInsert->insert_id;
                            $quizQInsert->close();

                            // Clone options
                            if (!empty($qq['options'])) {
                                foreach ($qq['options'] as $opt) {
                                    $optText = $opt['option_text'];
                                    $isCorrect = isset($opt['is_correct']) ? $opt['is_correct'] : 0;
                                    $optOrder = isset($opt['option_order']) ? $opt['option_order'] : 1;

                                    $optInsert = $conn->prepare(
                                        "INSERT INTO quiz_question_options (quiz_question_id, option_text, is_correct, option_order) 
                                        VALUES (?, ?, ?, ?)"
                                    );
                                    if (!$optInsert) { 
                                        throw new Exception('Failed to prepare quiz_question_option insert: ' . $conn->error); 
                                    }
                                    $optInsert->bind_param("isii", $newQuizQId, $optText, $isCorrect, $optOrder);
                                    if (!$optInsert->execute()) { 
                                        throw new Exception('Failed to insert quiz_question_option: ' . $optInsert->error); 
                                    }
                                    $optInsert->close();
                                }
                            }
                        }
                    }
                }

                // 5. Clone stories for this chapter
                $stories = chapter_getStories($conn, $chapter['chapter_id']);
                foreach ($stories as $story) {
                    $storyData = [
                        'chapter_id' => $chapterId,
                        'title' => $story['title'],
                        'synopsis_arabic' => $story['synopsis_arabic'],
                        'synopsis_english' => $story['synopsis_english'],
                        'video_url' => $story['video_url']
                    ];
                    $storyId = story_create($conn, $storyData);
                    if (!$storyId) throw new Exception('Failed to clone story');

                    // 6. Clone interactive sections and their questions
                    $sections = story_getInteractiveSections($conn, $story['story_id']);
                    foreach ($sections as $section) {
                        $sectionOrder = $section['section_order'];
                        $sectionStmt = $conn->prepare(
                            "INSERT INTO story_interactive_sections (story_id, section_order) 
                            VALUES (?, ?)"
                        );
                        $sectionStmt->bind_param("ii", $storyId, $sectionOrder);
                        $sectionStmt->execute();
                        $newSectionId = $sectionStmt->insert_id;
                        $sectionStmt->close();

                        // Clone questions for this section
                        $questions = section_getQuestions($conn, $section['section_id']);
                        foreach ($questions as $question) {
                            $qStmt = $conn->prepare(
                                "INSERT INTO interactive_questions (section_id, question_text, question_type, question_order) 
                                VALUES (?, ?, ?, ?)"
                            );
                            $qStmt->bind_param(
                                "issi",
                                $newSectionId,
                                $question['question_text'],
                                $question['question_type'],
                                $question['question_order']
                            );
                            $qStmt->execute();
                            $newQuestionId = $qStmt->insert_id;
                            $qStmt->close();

                            // Clone options for this question
                            $options = questionOption_getByQuestion($conn, $question['question_id']);
                            foreach ($options as $opt) {
                                $optStmt = $conn->prepare(
                                    "INSERT INTO question_options (question_id, option_order, option_text, is_correct) 
                                    VALUES (?, ?, ?, ?)"
                                );
                                $optStmt->bind_param(
                                    "iisi",
                                    $newQuestionId,
                                    $opt['option_order'],
                                    $opt['option_text'],
                                    $opt['is_correct']
                                );
                                $optStmt->execute();
                                $optStmt->close();
                            }
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

    // ✅ Execute the clone with update flag
    $newId = cloneProgram($conn, $programId, $teacherId, $isUpdate);

    if ($newId) {
        $message = $isUpdate ? 'Program updated successfully. Review and publish when ready.' : 'Program cloned successfully.';
        echo json_encode([
            'success' => true, 
            'newProgramId' => $newId,
            'message' => $message,
            'isUpdate' => $isUpdate
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clone program and content.']);
    }
?>