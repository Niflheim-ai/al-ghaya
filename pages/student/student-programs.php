<?php
session_start();
require '../../php/dbConnection.php';
require_once '../../php/functions.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$current_page = "student-programs";
$page_title = "Programs";
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<!-- Page Container -->
<div class="page-container">
    <div class="page-content max-w-7xl mx-auto w-full sm:px-6 lg:px-8">
        <!-- 1ST Section -->
        <section class="content-section py-4 px-0 sm:px-4">
            <?php include '../../components/student-program-tabs.php'; ?>

            <h1 class="section-title text-xl sm:text-2xl mb-4 font-bold">
                <?= isset($_GET['tab']) && $_GET['tab'] === 'all' ? 'All Programs' : 'Enrolled Programs' ?>
            </h1>

            <div class="section-card flex flex-col gap-4 lg:gap-6 rounded-lg bg-white shadow w-full">
                <!-- Filters -->
                <form method="GET" action="" class="w-full flex flex-col gap-4 sm:gap-6">
                    <div class="w-full flex flex-col gap-4 sm:gap-6">
                        <input type="hidden" name="tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : 'my' ?>">

                        <div class="w-full flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-8">
                            <div class="flex gap-2 items-center mb-1 sm:mb-0">
                                <i class="ph ph-funnel-simple text-[24px]"></i>
                                <p class="font-semibold">Filters</p>
                            </div>
                            <button type="button" onclick="window.location.href='?tab=<?= isset($_GET['tab']) ? $_GET['tab'] : 'my' ?>'" class="btn-grey">
                                Clear Filters
                            </button>
                        </div>

                        <!-- Difficulty -->
                        <div class="w-full flex flex-col gap-2 sm:gap-6">
                            <div>
                                <p class="font-semibold">Difficulty</p>
                                <div class="flex flex-wrap gap-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="difficulty" value="all" class="form-radio h-5 w-5 text-blue-600" <?= (!isset($_GET['difficulty']) || $_GET['difficulty'] === 'all') ? 'checked' : '' ?>>
                                        <span class="ml-2">All</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="difficulty" value="beginner" class="form-radio h-5 w-5 text-blue-600" <?= (isset($_GET['difficulty']) && $_GET['difficulty'] === 'beginner') ? 'checked' : '' ?>>
                                        <span class="ml-2">Beginner</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="difficulty" value="intermediate" class="form-radio h-5 w-5 text-blue-600" <?= (isset($_GET['difficulty']) && $_GET['difficulty'] === 'intermediate') ? 'checked' : '' ?>>
                                        <span class="ml-2">Intermediate</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="difficulty" value="advanced" class="form-radio h-5 w-5 text-blue-600" <?= (isset($_GET['difficulty']) && $_GET['difficulty'] === 'advanced') ? 'checked' : '' ?>>
                                        <span class="ml-2">Advanced</span>
                                    </label>
                                </div>
                            </div>

                            <?php if (!isset($_GET['tab']) || $_GET['tab'] !== 'all'): ?>
                            <div>
                                <p class="font-semibold">Status</p>
                                <div class="flex flex-wrap gap-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="status" value="all" class="form-radio h-5 w-5 text-blue-600" <?= (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'checked' : '' ?>>
                                        <span class="ml-2">All</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="status" value="in-progress" class="form-radio h-5 w-5 text-blue-600" <?= (isset($_GET['status']) && $_GET['status'] === 'in-progress') ? 'checked' : '' ?>>
                                        <span class="ml-2">In Progress</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="status" value="completed" class="form-radio h-5 w-5 text-blue-600" <?= (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'checked' : '' ?>>
                                        <span class="ml-2">Completed</span>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Search Bar -->
                        <div class="w-full flex flex-col sm:flex-row items-center justify-center gap-2 mt-2 mb-2">
                            <input type="text" name="search" placeholder="Program Name"
                                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                                   class="w-full sm:w-[320px] h-[40px] border border-company_black rounded-[10px] p-3 focus:outline-offset-2 focus:accent-tertiary">
                            <button type="submit" class="text-white bg-blue-800 p-2 rounded-md hover:bg-blue-600 hover:cursor-pointer transition-colors flex items-center justify-center">
                                <i class="ph ph-magnifying-glass text-[26px]"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <!-- Cards -->
                <div class="w-full">
                    <?php include '../../components/student-cards-template.php'; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Back to Top button -->
<button type="button" onclick="scrollToTop()"
    class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer z-50"
    id="scroll-to-top">
    <img src="https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png"
        class="w-10 h-10 rounded-full bg-white" alt="">
</button>

<?php include '../../components/footer.php'; ?>

<!-- Auto-submit filters when radio buttons change -->
<script>
// Auto-submit form when difficulty or status changes
document.querySelectorAll('input[name="difficulty"], input[name="status"]').forEach(radio => {
    radio.addEventListener('change', function() {
        this.form.submit();
    });
});
// Submit on Enter key in search box
document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.form.submit();
    }
});
</script>

<script src="../../dist/javascript/scroll-to-top.js"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<!-- <script src="../../dist/javascript/translate.js"></script> -->
</body>
</html>
