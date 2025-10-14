<!-- Story Form Component -->
<section class="content-section">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <button onclick="goBackToChapter()" class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="section-title text-2xl font-bold"><?= $story ? 'Edit Story' : 'Create Story' ?></h1>
        </div>
        <div class="text-sm text-gray-500">
            <?= htmlspecialchars($chapter['title'] ?? '') ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <form id="storyForm" method="POST" action="../../php/program-handler.php" class="space-y-8">
            <input type="hidden" name="action" value="<?= $story ? 'update_story' : 'create_story' ?>">
            <input type="hidden" name="program_id" value="<?= $program_id ?>">
            <input type="hidden" name="chapter_id" value="<?= $chapter_id ?>">
            <?php if ($story): ?>
                <input type="hidden" name="story_id" value="<?= $story['story_id'] ?>">
            <?php endif; ?>

            <!-- Story Title -->
            <div class="space-y-2">
                <label for="title" class="block text-sm font-medium text-gray-700">Story Title</label>
                <input type="text" id="title" name="title" required
                       value="<?= $story ? htmlspecialchars($story['title']) : '' ?>"
                       placeholder="e.g. Hadith"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Story Synopsis (Arabic) -->
            <div class="space-y-2">
                <label for="synopsis_arabic" class="block text-sm font-medium text-gray-700">Story Synopsis (Arabic)</label>
                <textarea id="synopsis_arabic" name="synopsis_arabic" rows="4" required
                          placeholder="في قلب مدينة نابضة بالحياة، حيث تنبض إيقاع الحياة اليومية عبر الشوارع المزدحمة..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 arabic-text"><?= $story ? htmlspecialchars($story['synopsis_arabic']) : '' ?></textarea>
            </div>

            <!-- Story Synopsis (English) -->
            <div class="space-y-2">
                <label for="synopsis_english" class="block text-sm font-medium text-gray-700">Story Synopsis (English)</label>
                <textarea id="synopsis_english" name="synopsis_english" rows="4" required
                          placeholder="In the heart of a vibrant city, where the rhythm of daily life pulsed through busy streets..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= $story ? htmlspecialchars($story['synopsis_english']) : '' ?></textarea>
            </div>

            <!-- Video Link -->
            <div class="space-y-2">
                <label for="video_url" class="block text-sm font-medium text-gray-700">Video link of the Story</label>
                <div class="relative">
                    <input type="url" id="video_url" name="video_url" required
                           value="<?= $story ? htmlspecialchars($story['video_url']) : '' ?>"
                           placeholder="https://youtube.com/..."
                           class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" onclick="validateVideoUrl()" 
                            class="absolute right-2 top-2 p-2 text-gray-400 hover:text-blue-600">
                        <i class="ph ph-check-circle text-xl"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-500">This video will be played for students to progress through the story</p>
            </div>

            <hr class="border-gray-200">

            <!-- Interactive Section -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-red-600">Interactive Section</h3>
                        <p class="text-sm text-gray-600">Minimum 1, Maximum 3 interactive sections per story</p>
                    </div>
                    <?php 
                    $interactiveSections = $story ? getStoryInteractiveSections($conn, $story['story_id']) : [];
                    $sectionCount = count($interactiveSections);
                    ?>
                    <button type="button" onclick="addInteractiveSection()" id="addSectionBtn"
                            class="<?= $sectionCount >= 3 ? 'opacity-50 cursor-not-allowed' : '' ?> bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                        <i class="ph ph-plus"></i>Add Section
                    </button>
                </div>

                <div id="interactiveSectionsContainer" class="space-y-6">
                    <?php if (empty($interactiveSections)): ?>
                        <div id="noSectionsMessage" class="text-center py-8 text-gray-500">
                            <i class="ph ph-chat-circle-dots text-4xl mb-4"></i>
                            <p>No interactive sections yet. Add at least one interactive section!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($interactiveSections as $sectionIndex => $section): ?>
                            <?php 
                            $questions = getSectionQuestions($conn, $section['section_id']);
                            $questionCount = count($questions);
                            ?>
                            <div class="interactive-section border-2 border-red-200 rounded-lg p-6" data-section-id="<?= $section['section_id'] ?>">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-medium text-red-800">Interactive Section <?= $section['section_order'] ?></h4>
                                    <button type="button" onclick="deleteSection(<?= $section['section_id'] ?>)" 
                                            class="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </div>
                                
                                <!-- Question Type Selection -->
                                <div class="mb-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" onclick="setQuestionType(<?= $section['section_id'] ?>, 'multiple_choice')" 
                                                class="question-type-btn multiple-choice px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors">
                                            <i class="ph ph-radio-button mr-2"></i>Multiple Choice
                                        </button>
                                        <button type="button" onclick="setQuestionType(<?= $section['section_id'] ?>, 'fill_in_blanks')" 
                                                class="question-type-btn fill-in-blanks px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors">
                                            <i class="ph ph-text-aa mr-2"></i>Fill-in-the-Blanks
                                        </button>
                                        <button type="button" onclick="setQuestionType(<?= $section['section_id'] ?>, 'multiple_select')" 
                                                class="question-type-btn multiple-select px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors">
                                            <i class="ph ph-checks mr-2"></i>Multiple Select
                                        </button>
                                    </div>
                                </div>

                                <!-- Questions Container -->
                                <div class="questions-container space-y-4" id="questions-<?= $section['section_id'] ?>">
                                    <?php if (empty($questions)): ?>
                                        <div class="question-placeholder text-center py-6 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                                            <p>Select a question type above and add your question</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($questions as $qIndex => $question): ?>
                                            <?php $options = getQuestionOptions($conn, $question['question_id']); ?>
                                            <div class="question-item bg-gray-50 p-4 rounded-lg border" data-question-id="<?= $question['question_id'] ?>">
                                                <div class="flex items-start justify-between mb-3">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2 mb-2">
                                                            <span class="text-sm font-medium text-gray-700">Question <?= $question['question_order'] ?></span>
                                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                                <?= ucfirst(str_replace('_', ' ', $question['question_type'])) ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-gray-700 mb-3"><?= htmlspecialchars($question['question_text']) ?></p>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button type="button" onclick="editQuestion(<?= $question['question_id'] ?>)" 
                                                                class="text-blue-500 hover:text-blue-700 p-1 rounded">
                                                            <i class="ph ph-pencil-simple"></i>
                                                        </button>
                                                        <button type="button" onclick="deleteQuestion(<?= $question['question_id'] ?>)" 
                                                                class="text-red-500 hover:text-red-700 p-1 rounded">
                                                            <i class="ph ph-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <!-- Options Display -->
                                                <?php if (!empty($options)): ?>
                                                    <div class="options-list space-y-2 mb-3">
                                                        <?php foreach ($options as $optIndex => $option): ?>
                                                            <div class="option-item flex items-center gap-2 p-2 rounded border
                                                                <?= $option['is_correct'] ? 'bg-green-100 border-green-300' : 'bg-white border-gray-200' ?>">
                                                                <?php if ($option['is_correct']): ?>
                                                                    <i class="ph ph-check-circle text-green-600"></i>
                                                                <?php else: ?>
                                                                    <i class="ph ph-circle text-gray-400"></i>
                                                                <?php endif; ?>
                                                                <span class="flex-1"><?= htmlspecialchars($option['option_text']) ?></span>
                                                                <?php if ($option['is_correct']): ?>
                                                                    <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded">Correct</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    
                                                    <div class="flex justify-center">
                                                        <button type="button" onclick="setAnswerKey(<?= $question['question_id'] ?>)" 
                                                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors inline-flex items-center gap-2">
                                                            <i class="ph ph-key"></i>Answer Key
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <p class="text-sm text-gray-500 mb-2">Question Left: <span class="font-medium"><?= $questionCount ?></span></p>
                                    <button type="button" onclick="addQuestion(<?= $section['section_id'] ?>)" 
                                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition-colors inline-flex items-center gap-2">
                                        <i class="ph ph-plus"></i>Add Question
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-center text-sm text-gray-500">
                    Interactive Sections: <?= $sectionCount ?> of 3
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <button type="button" onclick="cancelStoryForm()" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="saveStory()" 
                        class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors inline-flex items-center gap-2">
                    <i class="ph ph-floppy-disk"></i><?= $story ? 'Update Story' : 'Save Story' ?>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Question Modal -->
