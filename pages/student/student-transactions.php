<?php
    session_start();
    require '../../php/dbConnection.php';

    // Check if user is logged in and is a student
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
        header("Location: ../login.php");
        exit();
    }

    $studentID = (int)$_SESSION['userID'];

    // Initialize filter variables with defaults
    $searchQuery = '';
    $categoryFilter = '';
    $dateFrom = '';
    $dateTo = '';
    $sortBy = 'recent';

    // Override with GET parameters if present
    if (isset($_GET['search'])) $searchQuery = $_GET['search'];
    if (isset($_GET['category'])) $categoryFilter = $_GET['category'];
    if (isset($_GET['date_from'])) $dateFrom = $_GET['date_from'];
    if (isset($_GET['date_to'])) $dateTo = $_GET['date_to'];
    if (isset($_GET['sort'])) $sortBy = $_GET['sort'];

    // Build WHERE clause for filters
    $whereConditions = ["spe.student_id = ?"];
    $params = [$studentID];
    $types = "i";

    if (!empty($searchQuery)) {
        $whereConditions[] = "p.title LIKE ?";
        $params[] = "%{$searchQuery}%";
        $types .= "s";
    }

    if (!empty($categoryFilter)) {
        $whereConditions[] = "p.category = ?";
        $params[] = $categoryFilter;
        $types .= "s";
    }

    if (!empty($dateFrom)) {
        $whereConditions[] = "DATE(spe.enrollment_date) >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }

    if (!empty($dateTo)) {
        $whereConditions[] = "DATE(spe.enrollment_date) <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }

    $whereClause = implode(" AND ", $whereConditions);

    // Determine ORDER BY
    $orderBy = "spe.enrollment_date DESC"; // default
    switch ($sortBy) {
        case 'oldest':
            $orderBy = "spe.enrollment_date ASC";
            break;
        case 'recent':
        default:
            $orderBy = "spe.enrollment_date DESC";
            break;
    }

    // Get filtered enrollments
    $sql = "
        SELECT 
            spe.*,
            p.title as program_title,
            p.thumbnail as program_thumbnail,
            p.category as program_category,
            p.price as program_price
        FROM student_program_enrollments spe
        LEFT JOIN programs p ON spe.program_id = p.programID
        WHERE {$whereClause}
        ORDER BY {$orderBy}
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get available categories for filter dropdown
    $categoriesStmt = $conn->query("SELECT DISTINCT category FROM programs ORDER BY category");
    $categories = $categoriesStmt->fetch_all(MYSQLI_ASSOC);

    // Calculate totals
    $totalSpent = 0;
    $totalEnrollments = count($enrollments);

    foreach ($enrollments as $enrollment) {
        $totalSpent += (float)($enrollment['program_price'] ?? 0);
    }

    $current_page = 'student-transactions';
    $page_title = 'My Transactions';
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<!-- <style>
    .page-container {
        width: 100%;
        max-width: 100%;
        padding: 0;
    }
    .page-content {
        width: 100%;
        max-width: none;
        padding: 2rem 3rem;
    }
</style> -->

<div class="page-container">
    <div class="page-content">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="section-title text-3xl font-bold text-gray-900 mb-2">Transaction History</h1>
            <p class="text-gray-600">View all your payment transactions and enrollment history</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Total Spent -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-8 text-white">
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <i class="ph ph-coins text-4xl text-black"></i>
                    </div>
                    <span class="text-sm text-[#A58618] font-medium bg-white bg-opacity-20 px-4 py-2 rounded-full">All Time</span>
                </div>
                <h3 class="text-4xl font-bold mb-2">₱<?= number_format($totalSpent, 2) ?></h3>
                <p class="text-blue-100 text-base">Total Spent</p>
            </div>

            <!-- Total Enrollments -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-8 text-white">
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <i class="ph ph-graduation-cap text-4xl text-black"></i>
                    </div>
                    <span class="text-sm font-medium text-[#A58618] bg-white bg-opacity-20 px-4 py-2 rounded-full">Programs</span>
                </div>
                <h3 class="text-4xl font-bold mb-2"><?= $totalEnrollments ?></h3>
                <p class="text-green-100 text-base">Total Enrollments</p>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ph ph-magnifying-glass"></i> Search Program
                    </label>
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($searchQuery) ?>"
                           placeholder="Search by program name..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ph ph-funnel"></i> Category
                    </label>
                    <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $categoryFilter === $cat['category'] ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($cat['category'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ph ph-calendar"></i> From Date
                    </label>
                    <input type="date" 
                           name="date_from" 
                           value="<?= htmlspecialchars($dateFrom) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ph ph-calendar"></i> To Date
                    </label>
                    <input type="date" 
                           name="date_to" 
                           value="<?= htmlspecialchars($dateTo) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Sort By -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ph ph-sort-ascending"></i> Sort By
                    </label>
                    <select name="sort" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="recent" <?= $sortBy === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                        <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="md:col-span-3 flex items-end gap-3">
                    <button type="submit" class="flex-1 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                        <i class="ph ph-funnel"></i> Apply Filters
                    </button>
                    <a href="student-transactions.php" class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold transition-colors">
                        <i class="ph ph-x"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <div class="mb-4">
            <p class="text-sm text-gray-600">
                Showing <strong><?= count($enrollments) ?></strong> enrollment<?= count($enrollments) !== 1 ? 's' : '' ?>
                <?php if (!empty($searchQuery) || !empty($categoryFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
                    <span class="text-blue-600">(filtered)</span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Enrollments Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-900">My Enrollments</h2>
            </div>
            
            <?php if (empty($enrollments)): ?>
                <div class="p-16 text-center">
                    <div class="bg-gray-100 rounded-full w-32 h-32 flex items-center justify-center mx-auto mb-6">
                        <i class="ph ph-magnifying-glass text-7xl text-gray-400"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-700 mb-3">No Results Found</h3>
                    <p class="text-gray-500 mb-8 text-lg">
                        <?php if (!empty($searchQuery) || !empty($categoryFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
                            No enrollments match your search criteria. Try adjusting your filters.
                        <?php else: ?>
                            You haven't enrolled in any programs yet.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($searchQuery) || !empty($categoryFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
                        <a href="student-transactions.php" class="inline-flex items-center gap-2 px-8 py-4 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors text-lg shadow-lg">
                            <i class="ph ph-x text-xl"></i>
                            Clear Filters
                        </a>
                    <?php else: ?>
                        <a href="student-programs.php" class="inline-flex items-center gap-2 px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors text-lg shadow-lg">
                            <i class="ph ph-books text-xl"></i>
                            Browse Programs
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Transaction ID</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Program</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Amount Paid</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Enrollment Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Last Accessed</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 notranslate">
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="text-sm font-mono font-semibold text-gray-900 bg-gray-100 px-3 py-1 rounded">
                                            #<?= str_pad($enrollment['enrollment_id'], 6, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <?php if ($enrollment['program_thumbnail']): ?>
                                                <img src="../../uploads/thumbnails/<?= htmlspecialchars($enrollment['program_thumbnail']) ?>" 
                                                     alt="Program" 
                                                     class="w-12 h-12 rounded-lg object-cover">
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                                    <i class="ph ph-book text-2xl text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <?= htmlspecialchars($enrollment['program_title'] ?? 'Unknown Program') ?>
                                                </div>
                                                <div class="text-xs text-gray-500">Program Enrollment</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                            <i class="ph ph-tag"></i>
                                            <?= ucfirst(htmlspecialchars($enrollment['program_category'] ?? 'General')) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="text-lg font-bold text-gray-900">
                                            ₱<?= number_format((float)($enrollment['program_price'] ?? 0), 2) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">
                                            <?= date('M j, Y', strtotime($enrollment['enrollment_date'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('h:i A', strtotime($enrollment['enrollment_date'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= date('M j, Y', strtotime($enrollment['last_accessed'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('h:i A', strtotime($enrollment['last_accessed'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <a href="student-program-view.php?program_id=<?= $enrollment['program_id'] ?>" 
                                           class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-900 font-semibold text-sm hover:underline">
                                            <i class="ph ph-arrow-right"></i>
                                            View Program
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

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
<!-- <script src="../../dist/javascript/carousel.js"></script> -->
<script src="../../dist/javascript/user-dropdown.js"></script>
<!-- <script src="../../dist/javascript/translate.js"></script> -->
</body>

</html>