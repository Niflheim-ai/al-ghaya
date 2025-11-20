<?php
session_start();
require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$currentUserID = intval($_SESSION['userID']);

$current_page = "leaderboard";
$page_title = "Leaderboard";

// Get top 100 students
$leaderboardQuery = "
    SELECT 
        u.userID,
        u.fname,
        u.lname,
        u.profile_picture,
        COALESCE(u.points, 0) as points,
        COALESCE(u.level, 1) as level,
        COUNT(DISTINCT spe.program_id) as programs_enrolled,
        COALESCE(AVG(spe.completion_percentage), 0) as avg_completion
    FROM user u
    LEFT JOIN student_program_enrollments spe ON u.userID = spe.student_id
    WHERE u.role = 'student'
    GROUP BY u.userID, u.fname, u.lname, u.profile_picture, u.points, u.level
    ORDER BY u.points DESC, u.level DESC
    LIMIT 100
";

$leaderboardStmt = $conn->query($leaderboardQuery);
$leaderboard = $leaderboardStmt->fetch_all(MYSQLI_ASSOC);

// Get current user's rank
$currentUserRank = 0;
$currentUserData = null;
foreach ($leaderboard as $index => $student) {
    if ($student['userID'] == $currentUserID) {
        $currentUserRank = $index + 1;
        $currentUserData = $student;
        break;
    }
}

// If user not in top 100, get their rank separately
if (!$currentUserData) {
    $rankQuery = "
        SELECT 
            u.userID,
            u.fname,
            u.lname,
            u.profile_picture,
            COALESCE(u.points, 0) as points,
            COALESCE(u.level, 1) as level,
            COUNT(DISTINCT spe.program_id) as programs_enrolled,
            COALESCE(AVG(spe.completion_percentage), 0) as avg_completion,
            (SELECT COUNT(*) + 1 
             FROM user u2 
             WHERE u2.role = 'student' 
             AND COALESCE(u2.points, 0) > COALESCE(u.points, 0)
            ) as rank
        FROM user u
        LEFT JOIN student_program_enrollments spe ON u.userID = spe.student_id
        WHERE u.userID = ?
        GROUP BY u.userID, u.fname, u.lname, u.profile_picture, u.points, u.level
    ";
    $rankStmt = $conn->prepare($rankQuery);
    $rankStmt->bind_param("i", $currentUserID);
    $rankStmt->execute();
    $currentUserData = $rankStmt->get_result()->fetch_assoc();
    $currentUserRank = $currentUserData['rank'];
}

// Get stats
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'student'")->fetch_assoc()['count'];
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>

<div class="page-container">
    <div class="page-content">
        <section class="content-section">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="section-title flex items-center gap-3">
                    <i class="ph-fill ph-trophy text-yellow-500 text-4xl"></i>
                    Leaderboard
                </h1>
                <p class="text-gray-600 mt-2">Compete with fellow learners and climb to the top!</p>
            </div>

            <!-- Current User Card -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl shadow-2xl p-8 mb-8 text-white notranslate">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <?php
                        $userImg = !empty($currentUserData['profile_picture']) 
                            ? '../../uploads/student_profiles/' . htmlspecialchars($currentUserData['profile_picture'])
                            : '../../images/dashboard-profile-male.svg';
                        ?>
                        <img src="<?= $userImg ?>" alt="Profile" class="w-20 h-20 rounded-full border-4 border-white shadow-lg object-cover">
                        <div>
                            <p class="text-blue-100 text-sm font-medium mb-1">Your Ranking</p>
                            <h2 class="text-3xl font-bold">
                                <?= htmlspecialchars($currentUserData['fname'] . ' ' . $currentUserData['lname']) ?>
                            </h2>
                            <p class="text-blue-100 mt-1">
                                <span class="font-semibold">Rank #<?= number_format($currentUserRank) ?></span> 
                                of <?= number_format($totalStudents) ?> students
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-center">
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                            <div class="text-3xl font-bold"><?= number_format($currentUserData['points']) ?></div>
                            <div class="text-sm text-blue-100 mt-1">Points</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                            <div class="text-3xl font-bold">Lv. <?= $currentUserData['level'] ?></div>
                            <div class="text-sm text-blue-100 mt-1">Level</div>
                        </div>
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4">
                            <div class="text-3xl font-bold"><?= $currentUserData['programs_enrolled'] ?></div>
                            <div class="text-sm text-blue-100 mt-1">Programs</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leaderboard Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b">
                    <h3 class="text-xl font-bold text-gray-900">Top Learners</h3>
                    <p class="text-sm text-gray-600 mt-1">The most dedicated students in Al-Ghaya LMS</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Rank</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Level</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Points</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Programs</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 notranslate">
                            <?php foreach ($leaderboard as $index => $student): ?>
                                <?php
                                $rank = $index + 1;
                                $isCurrentUser = ($student['userID'] == $currentUserID);
                                $rowClass = $isCurrentUser ? 'bg-blue-50 border-l-4 border-blue-500' : 'hover:bg-gray-50';
                                
                                // Medal for top 3
                                $medal = '';
                                if ($rank === 1) $medal = '<i class="ph-fill ph-medal text-yellow-500 text-2xl"></i>';
                                elseif ($rank === 2) $medal = '<i class="ph-fill ph-medal text-gray-400 text-2xl"></i>';
                                elseif ($rank === 3) $medal = '<i class="ph-fill ph-medal text-orange-600 text-2xl"></i>';
                                
                                $studentImg = !empty($student['profile_picture']) 
                                    ? '../../uploads/student_profiles/' . htmlspecialchars($student['profile_picture'])
                                    : '../../images/dashboard-profile-male.svg';
                                ?>
                                <tr class="<?= $rowClass ?> transition-colors">
                                    <!-- Rank -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <?php if ($medal): ?>
                                                <?= $medal ?>
                                            <?php else: ?>
                                                <span class="text-lg font-bold text-gray-700">#<?= $rank ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Student -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img src="<?= $studentImg ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
                                            <div>
                                                <div class="font-semibold text-gray-900">
                                                    <?= htmlspecialchars($student['fname'] . ' ' . $student['lname']) ?>
                                                    <?php if ($isCurrentUser): ?>
                                                        <span class="ml-2 text-xs bg-blue-500 text-white px-2 py-1 rounded-full">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Level -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <i class="ph-fill ph-star text-purple-500"></i>
                                            <span class="text-lg font-bold text-purple-700">Lv. <?= $student['level'] ?></span>
                                        </div>
                                    </td>

                                    <!-- Points -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <i class="ph-fill ph-coin text-yellow-500"></i>
                                            <span class="text-lg font-bold text-gray-900"><?= number_format($student['points']) ?></span>
                                        </div>
                                    </td>

                                    <!-- Programs -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <i class="ph-fill ph-book-open text-yellow-500"></i>
                                        <span class="text-lg font-semibold text-gray-700"><?= $student['programs_enrolled'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($leaderboard) === 100): ?>
                    <div class="bg-gray-50 px-6 py-4 border-t text-center text-sm text-gray-600">
                        <i class="ph ph-info"></i> Showing top 100 students
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </div>
</div>

</body>
</html>