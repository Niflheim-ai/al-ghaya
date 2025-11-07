<?php
session_start();
require '../../php/dbConnection.php';
require '../../php/functions.php';
require '../../php/functions-user-progress.php';
require '../../php/program-core.php';
require '../../php/quiz-handler.php';
require_once '../../php/youtube-embed-helper.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$studentID = (int)$_SESSION['userID'];
$programID = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$storyID = isset($_GET['story_id']) ? (int)$_GET['story_id'] : 0;
$viewExam = isset($_GET['take_exam']) ? true : false;
$quizID = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($programID <= 0) { header('Location: student-programs.php'); exit(); }

$program = getProgramDetails($conn, $programID, $studentID);
if (!$program) { header('Location: student-programs.php?tab=all'); exit(); }
$isEnrolled = !empty($program['is_enrolled']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
    if (!$isEnrolled && enrollStudentInProgram($conn, $studentID, $programID)) {
        header('Location: student-program-view.php?program_id='.$programID);
        exit();
    }
}

if (!$isEnrolled) {
    $current_page = 'student-programs';
    $page_title = 'Program Details';
    include '../../components/header.php';
    include '../../components/student-nav.php';
    include 'student-program-view-original.php';
    include '../../components/footer.php';
    exit();
}

$chapters = getChapters($conn, $programID);
$completion = (float)($program['completion_percentage'] ?? 0);
$userStoryProgress = getUserStoryProgress($conn, $studentID, $programID);

// Check exam pass/certificate
$certSql = $conn->prepare("SELECT * FROM student_program_certificates WHERE program_id=? AND student_id=?");
$certSql->bind_param('ii', $programID, $studentID);
$certSql->execute();
$certificate = $certSql->get_result()->fetch_assoc();
$certSql->close();
$certificateEarned = !empty($certificate);

// Check if all stories are completed
$allStories = [];
foreach ($chapters as $chapter) {
    $stories = chapter_getStories($conn, $chapter['chapter_id']);
    foreach ($stories as $story) {
        $allStories[] = $story['story_id'];
    }
}
$isAllComplete = true;
foreach ($allStories as $storyIDchk) {
    if (empty($userStoryProgress[$storyIDchk])) { $isAllComplete = false; break; }
}

$showExam = $isAllComplete && !$certificateEarned;
$showCertificate = $certificateEarned;

$compiledExamQuestions = [];
if ($viewExam && $showExam) {
    $quizIds = [];
    foreach($chapters as $ch) {
        $quiz = getChapterQuiz($conn, $ch['chapter_id']);
        if($quiz) $quizIds[] = $quiz['quiz_id'];
    }
    $questions = [];
    foreach($quizIds as $qid) {
        $result = quizQuestion_getByQuiz($conn, $qid);
        foreach ($result as $q) $questions[] = $q;
    }
    shuffle($questions);
    $compiledExamQuestions = array_slice($questions, 0, 50);
}

$navigation = [];
foreach ($chapters as $chapter) {
    $chapterData = [
        'chapter_id' => $chapter['chapter_id'],
        'title' => $chapter['title'],
        'type' => 'chapter',
        'stories' => [],
        'quiz' => null
    ];
    $stories = chapter_getStories($conn, $chapter['chapter_id']);
    foreach ($stories as $story) {
        $is_completed = !empty($userStoryProgress[$story['story_id']]);
        $chapterData['stories'][] = [
            'story_id' => $story['story_id'],
            'title' => $story['title'],
            'synopsis_arabic' => $story['synopsis_arabic'],
            'synopsis_english' => $story['synopsis_english'],
            'video_url' => $story['video_url'],
            'type' => 'story',
            'is_completed' => $is_completed
        ];
    }
    $quiz = getChapterQuiz($conn, $chapter['chapter_id']);
    if ($quiz) {
        $is_quiz_unlocked = true;
        // Locked if any story in this chapter incomplete
        foreach ($chapterData['stories'] as $st) {
            if (!$st['is_completed']) { $is_quiz_unlocked = false; break; }
        }
        $chapterData['quiz'] = [
            'quiz_id' => $quiz['quiz_id'],
            'title' => $quiz['title'],
            'type' => 'quiz',
            'is_unlocked' => $is_quiz_unlocked
        ];
    }
    $navigation[] = $chapterData;
}
// Determine navigation lock state
function is_item_locked($chapter_idx, $story_idx, $navigation) {
    $found_unfinished = false;
    for ($c = 0; $c < count($navigation); $c++) {
        for ($s = 0; $s < count($navigation[$c]['stories']); $s++) {
            if (!$navigation[$c]['stories'][$s]['is_completed']) {
                $found_unfinished = true;
            }
            if ($c === $chapter_idx && $s === $story_idx) {
                return $found_unfinished;
            }
        }
        if ($navigation[$c]['quiz']) {
            if (!$navigation[$c]['quiz']['is_unlocked']) $found_unfinished = true;
        }
    }
    return $found_unfinished;
}
function is_quiz_active($quizID, $quizIDParam, $activeType) {
    return $quizID && $quizIDParam == $quizID && $activeType === 'chapter_quiz';
}
// Determine current content to display
$currentContent = null;
$currentType = 'story';
$is_completed = false;

