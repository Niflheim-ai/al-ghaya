<?php
// Chapter Content Form Component - Full Al-Ghaya Legacy Style
$programId = isset($program_id) ? (int)$program_id : 0;
$chapterId = isset($chapter_id) ? (int)$chapter_id : 0;
$chapter = isset($chapter) ? $chapter : (function() use ($conn, $chapterId) { return ph_getChapter($conn, $chapterId); })();
$stories = ph_getChapterStories($conn, $chapterId);
?>

<!-- Chapter Content Form -->
<section class="content-section">
    <h1 class="section-title md:text-2xl font-bold">Edit Chapter</h1>
    <p class="text-gray-600 mb-6"><?= htmlspecialchars($chapter['title'] ?? '') ?></p>

    <!-- Chapter Editor Section -->
    <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start mb-8">
        <div class="w-full flex gap-[25px] items-center justify-start">
            <div class="flex items-center gap-[10px] p-[10px] text-company_blue">
                <i class="ph ph-book-open text-[24px]"></i>
                <p class="body-text2-semibold">Chapter Details</p>
            </div>
        </div>
        
        <form id="chapterForm" method="POST" action="../../php/program-handler.php" class="w-full space-y-6">
            <input type="hidden" name="action" value="update_chapter">
            <input type="hidden" name="program_id" value="<?= $programId ?>">
            <input type="hidden" name="chapter_id" value="<?= $chapterId ?>">

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chapter Title</label>
                    <input type="text" name="title" 
                           value="<?= htmlspecialchars($chapter['title'] ?? '') ?>" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-[10px] focus:ring-2 focus:ring-company_blue focus:border-company_blue" 
                           required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chapter Content</label>
                    <textarea name="content" rows="5" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-[10px] focus:ring-2 focus:ring-company_blue focus:border-company_blue"><?= htmlspecialchars($chapter['content'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reflection Question</label>
                    <textarea name="question" rows="3" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-[10px] focus:ring-2 focus:ring-company_blue focus:border-company_blue"><?= htmlspecialchars($chapter['question'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="goBack()" 
                        class="px-6 py-3 border border-gray-300 rounded-[10px] text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="ph ph-arrow-left mr-1"></i> Back
                </button>
                <button type="submit" 
                        class="px-6 py-3 bg-company_blue hover:opacity-90 text-white rounded-[10px] transition-opacity">
                    <i class="ph ph-check mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Stories Section -->
    <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start">
        <div class="w-full flex gap-[25px] items-center justify-between">
            <div class="flex items-center gap-[10px] p-[10px] text-company_blue">
                <i class="ph ph-books text-[24px]"></i>
                <p class="body-text2-semibold">Stories</p>
                <span class="text-sm text-gray-500"><?= count($stories) ?> of 3 stories</span>
            </div>
            <?php if (count($stories) < 3): ?>
                <a href="teacher-programs.php?action=add_story&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>" 
                   class="px-4 py-2 bg-company_orange hover:opacity-90 text-white rounded-[10px] transition-opacity inline-flex items-center gap-2">
                    <i class="ph ph-plus"></i><span class="body-text3-semibold">Add Story</span>
                </a>
            <?php else: ?>
                <span class="px-4 py-2 bg-gray-400 text-white rounded-[10px] cursor-not-allowed inline-flex items-center gap-2">
                    <i class="ph ph-check"></i><span class="body-text3-semibold">Maximum Stories</span>
                </span>
            <?php endif; ?>
        </div>

        <?php if (empty($stories)): ?>
            <div class="w-full text-center py-12 border border-dashed border-gray-300 rounded-[20px]">
                <i class="ph ph-book text-6xl text-gray-300 mb-4"></i>
                <h4 class="body-text2-semibold text-gray-500 mb-2">No Stories Yet</h4>
                <p class="text-gray-400 mb-6">Stories help structure your chapter content and make it engaging for students.</p>
                <a href="teacher-programs.php?action=add_story&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>" 
                   class="px-6 py-3 bg-company_orange hover:opacity-90 text-white rounded-[10px] transition-opacity inline-flex items-center gap-2">
                    <i class="ph ph-plus"></i><span class="body-text3-semibold">Create First Story</span>
                </a>
            </div>
        <?php else: ?>
            <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-[20px]">
                <?php foreach ($stories as $index => $story): ?>
                    <?php 
                        $sections = ph_getStoryInteractiveSections($conn, (int)$story['story_id']);
                        $sectionCount = is_array($sections) ? count($sections) : 0;
                    ?>
                    <div class="story-card bg-white border border-gray-200 rounded-[20px] shadow-sm hover:shadow-lg transition-all duration-200">
                        <!-- Story Image -->
                        <div class="relative">
                            <div class="w-full h-[140px] bg-gradient-to-br from-blue-500 to-purple-600 rounded-t-[20px] flex items-center justify-center">
                                <i class="ph ph-book-open-text text-4xl text-white/80"></i>
                            </div>
                            <div class="absolute top-3 left-3 flex gap-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-white/90 text-gray-700">
                                    Story <?= $index + 1 ?>
                                </span>
                                <?php if ($sectionCount > 0): ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-500 text-white">
                                        <?= $sectionCount ?> interactive
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Story Content -->
                        <div class="p-[20px]">
                            <h4 class="body-text2-semibold text-company_blue mb-2 line-clamp-1">
                                <?= htmlspecialchars($story['title']) ?>
                            </h4>
                            <p class="text-sm text-gray-600 line-clamp-3 mb-4">
                                <?= htmlspecialchars($story['synopsis_english']) ?>
                            </p>
                            
                            <!-- Video URL Preview -->
                            <?php if (!empty($story['video_url'])): ?>
                                <div class="flex items-center gap-2 mb-4 p-2 bg-gray-50 rounded-[8px]">
                                    <i class="ph ph-video text-gray-600"></i>
                                    <span class="text-xs text-gray-500 truncate"><?= htmlspecialchars($story['video_url']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <a href="teacher-programs.php?action=add_story&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>&story_id=<?= (int)$story['story_id'] ?>" 
                                   class="text-company_blue hover:opacity-75 text-sm font-medium inline-flex items-center gap-1">
                                    <i class="ph ph-pencil-simple"></i> Edit Story
                                </a>
                                
                                <div class="flex items-center gap-3">
                                    <a href="teacher-programs.php?action=edit_interactive&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>&story_id=<?= (int)$story['story_id'] ?>" 
                                       class="text-purple-600 hover:opacity-75 text-sm font-medium inline-flex items-center gap-1">
                                        <i class="ph ph-chats"></i> Interactive
                                    </a>
                                    <button type="button" onclick="deleteStory(<?= (int)$story['story_id'] ?>)" 
                                            class="text-red-600 hover:opacity-75 text-sm font-medium inline-flex items-center gap-1">
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

    <!-- Quiz Section -->
    <?php 
        $quiz = ph_getChapterQuiz($conn, $chapterId);
    ?>
    <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start mt-8">
        <div class="w-full flex gap-[25px] items-center justify-between">
            <div class="flex items-center gap-[10px] p-[10px] text-company_orange">
                <i class="ph ph-question text-[24px]"></i>
                <p class="body-text2-semibold">Chapter Quiz</p>
                <?php if ($quiz): ?>
                    <span class="text-sm text-green-600 bg-green-50 px-2 py-1 rounded-full">
                        Quiz Available
                    </span>
                <?php endif; ?>
            </div>
            <a href="teacher-programs.php?action=add_quiz&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>" 
               class="px-4 py-2 bg-company_orange hover:opacity-90 text-white rounded-[10px] transition-opacity inline-flex items-center gap-2">
                <i class="ph ph-<?= $quiz ? 'pencil-simple' : 'plus' ?>"></i>
                <span class="body-text3-semibold"><?= $quiz ? 'Edit Quiz' : 'Add Quiz' ?></span>
            </a>
        </div>
        
        <?php if (!$quiz): ?>
            <div class="w-full text-center py-12 border border-dashed border-gray-300 rounded-[20px]">
                <i class="ph ph-question text-6xl text-gray-300 mb-4"></i>
                <h4 class="body-text2-semibold text-gray-500 mb-2">No Quiz Yet</h4>
                <p class="text-gray-400 mb-6">Create a quiz to test your students' understanding of this chapter.</p>
                <a href="teacher-programs.php?action=add_quiz&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>" 
                   class="px-6 py-3 bg-company_orange hover:opacity-90 text-white rounded-[10px] transition-opacity inline-flex items-center gap-2">
                    <i class="ph ph-plus"></i><span class="body-text3-semibold">Create Quiz</span>
                </a>
            </div>
        <?php else: ?>
            <div class="w-full bg-white border border-gray-200 rounded-[20px] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="body-text2-semibold text-company_blue"><?= htmlspecialchars($quiz['title']) ?></h4>
                    <span class="text-sm text-gray-500">Created: <?= date('M j, Y', strtotime($quiz['dateCreated'])) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span><i class="ph ph-calendar mr-1"></i> Updated: <?= date('M j, Y', strtotime($quiz['dateUpdated'])) ?></span>
                    </div>
                    <a href="teacher-programs.php?action=add_quiz&program_id=<?= $programId ?>&chapter_id=<?= $chapterId ?>" 
                       class="text-company_orange hover:opacity-75 font-medium">
                        <i class="ph ph-pencil-simple mr-1"></i> Edit Quiz
                    </a>
                </div>
            </div>
        <?php endif; ?>
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
        text: 'This will permanently delete this story and all its interactive sections.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting Story...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            const fd = new FormData();
            fd.append('action', 'delete_story');
            fd.append('story_id', storyId);
            
            fetch('../../php/program-handler.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Story has been deleted successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3b82f6'
                        }).then(() => { 
                            location.reload(); 
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to delete story.',
                            icon: 'error',
                            confirmButtonColor: '#3b82f6'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Network error. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#3b82f6'
                    });
                });
        }
    });
}
</script>

