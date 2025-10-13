<!-- Program Details Form Component -->
<section class="content-section">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <button onclick="goBack()" class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="section-title text-2xl font-bold"><?= $program ? 'Edit Program' : 'Program Details' ?></h1>
        </div>
        <?php if ($program): ?>
            <div class="flex gap-2">
                <span class="px-3 py-1 rounded-full text-sm 
                    <?= $program['status'] === 'published' ? 'bg-green-100 text-green-800' : 
                       ($program['status'] === 'pending_review' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                    <?= ucfirst(str_replace('_', ' ', $program['status'])) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <form id="programDetailsForm" method="POST" action="../../php/program-handler.php" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="action" value="<?= $program ? 'update_program' : 'create_program' ?>">
            <?php if ($program): ?>
                <input type="hidden" name="program_id" value="<?= $program['programID'] ?>">
            <?php endif; ?>
            <input type="hidden" name="teacher_id" value="<?= $teacher_id ?>">

            <!-- Thumbnail Upload -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Thumbnail</h3>
                <div class="flex items-center gap-6">
                    <div class="thumbnail-preview">
                        <img id="thumbnailPreview" 
                             src="<?= $program && $program['thumbnail'] ? '../../uploads/thumbnails/' . $program['thumbnail'] : '../../images/default-program.jpg' ?>" 
                             alt="Program Thumbnail" 
                             class="w-32 h-32 object-cover rounded-lg border-2 border-gray-200">
                    </div>
                    <div class="flex-1">
                        <label for="thumbnail" class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Program Thumbnail
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="file" id="thumbnail" name="thumbnail" accept="image/*" 
                                   class="hidden" onchange="previewThumbnail(this)">
                            <label for="thumbnail" 
                                   class="cursor-pointer bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                                <i class="ph ph-upload-simple mr-2"></i>
                                Upload Image
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Recommended: 500 x 400px, JPG/PNG</p>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200">

            <!-- Program Title -->
            <div class="space-y-2">
                <label for="title" class="block text-sm font-medium text-gray-700">Program Title</label>
                <input type="text" id="title" name="title" required
                       value="<?= $program ? htmlspecialchars($program['title']) : '' ?>"
                       placeholder="e.g. [translate:الحج] (Hajj)"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Program Description -->
            <div class="space-y-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Program Description</label>
                <textarea id="description" name="description" rows="4" required
                          placeholder="[translate:الحج هي الرحلة المقدسة إلى مكة المكرمة...] Hajj are the recorded accounts of the sayings, actions, silent approvals, and physical descriptions of the Prophet Muhammad (peace be upon him). They serve as the..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= $program ? htmlspecialchars($program['description']) : '' ?></textarea>
            </div>

            <!-- Difficulty Level -->
            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700">Difficulty</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="difficulty_level" value="Student" 
                               <?= ($program && $program['difficulty_level'] === 'Student') ? 'checked' : '' ?>
                               class="sr-only" required>
                        <div class="difficulty-card student-difficulty p-4 border-2 border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="ph-fill ph-barbell text-2xl"></i>
                                <div>
                                    <h4 class="font-semibold">Student Difficulty</h4>
                                    <p class="text-sm text-gray-600">Basic level content</p>
                                </div>
                            </div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="difficulty_level" value="Aspiring" 
                               <?= ($program && $program['difficulty_level'] === 'Aspiring') ? 'checked' : '' ?>
                               class="sr-only">
                        <div class="difficulty-card aspiring-difficulty p-4 border-2 border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="ph-fill ph-barbell text-2xl"></i>
                                <div>
                                    <h4 class="font-semibold">Aspiring Difficulty</h4>
                                    <p class="text-sm text-gray-600">Intermediate level content</p>
                                </div>
                            </div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="difficulty_level" value="Master" 
                               <?= ($program && $program['difficulty_level'] === 'Master') ? 'checked' : '' ?>
                               class="sr-only">
                        <div class="difficulty-card master-difficulty p-4 border-2 border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="ph-fill ph-barbell text-2xl"></i>
                                <div>
                                    <h4 class="font-semibold">Master Difficulty</h4>
                                    <p class="text-sm text-gray-600">Advanced level content</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Program Price -->
            <div class="space-y-2">
                <label for="price" class="block text-sm font-medium text-gray-700">Program Price (Philippine Peso)</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-500">₱</span>
                    <input type="number" id="price" name="price" min="0" step="0.01" required
                           value="<?= $program ? $program['price'] : '500.00' ?>"
                           class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Overview Video -->
            <div class="space-y-2">
                <label for="overview_video_url" class="block text-sm font-medium text-gray-700">Overview Video</label>
                <input type="url" id="overview_video_url" name="overview_video_url"
                       value="<?= $program ? htmlspecialchars($program['overview_video_url']) : '' ?>"
                       placeholder="https://youtube.com/..."
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-sm text-gray-500">A YouTube video to be displayed in the program overview</p>
            </div>

            <hr class="border-gray-200">

            <!-- Chapters Section -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Chapters</h3>
                    <button type="button" onclick="addChapter()" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="ph ph-plus mr-2"></i>Add Chapter
                    </button>
                </div>
                
                <div id="chaptersContainer" class="space-y-4">
                    <?php 
                    $chapters = $program ? getProgramChapters($conn, $program['programID']) : [];
                    if (empty($chapters)): 
                    ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="ph ph-book text-4xl mb-4"></i>
                            <p>No chapters yet. Add your first chapter to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($chapters as $index => $chapter): ?>
                            <div class="chapter-item bg-gray-50 rounded-lg p-4 border border-gray-200" data-chapter-id="<?= $chapter['chapter_id'] ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <i class="ph ph-book text-xl text-gray-600"></i>
                                        <div>
                                            <h4 class="font-medium"><?= htmlspecialchars($chapter['title']) ?></h4>
                                            <p class="text-sm text-gray-500">Chapter <?= $index + 1 ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="editChapter(<?= $chapter['chapter_id'] ?>)" 
                                                class="text-blue-500 hover:text-blue-700 p-2 rounded hover:bg-blue-50">
                                            <i class="ph ph-pencil-simple"></i>
                                        </button>
                                        <button type="button" onclick="deleteChapter(<?= $chapter['chapter_id'] ?>)" 
                                                class="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="border-gray-200">

            <!-- Save Actions -->
            <div class="flex justify-between items-center">
                <button type="button" onclick="goBack()" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <div class="flex gap-3">
                    <button type="submit" name="status" value="draft" 
                            class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                        <i class="ph ph-floppy-disk mr-2"></i>Save as Draft
                    </button>
                    <?php if (!$program || $program['status'] === 'draft'): ?>
                        <button type="submit" name="status" value="ready_for_review" 
                                class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                            <i class="ph ph-check-circle mr-2"></i>Save & Continue
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
// Form handling functions
function previewThumbnail(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('thumbnailPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function goBack() {
    if (confirm('Are you sure you want to go back? Any unsaved changes will be lost.')) {
        window.location.href = 'teacher-programs.php';
    }
}

function addChapter() {
    const title = prompt('Enter chapter title:');
    if (title) {
        const programId = <?= $program ? $program['programID'] : 'null' ?>;
        if (programId) {
            // AJAX call to add chapter
            fetch('../../php/program-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_chapter',
                    program_id: programId,
                    title: title
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error adding chapter: ' + data.message);
                }
            });
        } else {
            alert('Please save the program first before adding chapters.');
        }
    }
}

