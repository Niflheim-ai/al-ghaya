/**
 * Enhanced Program Management JavaScript
 * Handles all AJAX operations and UI interactions for program creation and management
 * Compatible with existing al-ghaya schema
 */

// Global variables
let currentProgramId = null;
let currentChapterId = null;
let currentStoryId = null;

// Base URL configuration
const BASE_URL = window.location.origin + '/al-ghaya';
const API_ENDPOINT = BASE_URL + '/php/program-handler.php';

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeEnhancedProgramManagement();
});

/**
 * Initialize all event listeners and UI components
 */
function initializeEnhancedProgramManagement() {
    // Get current program ID from URL or form
    const urlParams = new URLSearchParams(window.location.search);
    currentProgramId = urlParams.get('program_id') || document.querySelector('input[name="program_id"]')?.value;
    
    // Initialize UI components
    initializeFormHandlers();
    initializeChapterManagement();
    initializeStoryManagement();
    initializeQuizManagement();
    initializeValidationHandlers();
    
    // Show success/error messages
    displayNotifications();
    
    // Load initial data if editing
    if (currentProgramId) {
        loadProgramData(currentProgramId);
    }
}

/**
 * Initialize form submission handlers
 */
function initializeFormHandlers() {
    // Program details form
    const programForm = document.getElementById('program-form');
    if (programForm) {
        programForm.addEventListener('submit', handleProgramSubmit);
    }
    
    // Thumbnail preview
    const thumbnailInput = document.getElementById('thumbnail');
    if (thumbnailInput) {
        thumbnailInput.addEventListener('change', previewThumbnail);
    }
    
    // YouTube URL validation
    const videoInput = document.getElementById('video_link');
    if (videoInput) {
        videoInput.addEventListener('blur', validateYouTubeURL);
    }
}

/**
 * Initialize chapter management
 */
function initializeChapterManagement() {
    // Add chapter button
    const addChapterBtn = document.getElementById('add-chapter-btn');
    if (addChapterBtn) {
        addChapterBtn.addEventListener('click', showAddChapterForm);
    }
    
    // Add chapter form submission
    const addChapterForm = document.getElementById('add-chapter-form');
    if (addChapterForm) {
        addChapterForm.addEventListener('submit', handleAddChapter);
    }
    
    // Cancel add chapter
    const cancelChapterBtn = document.getElementById('cancel-chapter-btn');
    if (cancelChapterBtn) {
        cancelChapterBtn.addEventListener('click', hideAddChapterForm);
    }
}

/**
 * Initialize story management
 */
function initializeStoryManagement() {
    // Add story buttons (attached to chapters)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-story-btn')) {
            const chapterId = e.target.getAttribute('data-chapter-id');
            showAddStoryForm(chapterId);
        }
        
        if (e.target.classList.contains('edit-story-btn')) {
            const storyId = e.target.getAttribute('data-story-id');
            editStory(storyId);
        }
        
        if (e.target.classList.contains('delete-story-btn')) {
            const storyId = e.target.getAttribute('data-story-id');
            confirmDeleteStory(storyId);
        }
    });
}

/**
 * Initialize quiz management
 */
function initializeQuizManagement() {
    // Quiz related event listeners
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-quiz-btn')) {
            const chapterId = e.target.getAttribute('data-chapter-id');
            showAddQuizForm(chapterId);
        }
        
        if (e.target.classList.contains('add-question-btn')) {
            const quizId = e.target.getAttribute('data-quiz-id');
            showAddQuestionForm(quizId);
        }
    });
}

/**
 * Initialize validation handlers
 */
function initializeValidationHandlers() {
    // Real-time form validation
    const titleInput = document.getElementById('title');
    if (titleInput) {
        titleInput.addEventListener('input', validateProgramTitle);
    }
    
    const priceInput = document.getElementById('price');
    if (priceInput) {
        priceInput.addEventListener('input', validatePrice);
    }
}

/**
 * Handle program form submission
 */
async function handleProgramSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    // Add action based on whether we're creating or updating
    const action = currentProgramId ? 'update_program' : 'create_program';
    formData.append('action', action);
    
    if (currentProgramId) {
        formData.append('program_id', currentProgramId);
    }
    
    try {
        showLoading('Saving program...');
        
        const response = await fetch(API_ENDPOINT, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await handleResponse(response);
        
        if (data.success) {
            showSuccess(data.message || 'Program saved successfully!');
            
            // If creating new program, update URL and set currentProgramId
            if (!currentProgramId && data.program_id) {
                currentProgramId = data.program_id;
                updateURLWithProgramId(data.program_id);
            }
            
            // Reload program data
            setTimeout(() => {
                loadProgramData(currentProgramId);
            }, 1000);
        } else {
            showError(data.message || 'Failed to save program');
        }
    } catch (error) {
        console.error('Program submission error:', error);
        showError('An error occurred while saving the program');
    } finally {
        hideLoading();
    }
}

/**
 * Load program data for editing
 */
