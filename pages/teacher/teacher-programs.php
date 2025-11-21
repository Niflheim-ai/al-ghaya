<?php
session_start();
$current_page = "teacher-programs";
$page_title = "My Programs";
$debug_mode = false;

if (!isset($_SESSION['userID']) || (($_SESSION['role'] ?? '') !== 'teacher')) { $_SESSION['error_message']='Access denied'; header('Location: ../login.php'); exit(); }

require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';
require_once '../../php/program-core.php'; // unified core (handlers + helpers)
require_once '../../php/quiz-handler.php'; // quiz functions

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
    $quiz = getChapterQuiz($conn, $chapter_id);
    $quiz_questions = $quiz ? quizQuestion_getByQuiz($conn, $quiz['quiz_id']) : [];
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
                                                <?php if (($program['status'] ?? 'draft') === 'published'): ?>
                                                <a href="?action=create&program_id=<?= $program['programID'] ?>"
                                                class="text-gray-700 hover:text-gray-900 text-sm"
                                                title="View Program">
                                                    <i class="ph ph-eye"></i> View
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=create&program_id=<?= $program['programID'] ?>"
                                                class="text-blue-500 hover:text-blue-700 text-sm"
                                                title="Edit Program">
                                                    <i class="ph ph-pencil-simple"></i> Edit
                                                </a>
                                            <?php endif; ?>
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
                
                <div class="w-full h-fit flex flex-col gap-[20px] p-[20px] rounded-[40px] items-stretch justify-start mt-8 bg-company_white">
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
                        <?php foreach ($allPrograms as $program): ?>
                            <?php
                                $pid = (int)$program['programID'];
                                $enrollees = $enrolleeCounts[$pid] ?? 0;
                                $programImage = '../../images/blog-bg.svg';
                                if (!empty($program['image'])) {
                                    if (strpos($program['image'], 'thumbnails/') !== false || strpos($program['image'], 'uploads/') !== false) {
                                        $programImage = '../../' . $program['image'];
                                    } else {
                                        $programImage = '../../uploads/thumbnails/' . $program['image'];
                                    }
                                } elseif (!empty($program['thumbnail'])) {
                                    if (strpos($program['thumbnail'], 'thumbnails/') !== false || strpos($program['thumbnail'], 'uploads/') !== false) {
                                        $programImage = '../../' . $program['thumbnail'];
                                    } else {
                                        $programImage = '../../uploads/thumbnails/' . $program['thumbnail'];
                                    }
                                }
                            ?>
                            <a href="#" onclick="openReviewModal(<?= (int)$program['programID'] ?>, <?= (int)$user_id ?>); return false;" class="block">
                                <div class="w-full bg-white border border-gray-200 rounded-[20px] shadow-sm hover:shadow-md transition-shadow mb-4 flex overflow-hidden min-h-[140px]">
                                    <!-- Thumbnail Aside: Fixed 16:9 -->
                                    <div class="w-[220px] h-[240px] bg-gray-100 flex-shrink-0 flex items-center justify-center overflow-hidden rounded-l-[20px]">
                                        <img src="<?= htmlspecialchars($programImage) ?>"
                                            alt="<?= htmlspecialchars($program['title']) ?>"
                                            class="w-full h-full object-cover"
                                            style="aspect-ratio:16/9;"
                                            onerror="this.src='../../images/blog-bg.svg'">
                                    </div>
                                    <!-- Content: Flexible, fills rest of row -->
                                    <div class="flex flex-col justify-between content-between flex-1 p-6 min-h-[240px] h-full">
                                        <div>
                                            <h3 class="text-xl font-semibold text-gray-900 arabic"><?= htmlspecialchars($program['title']) ?></h3>
                                            <div class="text-gray-700 text-sm leading-relaxed mt-1">
                                                <?= htmlspecialchars(mb_strimwidth($program['description'] ?? '', 0, 220, '...')) ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between mt-4">
                                            <div class="flex items-center gap-2 text-gray-600 text-sm">
                                                <i class="ph ph-users-three text-[18px]"></i>
                                                <span><?= $enrollees ?> enrollees</span>
                                            </div>
                                            <div class="proficiency-badge">
                                                <i class="ph-fill ph-barbell text-[15px]"></i>
                                                <span class="text-[14px] font-semibold"><?= htmlspecialchars(ucfirst(strtolower($program['category']))) ?> Difficulty</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
            
        <?php elseif ($pageContent === 'program_details'): ?>
            <?php include '../../components/program-details-form.php'; ?>
        <?php elseif ($pageContent === 'chapter_content'): ?>
            <?php include '../../components/chapter-content-form.php'; ?>
        <?php elseif ($pageContent === 'story_form'): ?>
            <?php include '../../components/story-form.php'; ?>
        <?php elseif ($pageContent === 'quiz_form'): ?>
            <?php include '../../components/quiz-form.php'; ?>
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

