<?php
session_start();
require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';

$current_page = "teacher-analytics";
$page_title = "Teacher Analytics";

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['userID'];
$current_page = 'teacher-analytics';
$page_title = 'Analytics Dashboard';

// Get teacherID from teacher table using userID
$teacherStmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ?");
$teacherStmt->bind_param("i", $user_id);
$teacherStmt->execute();
$teacherResult = $teacherStmt->get_result();
$teacherData = $teacherResult->fetch_assoc();
$teacherStmt->close();

if (!$teacherData) {
    die("Teacher record not found for this user.");
}

$teacher_id = (int)$teacherData['teacherID'];

// Get filter parameters
$programFilter = $_GET['program'] ?? 'all';
$search = $_GET['search'] ?? '';
$order = $_GET['order'] ?? 'asc';

// Get teacher's programs using teacherID
$programsStmt = $conn->prepare("
    SELECT programID, title, price, category, status, thumbnail, dateCreated
    FROM programs 
    WHERE teacherID = ? 
    ORDER BY title
");
$programsStmt->bind_param("i", $teacher_id);
$programsStmt->execute();
$programs = $programsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$programsStmt->close();

// Get filter parameters for programs
$programSearch = $_GET['program_search'] ?? '';
$statusFilter = $_GET['status_filter'] ?? 'all';
$categoryFilter = $_GET['category_filter'] ?? 'all';
$programSort = $_GET['program_sort'] ?? 'title_asc';

// Filter programs
$filteredPrograms = $programs;

// Apply search filter
if (!empty($programSearch)) {
    $filteredPrograms = array_filter($filteredPrograms, function($prog) use ($programSearch) {
        return stripos($prog['title'], $programSearch) !== false;
    });
}

// Apply status filter
if ($statusFilter !== 'all') {
    $filteredPrograms = array_filter($filteredPrograms, function($prog) use ($statusFilter) {
        return ($prog['status'] ?? 'draft') === $statusFilter;
    });
}

// Apply category filter
if ($categoryFilter !== 'all') {
    $filteredPrograms = array_filter($filteredPrograms, function($prog) use ($categoryFilter) {
        return $prog['category'] === $categoryFilter;
    });
}

// Get enrollee counts for all programs
foreach ($filteredPrograms as &$program) {
    $program_id = $program['programID'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as enrollees FROM student_program_enrollments WHERE program_id = ?");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $program['enrollees'] = $result->fetch_assoc()['enrollees'];
    $stmt->close();
}
unset($program);

// Sort programs
switch ($programSort) {
    case 'title_desc':
        usort($filteredPrograms, function($a, $b) { return strcasecmp($b['title'], $a['title']); });
        break;
    case 'enrollees_desc':
        usort($filteredPrograms, function($a, $b) { return $b['enrollees'] - $a['enrollees']; });
        break;
    case 'enrollees_asc':
        usort($filteredPrograms, function($a, $b) { return $a['enrollees'] - $b['enrollees']; });
        break;
    case 'price_desc':
        usort($filteredPrograms, function($a, $b) { return $b['price'] - $a['price']; });
        break;
    case 'price_asc':
        usort($filteredPrograms, function($a, $b) { return $a['price'] - $b['price']; });
        break;
    case 'newest':
        usort($filteredPrograms, function($a, $b) { return strtotime($b['dateCreated']) - strtotime($a['dateCreated']); });
        break;
    case 'oldest':
        usort($filteredPrograms, function($a, $b) { return strtotime($a['dateCreated']) - strtotime($b['dateCreated']); });
        break;
    case 'title_asc':
    default:
        usort($filteredPrograms, function($a, $b) { return strcasecmp($a['title'], $b['title']); });
        break;
}

// Debug log
error_log("User ID: $user_id, Teacher ID: $teacher_id, Programs found: " . count($programs));

// Calculate key metrics
$totalPrograms = count($programs);
$publishedPrograms = 0;
$draftPrograms = 0;

foreach ($programs as $prog) {
    $status = $prog['status'] ?? 'published';
    if ($status === 'published') {
        $publishedPrograms++;
    } elseif ($status === 'draft') {
        $draftPrograms++;
    }
}

// Get total enrollments and build programs with enrollees
$totalEnrollees = 0;
$programsWithEnrollees = [];

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
    
    if ($enrollees > 0) {
        $programsWithEnrollees[] = $program;
    }
}

// Get students data with filters
$sql = "
    SELECT DISTINCT 
        u.userID, 
        u.fname, 
        u.lname, 
        u.email,
        spe.program_id AS programID, 
        p.title AS programTitle,
        spe.enrollment_date AS enrollmentDate
    FROM user u
    INNER JOIN student_program_enrollments spe ON u.userID = spe.student_id
    INNER JOIN programs p ON spe.program_id = p.programID
    WHERE u.role = 'student' AND p.teacherID = ?
";

$params = [$teacher_id];
$types = "i";

if ($programFilter !== 'all' && !empty($programFilter)) {
    $sql .= " AND p.programID = ?";
    $params[] = intval($programFilter);
    $types .= "i";
}

if (!empty($search)) {
    $sql .= " AND (u.fname LIKE ? OR u.lname LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$search%";
    array_push($params, $searchParam, $searchParam, $searchParam);
    $types .= "sss";
}

$sql .= " ORDER BY u.lname " . ($order === 'asc' ? 'ASC' : 'DESC');

$students = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    error_log("Students found: " . count($students));
}

