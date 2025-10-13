<!-- Story Form Component -->
<section class="content-section">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <button onclick="goBackToChapter()" class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="section-title text-2xl font-bold"><?= $story ? 'Edit Story' : 'Add Story' ?></h1>
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
                       placeholder="e.g. [translate:الحج]"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Story Synopsis (Arabic) -->
            <div class="space-y-2">
                <label for="synopsis_arabic" class="block text-sm font-medium text-gray-700">Story Synopsis (Arabic)</label>
                <textarea id="synopsis_arabic" name="synopsis_arabic" rows="4" required
                          placeholder="[translate:في قلب مدينة نابضة بالحياة، حيث تنبض إيقاع الحياة اليومية عبر الشوارع المزدحمة...]"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= $story ? htmlspecialchars($story['synopsis_arabic']) : '' ?></textarea>
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
                <input type="url" id="video_url" name="video_url" required
                       value="<?= $story ? htmlspecialchars($story['video_url']) : '' ?>"
                       placeholder="https://youtube.com/..."
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                    <button type="button" onclick="addInteractiveSection()" id="addSectionBtn"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="ph ph-plus mr-2"></i>Add Section
                    </button>
                </div>

                <div id="interactiveSectionsContainer" class="space-y-6">
                    <?php 
                    $interactiveSections = $story ? getStoryInteractiveSections($conn, $story['story_id']) : [];
                    if (empty($interactiveSections)): 
                    ?>
                        <div id="noSectionsMessage" class="text-center py-8 text-gray-500">
                            <i class="ph ph-chat-circle-dots text-4xl mb-4"></i>
                            <p>No interactive sections yet. Add at least one interactive section!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($interactiveSections as $section): ?>
                            <?php 
                            $questions = getSectionQuestions($conn, $section['section_id']);
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
                                    <div class="flex gap-4">
                                        <button type="button" onclick="setQuestionType(<?= $section['section_id'] ?>, 'multiple_choice')" 
                                                class="question-type-btn multiple-choice px-4 py-2 border rounded-lg hover:bg-gray-50">
                                            <i class="ph ph-radio-button mr-2"></i>Multiple Choice
                                        </button>
                                        <button type="button" onclick="setQuestionType(<?= $section['section_id'] ?>, 'fill_in_blanks')" 
                                                class="question-type-btn fill-blanks px-4 py-2 border rounded-lg hover:bg-gray-50">
                                            <i class="ph ph-text-aa mr-2"></i>Fill-in-the-Blanks
                                        </button>
                                        <button type="button" onclick="setQuestionType(<?= $section['section_id'] ?>, 'multiple_select')" 
                                                class="question-type-btn multiple-select px-4 py-2 border rounded-lg hover:bg-gray-50">
                                            <i class="ph ph-checks mr-2"></i>Multiple Select
                                        </button>
                                    </div>
                                </div>

                                <!-- Questions Container -->
                                <div class="questions-container space-y-4">
                                    <?php if (empty($questions)): ?>
                                        <div class="question-placeholder text-center py-4 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                                            <p>Select a question type above and add your question</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($questions as $question): ?>
                                            <?php $options = getQuestionOptions($conn, $question['question_id']); ?>
                                            <div class="question-item bg-gray-50 p-4 rounded-lg" data-question-id="<?= $question['question_id'] ?>">
                                                <div class="flex items-start justify-between mb-3">
                                                    <div class="flex-1">
                                                        <h5 class="font-medium mb-2">Question <?= $question['question_order'] ?></h5>
                                                        <p class="text-gray-700 mb-3"><?= htmlspecialchars($question['question_text']) ?></p>
                                                        <div class="question-type-badge inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full mb-3">
                                                            <?= ucfirst(str_replace('_', ' ', $question['question_type'])) ?>
                                                        </div>
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
                                                    <div class="options-list space-y-2">
                                                        <?php foreach ($options as $option): ?>
                                                            <div class="option-item flex items-center gap-2 
                                                                <?= $option['is_correct'] ? 'bg-green-100 border-green-300' : 'bg-white border-gray-200' ?> 
                                                                border p-2 rounded">
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
                                                        <button type="button" onclick="setAnswerKey(<?= $question['question_id'] ?>)" 
                                                                class="w-full mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                                            <i class="ph ph-key mr-2"></i>Answer Key
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" onclick="addQuestion(<?= $section['section_id'] ?>)" 
                                        class="mt-4 w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600">
                                    <i class="ph ph-plus mr-2"></i>Add Question
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <button type="button" onclick="goBackToChapter()" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                    <i class="ph ph-floppy-disk mr-2"></i><?= $story ? 'Update Story' : 'Save Story' ?>
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
                        class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500">
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

<script>
const programId = <?= $program_id ?>;
const chapterId = <?= $chapter_id ?>;
const storyId = <?= $story ? $story['story_id'] : 'null' ?>;

let currentSectionId = null;
let currentQuestionType = null;
let sectionCount = <?= count($interactiveSections) ?>;

function goBackToChapter() {
    if (confirm('Are you sure you want to go back? Any unsaved changes will be lost.')) {
        window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`;
    }
}

function addInteractiveSection() {
    if (sectionCount >= 3) {
        alert('Maximum 3 interactive sections allowed per story.');
        return;
    }
    
    if (storyId) {
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
                location.reload();
            } else {
                alert('Error adding section: ' + data.message);
            }
        });
    } else {
        alert('Please save the story first before adding interactive sections.');
    }
}

function deleteSection(sectionId) {
    if (confirm('Are you sure you want to delete this interactive section?')) {
        fetch('../../php/program-handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_interactive_section',
                section_id: sectionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting section: ' + data.message);
            }
        });
    }
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
}

function addQuestion(sectionId) {
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
            <input type="text" placeholder="Sample Answer" required
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
    const questionText = document.getElementById('questionText').value;
    const optionInputs = document.querySelectorAll('.option-input input');
    const options = Array.from(optionInputs).map(input => input.value).filter(val => val.trim());
    
    if (!questionText.trim()) {
        alert('Please enter a question.');
        return;
    }
    
    if (!currentQuestionType) {
        alert('Please select a question type first.');
        return;
    }
    
    if ((currentQuestionType === 'multiple_choice' || currentQuestionType === 'multiple_select') && options.length < 2) {
        alert('Please add at least 2 options.');
        return;
    }
    
    fetch('../../php/program-handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'create_question',
            section_id: currentSectionId,
            question_text: questionText,
            question_type: currentQuestionType,
            options: options
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeQuestionModal();
            location.reload();
        } else {
            alert('Error adding question: ' + data.message);
        }
    });
}

function setAnswerKey(questionId) {
    // This would open a modal to set the correct answer
    // For now, we'll just prompt which option is correct
    const optionIndex = prompt('Which option is correct? (Enter the option number):');
    if (optionIndex && !isNaN(optionIndex)) {
        fetch('../../php/program-handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set_correct_answer',
                question_id: questionId,
                correct_option_index: parseInt(optionIndex) - 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error setting answer key: ' + data.message);
            }
        });
    }
}

function deleteQuestion(questionId) {
    if (confirm('Are you sure you want to delete this question?')) {
        fetch('../../php/program-handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_question',
                question_id: questionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting question: ' + data.message);
            }
        });
    }
}

// Update section count and button visibility
function updateSectionControls() {
    const addBtn = document.getElementById('addSectionBtn');
    if (sectionCount >= 3) {
        addBtn.disabled = true;
        addBtn.textContent = 'Maximum Sections Reached';
        addBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSectionControls();
});
</script>