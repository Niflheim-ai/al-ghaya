<?php
session_start();
require '../../php/dbConnection.php';
require '../../php/functions.php';
require '../../php/functions-user-progress.php';
require '../../php/program-core.php';
require '../../php/quiz-handler.php';
require_once '../../php/youtube-embed-helper.php';

// ... (rest of the previous setup code, navigation, logic until HTML)

?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
<style>
.quiz-score-badge { position:absolute; top:14px; right:14px; z-index:10; background:#444; color:white; border-radius:8px; font-size:1.1em; padding:5px 16px; font-weight:bold; }
.quiz-answer.option-user-correct { background: #d1fae5; border: 2px solid #10b981; }
.quiz-answer.option-user-wrong { background: #fee2e2; border: 2px solid #dc2626; }
.quiz-answer.option-correct:not(.option-user-correct) { background: #fef9c3; border: 2px solid #facc15; }
.quiz-answer { margin-bottom:6px; padding:13px; border-radius:7px; border:2px solid #eee; position:relative; }
.quiz-answer .ph { font-size: 1.1em; }
.sidebar-item.quiz-completed { background-color: #e0fce0 !important; border-left:4px solid #16a34a; color: #15803d !important; opacity: 1; }
.quiz-btn-row { display:flex; flex-direction:row; gap:1rem; justify-content:center; margin-top:38px; }
</style>
<div class="page-container">
  <div class="page-content">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- SIDEBAR (unchanged) -->
      <?php include 'student-sidebar.php'; ?>
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
        <?php if ($currentType === 'chapter_quiz' && isset($quizQuestions)): ?>
        <div class="bg-white rounded-xl shadow-md p-6 mt-8 relative" id="quizCardWrapper">
          <span class="quiz-score-badge hidden" id="quizScoreBadge"></span>
          <h2 class="text-2xl font-bold text-orange-800 mb-4 flex items-center gap-2"><i class="ph ph-exam text-orange-500"></i> Chapter Quiz</h2>
          <form id="chapterQuizForm">
            <?php foreach ($quizQuestions as $i => $question): ?>
            <div class="mb-6 border-b pb-5">
              <div class="font-semibold mb-2">Q<?= $i+1 ?>: <?= htmlspecialchars($question['question_text']) ?></div>
              <?php foreach ($question['options'] as $opt): ?>
              <div class="mb-2">
                <label class="flex gap-2 items-center quiz-answer">
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
        // Quiz review logic + highlights
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
              const scoreBadge = document.getElementById('quizScoreBadge');
              quizResult.classList.remove('hidden');
              chapterQuizForm.style.display = 'none';
              // Show score at top right
              scoreBadge.classList.remove('hidden');
              scoreBadge.innerHTML = `Score: <b>${data.score}</b> / ${data.total}`;
              // Render review
              let html = '';
              for (let q of data.review) {
                html += `<div class='mb-7 pb-5 border-b'><div class='font-semibold mb-2'>${q.question_text}</div>`;
                for (let opt of q.options) {
                  let c = 'quiz-answer';
                  if (opt.user_selected && opt.is_correct) c += ' option-user-correct';
                  else if (opt.user_selected && !opt.is_correct) c += ' option-user-wrong';
                  else if (opt.is_correct) c += ' option-correct';
                  html += `<div class='${c} flex items-center gap-2'><i class='ph ${opt.is_correct ? (opt.user_selected ? "ph-check-circle-fill" : "ph-check-circle") : (opt.user_selected ? "ph-x-circle-fill" : "ph-circle")}'></i> ${opt.text}</div>`;
                }
                html += '</div>';
              }
              // CONTINUE/NEXT
              let btns = `<div class='quiz-btn-row'>`;
              if (data.passed) {
                // Find next chapter first story
                <?php
                $nextChapterStory = null;
                $foundCurrent = false;
                foreach ($navigation as $navCh) {
                  if ($foundCurrent && !empty($navCh['stories'])) {
                    $nextChapterStory = $navCh['stories'][0];
                    break;
                  }
                  if ($navCh['quiz'] && $navCh['quiz']['quiz_id'] == $quizID) $foundCurrent = true;
                }
                ?>
                btns += `<a href="?program_id=<?= $programID ?>&story_id=<?= $nextChapterStory['story_id'] ?? '' ?>" class="px-7 py-3 bg-blue-600 hover:bg-blue-800 text-white rounded-lg font-bold transition-colors">Continue to next chapter</a>`;
              } else {
                btns += `<button onclick="window.location.reload()" class="px-7 py-3 bg-orange-600 hover:bg-orange-800 text-white rounded-lg font-bold transition-colors">Retry Quiz</button>`;
              }
              btns += `</div>`;
              html += btns;
              quizResult.innerHTML = html;
              // Mark sidebar
              const quizSidebar = document.querySelector(`a.sidebar-item[href*="quiz_id=<?= $quizID ?>"]`);
              if (data.passed && quizSidebar) {
                quizSidebar.classList.add('quiz-completed');
              }
            });
          });
        }
        </script>
        <?php endif; ?>
        <!-- All other main content output, story display, video, next story/certificate, etc remains unchanged below! -->
      </section>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include '../../components/footer.php'; ?>
