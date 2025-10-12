<!-- Navbar -->
<nav class="bg-company_white flex flex-grow items-center justify-center sticky top-0 z-50">
    <div class="px-[50px] py-[20px] sm:px-6 lg:px-8 max-w-[1200px] w-full">
        <div class="relative gap-[25px] flex flex-1 items-center w-full">

            <!-- Logo -->
            <div class="flex shrink-0">
                <a href="../../pages/index.php">
                    <img class="w-[50px] h-auto" src="../../images/Al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                </a>
            </div>

            <!-- Navigation buttons -->
            <div class="hidden lg:flex justify-start gap-[20px] w-full">
                <a href="../student/student-dashboard.php" class="<?php if ($current_page == 'student-dashboard') {
                                                            echo 'group menu-item-active flex items-center';
                                                        } else {
                                                            echo 'group menu-item-inactive flex items-center';
                                                        } ?>">
                    <i class="ph ph-address-book-tabs text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-address-book-tabs text-[24px] hidden group-hover:block"></i>
                    <p>My Dashboard</p>
                </a>

                <a href="../student/student-programs.php" class="<?php if ($current_page == 'student-programs') {
                                                            echo 'group menu-item-active flex items-center';
                                                        } else {
                                                            echo 'group menu-item-inactive flex items-center';
                                                        } ?>">
                    <i class="ph ph-notebook text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-notebook text-[24px] hidden group-hover:block"></i>
                    <p>Programs</p>
                </a>

                <a href="../student/student-transactions.php" class="<?php if ($current_page == 'student-transactions') {
                                                                echo 'group menu-item-active flex items-center';
                                                            } else {
                                                                echo 'group menu-item-inactive flex items-center';
                                                            } ?>">
                    <i class="ph ph-receipt text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-receipt text-[24px] hidden group-hover:block"></i>
                    <p>Transactions</p>
                </a>
            </div>

            <div class="flex items-center">
                <button id="profile-menu-button" class="flex items-center focus:outline-none hover:cursor-pointer">
                    <a href="../student/student-profile.php">
                        <img class="aspect-1/1 w-12 rounded-full border-2 border-secondary" 
                        src="<?= $_SESSION['user_avatar'] ?>" alt="Avatar">
                    </a>
                </button>
            </div>
        </div>
    </div>
</nav>