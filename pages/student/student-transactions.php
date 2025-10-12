<?php
    session_start();
    require '../../php/dbConnection.php';
    // Check if user is logged in and is a student
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
        header("Location: ../login.php");
        exit();
    }

    // Check if user logged in via OAuth or email/password
    if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_email']) || !isset($_SESSION['user_avatar'])) {
        // Fetch user details from the database
        $stmt = $conn->prepare("SELECT fname, lname, email, gender FROM student WHERE studentID = ?");
        $stmt->bind_param("i", $_SESSION['userID']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Set session variables if not already set
        $_SESSION['user_name'] = trim($user['fname'] . ' ' . $user['lname']) ?: 'User';
        $_SESSION['user_email'] = $user['email'] ?? '';

        // Set avatar based on gender
        if ($user['gender'] === 'male') {
            $_SESSION['user_avatar'] = '../../images/male.svg';
        } elseif ($user['gender'] === 'female') {
            $_SESSION['user_avatar'] = '../../images/female.svg';
        } else {
            $_SESSION['user_avatar'] = '../../images/male.svg';
        }
    }

    // Now you can safely use these session variables
    $userName = $_SESSION['user_name'];
    $userEmail = $_SESSION['user_email'];
    $userAvatar = $_SESSION['user_avatar'];
    $current_page = "student-transactions";
    $page_title = "Transaction History";
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>
<!-- David (Latest Edits) -->
<div class="page-container">
    <div class="page-content">
        <!-- 1ST Section -->
        <section class="content-section">
            <h1 class="section-title">My Transactions</h1>
            <div class="section-card flex-col">
                <!-- Filters -->
                <div class="w-full flex flex-col gap-[10px]">
                    <div class="w-full flex items-center justify-start gap-[25px]">
                        <div class="flex gap-[10px] items-center">
                            <i class="ph ph-funnel-simple text-[24px]"></i>
                            <p class="font-semibold">Filters</p>
                        </div>
                        <button class="btn-grey">Clear Filters</button>
                    </div>
                    <div class="w-full h-auto flex flex-col gap-[10px]">
                        <p class="font-semibold">Difficulty</p>
                        <form action="">
                            <div class="flex gap-[10px]">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600" checked>
                                    <span class="ml-2">All</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                    <span class="ml-2">Beginner</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                    <span class="ml-2">Intermediate</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                    <span class="ml-2">Advanced</span>
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Search Bar -->
                <div class="w-full flex items-center justify-center gap-[10px]">
                    <i class="ph ph-magnifying-glass text-[30px]"></i>
                    <input type="text" placeholder="Program Name" class="w-[500px] h-[40px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary">
                </div>
                <!-- Transaction Table -->
            </div>
        </section>
    </div>
</div>


<!-- Back to Top button -->
<button type="button" onclick="scrollToTop()"
    class="scroll-to-top hidden fixed bottom-4 right-4 bg-gray-800 text-white rounded-full transition duration-300 hover:bg-gray-700 hover:text-gray-200 hover:cursor-pointer"
    id="scroll-to-top">
    <img src="https://media.geeksforgeeks.org/wp-content/uploads/20240227155250/up.png"
        class="w-10 h-10 rounded-full bg-white" alt="">
</button>

<?php include '../../components/footer.php'; ?>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<!-- JS -->
<script src="../../components/navbar.js"></script>
<script src="../../dist/javascript/scroll-to-top.js"></script>
<script src="../../dist/javascript/carousel.js"></script>
<script src="../../dist/javascript/user-dropdown.js"></script>
<script src="../../dist/javascript/translate.js"></script>
</body>

</html>