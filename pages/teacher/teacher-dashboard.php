<?php
session_start();
$current_page = "teacher-dashboard";
$page_title = "Faculty Dashboard";

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/teacher-nav.php'; ?>
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<div class="page-container">
    <div class="page-content">
        <!-- 1ST Section - Recent Edited -->
        <section class="content-section">
            <h1 class="section-title md:text-2xl font-bold">Recently Edited</h1>
            <div class="w-full overflow-x-auto">
                <?php
                // Fetch teacher's programs sorted by dateUpdated
                if (!isset($teacher_id) && isset($_SESSION['userID'])) {
                    $teacher_id = $_SESSION['userID'];
                }
                if (!function_exists('getTeacherPrograms')) {
                    require '../../php/functions.php';
                }
                $programs = getTeacherPrograms($conn, $teacher_id, 'dateUpdated');

                if (!empty($programs)) {
                    // Display the most recent program
                    $recentProgram = $programs[0];
                ?>
                    <div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-company_white border-[1px] border-primary relative">
                        <div class="w-full h-fit overflow-hidden rounded-[20px] flex flex-wrap">
                            <!-- Image -->
                            <img src="<?= !empty($recentProgram['image']) ? '../../uploads/program_thumbnails/' . $recentProgram['image'] : '../../images/blog-bg.svg' ?>"
                                alt="Program Image" class="w-[221px] h-[500px] object-cover flex-grow flex-shrink-0 basis-1/4">
                            <!-- Content -->
                            <div class="overflow-hidden p-[30px] h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-y-[25px]">
                                <h2 class="price-sub-header">₱<?= number_format($recentProgram['price'], 2) ?></h2>
                                <div class="flex flex-col gap-y-[10px] w-full h-fit">
                                    <div class="flex flex-col gap-y-[5px] w-full h-fit">
                                        <p class="arabic body-text2-semibold"><?= htmlspecialchars($recentProgram['title']) ?></p>
                                        <div class="mask-b-from-20% mask-b-to-80% w-full h-[120px]">
                                            <p><?= htmlspecialchars(substr($recentProgram['description'], 0, 150)) ?>...</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-y-[5px] w-full h-fit">
                                        <p class="font-semibold">Enrollees</p>
                                        <div class="flex gap-x-[10px]">
                                            <p>0</p>
                                            <p>Enrolled</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="proficiency-badge">
                                    <i class="ph-fill ph-barbell text-[15px]"></i>
                                    <p class="text-[14px]/[2em] font-semibold">
                                        <?= htmlspecialchars(ucfirst(strtolower($recentProgram['category']))); ?> Difficulty
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 flex justify-between items-center">
                            <span class="text-sm text-gray-500">
                                Last edited: <?= date('M d, Y', strtotime($recentProgram['dateUpdated'])) ?>
                            </span>
                            <div class="flex gap-2">
                                <span class="px-2 py-1 text-xs rounded-full <?= $recentProgram['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= ucfirst($recentProgram['status']) ?>
                                </span>
                                <a href="teacher-programs.php?action=create&program_id=<?= $recentProgram['programID'] ?>"
                                    class="text-blue-500 hover:text-blue-700 text-sm flex items-center">
                                    <i class="ph-pencil-simple text-[16px]"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                <?php
                } else {
                    // If no programs exist
                ?>
                    <div class="w-full text-center py-8">
                        <p class="text-gray-500">No recently edited programs found.</p>
                    </div>
                <?php
                }
                ?>
            </div>
        </section>

        <!-- 2ND Section - Quick Access Toolbar -->
        <section class="content-section">
            <div class="flex justify-between items-center mb-4">
                <h1 class="section-title text-xl md:text-2xl font-bold">Quick Access Toolbar</h1>
            </div>
            <?php include '../../components/quick-access.php'; ?>
        </section>

        <!-- 3RD Section - Analytics Overview -->
        <section class="content-section">
            <div class="flex justify-between items-center mb-4">
                <h1 class="section-title text-xl md:text-2xl font-bold">Analytics Overview</h1>
            </div>
            <div class="section-card bg-white p-4 md:p-6 rounded-lg shadow-md">
                <p class="text-gray-600">Your analytics data will appear here.</p>
                <!-- Add your analytics content here -->
            </div>
        </section>
    </div>
</div>


<!-- Back to Top button -->
<button type="button" onclick="scrollToTop()"
    class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition z-50"
    id="scroll-to-top">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
    </svg>
</button>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
<!-- JS -->
<script src="../../dist/javascript/translate.js"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../components/navbar.js"></script>
<script src="../../javascript/scroll-to-top.js"></script>
<script>
    // Scroll to top function
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
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

    // Confirmation dialogs using SweetAlert2
    function confirmAction(actionType, message) {
        Swal.fire({
            title: 'Confirm Action',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Handle the confirmed action here
                Swal.fire(
                    'Action Confirmed',
                    'Your action has been processed.',
                    'success'
                );

                // You would add your specific action handling here
                // For example: window.location.href = 'process-' + actionType + '.php';
            }
        });
    }

    // Example function for handling edit action
    function handleEdit() {
        // This would be called after confirmation
        // You can implement your edit logic here
        console.log('Edit action confirmed');
    }
</script>
</body>

</html>