<?php
session_start();
require '../../php/dbConnection.php';
require '../../php/functions.php';
require '../../php/functions-user-progress.php';
require_once '../../php/program-core.php';
require_once '../../php/quiz-handler.php';
require_once '../../php/youtube-embed-helper.php';
require_once '../../php/student-progress.php';

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
        $chapterData['quiz'] = [
            'quiz_id' => $quiz['quiz_id'],
            'title' => $quiz['title'],
            'type' => 'quiz'
        ];
    }
    $navigation[] = $chapterData;
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
    $quiz = null;
    foreach ($navigation as $navChapter) {
        if ($navChapter['quiz'] && $navChapter['quiz']['quiz_id'] === $quizID) {
            $quiz = $navChapter['quiz'];
            break;
        }
    }
    if ($quiz) {
        $quizQuestions = quizQuestion_getByQuiz($conn, $quizID);
        $currentType = 'chapter_quiz';
    }
}
elseif ($storyID > 0) {
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE story_id = ?");
    $stmt->bind_param("i", $storyID);
    $stmt->execute();
    $currentContent = $stmt->get_result()->fetch_assoc();
    $currentType = 'story';
    if ($currentContent) {
        // PATCH: Load interactive sections for this story
        $interactiveSections = interactiveSection_getByStory($conn, $currentContent['story_id']);
        if (!empty($interactiveSections)) {
            $currentSection = null;
            foreach ($interactiveSections as $section) {
                $sectionQuestions = interactiveQuestion_getBySection($conn, $section['section_id']);
                if (!empty($sectionQuestions)) {
                    $currentSection = $section;
                    $currentSection['questions'] = $sectionQuestions;
                    break;
                }
            }
            if ($currentSection && !empty($currentSection['questions'])) {
                $currentContent['interactive_section'] = $currentSection;
                $currentContent['quiz_question'] = $currentSection['questions'][0];
                $currentContent['quiz_question']['options'] = questionOption_getByQuestion($conn, $currentContent['quiz_question']['question_id']);
            }
        }
        $is_completed = !empty($userStoryProgress[$currentContent['story_id']]);
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
            // PATCH: Load interactive sections for first story too
            $interactiveSections = interactiveSection_getByStory($conn, $currentContent['story_id']);
            if (!empty($interactiveSections)) {
                $currentSection = null;
                foreach ($interactiveSections as $section) {
                    $sectionQuestions = interactiveQuestion_getBySection($conn, $section['section_id']);
                    if (!empty($sectionQuestions)) {
                        $currentSection = $section;
                        $currentSection['questions'] = $sectionQuestions;
                        break;
                    }
                }
                if ($currentSection && !empty($currentSection['questions'])) {
                    $currentContent['interactive_section'] = $currentSection;
                    $currentContent['quiz_question'] = $currentSection['questions'][0];
                    $currentContent['quiz_question']['options'] = questionOption_getByQuestion($conn, $currentContent['quiz_question']['question_id']);
                }
            }
            $is_completed = !empty($userStoryProgress[$currentContent['story_id']]);
        }
    }
}

// Check if current story is last in chapter and chapter has quiz
$currentChapterId = $currentContent['chapter_id'] ?? 0;
$currentChapterQuiz = null;
$isLastStoryInChapter = false;

