<!-- Story Form Component (refactored to use canonical functions and correct request fields) -->
<?php
require_once __DIR__ . '/../php/program-core.php';

// Ensure required vars exist from parent page
$programId = isset($program_id) ? (int)$program_id : 0;
$chapterId = isset($chapter_id) ? (int)$chapter_id : 0;
$storyData = $story ?? null;
?>
<section class="content-section">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <button onclick="goBackToChapter()" class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="section-title text-2xl font-bold"><?= $storyData ? 'Edit Story' : 'Create Story' ?></h1>
        </div>
        <div class="text-sm text-gray-500">
            <?= htmlspecialchars($chapter['title'] ?? '') ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <form id="storyForm" method="POST" action="../../php/program-core.php" class="space-y-8">
            <input type="hidden" name="action" value="<?= $storyData ? 'update_story' : 'create_story' ?>">
            <!-- Align names to program-handler expected keys -->
            <input type="hidden" name="programID" value="<?= $programId ?>">
            <input type="hidden" name="chapter_id" value="<?= $chapterId ?>">
            <?php if ($storyData): ?>
                <input type="hidden" name="story_id" value="<?= (int)$storyData['story_id'] ?>">
            <?php endif; ?>

            <!-- Story Title -->
            <div class="space-y-2">
                <label for="title" class="block text-sm font-medium text-gray-700">Story Title</label>
                <input type="text" id="title" name="title" required
                       value="<?= $storyData ? htmlspecialchars($storyData['title']) : '' ?>"
                       placeholder="e.g. Hadith"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Story Synopsis (Arabic) -->
            <div class="space-y-2">
                <label for="synopsis_arabic" class="block text-sm font-medium text-gray-700">Story Synopsis (Arabic)</label>
                <textarea id="synopsis_arabic" name="synopsis_arabic" rows="4" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 arabic-text"><?= $storyData ? htmlspecialchars($storyData['synopsis_arabic']) : '' ?></textarea>
            </div>

            <!-- Story Synopsis (English) -->
            <div class="space-y-2">
                <label for="synopsis_english" class="block text-sm font-medium text-gray-700">Story Synopsis (English)</label>
                <textarea id="synopsis_english" name="synopsis_english" rows="4" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= $storyData ? htmlspecialchars($storyData['synopsis_english']) : '' ?></textarea>
            </div>

            <!-- Video Link -->
            <div class="space-y-2">
                <label for="video_url" class="block text-sm font-medium text-gray-700">Video link of the Story</label>
                <div class="relative">
                    <input type="url" id="video_url" name="video_url" required
                           value="<?= $storyData ? htmlspecialchars($storyData['video_url']) : '' ?>"
                           placeholder="https://youtube.com/..."
                           class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" onclick="validateVideoUrl()" 
                            class="absolute right-2 top-2 p-2 text-gray-400 hover:text-blue-600">
                        <i class="ph ph-check-circle text-xl"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-500">This video will be played for students to progress through the story</p>
            </div>

            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <button type="button" onclick="cancelStoryForm()" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="saveStory()" 
                        class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors inline-flex items-center gap-2">
                    <i class="ph ph-floppy-disk"></i><?= $storyData ? 'Update Story' : 'Save Story' ?>
                </button>
            </div>
        </form>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const programId = <?= (int)$programId ?>;
const chapterId = <?= (int)$chapterId ?>;

function goBackToChapter(){ window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`; }
function cancelStoryForm(){ goBackToChapter(); }

function saveStory(){
  const title=document.getElementById('title').value.trim();
  const ar=document.getElementById('synopsis_arabic').value.trim();
  const en=document.getElementById('synopsis_english').value.trim();
  const url=document.getElementById('video_url').value.trim();
  if(!title||!ar||!en||!url){ Swal.fire({title:'Missing Information',text:'Please fill in all required fields.',icon:'error'}); return; }
  Swal.fire({title:'Saving Story...',allowOutsideClick:false,allowEscapeKey:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});
  document.getElementById('storyForm').submit();
}

function validateVideoUrl(){
  const url=document.getElementById('video_url').value.trim();
  const re=/^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/|v\/)|youtu\.be\/)([\w\-_]{11})(\S*)?$/;
  Swal.fire({title: re.test(url)?'Valid URL!':'Invalid URL', icon: re.test(url)?'success':'error'});
}
</script>

<style>
.arabic-text { direction: rtl; text-align: right; font-family: 'Noto Naskh Arabic','Times New Roman',serif; }
</style>
