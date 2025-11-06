<?php
session_start();
require '../../php/dbConnection.php';
require '../../php/functions.php';
require_once '../../php/youtube-embed-helper.php';

// Guard
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$studentID = (int)$_SESSION['userID'];
$programID = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$currentChapterID = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

if ($programID <= 0) { header('Location: student-programs.php'); exit(); }

// Get program with enrollment status
$program = getProgramDetails($conn, $programID, $studentID);
if (!$program) { header('Location: student-programs.php?tab=all'); exit(); }

$isEnrolled = !empty($program['is_enrolled']);

// Handle enroll request (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    if (!$isEnrolled) {
        if (enrollStudentInProgram($conn, $studentID, $programID)) {
            header('Location: student-program-view.php?program_id='.$programID);
            exit();
        }
    }
}

// If not enrolled, show the original design
if (!$isEnrolled) {
    // Include original design for non-enrolled students
    $current_page = 'student-programs';
    $page_title = 'Program Details';
    include '../../components/header.php';
    include '../../components/student-nav.php';
    include 'student-program-view-original.php';
    include '../../components/footer.php';
    exit();
}

// For enrolled students, show the new sidebar design
// Fetch chapters using existing function
$chapters = fetchChapters($conn, $programID);

// Get teacher info
$tidStmt = $conn->prepare('SELECT teacherID FROM programs WHERE programID = ?');
$tidStmt->bind_param('i', $programID);
$tidStmt->execute();
$tidRes = $tidStmt->get_result()->fetch_assoc();
$teacherID = $tidRes['teacherID'] ?? null;
$teacher = null;
if ($teacherID) {
    $teacherStmt = $conn->prepare('SELECT t.teacherID, t.fname, t.lname, t.specialization, t.profile_picture FROM teacher t WHERE t.teacherID = ?');
    $teacherStmt->bind_param('i', $teacherID);
    $teacherStmt->execute();
    $teacher = $teacherStmt->get_result()->fetch_assoc();
}

// Get student progress
$completion = (float)($program['completion_percentage'] ?? 0);

// Get current content (chapter)
$currentContent = null;
if ($currentChapterID > 0) {
    // Find the current chapter
    foreach ($chapters as $chapter) {
        if ($chapter['chapter_id'] == $currentChapterID) {
            $currentContent = $chapter;
            $currentContent['type'] = 'chapter';
            $currentContent['id'] = $chapter['chapter_id'];
            break;
        }
    }
} else {
    // Default to first chapter if available
    if (!empty($chapters)) {
        $currentContent = $chapters[0];
        $currentContent['type'] = 'chapter';
        $currentContent['id'] = $chapters[0]['chapter_id'];
    }
}

// Add video completion and interaction status (simplified)
if ($currentContent) {
    $currentContent['video_watched'] = true; // For now, assume videos are always watchable
    $currentContent['interactive_completed'] = false;
    $currentContent['can_proceed'] = true;
    $currentContent['video_url'] = $currentContent['video_url'] ?? '';
}

$current_page = 'student-programs';
$page_title = htmlspecialchars($program['title']);
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<style>
.sidebar {
    transition: transform 0.3s ease;
}

.sidebar.closed {
    transform: translateX(-100%);
}

.content-area {
    transition: margin-left 0.3s ease;
}

.content-area.sidebar-closed {
    margin-left: 0 !important;
}

.chapter-item {
    cursor: pointer;
    transition: all 0.2s ease;
}

.chapter-item:hover {
    background-color: #f8f9fa;
}

.chapter-item.current {
    background-color: #e0f2fe;
    border-color: #10375B;
}

.progress-bar {
    transition: width 0.3s ease;
}

.next-content-btn {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
    transition: all 0.3s ease;
}

.next-content-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        z-index: 50;
        width: 280px;
        transform: translateX(-100%);
    }
    
    .sidebar.open {
        transform: translateX(0);
    }
    
    .content-area {
        margin-left: 0 !important;
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 40;
    }
}
</style>

