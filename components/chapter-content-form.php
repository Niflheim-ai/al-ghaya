<!-- Chapter Content Form Component -->
<section class="content-section">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <button onclick="goBackToProgram()" class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="section-title text-2xl font-bold">Chapter Contents</h1>
        </div>
        <div class="text-sm text-gray-500">
            <?= htmlspecialchars($program['title'] ?? '') ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <!-- Chapter Header -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-2"><?= htmlspecialchars($chapter['title'] ?? '') ?></h2>
            <div class="flex items-center gap-4 text-sm text-gray-600">
                <span>Story Left: <span id="storyCount"><?= count(getChapterStories($conn, $chapter_id)) ?></span></span>
                <span>Quiz Left: <span id="quizCount"><?= getChapterQuiz($conn, $chapter_id) ? '1' : '0' ?></span></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <button onclick="addStory()" 
                    class="p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors group">
                <div class="text-center">
                    <i class="ph ph-plus-square text-3xl text-gray-400 group-hover:text-blue-500 mb-3"></i>
                    <h3 class="font-medium text-gray-900 group-hover:text-blue-600">Add Story</h3>
                    <p class="text-sm text-gray-500">Create a new story for this chapter</p>
                </div>
            </button>
            
            <button onclick="addQuiz()" 
                    class="p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors group">
                <div class="text-center">
                    <i class="ph ph-question text-3xl text-gray-400 group-hover:text-green-500 mb-3"></i>
                    <h3 class="font-medium text-gray-900 group-hover:text-green-600">Add Quiz</h3>
                    <p class="text-sm text-gray-500">Create chapter quiz (1 per chapter)</p>
                </div>
            </button>
        </div>

        <hr class="border-gray-200 mb-8">

        <!-- Stories Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold mb-4">Stories</h3>
            <?php 
            $stories = getChapterStories($conn, $chapter_id);
            if (empty($stories)): 
            ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="ph ph-book-open text-4xl mb-4"></i>
                    <p>No stories yet. Add your first story!</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($stories as $story): ?>
                        <div class="story-item bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <i class="ph ph-book-open text-xl text-blue-600"></i>
                                    <div>
                                        <h4 class="font-medium"><?= htmlspecialchars($story['title']) ?></h4>
                                        <p class="text-sm text-gray-500">Story <?= $story['story_order'] ?></p>
                                        <?php if ($story['synopsis_english']): ?>
                                            <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars(substr($story['synopsis_english'], 0, 100)) ?>...</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                        <?= count(getStoryInteractiveSections($conn, $story['story_id'])) ?> Interactive Sections
                                    </span>
                                    <button onclick="editStory(<?= $story['story_id'] ?>)" 
                                            class="text-blue-500 hover:text-blue-700 p-2 rounded hover:bg-blue-50">
                                        <i class="ph ph-pencil-simple"></i>
                                    </button>
                                    <button onclick="deleteStory(<?= $story['story_id'] ?>)" 
                                            class="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quiz Section -->
        <div>
            <h3 class="text-lg font-semibold mb-4">Chapter Quiz</h3>
            <?php 
            $quiz = getChapterQuiz($conn, $chapter_id);
            if (!$quiz): 
            ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="ph ph-question text-4xl mb-4"></i>
                    <p>No quiz yet. Each chapter must have exactly one quiz!</p>
                </div>
            <?php else: ?>
                <?php $quizQuestions = getQuizQuestions($conn, $quiz['quiz_id']); ?>
                <div class="quiz-item bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-question text-xl text-green-600"></i>
                            <div>
                                <h4 class="font-medium"><?= htmlspecialchars($quiz['title']) ?></h4>
                                <p class="text-sm text-gray-500"><?= count($quizQuestions) ?> questions (max 30)</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                Multiple Choice Only
                            </span>
                            <button onclick="editQuiz(<?= $quiz['quiz_id'] ?>)" 
                                    class="text-green-500 hover:text-green-700 p-2 rounded hover:bg-green-50">
                                <i class="ph ph-pencil-simple"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
            <button onclick="goBackToProgram()" 
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="ph ph-arrow-left mr-2"></i>Back to Program
            </button>
            <div class="text-sm text-gray-500">
                Chapter: <?= htmlspecialchars($chapter['title']) ?>
            </div>
        </div>
    </div>
</section>

<script>
const programId = <?= $program_id ?>;
const chapterId = <?= $chapter_id ?>;

function goBackToProgram() {
    window.location.href = `teacher-programs-enhanced.php?action=create&program_id=${programId}`;
}

function addStory() {
    window.location.href = `teacher-programs-enhanced.php?action=add_story&program_id=${programId}&chapter_id=${chapterId}`;
}

function addQuiz() {
    <?php if ($quiz): ?>
        // Quiz already exists, go to edit
        editQuiz(<?= $quiz['quiz_id'] ?>);
    <?php else: ?>
        window.location.href = `teacher-programs-enhanced.php?action=add_quiz&program_id=${programId}&chapter_id=${chapterId}`;
    <?php endif; ?>
}

function editStory(storyId) {
    window.location.href = `teacher-programs-enhanced.php?action=add_story&program_id=${programId}&chapter_id=${chapterId}&story_id=${storyId}`;
}

function editQuiz(quizId) {
    window.location.href = `teacher-programs-enhanced.php?action=add_quiz&program_id=${programId}&chapter_id=${chapterId}`;
}

function deleteStory(storyId) {
    if (confirm('Are you sure you want to delete this story? This will also delete all interactive sections within the story.')) {
        fetch('../../php/program-handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_story',
                story_id: storyId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting story: ' + data.message);
            }
        });
    }
}

// Update counts dynamically
function updateCounts() {
    // These would be updated via AJAX in a real implementation
    // For now, we'll just reload the page to show updated counts
}
</script>