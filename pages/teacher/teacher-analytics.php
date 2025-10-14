<?php
    session_start();
    $current_page = "teacher-analytics";
    $page_title = "Faculty Analytics";

    // Check if user is logged in and is a teacher
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
        header("Location: ../login.php");
        exit();
    }

    // Include required files
    require '../../php/dbConnection.php';
    require '../../php/functions.php';

    require_once '../../php/dbConnection.php';
    require_once '../../php/functions.php';
    require_once '../../php/program-helpers.php';

    // Get teacher's programs and their enrollees
    $teacher_id = $_SESSION['userID'];
    $programs = getTeacherPrograms($conn, $teacher_id);

    // Fetch enrollees count for each program
    $programsWithEnrollees = [];
    foreach ($programs as $program) {
        $program_id = $program['programID'];
        $stmt = $conn->prepare("SELECT COUNT(*) as enrollees FROM student_program WHERE programID = ?");
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $enrollees = $result->fetch_assoc()['enrollees'];
        $stmt->close();

        $program['enrollees'] = $enrollees;
        $programsWithEnrollees[] = $program;
    }

    // Get all students enrolled in teacher's programs
    $students = [];
    $programFilter = isset($_GET['program']) ? $_GET['program'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';

    $sql = "SELECT s.studentID, s.fname, s.lname, s.email, sp.programID, p.title as programTitle, sp.enrollmentDate
            FROM student s
            JOIN student_program sp ON s.studentID = sp.studentID
            JOIN programs p ON sp.programID = p.programID
            WHERE p.teacherID = ?";

    $params = [$teacher_id];
    $types = "i";

    if ($programFilter !== 'all') {
        $sql .= " AND p.programID = ?";
        $params[] = $programFilter;
        $types .= "i";
    }

    if (!empty($search)) {
        $sql .= " AND (s.fname LIKE ? OR s.lname LIKE ? OR s.email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }

    $sql .= " ORDER BY s.lname " . ($order === 'asc' ? 'ASC' : 'DESC');

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
?>
<?php include '../components/header.php'; ?>
<?php include '../components/teacher-nav.php'; ?>
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="page-container p-4 md:p-6">
    <div class="page-content">
        <section class="content-section">
            <div class="flex justify-between items-center mb-4">
                <h1 class="section-title text-xl md:text-2xl font-bold">Analytics Overview</h1>
            </div>

            <!-- Analytics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach ($programsWithEnrollees as $program): ?>
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold arabic"><?= htmlspecialchars($program['title']) ?></h3>
                            </div>
                            <!-- Difficulty Badge -->
                            <div class="proficiency-badge">
                                <i class="ph-fill ph-barbell text-[15px]"></i>
                                <p class="text-[12px] font-semibold">
                                    <?= htmlspecialchars(ucfirst(strtolower($program['category']))); ?> Difficulty
                                </p>
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="text-gray-600 text-sm mb-1">Total # of Enrollees</p>
                            <div class="flex items-center justify-center">
                                <i class="ph ph-users text-4xl text-[#A58618] mr-3 pt-2"></i>
                                <p class="text-3xl font-bold"><?= $program['enrollees'] ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Two Column Layout -->
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Column: Students Table -->
                <div class="lg:w-2/3 bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between">
                        <h2 class="text-xl font-bold mb-4">Enrolled Students</h2>
                        <button onclick="confirmAction('analytics', 'Are you sure you want to generate this report?')"
                            class="bg-blue-500 text-white px-2 rounded-md transition transition-colors text-sm md:text-base flex items-center justify-center hover:bg-blue-600 hover:cursor-pointer">
                            <i class="ph ph-download text-lg mr-1"></i>
                            Generate Report
                        </button>
                    </div>

                    <!-- Filters and Search -->
                    <form method="GET" action="" class="mb-4">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <label for="program" class="block text-sm font-medium text-gray-700 mb-1">Filter by Program</label>
                                <select id="program" name="program" class="w-full p-2 border rounded-md">
                                    <option value="all" <?= (!isset($_GET['program']) || $_GET['program'] === 'all') ? 'selected' : '' ?>>All Programs</option>
                                    <?php foreach ($programs as $program): ?>
                                        <option value="<?= $program['programID'] ?>" <?= (isset($_GET['program']) && $_GET['program'] == $program['programID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($program['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" id="search" name="search" placeholder="Search students..."
                                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                                       class="w-full p-2 border rounded-md">
                            </div>
                            <div class="flex items-end">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                                <div class="flex gap-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="order" value="asc" class="form-radio" <?= (!isset($_GET['order']) || $_GET['order'] === 'asc') ? 'checked' : '' ?>>
                                        <span class="ml-1">A-Z</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="order" value="desc" class="form-radio" <?= (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'checked' : '' ?>>
                                        <span class="ml-1">Z-A</span>
                                    </label>
                                </div>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Apply</button>
                            </div>
                        </div>
                    </form>

                    <!-- Students Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrollment Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No students found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($student['fname'] . ' ' . $student['lname']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($student['email']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($student['programTitle']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('M d, Y', strtotime($student['enrollmentDate'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Column: Bar Graph -->
                <div class="lg:w-1/3 bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Enrollees by Program</h2>
                    <div class="h-96">
                        <canvas id="enrolleesChart"></canvas>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Back to Top button -->
<button type="button" onclick="scrollToTop()"
    class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:cursor-pointer hover:bg-gray-700 transition z-50"
    id="scroll-to-top">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
    </svg>
</button>

<?php include '../components/footer.php'; ?>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- JS -->
<script src="../dist/javascript/translate.js"></script>
<script src="../dist/javascript/user-dropdown.js"></script>
<script src="../components/navbar.js"></script>
<script src="../dist/javascript/scroll-to-top.js"></script>
<script>
    // Scroll to top function
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    // Show/hide back to top button
    window.addEventListener('scroll', function() {
        const btn = document.getElementById('scroll-to-top');
        if (window.pageYOffset > 300) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    });

    function confirmAction(type, message) {
        Swal.fire({
            title: 'Confirm',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Handle report generation here
                Swal.fire('Generated!', 'Your report has been generated.', 'success');
            }
        });
    }

    // Initialize the bar chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('enrolleesChart').getContext('2d');

        // Prepare data for the chart
        const labels = <?= json_encode(array_map(function($program) {
            return htmlspecialchars($program['title']);
        }, $programsWithEnrollees)) ?>;

        const data = <?= json_encode(array_map(function($program) {
            return $program['enrollees'];
        }, $programsWithEnrollees)) ?>;

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Number of Enrollees',
                    data: data,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
</body>
</html>