<div id="questionModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium">Add Question</h3>
                <button onclick="closeQuestionModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <form id="questionForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Question Text</label>
                    <textarea id="questionText" rows="3" required
                              placeholder="Based on the story, what significant reward is mentioned for someone who takes a path to seek knowledge?"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div id="optionsContainer" class="space-y-2">
                    <!-- Options will be added here dynamically -->
                </div>
                <button type="button" onclick="addOption()" id="addOptionBtn"
                        class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 transition-colors">
                    <i class="ph ph-plus mr-2"></i>Add Option
                </button>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeQuestionModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" onclick="saveQuestion()" 
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        Add Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const programId = <?= $program_id ?>;
const chapterId = <?= $chapter_id ?>;
const storyId = <?= $story ? $story['story_id'] : 'null' ?>;

let currentSectionId = null;
let currentQuestionType = null;
let sectionCount = <?= $sectionCount ?>;

function goBackToChapter() {
    Swal.fire({
        title: 'Go Back?',
        text: 'Any unsaved changes will be lost.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, go back',
        cancelButtonText: 'Stay here'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`;
        }
    });
}

function cancelStoryForm() {
    Swal.fire({
        title: 'Cancel Story?',
        text: 'Any unsaved changes will be lost.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, cancel',
        cancelButtonText: 'Keep editing'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`;
        }
    });
}

