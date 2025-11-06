<?php
session_start();
require '../../php/dbConnection.php';
require '../../php/functions.php';
require_once '../../php/youtube-embed-helper.php';

// Guard
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$studentID = (int)$_SESSION['userID'];
$programID = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$currentChapterID = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
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

// Unenrolled: show original view exactly
if (!$isEnrolled) {
    $current_page = 'student-programs';
    $page_title = 'Program Details';
    include '../../components/header.php';
    include '../../components/student-nav.php';
    include 'student-program-view-original.php';
    include '../../components/footer.php';
    exit();
}

// Enrolled
$chapters = fetchChapters($conn, $programID);
$completion = (float)($program['completion_percentage'] ?? 0);

// Current content defaults to first chapter
$currentChapter = null;
if ($currentChapterID > 0) {
    foreach ($chapters as $c) if ($c['chapter_id'] == $currentChapterID) { $currentChapter = $c; break; }
}
if (!$currentChapter && !empty($chapters)) { $currentChapter = $chapters[0]; }

$current_page = 'student-programs';
$page_title = htmlspecialchars($program['title']);
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<!-- Force a consistent wrapper: a single grid row with 12 columns to keep sidebar and content aligned on all pages -->
<div class="page-container">
  <div class="page-content">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- LEFT: Sticky Sidebar (col-span-4) -->
      <aside class="lg:col-span-4 lg:order-first lg:self-start">
        <div class="lg:sticky lg:top-6 space-y-4">
          <!-- Progress Card -->
          <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-700">Progress</span>
              <span class="text-sm font-bold text-[#10375B]"><?= number_format($completion, 1) ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-[#10375B] h-2 rounded-full" style="width: <?= max(0,min(100,$completion)) ?>%"></div>
            </div>
          </div>

          <!-- Chapters Card -->
          <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-lg font-bold">Chapters</h2>
              <button id="collapseAll" type="button" class="text-sm text-gray-600 hover:text-gray-900">Collapse</button>
            </div>
            <?php if (empty($chapters)): ?>
              <p class="text-gray-500 text-sm">No chapters available.</p>
            <?php else: ?>
              <ul class="space-y-2">
                <?php foreach ($chapters as $chapter): ?>
                  <li class="border border-gray-200 rounded-md overflow-hidden">
                    <button type="button" class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-50" onclick="toggleChapter(<?= $chapter['chapter_id'] ?>)">
                      <span class="flex items-center gap-2 <?php if($currentChapter && $currentChapter['chapter_id']==$chapter['chapter_id']) echo 'font-semibold text-[#10375B]'; ?>">
                        <i class="ph ph-book-open"></i>
                        <?= htmlspecialchars($chapter['title']) ?>
                      </span>
                      <i id="chev-<?= $chapter['chapter_id'] ?>" class="ph ph-caret-down text-gray-400 transition-transform"></i>
                    </button>
                    <div id="panel-<?= $chapter['chapter_id'] ?>" class="px-3 pb-3 <?php if(!$currentChapter || $currentChapter['chapter_id']!=$chapter['chapter_id']) echo 'hidden'; ?>">
                      <a href="?program_id=<?= $programID ?>&chapter_id=<?= $chapter['chapter_id'] ?>" class="block pl-6 py-2 text-sm rounded hover:bg-gray-100">
                        <i class="ph ph-file-text text-[#A58618] mr-2"></i> Story: <?= htmlspecialchars($chapter['title']) ?>
                      </a>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </aside>

      <!-- RIGHT: Main Content (col-span-8) -->
      <section class="lg:col-span-8 lg:order-last space-y-6">
        <!-- Hero + Title block kept inside RIGHT column to avoid layout drift -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
          <div class="w-full">
            <?php $heroImg = !empty($program['image']) ? '../../images/'.htmlspecialchars($program['image']) : '../../images/blog-bg.svg'; ?>
            <img src="<?= $heroImg ?>" alt="Program Image" class="w-full h-64 md:h-80 object-cover">
          </div>
          <div class="p-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              <?= htmlspecialchars($program['title']) ?>
            </h1>
            <div class="inline-flex items-center gap-2 mt-2">
              <i class="ph-fill ph-barbell text-[16px]"></i>
              <span class="text-sm font-semibold"><?= htmlspecialchars(ucfirst(strtolower($program['category']))) ?> Difficulty</span>
            </div>
          </div>
        </div>

        <!-- Description Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
          <h2 class="text-xl font-bold mb-2">Description</h2>
          <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($program['description'] ?? '')) ?></p>
        </div>

        <!-- Chapter Content Card -->
        <?php if ($currentChapter): ?>
          <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
            <h3 class="text-lg font-semibold mb-4"><?= htmlspecialchars($currentChapter['title']) ?></h3>

            <?php if (!empty($currentChapter['video_url'])): ?>
              <?php $embedUrl = toYouTubeEmbedUrl($currentChapter['video_url']); ?>
              <?php if ($embedUrl): ?>
                <div class="mb-6">
                  <div class="relative w-full pb-[56.25%] h-0 overflow-hidden rounded-lg">
                    <iframe class="absolute top-0 left-0 w-full h-full" src="<?= htmlspecialchars($embedUrl) ?>" title="Chapter Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture;" allowfullscreen></iframe>
                  </div>
                </div>
              <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($currentChapter['content'])): ?>
              <div class="prose max-w-none">
                <?= nl2br(htmlspecialchars($currentChapter['content'])) ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($currentChapter['question'])): ?>
              <div class="mt-6 p-4 bg-gray-50 rounded border">
                <h4 class="font-semibold mb-2">Interactive Section</h4>
                <p class="text-gray-700 mb-3"><?= nl2br(htmlspecialchars($currentChapter['question'])) ?></p>
                <?php if (!empty($currentChapter['answer_options'])): ?>
                  <?php $opts = json_decode($currentChapter['answer_options'], true) ?: []; ?>
                  <?php foreach ($opts as $i => $opt): ?>
                    <div class="mb-2 p-3 bg-white border rounded"> <span class="font-medium"><?= chr(65+$i) ?>.</span> <?= htmlspecialchars($opt) ?> </div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <p class="text-xs text-gray-500 mt-2">Note: Validation and locking will be added after DB migration.</p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Next Chapter button -->
          <?php 
            $next = null; 
            if (!empty($chapters)) {
              foreach ($chapters as $idx => $c) {
                if ($c['chapter_id'] == $currentChapter['chapter_id'] && isset($chapters[$idx+1])) { $next = $chapters[$idx+1]; break; }
              }
            }
          ?>
          <?php if ($next): ?>
            <div class="text-center">
              <a href="?program_id=<?= $programID ?>&chapter_id=<?= $next['chapter_id'] ?>" class="inline-flex items-center px-6 py-3 bg-[#A58618] text-white rounded-lg font-semibold shadow hover:bg-[#8a6f15]">
                Next: <?= htmlspecialchars($next['title']) ?>
              </a>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="text-center py-10 text-gray-500">Select a chapter from the sidebar to begin.</div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>

<script>
function toggleChapter(id){
  const p = document.getElementById('panel-'+id);
  const c = document.getElementById('chev-'+id);
  if(!p) return;
  const hidden = p.classList.contains('hidden');
  document.querySelectorAll('[id^="panel-"]').forEach(el=>el.classList.add('hidden'));
  document.querySelectorAll('[id^="chev-"]').forEach(el=>el.style.transform='rotate(-90deg)');
  if(hidden){ p.classList.remove('hidden'); if(c) c.style.transform='rotate(0deg)'; }
}

const collapseBtn = document.getElementById('collapseAll');
if (collapseBtn){
  collapseBtn.addEventListener('click', ()=>{
    document.querySelectorAll('[id^="panel-"]').forEach(el=>el.classList.add('hidden'));
    document.querySelectorAll('[id^="chev-"]').forEach(el=>el.style.transform='rotate(-90deg)');
  });
}
</script>

<?php include '../../components/footer.php'; ?>