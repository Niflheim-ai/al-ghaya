<!-- Quick Access Toolbar Component -->
<div class="quick-access-card">
    <button type="button" class="group btn-blue" onclick="createNewProgram()">
        <i class="ph ph-plus-square text-[24px] group-hover:hidden"></i>
        <i class="ph-duotone ph-plus-square text-[24px] hidden group-hover:block"></i>
        <p class="font-medium">New Program</p>
    </button>
    <button type="button" class="group btn-green" onclick="openPublishModal()">
        <i class="ph ph-box-arrow-up text-[24px] group-hover:hidden"></i>
        <i class="ph-duotone ph-box-arrow-up text-[24px] hidden group-hover:block"></i>
        <p class="font-medium">Publish</p>
    </button>
    <button type="button" class="group btn-orange" onclick="showUpdateOptions()">
        <i class="ph ph-warning-octagon text-[24px] group-hover:hidden"></i>
        <i class="ph-duotone ph-warning-octagon text-[24px] hidden group-hover:block"></i>
        <p class="font-medium">Update</p>
    </button>
</div>

<!-- Publish Modal (unchanged) -->
<div id="publishModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ph ph-box-arrow-up text-green-600 text-[20px]"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Submit Programs for Publishing
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Select the draft programs you want to publish.
                            </p>
                        </div>
                        <div class="mt-4 max-h-60 overflow-y-auto">
                            <div id="publishProgramsList" class="space-y-2">
                                <!-- Programs will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitForPublishing()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Publish Selected
                </button>
                <button type="button" onclick="closePublishModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ✅ UPDATED: Update Options Modal -->
<div id="updateModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ph ph-warning-octagon text-orange-600 text-[20px]"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Update Published Program
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Select a published program to create a new version. This will:
                            </p>
                            <ul class="mt-2 text-sm text-gray-600 list-disc list-inside space-y-1">
                                <li>Create a new draft version you can edit</li>
                                <li>Keep the old version for existing enrollees</li>
                                <li>Hide the old version from new students</li>
                            </ul>
                        </div>
                        <div class="mt-4 max-h-60 overflow-y-auto">
                            <div id="updateProgramsList" class="space-y-2">
                                <!-- Published Programs will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="createProgramUpdate()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Create New Version
                </button>
                <button type="button" onclick="closeUpdateModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function createNewProgram() {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="ph ph-spinner-gap animate-spin text-[24px] mr-2"></i><p class="font-medium">Creating...</p>';
    btn.disabled = true;
    
    const currentPath = window.location.pathname;
    let redirectPath;
    
    if (currentPath.includes('/pages/teacher/')) {
        redirectPath = '../../pages/teacher/teacher-programs.php?action=create';
    } else if (currentPath.includes('/pages/')) {
        redirectPath = '../teacher/teacher-programs.php?action=create';
    } else {
        redirectPath = 'pages/teacher/teacher-programs.php?action=create';
    }
    
    window.location.href = redirectPath;
}

// Publish Modal Functions
function openPublishModal() {
    const modal = document.getElementById('publishModal');
    if (modal) {
        modal.classList.remove('hidden');
        loadDraftPrograms();
    }
}

function closePublishModal() {
    const modal = document.getElementById('publishModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function loadDraftPrograms() {
    const currentPath = window.location.pathname;
    const possiblePaths = [
        currentPath.includes('/pages/teacher/') ? '../../php/program-core.php' : 
        currentPath.includes('/pages/') ? '../php/program-core.php' : 
        'php/program-core.php',
        '../../php/program-core.php',
        '../php/program-core.php',
        'php/program-core.php'
    ];
    
    const apiUrls = [...new Set(possiblePaths)];
    
    function tryFetch(urlIndex = 0) {
        if (urlIndex >= apiUrls.length) {
            const programsList = document.getElementById('publishProgramsList');
            if (programsList) {
                programsList.innerHTML = '<p class="text-red-500 text-center py-4">Unable to load programs. Please check your connection.</p>';
            }
            return;
        }
        
        const apiUrl = apiUrls[urlIndex];
        
        fetch(apiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'get_draft_programs' }),
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 403) {
                    return response.json().then(data => {
                        throw new Error('unauthorized');
                    });
                }
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const programsList = document.getElementById('publishProgramsList');
            if (programsList) {
                if (data.success && data.programs && data.programs.length > 0) {
                    programsList.innerHTML = data.programs.map(program => `
                        <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                            <input type="checkbox" name="publish_programs[]" value="${program.programID}" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${program.title || 'Untitled Program'}</div>
                                <div class="text-sm text-gray-500">₱${parseFloat(program.price || 0).toFixed(2)} • ${program.category || 'beginner'}</div>
                            </div>
                        </label>
                    `).join('');
                } else {
                    programsList.innerHTML = '<p class="text-gray-500 text-center py-4">No draft programs available for publishing.</p>';
                }
            }
        })
        .catch(error => {
            console.error(`Error with URL ${apiUrl}:`, error);
            
            if (error.message === 'unauthorized') {
                const programsList = document.getElementById('publishProgramsList');
                if (programsList) {
                    programsList.innerHTML = '<p class="text-orange-500 text-center py-4">This action requires a teacher account. Please log in as a teacher.</p>';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Teacher Access Required',
                        text: 'The Publish function requires a teacher account. Please log in as a teacher to continue.',
                        confirmButtonColor: '#3b82f6'
                    });
                }
                return;
            }
            
            tryFetch(urlIndex + 1);
        });
    }
    
    tryFetch();
}

