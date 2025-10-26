<?php
// Interactive Story Sections Component
$programId = isset($program_id) ? (int)$program_id : 0;
$chapterId = isset($chapter_id) ? (int)$chapter_id : 0;
$storyId = isset($story_id) ? (int)$story_id : 0;
$story = isset($story_data) ? $story_data : null;

// Get existing interactive sections if editing
require_once __DIR__ . '/../php/quiz-handler.php';
$interactiveSections = interactiveSection_getByStory($conn, $storyId);
?>

<section class="content-section">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <button onclick="goBack()" class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </button>
            <h1 class="section-title text-2xl font-bold">Interactive Story Sections</h1>
        </div>
        <div class="text-sm text-gray-500">
            <?= htmlspecialchars($story['title'] ?? 'Story') ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <!-- Info Banner -->
        <div class="mb-8 p-6 bg-purple-50 rounded-lg">
            <h2 class="text-lg font-semibold text-purple-800 mb-2">Interactive Sections</h2>
            <ul class="text-purple-700 space-y-1">
                <li>• Add interactive questions throughout your story</li>
                <li>• Maximum 3 interactive sections per story</li>
                <li>• Support for multiple choice, fill-in-blanks, and multiple select</li>
                <li>• Engage students and test comprehension</li>
            </ul>
        </div>

        <!-- Sections Management -->
        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-semibold">Manage Interactive Sections</h3>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">
                        Sections: <span id="sectionCount"><?= count($interactiveSections) ?></span>/3
                    </span>
                    <button type="button" onclick="createSection()" id="createSectionBtn"
                            class="<?= count($interactiveSections) >= 3 ? 'opacity-50 cursor-not-allowed' : '' ?> px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-colors"
                            <?= count($interactiveSections) >= 3 ? 'disabled' : '' ?>>
                        <i class="ph ph-plus mr-1"></i> Add Section
                    </button>
                </div>
            </div>

            <?php if (empty($interactiveSections)): ?>
                <div id="noSectionsMessage" class="text-center py-12 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                    <i class="ph ph-chat-circle-dots text-5xl mb-4"></i>
                    <h4 class="text-lg font-medium mb-2">No Interactive Sections</h4>
                    <p class="mb-4">Add interactive questions to engage your students throughout the story.</p>
                    <button type="button" onclick="createSection()" 
                            class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-colors">
                        <i class="ph ph-plus mr-2"></i> Create First Section
                    </button>
                </div>
            <?php else: ?>
                <div id="sectionsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($interactiveSections as $index => $section): ?>
                        <div class="section-card bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-chat-circle-dots text-xl text-purple-600"></i>
                                    <h4 class="font-medium text-gray-900">Section <?= $index + 1 ?></h4>
                                </div>
                                <div class="relative">
                                    <button onclick="toggleSectionMenu(<?= (int)$section['section_id'] ?>)" 
                                            class="text-gray-400 hover:text-gray-600 p-1">
                                        <i class="ph ph-dots-three-vertical"></i>
                                    </button>
                                    <div id="menu-<?= (int)$section['section_id'] ?>" 
                                         class="hidden absolute right-0 top-8 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-10 w-36">
                                        <button onclick="editSection(<?= (int)$section['section_id'] ?>)" 
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                            <i class="ph ph-pencil-simple mr-2"></i>Edit
                                        </button>
                                        <button onclick="deleteSection(<?= (int)$section['section_id'] ?>)" 
                                                class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <i class="ph ph-trash mr-2"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="ph ph-list-numbers"></i>
                                    <span>Order: <?= (int)$section['section_order'] ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="ph ph-question"></i>
                                    <span><?= count($section['questions']) ?> Questions</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="ph ph-calendar"></i>
                                    <span><?= date('M j, Y', strtotime($section['dateCreated'])) ?></span>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <?php if (empty($section['questions'])): ?>
                                    <p class="text-sm text-gray-500 italic mb-3">No questions yet</p>
                                <?php else: ?>
                                    <div class="mb-3">
                                        <p class="text-sm font-medium text-gray-700 mb-2">Question Types:</p>
                                        <?php
                                        $questionTypes = [];
                                        foreach ($section['questions'] as $question) {
                                            $type = $question['question_type'];
                                            if (!isset($questionTypes[$type])) $questionTypes[$type] = 0;
                                            $questionTypes[$type]++;
                                        }
                                        ?>
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach ($questionTypes as $type => $count): ?>
                                                <span class="inline-flex items-center px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                                    <?= ucfirst(str_replace('_', ' ', $type)) ?> (<?= $count ?>)
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <button onclick="editSection(<?= (int)$section['section_id'] ?>)" 
                                        class="w-full px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white text-sm rounded-lg transition-colors">
                                    <i class="ph ph-pencil-simple mr-1"></i> 
                                    <?= empty($section['questions']) ? 'Add Questions' : 'Edit Section' ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200">
            <button onclick="goBack()" 
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors inline-flex items-center gap-2">
                <i class="ph ph-arrow-left"></i>Back to Story
            </button>
            
            <div class="text-right">
                <p class="text-sm text-gray-500">Story: <?= htmlspecialchars($story['title'] ?? '') ?></p>
                <p class="text-sm text-gray-400"><?= count($interactiveSections) ?>/3 interactive sections</p>
            </div>
        </div>
    </div>
