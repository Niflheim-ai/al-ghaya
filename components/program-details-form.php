<!-- Program Details Form Component -->
<section class="content-section">
    <input type="hidden" id="programID" value="<?= isset($program['programID']) ? (int)$program['programID'] : (isset($program_id) ? (int)$program_id : 0) ?>">
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
                    <?= ($program['status'] ?? 'draft') === 'published' ? 'bg-green-100 text-green-800' : 
                       (($program['status'] ?? 'draft') === 'pending_review' ? 'bg-blue-100 text-blue-800' : 
                       (($program['status'] ?? 'draft') === 'archived' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800')) ?>">
                    <?= ucfirst(str_replace('_', ' ', $program['status'] ?? 'draft')) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <form id="programDetailsForm" method="POST" action="../../php/program-core.php" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="action" value="<?= $program ? 'update_program' : 'create_program' ?>">
            <?php if ($program): ?>
                <input type="hidden" name="programID" value="<?= (int)$program['programID'] ?>">
            <?php endif; ?>
            <input type="hidden" name="teacher_id" value="<?= $teacher_id ?>">

            <!-- Thumbnail Upload -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Thumbnail</h3>
                <div class="flex flex-col items-center gap-6 p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 transition-colors">
                    <div class="thumbnail-preview w-full max-w-md">
                        <img id="thumbnailPreview" 
                             src="<?= $program && $program['thumbnail'] ? '../../uploads/thumbnails/' . $program['thumbnail'] : '../../images/default-program.jpg' ?>" 
                             alt="Program Thumbnail" 
                             class="w-full aspect-video object-cover rounded-lg border border-gray-200">
                    </div>
                    <div class="text-center">
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/*" 
                               class="hidden" onchange="previewThumbnail(this)">
                        <label for="thumbnail" 
                               class="cursor-pointer bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition-colors inline-flex items-center gap-2">
                            <i class="ph ph-upload-simple text-xl"></i>
                            Upload Image
                        </label>
                        <p class="text-sm text-gray-500 mt-2">Recommended: 500 x 400px, JPG/PNG</p>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200">

            <!-- Program Title -->
            <div class="space-y-2">
                <label for="title" class="block text-sm font-medium text-gray-700">Program Title</label>
                <input type="text" id="title" name="title" required
                       value="<?= $program ? htmlspecialchars($program['title']) : '' ?>"
                       placeholder="e.g. Hadith (حديث)"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Program Description -->
            <div class="space-y-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Program Description</label>
                <textarea id="description" name="description" rows="4" required
                          placeholder="Describe your program..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= $program ? htmlspecialchars($program['description']) : '' ?></textarea>
            </div>

            <!-- Difficulty Level -->
            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700">Difficulty</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="difficulty_level" value="Student" 
                               <?= (!$program || ($program['difficulty_label'] ?? 'Student') === 'Student') ? 'checked' : '' ?>
                               class="sr-only" required>
                        <div class="difficulty-card student-difficulty p-4 border-2 border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="ph-fill ph-barbell text-2xl text-gray-600"></i>
                                <div>
                                    <h4 class="font-semibold">Student Difficulty</h4>
                                    <p class="text-sm text-gray-600">Basic level content</p>
                                </div>
                            </div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="difficulty_level" value="Aspiring" 
                               <?= ($program && ($program['difficulty_label'] ?? '') === 'Aspiring') ? 'checked' : '' ?>
                               class="sr-only">
                        <div class="difficulty-card aspiring-difficulty p-4 border-2 border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="ph-fill ph-barbell text-2xl text-blue-600"></i>
                                <div>
                                    <h4 class="font-semibold">Aspiring Difficulty</h4>
                                    <p class="text-sm text-gray-600">Intermediate level content</p>
                                </div>
                            </div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="difficulty_level" value="Master" 
                               <?= ($program && ($program['difficulty_label'] ?? '') === 'Master') ? 'checked' : '' ?>
                               class="sr-only">
                        <div class="difficulty-card master-difficulty p-4 border-2 border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="ph-fill ph-barbell text-2xl text-yellow-600"></i>
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
                       value="<?= $program ? htmlspecialchars($program['overview_video_url'] ?? '') : '' ?>"
                       placeholder="https://youtube.com/..."
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-sm text-gray-500">A YouTube video to be displayed in the program overview</p>
            </div>

            <hr class="border-gray-200">

            <!-- Chapters Section -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Chapters</h3>
                    <?php if ($program): ?>
                        <button type="button" id="addChapterBtn" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                            <i class="ph ph-plus"></i>Add Chapter
                        </button>
                    <?php endif; ?>
                </div>
                
                <div id="chaptersContainer" class="space-y-4">
                    <?php 
                    $chapters = $program ? getProgramChapters($conn, $program['programID']) : [];
                    if (empty($chapters)): 
                    ?>
                        <div id="no-chapters-message" class="text-center py-8 text-gray-500">
                            <i class="ph ph-book text-4xl mb-4"></i>
                            <p>No chapters yet. <?= $program ? 'Add your first chapter to get started!' : 'Save the program first to add chapters.' ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($chapters as $index => $chapter): ?>
                            <div class="chapter-item flex items-center justify-between bg-gray-50 rounded-lg p-4 border border-gray-200" data-chapter-id="<?= $chapter['chapter_id'] ?>">
                                <div class="flex items-center gap-3">
                                    <i class="ph ph-book text-xl text-gray-600"></i>
                                    <span class="font-medium"><?= htmlspecialchars($chapter['title']) ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="editChapter(<?= $program['programID'] ?>, <?= $chapter['chapter_id'] ?>)" 
                                            class="text-blue-500 hover:text-blue-700 p-2 rounded hover:bg-blue-50 transition-colors">
                                        <i class="ph ph-pencil-simple"></i>
                                    </button>
                                    <button type="button" onclick="deleteChapter(<?= $program['programID'] ?>, <?= $chapter['chapter_id'] ?>)" 
                                            class="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50 transition-colors">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="border-gray-200">

            <!-- Save Actions: only Save as Draft here -->
            <div class="flex justify-between items-center">
                <button type="button" onclick="cancelForm()" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <div class="flex gap-3">
                    <button type="button" onclick="saveProgram('draft')" 
                            class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors inline-flex items-center gap-2">
                        <i class="ph ph-floppy-disk"></i>Save as Draft
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Add Chapter Modal -->
<div id="addChapterModal" class="hidden fixed inset-0 z-50 bg-gray-900 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">Add New Chapter</h3>
            <button onclick="hideAddChapterModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <input type="text" id="newChapterTitle" placeholder="Enter chapter title" 
               class="w-full px-4 py-3 border border-gray-300 rounded-lg mb-4 focus:ring-2 focus:ring-blue-500">
        <div class="flex justify-end gap-3">
            <button type="button" onclick="hideAddChapterModal()" 
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button type="button" onclick="submitNewChapter()" 
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                Add Chapter
            </button>
        </div>
    </div>
</div>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const programId = Number(document.getElementById('programID')?.value || 0);

// Form handling functions
function previewThumbnail(input) { if (input.files && input.files[0]) { const reader = new FileReader(); reader.onload = e => { document.getElementById('thumbnailPreview').src = e.target.result; }; reader.readAsDataURL(input.files[0]); } }
function goBack() { window.location.href = 'teacher-programs.php'; }
function cancelForm() { Swal.fire({ title:'Cancel Changes?', text:'Any unsaved changes will be lost.', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', cancelButtonColor:'#6b7280', confirmButtonText:'Yes, cancel', cancelButtonText:'Keep editing' }).then(r=>{ if(r.isConfirmed){ window.location.href='teacher-programs.php'; } }); }
function saveProgram(status) {const form = document.getElementById('programDetailsForm'); const s = document.createElement('input'); s.type = 'hidden'; s.name = 'status'; s.value = (status && typeof status === 'string' && status.trim().length) ? status : 'draft'; form.appendChild(s); if (typeof Swal !== 'undefined') {Swal.fire({title:'Saving Program...', allowOutsideClick:false, allowEscapeKey:false, showConfirmButton:false, didOpen:()=>Swal.showLoading()});}form.submit();}

function showAddChapterModal() {
    if (!programId) { Swal.fire({ title:'Save Program First', text:'Please save the program before adding chapters.', icon:'info', confirmButtonColor:'#3b82f6' }); return; }
    document.getElementById('addChapterModal').classList.remove('hidden');
    document.getElementById('newChapterTitle').focus();
}
function hideAddChapterModal() { document.getElementById('addChapterModal').classList.add('hidden'); document.getElementById('newChapterTitle').value=''; }

// Ensure Add Chapter button always sends programID
const addBtn = document.getElementById('addChapterBtn');
if (addBtn) { addBtn.addEventListener('click', showAddChapterModal); }

function submitNewChapter() {
    const title = document.getElementById('newChapterTitle').value.trim();
    if (!title) { Swal.fire({ title:'Missing Title', text:'Please enter a chapter title.', icon:'error', confirmButtonColor:'#3b82f6' }); return; }
    if (!programId) { Swal.fire({ title:'Program Not Found', text:'Please save the program first.', icon:'error', confirmButtonColor:'#3b82f6' }); return; }
    Swal.fire({ title:'Creating Chapter...', allowOutsideClick:false, allowEscapeKey:false, showConfirmButton:false, didOpen:()=>Swal.showLoading() });
    const fd = new FormData(); fd.append('action','create_chapter'); fd.append('programID', String(programId)); fd.append('title', title);
    fetch('../../php/program-core.php', { method:'POST', body: fd })
    .then(r=>r.json())
    .then(data=>{ if (data.success) { Swal.fire({ title:'Chapter Created!', text:'Redirecting to content...', icon:'success', confirmButtonColor:'#3b82f6' }).then(()=>{ window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${data.chapter_id}`; }); } else { Swal.fire({ title:'Error', text:data.message||'Failed to create chapter.', icon:'error', confirmButtonColor:'#3b82f6' }); } })
    .catch(()=> Swal.fire({ title:'Error', text:'Network error. Please try again.', icon:'error', confirmButtonColor:'#3b82f6' }));
}

// Difficulty selection styling
document.addEventListener('DOMContentLoaded', function() {
    const difficultyInputs = document.querySelectorAll('input[name="difficulty_level"]');
    difficultyInputs.forEach(input => { input.addEventListener('change', function() { document.querySelectorAll('.difficulty-card').forEach(card => { card.classList.remove('border-blue-500','bg-blue-50'); card.classList.add('border-gray-200'); }); if (this.checked) { const card=this.nextElementSibling; card.classList.remove('border-gray-200'); card.classList.add('border-blue-500','bg-blue-50'); } }); if (input.checked) { const card=input.nextElementSibling; card.classList.remove('border-gray-200'); card.classList.add('border-blue-500','bg-blue-50'); } });
    const t=document.getElementById('newChapterTitle'); if(t){ t.addEventListener('keypress', e=>{ if(e.key==='Enter'){ submitNewChapter(); } }); }
});

function editChapter(programId, chapterId) {
    window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`;
}

function deleteChapter(programId, chapterId) {
    Swal.fire({
        title: 'Delete Chapter?',
        text: 'This will permanently delete the chapter and all its content.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'delete_chapter');
            fd.append('chapter_id', chapterId);
            fetch('../../php/program-core.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`[data-chapter-id="${chapterId}"]`).remove();
                    Swal.fire('Deleted!', 'Chapter has been deleted.', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Failed to delete chapter.', 'error');
                }
            });
        }
    });
}
</script>

<style>
.difficulty-card.student-difficulty { color: #374151; }
.difficulty-card.aspiring-difficulty { color: #2563eb; }
.difficulty-card.master-difficulty { color: #d97706; }
.thumbnail-preview img { transition: transform 0.2s; }
.thumbnail-preview:hover img { transform: scale(1.02); }
#addChapterModal { backdrop-filter: blur(2px); }
</style>