// ✅ UPDATED: Load Published Programs for Update Modal
function loadPublishedPrograms() {
    const currentPath = window.location.pathname;
    const possiblePaths = [
        currentPath.includes('/pages/teacher/') ? '../../php/program-core.php' : 
        currentPath.includes('/pages/') ? '../php/program-core.php' : 
        'php/program-core.php',
        '../../php/program-core.php',
        '../php/program-core.php',
        'php/program-core.php'
    ];
    
    const apiUrls = [...new Set(possiblePaths)];
    
    function tryFetch(urlIndex = 0) {
        if (urlIndex >= apiUrls.length) {
            const programsList = document.getElementById('updateProgramsList');
            if (programsList) {
                programsList.innerHTML = '<p class="text-red-500 text-center py-4">Unable to load programs. Please check your connection.</p>';
            }
            return;
        }
        
        const apiUrl = apiUrls[urlIndex];
        
        fetch(apiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'get_published_programs' }),
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 403) {
                    return response.json().then(data => {
                        throw new Error('unauthorized');
                    });
                }
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const programsList = document.getElementById('updateProgramsList');
            if (programsList) {
                if (data.success && data.programs && data.programs.length > 0) {
                    programsList.innerHTML = data.programs.map(program => {
                        // ✅ Show version badge if available
                        const versionBadge = program.version_number > 1 
                            ? `<span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full">v${program.version_number}</span>` 
                            : '';
                        
                        return `
                            <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                <input type="radio" name="update_program_id" value="${program.programID}" class="form-radio text-orange-600">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">
                                        ${program.title || 'Untitled Program'}
                                        ${versionBadge}
                                    </div>
                                    <div class="text-sm text-gray-500">₱${parseFloat(program.price || 0).toFixed(2)} • ${program.category || 'beginner'}</div>
                                </div>
                            </label>
                        `;
                    }).join('');
                } else {
                    programsList.innerHTML = '<p class="text-gray-500 text-center py-4">No published programs available to update.</p>';
                }
            }
        })
        .catch(error => {
            console.error(`Error with URL ${apiUrl}:`, error);
            
            if (error.message === 'unauthorized') {
                const programsList = document.getElementById('updateProgramsList');
                if (programsList) {
                    programsList.innerHTML = '<p class="text-orange-500 text-center py-4">This action requires a teacher account. Please log in as a teacher.</p>';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Teacher Access Required',
                        text: 'The Update function requires a teacher account. Please log in as a teacher to continue.',
                        confirmButtonColor: '#3b82f6'
                    });
                }
                return;
            }
            
            tryFetch(urlIndex + 1);
        });
    }
    
    tryFetch();
}

function submitForPublishing() {
    const selectedPrograms = Array.from(document.querySelectorAll('input[name="publish_programs[]"]:checked')).map(cb => cb.value);
    
    if (selectedPrograms.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'No Programs Selected',
                text: 'Please select at least one program to publish.'
            });
        } else {
            alert('Please select at least one program to publish.');
        }
        return;
    }

    const currentPath = window.location.pathname;
    const possiblePaths = [
        currentPath.includes('/pages/teacher/') ? '../../php/program-core.php' : 
        currentPath.includes('/pages/') ? '../php/program-core.php' : 
        'php/program-core.php',
        '../../php/program-core.php',
        '../php/program-core.php',
        'php/program-core.php'
    ];
    const apiUrls = [...new Set(possiblePaths)];
    const apiUrl = apiUrls[0];

    fetch(apiUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ 
            action: 'submit_for_publishing', 
            program_ids: selectedPrograms 
        }),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: `${selectedPrograms.length} program(s) submitted for review successfully!`
                }).then(() => {
                    closePublishModal();
                    window.location.reload();
                });
            } else {
                alert(`${selectedPrograms.length} program(s) submitted for review successfully!`);
                closePublishModal();
                window.location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error submitting programs: ' + (data.message || 'Unknown error')
                });
            } else {
                alert('Error submitting programs: ' + (data.message || 'Unknown error'));
            }
        }
    })
    .catch(error => {
        console.error('Error submitting programs:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Error submitting programs. Please try again.'
            });
        } else {
            alert('Error submitting programs. Please try again.');
        }
    });
}

// ✅ UPDATE Modal Functions
function showUpdateOptions() {
    const modal = document.getElementById('updateModal');
    if (modal) {
        modal.classList.remove('hidden');
        loadPublishedPrograms();
    }
}

function closeUpdateModal() {
    const modal = document.getElementById('updateModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// ✅ NEW: Create Program Update with Version Tracking
function createProgramUpdate() {
    const selectedRadio = document.querySelector('input[name="update_program_id"]:checked');
    
    if (!selectedRadio) {
        Swal.fire({
            icon: 'warning',
            title: 'No Program Selected',
            text: 'Please select a published program to update.'
        });
        return;
    }
    
    const programId = selectedRadio.value;
    
    // Confirm action
    Swal.fire({
        title: 'Create New Version?',
        html: `
            This will:<br>
            • Create a new draft version you can edit<br>
            • Keep the old version for current enrollees<br>
            • Hide the old version from new students
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Create Version',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Creating new version...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            // ✅ Call clone-program.php with isUpdate flag
            fetch('../../php/clone-program.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `programId=${encodeURIComponent(programId)}&isUpdate=true`
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.newProgramId) {
                    Swal.fire({
                        icon: 'success',
                        title: 'New Version Created!',
                        text: 'You can now edit and publish the updated version.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // Redirect to edit the new version
                        window.location.href = `?action=create&program_id=${res.newProgramId}`;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Could not create new version.'
                    });
                }
            })
            .catch(error => {
                console.error('Error creating version:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not create new version. Please try again.'
                });
            });
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const publishModal = document.getElementById('publishModal');
    const updateModal = document.getElementById('updateModal');
    
    if (publishModal && event.target === publishModal) {
        closePublishModal();
    }
    if (updateModal && event.target === updateModal) {
        closeUpdateModal();
    }
});
</script>