</section>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const programId = <?= $programId ?>;
const chapterId = <?= $chapterId ?>;
const storyId = <?= $storyId ?>;
let currentSectionCount = <?= count($interactiveSections) ?>;
const maxSections = 3;

function goBack() {
    window.location.href = `teacher-programs.php?action=add_story&program_id=${programId}&chapter_id=${chapterId}&story_id=${storyId}`;
}

function createSection() {
    if (currentSectionCount >= maxSections) {
        Swal.fire({
            title: 'Maximum Sections Reached',
            text: `Each story can have a maximum of ${maxSections} interactive sections.`,
            icon: 'warning',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    Swal.fire({
        title: 'Create Interactive Section',
        text: `You are creating section ${currentSectionCount + 1} of ${maxSections} for this story.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Create Section',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Creating Section...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            fetch('../../php/quiz-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_interactive_section',
                    story_id: storyId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Section Created!',
                        text: 'Interactive section created successfully. You can now add questions to it.',
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        // Redirect to edit the new section
                        editSection(data.section_id);
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to create interactive section.',
                        icon: 'error',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            })
            .catch(() => {
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

function editSection(sectionId) {
    // Navigate to interactive section editor
    window.location.href = `teacher-programs.php?action=edit_interactive&program_id=${programId}&chapter_id=${chapterId}&story_id=${storyId}&section_id=${sectionId}`;
}

function deleteSection(sectionId) {
    Swal.fire({
        title: 'Delete Interactive Section?',
        text: 'This will permanently delete this section and all its questions.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting Section...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            fetch('../../php/quiz-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_interactive_section',
                    section_id: sectionId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Interactive section has been deleted successfully.',
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to delete interactive section.',
                        icon: 'error',
                        confirmButtonColor: '#3b82f6'
                    });
                }
            })
            .catch(() => {
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

function toggleSectionMenu(sectionId) {
    // Close all other menus
    document.querySelectorAll('[id^="menu-"]').forEach(menu => {
        if (menu.id !== `menu-${sectionId}`) {
            menu.classList.add('hidden');
        }
    });
    
    // Toggle current menu
    const menu = document.getElementById(`menu-${sectionId}`);
    if (menu) menu.classList.toggle('hidden');
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.relative')) {
        document.querySelectorAll('[id^="menu-"]').forEach(menu => menu.classList.add('hidden'));
    }
});

// Update create button state
function updateCreateButton() {
    const createBtn = document.getElementById('createSectionBtn');
    if (currentSectionCount >= maxSections && createBtn) {
        createBtn.disabled = true;
        createBtn.classList.add('opacity-50', 'cursor-not-allowed');
        createBtn.onclick = function() {
            Swal.fire({
                title: 'Maximum Sections Reached',
                text: `Each story can have a maximum of ${maxSections} interactive sections.`,
                icon: 'info',
                confirmButtonColor: '#3b82f6'
            });
        };
    }
}

// Initialize
updateCreateButton();
</script>

<style>
.section-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.section-card:hover {
    transform: translateY(-2px);
}
.section-card .relative {
    position: relative;
}
.section-card .absolute {
    position: absolute;
    z-index: 20;
}
</style>