<?php
    session_start();
    require '../../php/dbConnection.php';
    require '../../php/gamification.php';
    require '../../php/functions.php';
    require_once '../../php/achievement-handler.php';
    require_once '../../php/daily-challenge.php';
    require_once '../../php/student-progress.php';

    // Check if user is logged in and is a student
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
        header("Location: ../login.php");
        exit();
    }

    $studentID = $_SESSION['userID'];
    $gamification = new GamificationSystem($conn);

    // After updating points
    $handler = new AchievementHandler($conn, $studentID);
    $handler->checkPointsAchievements();

    // Check for new OAuth user welcome
    $showWelcomeMessage = false;
    if (isset($_GET['welcome']) && $_GET['welcome'] === 'new_oauth_user' && isset($_SESSION['new_oauth_user'])) {
        $showWelcomeMessage = true;
        unset($_SESSION['new_oauth_user']); // Clear the flag
    }

    // Handle challenge submission FIRST (before getting challenge)
    $challengeResult = null;
    $challengeMessage = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_challenge'])) {
        $question_id = intval($_POST['question_id'] ?? 0);
        $user_answer = $_POST['daily-challenge'] ?? '';
        
        $result = submitDailyChallenge($conn, $studentID, $question_id, $user_answer);
        if ($result['success']) {
            $challengeResult = $result['is_correct'] ? 'correct' : 'incorrect';
            $challengeMessage = $result['message'];
        }
    }

    // Get daily challenge
    $dailyChallenge = getDailyChallenge($conn, $studentID);

    // Get recommended programs
    $recommendedPrograms = getRecommendedPrograms($conn, $studentID, 6);

    // Get user stats and information
    $stmt = $conn->prepare("SELECT * FROM user WHERE userID = ?");
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Get gamification stats
    $userStats = $gamification->getUserStats($studentID);
    $recentTransactions = $gamification->getRecentTransactions($studentID, 5);
    $achievements = $gamification->getUserAchievements($studentID);

    // Get recent program progress - Fixed to use actual tables
    $stmt = $conn->prepare(" 
        SELECT p.programID, p.title, 
            COALESCE(p.thumbnail, p.image) as image,
            spe.completion_percentage AS progress,
            spe.last_accessed,
            (SELECT COUNT(DISTINCT cs.story_id)
                FROM program_chapters pc
                INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
                WHERE pc.programID = p.programID) AS total_stories,
            (SELECT COUNT(DISTINCT ssp.story_id)
                FROM program_chapters pc
                INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
                INNER JOIN student_story_progress ssp ON cs.story_id = ssp.story_id
                WHERE pc.programID = p.programID AND ssp.student_id = spe.student_id AND ssp.is_completed = 1) AS completed_stories,
            (SELECT MIN(cs.story_id)
                FROM program_chapters pc
                INNER JOIN chapter_stories cs ON pc.chapter_id = cs.chapter_id
                LEFT JOIN student_story_progress ssp ON cs.story_id = ssp.story_id AND ssp.student_id = spe.student_id
                WHERE pc.programID = p.programID AND (ssp.is_completed IS NULL OR ssp.is_completed = 0)
                ORDER BY pc.chapter_order, cs.story_order
                LIMIT 1) AS next_story_id
        FROM student_program_enrollments spe
        JOIN programs p ON p.programID = spe.program_id 
        WHERE spe.student_id = ?
        ORDER BY spe.last_accessed DESC 
        LIMIT 1
    ");

    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $recentProgram = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $recentProgramID = $recentProgram['programID'];
    $progressPercent = calculateProgramProgress($conn, $studentID, $recentProgramID);

    // Add readable chapter info if program exists
    if ($recentProgram) {
        // Calculate actual progress percentage
        if ($recentProgram['total_stories'] > 0) {
            $actualProgress = ($recentProgram['completed_stories'] / $recentProgram['total_stories']) * 100;
            $recentProgram['progress'] = round($actualProgress, 1);
        }
        
        // Get current chapter info
        if ($recentProgram['next_story_id']) {
            $chapterStmt = $conn->prepare("
                SELECT pc.chapter_id, pc.title as chapter_title, pc.chapter_order
                FROM chapter_stories cs
                INNER JOIN program_chapters pc ON cs.chapter_id = pc.chapter_id
                WHERE cs.story_id = ?
            ");
            $chapterStmt->bind_param("i", $recentProgram['next_story_id']);
            $chapterStmt->execute();
            $chapterInfo = $chapterStmt->get_result()->fetch_assoc();
            $chapterStmt->close();
            
            if ($chapterInfo) {
                $recentProgram['current_chapter'] = $chapterInfo['chapter_order'];
                $recentProgram['current_chapter_title'] = $chapterInfo['chapter_title'];
                $recentProgram['chapter_id'] = $chapterInfo['chapter_id'];
            }
        }
        
        // If no next story (all complete), show last chapter
        if (!isset($recentProgram['current_chapter'])) {
            $lastChapterStmt = $conn->prepare("
                SELECT chapter_id, title, chapter_order
                FROM program_chapters
                WHERE programID = ?
                ORDER BY chapter_order DESC
                LIMIT 1
            ");
            $lastChapterStmt->bind_param("i", $recentProgram['programID']);
            $lastChapterStmt->execute();
            $lastChapter = $lastChapterStmt->get_result()->fetch_assoc();
            $lastChapterStmt->close();
            
            if ($lastChapter) {
                $recentProgram['current_chapter'] = $lastChapter['chapter_order'];
                $recentProgram['current_chapter_title'] = $lastChapter['title'] . ' (Completed)';
                $recentProgram['chapter_id'] = $lastChapter['chapter_id'];
            }
        }
    }

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
                    <p>You've earned <?= PointValues::LOGIN_DAILY ?> points for logging in today!</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Account Status Section -->
        <section class="content-section py-6 px-2">
            <h1 class="section-title mb-4">Account Status</h1>
            <div class="section-card flex flex-col lg:flex-row gap-2 md:gap-4 lg:gap-0 items-stretch rounded-lg bg-white shadow">

                <!-- Student Details -->
                <div class="flex flex-col md:flex-row items-center lg:items-start py-6 px-4 gap-6">
                    <img src="<?= htmlspecialchars($userAvatar) ?>" alt="Profile" class="w-30 h-30 rounded-full object-cover mx-auto md:mx-0">
                    <div class="flex flex-col h-fit gap-y-6 md:gap-y-4 w-full">
                        <div>
                            <p class="label mb-1">Account Level</p>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="body-text2-semibold text-2xl font-bold text-[#10375B] notranslate">Level <?= $userStats['level'] ?></p>
                                    <div class="relative group">
                                        <button class="flex items-center justify-center w-5 h-5 rounded-full bg-gray-300 hover:bg-gray-400 transition-colors duration-200">
                                            <i class="ph ph-question text-[14px] text-gray-600"></i>
                                        </button>
                                        <!-- Tooltip -->
                                        <div class="absolute left-full ml-2 top-1/2 -translate-y-1/2 w-64 bg-gray-900 text-white text-sm rounded-lg p-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 shadow-lg">
                                            <div class="space-y-2">
                                                <p class="font-semibold border-b border-gray-700 pb-1">Earn Points:</p>
                                                <p>📚 Complete a program: <strong>50 pts</strong></p>
                                                <p>✅ Complete Daily Challenges: <strong>10 pts</strong></p>
                                                <p>📅 Daily login: <strong>10 pts</strong></p>
                                            </div>
                                            <div class="absolute right-full top-1/2 -translate-y-1/2 border-8 border-transparent border-r-gray-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                    <div class="bg-[#A58618] h-2.5 rounded-full transition-all duration-500" style="width: <?= $userStats['progress_to_next_level'] ?>%"></div>
                                </div>
                                <p class="text-sm text-gray-600 mt-1"><?= $userStats['points'] ?>/<?= $userStats['points_for_next_level'] ?> points</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Responsive Divider -->
                <div class="flex-shrink-0 flex justify-center items-center px-2 w-full lg:w-auto">
                    <!-- Vertical divider on desktop -->
                    <div class="hidden lg:block bg-company_black w-[2px] h-[216px] rounded-[5px]"></div>
                    <!-- Horizontal divider on mobile -->
                    <div class="block lg:hidden bg-company_black h-[2px] w-full rounded-[5px] my-5"></div>
                </div>

                <!-- Recent Program -->
                <div class="flex-1 flex flex-col lg:flex-row gap-4 py-4 px-4">
                    <?php if ($recentProgram): ?>
                    <div class="flex flex-col gap-y-6 justify-between flex-1">
                        <div>
                            <p class="label">Recent Program</p>
                            <p class="program-name-2 font-bold text-lg notranslate"><?= htmlspecialchars($recentProgram['title']) ?></p>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: <?= $progressPercent ?>%"></div>
                            </div>
                            <p class="text-sm text-gray-600"><?= round($progressPercent, 1) ?>% Complete</p>
                        </div>

                        <!-- Current Chapter -->
                        <div class="w-full h-auto flex border-[1px] border-primary p-[15px] gap-[15px] items-start rounded-lg mt-2">
                            <i class="ph ph-book-bookmark text-[24px] text-[#10375B]"></i>
                            <div>
                                <p class="font-semibold">Chapter <?= $recentProgram['current_chapter'] ?></p>
                                <p class="text-sm text-gray-600 notranslate"><?= htmlspecialchars($recentProgram['current_chapter_title'] ?: 'Continue Learning') ?></p>
                            </div>
                        </div>

                        <!-- Resume Program BTN -->
                        <div class="mt-2">
                            <a href="student-program-view.php?program_id=<?= (int)$recentProgram['programID'] ?><?= isset($recentProgram['next_story_id']) ? '&story_id=' . (int)$recentProgram['next_story_id'] : '' ?>">
                                <button type="button" class="group btn-gold w-full bg-[#A58618] text-white px-4 py-2 rounded-lg hover:bg-[#8a6f15] transition-colors flex justify-center items-center gap-2">
                                    <span class="font-medium">Resume Program</span>
                                    <i class="ph ph-bookmark text-[24px] group-hover:hidden"></i>
                                    <i class="ph-duotone ph-bookmark text-[24px] hidden group-hover:block"></i>
                                </button>
                            </a>
                        </div>
                    </div>

                    <!-- Image: stacked below content on mobile, right side on desktop -->
                    <div class="w-full sm:w-48 lg:w-48 h-48 sm:h-full min-h-[180px] lg:min-h-[216px] flex-shrink-0 mx-auto mt-6 lg:mt-0">
                        <img src="../../uploads/thumbnails/<?= htmlspecialchars($recentProgram['image'] ?: $recentProgram['thumbnail'] ?: 'default.jpg') ?>"
                            alt="<?= htmlspecialchars($recentProgram['title']) ?>"
                            class="w-full h-full object-cover rounded-lg"
                            onerror="this.src='../../images/blog-bg.svg'">
                    </div>
                    <?php else: ?>
                    <div class="flex flex-col gap-y-6 justify-center items-center text-center py-8 w-full">
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

        <!-- Gamification Stats Section -->
        <section class="content-section">
            <h1 class="section-title">Your Progress</h1>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Points Card -->
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <i class="ph ph-trophy text-3xl text-[#A58618] mr-3"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Total Points</h3>
                            <p class="text-2xl font-bold text-[#A58618]"><?= number_format($userStats['points']) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Level Card -->
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <i class="ph ph-star text-3xl text-[#10375B] mr-3"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Current Level</h3>
                            <p class="text-2xl font-bold text-[#10375B]">Level <?= $userStats['level'] ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Achievements Card -->
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <i class="ph ph-medal text-3xl text-green-600 mr-3"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">Achievements</h3>
                            <p class="text-2xl font-bold text-green-600"><?= count($achievements) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Daily Challenge Section -->
        <?php if ($dailyChallenge && !$dailyChallenge['attempted']): ?>
            <section class="content-section py-4 px-2">
                <h1 class="section-title mb-2">Daily Challenge</h1>
                <?php if (isset($dailyChallenge['debug_mode']) && $dailyChallenge['debug_mode']): ?>
                <span class="bg-yellow-500 text-white px-3 py-1 rounded-lg text-sm font-semibold inline-block mb-2">
                    🔧 DEBUG MODE - Unlimited Retries
                </span>
                <?php endif; ?>
                <div class="section-card flex flex-col lg:flex-row gap-4 lg:gap-6 items-stretch rounded-lg bg-white shadow p-4">
                    <!-- Challenge Program Details -->
                    <div class="flex flex-col gap-y-2 min-w-[217px] justify-center flex-1">
                        <p class="label">Challenge Program</p>
                        <p class="program-name-2 arabic font-bold notranslate"><?= htmlspecialchars($dailyChallenge['program_title']) ?></p>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="ph ph-trophy mr-2"></i>
                            <span>Get the correct answer and earn 10 points</span>
                        </div>
                    </div>
                    <!-- Responsive Divider -->
                    <div class="flex-shrink-0 flex justify-center items-center px-2 w-full lg:w-auto">
                        <div class="hidden lg:block bg-company_black w-[2px] h-[216px] rounded-[5px]"></div>
                        <div class="block lg:hidden bg-company_black h-[2px] w-full rounded-[5px] my-5"></div>
                    </div>
                    <!-- Challenge Question/Form -->
                    <div class="flex-1 flex flex-col justify-center">
                        <form method="POST" class="flex flex-col gap-y-6" id="challenge-form">
                            <input type="hidden" name="question_id" value="<?= $dailyChallenge['question_id'] ?>">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="font-semibold text-lg mb-4"><?= htmlspecialchars($dailyChallenge['question']) ?></p>
                            </div>
                            <?php if ($dailyChallenge['options']): ?>
                                <?php $options = json_decode($dailyChallenge['options'], true); ?>
                                <?php if ($options && is_array($options)): ?>
                                    <div class="answers flex flex-col gap-y-3">
                                        <?php foreach ($options as $index => $option): ?>
                                        <div class="flex items-center">
                                            <input type="radio" id="option<?= $index ?>" name="daily-challenge" value="<?= htmlspecialchars($option) ?>"
                                                class="accent-secondary duration-300 ease-in-out peer mr-3" required>
                                            <label for="option<?= $index ?>"
                                                class="flex flex-grow items-center cursor-pointer p-4 rounded-[15px] bg-gray-100 text-gray-800
                                                peer-checked:bg-[#10375B]/20 peer-checked:text-[#10375B] transition-colors duration-300 ease-in-out">
                                                <?= htmlspecialchars($option) ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="answers">
                                    <input type="text" name="daily-challenge" placeholder="Type your answer here..." required
                                        class="w-full p-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#10375B]">
                                </div>
                            <?php endif; ?>
                            <button type="submit" name="submit_challenge"
                                    class="btn-secondary bg-[#10375B] text-white px-6 py-3 rounded-lg hover:bg-blue-900 transition-colors w-full sm:w-auto">
                                Submit Answer
                            </button>
                        </form>
                        <?php if (isset($challengeResult)): ?>
                        <div class="mt-4 p-4 rounded-lg flex items-center justify-center <?= $challengeResult === 'correct' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' ?>">
                            <i class="ph <?= $challengeResult === 'correct' ? 'ph-check-circle' : 'ph-x-circle' ?> text-2xl mr-3"></i>
                            <span><?= htmlspecialchars($challengeMessage) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php elseif ($dailyChallenge && $dailyChallenge['attempted']): ?>
            <section class="content-section py-4 px-2">
                <h1 class="section-title mb-2">Daily Challenge</h1>
                <div class="section-card p-6 text-center flex flex-col justify-center items-center rounded-lg bg-white shadow">
                    <i class="ph <?= $dailyChallenge['is_correct'] ? 'ph-check-circle text-green-600' : 'ph-x-circle text-red-600' ?> text-6xl mb-4"></i>
                    <h3 class="font-bold text-xl mb-2">You've completed today's challenge!</h3>
                    <p class="text-gray-600 mb-4">
                        <?= $dailyChallenge['is_correct'] ?
                            "Great job! You earned {$dailyChallenge['points_awarded']} points!" :
                            "Not quite right. No points awarded, come back tomorrow for a new challenge!"
                        ?>
                    </p>
                    <p class="text-sm text-gray-500">Next challenge available tomorrow</p>
                </div>
            </section>
        <?php endif; ?>

        <!-- Recommended Programs Section -->
        <section class="content-section">
            <h1 class="section-title">Recommended Programs</h1>
            <?php if ($recommendedPrograms): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($recommendedPrograms as $program): ?>
                <?php
                // Determine image path
                $programImage = '../../images/blog-bg.svg'; // Default fallback
                if (!empty($program['image'])) {
                    // Check if it's a thumbnail path or full image path
                    if (strpos($program['image'], 'thumbnails/') !== false || strpos($program['image'], 'uploads/') !== false) {
                        $programImage = '../../' . $program['image'];
                    } else {
                        $programImage = '../../uploads/thumbnails/' . $program['image'];
                    }
                }
                ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:shadow-lg transition-shadow">
                    <img src="<?= htmlspecialchars($programImage) ?>" 
                        alt="<?= htmlspecialchars($program['title']) ?>" 
                        class="w-full h-48 object-cover"
                        onerror="this.src='../../images/blog-bg.svg'">
                    <div class="p-6">
                        <h3 class="font-bold text-lg mb-2 text-gray-800"><?= htmlspecialchars($program['title']) ?></h3>
                        <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars(mb_substr($program['description'], 0, 100)) ?>...</p>
                        <div class="flex justify-between items-center">
                            <span class="text-sm bg-[#10375B] text-white px-2 py-1 rounded capitalize"><?= $program['category'] ?></span>
                            <a href="student-program-view.php?program_id=<?= $program['programID'] ?>">
                                <button class="bg-[#A58618] text-white px-4 py-2 rounded hover:bg-[#8a6f15] transition-colors">
                                    View Program
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <i class="ph ph-graduation-cap text-6xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 mb-4">You're enrolled in all available programs!</p>
                <p class="text-sm text-gray-500">Check back later for new programs.</p>
            </div>
            <?php endif; ?>
        </section>

        <!-- Recent Activity Section -->
        <?php if ($recentTransactions): ?>
        <section class="content-section">
            <h1 class="section-title">Recent Activity</h1>
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <?php foreach ($recentTransactions as $transaction): ?>
                <div class="flex items-center justify-between p-4 border-b border-gray-100 last:border-b-0">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-[#A58618] rounded-full flex items-center justify-center text-white font-bold mr-3">
                            +<?= $transaction['points'] ?>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($transaction['description']) ?></p>
                            <p class="text-sm text-gray-600"><?= date('M j, Y g:i A', strtotime($transaction['dateCreated'])) ?></p>
                        </div>
                    </div>
                    <span class="text-[#A58618] font-semibold">+<?= $transaction['points'] ?> pts</span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<!-- Include SweetAlert2 for welcome message -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript for interactive elements -->
<script>
// Show welcome message for new OAuth users
<?php if ($showWelcomeMessage): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Welcome to Al-Ghaya! 🎉',
        html: `
            <div class="text-left space-y-4">
                <p><strong>Your account has been created successfully!</strong></p>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-blue-800 mb-2">🎓 Getting Started:</p>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>✓ Your account is set up as a Student</li>
                        <li>✓ You start at Level 1 with 0 points</li>
                        <li>✓ Browse programs to begin learning</li>
                        <li>✓ Earn points and achievements as you progress</li>
                    </ul>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-green-800 mb-2">🎆 Next Steps:</p>
                    <ul class="text-sm text-green-700 space-y-1">
                        <li>1. Update your profile with your preferences</li>
                        <li>2. Explore our Arabic learning programs</li>
                        <li>3. Join your first program to start learning</li>
                        <li>4. Complete daily challenges to earn points</li>
                    </ul>
                </div>
                <p class="text-sm text-gray-600">You can update your profile and preferences anytime from the navigation menu.</p>
            </div>
        `,
        icon: 'success',
        confirmButtonText: 'Start Learning!',
        confirmButtonColor: '#2563eb',
        allowOutsideClick: false,
        customClass: {
            popup: 'swal2-popup-welcome',
            title: 'swal2-title-welcome'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Quick Setup',
                text: 'Would you like to update your profile now to personalize your learning experience?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Update Profile',
                cancelButtonText: 'Maybe Later',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280'
            }).then((setupResult) => {
                if (setupResult.isConfirmed) {
                    window.location.href = 'student-profile.php';
                }
            });
        }
    });
});
<?php endif; ?>

