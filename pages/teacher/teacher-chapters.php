<?php
    session_start();
    $current_page = "teacher-chapters";
    $page_title = "Manage Chapters";
    
    // Check if user is logged in and is a teacher
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
        header("Location: ../login.php");
        exit();
    }
    
    // Include required files
    require '../../php/dbConnection.php';
    require '../../php/functions.php';
    require '../../php/gamification.php';
    
    $teacher_id = $_SESSION['userID'];
    $program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
    
    if (!$program_id) {
        header("Location: teacher-programs.php");
        exit();
    }
    
    // Verify ownership
    if (!verifyProgramOwnership($conn, $program_id, $teacher_id)) {
        $_SESSION['error_message'] = "You don't have permission to manage chapters for this program.";
        header("Location: teacher-programs.php");
        exit();
    }
    
    // Get program and chapters
    $program = getProgram($conn, $program_id, $teacher_id);
    if (!$program) {
        header("Location: teacher-programs.php");
        exit();
    }
    
    $chapters = getChapters($conn, $program_id);
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_chapter'])) {
            $title = trim($_POST['chapter_title']);
            $content = trim($_POST['chapter_content']);
            $video_url = trim($_POST['video_url'] ?? '');
            $audio_url = trim($_POST['audio_url'] ?? '');
            $question = trim($_POST['chapter_question']);
            $question_type = $_POST['question_type'] ?? 'multiple_choice';
            $correct_answer = trim($_POST['correct_answer'] ?? '');
            $answer_options = [];
            
            // Handle multiple choice options
            if ($question_type === 'multiple_choice') {
                for ($i = 1; $i <= 4; $i++) {
                    $option = trim($_POST["option_$i"] ?? '');
                    if (!empty($option)) {
                        $answer_options[] = $option;
                    }
                }
                $answer_options = json_encode($answer_options);
            } else {
                $answer_options = null;
            }
            
            $points_reward = (int)($_POST['points_reward'] ?? 50);
            
            if ($title && $content && $question) {
                $stmt = $conn->prepare("
                    INSERT INTO program_chapters (program_id, title, content, video_url, audio_url, question, question_type, correct_answer, answer_options, points_reward, chapter_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                        (SELECT COALESCE(MAX(chapter_order), 0) + 1 FROM program_chapters pc WHERE pc.program_id = ?)
                    )
                ");
                $stmt->bind_param("isssssssiii", $program_id, $title, $content, $video_url, $audio_url, $question, $question_type, $correct_answer, $answer_options, $points_reward, $program_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Chapter added successfully!";
                } else {
                    $_SESSION['error_message'] = "Error adding chapter: " . $conn->error;
                }
                
                header("Location: teacher-chapters.php?program_id=$program_id");
                exit();
            } else {
                $_SESSION['error_message'] = "Please fill in all required fields.";
            }
        }
        
        if (isset($_POST['update_chapter'])) {
            $chapter_id = (int)$_POST['chapter_id'];
            $title = trim($_POST['chapter_title']);
            $content = trim($_POST['chapter_content']);
            $video_url = trim($_POST['video_url'] ?? '');
            $audio_url = trim($_POST['audio_url'] ?? '');
            $question = trim($_POST['chapter_question']);
            $question_type = $_POST['question_type'] ?? 'multiple_choice';
            $correct_answer = trim($_POST['correct_answer'] ?? '');
            $answer_options = [];
            
            // Handle multiple choice options
            if ($question_type === 'multiple_choice') {
                for ($i = 1; $i <= 4; $i++) {
                    $option = trim($_POST["option_$i"] ?? '');
                    if (!empty($option)) {
                        $answer_options[] = $option;
                    }
                }
                $answer_options = json_encode($answer_options);
            } else {
                $answer_options = null;
            }
            
            $points_reward = (int)($_POST['points_reward'] ?? 50);
            
            if ($title && $content && $question) {
                $stmt = $conn->prepare("
                    UPDATE program_chapters 
                    SET title = ?, content = ?, video_url = ?, audio_url = ?, question = ?, question_type = ?, correct_answer = ?, answer_options = ?, points_reward = ?
                    WHERE chapter_id = ? AND program_id = ?
                ");
                $stmt->bind_param("ssssssssiil", $title, $content, $video_url, $audio_url, $question, $question_type, $correct_answer, $answer_options, $points_reward, $chapter_id, $program_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Chapter updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Error updating chapter: " . $conn->error;
                }
                
                header("Location: teacher-chapters.php?program_id=$program_id");
                exit();
            } else {
                $_SESSION['error_message'] = "Please fill in all required fields.";
            }
        }
        
        if (isset($_POST['reorder_chapters'])) {
            $chapter_orders = $_POST['chapter_orders'] ?? [];
            
            foreach ($chapter_orders as $chapter_id => $order) {
                $stmt = $conn->prepare("UPDATE program_chapters SET chapter_order = ? WHERE chapter_id = ? AND program_id = ?");
                $stmt->bind_param("iii", $order, $chapter_id, $program_id);
                $stmt->execute();
            }
            
            $_SESSION['success_message'] = "Chapter order updated successfully!";
            header("Location: teacher-chapters.php?program_id=$program_id");
            exit();
        }
    }
    
    // Handle delete chapter
    if (isset($_GET['delete_chapter'])) {
        $chapter_id = (int)$_GET['delete_chapter'];
        
        $stmt = $conn->prepare("DELETE FROM program_chapters WHERE chapter_id = ? AND program_id = ?");
        $stmt->bind_param("ii", $chapter_id, $program_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Chapter deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting chapter.";
        }
        
        header("Location: teacher-chapters.php?program_id=$program_id");
        exit();
    }
    
    // Refresh chapters list after operations
    $chapters = getChapters($conn, $program_id);
    
    // Handle success/error messages
    $success = $_SESSION['success_message'] ?? null;
    $error = $_SESSION['error_message'] ?? null;
    unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>