if ($currentContent && $currentType === 'story') {
    foreach ($navigation as $chapter) {
        if ($chapter['chapter_id'] === $currentChapterId) {
            $lastStory = end($chapter['stories']);
            if ($lastStory && $lastStory['story_id'] === $currentContent['story_id']) {
                $isLastStoryInChapter = true;
                $currentChapterQuiz = $chapter['quiz'] ?? null;
            }
            break;
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
.sidebar-item.active { background-color: #e0e7ff; border-left: 4px solid #4f46e5; }
.sidebar-item.completed { opacity: 0.55; color: #94a3b8; position: relative; }
.sidebar-item.completed:after { content: '\2713'; color: #10b981; position: absolute; left: 10px; font-size: 1.1em; }
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
              <?php if (empty($navigation)): ?>
                <p class="text-gray-500 text-sm p-4">No content available.</p>
              <?php else: ?>
                <?php foreach ($navigation as $navItem): ?>
                  <div class="mb-2">
                    <button type="button" class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-50 rounded-lg" onclick="toggleChapter(<?= $navItem['chapter_id'] ?>)">
                      <span class="flex items-center gap-2 font-semibold text-gray-800">
                        <i class="ph ph-folder text-blue-600"></i>
                        <?= htmlspecialchars($navItem['title']) ?>
                      </span>
                      <i id="chev-<?= $navItem['chapter_id'] ?>" class="ph ph-caret-down text-gray-400 transition-transform"></i>
                    </button>
                    <div id="chapter-panel-<?= $navItem['chapter_id'] ?>" class="ml-4 mt-1 space-y-1">
                      <?php if (!empty($navItem['stories'])): ?>
                        <?php foreach ($navItem['stories'] as $story): ?>
                          <a href="?program_id=<?= $programID ?>&story_id=<?= $story['story_id'] ?>" class="sidebar-item flex items-center gap-2 p-2 pl-6 text-sm rounded-lg <?php if (($currentContent && $currentContent['story_id'] == $story['story_id'])) echo 'active font-semibold '; if ($story['is_completed']) echo 'completed'; ?>">
                            <i class="ph ph-play-circle text-green-600"></i>
                            <?= htmlspecialchars($story['title']) ?>
                          </a>
                        <?php endforeach; ?>
                      <?php endif; ?>
                      <?php if ($navItem['quiz']): ?>
                        <a href="?program_id=<?= $programID ?>&quiz_id=<?= $navItem['quiz']['quiz_id'] ?>" class="sidebar-item flex items-center gap-2 p-2 pl-6 text-sm rounded-lg text-orange-700 hover:bg-orange-50 font-semibold">
                          <i class="ph ph-exam text-orange-600"></i>
                          <?= htmlspecialchars($navItem['quiz']['title']) ?>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          <!-- Final Exam Section -->
          <?php if ($showExam): ?>
            <div class="p-4 bg-gradient-to-r from-orange-50 to-yellow-50 border-2 border-orange-300 rounded-lg mt-4">
              <div class="flex items-center gap-2 mb-2">
                <i class="ph ph-exam text-2xl text-orange-600"></i>
                <h3 class="font-bold text-orange-900">Ready for Exam?</h3>
              </div>
              <p class="text-sm text-gray-700 mb-3">You've completed all stories! Take the final exam to earn your certificate.</p>
              <a href="?program_id=<?= $programID ?>&take_exam=1" class="block w-full text-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition-colors">
                Take Final Exam
              </a>
            </div>
          <?php endif; ?>
          <!-- Certificate Section -->
          <?php if ($showCertificate): ?>
            <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg mt-4">
              <div class="flex items-center gap-2 mb-2">
                <i class="ph ph-certificate text-2xl text-green-600"></i>
                <h3 class="font-bold text-green-900">Certificate Earned!</h3>
              </div>
              <p class="text-sm text-gray-700 mb-3">Congratulations on completing this program!</p>
              <a href="<?= htmlspecialchars($certificate['certificate_url'] ?? '#') ?>" target="_blank" class="block w-full text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors">
                Download Certificate
              </a>
            </div>
          <?php endif; ?>
        </div>
      </aside>
      <section class="lg:col-span-9 lg:order-last space-y-6">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="w-full">
            <?php $heroImg = !empty($program['thumbnail']) && $program['thumbnail'] !== 'default-thumbnail.jpg' ? '../../uploads/thumbnails/'.htmlspecialchars($program['thumbnail']) : '../../images/default-program.jpg'; ?>
            <img src="<?= $heroImg ?>" alt="Program Image" class="w-full h-48 md:h-64 object-cover">
          </div>
          <div class="p-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
              <?= htmlspecialchars($program['title']) ?>
            </h1>
            <div class="inline-flex items-center gap-2 mb-4">
              <i class="ph-fill ph-barbell text-blue-600"></i>
              <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars(ucfirst($program['category'])) ?> Level</span>
            </div>
          </div>
        </div>
        <?php // MAIN CONTENT LOGIC (Story, Quiz, Exam) ?>
        <?php if ($currentType === 'final_exam'): ?>
        <div class="bg-white rounded-xl shadow-md p-6 mt-8">
          <h2 class="text-2xl font-bold text-orange-800 mb-4 flex items-center gap-2"><i class="ph ph-exam text-orange-500"></i> Program Final Exam</h2>
          <form id="finalExamForm">
            <?php foreach ($compiledExamQuestions as $i => $question): ?>
            <div class="mb-6 border-b pb-5">
              <div class="font-semibold mb-2">Q<?= $i+1 ?>: <?= htmlspecialchars($question['question_text']) ?></div>
              <?php foreach ($question['options'] as $opt): ?>
              <div class="mb-2">
                <label class="flex gap-2 items-center">
                  <input type="radio" name="exam_answer_<?= $i ?>" value="<?= $opt['quiz_option_id'] ?>" required> <?= htmlspecialchars($opt['option_text']) ?>
                </label>
              </div>
              <?php endforeach; ?>
              <input type="hidden" name="exam_question_id_<?= $i ?>" value="<?= $question['quiz_question_id'] ?>">
            </div>
            <?php endforeach; ?>
            <input type="hidden" name="total_questions" value="<?= count($compiledExamQuestions) ?>">
            <div class="mt-8 text-center">
              <button type="submit" class="px-8 py-3 bg-orange-700 text-white rounded-lg font-semibold shadow hover:bg-orange-900">Submit Exam</button>
            </div>
          </form>
          <div id="examResult" class="hidden mt-8"></div>
        </div>
        <script>
document.getElementById('finalExamForm').onsubmit = function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  const answers = {};
  for (var [k, v] of formData.entries()) {
    if (k.startsWith('exam_answer_')) {
      const idx = k.replace('exam_answer_', '');
      answers[idx] = v;
    }
  }
  const questionIDs = [];
  for (let i = 0; i < formData.get('total_questions'); i++) {
    questionIDs.push(formData.get('exam_question_id_' + i));
  }
  fetch('../../php/exam-answer-handler.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      action: 'submit_final_exam',
      answers,
      questionIDs,
      program_id: <?= $programID ?>
    })
  }).then(r=>r.json()).then(data => {
    const resultDiv = document.getElementById('examResult');
    resultDiv.classList.remove('hidden');
    if(data.passed) {
      resultDiv.innerHTML = `<div class='bg-green-100 text-green-900 border-green-400 border-2 rounded-lg p-6 text-xl font-bold'>Congratulations! You have earned a certificate for this program! <a href='<?= htmlspecialchars($certificate['certificate_url'] ?? '#') ?>' class='underline text-green-700' target='_blank'>View Certificate</a></div>`;
      setTimeout(()=>window.location = '?program_id=<?= $programID ?>', 2200);
    } else {
      resultDiv.innerHTML = `<div class='bg-red-100 text-red-900 border-red-400 border-2 rounded-lg p-6 text-xl font-bold'>You did not pass the exam. All progress has been reset; you must restart the program.</div>`;
      setTimeout(()=>window.location = '?program_id=<?= $programID ?>', 2200);
    }
  });
};
</script>
        <?php elseif ($currentType === 'chapter_quiz' && isset($quizQuestions)): ?>
        <div class="bg-white rounded-xl shadow-md p-6 mt-8">
          <h2 class="text-2xl font-bold text-orange-800 mb-4 flex items-center gap-2"><i class="ph ph-exam text-orange-500"></i> Chapter Quiz</h2>
          <form id="chapterQuizForm">
            <?php foreach ($quizQuestions as $i => $question): ?>
            <div class="mb-6 border-b pb-5">
              <div class="font-semibold mb-2">Q<?= $i+1 ?>: <?= htmlspecialchars($question['question_text']) ?></div>
              <?php foreach ($question['options'] as $opt): ?>
              <div class="mb-2">
                <label class="flex gap-2 items-center">
                  <input type="radio" name="quiz_answer_<?= $i ?>" value="<?= $opt['quiz_option_id'] ?>" required> <?= htmlspecialchars($opt['option_text']) ?>
                </label>
              </div>
              <?php endforeach; ?>
              <input type="hidden" name="quiz_question_id_<?= $i ?>" value="<?= $question['quiz_question_id'] ?>">
            </div>
            <?php endforeach; ?>
            <input type="hidden" name="total_quiz_questions" value="<?= count($quizQuestions) ?>">
            <div class="mt-8 text-center">
              <button type="submit" class="px-8 py-3 bg-orange-700 text-white rounded-lg font-semibold shadow hover:bg-orange-900">Submit Quiz</button>
            </div>
          </form>
          <div id="quizResult" class="hidden mt-8"></div>
        </div>
        <script>
const chapterQuizForm = document.getElementById('chapterQuizForm');
if (chapterQuizForm) {
  chapterQuizForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(chapterQuizForm);
    const total = parseInt(formData.get('total_quiz_questions'));
    const answers = [];
    const questionIDs = [];
    for (let i = 0; i < total; i++) {
      answers.push(formData.get('quiz_answer_' + i));
      questionIDs.push(formData.get('quiz_question_id_' + i));
    }
    fetch('../../php/quiz-answer-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'chapter_quiz_submit',
        answers,
        questionIDs,
        quiz_id: <?= (int)$quizID ?>
      })
    }).then(r => r.json()).then(data => {
      const quizResult = document.getElementById('quizResult');
      quizResult.classList.remove('hidden');
      if (data.success) {
        quizResult.innerHTML = `<div class='bg-green-100 text-green-900 border-green-400 border-2 rounded-lg p-6 text-xl font-bold'>${data.message||'Chapter Quiz submitted!'}</div>`;
        setTimeout(()=>window.location='?program_id=<?= $programID ?>', 2200);
      } else {
        quizResult.innerHTML = `<div class='bg-red-100 text-red-900 border-red-400 border-2 rounded-lg p-6 text-xl font-bold'>${data.message||'Quiz submission error.'}</div>`;
      }
    });
  });
}
</script>
        <?php elseif ($currentContent && $currentType === 'story'): ?>
          <div class="bg-white rounded-xl shadow-md p-6 space-y-6">
            <div class="border-b pb-4">
              <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($currentContent['title']) ?></h2>
            </div>
            <?php if (!empty($currentContent['synopsis_arabic'])): ?>
              <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-5 border-r-4 border-blue-600">
                <h3 class="text-lg font-semibold text-blue-900 mb-2 flex items-center gap-2"><i class="ph ph-book-open"></i> ملخص القصة (Arabic Synopsis)</h3>
                <p class="text-gray-800 leading-relaxed text-right" dir="rtl"><?= nl2br(htmlspecialchars($currentContent['synopsis_arabic'])) ?></p>
              </div>
            <?php endif; ?>
            <?php if (!empty($currentContent['synopsis_english'])): ?>
              <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-5 border-l-4 border-green-600">
                <h3 class="text-lg font-semibold text-green-900 mb-2 flex items-center gap-2"><i class="ph ph-book-open"></i> English Synopsis</h3>
                <p class="text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($currentContent['synopsis_english'])) ?></p>
              </div>
            <?php endif; ?>
            <?php if (!empty($currentContent['video_url'])): ?>
              <?php $embedUrl = toYouTubeEmbedUrl($currentContent['video_url']); ?>
              <?php if ($embedUrl): ?>
                <div class="space-y-3">
                  <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2"><i class="ph ph-video-camera text-red-600"></i> Watch Story Video</h3>
                  <div class="relative w-full pb-[56.25%] h-0 overflow-hidden rounded-lg shadow-lg"><iframe id="storyVideo" class="absolute top-0 left-0 w-full h-full" src="<?= htmlspecialchars($embedUrl) ?>" title="Story Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture;" allowfullscreen></iframe></div>
                </div>
              <?php endif; ?>
            <?php endif; ?>
            <?php if ($is_completed): ?>
              <div class="mt-8 bg-green-50 border-2 border-green-200 rounded-xl shadow-sm p-8 flex items-center gap-4"><i class="ph ph-check-circle text-4xl text-green-500"></i><div><h3 class="text-lg font-bold text-green-800 mb-2">Story Completed!</h3><p class="text-green-900">You have finished this interactive section. You can proceed using the sidebar or Next button below.</p></div></div>
            <?php elseif (!empty($currentContent['interactive_section']) && !empty($currentContent['quiz_question'])): ?>
              <?php 
              $section = $currentContent['interactive_section'];
              $question = $currentContent['quiz_question']; 
              ?>
              <div id="quizSection" class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-6 border-2 border-purple-300">
                <div class="flex items-center gap-2 mb-4">
                  <i class="ph ph-chat-circle-dots text-3xl text-purple-600"></i>
                  <h3 class="text-xl font-bold text-purple-900">Interactive Section</h3>
                </div>
                <p class="text-gray-800 font-medium mb-4 text-lg"><?= htmlspecialchars($question['question_text']) ?></p>
                <form id="quizForm" class="space-y-3">
                  <?php foreach ($question['options'] as $index => $option): ?>
                    <label class="flex items-center gap-3 p-4 bg-white rounded-lg border-2 border-gray-200 hover:border-purple-400 cursor-pointer transition-all">
                      <input type="radio" name="answer" value="<?= $option['option_id'] ?>" class="w-5 h-5 text-purple-600 focus:ring-purple-500" required>
                      <span class="text-gray-800"><?= htmlspecialchars($option['option_text']) ?></span>
                    </label>
                  <?php endforeach; ?>
                  <input type="hidden" name="question_id" value="<?= $question['question_id'] ?>">
                  <input type="hidden" name="story_id" value="<?= $currentContent['story_id'] ?>">
                  <input type="hidden" name="chapter_id" value="<?= $currentContent['chapter_id'] ?>">
                  <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold shadow-lg transition-colors">
                      <i class="ph ph-check-circle mr-2"></i>Submit Answer
                    </button>
                    <button type="button" id="retryBtn" class="hidden px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors" onclick="retryQuestion()">
                      <i class="ph ph-arrow-clockwise mr-2"></i>Retry
                    </button>
                  </div>
                </form>
                <div id="answerFeedback" class="hidden mt-4 p-4 rounded-lg"></div>
              </div>
            <?php endif; ?>
            <div id="nextStorySection" class="<?= $is_completed ? '' : 'hidden' ?> text-center pt-4">
              <?php
              if ($isLastStoryInChapter && $currentChapterQuiz):
              ?>
                <a href="?program_id=<?= $programID ?>&quiz_id=<?= $currentChapterQuiz['quiz_id'] ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold shadow-lg transition-colors">
                  <i class="ph ph-exam"></i>
                  Take Chapter Quiz
                </a>
              <?php
              else:
                $nextStory = null;
                $foundCurrent = false;
                foreach ($navigation as $chapter) {
                  foreach ($chapter['stories'] as $story) {
                    if ($foundCurrent) {
                      $nextStory = $story;
                      break 2;
                    }
                    if ($currentContent && $story['story_id'] == $currentContent['story_id']) {
                      $foundCurrent = true;
                    }
                  }
                }
                if ($nextStory):
              ?>
                <a href="?program_id=<?= $programID ?>&story_id=<?= $nextStory['story_id'] ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-lg transition-colors">Next Story: <?= htmlspecialchars($nextStory['title']) ?><i class="ph ph-arrow-right"></i></a>
              <?php else: ?>
                <div class="text-gray-600 font-medium"><i class="ph ph-check-circle text-green-600 text-2xl"></i><p class="mt-2">You've completed all stories!</p><?php if ($showExam): ?><p class="mt-1 text-orange-700"><a href="?program_id=<?= $programID ?>&take_exam=1" class="font-bold underline">Ready for the Program Exam?</a></p><?php elseif ($showCertificate): ?><p class="mt-1 text-green-700"><a href="<?= htmlspecialchars($certificate['certificate_url'] ?? '#') ?>" target="_blank" class="font-semibold underline">Download your Certificate</a></p><?php endif; ?></div>
              <?php endif; endif; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="ph ph-graduation-cap text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Welcome to the Program!</h3>
            <p class="text-gray-500">Select a story from the sidebar to begin your learning journey.</p>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const programId = <?= $programID ?>;
