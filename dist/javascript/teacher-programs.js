// Thumbnail preview
function previewThumbnail() {
    const fileInput = document.getElementById('thumbnail');
    const preview = document.getElementById('thumbnailPreview');
    if (fileInput.files && fileInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        reader.readAsDataURL(fileInput.files[0]);
    }
}

// Toggle add chapter form
function toggleAddChapterForm() {
    const form = document.getElementById('addChapterForm');
    form.classList.toggle('hidden');
}

// Toggle edit chapter form
function toggleEditChapterForm() {
    const form = document.getElementById('editChapterForm');
    form.classList.add('hidden');
}

// Open edit chapter form
function openEditChapterForm(chapterIndex) {
    const chapters = JSON.parse(document.getElementById('chapters-data').value);
    const chapter = chapters[chapterIndex];
    if (chapter) {
        document.getElementById('edit_chapter_id').value = chapterIndex;
        document.getElementById('edit_chapter_title').value = chapter.title;
        document.getElementById('edit_chapter_content').value = chapter.content;
        document.getElementById('edit_chapter_question').value = chapter.question;
        document.getElementById('editChapterForm').classList.remove('hidden');
    }
}

// Add chapter via AJAX
function addChapter(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('add_chapter', '1');

    fetch('../php/create-program.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toggleAddChapterForm();
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                confirmButtonText: 'OK'
            }).then(() => {
                // Update the chapters list
                const chaptersList = document.getElementById('chapters-list');
                if (chaptersList) {
                    const newChapter = data.chapters[data.chapters.length - 1];
                    const chapterElement = document.createElement('div');
                    chapterElement.className = 'p-4 bg-gray-50 rounded-lg';
                    chapterElement.innerHTML = `
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="font-semibold mb-1">${newChapter.title}</h3>
                                <div class="text-sm text-gray-600">
                                    ${newChapter.content ? `<p class="mb-2"><strong>Story:</strong> ${newChapter.content.substring(0, 100)}...</p>` : ''}
                                    ${newChapter.question ? `<p><strong>Question:</strong> ${newChapter.question.substring(0, 100)}...</p>` : ''}
                                </div>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <button type="button" onclick="openEditChapterForm(${newChapter.chapter_id || (data.chapters.length - 1)})"
                                        class="text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" onclick="confirmDeleteChapter(${newChapter.chapter_id || (data.chapters.length - 1)})"
                                        class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    chaptersList.appendChild(chapterElement);
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message,
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to add chapter. Please try again.',
            confirmButtonText: 'OK'
        });
    });
    return false;
}

// Update chapter via AJAX
function updateChapter(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('update_chapter', '1');

    fetch('../php/create-program.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toggleEditChapterForm();
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                confirmButtonText: 'OK'
            }).then(() => {
                // Refresh the chapters list
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message,
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to update chapter. Please try again.',
            confirmButtonText: 'OK'
        });
    });
    return false;
}

// Confirm delete chapter
function confirmDeleteChapter(chapterIndex) {
    Swal.fire({
        title: 'Delete Chapter?',
        text: "Are you sure you want to delete this chapter?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('delete_chapter', chapterIndex);
            formData.append('program_id', document.querySelector('input[name="program_id"]').value);

            fetch('../php/create-program.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh the chapters list
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message,
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Confirm form submission
function confirmSubmit(e) {
    const status = document.querySelector('select[name="status"]').value;
    const message = status === 'published'
        ? 'Are you sure you want to publish this program? Published programs will be visible to students.'
        : 'Are you sure you want to save this program?';
    e.preventDefault();
    Swal.fire({
        title: 'Confirm',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
    return false;
}

// Save as draft
function confirmSaveAsDraft() {
    Swal.fire({
        title: 'Save as Draft?',
        text: "Are you sure you want to save this program as a draft?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, save as draft',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.querySelector('select[name="status"]').value = 'draft';
            document.querySelector('form').submit();
        }
    });
}

// Display success/error messages if they exist
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('success-message').value;
    const errorMessage = document.getElementById('error-message').value;

    if (successMessage) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: successMessage,
            confirmButtonText: 'OK'
        });
    }

    if (errorMessage) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            confirmButtonText: 'OK'
        });
    }
});