// Get recent activity (last 7 days)
$recentEnrollments = [];
$activityStmt = $conn->prepare("
    SELECT 
        p.title, 
        COUNT(*) as count, 
        DATE(spe.enrollment_date) as date
    FROM student_program_enrollments spe
    JOIN programs p ON spe.program_id = p.programID
    WHERE p.teacherID = ? AND spe.enrollment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY p.programID, DATE(spe.enrollment_date)
    ORDER BY spe.enrollment_date DESC
    LIMIT 10
");

if ($activityStmt) {
    $activityStmt->bind_param("i", $teacher_id);
    $activityStmt->execute();
    $activityResult = $activityStmt->get_result();
    $recentEnrollments = $activityResult->fetch_all(MYSQLI_ASSOC);
    $activityStmt->close();
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

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Programs Table -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">My Programs</h2>
                        <span class="text-sm text-gray-500"><?= count($programs) ?> programs total</span>
                    </div>

                    <!-- Program Filters -->
                    <form method="GET" action="" class="mb-6">
                        <input type="hidden" name="student_search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="program" value="<?= htmlspecialchars($programFilter) ?>">
                        <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search Programs -->
                            <div>
                                <label for="program_search" class="block text-sm font-medium text-gray-700 mb-1">Search Programs</label>
                                <input type="text" 
                                    id="program_search" 
                                    name="program_search" 
                                    placeholder="Program name..." 
                                    value="<?= htmlspecialchars($_GET['program_search'] ?? '') ?>"
                                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Filter by Status -->
                            <div>
                                <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status_filter" name="status_filter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="all" <?= ($_GET['status_filter'] ?? 'all') === 'all' ? 'selected' : '' ?>>All Status</option>
                                    <option value="published" <?= ($_GET['status_filter'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="draft" <?= ($_GET['status_filter'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="pending" <?= ($_GET['status_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="rejected" <?= ($_GET['status_filter'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </div>

                            <!-- Filter by Category -->
                            <div>
                                <label for="category_filter" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select id="category_filter" name="category_filter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="all" <?= ($_GET['category_filter'] ?? 'all') === 'all' ? 'selected' : '' ?>>All Categories</option>
                                    <option value="beginner" <?= ($_GET['category_filter'] ?? '') === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                                    <option value="intermediate" <?= ($_GET['category_filter'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                                    <option value="advanced" <?= ($_GET['category_filter'] ?? '') === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                                </select>
                            </div>

                            <!-- Sort Programs -->
                            <div>
                                <label for="program_sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                                <select id="program_sort" name="program_sort" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="title_asc" <?= ($_GET['program_sort'] ?? 'title_asc') === 'title_asc' ? 'selected' : '' ?>>Title (A-Z)</option>
                                    <option value="title_desc" <?= ($_GET['program_sort'] ?? '') === 'title_desc' ? 'selected' : '' ?>>Title (Z-A)</option>
                                    <option value="enrollees_desc" <?= ($_GET['program_sort'] ?? '') === 'enrollees_desc' ? 'selected' : '' ?>>Most Enrollees</option>
                                    <option value="enrollees_asc" <?= ($_GET['program_sort'] ?? '') === 'enrollees_asc' ? 'selected' : '' ?>>Least Enrollees</option>
                                    <option value="price_desc" <?= ($_GET['program_sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Highest Price</option>
                                    <option value="price_asc" <?= ($_GET['program_sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Lowest Price</option>
                                    <option value="newest" <?= ($_GET['program_sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="oldest" <?= ($_GET['program_sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="md:col-span-4 flex gap-3">
                                <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                                    <i class="ph ph-funnel"></i> Apply Filters
                                </button>
                                <a href="teacher-analytics.php" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition-colors">
                                    <i class="ph ph-x"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Programs Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrollees</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($filteredPrograms)): ?>
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                            <i class="ph ph-books text-4xl mb-2"></i>
                                            <p>No programs found matching your criteria.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($filteredPrograms as $program): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-4">
                                                <div class="flex items-center gap-3">
                                                    <?php if ($program['thumbnail']): ?>
                                                        <img src="../../uploads/thumbnails/<?= htmlspecialchars($program['thumbnail']) ?>" 
                                                            alt="Thumbnail" 
                                                            class="w-12 h-12 rounded-lg object-cover">
                                                    <?php else: ?>
                                                        <div class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                                            <i class="ph ph-book text-2xl text-gray-400"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="max-w-xs">
                                                        <div class="text-sm font-semibold text-gray-900 truncate">
                                                            <?= htmlspecialchars($program['title']) ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            ID: <?= $program['programID'] ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                    <?= ucfirst(htmlspecialchars($program['category'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <?php
                                                $statusColors = [
                                                    'published' => 'bg-green-100 text-green-800',
                                                    'draft' => 'bg-gray-100 text-gray-800',
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'rejected' => 'bg-red-100 text-red-800'
                                                ];
                                                $statusColor = $statusColors[$program['status'] ?? 'draft'] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 py-1 text-xs rounded-full <?= $statusColor ?>">
                                                    <?= ucfirst($program['status'] ?? 'draft') ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <i class="ph ph-users text-blue-600"></i>
                                                    <span class="text-sm font-semibold text-gray-900"><?= $program['enrollees'] ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="text-sm font-semibold text-green-600">
                                                    ₱<?= number_format($program['price'], 2) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="text-sm font-semibold text-purple-600">
                                                    ₱<?= number_format($program['enrollees'] * $program['price'], 2) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('M d, Y', strtotime($program['dateCreated'])) ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                <a href="teacher-program-edit.php?id=<?= $program['programID'] ?>" 
                                                class="text-blue-600 hover:text-blue-900 font-medium">
                                                    <i class="ph ph-pencil"></i> Edit
                                                </a>
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
            <p class="text-neutral-500 mb-4">*Only students enrolled in your programs will be displayed</p>
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
                    '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6',
                    '#EF4444', '#06B6D4', '#84CC16', '#F97316'
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
                        padding: 15,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                }
            }
        }
    });
});

// Export CSV function - Complete Analytics Data
function exportData(format) {
    if (format === 'csv') {
        const programs = <?= json_encode($filteredPrograms) ?>;
        const students = <?= json_encode($students) ?>;
        
        let csvContent = '\uFEFF'; // UTF-8 BOM for proper encoding
        
        // Programs Section
        csvContent += '=== MY PROGRAMS ===\n';
        csvContent += 'Program ID,Title,Category,Status,Enrollees,Price (PHP),Revenue (PHP),Created Date\n';
        
        programs.forEach(program => {
            const price = parseFloat(program.price) || 0;
            const enrollees = parseInt(program.enrollees) || 0;
            const revenue = enrollees * price;
            const createdDate = program.dateCreated ? formatDate(program.dateCreated) : 'N/A';
            csvContent += `${program.programID},"${escapeCSV(program.title)}",${program.category},${program.status || 'draft'},${enrollees},${price.toFixed(2)},${revenue.toFixed(2)},${createdDate}\n`;
        });
        
        // Blank line separator
        csvContent += '\n';
        
        // Students Section
        csvContent += '=== ENROLLED STUDENTS ===\n';
        csvContent += 'Student Name,Email,Program,Enrollment Date\n';
        
        students.forEach(student => {
            const enrollDate = formatDate(student.enrollmentDate);
            csvContent += `"${escapeCSV(student.fname + ' ' + student.lname)}","${student.email}","${escapeCSV(student.programTitle)}",${enrollDate}\n`;
        });
        
        // Blank line separator
        csvContent += '\n';
        
        // Summary Section
        csvContent += '=== SUMMARY ===\n';
        csvContent += 'Metric,Value\n';
        csvContent += `Total Programs,<?= $totalPrograms ?>\n`;
        csvContent += `Published Programs,<?= $publishedPrograms ?>\n`;
        csvContent += `Draft Programs,<?= $draftPrograms ?>\n`;
        csvContent += `Total Enrollees,<?= $totalEnrollees ?>\n`;
        csvContent += `Unique Students,${students.length}\n`;
        
        // Create and download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'analytics-complete-' + new Date().toISOString().slice(0, 10) + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        Swal.fire({
            icon: 'success',
            title: 'Exported!',
            text: 'Complete analytics data has been downloaded.',
            timer: 2000,
            showConfirmButton: false
        });
    }
}

// Helper function to escape CSV values
function escapeCSV(str) {
    if (!str) return '';
    return String(str).replace(/"/g, '""');
}

// Helper function to format dates
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}


// Print Report function
function printReport() {
    window.print();
}
</script>

<script src="../../dist/javascript/scroll-to-top.js"></script>

<style>
@media print {
    .scroll-to-top, .no-print { display: none !important; }
    .page-container { padding: 0 !important; }
    .shadow-md, .shadow-lg { box-shadow: none !important; }
}
</style>

</body>
</html>