let canProceed = false;

function toggleChapter(chapterId) {
  const panel = document.getElementById('chapter-panel-' + chapterId);
  const chevron = document.getElementById('chev-' + chapterId);
  if (panel.classList.contains('hidden')) {
    panel.classList.remove('hidden');
    chevron.style.transform = 'rotate(0deg)';
  } else {
    panel.classList.add('hidden');
    chevron.style.transform = 'rotate(-90deg)';
  }
}

<?php if ($currentContent && isset($currentContent['chapter_id'])): ?>
toggleChapter(<?= $currentContent['chapter_id'] ?>);
<?php endif; ?>

function updateProgress() {
  fetch('../../php/quiz-answer-handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({action: 'get_progress', program_id: programId})
  }).then(response => response.json()).then(data => {
    if (data.success) {
      const percentage = data.completion_percentage || 0;
      const progressBar = document.getElementById('progressBar');
      const progressPercent = document.getElementById('progressPercent');
      if (progressBar) progressBar.style.width = percentage + '%';
      if (progressPercent) progressPercent.textContent = percentage.toFixed(1) + '%';
      if (percentage >= 100) {
        Swal.fire({title:'Congratulations!', text:'Program completed!', icon:'success', confirmButtonColor:'#10375B'});
      }
    }
  }).catch(error => console.error('Progress update error:', error));
}