<!-- Program Review Modal -->
<div id="programReviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg max-w-5xl w-full max-h-[90vh] overflow-hidden shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold" id="modalProgramTitle">Program Review</h2>
        <p class="text-blue-100 text-sm mt-1" id="modalProgramTeacher"></p>
      </div>
      <button onclick="closeReviewModal()" class="text-white hover:text-gray-200 text-3xl leading-none">&times;</button>
    </div>

    <!-- Modal Content -->
    <div class="overflow-y-auto max-h-[calc(90vh-200px)] p-6" id="modalContent">
      <div class="text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="text-gray-600 mt-4">Loading program details...</p>
      </div>
    </div>

    <!-- Modal Footer with Actions -->
    <div class="bg-gray-50 px-6 py-4 flex justify-end border-t">
      <button onclick="closeReviewModal()" class="px-4 py-2 bg-red-600 rounded rounded-md text-white hover:text-gray-800 hover:bg-red-400">
        Go Back
      </button>
    </div>
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

<!-- Open View Program Modal -->
<script>
    let currentProgramId = null;

    // Open review modal and load program details
    function openReviewModal(programId) {
        currentProgramId = programId;
        document.getElementById('programReviewModal').classList.remove('hidden');
        loadProgramDetails(programId);
    }

    // Close modal
    function closeReviewModal() {
        const reviewModal = document.getElementById('programReviewModal');
        if (reviewModal) reviewModal.classList.add('hidden');

        // Show the last-viewed teacher modal again
        if (lastViewedTeacherId !== null) {
            // Use your actual modal show function for viewTeacher
            viewTeacher(lastViewedTeacherId);
        }
        currentProgramId = null;
    }

    function loadProgramDetails(programId) {
    const modalContent = document.getElementById('modalContent');
    
    console.log('Loading program ID:', programId);
    
    fetch(`../../php/admin-get-program-details.php?program_id=${programId}`)
        .then(response => response.json())
        .then(data => {
        console.log('Full response:', data); // ✅ Debug
        if (data.success) {
            console.log('Program chapters:', data.program.chapters); // ✅ Debug
            if (data.program.chapters && data.program.chapters.length > 0) {
            console.log('First chapter:', data.program.chapters[0]); // ✅ Debug
            console.log('Has quiz?', data.program.chapters[0].has_quiz); // ✅ Debug
            console.log('Question?', data.program.chapters[0].question); // ✅ Debug
            }
            renderProgramDetails(data.program);
        } else {
            modalContent.innerHTML = `<div class="text-center py-8 text-red-600">Error loading program: ${data.message}</div>`;
        }
        })
        .catch(error => {
        modalContent.innerHTML = `<div class="text-center py-8 text-red-600">Failed to load program details.</div>`;
        console.error('Error:', error);
        });
    }

    // Render program details in modal
    function renderProgramDetails(program) {
    const modalContent = document.getElementById('modalContent');
    document.getElementById('modalProgramTitle').textContent = program.title;
    document.getElementById('modalProgramTeacher').textContent = `Teacher: ${program.teacher_name} (${program.teacher_email})`;
    
    let chaptersHtml = '';
    if (program.chapters && program.chapters.length > 0) {
        program.chapters.forEach((chapter, idx) => {
        // Stories section
        let storiesHtml = '';
        if (chapter.stories && chapter.stories.length > 0) {
            chapter.stories.forEach((story, sIdx) => {
            // Interactive sections for this story
            let interactiveSectionsHtml = '';
            if (story.interactive_sections && story.interactive_sections.length > 0) {
                interactiveSectionsHtml = `
                <div class="mt-4 space-y-3">
                    <p class="text-xs font-semibold text-purple-800 mb-2">
                    <i class="ph ph-magic-wand text-purple-600 mr-1"></i>
                    Interactive Sections (${story.interactive_sections.length})
                    </p>
                    ${story.interactive_sections.map((section, secIdx) => {
                    // Render questions for this section
                    let questionsHtml = '';
                    if (section.questions && section.questions.length > 0) {
                        questionsHtml = section.questions.map((question, qIdx) => {
                        // Render options
                        let optionsHtml = question.options.map(opt => {
                            const isCorrect = opt.is_correct == 1;
                            return `
                            <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                                ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-1"></i>' : ''}
                                ${escapeHtml(opt.option_text)}
                                ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct)</span>' : ''}
                            </div>
                            `;
                        }).join('');
                        
                        return `
                            <div class="mb-3">
                            <p class="font-medium text-gray-800 mb-2">${qIdx + 1}. ${escapeHtml(question.question_text)}</p>
                            <p class="text-xs text-gray-500 mb-2">Type: ${question.question_type}</p>
                            <div class="space-y-1 ml-3">
                                ${optionsHtml}
                            </div>
                            </div>
                        `;
                        }).join('');
                    }
                    
                    return `
                        <div class="p-3 bg-purple-50 border-l-4 border-purple-400 rounded">
                        <p class="text-xs font-semibold text-purple-900 mb-3">Section ${secIdx + 1}</p>
                        ${questionsHtml || '<p class="text-xs text-gray-500 italic">No questions in this section</p>'}
                        </div>
                    `;
                    }).join('')}
                </div>
                `;
            }
            
            storiesHtml += `
                <div class="ml-6 mb-4 p-4 bg-white border border-gray-300 rounded-lg shadow-sm">
                <h5 class="font-bold text-blue-900 mb-2 flex items-center gap-2">
                    <i class="ph ph-book-open text-blue-600"></i>
                    Story ${sIdx + 1}: ${escapeHtml(story.title)}
                </h5>
                
                <!-- Arabic Synopsis -->
                <div class="mb-3 p-3 bg-amber-50 border-l-4 border-amber-400 rounded">
                    <p class="text-xs font-semibold text-amber-800 mb-1">Arabic Synopsis:</p>
                    <p class="text-sm text-gray-800 arabic leading-relaxed">${escapeHtml(story.synopsis_arabic)}</p>
                </div>
                
                <!-- English Synopsis -->
                <div class="mb-3 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
                    <p class="text-xs font-semibold text-blue-800 mb-1">English Synopsis:</p>
                    <p class="text-sm text-gray-800 leading-relaxed">${escapeHtml(story.synopsis_english)}</p>
                </div>
                
                <!-- Video Player -->
                ${story.video_url_embed ? `
                    <div class="mt-3">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Video Content:</p>
                    <div class="relative" style="padding-bottom: 56.25%; height: 0;">
                        <iframe 
                        src="${escapeHtml(story.video_url_embed)}" 
                        class="absolute top-0 left-0 w-full h-full rounded-lg"
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                        allowfullscreen>
                        </iframe>
                    </div>
                    </div>
                ` : (story.video_url ? `<p class="text-xs text-gray-500 italic">Video URL: ${escapeHtml(story.video_url)}</p>` : '<p class="text-xs text-gray-500 italic">No video for this story</p>')}
                
                <!-- Interactive Sections -->
                ${interactiveSectionsHtml}
                </div>
            `;
            });
        } else {
            storiesHtml = '<p class="ml-6 text-sm text-gray-500 italic">No stories in this chapter</p>';
        }
        
        // Chapter Interactive Question (if exists)
        let chapterQuestionHtml = '';
        if (chapter.question && chapter.question_type) {
            let optionsHtml = '';
            if (chapter.question_type === 'multiple_choice' && chapter.answer_options_parsed) {
            optionsHtml = chapter.answer_options_parsed.map(option => {
                const isCorrect = option === chapter.correct_answer;
                return `
                <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                    ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                    ${escapeHtml(option)}
                    ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
                </div>
                `;
            }).join('');
            } else if (chapter.question_type === 'true_false') {
            optionsHtml = `
                <div class="p-2 rounded border ${chapter.correct_answer === 'True' ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                ${chapter.correct_answer === 'True' ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                True
                ${chapter.correct_answer === 'True' ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
                </div>
                <div class="p-2 rounded border ${chapter.correct_answer === 'False' ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                ${chapter.correct_answer === 'False' ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                False
                ${chapter.correct_answer === 'False' ? '<span class="text-green-600 text-xs ml-2">(Correct Answer)</span>' : ''}
                </div>
            `;
            } else {
            optionsHtml = `<div class="p-2 bg-green-100 border border-green-500 rounded"><strong>Correct Answer:</strong> ${escapeHtml(chapter.correct_answer)}</div>`;
            }
            
            chapterQuestionHtml = `
            <div class="ml-6 mt-4 p-4 bg-purple-50 border-2 border-purple-400 rounded-lg">
                <h6 class="font-semibold text-purple-900 mb-2 flex items-center gap-2">
                <i class="ph ph-question text-purple-600"></i>
                Chapter Interactive Question (${chapter.points_reward} points)
                </h6>
                <p class="text-sm font-medium text-gray-800 mb-3">${escapeHtml(chapter.question)}</p>
                <div class="space-y-2">
                ${optionsHtml}
                </div>
            </div>
            `;
        }
        
        // Chapter Media
        let chapterMediaHtml = '';
        if (chapter.video_url_embed || chapter.audio_url) {
            chapterMediaHtml = '<div class="ml-6 mt-3 space-y-3">';
            if (chapter.video_url_embed) {
            chapterMediaHtml += `
                <div>
                <p class="text-xs font-semibold text-gray-700 mb-2">Chapter Video:</p>
                <div class="relative" style="padding-bottom: 56.25%; height: 0;">
                    <iframe 
                    src="${escapeHtml(chapter.video_url_embed)}" 
                    class="absolute top-0 left-0 w-full h-full rounded-lg"
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                    </iframe>
                </div>
                </div>
            `;
            }
            if (chapter.audio_url) {
            chapterMediaHtml += `
                <div>
                <p class="text-xs font-semibold text-gray-700 mb-2">Chapter Audio:</p>
                <audio controls class="w-full">
                    <source src="${escapeHtml(chapter.audio_url)}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                </div>
            `;
            }
            chapterMediaHtml += '</div>';
        }
        
        // Chapter Quiz
        let quizHtml = '';
        if (chapter.has_quiz && chapter.quiz_questions && chapter.quiz_questions.length > 0) {
            let questionsHtml = chapter.quiz_questions.map((q, qIdx) => {
            let optionsListHtml = q.options.map(opt => {
                const isCorrect = opt.is_correct == 1;
                return `
                <div class="p-2 rounded border ${isCorrect ? 'bg-green-100 border-green-500 font-semibold' : 'bg-gray-50 border-gray-300'}">
                    ${isCorrect ? '<i class="ph ph-check-circle text-green-600 mr-2"></i>' : ''}
                    ${escapeHtml(opt.option_text)}
                    ${isCorrect ? '<span class="text-green-600 text-xs ml-2">(Correct)</span>' : ''}
                </div>
                `;
            }).join('');
            
            return `
                <div class="mb-3 p-3 bg-white border border-gray-300 rounded">
                <p class="font-medium text-gray-800 mb-2">${qIdx + 1}. ${escapeHtml(q.question_text)}</p>
                <div class="space-y-1 ml-4">
                    ${optionsListHtml}
                </div>
                </div>
            `;
            }).join('');
            
            quizHtml = `
            <div class="ml-6 mt-4 p-4 bg-green-50 border-2 border-green-400 rounded-lg">
                <h6 class="font-semibold text-green-900 mb-3 flex items-center gap-2">
                <i class="ph ph-exam text-green-600"></i>
                Chapter Quiz (${chapter.quiz_questions.length} questions)
                </h6>
                ${questionsHtml}
            </div>
            `;
        }
        
        chaptersHtml += `
            <div class="mb-6 border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xl font-bold text-gray-900">
                <i class="ph ph-book-bookmark text-blue-600 mr-2"></i>
                Chapter ${chapter.chapter_order}: ${escapeHtml(chapter.title)}
                </h4>
                <div class="flex gap-2 text-xs">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded font-semibold">${chapter.story_count} stories</span>
                ${chapter.has_quiz ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded font-semibold">Has Quiz</span>` : ''}
                </div>
            </div>
            ${chapter.content ? `<div class="mb-4 p-3 bg-white border-l-4 border-blue-500 rounded"><p class="text-sm text-gray-700">${escapeHtml(chapter.content)}</p></div>` : ''}
            ${chapterMediaHtml}
            ${storiesHtml}
            ${chapterQuestionHtml}
            ${quizHtml}
            </div>
        `;
        });
    } else {
        chaptersHtml = '<p class="text-gray-500 italic">No chapters found in this program</p>';
    }
    
    modalContent.innerHTML = `
        <div class="space-y-6">
        <!-- Program Overview -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-300 rounded-lg p-5 shadow-sm">
            <h3 class="text-xl font-bold text-blue-900 mb-4">📋 Program Overview</h3>
            
            <div class="grid grid-cols-2 gap-4 text-sm mb-4">
            <div class="bg-white p-2 rounded"><strong>Difficulty:</strong> <span class="capitalize">${escapeHtml(program.category)}</span></div>
            <div class="bg-white p-2 rounded"><strong>Price:</strong> ₱${parseFloat(program.price).toFixed(2)}</div>
            </div>
            <div class="bg-white p-3 rounded">
            <strong class="text-gray-900">Description:</strong>
            <p class="text-gray-700 mt-1">${escapeHtml(program.description)}</p>
            </div>
            ${program.prerequisites ? `
            <div class="bg-white p-3 rounded mt-3">
                <strong class="text-gray-900">Prerequisites:</strong>
                <p class="text-gray-700 mt-1">${escapeHtml(program.prerequisites)}</p>
            </div>
            ` : ''} 
            ${program.learning_objectives ? `
            <div class="bg-white p-3 rounded mt-3">
                <strong class="text-gray-900">Learning Objectives:</strong>
                <p class="text-gray-700 mt-1">${escapeHtml(program.learning_objectives)}</p>
            </div>
            ` : ''}
        </div>

        <!-- Overview Video -->
            ${program.overview_video_url_embed ? `
            <div class="mb-4">
                <p class="text-sm font-semibold text-blue-900 mb-2">
                <i class="ph ph-play-circle text-blue-600 mr-1"></i>
                Program Introduction Video
                </p>
                <div class="relative bg-white rounded-lg overflow-hidden" style="padding-bottom: 56.25%; height: 0;">
                <iframe 
                    src="${escapeHtml(program.overview_video_url_embed)}" 
                    class="absolute top-0 left-0 w-full h-full"
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                </iframe>
                </div>
            </div>
            ` : ''}

        <!-- Chapters and Content -->
        <div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">📚 Program Content (${program.chapters ? program.chapters.length : 0} Chapters)</h3>
            ${chaptersHtml}
        </div>
        </div>
    `;
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReviewModal();
    }
    });
</script>
<?php include '../../components/footer.php'; ?>

</body>
</html>