// Auto-hide daily bonus notification
setTimeout(() => {
    const dailyBonus = document.getElementById('daily-bonus');
    if (dailyBonus) {
        dailyBonus.style.opacity = '0';
        dailyBonus.style.transition = 'opacity 0.5s';
        setTimeout(() => dailyBonus.remove(), 500);
    }
}, 5000);
</script>

<style>
.swal2-popup-welcome {
    border-radius: 12px !important;
    padding: 2rem !important;
    max-width: 600px !important;
}

.swal2-title-welcome {
    font-size: 1.75rem !important;
    font-weight: 700 !important;
    color: #1f2937 !important;
}
</style>

<!-- Back to Top button -->
<button type="button" onclick="scrollToTop()"
    class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer"
    id="scroll-to-top">
    <img src="https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png"
        class="w-10 h-10 rounded-full bg-white" alt="">
</button>

<?php include '../../components/footer.php'; ?>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<!-- JS -->
<!-- <script src="../../components/navbar.js"></script> -->
<script src="../../dist/javascript/scroll-to-top.js"></script>
<!-- <script src="../../dist/javascript/carousel.js"></script> -->
<!-- <script src="../../dist/javascript/user-dropdown.js"></script> -->
<!-- <script src="../../dist/javascript/translate.js"></script> -->
</body>
</html>