<!-- Enhanced Chapter Management -->
<div class="page-container p-4 md:p-6">
    <div class="page-content max-w-7xl mx-auto">
        <section class="content-section mb-8">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
                <div>
                    <h1 class="section-title text-2xl lg:text-3xl font-bold text-[#10375B]">
                        Manage Chapters
                    </h1>
                    <p class="text-lg text-gray-600 mt-2">Program: <strong><?= htmlspecialchars($program['title']) ?></strong></p>
                    <div class="flex items-center gap-4 mt-2">
                        <span class="bg-[#10375B] text-white px-3 py-1 rounded-full text-sm capitalize"><?= $program['category'] ?></span>
                        <span class="text-sm text-gray-600"><?= count($chapters) ?> chapters</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="openAddChapterModal()"
                            class="bg-[#A58618] hover:bg-[#8a6f15] text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="fas fa-plus"></i>
                        <span>Add Chapter</span>
                    </button>
                    <a href="teacher-programs.php"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Programs</span>
                    </a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-100 text-green-800 rounded-lg border border-green-200 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-100 text-red-800 rounded-lg border border-red-200 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Chapters List -->
            <?php if (count($chapters) > 0): ?>
                <div class="bg-white rounded-xl shadow-lg border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-800">Chapter List</h2>
                            <button type="button" onclick="toggleReorderMode()" id="reorder-btn"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                <i class="fas fa-sort"></i> Reorder
                            </button>
                        </div>
                    </div>
                    
                    <form method="POST" id="reorder-form" style="display: none;">
                        <input type="hidden" name="reorder_chapters" value="1">
                        <div class="p-4 bg-blue-50 border-b border-blue-200">
                            <div class="flex justify-between items-center">
                                <p class="text-blue-800"><i class="fas fa-info-circle mr-2"></i>Drag and drop to reorder chapters</p>
                                <div class="flex gap-2">
                                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                        <i class="fas fa-save"></i> Save Order
                                    </button>
                                    <button type="button" onclick="toggleReorderMode()" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <div class="divide-y divide-gray-200" id="chapters-list">
                        <?php foreach ($chapters as $index => $chapter): ?>
                            <div class="chapter-item p-6 hover:bg-gray-50 transition-colors" data-chapter-id="<?= $chapter['chapter_id'] ?>">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="drag-handle hidden cursor-move text-gray-400 hover:text-gray-600">
                                                <i class="fas fa-grip-vertical"></i>
                                            </div>
                                            <span class="bg-[#10375B] text-white px-3 py-1 rounded-full text-sm font-medium">
                                                Chapter <?= $chapter['chapter_order'] ?>
                                            </span>
                                            <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($chapter['title']) ?></h3>
                                            <input type="hidden" name="chapter_orders[<?= $chapter['chapter_id'] ?>]" value="<?= $chapter['chapter_order'] ?>">
                                        </div>
                                        
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            <!-- Content Preview -->
                                            <div>
                                                <h4 class="font-medium text-gray-700 mb-2">Story Content:</h4>
                                                <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded border max-h-20 overflow-y-auto">
                                                    <?= !empty($chapter['content']) ? nl2br(htmlspecialchars(substr($chapter['content'], 0, 200))) . (strlen($chapter['content']) > 200 ? '...' : '') : '<em class="text-gray-400">No content</em>' ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Question Preview -->
                                            <div>
                                                <h4 class="font-medium text-gray-700 mb-2">Question:</h4>
                                                <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded border max-h-20 overflow-y-auto">
                                                    <?= !empty($chapter['question']) ? htmlspecialchars(substr($chapter['question'], 0, 150)) . (strlen($chapter['question']) > 150 ? '...' : '') : '<em class="text-gray-400">No question</em>' ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Additional Info -->
                                        <div class="flex items-center gap-6 mt-4 text-sm text-gray-600">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-question-circle"></i>
                                                <span class="capitalize"><?= $chapter['question_type'] ?></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-trophy"></i>
                                                <span><?= $chapter['points_reward'] ?> points</span>
                                            </div>
                                            <?php if ($chapter['video_url']): ?>
                                                <div class="flex items-center gap-2 text-blue-600">
                                                    <i class="fas fa-video"></i>
                                                    <span>Video</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($chapter['audio_url']): ?>
                                                <div class="flex items-center gap-2 text-green-600">
                                                    <i class="fas fa-volume-up"></i>
                                                    <span>Audio</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex gap-2 ml-4">
                                        <button type="button" onclick="editChapter(<?= $chapter['chapter_id'] ?>)"
                                                class="text-blue-500 hover:text-blue-700 p-2 hover:bg-blue-50 rounded transition-colors"
                                                title="Edit Chapter">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" onclick="previewChapter(<?= $chapter['chapter_id'] ?>)"
                                                class="text-green-500 hover:text-green-700 p-2 hover:bg-green-50 rounded transition-colors"
                                                title="Preview Chapter">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" onclick="confirmDeleteChapter(<?= $chapter['chapter_id'] ?>)"
                                                class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded transition-colors"
                                                title="Delete Chapter">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-12 text-center">
                    <i class="fas fa-book-open text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Chapters Yet</h3>
                    <p class="text-gray-500 mb-6">Start building your program by adding the first chapter.</p>
                    <button type="button" onclick="openAddChapterModal()"
                            class="bg-[#A58618] hover:bg-[#8a6f15] text-white px-6 py-3 rounded-lg flex items-center gap-2 mx-auto transition-colors">
                        <i class="fas fa-plus"></i>
                        <span>Add First Chapter</span>
                    </button>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- Add/Edit Chapter Modal -->
