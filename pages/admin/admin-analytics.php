<?php
session_start();
include('../../php/dbConnection.php');
require_once '../../php/config.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') { 
    header('Location: ../login.php'); 
    exit(); 
}

$current_page = "admin-analytics";
$page_title = "Admin Analytics";

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Default: First day of month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Default: Today
$programFilter = $_GET['program'] ?? 'all';
$teacherFilter = $_GET['teacher'] ?? 'all';

// ====== PROGRAM METRICS ======
$statuses = ['draft','pending_review','published','rejected','archived'];
$counts = [];
foreach ($statuses as $s) {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM programs WHERE status = ?");
    $stmt->bind_param('s', $s); 
    $stmt->execute(); 
    $res = $stmt->get_result(); 
    $counts[$s] = (int)($res->fetch_assoc()['c'] ?? 0);
}
$totalPrograms = array_sum($counts);

// ====== USER METRICS ======
$userStmt = $conn->query("
    SELECT 
        SUM(CASE WHEN role='student' THEN 1 ELSE 0 END) as total_students,
        SUM(CASE WHEN role='teacher' THEN 1 ELSE 0 END) as total_teachers,
        SUM(CASE WHEN role='admin' THEN 1 ELSE 0 END) as total_admins
    FROM user
");
$userMetrics = $userStmt->fetch_assoc();

// ====== ENROLLMENT METRICS ======
$enrollmentStmt = $conn->prepare("
    SELECT COUNT(*) as total_enrollments
    FROM student_program_enrollments
    WHERE DATE(enrollment_date) BETWEEN ? AND ?
");
$enrollmentStmt->bind_param("ss", $dateFrom, $dateTo);
$enrollmentStmt->execute();
$enrollmentData = $enrollmentStmt->get_result()->fetch_assoc();
$totalEnrollments = $enrollmentData['total_enrollments'];

// ====== REVENUE METRICS ======
$revenueStmt = $conn->prepare("
    SELECT 
        COUNT(*) as transaction_count,
        COALESCE(SUM(amount), 0) as total_revenue,
        COALESCE(AVG(amount), 0) as avg_transaction
    FROM payment_transactions
    WHERE status = 'paid' AND DATE(datePaid) BETWEEN ? AND ?
");
$revenueStmt->bind_param("ss", $dateFrom, $dateTo);
$revenueStmt->execute();
$revenueData = $revenueStmt->get_result()->fetch_assoc();

// ====== CUMULATIVE ENROLLMENT CHART DATA ======
$chartStmt = $conn->prepare("
    SELECT 
        DATE(enrollment_date) as date,
        COUNT(*) as daily_count
    FROM student_program_enrollments
    WHERE DATE(enrollment_date) BETWEEN ? AND ?
    GROUP BY DATE(enrollment_date)
    ORDER BY date ASC
");
$chartStmt->bind_param("ss", $dateFrom, $dateTo);
$chartStmt->execute();
$chartData = $chartStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate cumulative totals
$cumulativeEnrollments = [];
$runningTotal = 0;
foreach ($chartData as $day) {
    $runningTotal += (int)$day['daily_count'];
    $cumulativeEnrollments[] = [
        'date' => $day['date'],
        'total' => $runningTotal
    ];
}

// ====== CUMULATIVE REVENUE CHART DATA ======
$revenueChartStmt = $conn->prepare("
    SELECT 
        DATE(datePaid) as date,
        SUM(amount) as daily_revenue
    FROM payment_transactions
    WHERE status = 'paid' AND DATE(datePaid) BETWEEN ? AND ?
    GROUP BY DATE(datePaid)
    ORDER BY date ASC
");
$revenueChartStmt->bind_param("ss", $dateFrom, $dateTo);
$revenueChartStmt->execute();
$revenueChartData = $revenueChartStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate cumulative revenue
$cumulativeRevenue = [];
$runningRevenue = 0;
foreach ($revenueChartData as $day) {
    $runningRevenue += (float)$day['daily_revenue'];
    $cumulativeRevenue[] = [
        'date' => $day['date'],
        'total' => $runningRevenue
    ];
}


// ====== TOP TEACHERS ======
$topTeachers = [];
$stmt = $conn->prepare("
    SELECT u.userID, u.fname, u.lname, COUNT(*) as cnt
    FROM programs p
    INNER JOIN teacher t ON p.teacherID = t.teacherID
    INNER JOIN user u ON t.userID = u.userID
    WHERE p.status='published'
    GROUP BY u.userID, u.fname, u.lname
    ORDER BY cnt DESC
    LIMIT 10
");
$stmt->execute(); 
$res = $stmt->get_result(); 
while($row = $res->fetch_assoc()) {
    $topTeachers[] = $row;
}

// ====== TOP PROGRAMS BY ENROLLMENT ======
$topPrograms = [];
$stmt = $conn->prepare("
    SELECT 
        p.programID, 
        p.title, 
        p.price,
        COUNT(spe.enrollment_id) as enrollment_count,
        COALESCE(SUM(pt.amount), 0) as revenue
    FROM programs p
    LEFT JOIN student_program_enrollments spe ON p.programID = spe.program_id
    LEFT JOIN payment_transactions pt ON p.programID = pt.program_id AND pt.status = 'paid'
    WHERE p.status = 'published'
    GROUP BY p.programID, p.title, p.price
    ORDER BY enrollment_count DESC
    LIMIT 10
");
$stmt->execute();
$topPrograms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ====== RECENT TRANSACTIONS ======
$transactions = [];
$transStmt = $conn->prepare("
    SELECT 
        pt.payment_id,
        pt.amount,
        pt.currency,
        pt.datePaid,
        u.fname,
        u.lname,
        u.email,
        prog.title as program_title,
        teach_user.fname as teacher_fname,
        teach_user.lname as teacher_lname
    FROM payment_transactions pt
    INNER JOIN programs prog ON pt.program_id = prog.programID
    INNER JOIN user u ON pt.student_id = u.userID
    INNER JOIN teacher tch ON prog.teacherID = tch.teacherID
    INNER JOIN user teach_user ON tch.userID = teach_user.userID
    WHERE pt.status = 'paid' AND DATE(pt.datePaid) BETWEEN ? AND ?
    ORDER BY pt.datePaid DESC
    LIMIT 50
");
$transStmt->bind_param("ss", $dateFrom, $dateTo);
$transStmt->execute();
$transactions = $transStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ====== DAILY ENROLLMENT CHART DATA ======
$chartStmt = $conn->prepare("
    SELECT 
        DATE(enrollment_date) as date,
        COUNT(*) as count
    FROM student_program_enrollments
    WHERE DATE(enrollment_date) BETWEEN ? AND ?
    GROUP BY DATE(enrollment_date)
    ORDER BY date ASC
");
$chartStmt->bind_param("ss", $dateFrom, $dateTo);
$chartStmt->execute();
$chartData = $chartStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ====== REVENUE BY DAY ======
$revenueChartStmt = $conn->prepare("
    SELECT 
        DATE(datePaid) as date,
        SUM(amount) as revenue,
        COUNT(*) as transactions
    FROM payment_transactions
    WHERE status = 'paid' AND DATE(datePaid) BETWEEN ? AND ?
    GROUP BY DATE(datePaid)
    ORDER BY date ASC
");
$revenueChartStmt->bind_param("ss", $dateFrom, $dateTo);
$revenueChartStmt->execute();
$revenueChartData = $revenueChartStmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>

<div class="page-container">
    <div class="page-content">
        <section class="content-section">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="section-title">Analytics Dashboard</h1>
                <button onclick="downloadFullReport()" class="group btn-primary">
                    <i class="ph ph-download text-[20px]"></i>
                    <p class="font-medium">Export Full Report</p>
                </button>
            </div>

            <!-- Date Range Filter -->
            <form method="GET" class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <i class="ph ph-funnel"></i> Apply Filters
                        </button>
                    </div>
                    <div class="flex items-end">
                        <a href="admin-analytics.php" class="w-full text-center bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <i class="ph ph-x"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Revenue -->
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Total Revenue</p>
                            <h3 class="text-3xl font-bold mt-1">₱<?= number_format($revenueData['total_revenue'], 2) ?></h3>
                            <p class="text-green-100 text-xs mt-1"><?= $revenueData['transaction_count'] ?> transactions</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-lg">
                            <i class="ph-fill ph-currency-circle-dollar text-3xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Enrollments -->
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Total Enrollments</p>
                            <h3 class="text-3xl font-bold mt-1"><?= number_format($totalEnrollments) ?></h3>
                            <p class="text-blue-100 text-xs mt-1">In selected period</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-lg">
                            <i class="ph-fill ph-graduation-cap text-3xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Students -->
                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Total Students</p>
                            <h3 class="text-3xl font-bold mt-1"><?= number_format($userMetrics['total_students']) ?></h3>
                            <p class="text-purple-100 text-xs mt-1">Registered users</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-lg">
                            <i class="ph-fill ph-user text-3xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Programs -->
                <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="text-orange-100 text-sm font-medium">Total Programs</p>
                            <h3 class="text-3xl font-bold mt-1"><?= number_format($totalPrograms) ?></h3>
                            <p class="text-orange-100 text-xs mt-1"><?= $counts['published'] ?> published</p>
                        </div>
                        <div class="bg-white/20 p-3 rounded-lg">
                            <i class="ph-fill ph-books text-3xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Program Status Breakdown -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-gray-200">
                    <i class="ph-duotone ph-file-dashed text-gray-500 text-3xl mb-2"></i>
                    <p class="text-2xl font-bold text-gray-900"><?= $counts['draft'] ?></p>
                    <p class="text-sm text-gray-600">Draft</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-yellow-200">
                    <i class="ph-duotone ph-clock text-yellow-600 text-3xl mb-2"></i>
                    <p class="text-2xl font-bold text-yellow-700"><?= $counts['pending_review'] ?></p>
                    <p class="text-sm text-gray-600">Pending Review</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-green-200">
                    <i class="ph-duotone ph-seal-check text-green-600 text-3xl mb-2"></i>
                    <p class="text-2xl font-bold text-green-700"><?= $counts['published'] ?></p>
                    <p class="text-sm text-gray-600">Published</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-red-200">
                    <i class="ph-duotone ph-prohibit text-red-600 text-3xl mb-2"></i>
                    <p class="text-2xl font-bold text-red-700"><?= $counts['rejected'] ?></p>
                    <p class="text-sm text-gray-600">Rejected</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center border-2 border-gray-200">
                    <i class="ph-duotone ph-archive text-gray-500 text-3xl mb-2"></i>
                    <p class="text-2xl font-bold text-gray-700"><?= $counts['archived'] ?></p>
                    <p class="text-sm text-gray-600">Archived</p>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Enrollment Chart -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-bold mb-4">Total Enrollments Over Time</h2>
                    <div style="position: relative; height: 300px;">
                        <canvas id="enrollmentChart"></canvas>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-lg font-bold mb-4">Total Revenue Over Time</h2>
                    <div style="position: relative; height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Programs Table -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold">Top Programs by Enrollment</h2>
                    <button onclick="downloadTopPrograms()" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        <i class="ph ph-download"></i> Export
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrollments</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($topPrograms as $i => $prog): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-bold text-gray-900"><?= $i + 1 ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($prog['title']) ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700">₱<?= number_format($prog['price'], 2) ?></td>
                                <td class="px-4 py-3 text-sm font-semibold text-blue-600"><?= $prog['enrollment_count'] ?></td>
                                <td class="px-4 py-3 text-sm font-bold text-green-600">₱<?= number_format($prog['revenue'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Teachers Table -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold">Top Teachers by Published Programs</h2>
                    <button onclick="downloadTopTeachers()" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        <i class="ph ph-download"></i> Export
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Published Programs</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($topTeachers as $i => $t): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-bold text-gray-900"><?= $i + 1 ?></td>
                                <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars(trim(($t['fname']??'').' '.($t['lname']??'')) ?: 'Unnamed') ?></td>
                                <td class="px-4 py-3 text-sm font-semibold text-blue-600"><?= $t['cnt'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold">Recent Transactions</h2>
                    <button onclick="downloadTransactions()" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        <i class="ph ph-download"></i> Export
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    <i class="ph ph-receipt text-4xl mb-2"></i>
                                    <p>No transactions found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $trans): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs font-mono text-gray-600">#<?= $trans['payment_id'] ?></td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($trans['fname'] . ' ' . $trans['lname']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($trans['email']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?= htmlspecialchars($trans['program_title']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($trans['teacher_fname'] . ' ' . $trans['teacher_lname']) ?></td>
                                    <td class="px-4 py-3 text-sm font-bold text-green-600">₱<?= number_format($trans['amount'], 2) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?= date('M d, Y g:i A', strtotime($trans['datePaid'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </section>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Total Enrollments Chart
const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
new Chart(enrollmentCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($cumulativeEnrollments, 'date')) ?>,
        datasets: [{
            label: 'Total Enrollments',
            data: <?= json_encode(array_column($cumulativeEnrollments, 'total')) ?>,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Total: ' + context.parsed.y.toLocaleString() + ' enrollments';
                    }
                }
            }
        },
        scales: {
            y: { 
                beginAtZero: true, 
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Total Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($cumulativeRevenue, 'date')) ?>,
        datasets: [{
            label: 'Total Revenue',
            data: <?= json_encode(array_column($cumulativeRevenue, 'total')) ?>,
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Total: ₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Export Functions
function downloadFullReport() {
    const rows = [
        ['Admin Analytics Report'],
        ['Generated:', new Date().toLocaleString()],
        ['Period:', '<?= $dateFrom ?> to <?= $dateTo ?>'],
        [''],
        ['OVERVIEW'],
        ['Total Revenue', '₱<?= number_format($revenueData['total_revenue'], 2) ?>'],
        ['Total Transactions', '<?= $revenueData['transaction_count'] ?>'],
        ['Total Enrollments', '<?= $totalEnrollments ?>'],
        ['Total Students', '<?= $userMetrics['total_students'] ?>'],
        ['Total Teachers', '<?= $userMetrics['total_teachers'] ?>'],
        ['Total Programs', '<?= $totalPrograms ?>'],
        [''],
        ['PROGRAM STATUS'],
        ['Draft', '<?= $counts['draft'] ?>'],
        ['Pending Review', '<?= $counts['pending_review'] ?>'],
        ['Published', '<?= $counts['published'] ?>'],
        ['Rejected', '<?= $counts['rejected'] ?>'],
        ['Archived', '<?= $counts['archived'] ?>']
    ];
    downloadCSV(rows, `admin_analytics_report_${new Date().toISOString().split('T')[0]}.csv`);
}

function downloadTopPrograms() {
    const rows = [
        ['Rank', 'Program', 'Price', 'Enrollments', 'Revenue'],
        <?php foreach ($topPrograms as $i => $p): ?>
        ['<?= $i+1 ?>', '<?= addslashes($p['title']) ?>', '<?= $p['price'] ?>', '<?= $p['enrollment_count'] ?>', '<?= $p['revenue'] ?>'],
        <?php endforeach; ?>
    ];
    downloadCSV(rows, 'top_programs.csv');
}

function downloadTopTeachers() {
    const rows = [
        ['Rank', 'Teacher', 'Published Programs'],
        <?php foreach ($topTeachers as $i => $t): ?>
        ['<?= $i+1 ?>', '<?= addslashes(trim(($t['fname']??'').' '.($t['lname']??''))) ?>', '<?= $t['cnt'] ?>'],
        <?php endforeach; ?>
    ];
    downloadCSV(rows, 'top_teachers.csv');
}

function downloadTransactions() {
    const rows = [
        ['Transaction ID', 'Student', 'Email', 'Program', 'Teacher', 'Amount', 'Date'],
        <?php foreach ($transactions as $trans): ?>
        ['<?= $trans['payment_id'] ?>', '<?= addslashes($trans['fname'].' '.$trans['lname']) ?>', '<?= $trans['email'] ?>', '<?= addslashes($trans['program_title']) ?>', '<?= addslashes($trans['teacher_fname'].' '.$trans['teacher_lname']) ?>', '<?= $trans['amount'] ?>', '<?= $trans['datePaid'] ?>'],
        <?php endforeach; ?>
    ];
    downloadCSV(rows, 'transactions.csv');
}

function downloadCSV(rows, filename) {
    const csv = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
</body>
</html>

<?php include('../../components/footer.php') ?>