<div class="page-container min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar fixed top-0 left-0 h-full w-80 bg-white shadow-lg z-30 overflow-y-auto">
        <!-- Sidebar Header -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 truncate" title="<?= htmlspecialchars($program['title']) ?>">
                    <?= htmlspecialchars(mb_strimwidth($program['title'], 0, 25, '...')) ?>
                </h2>
                <button id="sidebarToggle" class="p-2 rounded-lg hover:bg-gray-100 md:hidden">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            
            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Progress</span>
                    <span class="text-sm font-bold text-[#10375B]"><?= number_format($completion, 1) ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="progress-bar bg-[#10375B] h-2 rounded-full" style="width: <?= $completion ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Chapters -->
        <div class="p-4">
            <?php if (empty($chapters)): ?>
                <p class="text-gray-500 text-sm">No chapters available for this program.</p>
            <?php else: ?>
                <?php foreach ($chapters as $index => $chapter): ?>
                    <div class="chapter-section mb-3">
                        <!-- Chapter Header -->
                        <div class="chapter-item p-3 rounded-lg border border-gray-200 <?= ($currentContent && $currentContent['id'] == $chapter['chapter_id']) ? 'current' : '' ?>" 
                             data-chapter-id="<?= $chapter['chapter_id'] ?>" 
                             onclick="loadContent('chapter', <?= $chapter['chapter_id'] ?>)">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-play-circle text-[#10375B]"></i>
                                    <span class="font-medium text-sm"><?= htmlspecialchars($chapter['title']) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stories (simplified - showing chapter content as stories) -->
                        <?php if (!empty($chapter['content'])): ?>
                            <div class="stories mt-2">
                                <div class="story-item p-2 rounded-lg mb-1 text-sm ml-4 pl-4 border-l-2 border-gray-200 hover:border-[#10375B] hover:bg-gray-50" 
                                     onclick="loadContent('chapter', <?= $chapter['chapter_id'] ?>)">
                                    <div class="flex items-center gap-2">
                                        <i class="ph ph-file-text text-[#A58618] text-xs"></i>
                                        <span>Story: <?= htmlspecialchars($chapter['title']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar Overlay for Mobile -->
    <div id="sidebarOverlay" class="sidebar-overlay hidden" onclick="toggleSidebar()"></div>
    
    <!-- Main Content Area -->
    <div id="contentArea" class="content-area ml-80 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm border-b border-gray-200 p-4">
            <div class="flex items-center gap-4">
                <button id="sidebarToggleMain" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="ph ph-sidebar text-xl text-gray-600"></i>
                </button>
                <h1 class="text-xl font-bold text-gray-900">
                    <?= $currentContent ? htmlspecialchars($currentContent['title']) : 'Select Content' ?>
                </h1>
            </div>
        </div>
        
        <!-- Content Display -->
        <div id="contentDisplay" class="p-6">
            <?php if ($currentContent): ?>
                <div class="max-w-4xl mx-auto">
                    <!-- Video Section -->
                    <?php if (!empty($currentContent['video_url'])): ?>
                        <div class="mb-8">
                            <div class="video-container relative rounded-lg overflow-hidden bg-black">
                                <?php 
                                $embedUrl = toYouTubeEmbedUrl($currentContent['video_url']);
                                if ($embedUrl): 
                                ?>
                                    <div class="relative w-full pb-[56.25%] h-0">
                                        <iframe id="contentVideo" 
                                                class="absolute top-0 left-0 w-full h-full" 
                                                src="<?= htmlspecialchars($embedUrl) ?>?enablejsapi=1&rel=0" 
                                                title="Content Video" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture;" 
                                                allowfullscreen>
                                        </iframe>
                                    </div>
                                <?php else: ?>
                                    <div class="aspect-video flex items-center justify-center bg-gray-100">
                                        <p class="text-gray-500">Video not available. <a href="<?= htmlspecialchars($currentContent['video_url']) ?>" target="_blank" class="text-blue-600 underline">Watch on external site</a></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content Text -->
                    <?php if (!empty($currentContent['content'])): ?>
                        <div class="mb-8">
                            <div class="prose max-w-none">
                                <h2 class="text-2xl font-bold mb-4">Chapter Content</h2>
                                <div class="bg-white p-6 rounded-lg border">
                                    <?= nl2br(htmlspecialchars($currentContent['content'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Interactive Section -->
                    <?php if (!empty($currentContent['question'])): ?>
                        <div class="mb-8 p-6 bg-gray-50 rounded-lg border">
                            <h3 class="text-lg font-bold mb-4">Interactive Section</h3>
                            <div class="mb-4">
                                <p class="mb-4"><?= nl2br(htmlspecialchars($currentContent['question'])) ?></p>
                                
                                <?php if ($currentContent['question_type'] === 'multiple_choice' && !empty($currentContent['answer_options'])): ?>
                                    <?php 
                                    $options = json_decode($currentContent['answer_options'], true) ?: [];
                                    if ($options) {
                                        foreach ($options as $index => $option): 
                                    ?>
                                        <div class="block mb-2 p-3 rounded border bg-white">
                                            <span class="font-medium"><?= chr(65 + $index) ?>.</span> <?= htmlspecialchars($option) ?>
                                        </div>
                                    <?php 
                                        endforeach;
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-sm text-gray-600">
                                <p><strong>Note:</strong> This is a preview of the interactive section. Full functionality will be available after the database migration is complete.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="max-w-4xl mx-auto text-center py-12">
                    <i class="ph ph-book-open text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-700 mb-2">Welcome to <?= htmlspecialchars($program['title']) ?></h2>
                    <p class="text-gray-500">Select a chapter from the sidebar to begin learning</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Next Content Button -->
    <?php if ($currentContent): ?>
        <?php
        // Find next chapter
        $nextChapter = null;
        $currentIndex = -1;
        foreach ($chapters as $index => $chapter) {
            if ($chapter['chapter_id'] == $currentContent['id']) {
                $currentIndex = $index;
                break;
            }
        }
        if ($currentIndex >= 0 && isset($chapters[$currentIndex + 1])) {
            $nextChapter = $chapters[$currentIndex + 1];
        }
        ?>
        <?php if ($nextChapter): ?>
            <button id="nextContentBtn" 
                    class="next-content-btn px-6 py-3 bg-[#A58618] text-white rounded-lg font-semibold shadow-lg hover:bg-[#8a6f15]"
                    onclick="loadContent('chapter', <?= $nextChapter['chapter_id'] ?>)">
                Next: <?= htmlspecialchars($nextChapter['title']) ?>
            </button>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Sidebar functionality
let sidebarOpen = window.innerWidth > 768;

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const contentArea = document.getElementById('contentArea');
    
    sidebarOpen = !sidebarOpen;
    
    if (window.innerWidth <= 768) {
        // Mobile behavior
        if (sidebarOpen) {
            sidebar.classList.add('open');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        }
    } else {
        // Desktop behavior
        if (sidebarOpen) {
            sidebar.classList.remove('closed');
            contentArea.classList.remove('sidebar-closed');
        } else {
            sidebar.classList.add('closed');
            contentArea.classList.add('sidebar-closed');
        }
    }
}

// Content loading
function loadContent(type, id) {
    const url = new URL(window.location);
    if (type === 'chapter') {
        url.searchParams.set('chapter_id', id);
    }
    window.location.href = url.toString();
}

// Event listeners
document.getElementById('sidebarToggle')?.addEventListener('click', toggleSidebar);
document.getElementById('sidebarToggleMain')?.addEventListener('click', toggleSidebar);

// Mobile responsiveness
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        document.getElementById('sidebarOverlay').classList.add('hidden');
        document.getElementById('sidebar').classList.remove('open');
        sidebarOpen = true;
    } else {
        sidebarOpen = false;
    }
});

// Initialize sidebar state
if (window.innerWidth <= 768) {
    sidebarOpen = false;
    document.getElementById('sidebar').classList.remove('open');
}
</script>

<?php include '../../components/footer.php'; ?>