<style>
/* Al-Ghaya Legacy Styling */
.body-text2-semibold { font-weight: 600; font-size: 1rem; line-height: 1.5; }
.body-text3-semibold { font-weight: 600; font-size: 0.875rem; line-height: 1.25; }
.bg-company_white { background-color: #ffffff; }
.text-company_blue { color: #10375B; }
.text-company_orange { color: #F97316; }
.bg-company_blue { background-color: #10375B; }
.bg-company_orange { background-color: #F97316; }
.focus\:ring-company_blue:focus { --tw-ring-color: #10375B; }
.focus\:border-company_blue:focus { border-color: #10375B; }

/* Card hover effects */
.story-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Rounded corners consistency */
.rounded-\[40px\] { border-radius: 40px; }
.rounded-\[20px\] { border-radius: 20px; }
.rounded-\[10px\] { border-radius: 10px; }
.rounded-\[8px\] { border-radius: 8px; }

/* Content section styling */
.content-section { 
    padding: 2rem 1rem; 
    max-width: 1200px; 
    margin: 0 auto;
}

.section-title {
    color: #10375B;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

/* Grid gap consistency */
.gap-\[20px\] { gap: 20px; }
.gap-\[25px\] { gap: 25px; }
.gap-\[10px\] { gap: 10px; }

/* Padding consistency */
.p-\[20px\] { padding: 20px; }
.p-\[10px\] { padding: 10px; }
</style>