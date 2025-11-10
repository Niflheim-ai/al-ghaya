<!-- Story Form Component (refactored to use canonical functions and correct request fields) -->
<?php
require_once __DIR__ . '/../php/program-core.php';

// Ensure required vars exist from parent page
$programId = isset($program_id) ? (int)$program_id : 0;
$chapterId = isset($chapter_id) ? (int)$chapter_id : 0;
$storyData = $story ?? null;
$storyId = isset($storyData['story_id']) ? (int)$storyData['story_id'] : 0;

// Get existing interactive sections if editing
if ($storyId > 0) {
    require_once __DIR__ . '/../php/quiz-handler.php';
    $interactiveSections = interactiveSection_getByStory($conn, $storyId);
} else {
    $interactiveSections = [];
}
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

            <!-- Interactive Sections Management -->
            <div class="space-y-4 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Interactive Sections</h3>
                        <p class="text-sm text-gray-500 mt-1">Create 1-3 interactive multiple choice questions for this story</p>
                    </div>
                    <button type="button" onclick="addInteractiveSection()" id="addSectionBtn"
                            class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-colors inline-flex items-center gap-2"
                            <?= count($interactiveSections) >= 3 ? 'disabled' : '' ?>>
                        <i class="ph ph-plus"></i>Add Section
                    </button>
                </div>
                
                <div id="interactiveSectionsContainer" class="space-y-4">
                    <?php if (empty($interactiveSections)): ?>
                        <div id="noSectionsPlaceholder" class="text-center py-8 text-gray-400 border-2 border-dashed border-gray-300 rounded-lg">
                            <i class="ph ph-chat-circle-dots text-4xl mb-2"></i>
                            <p class="text-sm">No interactive sections yet. Click "Add Section" to create one.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($interactiveSections as $index => $section): ?>
                            <div class="interactive-section border border-gray-200 rounded-lg p-6 bg-gray-50" data-section-index="<?= $index ?>">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <i class="ph ph-chat-circle-dots text-xl text-purple-600"></i>
                                        <h4 class="font-medium text-gray-900">Interactive Section <?= $index + 1 ?></h4>
                                    </div>
                                    <button type="button" onclick="removeInteractiveSection(<?= $index ?>)" 
                                            class="text-red-500 hover:text-red-700 p-1">
                                        <i class="ph ph-trash text-xl"></i>
                                    </button>
                                </div>
                                
                                <input type="hidden" name="sections[<?= $index ?>][section_id]" value="<?= (int)$section['section_id'] ?>">
                                <input type="hidden" name="sections[<?= $index ?>][section_order]" value="<?= $index + 1 ?>">
                                
                                <!-- Questions for this section -->
                                <div class="space-y-4">
                                    <?php if (!empty($section['questions'])): ?>
                                        <?php foreach ($section['questions'] as $qIndex => $question): ?>
                                            <div class="question-item bg-white border border-gray-200 rounded-lg p-4">
                                                <div class="space-y-3">
                                                    <label class="block text-sm font-medium text-gray-700">Question</label>
                                                    <input type="text" 
                                                           name="sections[<?= $index ?>][questions][<?= $qIndex ?>][text]" 
                                                           value="<?= htmlspecialchars($question['question_text']) ?>"
                                                           placeholder="Enter your question"
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                                                    
                                                    <!-- Options -->
                                                    <div class="space-y-2 pl-4">
                                                        <p class="text-xs text-gray-500 mb-2">Check the correct answer(s):</p>
                                                        <?php if (!empty($question['options'])): ?>
                                                            <?php foreach ($question['options'] as $oIndex => $option): ?>
                                                                <div class="flex items-center gap-2">
                                                                    <input type="checkbox" 
                                                                           name="sections[<?= $index ?>][questions][<?= $qIndex ?>][options][<?= $oIndex ?>][is_correct]"
                                                                           <?= $option['is_correct'] ? 'checked' : '' ?>
                                                                           value="1"
                                                                           class="rounded text-green-500">
                                                                    <input type="text" 
                                                                           name="sections[<?= $index ?>][questions][<?= $qIndex ?>][options][<?= $oIndex ?>][text]"
                                                                           value="<?= htmlspecialchars($option['option_text']) ?>"
                                                                           placeholder="Option <?= $oIndex + 1 ?>"
                                                                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg" required>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500 italic">No questions yet for this section.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
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
let sectionCounter = <?= count($interactiveSections) ?>;
const maxSections = 3;

