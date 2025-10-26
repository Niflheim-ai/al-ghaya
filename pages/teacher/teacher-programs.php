<?php
session_start();
$current_page = "teacher-programs";
$page_title = "My Programs";

if (!isset($_SESSION['userID']) || (($_SESSION['role'] ?? '') !== 'teacher')) {
    $_SESSION['error_message'] = 'Access denied';
    header('Location: ../login.php');
    exit();
}

require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';
require_once '../../php/program-helpers.php';

$user_id = (int)$_SESSION['userID'];
$action = $_GET['action'] ?? 'list';
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : null;
$story_id = isset($_GET['story_id']) ? (int)$_GET['story_id'] : null;

$teacher_id = ph_getTeacherIdFromSession($conn, $user_id);
if (!$teacher_id) {
    $_SESSION['error_message'] = 'Teacher profile not found or inactive.';
    header('Location: ../teacher/teacher-dashboard.php');
    exit();
}

// Route handling
switch ($action) {
    case 'create':
        $pageContent = 'program_details';
        $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
        break;
        
    case 'edit_chapter':
        $pageContent = 'chapter_content';
        $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
        $chapter = $chapter_id ? ph_getChapter($conn, $chapter_id) : null;
        if (!$chapter) {
            $_SESSION['error_message'] = 'Failed to get chapter.';
            header('Location: ?action=create&program_id=' . (int)$program_id);
            exit();
        }
        $chapterProgramId = isset($chapter['program_id']) ? (int)$chapter['program_id'] : (int)($chapter['programID'] ?? -1);
        if (!$program || (int)($program['programID'] ?? 0) !== $chapterProgramId) {
            $_SESSION['error_message'] = 'Access denied to chapter.';
            header('Location: ?action=create&program_id=' . (int)$program_id);
            exit();
        }
        break;
        
    case 'add_story':
        $pageContent = 'story_form';
        $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
        $chapter = $chapter_id ? ph_getChapter($conn, $chapter_id) : null;
        $story = $story_id ? ph_getStory($conn, $story_id) : null;
        break;
        
    case 'add_quiz':
        $pageContent = 'quiz_form';
        $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
        $chapter = $chapter_id ? ph_getChapter($conn, $chapter_id) : null;
        $quiz = $chapter_id ? ph_getChapterQuiz($conn, $chapter_id) : null;
        break;
        
    case 'edit_interactive':
        $pageContent = 'interactive_sections';
        $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
        $chapter = $chapter_id ? ph_getChapter($conn, $chapter_id) : null;
        $story = $story_id ? ph_getStory($conn, $story_id) : null;
        break;
        
    default:
        $pageContent = 'programs_list';
        $myPrograms = ph_getTeacherPrograms($conn, $teacher_id);
        $allPrograms = getPublishedPrograms($conn);
}

$success = $_SESSION['success_message'] ?? null;
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Al Ghaya</title>
    <link rel="stylesheet" href="../../dist/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
    <style>
        .content-section { padding: 2rem 1rem; max-width: 1200px; margin: 0 auto; }
        .section-title { color: #10375B; font-weight: 700; margin-bottom: 0.5rem; }
        .bg-company_white { background-color: #ffffff; }
        .text-company_blue { color: #10375B; }
        .text-company_orange { color: #F97316; }
        .bg-company_blue { background-color: #10375B; }
        .bg-company_orange { background-color: #F97316; }
        .body-text2-semibold { font-weight: 600; font-size: 1rem; line-height: 1.5; }
        .body-text3-semibold { font-weight: 600; font-size: 0.875rem; line-height: 1.25; }
        .rounded-\[40px\] { border-radius: 40px; }
        .rounded-\[20px\] { border-radius: 20px; }
        .rounded-\[10px\] { border-radius: 10px; }
        .gap-\[20px\] { gap: 20px; }
        .gap-\[25px\] { gap: 25px; }
        .gap-\[10px\] { gap: 10px; }
        .p-\[20px\] { padding: 20px; }
        .p-\[10px\] { padding: 10px; }
    </style>
</head>
<body class="bg-gray-50">

<?php include '../../components/teacher-nav.php'; ?>

<main class="main-content">
    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50" id="success-toast">
            <i class="ph ph-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50" id="error-toast">
            <i class="ph ph-warning-circle mr-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Dynamic Content Based on Action -->
    <?php
    switch ($pageContent) {
        case 'program_details':
            include '../../components/program-details-form.php';
            break;
            
        case 'chapter_content':
            include '../../components/chapter-content-form.php';
            break;
            
        case 'story_form':
            include '../../components/story-form.php';
            break;
            
        case 'quiz_form':
            include '../../components/quiz-form.php';
            break;
            
        case 'interactive_sections':
            include '../../components/interactive-sections.php';
            break;
            
        default: // programs_list
            include '../../components/quick-access.php';
            include '../../components/teacher-cards-template.php';
            break;
    }
    ?>
</main>

<!-- Back to Top Button -->
<button type="button" onclick="scrollToTop()" class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition z-50" id="scroll-to-top">
    <i class="ph ph-arrow-up text-xl"></i>
</button>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../components/navbar.js"></script>

<script>
// Global navigation helpers
function editChapter(chapterId, programId) {
    try {
        chapterId = parseInt(chapterId, 10);
        programId = parseInt(programId, 10);
        if (!chapterId || !programId) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Invalid data', text: 'Missing program or chapter ID.' });
            } else {
                alert('Missing program or chapter ID.');
            }
            return;
        }
        window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${programId}&chapter_id=${chapterId}`;
    } catch (e) {
        console.error('editChapter failed', e);
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Navigation error', text: 'Could not open the chapter editor.' });
        } else {
            alert('Could not open the chapter editor.');
        }
    }
}

function viewChapter(chapterId, programId) {
    window.location.href = `teacher-programs.php?action=edit_chapter&program_id=${parseInt(programId,10)}&chapter_id=${parseInt(chapterId,10)}`;
}

function deleteChapterConfirm(chapterId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Chapter?',
            text: 'This will delete the chapter, its stories, quiz, and interactive sections.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it'
        }).then((r) => {
            if (r.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'delete_chapter');
                fd.append('chapter_id', chapterId);
                fetch('../../php/program-handler.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', 'Chapter deleted successfully.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message || 'Failed to delete chapter.', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Network error. Try again.', 'error'));
            }
        });
    } else if (confirm('Delete this chapter?')) {
        const fd = new FormData();
        fd.append('action', 'delete_chapter');
        fd.append('chapter_id', chapterId);
        fetch('../../php/program-handler.php', { method: 'POST', body: fd })
            .then(() => location.reload());
    }
}

// Misc UI helpers
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('scroll', function() {
    const btn = document.getElementById('scroll-to-top');
    if (btn) {
        if (window.pageYOffset > 300) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    }
});

// Auto-hide toast messages
setTimeout(() => {
    const successToast = document.getElementById('success-toast');
    const errorToast = document.getElementById('error-toast');
    if (successToast) successToast.style.display = 'none';
    if (errorToast) errorToast.style.display = 'none';
}, 5000);
</script>

</body>
</html>