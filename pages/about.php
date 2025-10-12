<?php
    session_start();
    $current_page = 'about';

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
    <link rel="stylesheet" href="../dist/css/index.css">
    <link rel="icon" type="image/x-icon" href="../images/Al-ghaya_logoForPrint.svg">
    <title>Al-Ghaya - About</title>
</head>
<body>
    <!-- Navbar -->
    <?php include '../components/nav-component.php'; ?>

    <!-- Description -->
    <section class="description">
        <div id="contact" class="container flex lg:flex-row items-center pt-10 pb-5 lg:px-10 max-w-screen outline" style="background-image: url('../images/blog-bg.svg')">
            <!-- Text Container -->
            <div class="text-container px-5 py-5 lg:py-20 max-w-screen place-items-center mx-auto text-start max-w-[55%] lg:max-w-[60%]">
                <p class="text-4xl lg:text-5xl font-bold text-[#E6E6E8] text-center">The Al-Ghaya Method: Learning Arabic and Islam</p>
                <p class="text-xl text-[#E6E6E8] text-justify pt-10">Experience our unique approach to mastering Arabic and Islamic studies. 
                    The Al-Ghaya Method blends self-paced flexibility with engaging, story-based learning, built on a foundation of sound knowledge. 
                    It's an effective way to deepen your understanding, tailored for you.</p>
            </div>
        </div>
    </section>
    
    
    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- How Did We Start -->
    <div id="contact" class="container mx-auto flex flex-col lg:flex-row items-center pt-10 px-4 lg:px-10 max-w-full lg:max-w-[70%]">
        <!-- Left Illustration (Visible only on large screens) -->
        <div class="hidden lg:flex w-1/2 justify-center mb-6 lg:mb-0">
            <img src="../images/explain3.svg" alt="Illustration" class="w-[350px] max-w-full">
        </div>

        <!-- Text Container -->
        <div class="w-full lg:w-1/2 px-2 text-start">
            <p class="text-3xl md:text-4xl lg:text-6xl font-bold text-[#10375B]">How did we start?</p>
            <p class="text-base md:text-lg text-[#05051A] text-start pt-5">
                The concept for the Al-Ghaya Learning Management System was born directly from a challenge faced by our institution, 
                Ma'had ISCAG: a decreasing number of enrolled students.
                <br><br>
                Recognizing the need to adapt and provide more accessible, flexible learning options, the idea for a gamified, 
                self-paced online platform was developed to address this problem and enhance the educational experience.
            </p>
        </div>
    </div>

    <!-- Problems we faced -->
    <div class="container mx-auto flex flex-col lg:flex-row items-center gap-8 px-4 mt-16 max-w-full lg:max-w-[70%]">
        <!-- Text Container -->
        <div class="w-full lg:w-1/2 bg-[#E7EBEF] rounded-[30px] border border-[#10375B] shadow-xl p-6 md:p-10">
            <p class="text-2xl md:text-3xl lg:text-4xl font-bold text-[#10375B]">Problems we faced</p>
            <p class="text-base md:text-lg text-[#10375B] text-start pt-6">
                <span class="mr-2">1</span> Difficulty focusing or finding time to study due to college or work.
            </p>
            <p class="text-base md:text-lg text-[#10375B] text-start pt-6">
                <span class="mr-2">2</span> Finding it hard to study and understand Islamic concepts, leading to loss of interest.
            </p>
        </div>

        <!-- Right Illustration (Visible only on large screens) -->
        <div class="hidden lg:flex w-1/2 justify-center mt-6 lg:mt-0">
            <img src="../images/explain-right.svg" alt="Illustration" class="w-[300px] max-w-full">
        </div>
    </div>

    <!-- Our solution -->
    <div class="container mx-auto flex flex-col lg:flex-row items-center gap-8 px-4 mt-16 max-w-full lg:max-w-[70%] mb-20">
        <!-- Left Illustration (Visible only on large screens) -->
        <div class="hidden lg:flex w-1/2 justify-center mb-6 lg:mb-0">
            <img src="../images/pointG.svg" alt="Illustration" class="w-[300px] max-w-full">
        </div>

        <!-- Text Container -->
        <div class="w-full lg:w-1/2 bg-[#E7EBEF] rounded-[30px] border border-[#10375B] shadow-xl p-6 md:p-10">
            <p class="text-2xl md:text-3xl lg:text-4xl font-bold text-[#10375B]">Our solution</p>
            <p class="text-base md:text-lg text-[#10375B] text-start pt-6">
                <span class="mr-2">1</span> Self-paced learning modality
            </p>
            <p class="text-base md:text-lg text-[#10375B] text-start pt-6">
                <span class="mr-2">2</span> Gamified storytelling
            </p>
        </div>
    </div>
    
    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Description -->
    <section class="description">
        <div id="contact" class="container flex lg:flex-row items-center pt-10 pb-5 lg:px-10 max-w-screen outline" style="background-image: url('../images/blog-bg.svg')">
            <!-- Text Container -->
            <div class="text-container px-5 py-5 lg:py-20 max-w-screen mx-auto text-start max-w-[50%] lg:max-w-[50%]">
                <p class="text-4xl lg:text-6xl font-bold text-[#E6E6E8]">Narrated by Ibn Mas`ud:</p>
                <p class="text-lg text-[#E6E6E8] text-justify pt-5">قَالَ كَانَ النَّبِيُّ صلى الله عليه وسلم يَتَخَوَّلُنَا بِالْمَوْعِظَةِ فِي الأَيَّامِ، كَرَاهَةَ السَّآمَةِ عَلَيْنَا‏</p>
                <p class="text-lg text-[#E6E6E8] text-justify pt-5">The Prophet (ﷺ) used to take care of us in preaching by selecting a suitable time, 
                    so that we might not get bored. (He abstained from pestering us with sermons and knowledge all the time).</p>
                <p class="text-lg text-[#2D8F5F] text-justify pt-5"> Sahih al-Bukhari 68</p>
            </div>
        </div>
    </section>

    <hr class="my-12 h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

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
    <footer class="bg-[#10375B]">
        <div class="mx-auto w-full max-w-screen-2xl p-4 py-6 lg:py-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 sm:gap-6 items-start">
                <!-- Logo and Character -->
                <div class="col-span-2 flex flex-row items-center justify-center mb-6">
                    <img src="../images/muslim-coffee.svg" class="h-65 lg:max-w-xs lg:flex hidden" alt="Character" />
                    <div class="flex items-center">
                        <img src="../images/Vertical.svg" class="h-[148px] me-3" alt="Al-Ghaya Logo"/>
                    </div>
                </div>

                <!-- Our Location -->
                <div class="col-span-1 px-10 lg:mt-20">
                    <h2 class="mb-3 text-lg font-semibold text-white uppercase">Our Location</h2>
                    <ul class="text-white font-medium">
                        <li class="mb-4">
                            <span class="font-bold">Al-Ghaya Office</span>
                        </li>
                        <li class="break-all max-w-xs">
                            <span>ISCAG School, Salitran 1, Dasmarinas, Cavite</span>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-span-1 px-10 lg:mt-20">
                    <h2 class="mb-3 text-lg font-semibold text-white uppercase">Contact</h2>
                    <ul class="text-white font-medium">
                        <li class="mb-3">
                            <span>Omar Eguia</span>
                        </li>
                        <li>
                            <span>+9766231230</span>
                        </li>
                    </ul>
                </div>

                <!-- Socials -->
                <div class="col-span-1 px-10 lg:mt-20">
                    <h2 class="mb-3 text-lg font-semibold text-white uppercase">Socials</h2>
                    <a href="#" class="text-white hover:text-gray-600">
                        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 8 19">
                            <path fill-rule="evenodd" d="M6.135 3H8V0H6.135a4.147 4.147 0 0 0-4.142 4.142V6H0v3h2v9.938h3V9h2.021l.592-3H5V3.591A.6.6 0 0 1 5.592 3h.543Z" clip-rule="evenodd"/>
                        </svg>
                        <span class="sr-only">Facebook page</span>
                    </a>
                </div>
            </div>

            <hr class="my-6 border-gray-200 mx-auto dark:border-gray-700 lg:my-8" />

            <div class="sm:flex sm:items-center sm:justify-center text-center">
                <span class="text-sm text-white">© 2025 Al-Ghaya. All Rights Reserved.</span>
            </div>
        </div>
    </footer>

    <!-- Back to Top button -->
    <button type="button" onclick="scrollToTop()" class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer" id="scroll-to-top">
        <img src= "https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png" class="w-10 h-10 rounded-full bg-white" alt="">
    </button>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <!-- JS -->
    <script src="../components/navbar.js"></script>
    <script src="../javascript/scroll-to-top.js"></script>
    <script src="../javascript/carousel.js"></script>
    <script src="../dist/javascript/translate.js"></script>
</body>
</html>