<div id="chapterModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800" id="modal-title">Add New Chapter</h2>
                <button onclick="closeChapterModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <form method="POST" class="p-6 space-y-6" id="chapter-form">
            <input type="hidden" name="program_id" value="<?= $program_id ?>">
            <input type="hidden" name="chapter_id" id="chapter_id">
            <input type="hidden" name="add_chapter" value="1" id="form_action">
            
            <!-- Chapter Title -->
            <div>
                <label for="chapter_title" class="block text-gray-700 font-semibold mb-2">
                    Chapter Title <span class="text-red-500">*</span>
                </label>
                <input type="text" id="chapter_title" name="chapter_title" required
                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10375B] focus:border-transparent"
                       placeholder="Enter chapter title...">
            </div>
            
            <!-- Story Content -->
            <div>
                <label for="chapter_content" class="block text-gray-700 font-semibold mb-2">
                    Story Content <span class="text-red-500">*</span>
                </label>
                <textarea id="chapter_content" name="chapter_content" rows="8" required
                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10375B] focus:border-transparent"
                          placeholder="Write the chapter story content here..."></textarea>
                <p class="text-sm text-gray-600 mt-1">This is the main learning content that students will read.</p>
            </div>
            
            <!-- Multimedia URLs -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label for="video_url" class="block text-gray-700 font-semibold mb-2">
                        Video URL <span class="text-gray-500">(Optional)</span>
                    </label>
                    <input type="url" id="video_url" name="video_url"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10375B] focus:border-transparent"
                           placeholder="https://youtube.com/watch?v=...">
                </div>
                <div>
                    <label for="audio_url" class="block text-gray-700 font-semibold mb-2">
                        Audio URL <span class="text-gray-500">(Optional)</span>
                    </label>
                    <input type="url" id="audio_url" name="audio_url"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10375B] focus:border-transparent"
                           placeholder="https://example.com/audio.mp3">
                </div>
            </div>
            
            <!-- Question Section -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Assessment Question</h3>
                
                <div class="space-y-4">
                    <!-- Question Type -->
                    <div>
                        <label for="question_type" class="block text-gray-700 font-semibold mb-2">Question Type</label>
                        <select id="question_type" name="question_type" onchange="toggleQuestionType()"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10375B] focus:border-transparent">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True/False</option>
                            <option value="short_answer">Short Answer</option>
                        </select>
                    </div>
                    
                    <!-- Question Text -->
                    <div>
                        <label for="chapter_question" class="block text-gray-700 font-semibold mb-2">
                            Question <span class="text-red-500">*</span>
                        </label>
                        <textarea id="chapter_question" name="chapter_question" rows="3" required
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10375B] focus:border-transparent"
                                  placeholder="Enter the assessment question..."></textarea>
                    </div>
                    
                    <!-- Multiple Choice Options -->
                    <div id="multiple_choice_options">
                        <label class="block text-gray-700 font-semibold mb-2">Answer Options</label>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="correct_option" value="1" id="correct_1">
                                <label for="correct_1" class="text-sm font-medium">Correct:</label>
                                <input type="text" name="option_1" id="option_1" placeholder="Option A"
                                       class="flex-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-[#10375B] focus:border-transparent">
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" name="correct_option" value="2" id="correct_2">
                                <label for="correct_2" class="text-sm font-medium">Option:</label>
                                <input type="text" name="option_2" id="option_2" placeholder="Option B"
                                       class="flex-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-[#10375B] focus:border-transparent">
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" name="correct_option" value="3" id="correct_3">
                                <label for="correct_3" class="text-sm font-medium">Option:</label>
                                <input type="text" name="option_3" id="option_3" placeholder="Option C"
                                       class="flex-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-[#10375B] focus:border-transparent">
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" name="correct_option" value="4" id="correct_4">
                                <label for="correct_4" class="text-sm font-medium">Option:</label>
                                <input type="text" name="option_4" id="option_4" placeholder="Option D"
                                       class="flex-1 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-[#10375B] focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    
                    <!-- True/False Options -->
                    <div id="true_false_options" class="hidden">
                        <label class="block text-gray-700 font-semibold mb-2">Correct Answer</label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="tf_answer" value="True" class="mr-2">
                                <span>True</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="tf_answer" value="False" class="mr-2">
                                <span>False</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Short Answer -->
                    <div id="short_answer_options" class="hidden">
                        <label for="short_answer" class="block text-gray-700 font-semibold mb-2">Correct Answer</label>
                        <input type="text" id="short_answer" name="short_answer" placeholder="Enter the correct answer..."
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10375B] focus:border-transparent">
                        <p class="text-sm text-gray-600 mt-1">Student answers will be compared to this (case-insensitive).</p>
                    </div>
                    
                    <!-- Points Reward -->
                    <div>
                        <label for="points_reward" class="block text-gray-700 font-semibold mb-2">Points Reward</label>
                        <input type="number" id="points_reward" name="points_reward" value="50" min="1" max="1000"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10375B] focus:border-transparent">
                        <p class="text-sm text-gray-600 mt-1">Points students earn for completing this chapter correctly.</p>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeChapterModal()"
                        class="px-6 py-3 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="submit-btn"
                        class="px-6 py-3 bg-[#10375B] text-white rounded-lg hover:bg-blue-900 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    <span id="submit-text">Add Chapter</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Chapter Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Chapter Preview</h2>
                <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-6" id="preview-content">
            <!-- Preview content will be loaded here -->
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
let isReorderMode = false;
let sortable = null;

