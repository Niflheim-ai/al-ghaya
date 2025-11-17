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
            $heroImg = !empty($program['image']) ? '../../uploads/thumbnails/'.htmlspecialchars($program['image']) : '../../images/blog-bg.svg';
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

          <!-- Right: Updated enrollment button with payment integration -->
          <div class="md:col-span-1 flex md:flex-col gap-3 items-stretch md:items-end justify-between md:justify-start">
            <button id="enrollBtn" type="button" class="px-5 py-2 rounded-lg font-semibold text-white bg-[#A58618] hover:bg-[#8a6f15] transition-colors flex items-center gap-2 justify-center">
              <i class="ph ph-lock-simple-open"></i>
              Enroll Now
            </button>
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
                      <span class="text-xs text-gray-500 flex items-center gap-1">
                        <i class="ph ph-lock-simple text-sm"></i>
                        Locked
                      </span>
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

<!-- SweetAlert2 for enrollment modal -->
<!-- SweetAlert2 for enrollment modal -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('enrollBtn').addEventListener('click', function() {
    const programId = <?= $programID ?>;
    const programTitle = <?= json_encode($program['title']) ?>;
    const programPrice = '<?= $symbol ?><?= number_format($price, 2) ?>';
    const isFree = <?= $price <= 0 ? 'true' : 'false' ?>;
    
    Swal.fire({
        title: '<i class="ph ph-graduation-cap text-4xl text-[#A58618]"></i><br>Confirm Enrollment',
        html: `
            <div class="text-left p-4">
                <p class="mb-3 text-gray-700">You are about to enroll in:</p>
                <div class="bg-gradient-to-r from-[#A58618]/10 to-[#10375B]/10 p-5 rounded-xl mb-4 border border-[#A58618]/20">
                    <p class="font-bold text-lg text-gray-900">${programTitle}</p>
                    <p class="text-3xl font-bold text-[#10375B] mt-2">${isFree ? 'FREE' : programPrice}</p>
                </div>
                
                ${!isFree ? `
                <div class="bg-blue-50 p-4 rounded-lg mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="ph ph-check-circle text-green-600"></i> What's included:
                    </p>
                    <ul class="text-sm text-gray-700 space-y-1">
                        <li class="flex items-start gap-2">
                            <i class="ph ph-check text-green-600 mt-0.5"></i>
                            <span>Lifetime access to all program content</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ph ph-check text-green-600 mt-0.5"></i>
                            <span>Interactive quizzes and assessments</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ph ph-check text-green-600 mt-0.5"></i>
                            <span>Progress tracking and achievements</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ph ph-check text-green-600 mt-0.5"></i>
                            <span>Certificate upon completion</span>
                        </li>
                    </ul>
                </div>
                ` : ''}
                
                ${!isFree ? `
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2 justify-center">
                    <i class="ph ph-lock text-green-600 text-lg"></i>
                    <span class="text-xs text-gray-600">Secure payment powered by PayMongo</span>
                </div>
                ` : ''}
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: isFree ? '<i class="ph ph-check-circle"></i> Enroll Now' : '<i class="ph ph-credit-card"></i> Proceed to Payment',
        confirmButtonColor: '#A58618',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#6b7280',
        width: '600px',
        customClass: {
            confirmButton: 'px-6 py-3 rounded-lg font-semibold',
            cancelButton: 'px-6 py-3 rounded-lg font-semibold'
        },
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch('../../php/create-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'program_id=' + programId
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response:', data); // Debug log
                if (!data.success) {
                    throw new Error(data.message || 'Failed to process enrollment');
                }
                return data;
            })
            .catch(error => {
                console.error('Error:', error); // Debug log
                Swal.showValidationMessage(`<i class="ph ph-warning-circle"></i> ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            console.log('Confirmed result:', result.value); // Debug log
            
            // Check if it's a free enrollment
            if (result.value.free_enrollment) {
                Swal.fire({
                    title: 'Enrolled Successfully!',
                    text: 'You have been enrolled in this program.',
                    icon: 'success',
                    confirmButtonColor: '#A58618',
                    confirmButtonText: 'Start Learning'
                }).then(() => {
                    // Redirect to the program view
                    window.location.href = 'student-program-view.php?program_id=' + programId;
                });
            } 
            // Check if it's a paid enrollment with checkout URL
            else if (result.value.checkout_url) {
                Swal.fire({
                    title: 'Redirecting to Payment',
                    html: '<div class="flex flex-col items-center gap-3"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#A58618]"></div><p>Please wait while we redirect you to the secure payment page...</p></div>',
                    showConfirmButton: false,
                    allowOutsideClick: false
                });
                
                // Redirect to PayMongo checkout
                setTimeout(() => {
                    window.location.href = result.value.checkout_url;
                }, 1500);
            }
            else {
                // Unexpected response
                Swal.fire({
                    title: 'Error',
                    text: 'Unexpected response from server. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#A58618'
                });
            }
        }
    });
});
</script>
