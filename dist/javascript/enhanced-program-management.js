/**
 * Enhanced Program Management JavaScript
 * Handles all AJAX operations and UI interactions for program creation and management
 * Compatible with existing al-ghaya schema - Updated version with better error handling
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
    // Get current program ID from URL or form or global variable
    const urlParams = new URLSearchParams(window.location.search);
    currentProgramId = window.currentProgramId || urlParams.get('program_id') || document.querySelector('input[name="program_id"]')?.value;
    
    // Initialize UI components
    initializeFormHandlers();
    initializeChapterManagement();
    initializeStoryManagement();
    initializeQuizManagement();
    initializeValidationHandlers();
    
    // Display notifications if they exist
    displayNotifications();
    
    // Load initial data if editing
    if (currentProgramId && window.currentAction === 'create') {
        loadProgramData(currentProgramId);
    }
}

/**
 * Initialize form submission handlers
 */
function initializeFormHandlers() {
    // Program details form
    const programForm = document.querySelector('form[action*="create-program.php"]');
    if (programForm) {
        programForm.addEventListener('submit', function(e) {
            // Let the form submit naturally to PHP for now
            return true;
        });
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
        addChapterBtn.addEventListener('click', showAddChapterModal);
    }
    
    // Create add chapter modal if it doesn't exist
    createAddChapterModal();
    
    // Event delegation for dynamically created chapter buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-chapter-btn')) {
            const chapterId = e.target.getAttribute('data-chapter-id');
            editChapter(chapterId);
        }
        
        if (e.target.classList.contains('delete-chapter-btn')) {
            const chapterId = e.target.getAttribute('data-chapter-id');
            confirmDeleteChapter(chapterId);
        }
    });
}

/**
 * Create add chapter modal dynamically
 */
function createAddChapterModal() {
    // Check if modal already exists
    if (document.getElementById('add-chapter-modal')) {
        return;
    }
    
    const modalHTML = `
        <div id="add-chapter-modal" class="fixed inset-0 flex justify-center items-center bg-black bg-opacity-50 hidden z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
                <form id="add-chapter-form" class="space-y-4">
                    <h2 class="text-lg font-semibold mb-4">Add New Chapter</h2>
                    
                    <div>
                        <label for="chapter_title" class="block text-gray-700 font-medium mb-2">Chapter Title</label>
                        <input type="text" id="chapter_title" name="chapter_title" required 
                               class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="chapter_content" class="block text-gray-700 font-medium mb-2">Chapter Content</label>
                        <textarea id="chapter_content" name="chapter_content" rows="4" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label for="chapter_question" class="block text-gray-700 font-medium mb-2">Chapter Question</label>
                        <textarea id="chapter_question" name="chapter_question" rows="2" 
                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-4 pt-4">
                        <button type="button" onclick="closeAddChapterModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add Chapter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add form submit handler
    const form = document.getElementById('add-chapter-form');
    if (form) {
        form.addEventListener('submit', handleAddChapter);
    }
}

/**
 * Show add chapter modal
 */
function showAddChapterModal() {
    const modal = document.getElementById('add-chapter-modal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('chapter_title')?.focus();
    }
}

/**
 * Close add chapter modal
 */
function closeAddChapterModal() {
    const modal = document.getElementById('add-chapter-modal');
    if (modal) {
        modal.classList.add('hidden');
        // Reset form
        const form = document.getElementById('add-chapter-form');
        if (form) {
            form.reset();
        }
    }
}

/**
 * Handle add chapter form submission
 */
async function handleAddChapter(e) {
    e.preventDefault();
    
    if (!currentProgramId) {
        showError('No program selected. Please save the program first.');
        return;
    }
    
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
            closeAddChapterModal();
            
            // Reload the page to show the new chapter
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
 * Initialize story management
 */
function initializeStoryManagement() {
    // Event delegation for story buttons
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
    // Event delegation for quiz buttons
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
            preview.src = `${BASE_URL}/uploads/thumbnails/${program.thumbnail}`;
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
    if (!container) {
        console.log('Chapters container not found - chapters will be handled by server-side rendering');
        return;
    }
    
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
            </div>
            
            <div class="flex gap-2 ml-4">
                <button type="button" class="edit-chapter-btn text-blue-500 hover:text-blue-700" data-chapter-id="${chapter.chapter_id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="delete-chapter-btn text-red-500 hover:text-red-700" data-chapter-id="${chapter.chapter_id}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    return div;
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
            // Reload page to reflect changes
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
    // These are handled by PHP and SweetAlert in the page template
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

// Stub functions for story and quiz management (to be implemented)
function showAddStoryForm(chapterId) {
    showError('Story management feature coming soon!');
}

function editStory(storyId) {
    showError('Story editing feature coming soon!');
}

function confirmDeleteStory(storyId) {
    showError('Story deletion feature coming soon!');
}

function showAddQuizForm(chapterId) {
    showError('Quiz management feature coming soon!');
}

function showAddQuestionForm(quizId) {
    showError('Quiz question management feature coming soon!');
}

function showEditChapterModal(chapter) {
    showError('Chapter editing modal coming soon!');
}

// Export functions for global access (backward compatibility)
window.previewThumbnail = previewThumbnail;
window.showAddChapterModal = showAddChapterModal;
window.closeAddChapterModal = closeAddChapterModal;