<?php
$programId = isset($program_id) ? (int)$program_id : 0;
$chapterId = isset($chapter_id) ? (int)$chapter_id : 0;
$quiz = isset($quiz) ? $quiz : null;
$quiz_questions = isset($quiz_questions) ? $quiz_questions : [];
$isEdit = $quiz !== null;
$isPublished = isset($program) && strtolower($program['status']) === 'published';
?>

<section class="content-section">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <button onclick="goBack()" class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="section-title text-2xl font-bold">
                <?= $isEdit ? 'Edit Chapter Quiz' : 'Create Chapter Quiz' ?>
            </h1>
        </div>
        <div class="text-sm text-gray-500">
            <?= htmlspecialchars($program['title'] ?? '') ?> › <?= htmlspecialchars($chapter['title'] ?? '') ?>
        </div>
    </div>

    <?php if ($isPublished): ?>
        <div class="mb-5 px-4 py-3 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-900 rounded-lg">
            <b>This quiz belongs to a published program. Content is view-only.</b>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <!-- Quiz Info -->
        <div class="mb-8 p-6 bg-blue-50 rounded-lg">
            <h2 class="text-lg font-semibold text-blue-800 mb-2">Quiz Requirements</h2>
            <ul class="text-blue-700 space-y-1">
                <li>• Each chapter must have exactly one quiz</li>
                <li>• Maximum 30 multiple-choice questions per quiz</li>
                <li>• Each question must have 2-6 answer options</li>
                <li>• Students need 70% to pass</li>
            </ul>
        </div>

        <!-- Quiz Title -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quiz Title</label>
                <?php if ($isPublished): ?>
                    <div class="font-medium text-lg bg-gray-100 px-3 py-2 rounded mb-4">
                        <?= htmlspecialchars($quiz['title'] ?? $chapter['title'] . ' Quiz') ?>
                    </div>
                <?php else: ?>
                    <input type="text" id="quizTitle"
                        value="<?= htmlspecialchars($quiz['title'] ?? $chapter['title'] . ' Quiz') ?>"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quiz Questions Section -->
        <div>
            <h3 class="text-xl font-semibold mb-4">Quiz Questions</h3>
            <div class="flex items-center justify-end my-4">
                <?php if (!$isPublished): ?>
                <button type="button" onclick="addQuestion()" class="bg-blue-500 text-white px-5 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="ph ph-plus mr-1"></i> Add Question
                </button>
                <span class="ml-3 text-gray-700">Total: <span id="questionCount">0</span></span>
                <?php endif; ?>
            </div>
            <div id="questionsContainer" class="space-y-6">
                <?php foreach ($quiz_questions as $qIdx => $question): ?>
                    <div class="question-item bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6" data-question-index="<?= $qIdx + 1 ?>">
                        <div class="flex items-start justify-between mb-4">
                            <h4 class="font-medium text-gray-900">Question <span class="question-number"><?= $qIdx + 1 ?></span></h4>
                            <?php if (!$isPublished): ?>
                            <button type="button" onclick="removeQuestion(this)" class="text-red-500 hover:text-red-700">
                                <i class="ph ph-trash text-lg"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Question Text</label>
                            <?php if ($isPublished): ?>
                                <div class="w-full px-3 py-2 border border-gray-200 rounded bg-gray-100"><?= htmlspecialchars($question['question_text']) ?></div>
                            <?php else: ?>
                                <textarea name="questions[<?= $qIdx ?>][question_text]"
                                    class="question-text w-full px-3 py-2 border border-gray-300 rounded bg-white"
                                    required><?= htmlspecialchars($question['question_text']) ?></textarea>
                            <?php endif; ?>
                        </div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Answer Options</label>
                        <div class="space-y-2 pl-4 options-container">
                            <?php foreach ($question['options'] as $oIdx => $option): ?>
                                <div class="flex items-center gap-2 option-item">
                                    <?php if ($isPublished): ?>
                                        <input type="radio" disabled <?= $option['is_correct'] ? 'checked' : '' ?>>
                                        <span class="flex-1 px-3 py-2 border border-gray-200 rounded bg-gray-50"><?= htmlspecialchars($option['option_text']) ?></span>
                                        <?php if ($option['is_correct']): ?><span class="text-green-600 font-bold ml-2">Correct</span><?php endif; ?>
                                    <?php else: ?>
                                        <input type="radio"
                                            name="questions[<?= $qIdx ?>][correct_option]"
                                            value="<?= $oIdx ?>"
                                            class="correct-option"
                                            <?= $option['is_correct'] ? 'checked' : '' ?>
                                            required>
                                        <input type="text"
                                            name="questions[<?= $qIdx ?>][options][<?= $oIdx ?>][option_text]"
                                            value="<?= htmlspecialchars($option['option_text']) ?>"
                                            class="option-text flex-1 px-3 py-2 border border-gray-300 rounded bg-white"
                                            required>
                                        <input type="hidden"
                                            name="questions[<?= $qIdx ?>][options][<?= $oIdx ?>][is_correct]"
                                            value="<?= $option['is_correct'] ? 1 : 0 ?>">
                                        <?php if ($option['is_correct']): ?><span class="text-green-600 font-bold ml-2">Correct</span><?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$isPublished): ?>
            <button type="button" onclick="addQuestion()" class="mb-4 bg-blue-500 text-white px-5 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                <i class="ph ph-plus mr-1"></i> Add Question
            </button>
            <?php endif; ?>
            <div id="noQuestionsMessage" style="display:none;" class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                <i class="ph ph-question text-4xl mb-4"></i>
                <h4 class="text-lg font-medium mb-2">No Questions Yet</h4>
                <p>No quiz questions exist for this chapter.</p>
            </div>
        </div>

        <?php if (!$isPublished): ?>
        <form id="quizForm" class="space-y-8">
            <div class="flex justify-end gap-3 pt-6 border-t">
                <button type="button" onclick="goBack()" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="saveButton" class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                    <i class="ph ph-check mr-1"></i> <?= $isEdit ? 'Update Quiz' : 'Save Quiz' ?>
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</section>