// Modal functions
function openAddChapterModal() {
    resetChapterForm();
    document.getElementById('modal-title').textContent = 'Add New Chapter';
    document.getElementById('submit-text').textContent = 'Add Chapter';
    document.getElementById('chapterModal').classList.remove('hidden');
    document.getElementById('chapterModal').classList.add('flex');
}

function editChapter(chapterId) {
    // Fetch chapter data via AJAX and populate form
    fetch(`get-chapter.php?chapter_id=${chapterId}`)
        .then(response => response.json())
        .then(chapter => {
            document.getElementById('modal-title').textContent = 'Edit Chapter';
            document.getElementById('submit-text').textContent = 'Update Chapter';
            document.getElementById('chapter_id').value = chapterId;
            document.getElementById('form_action').name = 'update_chapter';
            
            // Populate form fields
            document.getElementById('chapter_title').value = chapter.title || '';
            document.getElementById('chapter_content').value = chapter.content || '';
            document.getElementById('video_url').value = chapter.video_url || '';
            document.getElementById('audio_url').value = chapter.audio_url || '';
            document.getElementById('chapter_question').value = chapter.question || '';
            document.getElementById('question_type').value = chapter.question_type || 'multiple_choice';
            document.getElementById('points_reward').value = chapter.points_reward || 50;
            
            // Handle answer options based on question type
            if (chapter.question_type === 'multiple_choice' && chapter.answer_options) {
                const options = JSON.parse(chapter.answer_options);
                options.forEach((option, index) => {
                    const input = document.getElementById(`option_${index + 1}`);
                    if (input) input.value = option;
                    
                    if (option === chapter.correct_answer) {
                        document.getElementById(`correct_${index + 1}`).checked = true;
                    }
                });
            } else if (chapter.question_type === 'true_false') {
                const tfInputs = document.querySelectorAll('input[name="tf_answer"]');
                tfInputs.forEach(input => {
                    if (input.value === chapter.correct_answer) {
                        input.checked = true;
                    }
                });
            } else if (chapter.question_type === 'short_answer') {
                document.getElementById('short_answer').value = chapter.correct_answer || '';
            }
            
            toggleQuestionType();
            
            document.getElementById('chapterModal').classList.remove('hidden');
            document.getElementById('chapterModal').classList.add('flex');
        })
        .catch(error => {
            console.error('Error fetching chapter:', error);
            Swal.fire('Error', 'Could not load chapter data', 'error');
        });
}

