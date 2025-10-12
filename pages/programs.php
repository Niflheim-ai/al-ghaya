<?php
    session_start();
    $current_page = "programs";
    $page_title = 'programs';

    if (isset($_SESSION['userID']) && isset($_SESSION['role'])) {
        // Redirect based on user role
        switch ($_SESSION['role']) {
            case 'student':
                header("Location: student/student-dashboard.php");
                break;
            case 'teacher':
                header("Location: teacher/teacher-dashboard.php");
                break;
            case 'admin':
                header("Location: admin/admin-dashboard.php");
                break;
        }
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Swiper JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css"/>
    <!-- Tailwind -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <!-- CSS -->
    <link rel="stylesheet" href="../dist/css/programs.css">
    <link rel="icon" type="image/x-icon" href="../images/Al-ghaya_logoForPrint.svg">
    <title>Al-Ghaya - Programs</title>

    <style>
        @font-face {
            font-family: 'Winky Rough';
            src: url('../assets/fonts/WinkyRough-Regular.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        .font-winky {
            font-family: 'Winky Rough', sans-serif;
        }
    </style>
</head>
<body class="">
    <!-- Navbar -->
    <?php include '../components/nav-component.php'; ?>

    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-neutral-800 via-transparent to-neutral-800 opacity-45 dark:via-transparent"/>
    
    <!-- Title -->
    <div id="contact" class="container mx-auto flex flex-col lg:flex-row items-center py-15 lg:px-10">
        <!-- Left Illustration -->
        <div class="flex hidden lg:flex justify-center lg:block">
            <img src="../images/explain.svg" alt="Illustration" class="lg:w-50">
        </div>

        <!-- Text Container -->
        <div class="text-container px-5 py-5 lg:py-20 max-w-screen mx-auto text-center">
            <p class="text-5xl lg:text-6xl font-bold text-gray-900">We support <span class="text-[#A58618]">all</span> Proficiency level</p>
        </div>

        <!-- Right Illustration -->
        <div class="flex hidden lg:flex justify-center lg:block">
            <img src="../images/half-left.svg" alt="Illustration" class="lg:w-50">
        </div>
    </div>

    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Programs Section -->
    <section>
        <!-- Beginner -->
        <div class="flex flex-col container mx-auto px-4 mt-10">
            <!-- Title -->
            <div class="text-container text-center mx-auto lg:mx-0 flex bg-[#F3F3FC] shadow-xl max-w-fit p-2 px-6">
                <p class="text-2xl text-[#10375B] font-bold font-winky">Beginner</p>
            </div>

            <!-- Card Grid -->
            <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-6 mt-10 w-full max-w-none mx-auto">
            <!-- Card 1 -->
            <a href="program-select.php?title=hadith&category=beginner" class="group relative rounded-lg overflow-hidden">
                <img src="../images/hadith-sub.png" alt="Hadith" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Hadith</h3>
                    <p class="text-sm max-w-xs">Hadith are the recorded accounts of the sayings, actions, silent approvals, and physical descriptions of the Prophet Muhammad (peace be upon him)...</p>
                </div>
            </a>

            <!-- Card 2 -->
            <a href="program-select.php?title=seerah&category=beginner" class="group relative rounded-lg overflow-hidden">
                <img src="../images/seerah-sub.png" alt="Seerah" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Seerah</h3>
                    <p class="text-sm max-w-xs">Seerah is the biography of the Prophet Muhammad ﷺ, covering his birth, prophethood, and life events.</p>
                </div>
            </a>

            <!-- Card 3 -->
            <a href="program-select.php?title=lugha-arabia&category=beginner" class="group relative rounded-lg overflow-hidden">
                <img src="../images/lugha-arabia-sub.png" alt="Lugha Arabia" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Lugha Arabia</h3>
                    <p class="text-sm max-w-xs">Study of the Arabic language structure, grammar, and vocabulary as used in the Quran.</p>
                </div>
            </a>
            </div>
        </div>

        <!-- Intermediate -->
        <div class="flex flex-col container mx-auto px-4 mt-10">
            <!-- Title -->
            <div class="text-container text-center mx-auto lg:mx-0 flex bg-[#F3F3FC] shadow-xl max-w-fit p-2 px-6">
                <p class="text-2xl text-[#10375B] font-bold font-winky">Intermediate</p>
            </div>

            <!-- Card Grid -->
            <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-6 mt-10 w-full max-w-none mx-auto">
            <!-- Card 1 -->
            <a href="program-select.php?title=adeeqah&category=intermediate" class="group relative rounded-lg overflow-hidden">
                <img src="../images/sub4.png" alt="adeeqah" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Adeeqah</h3>
                    <p class="text-sm max-w-xs">Hadith are the recorded accounts of the sayings, actions, silent approvals, and physical descriptions of the Prophet Muhammad (peace be upon him)...</p>
                </div>
            </a>

            <!-- Card 2 -->
            <a href="program-select.php?title=fiqh&category=intermediate" class="group relative rounded-lg overflow-hidden">
                <img src="../images/sub5.png" alt="Fiqh" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Fiqh</h3>
                    <p class="text-sm max-w-xs">Seerah is the biography of the Prophet Muhammad ﷺ, covering his birth, prophethood, and life events.</p>
                </div>
            </a>

            <!-- Card 3 -->
            <a href="program-select.php?title=tafsir&category=intermediate" class="group relative rounded-lg overflow-hidden">
                <img src="../images/quoran.jpg" alt="Tafsir" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Tafsir</h3>
                    <p class="text-sm max-w-xs">Study of the Arabic language structure, grammar, and vocabulary as used in the Quran.</p>
                </div>
            </a>
            </div>
        </div>

        <!-- Advanced -->
        <div class="flex flex-col container mx-auto px-4 mt-10">
            <!-- Title -->
            <div class="text-container text-center mx-auto lg:mx-0 flex bg-[#F3F3FC] shadow-xl max-w-fit p-2 px-6">
                <p class="text-2xl text-[#10375B] font-bold font-winky">Advanced</p>
            </div>

            <!-- Card Grid -->
            <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-3 gap-6 mt-10 w-full max-w-none mx-auto">
            <!-- Card 1 -->
            <a href="program-select.php?title=tafsir&category=advanced" class="group relative rounded-lg overflow-hidden">
                <img src="../images/sub7.png" alt="Tafsir - Mastery" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Tafsir - Mastery</h3>
                    <p class="text-sm max-w-xs">Hadith are the recorded accounts of the sayings, actions, silent approvals, and physical descriptions of the Prophet Muhammad (peace be upon him)...</p>
                </div>
            </a>

            <!-- Card 2 -->
            <a href="program-select.php?title=tajweed&category=advanced" class="group relative rounded-lg overflow-hidden">
                <img src="../images/sub8.png" alt="Tajweed" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Tajweed</h3>
                    <p class="text-sm max-w-xs">Seerah is the biography of the Prophet Muhammad ﷺ, covering his birth, prophethood, and life events.</p>
                </div>
            </a>

            <!-- Card 3 -->
            <a href="program-select.php?title=adeeqah&category=advanced" class="group relative rounded-lg overflow-hidden">
                <img src="../images/sub9.png" alt="Adeeqah - Mastery" class="w-full h-60 object-cover group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-[#11365C]/80 to-transparent group-hover:from-[#11365C] transition-all duration-300"></div>
                <div class="absolute bottom-4 left-4 text-white">
                    <h3 class="text-lg font-bold">Adeeqah - Mastery</h3>
                    <p class="text-sm max-w-xs">Study of the Arabic language structure, grammar, and vocabulary as used in the Quran.</p>
                </div>
            </a>
            </div>
        </div>
    </section>

    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Description Box -->
     <div class="container description-box mx-auto mt-20 mb-10 w-[60%] bg-[#E7EBEF] rounded-[20px] border border-[#10375B]">
        <p class="relative text-[#10375B] p-8">
            Our programs, which adapt content from traditional books and lessons, are crafted into engaging stories because research shows that 
            storytelling is a highly effective learning tool. Storytelling is a transformative method that significantly improves learning experiences by 
            emphasizing engagement, personalization, and relevance to learners. It provides context and meaning, making the material more relatable and fostering interaction.
        </p>
     </div>

     <div class="container button-container flex mx-auto mb-15">
        <a href="all-programs.php" class="mx-auto">
            <button class="relative mx-auto inline-flex gap-2 items-center py-2 px-4 bg-white rounded-lg text-[#A58618] cursor-pointer border border-[#A58618] hover:bg-[#A58618] hover:text-white hover:fill-white">
                <svg fill="#A58618" class="fill-current stroke-current" width="30px" height="30px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" stroke="#A58618"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>book-bookmark</title> <path d="M27.75 2c-0-0.414-0.336-0.75-0.75-0.75h-4.963c-0.020-0.002-0.037-0.012-0.058-0.012h-7.957c-0.021 0-0.038 0.010-0.058 0.012h-6.763c-0.028-0.001-0.061-0.002-0.094-0.002-1.541 0-2.797 1.22-2.856 2.746l-0 0.005v24.389c0.113 1.331 1.221 2.367 2.572 2.367 0.063 0 0.125-0.002 0.187-0.007l-0.008 0h20c0.414 0 0.75-0.336 0.75-0.75s-0.336-0.75-0.75-0.75v0h-19.8c-0.023 0.001-0.050 0.002-0.077 0.002-0.717 0-1.306-0.547-1.373-1.247l-0-0.006v-1.223c0-0.459 0.584-0.861 1.248-0.861l20.002 0.057c0 0 0.001 0 0.001 0 0.207 0 0.394-0.084 0.529-0.22l0-0c0.017-0.017 0.025-0.041 0.040-0.060 0.047-0.051 0.087-0.11 0.118-0.173l0.002-0.004c0.016-0.049 0.029-0.105 0.034-0.164l0-0.003c0.011-0.035 0.020-0.077 0.025-0.121l0-0.003v-0.002zM26.25 24.471l-15.5-0.044v-21.677h2.521v10.448c0 0 0 0 0 0.001 0 0.414 0.336 0.749 0.749 0.749 0.181 0 0.347-0.064 0.476-0.171l-0.001 0.001 3.51-2.869 3.504 2.804c0.127 0.102 0.29 0.164 0.468 0.164 0.414 0 0.75-0.336 0.751-0.75v-10.377h3.521zM21.229 2.75v8.816l-2.76-2.208c-0.127-0.103-0.29-0.165-0.468-0.165-0.181 0-0.347 0.064-0.477 0.172l0.001-0.001-2.754 2.252v-8.866zM5.75 24.686v-20.686c0.067-0.705 0.657-1.252 1.374-1.252 0.027 0 0.054 0.001 0.080 0.002l-0.004-0h2.050v21.672l-2.25-0.006c-0.453 0.002-0.883 0.101-1.269 0.278l0.019-0.008z"></path> </g></svg>
                <span>View all programs offered</span>
            </button>
        </a>
     </div>

     <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Pricing -->
    <div class="container pricing-container mx-auto mt-20 mb-15">
        <p class="text-3xl font-bold text-gray-900 text-center">Programs Pricing</p>
        <div class="flex flex-col md:flex-row items-center justify-center gap-6 mt-10">
            <!-- Card 1: Arabic Bundle -->
            <div class="relative flex flex-col w-[320px] h-[424px] rounded-lg bg-[#F6F3E8] p-6 font-bold shadow-lg">
                <div class="relative mb-8 overflow-hidden border-b border-neutral-800 pb-4">
                    <p class="block text-xs font-semibold uppercase text-[#A58618]">Arabic Bundle</p>
                    <div class="mt-2 flex flex-col justify-center items-start gap-1 text-4xl leading-none">
                        <span class="text-4xl text-[#A58618]">₱1,500</span>
                        <span class="text-sm text-[#2C8E5E]">Save ₱200</span>
                    </div>
                </div>
                <ul class="flex flex-col gap-4">
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#A58618] text-[#A58618]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#A58618]">1 project</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#A58618] text-[#A58618]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#A58618]">1TB storage</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#A58618] text-[#A58618]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#A58618]">24/7 Support</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#A58618] text-[#A58618]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#A58618]">24/7 Support</p>
                    </li>
                </ul>
                <a href="login.php">
                    <button class="mt-6 py-2 px-4 bg-[#A58618] rounded-lg w-full text-white cursor-pointer">Get Started</button>
                </a>
            </div>

            <!-- Card 2: Beginner Bundle -->
            <div class="relative flex flex-col w-[320px] h-[424px] rounded-lg bg-[#E7EBEF] p-6 font-bold shadow-lg">
                <div class="relative mb-8 overflow-hidden border-b border-neutral-800 pb-4">
                    <p class="block text-xs font-semibold uppercase text-[#10375B]">Beginner Bundle</p>
                    <div class="mt-2 flex flex-col justify-center items-start gap-1 text-4xl leading-none">
                        <span class="text-4xl text-[#10375B]">₱1,500</span>
                        <span class="text-sm text-[#2C8E5E]">Save ₱500</span>
                    </div>
                </div>
                <ul class="flex flex-col gap-4">
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#10375B] text-[#10375B]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#10375B]">2 projects</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#10375B] text-[#10375B]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#10375B]">Analytics</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#10375B] text-[#10375B]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#10375B]">Insight Panel</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#10375B] text-[#10375B]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#10375B]">Share Features</p>
                    </li>
                </ul>
                <a href="login.php">
                    <button class="mt-6 py-2 px-4 bg-[#10375B] rounded-lg w-full text-white cursor-pointer">Get Started</button>
                </a>
            </div>

            <!-- Card 3: Islamic Bundle -->
            <div class="relative flex flex-col w-[320px] h-[424px] rounded-lg bg-[#F6F3E8] p-6 font-bold shadow-lg">
                <div class="relative mb-8 overflow-hidden border-b border-neutral-800 pb-4">
                    <p class="block text-xs font-semibold uppercase text-[#A58618]">Arabic Bundle</p>
                    <div class="mt-2 flex flex-col justify-center items-start gap-1 text-4xl leading-none">
                        <span class="text-4xl text-[#A58618]">₱1,500</span>
                        <span class="text-sm text-[#2C8E5E]">Save ₱200</span>
                    </div>
                </div>
                <ul class="flex flex-col gap-4">
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#A58618] text-[#A58618]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#A58618]">Unlimited projects</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#A58618] text-[#A58618]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#A58618]">1TB storage</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#A58618] text-[#A58618]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#A58618]">24/7 Support</p>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-[#A58618] text-[#A58618]">
                            <img src="../images/check.svg" class="h-4 w-4" alt="">
                        </span>
                        <p class="text-[#A58618]">24/7 Support</p>
                    </li>
                </ul>
                <a href="login.php">
                    <button class="mt-6 py-2 px-4 bg-[#A58618] rounded-lg w-full text-white cursor-pointer">Get Started</button>
                </a>
            </div>
        </div>
    </div>

    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Call To Action -->
    <div id="contact" class="container mx-auto flex flex-col lg:flex-row items-center py-20 lg:px-10">
        <!-- Left Illustration -->
        <div class="flex hidden lg:ml-20 lg:block">
            <img src="../images/muslimah.svg" alt="Illustration" class="lg:w-50">
        </div>

        <!-- Text Container -->
        <div class="text-container px-5 flex-2 py-10 lg:py-20 max-w-screen mx-auto text-center">
            <p class="text-4xl lg:text-5xl font-bold text-gray-900">Al-Ghaya made it Accessible and Flexible</p>
            <p class="text-lg font-semibold text-gray-600 mt-5">
                Ready to learn Arabic and Islam on your schedule, wherever you are? Al-Ghaya makes deep learning achievable with flexible access.
            </p>
            <a href="login.php">
                <button type="button" class="mt-10 text-lg text-white bg-[#10375B] px-6 py-3 rounded-xl hover:bg-blue-900 hover:cursor-pointer">
                    Enroll Now
                </button>
            </a>
        </div>

        <!-- Right Illustration -->
        <div class="flex-1 hidden lg:block">
            <img src="../images/muslim.svg" alt="Illustration" class="lg:w-50">
        </div>
    </div>

    <hr class="my-12 h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Footer -->
    <?php include '../components/footer.php'; ?>

    <!-- Back to Top button -->
    <button type="button" onclick="scrollToTop()" class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer" id="scroll-to-top">
        <img src= "https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png" class="w-10 h-10 rounded-full bg-white" alt="">
    </button>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <!-- Navbar -->
    <script src="../components/navbar.js"></script>
    <!-- Carousel -->
    <script src="../javascript/programs-carousel.js"></script>
    <!-- Scroll To Top -->
    <script src="../javascript/scroll-to-top.js"></script>
    <script src="../dist/javascript/translate.js"></script>
</body>
</html>