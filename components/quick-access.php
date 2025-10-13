<!-- Quick Access Toolbar Component - Compatible with Existing Schema -->
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

<!-- Publish Modal -->
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

<!-- Update Options Modal -->
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
                            Update Programs
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Choose what you want to update:
                            </p>
                        </div>
                        <div class="mt-4 space-y-3">
                            <button onclick="bulkUpdateStatus()" class="w-full text-left px-4 py-2 bg-blue-50 hover:bg-blue-100 rounded-lg flex items-center gap-3">
                                <i class="ph ph-arrow-circle-up text-blue-600"></i>
                                <div>
                                    <div class="font-medium text-blue-900">Update Program Status</div>
                                    <div class="text-sm text-blue-600">Change multiple programs from draft to published</div>
                                </div>
                            </button>
                            <button onclick="bulkUpdatePricing()" class="w-full text-left px-4 py-2 bg-green-50 hover:bg-green-100 rounded-lg flex items-center gap-3">
                                <i class="ph ph-currency-dollar text-green-600"></i>
                                <div>
                                    <div class="font-medium text-green-900">Update Pricing</div>
                                    <div class="text-sm text-green-600">Bulk update program prices</div>
                                </div>
                            </button>
                            <button onclick="bulkUpdateCategories()" class="w-full text-left px-4 py-2 bg-purple-50 hover:bg-purple-100 rounded-lg flex items-center gap-3">
                                <i class="ph ph-tag text-purple-600"></i>
                                <div>
                                    <div class="font-medium text-purple-900">Update Categories</div>
                                    <div class="text-sm text-purple-600">Change program difficulty levels</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeUpdateModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// New Program Function - FIXED VERSION
function createNewProgram() {
    // Show loading indicator
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="ph ph-spinner-gap animate-spin text-[24px] mr-2"></i><p class="font-medium">Creating...</p>';
    button.disabled = true;
    
    // Determine correct endpoint path based on current page location
    const currentPath = window.location.pathname;
    let endpoint;
    
    if (currentPath.includes('/pages/teacher/')) {
        // Called from pages/teacher/*.php
        endpoint = '../../php/ajax-create-program.php';
    } else if (currentPath.includes('/pages/')) {
        // Called from other pages
        endpoint = '../php/ajax-create-program.php';
    } else {
        // Called from root level
        endpoint = 'php/ajax-create-program.php';
    }
    
    // Create new program via AJAX with proper error handling
    fetch(endpoint, {
        method: 'POST',
        headers: { 
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Response is not JSON, probably an error page
            return response.text().then(text => {
                throw new Error('Server returned non-JSON response: ' + (text.length > 200 ? text.substring(0, 200) + '...' : text));
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success && data.program_id) {
            // Determine redirect URL based on current location
            let redirectUrl;
            if (currentPath.includes('/pages/teacher/')) {
                redirectUrl = `teacher-programs.php?action=create&program_id=${data.program_id}`;
            } else {
                redirectUrl = `pages/teacher/teacher-programs.php?action=create&program_id=${data.program_id}`;
            }
            
            // Redirect to program creation page
            window.location.href = redirectUrl;
        } else {
            throw new Error(data.message || 'Failed to create program - no program ID returned');
        }
    })
    .catch(error => {
        console.error('Error creating program:', error);
        
        // Restore button state
        button.innerHTML = originalContent;
        button.disabled = false;
        
        // Show user-friendly error message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error Creating Program',
                text: error.message || 'An unexpected error occurred. Please try again.',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Error creating program: ' + (error.message || 'Please try again.'));
        }
    });
}

// Publish Modal Functions
function openPublishModal() {
    document.getElementById('publishModal').classList.remove('hidden');
    loadDraftPrograms();
}

function closePublishModal() {
    document.getElementById('publishModal').classList.add('hidden');
}

function loadDraftPrograms() {
    fetch('../../php/program-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'get_draft_programs' })
    })
    .then(response => response.json())
    .then(data => {
        const programsList = document.getElementById('publishProgramsList');
        if (data.success && data.programs.length > 0) {
            programsList.innerHTML = data.programs.map(program => `
                <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                    <input type="checkbox" name="publish_programs[]" value="${program.programID}" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">${program.title}</div>
                        <div class="text-sm text-gray-500">₱${parseFloat(program.price).toFixed(2)} • ${program.category}</div>
                    </div>
                </label>
            `).join('');
        } else {
            programsList.innerHTML = '<p class="text-gray-500 text-center py-4">No draft programs available for publishing.</p>';
        }
    })
    .catch(error => {
        console.error('Error loading draft programs:', error);
        document.getElementById('publishProgramsList').innerHTML = '<p class="text-red-500 text-center py-4">Error loading programs.</p>';
    });
}

function submitForPublishing() {
    const selectedPrograms = Array.from(document.querySelectorAll('input[name="publish_programs[]"]:checked')).map(cb => cb.value);
    
    if (selectedPrograms.length === 0) {
        alert('Please select at least one program to publish.');
        return;
    }

    fetch('../../php/program-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            action: 'submit_for_publishing', 
            program_ids: selectedPrograms 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${selectedPrograms.length} program(s) published successfully!`);
            closePublishModal();
            // Refresh the page to show updated status
            window.location.reload();
        } else {
            alert('Error publishing programs: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error publishing programs:', error);
        alert('Error publishing programs. Please try again.');
    });
}

// Update Modal Functions
function showUpdateOptions() {
    document.getElementById('updateModal').classList.remove('hidden');
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.add('hidden');
}

function bulkUpdateStatus() {
    closeUpdateModal();
    alert('Bulk status update feature coming soon!');
}

function bulkUpdatePricing() {
    closeUpdateModal();
    alert('Bulk pricing update feature coming soon!');
}

function bulkUpdateCategories() {
    closeUpdateModal();
    alert('Bulk categories update feature coming soon!');
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const publishModal = document.getElementById('publishModal');
    const updateModal = document.getElementById('updateModal');
    
    if (event.target === publishModal) {
        closePublishModal();
    }
    if (event.target === updateModal) {
        closeUpdateModal();
    }
});
</script>