<!-- Question Template (Hidden) -->
<template id="questionTemplate">
    <div class="question-item bg-gray-50 border border-gray-200 rounded-lg p-6" data-question-index="">
        <div class="flex items-start justify-between mb-4">
            <h4 class="font-medium text-gray-900">Question <span class="question-number"></span></h4>
            <button type="button" onclick="removeQuestion(this)" class="text-red-500 hover:text-red-700">
                <i class="ph ph-trash text-lg"></i>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Question Text *</label>
                <textarea class="question-text w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="3" placeholder="Enter your question here..." required></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Answer Options *</label>
                <div class="space-y-3 options-container"></div>
                <button type="button" onclick="addOption(this)" class="mt-2 px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded add-option-btn">
                    <i class="ph ph-plus mr-1"></i> Add Option
                </button>
                <p class="text-xs text-gray-500 mt-1">Mark the correct answer with the radio button. Max 6 options.</p>
            </div>
        </div>
    </div>
</template>

<!-- Option Template (Hidden) -->
<template id="optionTemplate">
    <div class="flex items-center gap-3 option-item">
        <input type="radio" class="correct-option text-blue-600 focus:ring-blue-500">
        <input type="text" class="option-text flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter answer option..." required>
        <button type="button" onclick="removeOption(this)" class="text-red-500 hover:text-red-700 remove-option-btn" style="display: none;">
            <i class="ph ph-x text-lg"></i>
        </button>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const programId = <?= $programId ?>;
const chapterId = <?= $chapterId ?>;
let questionIndex = document.querySelectorAll('.question-item').length; // start from loaded count
const maxQuestions = 30;
const maxOptions = 6;

