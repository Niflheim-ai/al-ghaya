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

    // Get recent program progress (Fixed table reference)
    $stmt = $conn->prepare("
        SELECT p.*, 
               spe.completion_percentage as progress, 
               1 as current_chapter, 
               spe.last_accessed as lastAccessedAt,
               'Continue Learning' as current_chapter_title
        FROM programs p 
        JOIN student_program_enrollments spe ON p.programID = spe.program_id 
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

    // Daily challenge - random question from enrolled programs (Fixed table reference)
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

    // Handle daily challenge submission
    if ($_POST['submit_challenge'] ?? false) {
        $selectedAnswer = $_POST['daily-challenge'] ?? '';
        $challengeId = $_POST['challenge_id'] ?? 0;
        
        if ($challengeId && $selectedAnswer) {
            // Get correct answer
            $stmt = $conn->prepare("SELECT correct_answer FROM program_chapters WHERE chapter_id = ?");
            $stmt->bind_param("i", $challengeId);
            $stmt->execute();
            $correctAnswer = $stmt->get_result()->fetch_assoc()['correct_answer'] ?? '';
            
            if (strtolower(trim($selectedAnswer)) === strtolower(trim($correctAnswer))) {
                $gamification->awardPoints($studentID, PointValues::QUIZ_CORRECT, 'daily_challenge', 'Correct daily challenge answer');
                $challengeResult = 'correct';
            } else {
                $challengeResult = 'incorrect';
            }
        }
    }
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
                        
                        <!-- Current Chapter -->
                        <div class="w-full h-auto flex border-[1px] border-primary p-[15px] gap-[15px] items-start rounded-lg">
                            <i class="ph ph-book-bookmark text-[24px] text-[#10375B]"></i>
                            <div>
                                <p class="font-semibold">Chapter <?= $recentProgram['current_chapter'] ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($recentProgram['current_chapter_title'] ?: 'Continue Learning') ?></p>
                            </div>
                        </div>
                        
                        <!-- Resume Program BTN -->
                        <a href="student-program-view.php?id=<?= $recentProgram['programID'] ?>">
                            <button type="button" class="group btn-gold w-full bg-[#A58618] text-white px-4 py-2 rounded-lg hover:bg-[#8a6f15] transition-colors">
                                <p class="font-medium">Resume Program</p>
                                <i class="ph ph-bookmark text-[24px] group-hover:hidden"></i>
                                <i class="ph-duotone ph-bookmark text-[24px] hidden group-hover:block"></i>
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
        <?php if ($dailyChallenge): ?>
        <section class="content-section">
            <h1 class="section-title">Daily Challenge</h1>
            <div class="section-card">
                <div class="flex flex-col gap-y-[10px] min-w-[217px]">
                    <p class="label">Challenge Program</p>
                    <p class="program-name-2 arabic font-bold"><?= htmlspecialchars($dailyChallenge['program_title']) ?></p>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="ph ph-trophy mr-2"></i>
                        <span>Earn <?= PointValues::QUIZ_CORRECT ?> points</span>
                    </div>
                </div>
                
                <!-- Divider -->
                <div class="bg-company_black w-[2px] h-[216px] rounded-[5px]"></div>
                
                <!-- Challenge Question -->
                <form method="POST" class="flex flex-col flex-grow gap-y-[25px]" id="challenge-form">
                    <input type="hidden" name="challenge_id" value="<?= $dailyChallenge['chapter_id'] ?>">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="font-semibold text-lg mb-4"><?= htmlspecialchars($dailyChallenge['question']) ?></p>
                    </div>
                    
                    <?php if ($dailyChallenge['answer_options']): ?>
                        <?php $options = json_decode($dailyChallenge['answer_options'], true); ?>
                        <?php if ($options): ?>
                            <div class="answers flex flex-col gap-y-3">
                                <?php foreach ($options as $index => $option): ?>
                                <div class="flex items-center">
                                    <input type="radio" id="option<?= $index ?>" name="daily-challenge" value="<?= htmlspecialchars($option) ?>"
                                        class="accent-secondary duration-300 ease-in-out peer mr-3">
                                    <label for="option<?= $index ?>"
                                        class="flex flex-grow items-center cursor-pointer p-4 rounded-[15px] bg-gray-100 text-gray-800 peer-checked:bg-[#10375B]/20 peer-checked:text-[#10375B] transition-colors duration-300 ease-in-out">
                                        <?= htmlspecialchars($option) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="answers">
                            <input type="text" name="daily-challenge" placeholder="Type your answer here..." 
                                class="w-full p-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#10375B]">
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="submit_challenge" class="btn-secondary bg-[#10375B] text-white px-6 py-3 rounded-lg hover:bg-blue-900 transition-colors" id="challenge-submit" disabled>
                        Submit Answer
                    </button>
                </form>
            </div>
            
            <?php if (isset($challengeResult)): ?>
            <div class="mt-4 p-4 rounded-lg <?= $challengeResult === 'correct' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' ?>">
                <?php if ($challengeResult === 'correct'): ?>
                    <i class="ph ph-check-circle text-xl mr-2"></i>
                    <strong>Correct!</strong> You earned <?= PointValues::QUIZ_CORRECT ?> points! 🎉
                <?php else: ?>
                    <i class="ph ph-x-circle text-xl mr-2"></i>
                    <strong>Not quite right.</strong> Try again tomorrow!
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Recommended Programs Section -->
        <section class="content-section">
            <h1 class="section-title">Recommended Programs</h1>
            <?php if ($recommendedPrograms): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($recommendedPrograms as $program): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:shadow-lg transition-shadow">
                    <img src="../../images/<?= $program['image'] ?: 'blog-bg.svg' ?>" alt="<?= htmlspecialchars($program['title']) ?>" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="font-bold text-lg mb-2 text-gray-800"><?= htmlspecialchars($program['title']) ?></h3>
                        <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars(substr($program['description'], 0, 100)) ?>...</p>
                        <div class="flex justify-between items-center">
                            <span class="text-sm bg-[#10375B] text-white px-2 py-1 rounded capitalize"><?= $program['category'] ?></span>
                            <a href="../programs.php?title=<?= urlencode($program['title']) ?>&category=<?= urlencode($program['category']) ?>">
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
                <p class="text-gray-600 mb-4">No recommendations available at the moment.</p>
                <a href="student-programs.php">
                    <button class="bg-[#10375B] text-white px-6 py-3 rounded-lg hover:bg-blue-900 transition-colors">
                        Explore All Programs
                    </button>
                </a>
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
            // Show quick tutorial or profile setup prompt
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

// Enable challenge submit button when option is selected
const challengeForm = document.getElementById('challenge-form');
if (challengeForm) {
    const submitBtn = document.getElementById('challenge-submit');
    const inputs = challengeForm.querySelectorAll('input[name="daily-challenge"]');
    
    inputs.forEach(input => {
        input.addEventListener('change', () => {
            submitBtn.disabled = false;
        });
        
        input.addEventListener('input', () => {
            submitBtn.disabled = input.value.trim() === '';
        });
    });
}
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
<script src="../../components/navbar.js"></script>
<script src="../../dist/javascript/scroll-to-top.js"></script>
<script src="../../dist/javascript/carousel.js"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../dist/javascript/translate.js"></script>
</body>
</html>