async function loadProgramData(programId) {
    try {
        const response = await apiRequest('get_program', { program_id: programId });
        
        if (response.success && response.program) {
            populateProgramForm(response.program);
        }
        
        // Load chapters
        loadChapters(programId);
        
    } catch (error) {
        console.error('Error loading program data:', error);
    }
}

/**
 * Populate program form with data
 */
function populateProgramForm(program) {
    const fields = ['title', 'description', 'price', 'status', 'video_link'];
    
    fields.forEach(field => {
        const element = document.getElementById(field);
        if (element && program[field] !== undefined) {
            element.value = program[field];
        }
    });
    
    // Handle category/difficulty radio buttons
    if (program.category) {
        const categoryRadio = document.querySelector(`input[name="category"][value="${program.category}"]`);
        if (categoryRadio) {
            categoryRadio.checked = true;
        }
    }
    
    // Handle thumbnail preview
    if (program.thumbnail && program.thumbnail !== 'default-thumbnail.jpg') {
        const preview = document.getElementById('thumbnailPreview');
        if (preview) {
            preview.src = `${BASE_URL}/uploads/program_thumbnails/${program.thumbnail}`;
        }
    }
}

/**
 * Load chapters for the program
 */
async function loadChapters(programId) {
    try {
        const response = await apiRequest('get_chapters', { program_id: programId });
        
        if (response.success && response.chapters) {
            renderChapters(response.chapters);
        }
    } catch (error) {
        console.error('Error loading chapters:', error);
    }
}

/**
 * Render chapters in the UI
 */
function renderChapters(chapters) {
    const container = document.getElementById('chapters-container');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (chapters.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No chapters added yet.</p>';
        return;
    }
    
    chapters.forEach((chapter, index) => {
        const chapterElement = createChapterElement(chapter, index);
        container.appendChild(chapterElement);
    });
}

/**
 * Create chapter DOM element
 */
