<!-- Navbar -->
 <div id="google_translate_element" style="display:none;"></div>
<nav class="bg-[#F3F3FC]">
    <div class="mx-auto px-2 sm:px-6 lg:px-8">
        <div class="relative flex h-16 items-center justify-between">
            <div class="flex flex-1 items-center justify-between w-full">

                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="index.php">
                        <img class="h-8 w-auto" src="../images/al-ghaya_logoForPrint.svg" alt="Your Company">
                        <a href="index.php" class="rounded-md px-3 py-2 text-xl font-medium text-neutral-800" aria-current="page">Al-Ghaya</a>
                    </a>
                </div>

                <!-- Navigation buttons -->
                <div class="hidden lg:flex header-content justify-end mr-3 flex w-full">
                    <div class="flex space-x-4 items-center justify-center">
                        <a href="index.php" class="<?php if($current_page == 'homepage') {
                            echo 'rounded-md px-3 py-2 text-md font-medium bg-gray-700 text-white';
                        }
                        else {
                            echo 'rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                        }  ?>">Home</a>

                        <a href="about.php" class="<?php if($current_page == 'about') {
                            echo 'rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                        }
                        else {
                            echo 'rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                        }  ?>">About</a>

                        <a href="programs.php" class="<?php if($current_page == 'programs') {
                            echo 'rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                        }
                        else {
                            echo 'rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                        }  ?>">Programs</a>

                        <a href="faculty.php" class="<?php if($current_page == 'faculty') {
                            echo 'rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                        }
                        else {
                            echo 'rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                        }  ?>">Faculty</a>
                    </div>
                </div>

                <!-- Right side buttons -->
                <div class="hidden lg:flex items-center gap-3">

                    <!-- Change Language Dropdown -->
                    <div class="hidden lg:flex items-center relative z-40 mr-4">
                        <button id="lang-button"
                            class="flex items-center gap-1 px-3 py-2 rounded-lg hover:bg-gray-200 hover:cursor-pointer">
                            <i class="ph-light ph-globe text-secondary text-[24px]"></i>
                            <span id="selected-lang" class="text-secondary font-medium">EN</span>
                            <i class="ph ph-caret-down text-secondary text-[14px]"></i>
                        </button>

                        <div id="lang-dropdown"
                            class="absolute right-0 top-full mt-2 w-40 bg-white rounded-md shadow-lg py-2 hidden border border-gray-200">
                            <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="en" data-label="EN">English</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="fil" data-label="FIL">Filipino</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="ar" data-label="AR">العربية</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="ur" data-label="UR">اردو</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="id" data-label="ID">Indonesia</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="ms" data-label="MS">Melayu</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="tr" data-label="TR">Türkçe</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a class="block px-4 py-2 text-sm hover:bg-gray-100 hover:cursor-pointer" data-lang="fr" data-label="FR">Français</a>
                        </div>
                    </div>

                    <!-- Hidden Google Translate Element -->
                    <div id="google_translate_element"></div>


                    <!-- Register/Login -->
                    <a href="register.php" class="rounded-md bg-[#10375B] px-3 py-2 text-base font-medium text-white hover:bg-blue-900">Register</a>
                    <a href="login.php" class="rounded-md px-3 py-2 font-medium text-[#A58618] border border-[#A58618] hover:bg-[#A58618] hover:text-white">Login</a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:flex lg:hidden flex items-center">
                    <button id="menu-toggle" class="text-neutral-800 hover:text-gray-400 focus:outline-none hover:cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (Initially Hidden) -->
        <div id="mobile-menu" class="hidden flex flex-col items-center overflow-hidden max-h-0 transition-all duration-900 ease-in-out">
            <div class="px-2 pt-2 pb-3 space-y-2 flex flex-col items-center w-full">
                <a href="index.php" class="<?php if($current_page == 'homepage') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                }
                else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                }  ?>">Home</a>

                <a href="about.php" class="<?php if($current_page == 'about') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                }
                else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                }  ?>">About</a>

                <a href="programs.php" class="<?php if($current_page == 'programs') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                }
                else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                }  ?>">Programs</a>

                <a href="faculty.php" class="<?php if($current_page == 'faculty') {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 bg-gray-700 text-white';
                }
                else {
                    echo 'block w-full text-center rounded-md px-3 py-2 text-md font-medium text-neutral-800 hover:bg-gray-700 hover:text-white';
                }  ?>">Faculty</a>

                <div class="border-t border-gray-700 w-full my-2"></div>
                <a href="../pages/register.php" class="block w-full text-center rounded-md bg-[#10375B] px-3 py-2 text-base font-medium text-white hover:bg-blue-900">Register</a>
                <a href="../pages/login.php" class="block w-full text-center rounded-md px-3 py-2 font-medium text-neutral-800 hover:bg-gray-700 hover:text-white">Login</a>

                <!-- Mobile Language Dropdown -->
                <div class="w-full flex flex-col items-center mt-3 relative">
                    <button id="lang-btn-mobile" class="flex items-center gap-1 px-3 py-2 rounded-md hover:bg-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="#000000" viewBox="0 0 256 256">
                            <path d="M128,24h0A104,104,0,1,0,232,128,104.12,104.12,0,0,0,128,24Zm88,104a87.61,87.61,0,0,1-3.33,24H174.16a157.44,157.44,0,0,0,0-48h38.51A87.61,87.61,0,0,1,216,128ZM102,168H154a115.11,115.11,0,0,1-26,45A115.27,115.27,0,0,1,102,168Zm-3.9-16a140.84,140.84,0,0,1,0-48h59.88a140.84,140.84,0,0,1,0,48ZM40,128a87.61,87.61,0,0,1,3.33-24H81.84a157.44,157.44,0,0,0,0,48H43.33A87.61,87.61,0,0,1,40,128ZM154,88H102a115.11,115.11,0,0,1,26-45A115.27,115.27,0,0,1,154,88Zm52.33,0H170.71a135.28,135.28,0,0,0-22.3-45.6A88.29,88.29,0,0,1,206.37,88ZM107.59,42.4A135.28,135.28,0,0,0,85.29,88H49.63A88.29,88.29,0,0,1,107.59,42.4ZM49.63,168H85.29a135.28,135.28,0,0,0,22.3,45.6A88.29,88.29,0,0,1,49.63,168Zm98.78,45.6a135.28,135.28,0,0,0,22.3-45.6h35.66A88.29,88.29,0,0,1,148.41,213.6Z"></path>
                        </svg>
                        <span id="selected-lang-mobile">English</span>
                    </button>
                    <div id="lang-menu-mobile" 
                        class="hidden overflow-hidden max-h-0 w-32 bg-white border rounded-md shadow-lg z-50 mt-2 transition-all duration-500 ease-in-out">
                        <button class="block w-full text-left px-4 py-2 hover:bg-gray-100" data-lang="en">English</button>
                        <button class="block w-full text-left px-4 py-2 hover:bg-gray-100" data-lang="fil">Filipino</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<script type="text/javascript">
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',         // your default site language
        includedLanguages: 'en,fil',// supported languages
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE
    }, 'google_translate_element');
}
</script>

<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>