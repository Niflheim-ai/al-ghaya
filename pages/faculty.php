<?php
    session_start();
    $current_page = 'faculty';
    $page_title = 'faculty';

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
    <!-- Tailwind -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <!-- CSS -->
    <link rel="stylesheet" href="../dist/css/faculty.css">
    <link rel="icon" type="image/x-icon" href="../images/Al-ghaya_logoForPrint.svg">
    <title>Al-Ghaya - Faculty</title>
</head>
<body>
    <!-- Navbar -->
    <?php include '../components/nav-component.php'; ?>

    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-neutral-800 via-transparent to-neutral-800 opacity-45 dark:via-transparent"/>

    <!-- Description -->
    <div id="contact" class="container mx-auto flex lg:flex-row items-center pt-10 pb-5 lg:px-10 max-w-[55%] lg:max-w-[70%]">
        <!-- Left Illustration -->
        <div class="flex hidden lg:flex justify-center lg:block">
            <img src="../images/half-right.svg" alt="Illustration" class="lg:w-200">
        </div>

        <!-- Text Container -->
        <div class="text-container px-5 py-5 lg:py-20 max-w-screen mx-auto text-start">
            <p class="text-4xl lg:text-6xl font-bold text-gray-900">Get to know our faculty</p>
            <p class="text-lg text-neutral-500 text-justify pt-5">Our dedicated faculty are entrusted with guiding your learning journey at Al-Ghaya. 
                With strong qualifications and a commitment to the authentic teachings of Ahlus Sunnah wal Jama'ah, 
                they are here to ensure you receive sound knowledge and values directly from trusted sources. 
                Learn with confidence from our knowledgeable instructors.</p>
        </div>
    </div>
    
    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>
    
    <!-- Faculty Display -->
    <div class="grid grid-cols-1 xl:grid-cols-4 md:grid-cols-2 lg:grid-cols-3 gap-10 lg:gap-35 xl:gap-30 px-3 py-3 pt-15 pb-15 lg:mx-auto mx-auto justify-center items-center max-w-[65%]">
        <div class="w-60 h-100 rounded-xl">
            <img src="../images/fac-1.png" class="rounded-xl" alt="">
            <p class="pt-5 text-[#05051A] font-bold">Ustadh Eljamil</p>
            <p class="pt-4 text-neutral-500">Al-Maarif Graduate <br>(Batch 10)</p>
            <p class="pt-4 text-[#A58618]">Mastery in Fiqh</p>
        </div>
        
        <div class="w-60 h-100 rounded-xl">
            <img src="../images/fac-2.png" class="rounded-xl" alt="">
            <p class="pt-5 text-[#05051A] font-bold">Ustadh Muhammad</p>
            <p class="pt-4 text-neutral-500">Al-Maarif Graduate <br>(Batch 10)</p>
            <p class="pt-4 text-[#A58618]">Mastery in Fiqh</p>
        </div>

        <div class="w-60 h-100 rounded-xl">
            <img src="../images/fac-3.png" class="rounded-xl" alt="">
            <p class="pt-5 text-[#05051A] font-bold">Ustadh Yusuf</p>
            <p class="pt-4 text-neutral-500">Al-Maarif Graduate <br>(Batch 10)</p>
            <p class="pt-4 text-[#A58618]">Mastery in Fiqh</p>
        </div>

        <div class="w-60 h-100 rounded-xl">
            <img src="../images/fac-4.png" class="rounded-xl" alt="">
            <p class="pt-5 text-[#05051A] font-bold">Ustadh Nuh</p>
            <p class="pt-4 text-neutral-500">Al-Maarif Graduate <br>(Batch 10)</p>
            <p class="pt-4 text-[#A58618]">Mastery in Fiqh</p>
        </div>

        <div class="w-60 h-100 rounded-xl">
            <img src="../images/fac-5.png" class="rounded-xl" alt="">
            <p class="pt-5 text-[#05051A] font-bold">Ustadhah Fatima</p>
            <p class="pt-4 text-neutral-500">Al-Maarif Graduate <br>(Batch 10)</p>
            <p class="pt-4 text-[#A58618]">Mastery in Fiqh</p>
        </div>

        <div class="w-60 h-100 rounded-xl">
            <img src="../images/fac-6.png" class="rounded-xl" alt="">
            <p class="pt-5 text-[#05051A] font-bold">Ustadhah Aisha</p>
            <p class="pt-4 text-neutral-500">Al-Maarif Graduate <br>(Batch 10)</p>
            <p class="pt-4 text-[#A58618]">Mastery in Fiqh</p>
        </div>

        <div class="w-60 h-100 rounded-xl">
            <img src="../images/fac-7.png" class="rounded-xl" alt="">
            <p class="pt-5 text-[#05051A] font-bold">Ustadhah Ameerah</p>
            <p class="pt-4 text-neutral-500">Al-Maarif Graduate <br>(Batch 10)</p>
            <p class="pt-4 text-[#A58618]">Mastery in Fiqh</p>
        </div>
        
        <div class="w-60 h-100 rounded-xl">
            <img src="../images/fac-8.png" class="rounded-xl" alt="">
            <p class="pt-5 text-[#05051A] font-bold">Ustadhah Khadijah</p>
            <p class="pt-4 text-neutral-500">Al-Maarif Graduate <br>(Batch 10)</p>
            <p class="pt-4 text-[#A58618]">Mastery in Fiqh</p>
        </div>
    </div>

    <hr class="h-px border-t-0 bg-transparent bg-gradient-to-r from-transparent via-neutral-800 to-transparent opacity-45 dark:via-neutral-800"/>

    <!-- Container -->
    <div class="text-container mx-auto flex flex-col lg:flex-row items-center justify-center h-auto lg:h-[508px] gap-x-20">
        <!-- Text Content -->
        <div class="flex flex-col max-w-[70%] lg:max-w-[35%] md:max-w-[60%] text-center lg:mt-15 lg:text-left">
            <p class="text-xl md:text-3xl lg:text-4xl font-bold text-[#10375B]">
                Learn Arabic Language and Islam, the Al-Ghaya way
            </p>
            <p class="text-base lg:text-xl md:text-lg text-black py-5 font-bold text-justify">
                Experience a truly unique approach to learning. With Al-Ghaya, immerse yourself in Arabic and Islamic studies through engaging stories, 
                track your growth, and learn at your own flexible pace. Discover the difference the Al-Ghaya way makes.
            </p>
            <div class="mt-4">
                <a href="login.php">
                    <button type="button" class="text-lg text-white bg-[#10375B] w-full px-4 py-2 mb-6 rounded-xl border border-white hover:bg-blue-900 hover:cursor-pointer">
                    Enroll Now
                    </button>
                </a>
            </div>
        </div>

        <!-- Right Illustration -->
        <div class="flex justify-center">
            <img src="../images/hero.svg" alt="Illustration" class="w-auto max-h-[300px] lg:max-h-none">
        </div>
    </div>

    <!-- Footer -->
    <?php include '../components/footer.php'; ?>

    <!-- Back to Top button -->
    <button type="button" onclick="scrollToTop()" class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer" id="scroll-to-top">
        <img src= "https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png" class="w-10 h-10 rounded-full bg-white" alt="">
    </button>

    <!-- Navbar -->
    <script src="../components/navbar.js"></script>
    <!-- Scroll To Top -->
    <script src="../javascript/scroll-to-top.js"></script>
    <script src="../dist/javascript/translate.js"></script>
</body>
</html>