<?php
// [Patch] Added chapter quiz proper JS submission (fix quiz/exam/certificate)
// (Rest of file unchanged)

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
    // Show selected quiz (list of questions & choices)
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
        $quiz = getChapterQuiz($conn, $currentContent['chapter_id']);
        if ($quiz) {
            $questions = quizQuestion_getByQuiz($conn, $quiz['quiz_id']);
            if (!empty($questions)) {
                $currentContent['quiz_question'] = $questions[array_rand($questions)];
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
        <!-- Sidebar unchanged -->
      </aside>
      <section class="lg:col-span-9 lg:order-last space-y-6">
        <!-- ... Partial file ... -->
        <?php if ($currentType === 'final_exam'): ?>
        <!-- Final Exam unchanged ... -->
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
// PATCHED: Chapter Quiz Submission Handler
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
        quiz_id: <?= $quizID ?>
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
        <!-- Story section unchanged ... -->
        <?php else: ?>
        <!-- ... Welcome ... -->
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>
<!-- ... JS and footer unchanged ... -->
