<?php
    session_start();
    require '../../php/dbConnection.php';

    // Check if user is logged in and is a student
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
        header("Location: ../login.php");
        exit();
    }

    $studentID = (int)$_SESSION['userID'];

    // Get all enrollments for this student (using your actual table)
    $stmt = $conn->prepare("
        SELECT 
            spe.*,
            p.title as program_title,
            p.thumbnail as program_thumbnail,
            p.category as program_category,
            p.price as program_price
        FROM student_program_enrollments spe
        LEFT JOIN programs p ON spe.program_id = p.programID
        WHERE spe.student_id = ?
        ORDER BY spe.enrollment_date DESC
    ");
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calculate totals
    $totalSpent = 0;
    $totalEnrollments = count($enrollments);

    foreach ($enrollments as $enrollment) {
        // Add program price to total (assuming programs have a price field)
        $totalSpent += (float)($enrollment['program_price'] ?? 0);
    }

    $current_page = 'student-transactions';
    $page_title = 'Transactions';
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>
<style>
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
</style>
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">

<div class="page-container">
    <div class="page-content">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Transaction History</h1>
            <p class="text-gray-600">View all your payment transactions and enrollment history</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Total Spent -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-8 text-white">
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <i class="ph ph-coins text-4xl"></i>
                    </div>
                    <span class="text-sm font-medium bg-white bg-opacity-20 px-4 py-2 rounded-full">All Time</span>
                </div>
                <h3 class="text-4xl font-bold mb-2">$<?= number_format($totalSpent, 2) ?></h3>
                <p class="text-blue-100 text-base">Total Spent</p>
            </div>

            <!-- Total Enrollments -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-8 text-white">
                <div class="flex items-center justify-between mb-6">
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <i class="ph ph-graduation-cap text-4xl"></i>
                    </div>
                    <span class="text-sm font-medium bg-white bg-opacity-20 px-4 py-2 rounded-full">Programs</span>
                </div>
                <h3 class="text-4xl font-bold mb-2"><?= $totalEnrollments ?></h3>
                <p class="text-green-100 text-base">Total Enrollments</p>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-900">Recent Transactions</h2>
            </div>
            
            <?php if (empty($transactions)): ?>
                <div class="p-16 text-center">
                    <div class="bg-gray-100 rounded-full w-32 h-32 flex items-center justify-center mx-auto mb-6">
                        <i class="ph ph-receipt text-7xl text-gray-400"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-700 mb-3">No Transactions Yet</h3>
                    <p class="text-gray-500 mb-8 text-lg">You haven't made any purchases or enrollments yet.</p>
                    <a href="student-programs.php" class="inline-flex items-center gap-2 px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors text-lg shadow-lg">
                        <i class="ph ph-books text-xl"></i>
                        Browse Programs
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Transaction ID</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Program</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Payment Method</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($transactions as $trans): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="text-sm font-mono font-semibold text-gray-900 bg-gray-100 px-3 py-1 rounded">
                                            #<?= str_pad($trans['transaction_id'], 6, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <?php if ($trans['program_thumbnail']): ?>
                                                <img src="../../uploads/thumbnails/<?= htmlspecialchars($trans['program_thumbnail']) ?>" 
                                                     alt="Program" 
                                                     class="w-12 h-12 rounded-lg object-cover">
                                            <?php endif; ?>
                                            <div>
                                                <?php if ($trans['program_title']): ?>
                                                    <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($trans['program_title']) ?></div>
                                                    <div class="text-xs text-gray-500">Program Enrollment</div>
                                                <?php else: ?>
                                                    <span class="text-sm text-gray-500">General Payment</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                            <i class="ph ph-tag"></i>
                                            <?= ucfirst(htmlspecialchars($trans['transaction_type'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="text-lg font-bold text-gray-900">$<?= number_format($trans['amount'], 2) ?></span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="text-sm text-gray-700">
                                            <?= $trans['payment_method'] ? ucfirst(htmlspecialchars($trans['payment_method'])) : 'N/A' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <?php
                                        $statusConfig = [
                                            'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'ph-check-circle'],
                                            'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'ph-clock'],
                                            'failed' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'ph-x-circle'],
                                            'refunded' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'ph-arrow-u-up-left']
                                        ];
                                        $status = $statusConfig[$trans['transaction_status']] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'icon' => 'ph-question'];
                                        ?>
                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-full <?= $status['bg'] ?> <?= $status['text'] ?>">
                                            <i class="ph <?= $status['icon'] ?>"></i>
                                            <?= ucfirst($trans['transaction_status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">
                                            <?= date('M j, Y', strtotime($trans['transaction_date'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('h:i A', strtotime($trans['transaction_date'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <button onclick="viewDetails(<?= $trans['transaction_id'] ?>)" 
                                                class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-900 font-semibold text-sm hover:underline">
                                            <i class="ph ph-eye"></i>
                                            View
                                        </button>
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
<script src="../../dist/javascript/carousel.js"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../dist/javascript/translate.js"></script>
</body>

</html>