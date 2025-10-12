<?php
$current_page = "admin-faculty";
$page_title = "Faculty Management";
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<div class="page-container">
    <div class="page-content">
        <!-- 1ST SECTION: Charts library template -->
        <section class="content-section">
            <h1 class="section-title">Faculty Management</h1>
            <div class="w-full flex gap-[10px]">
                <!-- Add Faculty Account -->
                <button type="button" class="group flex flex-grow p-[25px] gap-[10px] rounded-[10px] text-company_white bg-secondary flex flex-col items-center justify-center hover:bg-company_black transition-all duration-200">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph ph-user-plus text-[40px] group-hover:hidden"></i>
                        <i class="ph-duotone ph-user-plus text-[40px] hidden group-hover:block"></i>
                    </div>
                    <p>Add Faculty Account</p>
                </button>

                <!-- Total Teachers -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
                    <div class="text-tertiary flex items-center gap-[10px]">
                        <i class="ph-duotone ph-chalkboard-simple text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Total # of Teachers</p>
                </div>

                <!-- Publish Requests -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_green flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-seal-check text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Publish Requests</p>
                </div>

                <!-- Update Requests -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_orange flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-traffic-cone text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Update Requests</p>
                </div>

                <!-- Archived Teachers -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_red flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-archive text-[40px]"></i>
                        <p class="sub-header">00</p>
                    </div>
                    <p>Archived Teachers</p>
                </div>
            </div>
            <!-- 2ND SECTION: Recent Transactions with Sort and Download -->
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
                            <p class="font-medium">Download Faculty Records</p>
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
                        <p class="text-company_grey">Records table placeholder</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
</body>
</html>