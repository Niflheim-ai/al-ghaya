<?php
session_start();
$current_page = "teacher-analytics";
$page_title = "Teacher Analytics";

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    $_SESSION['error_message'] = 'Please log in as a teacher to access this page.';
    header("Location: ../login.php");
    exit();
}

// Include required files
require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';
require_once '../../php/program-helpers.php';

$user_id = $_SESSION['userID'];

// Get teacher ID using consolidated function
$teacher_id = getTeacherIdFromSession($conn, $user_id);

if (!$teacher_id) {
    $_SESSION['error_message'] = 'Teacher profile not found. Please contact administrator.';
    header("Location: ../teacher-dashboard.php");
    exit();
}

// Get teacher's programs
$programs = getTeacherPrograms($conn, $teacher_id);

// Calculate analytics data
$totalPrograms = count($programs);
$publishedPrograms = count(array_filter($programs, function($p) { return $p['status'] === 'published'; }));
$draftPrograms = count(array_filter($programs, function($p) { return $p['status'] === 'draft'; }));
$totalRevenue = array_sum(array_column($programs, 'price'));

$programsWithEnrollees = [];
    $totalEnrollees = 0;
foreach ($programs as $program) {
    $program_id = $program['programID'];
    $stmt = $conn->prepare("SELECT COUNT(*) as enrollees FROM student_program_enrollments WHERE program_id = ?");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $enrollees = $result->fetch_assoc()['enrollees'];
    $stmt->close();

    $program['enrollees'] = $enrollees;
    $totalEnrollees += $enrollees;
    $programsWithEnrollees[] = $program;
}

// --- Get students data with filters (This part was already correct) ---
$programFilter = $_GET['program'] ?? 'all';
$search = $_GET['search'] ?? '';
$order = $_GET['order'] ?? 'asc';

// Build SQL query for students
$sql = "SELECT DISTINCT u.userID, u.fname, u.lname, u.email, spe.program_id AS programID, p.title AS programTitle, spe.enrollment_date AS enrollmentDate
    FROM user u
    JOIN student_program_enrollments spe ON u.userID = spe.student_id
    JOIN programs p ON spe.program_id = p.programID
    WHERE u.role = 'student' AND p.teacherID = ?";

$params = [$teacher_id];
$types = "i";

if ($programFilter !== 'all' && !empty($programFilter)) {
    $sql .= " AND p.programID = ?";
    $params[] = intval($programFilter);
    $types .= "i";
}

if (!empty($search)) {
    $sql .= " AND (u.fname LIKE ? OR u.lname LIKE ? OR u.email LIKE ? OR p.title LIKE ?)";
    $searchParam = "%$search%";
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    $types .= "ssss";
}

$sql .= " ORDER BY u.lname " . ($order === 'asc' ? 'ASC' : 'DESC');

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $students = [];
    error_log("Analytics student query failed: " . $conn->error);
}

// --- CORRECTED: Get recent activity (last 7 days) ---
// The query now uses the correct table 'student_program_enrollments' and its columns.
$recentEnrollments = [];
$activityStmt = $conn->prepare(
    "SELECT p.title, COUNT(*) as count, DATE(spe.enrollment_date) as date
     FROM student_program_enrollments spe
     JOIN programs p ON spe.program_id = p.programID 
     WHERE p.teacherID = ? AND spe.enrollment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY p.programID, DATE(spe.enrollment_date)
     ORDER BY spe.enrollment_date DESC"
);

if ($activityStmt) {
    $activityStmt->bind_param("i", $teacher_id);
    $activityStmt->execute();
    $activityResult = $activityStmt->get_result();
    $recentEnrollments = $activityResult->fetch_all(MYSQLI_ASSOC);
    $activityStmt->close();
} else {
    error_log("Recent activity query failed: " . $conn->error);
}
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>

