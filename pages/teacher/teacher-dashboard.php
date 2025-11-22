<?php
session_start();
$current_page = "teacher-dashboard";
$page_title = "Faculty Dashboard";

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

require_once '../../php/dbConnection.php';
require_once '../../php/functions.php';
require_once '../../php/program-core.php';

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
                // Get the proper teacher ID from the teacher table
                $user_id = $_SESSION['userID'];
                $teacher_id = getTeacherIdFromSession($conn, $user_id);
                
                // Function to get status badge with text and color
                function getStatusBadge($status) {
                    switch (strtolower($status)) {
                        case 'published':
                            return '<span class="inline-flex items-center px-3 py-1 text-sm font-semibold text-green-800 bg-green-100 rounded-full border border-green-200"><i class="ph-check-circle-fill mr-1.5 text-green-600"></i>Published</span>';
                        case 'draft':
                            return '<span class="inline-flex items-center px-3 py-1 text-sm font-semibold text-yellow-800 bg-yellow-100 rounded-full border border-yellow-200"><i class="ph-file-dashed-fill mr-1.5 text-yellow-600"></i>Draft</span>';
                        case 'archived':
                            return '<span class="inline-flex items-center px-3 py-1 text-sm font-semibold text-gray-800 bg-gray-100 rounded-full border border-gray-200"><i class="ph-archive-fill mr-1.5 text-gray-600"></i>Archived</span>';
                        case 'pending_review':
                            return '<span class="inline-flex items-center px-3 py-1 text-sm font-semibold text-blue-800 bg-blue-100 rounded-full border border-blue-200"><i class="ph-clock-fill mr-1.5 text-blue-600"></i>Pending Review</span>';
                        case 'rejected':
                            return '<span class="inline-flex items-center px-3 py-1 text-sm font-semibold text-red-800 bg-red-100 rounded-full border border-red-200"><i class="ph-x-circle-fill mr-1.5 text-red-600"></i>Rejected</span>';
                        default:
                            return '<span class="inline-flex items-center px-3 py-1 text-sm font-semibold text-gray-800 bg-gray-100 rounded-full border border-gray-200"><i class="ph-question-fill mr-1.5 text-gray-600"></i>' . ucfirst($status) . '</span>';
                    }
                }
                
                if ($teacher_id) {
                    // Fetch teacher's programs sorted by dateUpdated
                    $programs = getTeacherPrograms($conn, $teacher_id, 'dateUpdated');

                    if (!empty($programs)) {
                        // Display the most recent program
                        $recentProgram = $programs[0];
                ?>
                        <div class="min-w-[345px] min-h-[300px] rounded-[20px] w-full h-fit bg-company_white border-[1px] border-primary relative">
                            <div class="w-full h-fit overflow-hidden rounded-[20px] flex flex-wrap">
                                <!-- Image -->
                                <img src="<?= !empty($recentProgram['thumbnail']) ? '../../uploads/thumbnails/' . $recentProgram['thumbnail'] : '../../images/blog-bg.svg' ?>"
                                    alt="Program Image" class="w-[221px] h-[500px] object-cover flex-grow flex-shrink-0 basis-1/4">
                                <!-- Content -->
                                <div class="overflow-hidden p-[30px] h-fit min-h-[300px] flex-grow flex-shrink-0 basis-3/4 flex flex-col gap-y-[25px]">
                                    <!-- Status Badge -->
                                    <div class="flex justify-between items-start">
                                        <h2 class="price-sub-header">₱<?= number_format($recentProgram['price'], 2) ?></h2>
                                        <?= getStatusBadge($recentProgram['status']) ?>
                                    </div>
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
                                    <a href="teacher-programs.php?action=create&program_id=<?= $recentProgram['programID'] ?>"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                        <i class="ph-pencil-simple text-[16px] mr-1.5"></i> Edit Program
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
                            <div class="mt-4">
                                <a href="teacher-programs.php?action=create" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                    <i class="ph-plus text-[16px] mr-2"></i>
                                    Create Your First Program
                                </a>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    // If teacher profile not found
                ?>
                    <div class="w-full text-center py-8">
                        <p class="text-red-500">Teacher profile not found. Please contact administrator.</p>
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
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?= count($programs ?? []) ?></div>
                        <div class="text-sm text-gray-600">Total Programs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?= isset($programs) ? count(array_filter($programs, function($p) { return $p['status'] === 'published'; })) : 0 ?></div>
                        <div class="text-sm text-gray-600">Published</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600"><?= isset($programs) ? count(array_filter($programs, function($p) { return $p['status'] === 'draft'; })) : 0 ?></div>
                        <div class="text-sm text-gray-600">Drafts</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600"><?= isset($programs) ? count(array_filter($programs, function($p) { return $p['status'] === 'archived'; })) : 0 ?></div>
                        <div class="text-sm text-gray-600">Archived</div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (isset($programs) && !empty($programs) && count($programs) > 1): ?>
        <!-- 4TH Section - All Programs -->
        <section class="content-section">
            <div class="flex justify-between items-center mb-4">
                <h1 class="section-title text-xl md:text-2xl font-bold">All Programs</h1>
                <a href="teacher-programs.php" class="text-blue-500 hover:text-blue-700 text-sm flex items-center">
                    View All <i class="ph-arrow-right text-[16px] ml-1"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach (array_slice($programs, 0, 6) as $program): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <img src="<?= !empty($program['thumbnail']) ? '../../uploads/thumbnails/' . $program['thumbnail'] : '../../images/blog-bg.svg' ?>"
                                alt="<?= htmlspecialchars($program['title']) ?>" class="w-full h-32 object-cover">
                            <!-- Status Badge on Image -->
                            <div class="absolute top-2 right-2">
                                <?= getStatusBadge($program['status']) ?>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2 line-clamp-2"><?= htmlspecialchars($program['title']) ?></h3>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-3"><?= htmlspecialchars(substr($program['description'], 0, 100)) ?>...</p>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-lg font-bold text-blue-600">₱<?= number_format($program['price'], 2) ?></span>
                                <span class="text-xs text-gray-500">
                                    <?= ucfirst($program['category']) ?> Level
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">
                                    Updated: <?= date('M d', strtotime($program['dateUpdated'])) ?>
                                </span>
                                <a href="teacher-programs.php?action=create&program_id=<?= $program['programID'] ?>"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 transition-colors">
                                    <i class="ph-pencil-simple text-[12px] mr-1"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
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
<script src="../../dist/javascript/scroll-to-top.js"></script>
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
<?php include '../../components/footer.php'; ?>
</body>

</html>