function goBackToChapter(){ window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`; }
function cancelStoryForm(){ goBackToChapter(); }

function addInteractiveSection() {
    if (sectionCounter >= maxSections) {
        Swal.fire({
            title: 'Maximum Sections Reached',
            text: `You can only add up to ${maxSections} interactive sections per story.`,
            icon: 'warning'
        });
        return;
    }
    
    const container = document.getElementById('interactiveSectionsContainer');
    const placeholder = document.getElementById('noSectionsPlaceholder');
    if (placeholder) placeholder.remove();
    
    const sectionIndex = sectionCounter;
    const sectionHTML = `
        <div class="interactive-section border border-gray-200 rounded-lg p-6 bg-gray-50" data-section-index="${sectionIndex}">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i class="ph ph-chat-circle-dots text-xl text-purple-600"></i>
                    <h4 class="font-medium text-gray-900">Interactive Section ${sectionIndex + 1}</h4>
                </div>
                <button type="button" onclick="removeInteractiveSection(${sectionIndex})" 
                        class="text-red-500 hover:text-red-700 p-1">
                    <i class="ph ph-trash text-xl"></i>
                </button>
            </div>
            
            <input type="hidden" name="sections[${sectionIndex}][section_order]" value="${sectionIndex + 1}">
            
            <!-- Single Question Template -->
            <div class="space-y-4">
                <div class="question-item bg-white border border-gray-200 rounded-lg p-4">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-gray-700">Question</label>
                        <input type="text" 
                               name="sections[${sectionIndex}][questions][0][text]" 
                               placeholder="Enter your question"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                        
                        <!-- Options -->
                        <div class="space-y-2 pl-4">
                            <p class="text-xs text-gray-500 mb-2">Check the correct answer(s):</p>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" 
                                       name="sections[${sectionIndex}][questions][0][options][0][is_correct]"
                                       value="1"
                                       class="rounded text-green-500">
                                <input type="text" 
                                       name="sections[${sectionIndex}][questions][0][options][0][text]"
                                       placeholder="Option 1"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg" required>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" 
                                       name="sections[${sectionIndex}][questions][0][options][1][is_correct]"
                                       value="1"
                                       class="rounded text-green-500">
                                <input type="text" 
                                       name="sections[${sectionIndex}][questions][0][options][1][text]"
                                       placeholder="Option 2"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg" required>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" 
                                       name="sections[${sectionIndex}][questions][0][options][2][is_correct]"
                                       value="1"
                                       class="rounded text-green-500">
                                <input type="text" 
                                       name="sections[${sectionIndex}][questions][0][options][2][text]"
                                       placeholder="Option 3"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg" required>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" 
                                       name="sections[${sectionIndex}][questions][0][options][3][is_correct]"
                                       value="1"
                                       class="rounded text-green-500">
                                <input type="text" 
                                       name="sections[${sectionIndex}][questions][0][options][3][text]"
                                       placeholder="Option 4"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', sectionHTML);
    sectionCounter++;
    updateAddButton();
}

function removeInteractiveSection(sectionIndex) {
    Swal.fire({
        title: 'Remove Section?',
        text: 'This will remove this interactive section from the story.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, remove it'
    }).then((result) => {
        if (result.isConfirmed) {
            const section = document.querySelector(`[data-section-index="${sectionIndex}"]`);
            if (section) section.remove();
            
            sectionCounter--;
            updateAddButton();
            
            // Show placeholder if no sections
            const container = document.getElementById('interactiveSectionsContainer');
            if (container.children.length === 0) {
                container.innerHTML = `
                    <div id="noSectionsPlaceholder" class="text-center py-8 text-gray-400 border-2 border-dashed border-gray-300 rounded-lg">
                        <i class="ph ph-chat-circle-dots text-4xl mb-2"></i>
                        <p class="text-sm">No interactive sections yet. Click "Add Section" to create one.</p>
                    </div>
                `;
            }
        }
    });
}

function updateAddButton() {
    const btn = document.getElementById('addSectionBtn');
    if (sectionCounter >= maxSections) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function saveStory(){
    const title=document.getElementById('title').value.trim();
    const ar=document.getElementById('synopsis_arabic').value.trim();
    const en=document.getElementById('synopsis_english').value.trim();
    const url=document.getElementById('video_url').value.trim();
    
    if(!title||!ar||!en||!url){ 
        Swal.fire({title:'Missing Information',text:'Please fill in all required fields.',icon:'error'}); 
        return; 
    }
    
    // Validate at least 1 interactive section
    const sections = document.querySelectorAll('.interactive-section');
    if (sections.length === 0) {
        Swal.fire({title:'Missing Interactive Section',text:'Please add at least 1 interactive section to the story.',icon:'error'});
        return;
    }
    
    // Validate each section has question and options filled
    let isValid = true;
    sections.forEach((section, idx) => {
        const questionInput = section.querySelector('input[name*="[questions][0][text]"]');
        if (!questionInput || !questionInput.value.trim()) {
            Swal.fire({title:'Missing Question',text:`Interactive Section ${idx + 1} needs a question.`,icon:'error'});
            isValid = false;
            return;
        }
        
        const optionInputs = section.querySelectorAll('input[name*="[options]"][name*="[text]"]');
        let hasCorrectAnswer = false;
        let allOptionsFilled = true;
        
        optionInputs.forEach(optInput => {
            if (!optInput.value.trim()) {
                allOptionsFilled = false;
            }
            const checkbox = optInput.parentElement.querySelector('input[type="checkbox"]');
            if (checkbox && checkbox.checked) {
                hasCorrectAnswer = true;
            }
        });
        
        if (!allOptionsFilled) {
            Swal.fire({title:'Missing Options',text:`Interactive Section ${idx + 1} needs all options filled.`,icon:'error'});
            isValid = false;
            return;
        }
        
        if (!hasCorrectAnswer) {
            Swal.fire({title:'Missing Correct Answer',text:`Interactive Section ${idx + 1} needs at least one correct answer marked.`,icon:'error'});
            isValid = false;
            return;
        }
    });
    
    if (!isValid) return;
    
    Swal.fire({title:'Saving Story...',allowOutsideClick:false,allowEscapeKey:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});
    document.getElementById('storyForm').submit();
}

function validateVideoUrl(){
    const url=document.getElementById('video_url').value.trim();
    const re=/^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/|v\/)|youtu\.be\/)([\w\-_]{11})(\S*)?$/;
    Swal.fire({title: re.test(url)?'Valid URL!':'Invalid URL', icon: re.test(url)?'success':'error'});
}

// Initialize
updateAddButton();
</script>

<style>
.arabic-text { direction: rtl; text-align: right; font-family: 'Noto Naskh Arabic','Times New Roman',serif; }
.interactive-section { transition: all 0.2s ease; }
.interactive-section:hover { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
</style>