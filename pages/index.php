<?php
    session_start(); // Start session at the top
    $current_page = "homepage";
    $page_title = 'index';

    // Check if user is logged in
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <!-- Tailwind -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <!-- CSS -->
    <link rel="stylesheet" href="../dist/css/index.css">
    <link rel="icon" type="image/x-icon" href="../images/Al-ghaya_logoForPrint.svg">
    <title>Al-Ghaya - Homepage</title>
    <?php include '../components/header.php' ?>
</head>
<body class="bg-[#F3F3FC]">
    <!-- Navbar -->
    <?php include '../components/nav-component.php'; ?>

    <!-- Hero Section -->
    <div id="hero-section" class="relative flex mx-auto overflow-hidden" style="background-image: url('../images/blog-bg.svg');">
        <!-- Container -->
        <div class="text-container mx-auto flex flex-col lg:flex-row items-center justify-center h-auto lg:h-[508px] gap-x-20">
            <!-- Left Illustration -->
            <div class="flex justify-center">
                <img src="../images/Hero.svg" alt="Illustration" class="w-auto max-h-[300px] lg:max-h-none">
            </div>

            <!-- Text Content -->
            <div class="flex flex-col max-w-[70%] lg:max-w-[45%] md:max-w-[60%] text-center lg:mt-15 lg:text-left">
                <p class="text-xl md:text-3xl lg:text-5xl font-bold text-white">
                    Study <span class="text-[#dddbff]">Arabic</span> and <span class="text-[#dddbff]">Islam</span> at your own pace
                </p>
                <p class="text-base lg:text-xl md:text-lg text-gray-100 py-5 font-bold text-justify">
                    Embark on a unique and engaging journey to master the Arabic language and deepen your understanding of Islamic studies with Al-Ghaya.
                    Study at your own pace through captivating, gamified stories that bring learning to life. Experience a flexible and interactive learning
                    environment designed for your success.
                </p>
                <div class="mt-4">
                    <a href="login.php">
                        <button type="button" class="text-lg text-white bg-[#10375B] w-fit px-4 py-2 mb-6 rounded-xl border border-white hover:bg-blue-900 hover:cursor-pointer">
                            Enroll Now
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Learning Method Section -->
    <div id="learning-method-section" class="relative mx-auto pt-20 max-w-[1500px] overflow-visible">
        <div class="text-center mb-10">
            <p class="text-3xl font-bold text-neutral-800">How Can Al-Ghaya Help?</p>
            <p class="text-xl font-bold text-neutral-600">The Features</p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-5 lg:grid-rows-6 gap-0">
            <!-- Feature 1 -->
            <div class="flex flex-col lg:col-span-2 lg:row-span-3 items-center text-center bg-white p-5 lg:items-start lg:text-start">
                <img src="../images/self-paced.png" alt="Feature 1" class="w-full h-48 object-cover rounded-lg mb-4">
                <p class="text-xl font-bold text-neutral-800">Self-paced Learning</p>
                <p class="text-base lg:px-0 text-neutral-600 md:px-10 text-justify">
                    Study when and where it suits you, with no fixed class times or deadlines,
                    allowing you to progress through your Arabic and Islamic studies programs entirely at your own speed.
                </p>
            </div>
            <!-- Feature 2 -->
            <div class="flex flex-col lg:col-span-3 lg:row-span-3 items-center text-center bg-white p-5 lg:items-start lg:text-start">
                <img src="../images/SE-c3e5e444-b4e4-4bd1-bda8-ad57282bd703.jpg" alt="Feature 2" class="w-full h-48 object-cover rounded-lg mb-4">
                <p class="text-xl font-bold text-neutral-800">Proficiency-level Based Programs</p>
                <p class="text-base lg:px-0 text-neutral-600 md:px-10 text-justify">
                    Learn according to your skill level, with programs structured across Beginner (Student),
                    Intermediate (Aspiring), and Advanced (Master) proficiencies that align with your account progress.
                </p>
            </div>
            <!-- Feature 3 -->
            <div class="flex flex-col lg:col-span-3 lg:row-span-3 lg:row-start-4 items-center text-center bg-white p-5 lg:items-start lg:text-start">
                <img src="../images/quoran.jpg" alt="Feature 3" class="w-full h-48 object-cover rounded-lg mb-4">
                <p class="text-xl font-bold text-neutral-800">Interactive Storytelling</p>
                <p class="text-base lg:px-0 text-neutral-600 md:px-10 text-justify">
                    Engage with interactive lessons delivered through captivating stories,
                    earning points and managing lives as you learn, making studying feel like an exciting game.
                </p>
            </div>
            <!-- Feature 4 -->
            <div class="flex flex-col lg:col-span-2 lg:row-span-3 lg:row-start-4 items-center text-center bg-white p-5 lg:items-start lg:text-start">
                <img src="../images/tracker.png" alt="Feature 4" class="w-full h-48 object-cover rounded-lg mb-4">
                <p class="text-xl font-bold text-neutral-800">Program Progress Tracker</p>
                <p class="text-base lg:px-0 text-neutral-600 md:px-10 text-justify">
                    Easily monitor your advancement through all your enrolled programs,
                    seeing exactly how far you've come and what's next on your learning path.
                </p>
            </div>
        </div>
    </div>

    <hr class="my-12 h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Programs Offered Section -->
    <section id="programs" class="relative text-white py-20">
        <div class="relative lg:flex lg:items-center z-10 max-w-full mx-auto sm:px-6">
            <div class="text-center mb-10 p-5 lg:mx-auto">
                <h2 class="text-6xl font-bold">The Featured Programs</h2>
            </div>
            <!-- Course Cards Grid -->
            <div class="grid grid-cols-1 px-10 lg:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bg-white text-black rounded-lg shadow-lg overflow-hidden lg:max-w-sm">
                    <div class="w-full h-48 overflow-hidden">
                        <img class="w-full h-full object-cover" src="../images/arabic-lessons-near-me-in-abuja.jpg.webp" alt="Arabic Language">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2 text-[#A58618]">Arabic Language</h3>
                        <p class="text-gray-600 mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                        <a href="#" class="inline-block px-4 py-2 text-sm font-medium text-white bg-[#10375B] rounded hover:bg-blue-900">Learn More</a>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white text-black rounded-lg shadow-lg overflow-hidden lg:max-w-sm">
                    <div class="w-full h-48 overflow-hidden">
                        <img class="w-full h-full object-cover" src="../images/camel.jpg" alt="Hadith">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2 text-[#A58618]">Hadith</h3>
                        <p class="text-gray-600 mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                        <a href="#" class="inline-block px-4 py-2 text-sm font-medium text-white bg-[#10375B] rounded hover:bg-blue-900">Learn More</a>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white text-black rounded-lg shadow-lg overflow-hidden lg:max-w-sm">
                    <div class="w-full h-48 overflow-hidden">
                        <img class="w-full h-full object-cover" src="../images/Untitled-design-22-1255x706.png" alt="Fiqh">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2 text-[#A58618]">Fiqh</h3>
                        <p class="text-gray-600 mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
                        <a href="#" class="inline-block px-4 py-2 text-sm font-medium text-white bg-[#10375B] rounded hover:bg-blue-900">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Background Pattern -->
        <div class="absolute inset-0 z-0">
            <img src="../images/blog-bg.svg" alt="Background Pattern" class="w-full h-full object-cover">
        </div>
    </section>

    <hr class="my-12 h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Team Section -->
    <div id="team" class="container mx-auto py-20 px-5 lg:px-0 gap-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <!-- Left Side: Team Description -->
            <div class="lg:text-left text-center px-10">
                <p class="text-5xl font-extrabold text-gray-800">Want to know our Adeeqah and Learning Quality?</p>
                <p class="text-xl font-semibold text-gray-600 pt-10">
                    Our dedicated professionals bring expertise and passion to our work.
                    Get to know the amazing individuals behind our success.
                </p>
                <a href="about.php">
                    <button type="button" class="text-lg mx-auto mt-10 text-white bg-[#10375B] px-5 py-2 rounded-xl hover:bg-blue-900 hover:cursor-pointer">
                        Learn More
                    </button>
                </a>
            </div>

            <!-- Right Side: Swiper Carousel -->
            <div class="swiper swiper-container max-w-60 md:max-w-md lg:max-w-lg xl:max-w-xl">
                <div class="swiper-wrapper">
                    <!-- Team Member 1 -->
                    <div class="swiper-slide flex flex-col items-center">
                        <img src="../images/lecturer.jpg" alt="Team Member 1" class="shadow-lg h-60 w-60 md:w-md md:h-md lg:w-[400px] lg:h-[400px] object-cover">
                    </div>
                    <!-- Team Member 2 -->
                    <div class="swiper-slide flex flex-col items-center">
                        <img src="../images/lecturer2.jpg" alt="Team Member 2" class="shadow-lg h-60 w-60 md:w-md md:h-md lg:w-[400px] lg:h-[400px] object-cover">
                    </div>
                    <!-- Team Member 3 -->
                    <div class="swiper-slide flex flex-col items-center">
                        <img src="../images/andri-helmansyah-e9tCOGG40mw-unsplash.jpg" alt="Team Member 3" class="shadow-lg h-60 w-60 md:w-md md:h-md lg:w-[400px] lg:h-[400px] object-cover">
                    </div>
                    <!-- Team Member 4 -->
                    <div class="swiper-slide flex flex-col items-center">
                        <img src="../images/Untitled-design-22-1255x706.png" alt="Team Member 1" class="shadow-lg h-60 w-60 md:w-md md:h-md lg:w-[400px] lg:h-[400px] object-cover">
                    </div>
                </div>
                <!-- Fading Borders -->
                <div class="fading-border left"></div>
                <div class="fading-border right"></div>
            </div>
        </div>
    </div>

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
    <?php include '../components/footer.php'; ?>

    <!-- Back to Top button -->
    <button type="button" onclick="scrollToTop()" class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer" id="scroll-to-top">
        <img src="https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png" class="w-10 h-10 rounded-full bg-white" alt="">
    </button>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- JS -->
    <script src="../components/navbar.js"></script>
    <script src="../dist/javascript/scroll-to-top.js"></script>
    <script src="../dist/javascript/carousel.js"></script>
    <script src="../dist/javascript/translate.js"></script>
</body>
</html>
