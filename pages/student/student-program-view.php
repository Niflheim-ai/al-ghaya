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
$currentStoryID = isset($_GET['story_id']) ? (int)$_GET['story_id'] : 0;

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
    include 'student-program-view-original.php';
    exit();
}

// For enrolled students, show the new sidebar design
// Fetch chapters with stories
$chapters = fetchChaptersWithStories($conn, $programID);
$teacher = getTeacherInfo($conn, $programID);
$studentProgress = getStudentProgress($conn, $studentID, $programID);

// Get current content (chapter or story)
$currentContent = null;
if ($currentStoryID > 0) {
    $currentContent = getStoryContent($conn, $currentStoryID, $studentID);
} elseif ($currentChapterID > 0) {
    $currentContent = getChapterContent($conn, $currentChapterID, $studentID);
} else {
    // Default to first unlocked chapter/story
    $currentContent = getFirstAvailableContent($conn, $programID, $studentID);
}

// Handle content completion tracking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'mark_video_watched' && isset($_POST['content_id'], $_POST['content_type'])) {
        markVideoWatched($conn, $studentID, $_POST['content_id'], $_POST['content_type']);
        echo json_encode(['success' => true]);
        exit();
    }
    
    if ($action === 'submit_interactive' && isset($_POST['content_id'], $_POST['answers'])) {
        $result = submitInteractiveAnswers($conn, $studentID, $_POST['content_id'], $_POST['answers']);
        echo json_encode($result);
        exit();
    }
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

.story-item {
    margin-left: 1rem;
    padding-left: 1rem;
    border-left: 2px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.2s ease;
}

.story-item:hover {
    background-color: #f1f5f9;
    border-left-color: #10375B;
}

.story-item.locked {
    opacity: 0.5;
    cursor: not-allowed;
}

.story-item.current {
    background-color: #e0f2fe;
    border-left-color: #10375B;
}

.chapter-item.current {
    background-color: #e0f2fe;
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

.video-container {
    position: relative;
}

.video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    z-index: 10;
}