if ($viewExam && $showExam) {
    $currentType = 'final_exam';
    $is_completed = false;
}
elseif ($quizID > 0) {
    // Show selected quiz (list of questions & choices)
    $quiz = null;
    foreach ($navigation as $navChapter) {
        if ($navChapter['quiz'] && $navChapter['quiz']['quiz_id'] === $quizID) {
            $quiz = $navChapter['quiz'];
            break;
        }
    }
    if ($quiz && $quiz['is_unlocked']) {
        $quizQuestions = quizQuestion_getByQuiz($conn, $quizID);
        $currentType = 'chapter_quiz';
    } else {
        // If locked or invalid, redirect to first incomplete story
        header('Location: student-program-view.php?program_id='.$programID);
        exit();
    }
}
elseif ($storyID > 0) {
    // Find which chapter/story this is, block access if reaching locked
    $found = false;
    foreach ($navigation as $cIdx => $nChapter) {
        foreach ($nChapter['stories'] as $sIdx => $nStory) {
            if ($nStory['story_id'] == $storyID) {
                if (is_item_locked($cIdx, $sIdx, $navigation)) {
                    header('Location: student-program-view.php?program_id='.$programID);
                    exit();
                }
                $found = true;
                break 2;
            }
        }
    }
    if ($found) {
        $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE story_id = ?");
        $stmt->bind_param("i", $storyID);
        $stmt->execute();
        $currentContent = $stmt->get_result()->fetch_assoc();
        $currentType = 'story';
        if ($currentContent) {
            $quiz = getChapterQuiz($conn, $currentContent['chapter_id']);
            if ($quiz) {
                $questions = quizQuestion_getByQuiz($conn, $quiz['quiz_id']);
                if (!empty($questions)) {
                    $currentContent['quiz_question'] = $questions[array_rand($questions)];
                }
            }
            $is_completed = !empty($userStoryProgress[$currentContent['story_id']]);
        }
    }
} else {
    $firstStory = null;
    foreach ($navigation as $chapter) {
        foreach ($chapter['stories'] as $story) {
            if (!$story['is_completed']) {
                $firstStory = $story;
                break 2;
            }
        }
    }
    if ($firstStory) {
        $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE story_id = ?");
        $stmt->bind_param("i", $firstStory['story_id']);
        $stmt->execute();
        $currentContent = $stmt->get_result()->fetch_assoc();
        $currentType = 'story';
        if ($currentContent) {
            $quiz = getChapterQuiz($conn, $currentContent['chapter_id']);
            if ($quiz) {
                $questions = quizQuestion_getByQuiz($conn, $quiz['quiz_id']);
                if (!empty($questions)) {
                    $currentContent['quiz_question'] = $questions[array_rand($questions)];
                }
            }
            $is_completed = !empty($userStoryProgress[$currentContent['story_id']]);
        }
    }
}
$current_page = 'student-programs';
$page_title = htmlspecialchars($program['title']);
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
<style>
.sidebar-item { transition: all 0.2s ease; }
.sidebar-item:hover { background-color: #f3f4f6; }
.sidebar-item.active { background-color: #e0e7ff; border-left: 4px solid #4f46e5; font-weight:bold; }
.sidebar-item.completed { opacity: 0.55; color: #94a3b8; position: relative; }
.sidebar-item.completed:after { content: '\2713'; color: #10b981; position: absolute; left: 10px; font-size: 1.1em; }
.sidebar-item.locked { pointer-events: none; opacity: 0.2; }
.lg\:sticky { position: sticky; top: 68px; z-index: 10; }
.lg\:col-span-3 { min-width: 260px; max-width: 320px; }
</style>
<div class="page-container">
  <div class="page-content">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <aside class="lg:col-span-3 lg:order-first lg:sticky lg:top-16">
        <div class="space-y-4">
          <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700">Progress</span>
              <span id="progressPercent" class="text-sm font-bold text-blue-600"><?= number_format($completion, 1) ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
              <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full transition-all" style="width: <?= max(0,min(100,$completion)) ?>%"></div>
            </div>
            <?php if ($_SESSION['role'] === 'student'): ?>
            <div class="bg-yellow-50 rounded-lg p-3 my-4 text-center border border-yellow-300">
              <strong>Development Only:</strong>
              <form id="devResetForm" class="inline-flex gap-2 mt-2">
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Reset Progress</button>
                <button type="button" onclick="unenrollDev()" class="px-4 py-2 bg-red-600 text-white rounded">Unenroll</button>
              </form>
            </div>
            <script>
              function getProgramId() { return <?= $programID ?>; }
              document.getElementById('devResetForm').onsubmit = function(e) {
                e.preventDefault();
                fetch('../../php/progress-reset-handler.php', {
                  method:'POST',
                  headers:{'Content-Type':'application/json'},
                  body:JSON.stringify({program_id:getProgramId()})
                }).then(r=>r.json()).then(data => {
                  alert(data.message||'Progress reset!');
                  if(data.success) location.reload();
                });
              }
              function unenrollDev() {
                fetch('../../php/progress-reset-handler.php', {
                  method:'POST',
                  headers:{'Content-Type':'application/json'},
                  body:JSON.stringify({action:'unenroll',program_id:getProgramId()})
                }).then(r=>r.json()).then(data => {
                  alert(data.message||'Unenrolled!');
                  if(data.success) location.href = 'student-programs.php';
                });
              }
            </script>
          <?php endif; ?>
          </div>
          <div class="bg-white border border-gray-200 rounded-lg shadow-sm max-h-[calc(100vh-200px)] overflow-y-auto">
            <div class="p-4 border-b border-gray-200 sticky top-0 bg-white">
              <h2 class="text-lg font-bold flex items-center gap-2">
                <i class="ph ph-list text-blue-600"></i>
                Course Content
              </h2>
            </div>
            <div class="p-2">
              <?php foreach ($navigation as $cIdx => $navItem): ?>
                <div class="mb-2">
                  <button type="button" class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-50 rounded-lg">
                    <span class="flex items-center gap-2 font-semibold text-gray-800">
                      <i class="ph ph-folder text-blue-600"></i>
                      <?= htmlspecialchars($navItem['title']) ?>
                    </span>
                  </button>
                  <div id="chapter-panel-<?= $navItem['chapter_id'] ?>" class="ml-4 mt-1 space-y-1">
                    <?php foreach ($navItem['stories'] as $sIdx => $story): 
                        $isLocked = is_item_locked($cIdx, $sIdx, $navigation);
                    ?>
                      <a href="<?= !$isLocked ? '?program_id='.$programID.'&story_id='.$story['story_id'] : '#' ?>" 
                         class="sidebar-item flex items-center gap-2 p-2 pl-6 text-sm rounded-lg <?= ($currentContent && $currentContent['story_id'] == $story['story_id'] && $currentType==='story') ? 'active font-semibold' : '' ?><?= $story['is_completed'] ? ' completed' : '' ?><?= $isLocked ? ' locked' : '' ?>">
                        <i class="ph ph-play-circle text-green-600"></i>
                        <?= htmlspecialchars($story['title']) ?>
                      </a>
                    <?php endforeach; ?>
                    <?php if ($navItem['quiz']): 
                          $isQuizActive = is_quiz_active($navItem['quiz']['quiz_id'], $quizID, $currentType);
                          $isQuizLocked = !$navItem['quiz']['is_unlocked'];
                    ?>
                      <a href="<?= !$isQuizLocked ? '?program_id='.$programID.'&quiz_id='.$navItem['quiz']['quiz_id'] : '#' ?>" 
                         class="sidebar-item flex items-center gap-2 p-2 pl-6 text-sm rounded-lg text-orange-700 hover:bg-orange-50 font-semibold<?= $isQuizActive ? ' active' : '' ?><?= $isQuizLocked ? ' locked' : '' ?>">
                        <i class="ph ph-exam text-orange-600"></i>
                        <?= htmlspecialchars($navItem['quiz']['title']) ?>
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php if ($showExam): ?>
            <a href="?program_id=<?= $programID ?>&take_exam=1" class="sidebar-item flex items-center gap-2 p-3 mt-6 mb-2 rounded-lg bg-yellow-50 font-bold text-orange-800 border border-orange-300<?= $currentType==='final_exam'?' active':'' ?>">
              <i class="ph ph-exam text-orange-600"></i>
              Program Exam
            </a>
          <?php endif; ?>
          <?php if ($showCertificate): ?>
            <div class="sidebar-item flex items-center gap-2 p-3 mb-2 rounded-lg bg-green-50 font-bold text-green-800 border border-green-300<?= !$showExam && $currentType==='final_exam'?' active':'' ?>">
              <i class="ph ph-certificate text-green-600"></i>
              <a href="<?= htmlspecialchars($certificate['certificate_url'] ?? '#') ?>" target="_blank">Certificate</a>
            </div>
          <?php endif; ?>
        </div>
      </aside>
      <!-- Main content panel logic is unchanged, but next/continue buttons should now check for next quiz/stories and exam/cert eligibility -->
      <!-- Add handling for 'Continue to Quiz' and lock story advancing until quiz is done -->
<?php /* -- Main content/app logic continues as in your last working main story/quiz/exam panel -- */ ?>
