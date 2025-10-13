<?php
session_start();
$current_page = "teacher-programs";
$page_title = "My Programs";

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

// Include required files
require '../../php/dbConnection.php';
require '../../php/enhanced-program-functions.php';

$teacher_id = $_SESSION['userID'];
$action = $_GET['action'] ?? 'list';
$program_id = $_GET['program_id'] ?? null;
$chapter_id = $_GET['chapter_id'] ?? null;
$story_id = $_GET['story_id'] ?? null;

// Handle different actions
switch ($action) {
    case 'create':
        $pageContent = 'program_details';
        $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
        break;
    case 'edit_chapter':
        $pageContent = 'chapter_content';
        $program = getProgram($conn, $program_id, $teacher_id);
        $chapter = getChapter($conn, $chapter_id);
        break;
    case 'add_story':
        $pageContent = 'story_form';
        $program = getProgram($conn, $program_id, $teacher_id);
        $chapter = getChapter($conn, $chapter_id);
        $story = $story_id ? getStory($conn, $story_id) : null;
        break;
    case 'add_quiz':
        $pageContent = 'quiz_form';
        $program = getProgram($conn, $program_id, $teacher_id);
        $chapter = getChapter($conn, $chapter_id);
        $quiz = getChapterQuiz($conn, $chapter_id);
        break;
    default:
        $pageContent = 'programs_list';
        $myPrograms = getTeacherPrograms($conn, $teacher_id);
        $allPrograms = getAllPublishedPrograms($conn);
        break;
}

// Handle success/error messages
$success = $_SESSION['success_message'] ?? null;
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>

<!-- SweetAlert2 and other dependencies -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="page-container">
    <div class="page-content">
        
        <?php if ($pageContent === 'programs_list'): ?>
            <!-- MAIN PROGRAMS LIST VIEW -->
            <section class="content-section">
                <h1 class="section-title md:text-2xl font-bold">My Programs</h1>
                
                <!-- Quick Access Toolbar -->
                <?php include '../../components/quick-access.php'; ?>
                
                <!-- My Programs Section -->
                <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start">
                    <div class="w-full flex gap-[25px] items-center justify-start">
                        <div class="flex items-center gap-[10px] p-[10px] text-company_orange">
                            <i class="ph ph-user-circle text-[24px]"></i>
                            <p class="body-text2-semibold">My Programs</p>
                        </div>
                    </div>
                    
                    <?php if (empty($myPrograms)): ?>
                        <div class="w-full text-center py-8">
                            <p class="text-gray-500">No programs created yet. Create your first program!</p>
                            <a href="?action=create" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                Create New Program
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($myPrograms as $program): ?>
                                <div class="program-card bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <div class="relative">
                                        <img src="<?= $program['thumbnail'] ? '../../uploads/thumbnails/' . $program['thumbnail'] : '../../images/default-program.jpg' ?>" 
                                             alt="<?= htmlspecialchars($program['title']) ?>" 
                                             class="w-full h-48 object-cover rounded-t-lg">
                                        <span class="absolute top-2 right-2 px-2 py-1 text-xs rounded-full 
                                            <?= $program['status'] === 'published' ? 'bg-green-100 text-green-800' : 
                                               ($program['status'] === 'pending_review' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                                            <?= ucfirst(str_replace('_', ' ', $program['status'])) ?>
                                        </span>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-lg mb-2"><?= htmlspecialchars($program['title']) ?></h3>
                                        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?= htmlspecialchars(substr($program['description'], 0, 100)) ?>...</p>
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="text-lg font-bold text-blue-600">₱<?= number_format($program['price'], 2) ?></span>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"><?= $program['category'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-gray-500"><?= date('M d, Y', strtotime($program['dateCreated'])) ?></span>
                                            <div class="flex gap-2">
                                                <a href="?action=create&program_id=<?= $program['programID'] ?>" 
                                                   class="text-blue-500 hover:text-blue-700 text-sm">
                                                    <i class="ph ph-pencil-simple"></i> Edit
                                                </a>
                                                <button onclick="viewProgram(<?= $program['programID'] ?>)" 
                                                        class="text-green-500 hover:text-green-700 text-sm">
                                                    <i class="ph ph-eye"></i> View
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Program Library Section -->
                <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start mt-8">
                    <div class="w-full flex gap-[25px] items-center justify-start">
                        <div class="flex items-center gap-[10px] p-[10px] text-company_blue">
                            <i class="ph ph-books text-[24px]"></i>
                            <p class="body-text2-semibold">Program Library</p>
                        </div>
                    </div>
                    
                    <?php if (empty($allPrograms)): ?>
                        <div class="w-full text-center py-8">
                            <p class="text-gray-500">No programs available in the library yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <?php foreach ($allPrograms as $program): ?>
                                <div class="program-card-small bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <img src="<?= $program['thumbnail'] ? '../../uploads/thumbnails/' . $program['thumbnail'] : '../../images/default-program.jpg' ?>" 
                                         alt="<?= htmlspecialchars($program['title']) ?>" 
                                         class="w-full h-32 object-cover rounded-t-lg">
                                    <div class="p-3">
                                        <h4 class="font-medium text-sm mb-1"><?= htmlspecialchars($program['title']) ?></h4>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-bold text-blue-600">₱<?= number_format($program['price'], 2) ?></span>
                                            <span class="px-1 py-0.5 bg-gray-100 text-gray-600 text-xs rounded"><?= $program['category'] ?></span>
                                        </div>
                                        <button onclick="viewProgram(<?= $program['programID'] ?>)" 
                                                class="mt-2 w-full text-center text-blue-500 hover:text-blue-700 text-xs">
                                            <i class="ph ph-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
        <?php elseif ($pageContent === 'program_details'): ?>
            <!-- PROGRAM DETAILS FORM -->
            <?php include '../../components/program-details-form.php'; ?>
            
        <?php elseif ($pageContent === 'chapter_content'): ?>
            <!-- CHAPTER CONTENT MANAGEMENT -->
            <?php include '../../components/chapter-content-form.php'; ?>
            
        <?php elseif ($pageContent === 'story_form'): ?>
            <!-- STORY CREATION/EDIT FORM -->
            <?php include '../../components/story-form.php'; ?>
            
        <?php elseif ($pageContent === 'quiz_form'): ?>
            <!-- QUIZ CREATION/EDIT FORM -->
            <?php include '../../components/quiz-form.php'; ?>
            
        <?php endif; ?>
        
    </div>
</div>

<!-- Back to Top Button -->
<button type="button" onclick="scrollToTop()" 
        class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition z-50" 
        id="scroll-to-top">
    <i class="ph ph-arrow-up text-xl"></i>
</button>

<!-- Include JavaScript files -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../components/navbar.js"></script>
<script src="../../dist/javascript/enhanced-program-management.js"></script>

<script>
// Display success/error messages
<?php if ($success): ?>
    Swal.fire({
        title: 'Success!',
        text: '<?= addslashes($success) ?>',
        icon: 'success'
    });
<?php endif; ?>

<?php if ($error): ?>
    Swal.fire({
        title: 'Error!',
        text: '<?= addslashes($error) ?>',
        icon: 'error'
    });
<?php endif; ?>

// Scroll to top functionality
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('scroll', function() {
    const btn = document.getElementById('scroll-to-top');
    if (window.pageYOffset > 300) {
        btn.classList.remove('hidden');
    } else {
        btn.classList.add('hidden');
    }
});

// View program details
function viewProgram(programId) {
    window.open(`program-overview.php?id=${programId}`, '_blank');
}
</script>

</body>
</html>