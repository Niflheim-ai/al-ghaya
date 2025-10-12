<!-- Navbar -->
<nav class="bg-secondary flex flex-grow items-center justify-center sticky top-0 z-50">
    <div class="px-[50px] py-[20px] sm:px-6 lg:px-8 max-w-[1200px] w-full">
        <div class="relative gap-[25px] flex flex-1 items-center w-full">

            <!-- Logo -->
            <div class="flex shrink-0">
                <a href="index.php">
                    <img class="w-[50px] h-auto" src="../../images/Al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                </a>
            </div>

            <!-- Navigation buttons -->
            <div class="hidden lg:flex justify-start gap-[20px] w-full">
                <a href="../../pages/admin/admin-dashboard.php" class="<?php if ($current_page == 'admin-home') {
                                                            echo 'group menu-item-active-admin flex items-center';
                                                        } else {
                                                            echo 'group menu-item-inactive-admin flex items-center';
                                                        } ?>">
                    <i class="ph ph-address-book-tabs text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-address-book-tabs text-[24px] hidden group-hover:block"></i>
                    <p>My Dashboard</p>
                </a>

                <a href="../../pages/admin/admin-students.php" class="<?php if ($current_page == 'admin-students') {
                                                            echo 'group menu-item-active-admin flex items-center';
                                                        } else {
                                                            echo 'group menu-item-inactive-admin flex items-center';
                                                        } ?>">
                    <i class="ph ph-student text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-student text-[24px] hidden group-hover:block"></i>
                    <p>Students</p>
                </a>

                <a href="../../pages/admin/admin-faculty.php" class="<?php if ($current_page == 'admin-faculty') {
                                                            echo 'group menu-item-active-admin flex items-center';
                                                        } else {
                                                            echo 'group menu-item-inactive-admin flex items-center';
                                                        } ?>">
                    <i class="ph ph-chalkboard-simple text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-chalkboard-simple text-[24px] hidden group-hover:block"></i>
                    <p>Faculty</p>
                </a>

                <a href="../../pages/admin/admin-analytics.php" class="<?php if ($current_page == 'admin-analytics') {
                                                                echo 'group menu-item-active-admin flex items-center';
                                                            } else {
                                                                echo 'group menu-item-inactive-admin flex items-center';
                                                            } ?>">
                    <i class="ph ph-presentation-chart text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-presentation-chart text-[24px] hidden group-hover:block"></i>
                    <p>Analytics</p>
                </a>
            </div>

            <div class="flex items-center">
                <button id="profile-menu-button" class="flex items-center focus:outline-none hover:cursor-pointer">
                    <a href="../../pages/admin/admin-profile.php">
                        <img class="aspect-1/1 w-12 rounded-full border-2 border-company_white bg-company_white" 
                        src="<?= htmlspecialchars($_SESSION['user_avatar'] ?? '../../images/male.svg') ?>" alt="Avatar">
                    </a>
                </button>
            </div>
        </div>
    </div>
</nav>