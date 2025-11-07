<?php
session_start();
require '../../php/dbConnection.php';
require '../../php/functions.php';
require '../../php/program-core.php';
require '../../php/quiz-handler.php';
require_once '../../php/youtube-embed-helper.php';

// Guard
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$studentID = (int)$_SESSION['userID'];
$programID = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$storyID = isset($_GET['story_id']) ? (int)$_GET['story_id'] : 0;
if ($programID <= 0) { header('Location: student-programs.php'); exit(); }

// Get program with enrollment status
$program = getProgramDetails($conn, $programID, $studentID);
if (!$program) { header('Location: student-programs.php?tab=all'); exit(); }
$isEnrolled = !empty($program['is_enrolled']);

// Handle enroll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll') {
    if (!$isEnrolled && enrollStudentInProgram($conn, $studentID, $programID)) {
        header('Location: student-program-view.php?program_id='.$programID);
        exit();
    }
}

// Unenrolled: show original view
if (!$isEnrolled) {
    $current_page = 'student-programs';
    $page_title = 'Program Details';
    include '../../components/header.php';
    include '../../components/student-nav.php';
    include 'student-program-view-original.php';
    include '../../components/footer.php';
    exit();
}

// Enrolled: Get all chapters with stories and quizzes
$chapters = getChapters($conn, $programID);
$completion = (float)($program['completion_percentage'] ?? 0);

