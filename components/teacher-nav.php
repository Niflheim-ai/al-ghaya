<!-- Navbar -->
<nav class="bg-company_white flex flex-grow items-center justify-center sticky top-0 z-50">
    <div class="px-[50px] py-[20px] sm:px-6 lg:px-8 max-w-[1200px] w-full">
        <div class="relative gap-[25px] flex flex-1 items-center w-full">

            <!-- Logo -->
            <div class="flex shrink-0">
                <a href="teacher-dashboard.php">
                    <img class="w-[50px] h-auto" src="../../images/Al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                </a>
            </div>

            <!-- Navigation buttons -->
            <div class="hidden lg:flex justify-start gap-[20px] w-full">
                <a href="teacher-dashboard.php" class="<?php if ($current_page == 'teacher-dashboard') {
                    echo 'group menu-item-active flex items-center';
                } else {
                    echo 'group menu-item-inactive flex items-center';
                } ?>">
                    <i class="ph ph-address-book-tabs text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-address-book-tabs text-[24px] hidden group-hover:block"></i>
                    <p>My Dashboard</p>
                </a>

                <a href="teacher-programs.php" class="<?php if ($current_page == 'teacher-programs') {
                    echo 'group menu-item-active flex items-center';
                } else {
                    echo 'group menu-item-inactive flex items-center';
                } ?>">
                    <i class="ph ph-notebook text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-notebook text-[24px] hidden group-hover:block"></i>
                    <p>Programs</p>
                </a>

                <a href="teacher-analytics.php" class="<?php if ($current_page == 'teacher-analytics') {
                    echo 'group menu-item-active flex items-center';
                } else {
                    echo 'group menu-item-inactive flex items-center';
                } ?>">
                    <i class="ph ph-receipt text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-receipt text-[24px] hidden group-hover:block"></i>
                    <p>Analytics</p>
                </a>
            </div>

            <div class="flex items-center">
                <!-- Change Language Dropdown -->
                <div class="hidden lg:flex items-center relative z-50 mr-3">
                    <!-- Language Button -->
                    <button id="lang-button"
                        class="flex items-center gap-1 px-3 py-2 rounded-lg hover:bg-gray-200 hover:cursor-pointer">
                        <i class="ph-light ph-globe text-secondary text-[30px]"></i>
                        <span id="selected-lang" class="text-secondary font-semibold">English</span>
                    </button>

                    <!-- Dropdown Menu (hidden by default) -->
                    <div id="lang-dropdown"
                        class="absolute right-0 top-full mt-2 w-48 bg-white rounded-md shadow-lg py-2 hidden">
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer"
                            data-lang="en">English</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer"
                            data-lang="fil">Filipino</a>
                    </div>
                </div>

                <!-- Login/User Profile (Desktop Only) -->
                <div class="hidden lg:flex items-center relative">
                    <!-- Profile Button -->
                    <button id="profile-menu-button" class="flex items-center focus:outline-none hover:cursor-pointer">
                        <i class="ph ph-gear text-secondary text-[30px]"></i>
                    </button>

                    <!-- Dropdown Menu (hidden by default) -->
                    <div id="profile-dropdown"
                        class="absolute right-0 top-full mt-2 w-48 bg-white rounded-md shadow-lg py-2 hidden">
                        <a href="student-profile.php" class="block px-4 py-2 text-sm hover:bg-gray-100">My Profile</a>
                        <a href="student-settings.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Settings</a>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a href="../../php/signout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-100">Sign
                            Out</a>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:flex lg:hidden flex items-center">
                <button id="menu-toggle"
                    class="text-neutral-800 hover:text-gray-400 focus:outline-none hover:cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </div>
        </div>


        <!-- Mobile Menu (Initially Hidden) -->
        <div id="mobile-menu"
            class="hidden flex flex-col items-center overflow-hidden max-h-0 transition-all duration-900 ease-in-out">
            <div class="px-2 pt-2 pb-3 space-y-2 flex flex-col items-center w-full">
                <a href="student-dashboard.php" class="<?php if ($current_page == 'teacher-dashboard') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                } ?>">My Dashboard</a>

                <a href="programs.php" class="<?php if ($current_page == 'teacher-programs') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                } ?>">Programs</a>

                <a href="student-transactions.php" class="<?php if ($current_page == 'teacher-transactions') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                } else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                } ?>">Analytics</a>
                <div class="border-t border-gray-700 w-full my-2"></div>
                <a href="../../php/signout.php"
                    class="block w-full text-center rounded-md bg-[#10375B] px-3 py-2 text-base font-medium text-white hover:bg-blue-900">Sign
                    Out</a>
            </div>
        </div>
    </div>
</nav>