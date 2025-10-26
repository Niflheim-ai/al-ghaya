<!-- Chapter Content Form Component (refactored to use canonical functions) -->
<?php
require_once __DIR__ . '/../php/program-handler.php';
$stories = chapter_getStories($conn, $chapter_id);
$quiz = chapter_getQuiz($conn, $chapter_id);
$storyCount = is_array($stories) ? count($stories) : 0;
$quizCount = $quiz ? 1 : 0;
?>
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
            <h2 class="text-xl font-semibold mb-4"><?= htmlspecialchars($chapter['title'] ?? '') ?></h2>
            <div class="grid grid-cols-2 gap-4 max-w-md">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Story Left:</p>
                    <p class="text-2xl font-bold text-blue-600" id="storyCount"><?= $storyCount ?></p>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Quiz Left:</p>
                    <p class="text-2xl font-bold text-green-600" id="quizCount"><?= $quizCount ?></p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <button onclick="addStory()" id="addStoryBtn"
                    class="<?= $storyCount >= 3 ? 'opacity-50 cursor-not-allowed' : '' ?> p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors group">
                <div class="text-center">
                    <i class="ph ph-plus-square text-3xl text-gray-400 group-hover:text-blue-500 mb-3"></i>
                    <h3 class="font-medium text-gray-900 group-hover:text-blue-600">Add Story</h3>
                    <p class="text-sm text-gray-500">
                        <?php if ($storyCount >= 3): ?>
                            Maximum stories reached (3/3)
                        <?php elseif ($storyCount == 0): ?>
                            Create your first story (min 1 required)
                        <?php else: ?>
                            Add story (<?= $storyCount ?>/3 created)
                        <?php endif; ?>
                    </p>
                </div>
            </button>
            
            <button onclick="addQuiz()" id="addQuizBtn"
                    class="<?= $quizCount >= 1 ? 'opacity-50 cursor-not-allowed' : '' ?> p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors group">
                <div class="text-center">
                    <i class="ph ph-question text-3xl text-gray-400 group-hover:text-green-500 mb-3"></i>
                    <h3 class="font-medium text-gray-900 group-hover:text-green-600">Add Quiz</h3>
                    <p class="text-sm text-gray-500">
                        <?php if ($quizCount >= 1): ?>
                            Quiz already exists (1/1)
                        <?php else: ?>
                            Create chapter quiz (0/1)
                        <?php endif; ?>
                    </p>
                </div>
            </button>
        </div>

        <hr class="border-gray-200 mb-8">

        <!-- Stories Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Stories</h3>
                <span class="text-sm text-gray-500"><?= $storyCount ?> of 3 stories</span>
            </div>
            
            <?php if (empty($stories)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="ph ph-book-open text-4xl mb-4"></i>
                    <p>No stories yet. Each chapter needs at least 1 story!</p>
                    <button onclick="addStory()" class="mt-4 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Add Your First Story
                    </button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($stories as $index => $story): ?>
                        <?php $interactiveSections = getStoryInteractiveSections($conn, $story['story_id']); $sectionCount = is_array($interactiveSections) ? count($interactiveSections) : 0; ?>
                        <div class="story-card bg-white rounded-lg p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="ph ph-book-open text-xl text-blue-600"></i>
                                        <span class="text-sm font-medium text-blue-600">Story <?= (int)($story['story_order'] ?? ($index+1)) ?></span>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 mb-2"><?= htmlspecialchars($story['title']) ?></h4>
                                    <?php if (!empty($story['synopsis_english'])): ?>
                                        <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars(substr($story['synopsis_english'], 0, 100)) ?>...</p>
                                    <?php endif; ?>
                                </div>
                                <div class="relative">
                                    <button onclick="toggleStoryMenu(<?= (int)$story['story_id'] ?>)" class="text-gray-400 hover:text-gray-600 p-1">
                                        <i class="ph ph-dots-three-vertical"></i>
                                    </button>
                                    <div id="menu-<?= (int)$story['story_id'] ?>" class="hidden absolute right-0 top-8 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-10 w-36">
                                        <button onclick="editStory(<?= (int)$story['story_id'] ?>)" class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                            <i class="ph ph-pencil-simple mr-2"></i>Edit
                                        </button>
                                        <button onclick="deleteStory(<?= (int)$story['story_id'] ?>)" class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <i class="ph ph-trash mr-2"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4 text-sm">
                                    <div class="flex items-center gap-1">
                                        <i class="ph ph-chat-circle-dots text-purple-600"></i>
                                        <span class="text-gray-600"><?= $sectionCount ?> Interactive</span>
                                    </div>
                                    <?php if (!empty($story['video_url'])): ?>
                                        <div class="flex items-center gap-1">
                                            <i class="ph ph-play-circle text-red-600"></i>
                                            <span class="text-gray-600">Video</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button onclick="editStory(<?= (int)$story['story_id'] ?>)" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                    Edit Content
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quiz Section -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Chapter Quiz</h3>
                <span class="text-sm text-gray-500"><?= $quizCount ?> of 1 quiz</span>
            </div>
            
            <?php if (!$quiz): ?>
                <div class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                    <i class="ph ph-question text-4xl mb-4"></i>
                    <p>No quiz yet. Each chapter must have exactly one quiz!</p>
                    <button onclick="addQuiz()" class="mt-4 bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Create Chapter Quiz
                    </button>
                </div>
            <?php else: ?>
                <div class="quiz-card bg-white rounded-lg p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-question text-2xl text-green-600"></i>
                            <div>
                                <h4 class="font-semibold text-gray-900">Quiz</h4>
                                <p class="text-sm text-gray-600">Multiple Choice</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                Quiz Ready
                            </span>
                            <button onclick="editQuiz(<?= (int)($quiz['quiz_id'] ?? 0) ?>)" 
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="ph ph-pencil-simple mr-1"></i>Edit Quiz
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
            <button onclick="goBackToProgram()" 
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors inline-flex items-center gap-2">
                <i class="ph ph-arrow-left"></i>Back to Program
            </button>
            <div class="text-right">
                <p class="text-sm text-gray-500">Chapter: <?= htmlspecialchars($chapter['title']) ?></p>
                <?php if ($storyCount == 0): ?>
                    <p class="text-sm text-red-600 font-medium">⚠ At least 1 story required</p>
                <?php elseif ($storyCount < 3): ?>
                    <p class="text-sm text-blue-600">✓ Add <?= 3 - $storyCount ?> more stories (optional)</p>
                <?php else: ?>
                    <p class="text-sm text-green-600">✓ Maximum stories added</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const programId = <?= (int)$program_id ?>;
const chapterId = <?= (int)$chapter_id ?>;
let currentStoryCount = <?= (int)$storyCount ?>;
let currentQuizCount = <?= (int)$quizCount ?>;

function goBackToProgram() {
    window.location.href = `teacher-programs.php?action=create&program_id=${programId}`;
}

function addStory() {
    if (currentStoryCount >= 3) {
        Swal.fire({ title: 'Maximum Stories Reached', text: 'Each chapter can have a maximum of 3 stories.', icon: 'warning', confirmButtonColor: '#3b82f6' });
        return;
    }
    Swal.fire({ title: 'Create New Story', text: `You are creating story ${currentStoryCount + 1} of 3 for this chapter.`, icon: 'question', showCancelButton: true, confirmButtonColor: '#3b82f6', cancelButtonColor: '#6b7280', confirmButtonText: 'Create Story', cancelButtonText: 'Cancel' }).then((result)=>{
        if (result.isConfirmed) { window.location.href = `teacher-programs.php?action=add_story&program_id=${programId}&chapter_id=${chapterId}`; }
    });
}

function addQuiz() {
    if (currentQuizCount >= 1) {
        Swal.fire({ title: 'Quiz Already Exists', text: 'Each chapter can have only one quiz. Would you like to edit the existing quiz?', icon: 'info', showCancelButton: true, confirmButtonColor: '#3b82f6', cancelButtonColor: '#6b7280', confirmButtonText: 'Edit Quiz', cancelButtonText: 'Cancel' }).then((result)=>{
            if (result.isConfirmed) { <?php if ($quiz): ?> editQuiz(<?= (int)$quiz['quiz_id'] ?>); <?php endif; ?> }
        });
        return;
    }
    Swal.fire({ title: 'Create Chapter Quiz', text: 'A quiz helps assess student understanding of this chapter.', icon: 'question', showCancelButton: true, confirmButtonColor: '#10b981', cancelButtonColor: '#6b7280', confirmButtonText: 'Create Quiz', cancelButtonText: 'Cancel' }).then((result)=>{
        if (result.isConfirmed) { window.location.href = `teacher-programs.php?action=add_quiz&program_id=${programId}&chapter_id=${chapterId}`; }
    });
}

function editStory(storyId) { window.location.href = `teacher-programs.php?action=add_story&program_id=${programId}&chapter_id=${chapterId}&story_id=${storyId}`; }
function editQuiz(quizId) { window.location.href = `teacher-programs.php?action=add_quiz&program_id=${programId}&chapter_id=${chapterId}&quiz_id=${quizId}`; }

function toggleStoryMenu(storyId) {
    document.querySelectorAll('[id^="menu-"]').forEach(menu => { if (menu.id !== `menu-${storyId}`) { menu.classList.add('hidden'); } });
    const menu = document.getElementById(`menu-${storyId}`); if (menu) menu.classList.toggle('hidden');
}

function deleteStory(storyId) {
    if (currentStoryCount <= 1) { Swal.fire({ title: 'Cannot Delete', text: 'Each chapter must have at least 1 story.', icon: 'warning', confirmButtonColor: '#3b82f6' }); return; }
    Swal.fire({ title: 'Delete Story?', text: 'This will permanently delete the story and all its interactive sections.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Yes, delete it', cancelButtonText: 'Cancel' }).then((result)=>{
        if (result.isConfirmed) {
            Swal.fire({ title: 'Deleting Story...', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: ()=>{ Swal.showLoading(); } });
            fetch('../../php/program-handler.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'delete_story', story_id: storyId }) })
              .then(r=>r.json()).then(data=>{
                if (data.success) { Swal.fire({ title: 'Deleted!', text: 'Story has been deleted successfully.', icon: 'success', confirmButtonColor: '#3b82f6' }).then(()=> location.reload()); }
                else { Swal.fire({ title: 'Error', text: data.message || 'Failed to delete story.', icon: 'error', confirmButtonColor: '#3b82f6' }); }
              }).catch(()=> Swal.fire({ title: 'Error', text: 'Network error. Please try again.', icon: 'error', confirmButtonColor: '#3b82f6' }));
        }
    });
}

// Close menus when clicking outside
document.addEventListener('click', function(e) { if (!e.target.closest('.relative')) { document.querySelectorAll('[id^="menu-"]').forEach(menu => menu.classList.add('hidden')); } });

// Update button states based on counts
document.addEventListener('DOMContentLoaded', function() {
    const addStoryBtn = document.getElementById('addStoryBtn');
    const addQuizBtn = document.getElementById('addQuizBtn');
    if (currentStoryCount >= 3 && addStoryBtn) { addStoryBtn.setAttribute('disabled', 'true'); addStoryBtn.onclick = function(){ Swal.fire({ title: 'Maximum Stories Reached', text: 'Each chapter can have a maximum of 3 stories.', icon: 'info', confirmButtonColor: '#3b82f6' }); }; }
    if (currentQuizCount >= 1 && addQuizBtn) { addQuizBtn.setAttribute('disabled', 'true'); }
});
</script>

<style>
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.story-card { transition: transform 0.2s, box-shadow 0.2s; }
.story-card:hover { transform: translateY(-2px); }
.quiz-card { background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-color: #10b981; }
button[disabled] { pointer-events: none; }
.story-card .relative { position: relative; }
.story-card .absolute { position: absolute; z-index: 20; }
</style>