function closeChapterModal() {
    document.getElementById('chapterModal').classList.add('hidden');
    document.getElementById('chapterModal').classList.remove('flex');
    resetChapterForm();
}

function resetChapterForm() {
    document.getElementById('chapter-form').reset();
    document.getElementById('chapter_id').value = '';
    document.getElementById('form_action').name = 'add_chapter';
    toggleQuestionType();
}

function toggleQuestionType() {
    const questionType = document.getElementById('question_type').value;
    const mcOptions = document.getElementById('multiple_choice_options');
    const tfOptions = document.getElementById('true_false_options');
    const saOptions = document.getElementById('short_answer_options');
    
    // Hide all option types
    mcOptions.classList.add('hidden');
    tfOptions.classList.add('hidden');
    saOptions.classList.add('hidden');
    
    // Show relevant option type
    if (questionType === 'multiple_choice') {
        mcOptions.classList.remove('hidden');
    } else if (questionType === 'true_false') {
        tfOptions.classList.remove('hidden');
    } else if (questionType === 'short_answer') {
        saOptions.classList.remove('hidden');
    }
}

// Update correct answer based on question type
document.addEventListener('DOMContentLoaded', function() {
    // Handle multiple choice correct answer selection
    document.querySelectorAll('input[name="correct_option"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const optionInput = document.getElementById(`option_${this.value}`);
                document.getElementById('correct_answer').value = optionInput.value;
            }
        });
    });
    
    // Update correct answer when option text changes
    for (let i = 1; i <= 4; i++) {
        const optionInput = document.getElementById(`option_${i}`);
        optionInput?.addEventListener('input', function() {
            const correctRadio = document.getElementById(`correct_${i}`);
            if (correctRadio.checked) {
                document.getElementById('correct_answer').value = this.value;
            }
        });
    }
    
    // Handle true/false answer
    document.querySelectorAll('input[name="tf_answer"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('correct_answer').value = this.value;
            }
        });
    });
    
    // Handle short answer
    document.getElementById('short_answer')?.addEventListener('input', function() {
        document.getElementById('correct_answer').value = this.value;
    });
});

// Add hidden correct_answer input to form
const correctAnswerInput = document.createElement('input');
correctAnswerInput.type = 'hidden';
correctAnswerInput.name = 'correct_answer';
correctAnswerInput.id = 'correct_answer';
document.getElementById('chapter-form').appendChild(correctAnswerInput);