<!-- Dependencies -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-container">
    <div class="page-content">
        <section class="content-section">
            <div class="flex justify-between items-center mb-6">
                <h1 class="section-title text-2xl md:text-3xl font-bold">Analytics Dashboard</h1>
                <div class="flex gap-3">
                    <button onclick="exportData('csv')" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="ph ph-download mr-2"></i>Export CSV
                    </button>
                    <button onclick="printReport()" 
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="ph ph-printer mr-2"></i>Print Report
                    </button>
                </div>
            </div>

            <!-- Analytics Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-4">
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Total Programs</p>
                            <p class="text-2xl font-bold text-blue-600"><?= $totalPrograms ?></p>
                        </div>
                        <i class="ph ph-books text-3xl text-blue-500"></i>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Published Programs</p>
                            <p class="text-2xl font-bold text-green-600"><?= $publishedPrograms ?></p>
                        </div>
                        <i class="ph ph-check-circle text-3xl text-green-500"></i>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Total Students</p>
                            <p class="text-2xl font-bold text-orange-600"><?= $totalEnrollees ?></p>
                        </div>
                        <i class="ph ph-users text-3xl text-orange-500"></i>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Draft Programs</p>
                            <p class="text-2xl font-bold text-purple-600"><?= $draftPrograms ?></p>
                        </div>
                        <i class="ph ph-file-dashed text-3xl text-purple-500"></i>
                    </div>
                </div>
            </div>

            <!-- Program Performance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-2">
                <?php foreach ($programsWithEnrollees as $program): ?>
                    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2"><?= htmlspecialchars($program['title']) ?></h3>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?= $program['status'] === 'published' ? 'bg-green-100 text-green-800' : 
                                           ($program['status'] === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                        <?= ucfirst($program['status']) ?>
                                    </span>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                        <?= ucfirst($program['category']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="text-center">
                                <p class="text-gray-600 text-sm">Enrollees</p>
                                <div class="flex items-center justify-center gap-2">
                                    <i class="ph ph-users text-xl text-blue-600"></i>
                                    <p class="text-xl font-bold text-blue-600"><?= $program['enrollees'] ?></p>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-600 text-sm">Price</p>
                                <p class="text-lg font-bold text-green-600">₱<?= number_format($program['price'], 2) ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-600 text-sm">Revenue</p>
                                <p class="text-lg font-bold text-purple-600">₱<?= number_format($program['enrollees'] * $program['price'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Main Content Grid -->
            <p class="text-neutral-500">*Only students enrolled in your programs will be displayed</p>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Students Table -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Enrolled Students</h2>
                        <span class="text-sm text-gray-500"><?= count($students) ?> students found</span>
                    </div>

                    <!-- Filters and Search -->
                    <form method="GET" action="" class="mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="program" class="block text-sm font-medium text-gray-700 mb-1">Filter by Program</label>
                                <select id="program" name="program" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="all" <?= $programFilter === 'all' ? 'selected' : '' ?>>All Programs</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?= $program['programID'] ?>" <?= $programFilter == $program['programID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($program['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Students</label>
                                <input type="text" id="search" name="search" placeholder="Name or email..." 
                                       value="<?= htmlspecialchars($search) ?>"
                                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                                    <select name="order" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="asc" <?= $order === 'asc' ? 'selected' : '' ?>>A-Z</option>
                                        <option value="desc" <?= $order === 'desc' ? 'selected' : '' ?>>Z-A</option>
                                    </select>
                                </div>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Students Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                            <i class="ph ph-users text-4xl mb-2"></i>
                                            <p>No students found matching your criteria.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                        <span class="text-blue-600 font-medium text-sm">
                                                            <?= strtoupper(substr($student['fname'], 0, 1) . substr($student['lname'], 0, 1)) ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?= htmlspecialchars($student['fname'] . ' ' . $student['lname']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <?= htmlspecialchars($student['email']) ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                                                <?= htmlspecialchars($student['programTitle']) ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('M d, Y', strtotime($student['enrollmentDate'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Charts and Recent Activity -->
                <div class="space-y-6">
                    <!-- Enrollments Chart -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Enrollments by Program</h3>
                        <div class="h-64">
                            <canvas id="enrollmentsChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Activity</h3>
                        <div class="space-y-3">
                            <?php if (empty($recentEnrollments)): ?>
                                <div class="text-center py-4 text-gray-500">
                                    <i class="ph ph-clock text-2xl mb-2"></i>
                                    <p class="text-sm">No recent enrollments</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentEnrollments as $activity): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <i class="ph ph-user-plus text-green-600"></i>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($activity['title']) ?></p>
                                                <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($activity['date'])) ?></p>
                                            </div>
                                        </div>
                                        <span class="text-sm font-bold text-green-600">+<?= $activity['count'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Back to Top Button -->
<button type="button" onclick="scrollToTop()" 
        class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition z-50" 
        id="scroll-to-top">
    <i class="ph ph-arrow-up text-xl"></i>
</button>

<?php include '../../components/footer.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../components/navbar.js"></script>

<script>
// Chart.js configuration
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('enrollmentsChart').getContext('2d');
    
    const labels = <?= json_encode(array_map(function($program) {
        return htmlspecialchars($program['title']);
    }, $programsWithEnrollees)) ?>;
    
    const data = <?= json_encode(array_map(function($program) {
        return $program['enrollees'];
    }, $programsWithEnrollees)) ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#3B82F6', // Blue
                    '#10B981', // Green  
                    '#F59E0B', // Orange
                    '#8B5CF6', // Purple
                    '#EF4444', // Red
                    '#06B6D4', // Cyan
                    '#84CC16', // Lime
                    '#F97316'  // Orange
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });
});

// Utility functions
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function exportData(format) {
    if (format === 'csv') {
        Swal.fire({
            title: 'Export Data',
            text: 'Generate CSV report of enrolled students?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Export CSV',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create CSV data
                const students = <?= json_encode($students) ?>;
                let csvContent = 'Student Name,Email,Program,Enrollment Date\n';
                
                students.forEach(student => {
                    csvContent += `"${student.fname} ${student.lname}","${student.email}","${student.programTitle}","${student.enrollmentDate}"\n`;
                });
                
                // Download CSV
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'student-analytics-' + new Date().toISOString().slice(0, 10) + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                Swal.fire('Exported!', 'CSV file has been downloaded.', 'success');
            }
        });
    }
}

function printReport() {
    window.print();
}

// Show/hide back to top button
window.addEventListener('scroll', function() {
    const btn = document.getElementById('scroll-to-top');
    if (btn) {
        if (window.pageYOffset > 300) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    }
});
</script>

<style>
@media print {
    .scroll-to-top, .no-print { display: none !important; }
    .page-container { padding: 0 !important; }
    .shadow-md, .shadow-lg { box-shadow: none !important; }
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

</body>
</html>