function editChapter(chapterId) {
    const programId = <?= $program ? $program['programID'] : 'null' ?>;
    window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`;
}

function deleteChapter(chapterId) {
    if (confirm('Are you sure you want to delete this chapter? This will also delete all stories and quizzes in this chapter.')) {
        fetch('../../php/program-handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_chapter',
                chapter_id: chapterId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting chapter: ' + data.message);
            }
        });
    }
}

// Difficulty selection styling
document.addEventListener('DOMContentLoaded', function() {
    const difficultyInputs = document.querySelectorAll('input[name="difficulty_level"]');
    difficultyInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Reset all cards
            document.querySelectorAll('.difficulty-card').forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
            });
            
            // Highlight selected card
            if (this.checked) {
                const card = this.nextElementSibling;
                card.classList.remove('border-gray-200');
                card.classList.add('border-blue-500', 'bg-blue-50');
            }
        });
        
        // Set initial state
        if (input.checked) {
            const card = input.nextElementSibling;
            card.classList.remove('border-gray-200');
            card.classList.add('border-blue-500', 'bg-blue-50');
        }
    });
});
</script>

<style>
.difficulty-card.student-difficulty { color: #374151; }
.difficulty-card.aspiring-difficulty { color: #10375B; }
.difficulty-card.master-difficulty { color: #A58618; }
</style>