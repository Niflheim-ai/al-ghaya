<?php
session_start();
require '../../php/dbConnection.php';
require_once '../../php/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$current_page = "student-programs";
$page_title = "Programs";
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<!-- David (Latest Edits) -->
<div class="page-container">
    <div class="page-content">
        <!-- 1ST Section -->
        <section class="content-section">
            <?php include '../../components/student-program-tabs.php'; ?>
            <h1 class="section-title">
                <?= isset($_GET['tab']) && $_GET['tab'] === 'all' ? 'All Programs' : 'Enrolled Programs' ?>
            </h1>
            <div class="section-card flex-col">
                <!-- Filters -->
                <form method="GET" action="" class="w-full flex flex-col gap-[25px]">
                    <div class="w-full flex flex-col gap-[10px]">
                        <input type="hidden" name="tab" value="<?= isset($_GET['tab']) ? $_GET['tab'] : 'my' ?>">
                        <div class="w-full flex items-center justify-start gap-[25px]">
                            <div class="flex gap-[10px] items-center">
                                <i class="ph ph-funnel-simple text-[24px]"></i>
                                <p class="font-semibold">Filters</p>
                            </div>
                            <button type="reset" class="btn-grey">Clear Filters</button>
                        </div>
                        <div class="w-full h-auto flex flex-col gap-[10px]">
                            <p class="font-semibold">Difficulty</p>
                            <div class="flex gap-[10px]">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="difficulty" value="all" class="form-radio h-5 w-5 text-blue-600" <?= (!isset($_GET['difficulty']) || $_GET['difficulty'] === 'all') ? 'checked' : '' ?>>
                                    <span class="ml-2">All</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="difficulty" value="Beginner" class="form-radio h-5 w-5 text-blue-600" <?= (isset($_GET['difficulty']) && $_GET['difficulty'] === 'Beginner') ? 'checked' : '' ?>>
                                    <span class="ml-2">Beginner</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="difficulty" value="Intermediate" class="form-radio h-5 w-5 text-blue-600" <?= (isset($_GET['difficulty']) && $_GET['difficulty'] === 'Intermediate') ? 'checked' : '' ?>>
                                    <span class="ml-2">Intermediate</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="difficulty" value="Advanced" class="form-radio h-5 w-5 text-blue-600" <?= (isset($_GET['difficulty']) && $_GET['difficulty'] === 'Advanced') ? 'checked' : '' ?>>
                                    <span class="ml-2">Advanced</span>
                                </label>
                            </div>
                            <p class="font-semibold">Status</p>
                            <div class="flex gap-[10px]">
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
                    </div>
                    <!-- Search Bar -->
                    <div class="w-full flex items-center justify-center gap-[10px] mt-4">
                        <!-- <button type="submit" class="btn-secondary"><i class="ph ph-magnifying-glass text-[16px]"></i> Search</button> -->
                        <button type="submit">
                            <i class="ph ph-magnifying-glass text-[30px]"></i>
                        </button>
                        <input type="text" name="search" placeholder="Program Name"
                            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                            class="w-[500px] h-[40px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary">
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
    class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer"
    id="scroll-to-top">
    <img src="https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png"
        class="w-10 h-10 rounded-full bg-white" alt="">
</button>

<?php include '../../components/footer.php'; ?>

<!-- Script paths fixed -->
<script src="../../dist/javascript/scroll-to-top.js"></script>
<script src="../../dist/javascript/carousel.js"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../dist/javascript/translate.js"></script>

</body>

</html>
