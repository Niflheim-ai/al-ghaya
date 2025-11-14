<?php
session_start();
require '../../php/dbConnection.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$studentID = (int)$_SESSION['userID'];
$current_page = 'achievements';
$page_title = 'My Achievements';

// Get all achievement definitions
$achievementsStmt = $conn->query("
    SELECT * FROM achievement_definitions 
    WHERE is_active = 1 
    ORDER BY 
        CASE achievement_type
            WHEN 'first_login' THEN 1
            WHEN 'first_program' THEN 2
            WHEN 'program_complete' THEN 3
            WHEN 'chapter_streak_5' THEN 4
            WHEN 'points_100' THEN 5
            WHEN 'points_500' THEN 6
            WHEN 'points_1000' THEN 7
            WHEN 'beginner_graduate' THEN 8
            WHEN 'intermediate_graduate' THEN 9
            WHEN 'advanced_graduate' THEN 10
            WHEN 'level_up' THEN 11
            WHEN 'proficiency_up' THEN 12
            ELSE 13
        END,
        points_required ASC
");

$allAchievements = $achievementsStmt->fetch_all(MYSQLI_ASSOC);

// Get user's unlocked achievements
$unlockedStmt = $conn->prepare("
    SELECT achievement_type, dateUnlocked 
    FROM user_achievements 
    WHERE userID = ?
");
$unlockedStmt->bind_param("i", $studentID);
$unlockedStmt->execute();
$unlockedResult = $unlockedStmt->get_result();
$unlockedAchievements = [];
while ($row = $unlockedResult->fetch_assoc()) {
    $unlockedAchievements[$row['achievement_type']] = $row['dateUnlocked'];
}
$unlockedStmt->close();

// Calculate statistics
$totalAchievements = count($allAchievements);
$unlockedCount = count($unlockedAchievements);
$progressPercentage = $totalAchievements > 0 ? round(($unlockedCount / $totalAchievements) * 100, 1) : 0;

// Get user's current points (if you have a points system)
$pointsStmt = $conn->prepare("SELECT points FROM user WHERE userID = ?");
$pointsStmt->bind_param("i", $studentID);
$pointsStmt->execute();
$pointsResult = $pointsStmt->get_result();
$userPoints = $pointsResult->fetch_assoc()['points'] ?? 0;
$pointsStmt->close();
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    .achievement-card {
        transition: all 0.3s ease;
    }
    .achievement-card:hover {
        transform: translateY(-5px);
    }
    .locked {
        filter: grayscale(100%);
        opacity: 0.5;
    }
    .locked:hover {
        opacity: 0.7;
    }
    .achievement-icon {
        width: 80px;
        height: 80px;
        object-fit: contain;
    }
    .badge-obtained {
        position: absolute;
        top: 10px;
        right: 10px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-size: 10px;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 12px;
        text-transform: uppercase;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
    }
    .lock-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 48px;
        color: #9ca3af;
        opacity: 0.3;
    }
</style>

<div class="page-container">
    <div class="page-content">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">My Achievements</h1>
            <p class="text-gray-600">Unlock achievements by completing programs and reaching milestones</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Achievements -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white bg-opacity-20 rounded-lg p-3">
                        <i class="ph ph-trophy text-3xl text-blue-500"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold mb-1"><?= $unlockedCount ?> / <?= $totalAchievements ?></h3>
                <p class="text-blue-100 text-sm">Achievements Unlocked</p>
            </div>

            <!-- Progress -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white bg-opacity-20 rounded-lg p-3">
                        <i class="ph ph-chart-line text-3xl text-green-500"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold mb-1"><?= $progressPercentage ?>%</h3>
                <p class="text-green-100 text-sm">Completion Progress</p>
            </div>

            <!-- Points -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-white bg-opacity-20 rounded-lg p-3">
                        <i class="ph ph-star text-3xl text-purple-500"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold mb-1"><?= number_format($userPoints) ?></h3>
                <p class="text-purple-100 text-sm">Total Points</p>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-900">Overall Progress</h3>
                <span class="text-sm font-semibold text-blue-600"><?= $unlockedCount ?> / <?= $totalAchievements ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-4 rounded-full transition-all duration-500" style="width: <?= $progressPercentage ?>%"></div>
            </div>
        </div>

        <!-- Achievements Grid -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">All Achievements</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($allAchievements as $achievement): ?>
                    <?php 
                    $isUnlocked = isset($unlockedAchievements[$achievement['achievement_type']]);
                    $dateUnlocked = $isUnlocked ? $unlockedAchievements[$achievement['achievement_type']] : null;
                    ?>
                    
                    <div class="achievement-card bg-white rounded-xl shadow-md p-6 border-2 <?= $isUnlocked ? 'border-green-500' : 'border-gray-200' ?> relative <?= !$isUnlocked ? 'locked' : '' ?>">
                        
                        <!-- Obtained Badge -->
                        <?php if ($isUnlocked): ?>
                            <div class="badge-obtained">
                                <i class="ph ph-check-circle"></i> Obtained
                            </div>
                        <?php endif; ?>

                        <!-- Lock Icon for Locked Achievements -->
                        <?php if (!$isUnlocked): ?>
                            <div class="lock-overlay">
                                <i class="ph ph-lock"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Achievement Icon -->
                        <div class="flex justify-center mb-4">
                            <?php if (!empty($achievement['icon'])): ?>
                                <img src="../../images/achievements/<?= htmlspecialchars($achievement['icon']) ?>" 
                                     alt="<?= htmlspecialchars($achievement['name']) ?>"
                                     class="achievement-icon">
                            <?php else: ?>
                                <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="ph ph-trophy text-4xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Achievement Name -->
                        <h3 class="text-lg font-bold text-gray-900 text-center mb-2">
                            <?= htmlspecialchars($achievement['name']) ?>
                        </h3>

                        <!-- Achievement Description -->
                        <p class="text-sm text-gray-600 text-center mb-3">
                            <?= htmlspecialchars($achievement['description']) ?>
                        </p>

                        <!-- Points Required (if applicable) -->
                        <?php if ($achievement['points_required']): ?>
                            <div class="flex items-center justify-center gap-2 mb-3">
                                <i class="ph ph-star text-yellow-500"></i>
                                <span class="text-sm font-semibold text-gray-700">
                                    <?= number_format($achievement['points_required']) ?> points
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Unlock Date -->
                        <?php if ($isUnlocked && $dateUnlocked): ?>
                            <div class="text-center pt-3 border-t border-gray-200">
                                <p class="text-xs text-gray-500">
                                    <i class="ph ph-calendar"></i> 
                                    Unlocked on <?= date('M j, Y', strtotime($dateUnlocked)) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php endforeach; ?>
            </div>

            <?php if (empty($allAchievements)): ?>
                <div class="text-center py-12">
                    <i class="ph ph-trophy text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No Achievements Available</h3>
                    <p class="text-gray-500">Check back later for new achievements!</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../../components/footer.php'; ?>