// Build navigation structure
$navigation = [];
foreach ($chapters as $chapter) {
    $chapterData = [
        'chapter_id' => $chapter['chapter_id'],
        'title' => $chapter['title'],
        'type' => 'chapter',
        'stories' => [],
        'quiz' => null
    ];
    
    // Get stories for this chapter
    $stories = chapter_getStories($conn, $chapter['chapter_id']);
    foreach ($stories as $story) {
        $chapterData['stories'][] = [
            'story_id' => $story['story_id'],
            'title' => $story['title'],
            'synopsis_arabic' => $story['synopsis_arabic'],
            'synopsis_english' => $story['synopsis_english'],
            'video_url' => $story['video_url'],
            'type' => 'story'
        ];
    }
    
    // Get quiz for this chapter
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
$currentType = 'story'; // 'story' or 'quiz'

if ($storyID > 0) {
    // Load specific story
    $stmt = $conn->prepare("SELECT * FROM chapter_stories WHERE story_id = ?");
    $stmt->bind_param("i", $storyID);
    $stmt->execute();
    $currentContent = $stmt->get_result()->fetch_assoc();
    $currentType = 'story';
    
    if ($currentContent) {
        // Get a random quiz question for this story's chapter
        $quiz = getChapterQuiz($conn, $currentContent['chapter_id']);
        if ($quiz) {
            $questions = quizQuestion_getByQuiz($conn, $quiz['quiz_id']);
            if (!empty($questions)) {
                $currentContent['quiz_question'] = $questions[array_rand($questions)];
            }
        }
    }
} else {
    // Load first story of first chapter
    if (!empty($navigation) && !empty($navigation[0]['stories'])) {
        $firstStory = $navigation[0]['stories'][0];
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
.content-locked { opacity: 0.5; pointer-events: none; }
</style>

<div class="page-container">
  <div class="page-content">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- LEFT SIDEBAR: Navigation -->
      <aside class="lg:col-span-3 lg:order-first">
        <div class="lg:sticky lg:top-6 space-y-4">
          
          <!-- Progress Card -->
          <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700">Progress</span>
              <span id="progressPercent" class="text-sm font-bold text-blue-600"><?= number_format($completion, 1) ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
              <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full transition-all" style="width: <?= max(0,min(100,$completion)) ?>%"></div>
            </div>
          </div>

          <!-- Program Navigation -->
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
                  <!-- Chapter Header -->
                  <div class="mb-2">
                    <button type="button" 
                            class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-50 rounded-lg"
                            onclick="toggleChapter(<?= $navItem['chapter_id'] ?>)">
                      <span class="flex items-center gap-2 font-semibold text-gray-800">
                        <i class="ph ph-folder text-blue-600"></i>
                        <?= htmlspecialchars($navItem['title']) ?>
                      </span>
                      <i id="chev-<?= $navItem['chapter_id'] ?>" class="ph ph-caret-down text-gray-400 transition-transform"></i>
                    </button>
                    
                    <!-- Chapter Items -->
                    <div id="chapter-panel-<?= $navItem['chapter_id'] ?>" class="ml-4 mt-1 space-y-1">
                      
                      <!-- Stories -->
                      <?php if (!empty($navItem['stories'])): ?>
                        <?php foreach ($navItem['stories'] as $story): ?>
                          <a href="?program_id=<?= $programID ?>&story_id=<?= $story['story_id'] ?>" 
                             class="sidebar-item flex items-center gap-2 p-2 pl-6 text-sm rounded-lg <?= ($currentContent && $currentContent['story_id'] == $story['story_id']) ? 'active font-semibold' : 'text-gray-700' ?>">
                            <i class="ph ph-play-circle text-green-600"></i>
                            <?= htmlspecialchars($story['title']) ?>
                          </a>
                        <?php endforeach; ?>
                      <?php endif; ?>
                      
                      <!-- Quiz -->
                      <?php if ($navItem['quiz']): ?>
                        <div class="sidebar-item flex items-center gap-2 p-2 pl-6 text-sm rounded-lg text-gray-500">
                          <i class="ph ph-exam text-orange-600"></i>
                          <?= htmlspecialchars($navItem['quiz']['title']) ?>
                        </div>
                      <?php endif; ?>
                      
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
          
        </div>
      </aside>

      <!-- RIGHT: Main Content Area -->
      <section class="lg:col-span-9 lg:order-last space-y-6">
        
        <!-- Program Header -->
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

        <!-- Story Content -->
        <?php if ($currentContent && $currentType === 'story'): ?>
          <div class="bg-white rounded-xl shadow-md p-6 space-y-6">
            
            <!-- Story Title -->
            <div class="border-b pb-4">
              <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($currentContent['title']) ?></h2>
            </div>
            
            <!-- Arabic Synopsis -->
            <?php if (!empty($currentContent['synopsis_arabic'])): ?>
              <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-5 border-r-4 border-blue-600">
                <h3 class="text-lg font-semibold text-blue-900 mb-2 flex items-center gap-2">
                  <i class="ph ph-book-open"></i>
                  ملخص القصة (Arabic Synopsis)
                </h3>
                <p class="text-gray-800 leading-relaxed text-right" dir="rtl"><?= nl2br(htmlspecialchars($currentContent['synopsis_arabic'])) ?></p>
              </div>
            <?php endif; ?>
            
            <!-- English Synopsis -->
            <?php if (!empty($currentContent['synopsis_english'])): ?>
              <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-5 border-l-4 border-green-600">
                <h3 class="text-lg font-semibold text-green-900 mb-2 flex items-center gap-2">
                  <i class="ph ph-book-open"></i>
                  English Synopsis
                </h3>
                <p class="text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($currentContent['synopsis_english'])) ?></p>
              </div>
            <?php endif; ?>
            
            <!-- Video Player -->
            <?php if (!empty($currentContent['video_url'])): ?>
              <?php $embedUrl = toYouTubeEmbedUrl($currentContent['video_url']); ?>
              <?php if ($embedUrl): ?>
                <div class="space-y-3">
                  <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="ph ph-video-camera text-red-600"></i>
                    Watch Story Video
                  </h3>
                  <div class="relative w-full pb-[56.25%] h-0 overflow-hidden rounded-lg shadow-lg">
                    <iframe id="storyVideo" class="absolute top-0 left-0 w-full h-full" src="<?= htmlspecialchars($embedUrl) ?>" title="Story Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture;" allowfullscreen></iframe>
                  </div>
                </div>
              <?php endif; ?>
            <?php endif; ?>
            
            <!-- Interactive Quiz Question -->
            <?php if (!empty($currentContent['quiz_question'])): ?>
              <?php $question = $currentContent['quiz_question']; ?>
              <div id="quizSection" class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-xl p-6 border-2 border-orange-300">
                <div class="flex items-center gap-2 mb-4">
                  <i class="ph ph-brain text-3xl text-orange-600"></i>
                  <h3 class="text-xl font-bold text-orange-900">Knowledge Check</h3>
                </div>
                
                <p class="text-gray-800 font-medium mb-4 text-lg"><?= htmlspecialchars($question['question_text']) ?></p>
                
                <form id="quizForm" class="space-y-3">
                  <?php foreach ($question['options'] as $index => $option): ?>
                    <label class="flex items-center gap-3 p-4 bg-white rounded-lg border-2 border-gray-200 hover:border-orange-400 cursor-pointer transition-all">
                      <input type="radio" name="answer" value="<?= $option['quiz_option_id'] ?>" class="w-5 h-5 text-orange-600 focus:ring-orange-500" required>
                      <span class="text-gray-800"><?= htmlspecialchars($option['option_text']) ?></span>
                    </label>
                  <?php endforeach; ?>
                  
                  <input type="hidden" name="question_id" value="<?= $question['quiz_question_id'] ?>">
                  <input type="hidden" name="story_id" value="<?= $currentContent['story_id'] ?>">
                  <input type="hidden" name="chapter_id" value="<?= $currentContent['chapter_id'] ?>">
                  
                  <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold shadow-lg transition-colors">
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
            
            <!-- Next Story Button (ALWAYS HIDDEN until quiz passed) -->
            <div id="nextStorySection" class="hidden text-center pt-4">
              <?php
                // Find next story
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
              ?>
              <?php if ($nextStory): ?>
                <a href="?program_id=<?= $programID ?>&story_id=<?= $nextStory['story_id'] ?>" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-lg transition-colors">
                  Next Story: <?= htmlspecialchars($nextStory['title']) ?>
                  <i class="ph ph-arrow-right"></i>
                </a>
              <?php else: ?>
                <div class="text-gray-600 font-medium">
                  <i class="ph ph-check-circle text-green-600 text-2xl"></i>
                  <p class="mt-2">You've completed all stories!</p>
                </div>
              <?php endif; ?>
            </div>
            
          </div>
        <?php else: ?>
          <!-- No content selected -->
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

// Toggle chapter in sidebar
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

// Initialize - expand current chapter
<?php if ($currentContent && isset($currentContent['chapter_id'])): ?>
toggleChapter(<?= $currentContent['chapter_id'] ?>);
<?php endif; ?>

// Update progress bar
function updateProgress() {
  fetch('../../php/quiz-answer-handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'get_progress',
      program_id: programId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      const percentage = data.completion_percentage || 0;
      const progressBar = document.getElementById('progressBar');
      const progressPercent = document.getElementById('progressPercent');
      
      if (progressBar) {
        progressBar.style.width = percentage + '%';
      }
      if (progressPercent) {
        progressPercent.textContent = percentage.toFixed(1) + '%';
      }
    }
  })
  .catch(error => console.error('Progress update error:', error));
}

// Handle quiz submission
const quizForm = document.getElementById('quizForm');
if (quizForm) {
  quizForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(quizForm);
    const selectedAnswer = formData.get('answer');
    const questionId = formData.get('question_id');
    
    if (!selectedAnswer) {
      Swal.fire({
        title: 'No Answer Selected',
        text: 'Please select an answer before submitting.',
        icon: 'warning',
        confirmButtonColor: '#ea580c'
      });
      return;
    }
    
    // Send answer to server for validation
    fetch('../../php/quiz-answer-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'check_answer',
        question_id: questionId,
        option_id: selectedAnswer,
        story_id: formData.get('story_id')
      })
    })
    .then(response => response.json())
    .then(data => {
      const feedbackDiv = document.getElementById('answerFeedback');
      const retryBtn = document.getElementById('retryBtn');
      const nextSection = document.getElementById('nextStorySection');
      const submitBtn = quizForm.querySelector('button[type="submit"]');
      
      feedbackDiv.classList.remove('hidden');
      
      if (data.correct) {
        // Correct answer
        feedbackDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 border-2 border-green-500';
        feedbackDiv.innerHTML = `
          <div class="flex items-center gap-3">
            <i class="ph ph-check-circle text-3xl text-green-600"></i>
            <div>
              <h4 class="font-bold text-green-900">Correct! Well Done! 🎉</h4>
              <p class="text-green-800 text-sm">${data.message || 'You can now proceed to the next story.'}</p>
            </div>
          </div>
        `;
        
        canProceed = true;
        submitBtn.disabled = true;
        quizForm.querySelectorAll('input[type="radio"]').forEach(input => input.disabled = true);
        nextSection.classList.remove('hidden');
        
        // Update progress bar
        updateProgress();
        
      } else {
        // Wrong answer
        feedbackDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 border-2 border-red-500';
        feedbackDiv.innerHTML = `
          <div class="flex items-center gap-3">
            <i class="ph ph-x-circle text-3xl text-red-600"></i>
            <div>
              <h4 class="font-bold text-red-900">Incorrect Answer</h4>
              <p class="text-red-800 text-sm">${data.message || 'Please review the story and try again.'}</p>
            </div>
          </div>
        `;
        
        canProceed = false;
        submitBtn.style.display = 'none';
        retryBtn.classList.remove('hidden');
      }
    })
    .catch(error => {
      Swal.fire({
        title: 'Error',
        text: 'Failed to submit answer. Please try again.',
        icon: 'error',
        confirmButtonColor: '#dc2626'
      });
    });
  });
}

function retryQuestion() {
  // Reset form
  const feedbackDiv = document.getElementById('answerFeedback');
  const retryBtn = document.getElementById('retryBtn');
  const submitBtn = quizForm.querySelector('button[type="submit"]');
  const radios = quizForm.querySelectorAll('input[type="radio"]');
  
  feedbackDiv.classList.add('hidden');
  retryBtn.classList.add('hidden');
  submitBtn.style.display = '';
  submitBtn.disabled = false;
  
  radios.forEach(radio => {
    radio.checked = false;
    radio.disabled = false;
  });
  
  canProceed = false;
}
</script>

<?php include '../../components/footer.php'; ?>