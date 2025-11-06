<?php
// This file contains the original student program view design for non-enrolled students
// Fetch chapters and teacher info
$chapters = fetchChapters($conn, $programID);
$tidStmt = $conn->prepare('SELECT teacherID FROM programs WHERE programID = ?');
$tidStmt->bind_param('i', $programID);
$tidStmt->execute();
$tidRes = $tidStmt->get_result()->fetch_assoc();
$teacherID = $tidRes['teacherID'] ?? null;
$teacher = null;
if ($teacherID) {
    $teacherStmt = $conn->prepare('SELECT t.teacherID, t.fname, t.lname, t.specialization, t.profile_picture FROM teacher t WHERE t.teacherID = ?');
    $teacherStmt->bind_param('i', $teacherID);
    $teacherStmt->execute();
    $teacher = $teacherStmt->get_result()->fetch_assoc();
}

$completion = (float)($program['completion_percentage'] ?? 0);
$price = isset($program['price']) ? (float)$program['price'] : 0.0;
$currency = $program['currency'] ?: 'PHP';
$symbolMap = ['USD'=>'$','EUR'=>'€','GBP'=>'£','JPY'=>'¥','CNY'=>'¥','KRW'=>'₩','INR'=>'₹','PHP'=>'₱','AUD'=>'A$','CAD'=>'C$','SGD'=>'S$','HKD'=>'HK$'];
$symbol = $symbolMap[strtoupper($currency)] ?? '';

// Enrollee count
$enrolStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM student_program_enrollments WHERE program_id = ?');
$enrolStmt->bind_param('i', $programID);
$enrolStmt->execute();
$enrollees = (int)($enrolStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
?>

<div class="page-container">
  <div class="page-content">
    <section class="content-section">
      <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <!-- Top: full-width program IMAGE -->
        <div class="w-full">
          <?php
            $heroImg = !empty($program['image']) ? '../../images/'.htmlspecialchars($program['image']) : '../../images/blog-bg.svg';
          ?>
          <img src="<?= $heroImg ?>" alt="Program Image" class="w-full h-64 md:h-80 object-cover">
        </div>

        <!-- Header row: left (price, title, difficulty), right (enroll button, enrollees) -->
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
          <!-- Left -->
          <div class="md:col-span-2 space-y-2">
            <div class="text-[#10375B] font-bold text-xl">
              <?= $symbol ? htmlspecialchars($symbol) : htmlspecialchars(strtoupper($currency)).' ' ?><?= number_format($price, 2) ?>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
              <?= htmlspecialchars($program['title']) ?>
            </h1>
            <div class="proficiency-badge inline-flex items-center gap-2 mt-1">
              <i class="ph-fill ph-barbell text-[16px]"></i>
              <span class="text-sm font-semibold"><?= htmlspecialchars(ucfirst(strtolower($program['category']))) ?> Difficulty</span>
            </div>
          </div>

          <!-- Right -->
          <div class="md:col-span-1 flex md:flex-col gap-3 items-stretch md:items-end justify-between md:justify-start">
            <form method="POST">
              <input type="hidden" name="action" value="enroll">
              <button type="submit" class="px-5 py-2 rounded-lg font-semibold text-white bg-[#A58618] hover:bg-[#8a6f15]">
                Enroll
              </button>
            </form>
            <div class="text-gray-700 text-sm flex items-center gap-2">
              <i class="ph ph-users-three text-[18px]"></i>
              <span><?= $enrollees ?> enrollees</span>
            </div>
          </div>
        </div>

        <!-- Description and Chapters -->
        <div class="px-6 pb-6 space-y-6">
          <div>
            <h2 class="text-xl font-bold mb-2">Description</h2>
            <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($program['description'] ?? '')) ?></p>
          </div>

          <div>
            <h2 class="text-xl font-bold mb-2">Chapters</h2>
            <?php if (empty($chapters)): ?>
              <p class="text-gray-500">No chapters available for this program.</p>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($chapters as $chapter): ?>
                  <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between">
                      <h3 class="font-semibold"><?= htmlspecialchars($chapter['title']) ?></h3>
                      <span class="text-xs text-gray-500">Locked</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Enroll to view chapter content.</p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Overview Video (YouTube-safe embed) -->
          <?php 
            $embedUrl = toYouTubeEmbedUrl($program['overview_video_url'] ?? '');
          ?>
          <?php if ($embedUrl): ?>
          <div>
            <h2 class="text-xl font-bold mb-2">Overview</h2>
            <div class="relative w-full pb-[56.25%] h-0 overflow-hidden rounded-lg">
              <iframe class="absolute top-0 left-0 w-full h-full" src="<?= htmlspecialchars($embedUrl) ?>" title="Program Overview" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture;" allowfullscreen></iframe>
            </div>
          </div>
          <?php elseif (!empty($program['overview_video_url'])): ?>
          <div>
            <h2 class="text-xl font-bold mb-2">Overview</h2>
            <p class="text-sm text-gray-500">Embedding is not available. <a class="text-blue-600 underline" href="<?= htmlspecialchars($program['overview_video_url']) ?>" target="_blank" rel="noopener">Watch on YouTube</a>.</p>
          </div>
          <?php endif; ?>

          <!-- Teacher info - centered -->
          <?php if ($teacher): ?>
          <div class="border-t pt-6 mt-2">
            <h2 class="text-xl font-bold mb-3 text-center">About the Teacher</h2>
            <div class="flex flex-col items-center gap-3 text-center">
              <?php
                $tImg = !empty($teacher['profile_picture']) ? '../../uploads/teacher_profiles/'.htmlspecialchars($teacher['profile_picture']) : '../../images/dashboard-profile-male.svg';
              ?>
              <img src="<?= $tImg ?>" alt="Teacher" class="w-20 h-20 rounded-full object-cover">
              <div>
                <div class="font-semibold text-gray-900">
                  <?= htmlspecialchars(trim(($teacher['fname'] ?? '').' '.($teacher['lname'] ?? ''))) ?>
                </div>
                <div class="text-sm text-gray-600">
                  <?= htmlspecialchars($teacher['specialization'] ?? 'Teacher') ?>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</div>