function createChapterElement(chapter, index) {
    const div = document.createElement('div');
    div.className = 'chapter-item p-4 bg-gray-50 rounded-lg mb-4';
    div.setAttribute('data-chapter-id', chapter.chapter_id);
    
    div.innerHTML = `
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <h3 class="font-semibold mb-2">${escapeHtml(chapter.title)}</h3>
                <div class="text-sm text-gray-600">
                    ${chapter.content ? `<p class="mb-2"><strong>Content:</strong> ${escapeHtml(chapter.content.substring(0, 100))}...</p>` : ''}
                    ${chapter.question ? `<p><strong>Question:</strong> ${escapeHtml(chapter.question.substring(0, 100))}...</p>` : ''}
                </div>
                
                <!-- Chapter Actions -->
                <div class="mt-3 flex gap-2">
                    <button type="button" class="add-story-btn text-blue-600 hover:text-blue-800 text-sm" data-chapter-id="${chapter.chapter_id}">
                        <i class="fas fa-plus mr-1"></i>Add Story
                    </button>
                    <button type="button" class="add-quiz-btn text-green-600 hover:text-green-800 text-sm" data-chapter-id="${chapter.chapter_id}">
                        <i class="fas fa-question mr-1"></i>Add Quiz
                    </button>
                </div>
                
                <!-- Stories Container -->
                <div id="stories-${chapter.chapter_id}" class="stories-container mt-3"></div>
                
                <!-- Quiz Container -->
                <div id="quiz-${chapter.chapter_id}" class="quiz-container mt-3"></div>
            </div>
            
            <div class="flex gap-2 ml-4">
                <button type="button" onclick="editChapter(${chapter.chapter_id})" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" onclick="confirmDeleteChapter(${chapter.chapter_id})" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    return div;
}

/**
 * Show add chapter form
 */
function showAddChapterForm() {
    const form = document.getElementById('add-chapter-form');
    if (form) {
        form.style.display = 'block';
        document.getElementById('chapter_title').focus();
    }
}

/**
 * Hide add chapter form
 */
function hideAddChapterForm() {
    const form = document.getElementById('add-chapter-form');
    if (form) {
        form.style.display = 'none';
        form.reset();
    }
}

/**
 * Handle add chapter form submission
 */
async function handleAddChapter(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'add_chapter');
    formData.append('program_id', currentProgramId);
    
    try {
        showLoading('Adding chapter...');
        
        const response = await fetch(API_ENDPOINT, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await handleResponse(response);
        
        if (data.success) {
            showSuccess('Chapter added successfully!');
            hideAddChapterForm();
            loadChapters(currentProgramId);
        } else {
            showError(data.message || 'Failed to add chapter');
        }
    } catch (error) {
        console.error('Add chapter error:', error);
        showError('An error occurred while adding the chapter');
    } finally {
        hideLoading();
    }
}

/**
 * Edit chapter
 */
async function editChapter(chapterId) {
    try {
        const response = await apiRequest('get_chapter', { chapter_id: chapterId });
        
        if (response.success && response.chapter) {
            showEditChapterModal(response.chapter);
        }
    } catch (error) {
        console.error('Error loading chapter for edit:', error);
        showError('Failed to load chapter data');
    }
}

/**
 * Confirm delete chapter
 */
function confirmDeleteChapter(chapterId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Chapter?',
            text: 'Are you sure you want to delete this chapter? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteChapter(chapterId);
            }
        });
    } else {
        if (confirm('Are you sure you want to delete this chapter?')) {
            deleteChapter(chapterId);
        }
    }
}

/**
 * Delete chapter
 */
async function deleteChapter(chapterId) {
    try {
        showLoading('Deleting chapter...');
        
        const response = await apiRequest('delete_chapter', {
            chapter_id: chapterId,
            program_id: currentProgramId
        });
        
        if (response.success) {
            showSuccess('Chapter deleted successfully!');
            loadChapters(currentProgramId);
        } else {
            showError(response.message || 'Failed to delete chapter');
        }
    } catch (error) {
        console.error('Delete chapter error:', error);
        showError('An error occurred while deleting the chapter');
    } finally {
        hideLoading();
    }
}

/**
 * Thumbnail preview function
 */
function previewThumbnail() {
    const fileInput = document.getElementById('thumbnail');
    const preview = document.getElementById('thumbnailPreview');
    
    if (fileInput && preview && fileInput.files && fileInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(fileInput.files[0]);
    }
}

/**
 * Validate YouTube URL
 */
async function validateYouTubeURL() {
    const input = document.getElementById('video_link');
    if (!input || !input.value.trim()) return;
    
    try {
        const response = await apiRequest('validate_youtube_url', { url: input.value });
        
        if (response.success && response.is_valid) {
            input.classList.remove('border-red-500');
            input.classList.add('border-green-500');
        } else {
            input.classList.remove('border-green-500');
            input.classList.add('border-red-500');
            showError('Please enter a valid YouTube URL');
        }
    } catch (error) {
        console.error('YouTube URL validation error:', error);
    }
}

/**
 * Validate program title
 */
function validateProgramTitle() {
    const input = document.getElementById('title');
    if (!input) return;
    
    const title = input.value.trim();
    const minLength = 5;
    const maxLength = 100;
    
    if (title.length < minLength) {
        input.classList.add('border-red-500');
        showValidationError(input, `Title must be at least ${minLength} characters`);
    } else if (title.length > maxLength) {
        input.classList.add('border-red-500');
        showValidationError(input, `Title must be less than ${maxLength} characters`);
    } else {
        input.classList.remove('border-red-500');
        input.classList.add('border-green-500');
        hideValidationError(input);
    }
}

/**
 * Validate price
 */
function validatePrice() {
    const input = document.getElementById('price');
    if (!input) return;
    
    const price = parseFloat(input.value);
    
    if (isNaN(price) || price < 0) {
        input.classList.add('border-red-500');
        showValidationError(input, 'Price must be a valid number');
    } else if (price > 10000) {
        input.classList.add('border-red-500');
        showValidationError(input, 'Price seems too high');
    } else {
        input.classList.remove('border-red-500');
        input.classList.add('border-green-500');
        hideValidationError(input);
    }
}

/**
 * Generic API request function
 */
async function apiRequest(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    
    Object.keys(data).forEach(key => {
        formData.append(key, data[key]);
    });
    
    const response = await fetch(API_ENDPOINT, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    });
    
    return handleResponse(response);
}

/**
 * Handle API response
 */
async function handleResponse(response) {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
        const text = await response.text();
        console.error('Non-JSON response:', text);
        throw new Error('Server returned non-JSON response');
    }
    
    return response.json();
}

/**
 * Display notifications on page load
 */
function displayNotifications() {
    const successElement = document.getElementById('success-message');
    const errorElement = document.getElementById('error-message');
    
    if (successElement && successElement.value) {
        showSuccess(successElement.value);
    }
    
    if (errorElement && errorElement.value) {
        showError(errorElement.value);
    }
}

/**
 * Show success message
 */
function showSuccess(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        alert('Success: ' + message);
    }
}

/**
 * Show error message
 */
function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message
        });
    } else {
        alert('Error: ' + message);
    }
}

/**
 * Show loading indicator
 */
function showLoading(message = 'Loading...') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    if (typeof Swal !== 'undefined') {
        Swal.close();
    }
}

/**
 * Show validation error
 */
function showValidationError(input, message) {
    let errorDiv = input.parentNode.querySelector('.validation-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error text-red-500 text-sm mt-1';
        input.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

/**
 * Hide validation error
 */
function hideValidationError(input) {
    const errorDiv = input.parentNode.querySelector('.validation-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Update URL with program ID
 */
function updateURLWithProgramId(programId) {
    const url = new URL(window.location);
    url.searchParams.set('program_id', programId);
    url.searchParams.set('action', 'create');
    window.history.pushState({ programId }, '', url);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Export functions for global access (backward compatibility)
window.previewThumbnail = previewThumbnail;
window.editChapter = editChapter;
window.confirmDeleteChapter = confirmDeleteChapter;
window.showAddChapterForm = showAddChapterForm;
window.hideAddChapterForm = hideAddChapterForm;