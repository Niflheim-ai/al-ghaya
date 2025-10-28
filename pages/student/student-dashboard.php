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

    // Check for new OAuth user welcome
    $showWelcomeMessage = false;
    if (isset($_GET['welcome']) && $_GET['welcome'] === 'new_oauth_user' && isset($_SESSION['new_oauth_user'])) {
        $showWelcomeMessage = true;
        unset($_SESSION['new_oauth_user']); // Clear the flag
    }

    // Get user stats and information
    $stmt = $conn->prepare("SELECT * FROM user WHERE userID = ?");
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Get gamification stats
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

    // Get recommended programs (not enrolled, matching proficiency)
    $stmt = $conn->prepare("
        SELECT p.* FROM programs p
        WHERE p.status = 'published' 
        AND p.category = ? 
        AND p.programID NOT IN (SELECT program_id FROM student_program_enrollments WHERE student_id = ?)
        ORDER BY RAND()
        LIMIT 3
    ");
    $stmt->bind_param("si", $user['proficiency'], $studentID);
    $stmt->execute();
    $recommendedPrograms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Daily challenge - random question from enrolled programs
    $dailyChallenge = null;
    $stmt = $conn->prepare("
        SELECT pc.*, p.title as program_title
        FROM program_chapters pc
        JOIN programs p ON pc.programID = p.programID
        JOIN student_program_enrollments spe ON p.programID = spe.program_id
        WHERE spe.student_id = ? AND pc.question IS NOT NULL AND pc.question != ''
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $dailyChallenge = $stmt->get_result()->fetch_assoc();

    // Award daily login points (check if already awarded today)
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT id FROM point_transactions 
        WHERE userID = ? AND activity_type = 'daily_login' AND DATE(dateCreated) = ?
    ");
    $stmt->bind_param("is", $studentID, $today);
    $stmt->execute();
    $dailyLoginAwarded = $stmt->get_result()->num_rows > 0;

    if (!$dailyLoginAwarded) {
        $gamification->awardPoints($studentID, PointValues::LOGIN_DAILY, 'daily_login', 'Daily login bonus');
    }

    // Set user session variables for display
    if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_email']) || !isset($_SESSION['user_avatar'])) {
        $_SESSION['user_name'] = trim($user['fname'] . ' ' . $user['lname']) ?: 'User';
        $_SESSION['user_email'] = $user['email'] ?? '';
        
        // Set avatar based on gender
        if ($user['gender'] === 'male') {
            $_SESSION['user_avatar'] = '../../images/male.svg';
        } elseif ($user['gender'] === 'female') {
            $_SESSION['user_avatar'] = '../../images/female.svg';
        } else {
            $_SESSION['user_avatar'] = '../../images/dashboard-profile-male.svg';
        }
    }

    $userName = $_SESSION['user_name'];
    $userEmail = $_SESSION['user_email'];
    $userAvatar = $_SESSION['user_avatar'];
    $current_page = "student-dashboard";
    $page_title = "My Dashboard";
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<!-- Enhanced Student Dashboard -->
<div class="page-container">
    <div class="page-content">
        <!-- Welcome Section with Points Animation -->
        <?php if (!$dailyLoginAwarded): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" id="daily-bonus">
            <div class="flex items-center">
                <i class="ph ph-trophy text-2xl mr-3"></i>
                <div>
                    <strong>Daily Bonus Earned!</strong>
                    <p>You've earned <?= PointValues::LOGIN_DAILY ?> points for logging in today! 🎉</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Account Status Section -->
        <section class="content-section">
            <h1 class="section-title">Account Status</h1>
            <div class="section-card">
                <!-- Student Details -->
                <div>
                    <div class="flex items-center">
                        <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Profile" class="w-20 h-20 rounded-full object-cover">
                        <div class="flex flex-col h-fit gap-y-[25px] ml-6">
                            <div class="flex flex-col">
                                <p class="label">Account Level</p>
                                <div>
                                    <p class="body-text2-semibold text-2xl font-bold text-[#10375B]">Level <?= $userStats['level'] ?></p>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                        <div class="bg-[#A58618] h-2.5 rounded-full transition-all duration-500" 
                                             style="width: <?= $userStats['progress_to_next_level'] ?>%"></div>
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
                
                <!-- Recent Program -->
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
                        
                        <!-- Resume Program BTN -->
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
                            <button class="bg-[#10375B] text-white px-4 py-2 rounded-lg hover:bg-blue-900 transition-colors">
                                Browse Programs
                            </button>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Your Progress Section -->
        <section class="content-section">
            <h1 class="section-title">Your Progress</h1>
            <div class="section-card grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Points -->
                <div class="text-center">
                    <div class="bg-blue-100 p-4 rounded-lg">
                        <i class="ph ph-star text-4xl text-blue-600 mb-2"></i>
                        <h3 class="text-xl font-bold text-blue-600"><?= $userStats['points'] ?></h3>
                        <p class="text-gray-600">Total Points</p>
                    </div>
                </div>
                
                <!-- Level -->
                <div class="text-center">
                    <div class="bg-yellow-100 p-4 rounded-lg">
                        <i class="ph ph-trophy text-4xl text-yellow-600 mb-2"></i>
                        <h3 class="text-xl font-bold text-yellow-600"><?= $userStats['level'] ?></h3>
                        <p class="text-gray-600">Current Level</p>
                    </div>
                </div>
                
                <!-- Achievements -->
                <div class="text-center">
                    <div class="bg-green-100 p-4 rounded-lg">
                        <i class="ph ph-medal text-4xl text-green-600 mb-2"></i>
                        <h3 class="text-xl font-bold text-green-600"><?= count($achievements) ?></h3>
                        <p class="text-gray-600">Achievements</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Daily Challenge -->
        <?php if ($dailyChallenge): ?>
        <section class="content-section">
            <h1 class="section-title">Daily Challenge</h1>
            <div class="section-card">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-6 rounded-lg">
                    <div class="flex items-center mb-4">
                        <i class="ph ph-lightning text-3xl mr-3"></i>
                        <div>
                            <h3 class="text-xl font-bold">Challenge from <?= htmlspecialchars($dailyChallenge['program_title']) ?></h3>
                            <p class="opacity-90">Complete today's challenge to earn bonus points!</p>
                        </div>
                    </div>
                    <div class="bg-white/20 p-4 rounded-lg">
                        <p class="mb-4"><?= nl2br(htmlspecialchars(substr($dailyChallenge['question'], 0, 200))) ?>...</p>
                        <button class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                            Take Challenge
                        </button>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Recommended Programs -->
        <?php if (!empty($recommendedPrograms)): ?>
        <section class="content-section">
            <h1 class="section-title">Recommended for You</h1>
            <div class="section-card">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($recommendedPrograms as $program): ?>
                    <a href="student-program-view.php?program_id=<?= $program['programID'] ?>" class="block">
                        <div class="bg-white border rounded-lg p-4 hover:shadow-lg transition-shadow">
                            <img src="../../images/<?= $program['image'] ?: 'blog-bg.svg' ?>" alt="Program" class="w-full h-32 object-cover rounded-lg mb-3">
                            <h4 class="font-semibold mb-2"><?= htmlspecialchars($program['title']) ?></h4>
                            <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars(substr($program['description'], 0, 100)) ?>...</p>
                            <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                <?= htmlspecialchars(ucfirst($program['category'])) ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Recent Activity -->
        <?php if (!empty($recentTransactions)): ?>
        <section class="content-section">
            <h1 class="section-title">Recent Activity</h1>
            <div class="section-card">
                <div class="space-y-4">
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-2 rounded-full mr-3">
                                <i class="ph ph-plus text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($transaction['description']) ?></p>
                                <p class="text-sm text-gray-600"><?= date('M d, Y', strtotime($transaction['dateCreated'])) ?></p>
                            </div>
                        </div>
                        <span class="text-green-600 font-bold">+<?= $transaction['points'] ?> pts</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<!-- SweetAlert for Welcome Message -->
<?php if ($showWelcomeMessage): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Welcome to Al-Ghaya!',
        text: 'Your account has been successfully created. Start your learning journey today!',
        icon: 'success',
        confirmButtonText: 'Get Started',
        confirmButtonColor: '#10375B'
    });
});
</script>
<?php endif; ?>

<!-- Points Animation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dailyBonus = document.getElementById('daily-bonus');
    if (dailyBonus) {
        setTimeout(() => {
            dailyBonus.style.opacity = '0';
            dailyBonus.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                dailyBonus.style.display = 'none';
            }, 500);
        }, 5000); // Hide after 5 seconds
    }
});
</script>

<!-- Back to Top button -->
<button type="button" onclick="scrollToTop()"
    class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer"
    id="scroll-to-top">
    <img src="https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png"
        class="w-10 h-10 rounded-full bg-white" alt="">
</button>

<?php include '../../components/footer.php'; ?>

<!-- Script paths fixed -->
<script src="../../dist/javascript/scroll-to-top.js"></script>
<script src="../../dist/javascript/carousel.js"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../dist/javascript/translate.js"></script>

</body>

</html>