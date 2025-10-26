<?php
session_start();
$current_page = "teacher-programs";
$page_title = "My Programs";
$debug_mode = false;

if (!isset($_SESSION['userID']) || (($_SESSION['role'] ?? '') !== 'teacher')) { $_SESSION['error_message']='Access denied'; header('Location: ../login.php'); exit(); }

require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';
require_once '../../php/program-helpers.php';

$user_id = (int)$_SESSION['userID'];
$action = $_GET['action'] ?? 'list';
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : null;
$story_id = isset($_GET['story_id']) ? (int)$_GET['story_id'] : null;
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : null;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : null;

$teacher_id = ph_getTeacherIdFromSession($conn, $user_id);
if (!$teacher_id) { $_SESSION['error_message']='Teacher profile not found or inactive.'; header('Location: ../teacher/teacher-dashboard.php'); exit(); }

switch ($action) {
  case 'create':
    $pageContent='program_details';
    $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
    break;
  case 'edit_chapter':
    $pageContent='chapter_content';
    $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? ph_getChapter($conn, $chapter_id) : null;
    if (!$chapter) { $_SESSION['error_message']='Failed to get chapter.'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    $chapterProgramId = isset($chapter['program_id']) ? (int)$chapter['program_id'] : (int)($chapter['programID'] ?? -1);
    if (!$program || (int)($program['programID'] ?? 0) !== $chapterProgramId) { $_SESSION['error_message']='Access denied to chapter.'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    break;
  case 'add_story':
    $pageContent='story_form';
    $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? ph_getChapter($conn, $chapter_id) : null;
    $chapterProgramId = $chapter ? (isset($chapter['program_id']) ? (int)$chapter['program_id'] : (int)($chapter['programID'] ?? -1)) : -1;
    if (!$program || !$chapter || (int)($program['programID'] ?? 0)!==$chapterProgramId) { $_SESSION['error_message']='Invalid program or no permission'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    $story = $story_id ? ph_getStory($conn, $story_id) : null;
    break;
  case 'add_quiz':
    $pageContent='quiz_form';
    $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? ph_getChapter($conn, $chapter_id) : null;
    $chapterProgramId = $chapter ? (isset($chapter['program_id']) ? (int)$chapter['program_id'] : (int)($chapter['programID'] ?? -1)) : -1;
    if (!$program || !$chapter || (int)($program['programID'] ?? 0)!==$chapterProgramId) { $_SESSION['error_message']='Invalid program or no permission'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    $quiz = $chapter_id ? ph_getChapterQuiz($conn, $chapter_id) : null;
    if ($quiz) {
        require_once '../../php/quiz-handler.php';
        $quiz_questions = quizQuestion_getByQuiz($conn, $quiz['quiz_id']);
    }
    break;
  case 'edit_interactive':
    $pageContent='interactive_sections';
    $program = $program_id ? ph_getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? ph_getChapter($conn, $chapter_id) : null;
    $story_data = $story_id ? ph_getStory($conn, $story_id) : null;
    $chapterProgramId = $chapter ? (isset($chapter['program_id']) ? (int)$chapter['program_id'] : (int)($chapter['programID'] ?? -1)) : -1;
    if (!$program || !$chapter || !$story_data || (int)($program['programID'] ?? 0)!==$chapterProgramId) { $_SESSION['error_message']='Invalid program, chapter, or story'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    break;
  default:
    $pageContent='programs_list';
    $myPrograms = ph_getTeacherPrograms($conn, $teacher_id);
    $allPrograms = getPublishedPrograms($conn);
}

$success = $_SESSION['success_message'] ?? null;
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>
<!-- rest of file including components inclusion -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">

<!-- existing page content and dynamic sections here -->

<!-- Back to Top Button -->
<button type="button" onclick="scrollToTop()" class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition z-50" id="scroll-to-top">
    <i class="ph ph-arrow-up text-xl"></i>
</button>

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
    const fd = new FormData(); fd.append('action', 'delete_chapter'); fd.append('chapter_id', chapterId);
    fetch('../../php/program-handler.php', { method: 'POST', body: fd }).then(() => location.reload());
  }
}

// Misc UI helpers
function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }
window.addEventListener('scroll', function() {
  const btn = document.getElementById('scroll-to-top');
  if (btn) { if (window.pageYOffset > 300) { btn.classList.remove('hidden'); } else { btn.classList.add('hidden'); } }
});
</script>

</body>
</html>