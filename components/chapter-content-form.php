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

            <!-- Stories List -->
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Stories</h3>
                    <a href="teacher-programs.php?action=add_story&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>" 
                       class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                        <i class="ph ph-plus mr-1"></i> Add Story
                    </a>
                </div>

                <?php if (empty($stories)): ?>
                    <div class="text-center py-12 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                        <i class="ph ph-book text-5xl mb-4"></i>
                        <h4 class="text-lg font-medium mb-2">No Stories Yet</h4>
                        <p class="mb-4">Add up to 3 stories for this chapter.</p>
                        <a href="teacher-programs.php?action=add_story&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>" 
                           class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                            <i class="ph ph-plus mr-2"></i> Add First Story
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($stories as $story): ?>
                            <?php 
                                $sections = ph_getStoryInteractiveSections($conn, (int)$story['story_id']);
                                $sectionCount = count($sections);
                            ?>
                            <div class="story-card bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                <div class="p-4">
                                    <div class="flex items-start justify-between mb-3">
                                        <h4 class="font-medium text-gray-900 line-clamp-1"><?= htmlspecialchars($story['title']) ?></h4>
                                        <span class="text-xs px-2 py-1 rounded-full <?= $sectionCount > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                            <?= $sectionCount ?> sections
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 line-clamp-2 mb-3"><?= htmlspecialchars($story['synopsis_english']) ?></p>
                                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
                                        <i class="ph ph-video"></i>
                                        <span><?= htmlspecialchars($story['video_url']) ?></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <a href="teacher-programs.php?action=add_story&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>&story_id=<?= (int)$story['story_id'] ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="ph ph-pencil-simple"></i> Edit Story
                                        </a>
                                        <div class="flex items-center gap-2">
                                            <a href="teacher-programs.php?action=edit_interactive&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>&story_id=<?= (int)$story['story_id'] ?>" 
                                               class="text-purple-600 hover:text-purple-800 text-sm">
                                                <i class="ph ph-chats"></i> Interactive
                                            </a>
                                            <button type="button" onclick="deleteStory(<?= (int)$story['story_id'] ?>)" class="text-red-600 hover:text-red-800 text-sm">
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
.story-card { transition: transform 0.2s, box-shadow 0.2s; }
.story-card:hover { transform: translateY(-2px); }
</style>
