<?php
// Chapter Content Form Component
$programId = isset($program_id) ? (int)$program_id : 0;
$chapterId = isset($chapter_id) ? (int)$chapter_id : 0;
$chapter = isset($chapter) ? $chapter : (function() use ($conn, $chapterId) { return ph_getChapter($conn, $chapterId); })();
$stories = ph_getChapterStories($conn, $chapterId);
?>

<section class="content-section">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <button onclick="goBack()" class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="section-title text-2xl font-bold">Edit Chapter</h1>
        </div>
        <div class="text-sm text-gray-500">
            <?= htmlspecialchars($chapter['title'] ?? '') ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <!-- Chapter Editor -->
        <form id="chapterForm" method="POST" action="../../php/program-handler.php" class="space-y-8">
            <input type="hidden" name="action" value="update_chapter">
            <input type="hidden" name="program_id" value="<?= $programId ?>">
            <input type="hidden" name="chapter_id" value="<?= $chapterId ?>">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chapter Title</label>
                    <input type="text" name="title" 
                           value="<?= htmlspecialchars($chapter['title'] ?? '') ?>" 
                           class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chapter Content</label>
                    <textarea name="content" rows="5" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($chapter['content'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reflection Question</label>
                    <textarea name="question" rows="3" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($chapter['question'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Stories List (Legacy styling reapplied) -->
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-[10px] p-[10px] text-company_blue">
                        <i class="ph ph-books text-[24px]"></i>
                        <h3 class="body-text2-semibold">Stories</h3>
                    </div>
                    <a href="teacher-programs.php?action=add_story&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>" 
                       class="px-4 py-2 bg-company_orange hover:opacity-90 text-white rounded-[10px] transition-colors inline-flex items-center gap-2">
                        <i class="ph ph-plus"></i><span class="body-text3-semibold">Add Story</span>
                    </a>
                </div>

                <?php if (empty($stories)): ?>
                    <div class="w-full text-center py-10 bg-company_white border border-dashed border-gray-300 rounded-[20px]">
                        <i class="ph ph-book text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500">No stories yet. Add up to 3 stories for this chapter.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <?php foreach ($stories as $story): ?>
                            <?php 
                                $sections = ph_getStoryInteractiveSections($conn, (int)$story['story_id']);
                                $sectionCount = is_array($sections) ? count($sections) : 0;
                            ?>
                            <div class="bg-company_white border border-gray-200 rounded-[20px] shadow-sm hover:shadow-md transition-shadow">
                                <div class="relative">
                                    <img src="<?= '../../images/default-story.jpg' ?>" alt="Story" class="w-full h-36 object-cover rounded-t-[20px]">
                                    <span class="absolute top-2 left-2 px-2 py-1 text-xs rounded-full bg-gray-900/70 text-white">
                                        <?= $sectionCount ?> sections
                                    </span>
                                </div>
                                <div class="p-4">
                                    <h4 class="body-text2-semibold text-company_blue mb-1 line-clamp-1"><?= htmlspecialchars($story['title']) ?></h4>
                                    <p class="text-sm text-gray-600 line-clamp-2 mb-3"><?= htmlspecialchars($story['synopsis_english']) ?></p>
                                    <div class="flex items-center justify-between">
                                        <a href="teacher-programs.php?action=add_story&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>&story_id=<?= (int)$story['story_id'] ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-sm inline-flex items-center gap-1">
                                            <i class="ph ph-pencil-simple"></i> Edit
                                        </a>
                                        <div class="flex items-center gap-3">
                                            <a href="teacher-programs.php?action=edit_interactive&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>&story_id=<?= (int)$story['story_id'] ?>" 
                                               class="text-purple-600 hover:text-purple-800 text-sm inline-flex items-center gap-1">
                                                <i class="ph ph-chats"></i> Interactive
                                            </a>
                                            <button type="button" onclick="deleteStory(<?= (int)$story['story_id'] ?>)" class="text-red-600 hover:text-red-800 text-sm inline-flex items-center gap-1">
                                                <i class="ph ph-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <button type="button" onclick="goBack()" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Back
                </button>
                <button type="submit" class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                    <i class="ph ph-check mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const programId = <?= $programId ?>;
const chapterId = <?= $chapterId ?>;

function goBack() {
    window.location.href = `teacher-programs.php?action=create&program_id=${programId}`;
}

function deleteStory(storyId) {
    Swal.fire({
        title: 'Delete Story?',
        text: 'This will permanently delete this story and its interactive sections.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'delete_story');
            fd.append('story_id', storyId);
            fetch('../../php/program-handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', 'Story deleted successfully.', 'success')
                            .then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete story.', 'error');
                    }
                });
        }
    });
}
</script>

<style>
/* Legacy card feel */
.body-text2-semibold { font-weight: 600; font-size: 1rem; }
.body-text3-semibold { font-weight: 600; font-size: 0.9rem; }
.bg-company_white { background: #ffffff; }
.text-company_blue { color: #10375B; }
.text-company_orange { color: #F97316; }
</style>