function goBack() {
    window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`;
}

function addQuestion() {
    if (questionIndex >= maxQuestions) {
        Swal.fire({
            title: 'Maximum Questions Reached',
            text: `You can have a maximum of ${maxQuestions} questions per quiz.`,
            icon: 'warning',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }

    const template = document.getElementById('questionTemplate');
    const clone = template.content.cloneNode(true);
    const questionItem = clone.querySelector('.question-item');

    questionIndex++;
    questionItem.setAttribute('data-question-index', questionIndex);
    const numEl = questionItem.querySelector('.question-number');
    if (numEl) numEl.textContent = questionIndex;

    document.getElementById('questionsContainer').appendChild(clone);

    // Add initial options
    const addBtn = questionItem.querySelector('.add-option-btn');
    addOption(addBtn, true);
    addOption(addBtn, true);

    updateUI();
}

function addOption(button, isInitial = false) {
    const questionItem = button.closest('.question-item');
    const optionsContainer = questionItem.querySelector('.options-container');
    const currentOptions = optionsContainer.querySelectorAll('.option-item');

    if (currentOptions.length >= maxOptions) {
        if (!isInitial) {
            Swal.fire({
                title: 'Maximum Options Reached',
                text: `Each question can have a maximum of ${maxOptions} options.`,
                icon: 'warning',
                confirmButtonColor: '#3b82f6'
            });
        }
        return;
    }

    const template = document.getElementById('optionTemplate');
    const clone = template.content.cloneNode(true);
    const optionItem = clone.querySelector('.option-item');

    const questionIndexVal = questionItem.getAttribute('data-question-index');
    const optionIndex = currentOptions.length;

    // Set unique name for radio buttons within this question
    const radio = optionItem.querySelector('.correct-option');
    radio.name = `correct_option_${questionIndexVal}`;
    radio.value = optionIndex;

    optionsContainer.appendChild(clone);

    updateOptionButtons(questionItem);
}

function removeOption(button) {
    const questionItem = button.closest('.question-item');
    const optionItem = button.closest('.option-item');
    const optionsContainer = questionItem.querySelector('.options-container');

    if (optionsContainer.querySelectorAll('.option-item').length <= 2) {
        Swal.fire({
            title: 'Minimum Options Required',
            text: 'Each question must have at least 2 answer options.',
            icon: 'warning',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }

    optionItem.remove();
    updateOptionButtons(questionItem);
    reindexOptions(questionItem);
}

function updateOptionButtons(questionItem) {
    const options = questionItem.querySelectorAll('.option-item');
    options.forEach(option => {
        const removeBtn = option.querySelector('.remove-option-btn');
        if (removeBtn) removeBtn.style.display = options.length > 2 ? 'block' : 'none';
    });
}

function reindexOptions(questionItem) {
    const questionIndexVal = questionItem.getAttribute('data-question-index');
    const options = questionItem.querySelectorAll('.option-item');

    options.forEach((option, index) => {
        const radio = option.querySelector('.correct-option');
        radio.name = `correct_option_${questionIndexVal}`;
        radio.value = index;
    });
}

function removeQuestion(button) {
    const questionItem = button.closest('.question-item');

    Swal.fire({
        title: 'Delete Question?',
        text: 'This will permanently delete this question and all its options.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            questionItem.remove();
            reindexQuestions();
            updateUI();
        }
    });
}

function reindexQuestions() {
    const questions = document.querySelectorAll('.question-item');
    questionIndex = 0;

    questions.forEach((question, index) => {
        questionIndex++;
        question.setAttribute('data-question-index', questionIndex);
        const numEl = question.querySelector('.question-number');
        if (numEl) numEl.textContent = questionIndex;
        reindexOptions(question);
    });
}

function updateUI() {
    const questionCount = document.querySelectorAll('.question-item').length;
    const questionCountEl = document.getElementById('questionCount');
    if (questionCountEl) questionCountEl.textContent = questionCount;

    const noQuestionsMessage = document.getElementById('noQuestionsMessage');
    const questionsContainer = document.getElementById('questionsContainer');
    const saveButton = document.getElementById('saveButton');

    if (questionCount === 0) {
        if (noQuestionsMessage) noQuestionsMessage.style.display = 'block';
        if (questionsContainer) questionsContainer.style.display = 'none';
        if (saveButton) saveButton.disabled = true;
    } else {
        if (noQuestionsMessage) noQuestionsMessage.style.display = 'none';
        if (questionsContainer) questionsContainer.style.display = 'block';
        if (saveButton) saveButton.disabled = false;
    }
}

// Form submission with AJAX
document.getElementById('quizForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const questions = document.querySelectorAll('.question-item');
    let isValid = true;
    let errors = [];

    if (questions.length === 0) {
        errors.push('Quiz must have at least one question.');
        isValid = false;
    }

    const questionsData = [];

    questions.forEach((question, index) => {
        const questionTextInput = question.querySelector('.question-text');
        const questionText = questionTextInput ? questionTextInput.value.trim() : '';
        const options = question.querySelectorAll('.option-item');
        const selectedAnswer = question.querySelector('.correct-option:checked');

        if (!questionText) {
            errors.push(`Question ${index + 1}: Question text is required.`);
            isValid = false;
        }

        let validOptions = 0;
        const optionsData = [];

        options.forEach((option, optIndex) => {
            const optionTextInput = option.querySelector('.option-text');
            if (!optionTextInput) return;
            const optionText = optionTextInput.value.trim();
            if (optionText) {
                validOptions++;
                optionsData.push({
                    text: optionText,
                    is_correct: selectedAnswer && parseInt(selectedAnswer.value) === optIndex
                });
            }
        });

        if (validOptions < 2) {
            errors.push(`Question ${index + 1}: At least 2 answer options are required.`);
            isValid = false;
        }

        if (!selectedAnswer) {
            errors.push(`Question ${index + 1}: Please mark the correct answer.`);
            isValid = false;
        }

        if (isValid || errors.length === 0) {
            questionsData.push({
                text: questionText,
                options: optionsData
            });
        }
    });

    if (!isValid) {
        Swal.fire({
            title: 'Quiz Validation Failed',
            html: errors.join('<br>'),
            icon: 'error',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }

    const formData = {
        action: 'save_quiz',
        chapter_id: chapterId,
        quiz_title: document.getElementById('quizTitle').value,
        questions: questionsData
    };

    Swal.fire({
        title: 'Saving Quiz...',
        text: 'Please wait while we save your quiz.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('../../php/quiz-handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: data.message || 'Quiz saved successfully!',
                icon: 'success',
                confirmButtonColor: '#3b82f6'
            }).then(() => { goBack(); });
        } else {
            throw new Error(data.message || 'Failed to save quiz');
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Error',
            text: error.message || 'Failed to save quiz. Please try again.',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    });
});

updateUI();
</script>

<style>
.question-item {
    transition: all 0.3s ease;
}
.question-item:hover {
    border-color: #3b82f6;
}
.option-item {
    transition: all 0.2s ease;
}
.add-option-btn:hover {
    transform: translateY(-1px);
}
#saveButton:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