function saveStory() {
    // Validate required fields
    const title = document.getElementById('title').value.trim();
    const synopsisArabic = document.getElementById('synopsis_arabic').value.trim();
    const synopsisEnglish = document.getElementById('synopsis_english').value.trim();
    const videoUrl = document.getElementById('video_url').value.trim();
    
    if (!title || !synopsisArabic || !synopsisEnglish || !videoUrl) {
        Swal.fire({
            title: 'Missing Information',
            text: 'Please fill in all required fields.',
            icon: 'error',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    // Show loading
    Swal.fire({
        title: 'Saving Story...',
        text: 'Please wait while we save your story.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    document.getElementById('storyForm').submit();
}

function validateVideoUrl() {
    const url = document.getElementById('video_url').value.trim();
    if (!url) {
        Swal.fire({
            title: 'No URL',
            text: 'Please enter a YouTube URL first.',
            icon: 'info',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    // Simple YouTube URL validation
    const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/|v\/)|youtu\.be\/)([\w\-_]{11})(\S*)?$/;
    if (youtubeRegex.test(url)) {
        Swal.fire({
            title: 'Valid URL!',
            text: 'YouTube URL is valid.',
            icon: 'success',
            confirmButtonColor: '#3b82f6'
        });
    } else {
        Swal.fire({
            title: 'Invalid URL',
            text: 'Please enter a valid YouTube URL.',
            icon: 'error',
            confirmButtonColor: '#3b82f6'
        });
    }
}

function addInteractiveSection() {
    if (sectionCount >= 3) {
        Swal.fire({
            title: 'Maximum Sections Reached',
            text: 'Each story can have a maximum of 3 interactive sections.',
            icon: 'warning',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    if (!storyId) {
        Swal.fire({
            title: 'Save Story First',
            text: 'Please save the story before adding interactive sections.',
            icon: 'info',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    Swal.fire({
        title: 'Add Interactive Section',
        text: `Adding section ${sectionCount + 1} of 3. Interactive sections help engage students with questions.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Add Section',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Creating Section...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('../../php/program-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_interactive_section',
                    story_id: storyId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Section Added!',
                        text: 'Interactive section created successfully.',
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to create section.',
                        icon: 'error',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
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

function deleteSection(sectionId) {
    if (sectionCount <= 1) {
        Swal.fire({
            title: 'Cannot Delete',
            text: 'Each story must have at least 1 interactive section.',
            icon: 'warning',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    Swal.fire({
        title: 'Delete Section?',
        text: 'This will permanently delete the interactive section and all its questions.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Implementation for delete section
            location.reload();
        }
    });
}

function setQuestionType(sectionId, questionType) {
    currentSectionId = sectionId;
    currentQuestionType = questionType;
    
    // Update button styles
    const section = document.querySelector(`[data-section-id="${sectionId}"]`);
    const buttons = section.querySelectorAll('.question-type-btn');
    buttons.forEach(btn => {
        btn.classList.remove('bg-blue-500', 'text-white');
        btn.classList.add('border-gray-300');
    });
    
    const activeBtn = section.querySelector(`.${questionType.replace('_', '-')}`);
    if (activeBtn) {
        activeBtn.classList.add('bg-blue-500', 'text-white');
        activeBtn.classList.remove('border-gray-300');
    }
    
    Swal.fire({
        title: 'Question Type Selected',
        text: `Selected: ${questionType.replace('_', ' ').toUpperCase()}`,
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
}

function addQuestion(sectionId) {
    if (!currentQuestionType) {
        Swal.fire({
            title: 'Select Question Type',
            text: 'Please select a question type first.',
            icon: 'info',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    currentSectionId = sectionId;
    document.getElementById('questionModal').classList.remove('hidden');
    document.getElementById('optionsContainer').innerHTML = '';
    document.getElementById('questionText').value = '';
    
    // Add default options based on question type
    if (currentQuestionType === 'multiple_choice' || currentQuestionType === 'multiple_select') {
        addOption();
        addOption();
    }
}

function addOption() {
    const container = document.getElementById('optionsContainer');
    const optionCount = container.children.length + 1;
    const optionHtml = `
        <div class="option-input flex items-center gap-2">
            <span class="text-sm text-gray-500 w-8">${optionCount}.</span>
            <input type="text" placeholder="They will become wealthy and famous" required
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <button type="button" onclick="removeOption(this)" class="text-red-500 hover:text-red-700 p-1">
                <i class="ph ph-x"></i>
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', optionHtml);
}

function removeOption(button) {
    button.closest('.option-input').remove();
    // Renumber remaining options
    const options = document.querySelectorAll('.option-input');
    options.forEach((option, index) => {
        option.querySelector('span').textContent = (index + 1) + '.';
    });
}

function closeQuestionModal() {
    document.getElementById('questionModal').classList.add('hidden');
}

function saveQuestion() {
    const questionText = document.getElementById('questionText').value.trim();
    const optionInputs = document.querySelectorAll('.option-input input');
    const options = Array.from(optionInputs).map(input => input.value.trim()).filter(val => val);
    
    if (!questionText) {
        Swal.fire({
            title: 'Missing Question',
            text: 'Please enter a question.',
            icon: 'error',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    if ((currentQuestionType === 'multiple_choice' || currentQuestionType === 'multiple_select') && options.length < 2) {
        Swal.fire({
            title: 'Insufficient Options',
            text: 'Please add at least 2 options.',
            icon: 'error',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    // Show loading
    Swal.fire({
        title: 'Adding Question...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Implementation would go here
    setTimeout(() => {
        closeQuestionModal();
        Swal.fire({
            title: 'Question Added!',
            text: 'Question has been added successfully.',
            icon: 'success',
            confirmButtonColor: '#3b82f6'
        });
    }, 1000);
}

function setAnswerKey(questionId) {
    Swal.fire({
        title: 'Set Answer Key',
        text: 'Which option is the correct answer?',
        input: 'select',
        inputOptions: {
            '1': 'Option 1',
            '2': 'Option 2',
            '3': 'Option 3',
            '4': 'Option 4'
        },
        inputPlaceholder: 'Select correct answer',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        inputValidator: (value) => {
            if (!value) {
                return 'Please select the correct answer!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Answer Key Set!',
                text: `Option ${result.value} is now marked as correct.`,
                icon: 'success',
                confirmButtonColor: '#3b82f6'
            });
        }
    });
}

function deleteQuestion(questionId) {
    Swal.fire({
        title: 'Delete Question?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleted!',
                text: 'Question has been deleted.',
                icon: 'success',
                confirmButtonColor: '#3b82f6'
            });
        }
    });
}

function editQuestion(questionId) {
    // Implementation for edit question
    Swal.fire({
        title: 'Edit Question',
        text: 'Question editing functionality coming soon.',
        icon: 'info',
        confirmButtonColor: '#3b82f6'
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Update section controls
    const addSectionBtn = document.getElementById('addSectionBtn');
    if (sectionCount >= 3) {
        addSectionBtn.disabled = true;
        addSectionBtn.innerHTML = '<i class="ph ph-check"></i>Maximum Sections';
    }
});
</script>

<style>
.arabic-text {
    direction: rtl;
    text-align: right;
    font-family: 'Noto Naskh Arabic', 'Times New Roman', serif;
}

.interactive-section {
    position: relative;
    background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
    border-color: #f87171;
}

.question-item {
    background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
}

.option-item {
    transition: all 0.2s ease;
}

.option-item:hover {
    transform: translateX(2px);
}

/* Modal backdrop */
#questionModal {
    backdrop-filter: blur(2px);
}

/* Button states */
button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.question-type-btn {
    transition: all 0.2s ease;
    border: 1px solid #d1d5db;
}

.question-type-btn:hover:not(.bg-blue-500) {
    border-color: #9ca3af;
    background-color: #f9fafb;
}

.question-type-btn.bg-blue-500 {
    border-color: #3b82f6;
}
</style>