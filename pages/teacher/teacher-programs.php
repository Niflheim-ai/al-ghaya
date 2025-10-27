<?php
session_start();
$current_page = "teacher-programs";
$page_title = "My Programs";
$debug_mode = false;

if (!isset($_SESSION['userID']) || (($_SESSION['role'] ?? '') !== 'teacher')) { $_SESSION['error_message']='Access denied'; header('Location: ../login.php'); exit(); }

require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';
require_once '../../php/program-core.php'; // unified core (handlers + helpers)

$user_id = (int)$_SESSION['userID'];
$action = $_GET['action'] ?? 'list';
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : null;
$story_id = isset($_GET['story_id']) ? (int)$_GET['story_id'] : null;

$teacher_id = getTeacherIdFromSession($conn, $user_id);
if (!$teacher_id) { $_SESSION['error_message']='Teacher profile not found or inactive.'; header('Location: ../teacher/teacher-dashboard.php'); exit(); }

switch ($action) {
  case 'create':
    $pageContent='program_details';
    $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
    break;
  case 'edit_chapter':
    $pageContent='chapter_content';
    $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
    if (!$chapter) { $_SESSION['error_message']='Failed to get chapter.'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    if (!$program || (int)($program['programID']??0) !== (int)($chapter['programID']??-1)) { $_SESSION['error_message']='Access denied to chapter.'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    break;
  case 'add_story':
    $pageContent='story_form';
    $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
    if (!$program || !$chapter || (int)($program['programID']??0)!==(int)($chapter['programID']??-1)) { $_SESSION['error_message']='Invalid program or no permission'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    $story = $story_id ? getStory($conn, $story_id) : null;
    break;
  case 'add_quiz':
    $pageContent='quiz_form';
    $program = $program_id ? getProgram($conn, $program_id, $teacher_id) : null;
    $chapter = $chapter_id ? getChapter($conn, $chapter_id) : null;
    if (!$program || !$chapter || (int)($program['programID']??0)!==(int)($chapter['programID']??-1)) { $_SESSION['error_message']='Invalid program or no permission'; header('Location: ?action=create&program_id='.(int)$program_id); exit(); }
    $quiz = $chapter_id ? getChapterQuiz($conn, $chapter_id) : null;
    break;
  default:
    $pageContent='programs_list';
    $myPrograms = getTeacherPrograms($conn, $teacher_id);
    $allPrograms = getPublishedPrograms($conn);
}

$success = $_SESSION['success_message'] ?? null;
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">

<div class="page-container">
    <div class="page-content">
        
        <?php if ($pageContent === 'programs_list'): ?>
            <section class="content-section">
                <h1 class="section-title md:text-2xl font-bold">My Programs</h1>
                
                <?php include '../../components/quick-access.php'; ?>
                
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
                                        <img src="<?= !empty($program['thumbnail']) && $program['thumbnail'] !== 'default-thumbnail.jpg' ? '../../uploads/thumbnails/' . htmlspecialchars($program['thumbnail']) : '../../images/default-program.jpg' ?>" 
                                             alt="<?= htmlspecialchars($program['title']) ?>" 
                                             class="w-full h-48 object-cover rounded-t-lg">
                                        <span class="absolute top-2 right-2 px-2 py-1 text-xs rounded-full 
                                            <?= ($program['status'] ?? 'draft') === 'published' ? 'bg-green-100 text-green-800' : 
                                               (($program['status'] ?? 'draft') === 'pending_review' ? 'bg-blue-100 text-blue-800' : 
                                               (($program['status'] ?? 'draft') === 'archived' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800')) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $program['status'] ?? 'draft')) ?>
                                        </span>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-lg mb-2"><?= htmlspecialchars($program['title']) ?></h3>
                                        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?= htmlspecialchars(substr($program['description'] ?? '', 0, 100)) ?>...</p>
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="text-lg font-bold text-blue-600">₱<?= number_format($program['price'], 2) ?></span>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"><?= htmlspecialchars($program['category']) ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-gray-500">Updated <?= date('M d, Y', strtotime($program['dateUpdated'] ?? $program['dateCreated'])) ?></span>
                                            <div class="flex gap-2">
                                                <a href="?action=create&program_id=<?= $program['programID'] ?>" 
                                                   class="text-blue-500 hover:text-blue-700 text-sm">
                                                    <i class="ph ph-pencil-simple"></i> Edit
                                                </a>
                                                <button onclick="archiveProgram(<?= $program['programID'] ?>)" 
                                                        class="text-gray-600 hover:text-gray-800 text-sm">
                                                    <i class="ph ph-archive"></i> Archive
                                                </button>
                                                <button onclick="deleteProgram(<?= $program['programID'] ?>)" 
                                                        class="text-red-500 hover:text-red-700 text-sm">
                                                    <i class="ph ph-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="w-full h-fit flex flex-col bg-company_white gap-[20px] p-[20px] rounded-[40px] items-start justify-start mt-8">
                    <div class="w-full flex gap-[25px] items-center justify-start">
                        <div class="flex items-center gap-[10px] p-[10px] text-company_blue">
                            <i class="ph ph-books text-[24px]"></i>
                            <p class="body-text2-semibold">Program Library</p>
                        </div>
                    </div>
                    
                    <?php if (empty($allPrograms)): ?>
                        <div class="w-full text-center py-8">
                            <p class="text-gray-500">No published programs yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <?php foreach ($allPrograms as $program): ?>
                                <div class="program-card-small bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <img src="<?= !empty($program['thumbnail']) && $program['thumbnail'] !== 'default-thumbnail.jpg' ? '../../uploads/thumbnails/' . htmlspecialchars($program['thumbnail']) : '../../images/default-program.jpg' ?>" 
                                         alt="<?= htmlspecialchars($program['title']) ?>" 
                                         class="w-full h-32 object-cover rounded-t-lg">
                                    <div class="p-3">
                                        <h4 class="font-medium text-sm mb-1"><?= htmlspecialchars($program['title']) ?></h4>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-bold text-blue-600">₱<?= number_format($program['price'], 2) ?></span>
                                            <span class="px-1 py-0.5 bg-gray-100 text-gray-600 text-xs rounded"><?= htmlspecialchars($program['category']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
        <?php elseif ($pageContent === 'program_details'): ?>
            <?php include '../../components/program-details-form.php'; ?>
        <?php elseif ($pageContent === 'chapter_content'): ?>
            <?php include '../../components/chapter-content-form.php'; ?>
        <?php elseif ($pageContent === 'story_form'): ?>
            <?php include '../../components/story-form.php'; ?>
        <?php else: ?>
            <section class="content-section">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Feature Coming Soon</h2>
                    <p class="text-gray-600 mb-6">This feature is under development and will be available soon.</p>
                    <a href="?action=list" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Back to Programs
                    </a>
                </div>
            </section>
        <?php endif; ?>
        
    </div>
</div>

<button type="button" onclick="scrollToTop()" 
        class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition z-50" 
        id="scroll-to-top">
    <i class="ph ph-arrow-up text-xl"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../components/navbar.js"></script>

<script>
function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }
window.addEventListener('scroll', function() { const btn = document.getElementById('scroll-to-top'); if (btn) { if (window.pageYOffset > 300) { btn.classList.remove('hidden'); } else { btn.classList.add('hidden'); } } });

function archiveProgram(id){
  Swal.fire({ title:'Archive Program?', text:'You can restore later by editing status.', icon:'warning', showCancelButton:true, confirmButtonText:'Archive' }).then(res=>{
    if(!res.isConfirmed) return;
    const fd = new FormData(); fd.append('action','update_program'); fd.append('programID', id); fd.append('status','archived');
    fetch('../../php/program-core.php', { method:'POST', body: fd }).then(()=>location.reload());
  });
}

function deleteProgram(id){
  Swal.fire({ title:'Delete Program?', text:'This action cannot be undone.', icon:'error', showCancelButton:true, confirmButtonText:'Delete' }).then(res=>{
    if(!res.isConfirmed) return;
    const fd = new FormData(); fd.append('action','delete_program'); fd.append('programID', id);
    fetch('../../php/program-core.php', { method:'POST', body: fd }).then(()=>location.reload());
  });
}
</script>

</body>
</html>