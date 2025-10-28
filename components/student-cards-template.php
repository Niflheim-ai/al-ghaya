<?php
require '../../php/dbConnection.php';
require_once '../../php/functions.php';

// Determine which programs to fetch based on the active tab and filters
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'my';
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$programs = [];
$studentId = $_SESSION['userID'] ?? 0;

if ($activeTab === 'my') {
    $programs = fetchEnrolledPrograms($conn, $studentId, $difficulty, $status, $search);
} else {
    // All Programs: must EXCLUDE any programs the student is enrolled in
    $programs = fetchPublishedPrograms($conn, $studentId, $difficulty, $status, $search);
    // Filter out enrolled ones defensively in case function returns them
    $programs = array_values(array_filter($programs, function($p){
        return !isset($p['enrollment_status']) || $p['enrollment_status'] === 'not-enrolled';
    }));
}

// Helper: compute enrollees count per program (batch-friendly)
function getEnrolleeCounts($conn, $programIds) {
    if (empty($programIds)) return [];
    $placeholders = implode(',', array_fill(0, count($programIds), '?'));
    $types = str_repeat('i', count($programIds));
    $sql = "SELECT program_id, COUNT(*) AS cnt FROM student_program_enrollments WHERE program_id IN ($placeholders) GROUP BY program_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$programIds);
    $stmt->execute();
    $res = $stmt->get_result();
    $counts = [];
    while ($row = $res->fetch_assoc()) { $counts[(int)$row['program_id']] = (int)$row['cnt']; }
    return $counts;
}

// Currency symbol map
function currency_symbol($code){
    $map = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 'CNY' => '¥', 'KRW' => '₩',
        'INR' => '₹', 'PHP' => '₱', 'AUD' => 'A$', 'CAD' => 'C$', 'SGD' => 'S$', 'HKD' => 'HK$'
    ];
    $uc = strtoupper($code ?: 'PHP');
    return $map[$uc] ?? '';
}

// Collect program IDs and get enrollee counts in one query
$programIds = array_map(fn($p) => (int)$p['programID'], $programs);
$enrolleeCounts = getEnrolleeCounts($conn, $programIds);
?>

<?php if (empty($programs)): ?>
    <div class="w-full text-center py-8">
        <p class="text-gray-500">
            <?= $activeTab === 'my' ? 'No enrolled programs found.' : 'No available programs found.' ?>
        </p>
    </div>
<?php else: ?>
    <?php foreach ($programs as $program): ?>
        <?php
            $pid = (int)$program['programID'];
            $price = isset($program['price']) ? (float)$program['price'] : 0.0;
            $currency = isset($program['currency']) && $program['currency'] !== '' ? $program['currency'] : 'PHP';
            $enrollees = $enrolleeCounts[$pid] ?? 0;
            $symbol = currency_symbol($currency);
            $isMyTab = ($activeTab === 'my');
            $completion = isset($program['completion_percentage']) ? (float)$program['completion_percentage'] : 0.0;
            $isInProgress = $isMyTab && $completion > 0 && $completion < 100;
            $isCompleted = $isMyTab && $completion >= 100;
        ?>
        <a href="student-program-view.php?program_id=<?= $pid ?>" class="block">
            <div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-white border border-gray-200 mb-4 hover:shadow-lg transition-shadow duration-300 relative">
                <!-- Resume indicator (My Programs only) -->
                <?php if ($isInProgress): ?>
                    <div class="absolute top-3 right-3 bg-[#10375B] text-white text-xs font-semibold px-3 py-1 rounded-full shadow">
                        Resume
                    </div>
                <?php endif; ?>
                <?php if ($isCompleted): ?>
                    <div class="absolute top-3 right-3 bg-green-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">
                        Completed
                    </div>
                <?php endif; ?>
                <div class="w-full overflow-hidden rounded-[20px] flex flex-wrap">
                    <!-- Image -->
                    <img src="<?= !empty($program['image']) ? '../../uploads/program_thumbnails/'.htmlspecialchars($program['image']) : '../../images/blog-bg.svg' ?>"
                         alt="Program Image"
                         class="h-auto min-w-[221px] min-h-[170px] object-cover flex-grow flex-shrink-0 basis-1/4">
                    
                    <!-- Content (Right) -->
                    <div class="overflow-hidden p-6 h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-3">
                        <!-- Top line: Price for All Programs; Progress bar for My Programs -->
                        <?php if ($isMyTab): ?>
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">Progress</span>
                                    <span class="text-sm font-semibold text-[#10375B]"><?= number_format($completion, 1) ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-[#A58618] h-2 rounded-full" style="width: <?= max(0, min(100, $completion)) ?>%"></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center justify-between">
                                <span class="text-[#10375B] font-bold text-3xl">
                                    <?= $symbol ? htmlspecialchars($symbol) : htmlspecialchars(strtoupper($currency)).' ' ?><?= number_format($price, 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Title -->
                        <h3 class="text-xl font-semibold text-gray-900 arabic">
                            <?= htmlspecialchars($program['title']) ?>
                        </h3>

                        <!-- Description -->
                        <div class="text-gray-700 text-sm leading-relaxed">
                            <?= htmlspecialchars(mb_strimwidth($program['description'] ?? '', 0, 220, '...')) ?>
                        </div>

                        <!-- Enrollees count -->
                        <div class="mt-auto flex items-center gap-2 text-gray-600 text-sm">
                            <i class="ph ph-users-three text-[18px]"></i>
                            <span><?= $enrollees ?> enrollees</span>
                        </div>

                        <!-- Difficulty at bottom -->
                        <div class="proficiency-badge mt-2">
                            <i class="ph-fill ph-barbell text-[15px]"></i>
                            <p class="text-[14px]/[2em] font-semibold">
                                <?= htmlspecialchars(ucfirst(strtolower($program['category']))) ?> Difficulty
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>