@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        z-index: 50;
        width: 280px;
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
                    <span class="text-sm font-bold text-[#10375B]"><?= number_format($studentProgress['completion_percentage'], 1) ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="progress-bar bg-[#10375B] h-2 rounded-full" style="width: <?= $studentProgress['completion_percentage'] ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Chapters and Stories -->
        <div class="p-4">
            <?php if (empty($chapters)): ?>
                <p class="text-gray-500 text-sm">No chapters available for this program.</p>
            <?php else: ?>
                <?php foreach ($chapters as $chapter): ?>
                    <div class="chapter-section mb-3">
                        <!-- Chapter Header -->
                        <div class="chapter-item p-3 rounded-lg border border-gray-200 <?= ($currentContent && $currentContent['type'] === 'chapter' && $currentContent['id'] == $chapter['chapter_id']) ? 'current' : '' ?>" 
                             data-chapter-id="<?= $chapter['chapter_id'] ?>" 
                             onclick="loadContent('chapter', <?= $chapter['chapter_id'] ?>)">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <?php if ($chapter['is_unlocked']): ?>
                                        <i class="ph ph-play-circle text-[#10375B]"></i>
                                    <?php else: ?>
                                        <i class="ph ph-lock text-gray-400"></i>
                                    <?php endif; ?>
                                    <span class="font-medium text-sm"><?= htmlspecialchars($chapter['title']) ?></span>
                                    <?php if ($chapter['is_completed']): ?>
                                        <i class="ph ph-check-circle text-green-500"></i>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($chapter['stories'])): ?>
                                    <button class="chapter-toggle p-1 rounded hover:bg-gray-100" onclick="event.stopPropagation(); toggleChapterStories(<?= $chapter['chapter_id'] ?>)">
                                        <i class="ph ph-caret-down text-gray-400 transition-transform" id="toggle-<?= $chapter['chapter_id'] ?>"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Stories -->
                        <?php if (!empty($chapter['stories'])): ?>
                            <div class="stories mt-2" id="stories-<?= $chapter['chapter_id'] ?>">
                                <?php foreach ($chapter['stories'] as $story): ?>
                                    <div class="story-item p-2 rounded-lg mb-1 text-sm <?= $story['is_unlocked'] ? '' : 'locked' ?> <?= ($currentContent && $currentContent['type'] === 'story' && $currentContent['id'] == $story['story_id']) ? 'current' : '' ?>" 
                                         data-story-id="<?= $story['story_id'] ?>" 
                                         onclick="<?= $story['is_unlocked'] ? 'loadContent(\'story\', '.$story['story_id'].')' : 'return false;' ?>">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <?php if ($story['is_unlocked']): ?>
                                                    <i class="ph ph-file-text text-[#A58618] text-xs"></i>
                                                <?php else: ?>
                                                    <i class="ph ph-lock text-gray-400 text-xs"></i>
                                                <?php endif; ?>
                                                <span><?= htmlspecialchars($story['title']) ?></span>
                                                <?php if ($story['is_completed']): ?>
                                                    <i class="ph ph-check-circle text-green-500 text-xs"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar Overlay for Mobile -->
    <div id="sidebarOverlay" class="sidebar-overlay hidden md:hidden" onclick="toggleSidebar()"></div>
    
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
                                    <?php if (!$currentContent['video_watched']): ?>
                                        <div class="video-overlay" id="videoOverlay">
                                            <div class="text-center">
                                                <i class="ph ph-lock text-4xl mb-2"></i>
                                                <p>Complete the video to continue</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
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
                                <?= nl2br(htmlspecialchars($currentContent['content'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Interactive Section -->
                    <?php if (!empty($currentContent['question'])): ?>
                        <div class="mb-8 p-6 bg-gray-50 rounded-lg border">
                            <h3 class="text-lg font-bold mb-4">Interactive Section</h3>
                            <form id="interactiveForm" onsubmit="submitInteractive(event)">
                                <div class="mb-4">
                                    <p class="mb-4"><?= nl2br(htmlspecialchars($currentContent['question'])) ?></p>
                                    
                                    <?php if ($currentContent['question_type'] === 'multiple_choice' && !empty($currentContent['answer_options'])): ?>
                                        <?php 
                                        $options = json_decode($currentContent['answer_options'], true) ?: [];
                                        foreach ($options as $index => $option): 
                                        ?>
                                            <label class="block mb-2 p-3 rounded border cursor-pointer hover:bg-white transition-colors">
                                                <input type="radio" name="answer" value="<?= $index ?>" class="mr-3">
                                                <?= htmlspecialchars($option) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php elseif ($currentContent['question_type'] === 'true_false'): ?>
                                        <label class="block mb-2 p-3 rounded border cursor-pointer hover:bg-white transition-colors">
                                            <input type="radio" name="answer" value="true" class="mr-3">
                                            True
                                        </label>
                                        <label class="block mb-2 p-3 rounded border cursor-pointer hover:bg-white transition-colors">
                                            <input type="radio" name="answer" value="false" class="mr-3">
                                            False
                                        </label>
                                    <?php else: ?>
                                        <textarea name="answer" class="w-full p-3 border rounded-lg" rows="4" placeholder="Enter your answer..."></textarea>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" 
                                        class="px-6 py-2 bg-[#10375B] text-white rounded-lg hover:bg-[#0d2d4a] disabled:opacity-50 disabled:cursor-not-allowed"
                                        <?= $currentContent['interactive_completed'] ? 'disabled' : '' ?>>
                                    <?= $currentContent['interactive_completed'] ? 'Completed' : 'Submit Answer' ?>
                                </button>
                            </form>
                            
                            <!-- Results Display -->
                            <div id="interactiveResults" class="mt-4 hidden">
                                <div class="p-4 rounded-lg" id="resultMessage"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="max-w-4xl mx-auto text-center py-12">
                    <i class="ph ph-book-open text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-700 mb-2">Welcome to <?= htmlspecialchars($program['title']) ?></h2>
                    <p class="text-gray-500">Select a chapter or story from the sidebar to begin learning</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Next Content Button -->
    <?php if ($currentContent && isset($currentContent['next_content'])): ?>
        <button id="nextContentBtn" 
                class="next-content-btn px-6 py-3 bg-[#A58618] text-white rounded-lg font-semibold shadow-lg hover:bg-[#8a6f15] disabled:bg-gray-400"
                onclick="loadNextContent()"
                <?= $currentContent['can_proceed'] ? '' : 'disabled' ?>>
            <?= $currentContent['can_proceed'] ? 'Next: ' . htmlspecialchars($currentContent['next_content']['title']) : 'Complete current content to continue' ?>
        </button>
    <?php endif; ?>
</div>

<script>
// Sidebar functionality
let sidebarOpen = true;

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const contentArea = document.getElementById('contentArea');
    
    sidebarOpen = !sidebarOpen;
    
    if (window.innerWidth <= 768) {
        // Mobile behavior
        if (sidebarOpen) {
            sidebar.classList.remove('closed');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('closed');
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

// Chapter toggle functionality
function toggleChapterStories(chapterId) {
    const stories = document.getElementById(`stories-${chapterId}`);
    const toggle = document.getElementById(`toggle-${chapterId}`);
    
    if (stories.style.display === 'none') {
        stories.style.display = 'block';
        toggle.style.transform = 'rotate(0deg)';
    } else {
        stories.style.display = 'none';
        toggle.style.transform = 'rotate(-90deg)';
    }
}

// Content loading
function loadContent(type, id) {
    const url = new URL(window.location);
    if (type === 'chapter') {
        url.searchParams.set('chapter_id', id);
        url.searchParams.delete('story_id');
    } else {
        url.searchParams.set('story_id', id);
        url.searchParams.delete('chapter_id');
    }
    window.location.href = url.toString();
}

// Video tracking
function onYouTubeIframeAPIReady() {
    if (document.getElementById('contentVideo')) {
        const player = new YT.Player('contentVideo', {
            events: {
                'onStateChange': onPlayerStateChange
            }
        });
    }
}

function onPlayerStateChange(event) {
    if (event.data == YT.PlayerState.ENDED) {
        markVideoWatched();
    }
}

function markVideoWatched() {
    const contentId = <?= $currentContent ? $currentContent['id'] : 0 ?>;
    const contentType = '<?= $currentContent ? $currentContent['type'] : '' ?>';
    
    if (contentId > 0) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_video_watched&content_id=${contentId}&content_type=${contentType}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const overlay = document.getElementById('videoOverlay');
                if (overlay) overlay.remove();
                updateNextButton();
            }
        });
    }
}

// Interactive section handling
function submitInteractive(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const contentId = <?= $currentContent ? $currentContent['id'] : 0 ?>;
    
    const answers = {};
    for (let [key, value] of formData.entries()) {
        answers[key] = value;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=submit_interactive&content_id=${contentId}&answers=${JSON.stringify(answers)}`
    })
    .then(response => response.json())
    .then(data => {
        const results = document.getElementById('interactiveResults');
        const message = document.getElementById('resultMessage');
        
        results.classList.remove('hidden');
        
        if (data.correct) {
            message.className = 'p-4 rounded-lg bg-green-100 border border-green-200 text-green-800';
            message.innerHTML = '<i class="ph ph-check-circle mr-2"></i>Correct! ' + (data.message || '');
            form.querySelector('button').disabled = true;
            form.querySelector('button').textContent = 'Completed';
            updateNextButton();
        } else {
            message.className = 'p-4 rounded-lg bg-red-100 border border-red-200 text-red-800';
            message.innerHTML = '<i class="ph ph-x-circle mr-2"></i>Incorrect. ' + (data.message || 'Please try again.');
        }
    });
}

// Next content functionality
function loadNextContent() {
    const nextContent = <?= $currentContent && isset($currentContent['next_content']) ? json_encode($currentContent['next_content']) : 'null' ?>;
    if (nextContent) {
        loadContent(nextContent.type, nextContent.id);
    }
}

function updateNextButton() {
    // This would be called after video watching or interactive completion
    // In a real implementation, you'd check the current progress and update the button state
    location.reload(); // Simple reload for now
}

// Event listeners
document.getElementById('sidebarToggle').addEventListener('click', toggleSidebar);
document.getElementById('sidebarToggleMain').addEventListener('click', toggleSidebar);

// Mobile responsiveness
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        document.getElementById('sidebarOverlay').classList.add('hidden');
        if (sidebarOpen) {
            document.getElementById('sidebar').classList.remove('closed');
        }
    }
});

// Initialize sidebar state on mobile
if (window.innerWidth <= 768) {
    sidebarOpen = false;
    document.getElementById('sidebar').classList.add('closed');
}

// Load YouTube API
const tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
const firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
</script>

<?php include '../../components/footer.php'; ?>