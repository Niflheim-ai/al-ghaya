<?php
    session_start();
    require '../../php/dbConnection.php';
    require '../../php/gamification.php';
    require '../../php/functions.php';

    // Check if user is logged in and is a student
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
        header("Location: ../login.php");
        exit();
    }

    $studentID = $_SESSION['userID'];
    $gamification = new GamificationSystem($conn);

    // Get user
    $stmt = $conn->prepare("SELECT * FROM user WHERE userID = ?");
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Stats
    $userStats = $gamification->getUserStats($studentID);
    $recentTransactions = $gamification->getRecentTransactions($studentID, 5);
    $achievements = $gamification->getUserAchievements($studentID);

    // Recent program + last chapter id for Resume Program button
    $stmt = $conn->prepare("
        SELECT p.programID, p.title, p.image,
               spe.completion_percentage AS progress,
               COALESCE(
                 (SELECT MAX(scp.chapterID)
                  FROM student_chapter_progress scp
                  WHERE scp.studentID = spe.student_id AND scp.programID = spe.program_id AND scp.completed = 1),
                 1
               ) AS last_chapter_id
        FROM student_program_enrollments spe
        JOIN programs p ON p.programID = spe.program_id
        WHERE spe.student_id = ?
        ORDER BY spe.last_accessed DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $recentProgram = $stmt->get_result()->fetch_assoc();

    // Session UI fields
    if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_email']) || !isset($_SESSION['user_avatar'])) {
        $_SESSION['user_name'] = trim($user['fname'] . ' ' . $user['lname']) ?: 'User';
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['user_avatar'] = '../../images/dashboard-profile-male.svg';
    }

    $userAvatar = $_SESSION['user_avatar'];
    $current_page = "student-dashboard";
    $page_title = "My Dashboard";
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<div class="page-container">
  <div class="page-content">
    <section class="content-section">
      <h1 class="section-title">Account Status</h1>
      <div class="section-card">
        <!-- Student Details (left) -->
        <div>
          <div class="flex items-center">
            <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Profile" class="w-20 h-20 rounded-full object-cover">
            <div class="flex flex-col h-fit gap-y-[25px] ml-6">
              <div class="flex flex-col">
                <p class="label">Account Level</p>
                <div>
                  <p class="body-text2-semibold text-2xl font-bold text-[#10375B]">Level <?= $userStats['level'] ?></p>
                  <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                    <div class="bg-[#A58618] h-2.5 rounded-full transition-all duration-500" style="width: <?= $userStats['progress_to_next_level'] ?>%"></div>
                  </div>
                  <p class="text-sm text-gray-600 mt-1"><?= $userStats['points'] ?>/<?= $userStats['points_for_next_level'] ?> exp</p>
                </div>
              </div>
              <div class="flex flex-col gap-y-[10px] items-start">
                <p class="label">Proficiency Level</p>
                <div class="proficiency-badge bg-[#10375B] text-white px-4 py-2 rounded-lg">
                  <p class="text-[14px] font-semibold capitalize"><?= htmlspecialchars($userStats['proficiency']) ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Divider -->
        <div class="bg-company_black w-[2px] h-[216px] rounded-[5px]"></div>

        <!-- Recent Program (right) with Resume Program button -->
        <div class="w-[500px] h-auto gap-[25px] flex rounded-r-[10px] overflow-hidden">
          <?php if ($recentProgram): ?>
            <div class="min-w-[217px] flex flex-col gap-y-[25px]">
              <div>
                <p class="label">Recent Program</p>
                <p class="program-name-2 arabic font-bold text-lg"><?= htmlspecialchars($recentProgram['title']) ?></p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                  <div class="bg-green-500 h-2 rounded-full" style="width: <?= $recentProgram['progress'] ?>%"></div>
                </div>
                <p class="text-sm text-gray-600"><?= round($recentProgram['progress'], 1) ?>% Complete</p>
              </div>

              <a href="student-program-view.php?program_id=<?= (int)$recentProgram['programID'] ?>&chapter_id=<?= (int)$recentProgram['last_chapter_id'] ?>">
                <button type="button" class="group btn-gold w-full bg-[#A58618] text-white px-4 py-2 rounded-lg hover:bg-[#8a6f15] transition-colors">
                  <p class="font-medium">Resume Program</p>
                  <i class="ph ph-play text-[24px] group-hover:hidden"></i>
                  <i class="ph-duotone ph-play text-[24px] hidden group-hover:block"></i>
                </button>
              </a>
            </div>
            <img src="../../images/<?= $recentProgram['image'] ?: 'blog-bg.svg' ?>" alt="Program Image" class="object-cover w-32 h-32 rounded-lg">
          <?php else: ?>
            <div class="min-w-[217px] flex flex-col gap-y-[25px] justify-center items-center text-center">
              <i class="ph ph-book text-6xl text-gray-400"></i>
              <p class="text-gray-600">No programs enrolled yet</p>
              <a href="student-programs.php">
                <button class="bg-[#10375B] text-white px-4 py-2 rounded-lg hover:bg-blue-900 transition-colors">Browse Programs</button>
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</div>

<?php include '../../components/footer.php'; ?>