const quizForm = document.getElementById('quizForm');
if (quizForm) {
  quizForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(quizForm);
    const selectedAnswer = formData.get('answer');
    const questionId = formData.get('question_id');
    if (!selectedAnswer) {
      Swal.fire({title:'No Answer Selected', text:'Please select an answer before submitting.', icon:'warning', confirmButtonColor:'#ea580c'});
      return;
    }
    fetch('../../php/quiz-answer-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'check_interactive_answer',
        question_id: questionId,
        option_id: selectedAnswer,
        story_id: formData.get('story_id')
      })
    }).then(response => response.json()).then(data => {
      const feedbackDiv = document.getElementById('answerFeedback');
      const retryBtn = document.getElementById('retryBtn');
      const nextSection = document.getElementById('nextStorySection');
      const submitBtn = quizForm.querySelector('button[type="submit"]');
      feedbackDiv.classList.remove('hidden');
      if (data.correct) {
        feedbackDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 border-2 border-green-500';
        feedbackDiv.innerHTML = `<div class="flex items-center gap-3"><i class="ph ph-check-circle text-3xl text-green-600"></i><div><h4 class="font-bold text-green-900">Correct! Well Done! 🎉</h4><p class="text-green-800 text-sm">${data.message || 'You can now proceed to the next story.'}</p></div></div>`;
        canProceed = true;
        submitBtn.disabled = true;
        quizForm.querySelectorAll('input[type="radio"]').forEach(input => input.disabled = true);
        nextSection.classList.remove('hidden');
        updateProgress();
      } else {
        feedbackDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 border-2 border-red-500';
        feedbackDiv.innerHTML = `<div class="flex items-center gap-3"><i class="ph ph-x-circle text-3xl text-red-600"></i><div><h4 class="font-bold text-red-900">Incorrect Answer</h4><p class="text-red-800 text-sm">${data.message || 'Please review the story and try again.'}</p></div></div>`;
        canProceed = false;
        submitBtn.style.display = 'none';
        retryBtn.classList.remove('hidden');
      }
    }).catch(error => {
      Swal.fire({title: 'Error', text: 'Failed to submit answer. Please try again.', icon: 'error', confirmButtonColor: '#dc2626'});
    });
  });
}

function retryQuestion() {
  const feedbackDiv = document.getElementById('answerFeedback');
  const retryBtn = document.getElementById('retryBtn');
  const submitBtn = quizForm.querySelector('button[type="submit"]');
  const radios = quizForm.querySelectorAll('input[type="radio"]');
  feedbackDiv.classList.add('hidden');
  retryBtn.classList.add('hidden');
  submitBtn.style.display = '';
  submitBtn.disabled = false;
  radios.forEach(radio => { radio.checked = false; radio.disabled = false; });
  canProceed = false;
}
</script>
<?php include '../../components/footer.php'; ?>