// Reorder functions
function toggleReorderMode() {
    isReorderMode = !isReorderMode;
    const reorderBtn = document.getElementById('reorder-btn');
    const reorderForm = document.getElementById('reorder-form');
    const dragHandles = document.querySelectorAll('.drag-handle');
    const chaptersList = document.getElementById('chapters-list');
    
    if (isReorderMode) {
        reorderBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
        reorderForm.style.display = 'block';
        dragHandles.forEach(handle => handle.classList.remove('hidden'));
        
        // Initialize sortable
        sortable = new Sortable(chaptersList, {
            handle: '.drag-handle',
            animation: 150,
            onUpdate: function(evt) {
                // Update the order inputs
                const items = chaptersList.querySelectorAll('.chapter-item');
                items.forEach((item, index) => {
                    const chapterId = item.dataset.chapterId;
                    const orderInput = item.querySelector('input[type="hidden"]');
                    if (orderInput) {
                        orderInput.value = index + 1;
                    }
                });
            }
        });
    } else {
        reorderBtn.innerHTML = '<i class="fas fa-sort"></i> Reorder';
        reorderForm.style.display = 'none';
        dragHandles.forEach(handle => handle.classList.add('hidden'));
        
        if (sortable) {
            sortable.destroy();
            sortable = null;
        }
    }
}

// Preview chapter
function previewChapter(chapterId) {
    fetch(`get-chapter.php?chapter_id=${chapterId}`)
        .then(response => response.json())
        .then(chapter => {
            const previewContent = document.getElementById('preview-content');
            
            let content = `
                <div class="space-y-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">${chapter.title}</h3>
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-blue-800 font-medium mb-2">Chapter ${chapter.chapter_order}</p>
                            <p class="text-sm text-blue-600">${chapter.points_reward} points reward</p>
                        </div>
                    </div>
            `;
            
            if (chapter.content) {
                content += `
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3">Story Content:</h4>
                        <div class="prose max-w-none bg-gray-50 p-4 rounded-lg border">
                            ${chapter.content.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                `;
            }
            
            if (chapter.video_url) {
                content += `
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3">Video:</h4>
                        <div class="bg-gray-50 p-4 rounded-lg border">
                            <a href="${chapter.video_url}" target="_blank" class="text-blue-600 hover:underline">
                                <i class="fas fa-external-link-alt mr-2"></i>${chapter.video_url}
                            </a>
                        </div>
                    </div>
                `;
            }
            
            if (chapter.audio_url) {
                content += `
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3">Audio:</h4>
                        <div class="bg-gray-50 p-4 rounded-lg border">
                            <a href="${chapter.audio_url}" target="_blank" class="text-green-600 hover:underline">
                                <i class="fas fa-external-link-alt mr-2"></i>${chapter.audio_url}
                            </a>
                        </div>
                    </div>
                `;
            }
            
            if (chapter.question) {
                content += `
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-3">Assessment Question:</h4>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <p class="text-gray-800 mb-3">${chapter.question}</p>
                            <p class="text-sm text-gray-600 mb-2"><strong>Type:</strong> ${chapter.question_type.replace('_', ' ').toUpperCase()}</p>
                `;
                
                if (chapter.answer_options && chapter.question_type === 'multiple_choice') {
                    const options = JSON.parse(chapter.answer_options);
                    content += '<div class="mt-3"><strong class="text-sm text-gray-600">Options:</strong><ul class="list-disc list-inside mt-1 text-sm text-gray-700">';
                    options.forEach((option, index) => {
                        const isCorrect = option === chapter.correct_answer;
                        content += `<li class="${isCorrect ? 'text-green-600 font-semibold' : ''}">${option} ${isCorrect ? '✓' : ''}</li>`;
                    });
                    content += '</ul></div>';
                } else {
                    content += `<p class="text-sm text-green-600 font-semibold"><strong>Correct Answer:</strong> ${chapter.correct_answer}</p>`;
                }
                
                content += `
                        </div>
                    </div>
                `;
            }
            
            content += '</div>';
            previewContent.innerHTML = content;
            
            document.getElementById('previewModal').classList.remove('hidden');
            document.getElementById('previewModal').classList.add('flex');
        })
        .catch(error => {
            console.error('Error fetching chapter:', error);
            Swal.fire('Error', 'Could not load chapter preview', 'error');
        });
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
    document.getElementById('previewModal').classList.remove('flex');
}

// Confirm delete chapter
function confirmDeleteChapter(chapterId) {
    Swal.fire({
        title: 'Delete Chapter?',
        text: "This action cannot be undone. All student progress for this chapter will be lost.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `teacher-chapters.php?program_id=<?= $program_id ?>&delete_chapter=${chapterId}`;
        }
    });
}

// Initialize question type toggle
toggleQuestionType();
</script>

</body>
</html>