<?php
session_start();
include('../../php/dbConnection.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$current_page = "admin-analytics";
$page_title = "System Analytics";
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<div class="page-container">
    <div class="page-content">
        <!-- 1ST SECTION: Charts library template -->
        <section class="content-section">
            <h1 class="section-title">System Analytics</h1>
            <div class="w-full flex gap-[10px]">
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
                    <div class="text-secondary flex items-center gap-[10px]">
                        <i class="ph-duotone ph-notebook text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Total # of Programs</p>
                </div>
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
                    <div class="text-tertiary flex items-center gap-[10px]">
                        <i class="ph-duotone ph-chalkboard-simple text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Total # of Teachers</p>
                </div>
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
                    <div class="text-tertiary flex items-center gap-[10px]">
                        <i class="ph-duotone ph-student text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Total # of Students</p>
                </div>
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
                    <div class="text-company_green flex items-center gap-[10px]">
                        <i class="ph-duotone ph-receipt text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Total # of Transactions</p>
                </div>
            </div>
        </section>
        <section class="content-section">
            <h1 class="section-title">Enrollees per Program</h1>
            <div class="w-full flex gap-[10px]">
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
                    <p class="arabic font-semibold">Program Name</p>
                    <div class="text-tertiary flex items-center gap-[10px]">
                        <i class="ph-duotone ph-users text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Total # of Enrollees</p>
                </div>
            </div>
        </section>
        <section class="content-section">
            <div class="section-card">
                <!-- Placeholder / template area where chart library will render -->
                <div id="charts-library"
                    class="w-full h-[360px] bg-primary/5 border border-primary/10 rounded-[10px] flex items-center justify-center">
                    <p class="text-company_grey">Chart placeholder — library will render here</p>
                </div>
            </div>
        </section>
        <section class="content-section">
            <h1 class="section-title">Transactions Table</h1>
            <div class="section-card flex-col">
                <div class="w-full flex items-center justify-between mt-[16px]">
                    <!-- Left: Sort controls -->
                    <div class="flex flex-col items-start gap-[20px]">
                        <div class="flex gap-[10px] items-center">
                            <i class="ph ph-arrows-down-up text-[24px]"></i>
                            <p class="body-text2-semibold">Sort</p>
                        </div>
                        <div class="flex gap-[10px] items-center">
                            <label class="inline-flex items-center">
                                <input type="radio" name="transactions-sort" value="recent" class="form-radio h-4 w-4" checked>
                                <span class="ml-2">Recent</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="transactions-sort" value="ascending" class="form-radio h-4 w-4">
                                <span class="ml-2">Ascending</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="transactions-sort" value="descending" class="form-radio h-4 w-4">
                                <span class="ml-2">Descending</span>
                            </label>
                        </div>
                    </div>
                    <!-- Right: Download button -->
                    <div>
                        <button type="button" class="group btn-secondary">
                            <i class="ph ph-download text-[20px] group-hover:hidden"></i>
                            <i class="ph-duotone ph-download text-[20px] hidden group-hover:block"></i>
                            <p class="font-medium">Download Transaction Report</p>
                        </button>
                    </div>
                </div>
                <!-- Optional: search / table placeholders -->
                <div>
                    <div class="w-full flex items-center gap-[10px]">
                        <i class="ph ph-magnifying-glass text-[30px]"></i>
                        <input type="text" placeholder="Program Name" class="w-[500px] h-[40px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary">
                    </div>
                    <div class="w-full h-[220px] bg-primary/5 border border-primary/10 rounded-[10px] flex items-center justify-center">
                        <p class="text-company_grey">Transaction table placeholder</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
</body>

</html>