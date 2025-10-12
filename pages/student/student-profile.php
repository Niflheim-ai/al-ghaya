<!-- FOR UI FIX (UNDER MAINTENANCE) -->

<?php
session_start();
require '../../php/dbConnection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// Fetch user details
$stmt = $conn->prepare("SELECT studentID, fname, lname, email, gender, level, points, proficiency FROM student WHERE studentID = ?");
$stmt->bind_param("i", $_SESSION['userID']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$current_page = "student-profile";
$page_title = "My Profile";

// Set avatar based on gender if not already set in session
if (!isset($_SESSION['user_avatar'])) {
    if (isset($user['gender'])) {
        if ($user['gender'] === 'male') {
            $_SESSION['user_avatar'] = '../../images/male.svg';
        } elseif ($user['gender'] === 'female') {
            $_SESSION['user_avatar'] = '../../images/female.svg';
        } else {
            $_SESSION['user_avatar'] = '../../images/male.svg';
        }
    } else {
        $_SESSION['user_avatar'] = '../../images/male.svg';
    }
}

// Set user details
$userName = htmlspecialchars($user['fname'] . ' ' . $user['lname']);
$userEmail = htmlspecialchars($user['email']);
$userAvatar = htmlspecialchars($_SESSION['user_avatar']);
$userLevel = $user['level'] ?? 0;
$userPoints = $user['points'] ?? 0;
$userProficiency = htmlspecialchars($user['proficiency'] ?? 'Beginner');
$userGender = htmlspecialchars(ucfirst($user['gender']));
$userID = $user['studentID'];
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>

<div class="page-container p-4">
    <div class="page-content">
        <section class="content-section">
            <h1 class="section-title text-center md:text-left">My Profile</h1>
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Column -->
                <div class="flex flex-col gap-6 w-full lg:w-1/3">
                    <!-- Profile Card -->
                    <section class="bg-company_white rounded-xl p-4 md:p-6 relative">
                        <!-- Avatar -->
                        <div class="w-32 h-32 md:w-48 md:h-48 mx-auto mb-4 rounded-full overflow-hidden border-4 border-secondary">
                            <img src="<?= $userAvatar ?>" alt="Avatar" class="w-full object-cover">

                        </div>

                        <!-- Profile Details -->
                        <div class="space-y-4">
                            <!-- Name -->
                            <div class="text-center md:text-left">
                                <p class="font-bold text-lg md:text-xl"><?= $userName ?></p>
                            </div>

                            <!-- Level & EXP -->
                            <div class="flex flex-col md:flex-row md:items-center gap-2">
                                <p class="font-medium text-sm">Account Level:</p>
                                <div class="flex items-center gap-2">
                                    <p class="font-bold">Level <?= $userLevel ?></p>
                                    <p class="text-sm"><?= $userPoints ?> exp</p>
                                </div>
                            </div>

                            <!-- Proficiency -->
                            <div>
                                <p class="font-medium text-sm">Proficiency:</p>
                                <div class="inline-block px-3 py-1 bg-company_black text-white rounded-lg text-sm font-semibold">
                                    <?= $userProficiency ?>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <hr class="my-4 border-company_black">

                        <!-- Additional Info -->
                        <div class="space-y-3">
                            <div>
                                <p class="font-medium text-sm">Email:</p>
                                <p class="break-all"><?= $userEmail ?></p>
                            </div>
                            <div>
                                <p class="font-medium text-sm">Gender:</p>
                                <p><?= $userGender ?></p>
                            </div>
                            <div>
                                <p class="font-medium text-sm">Student ID:</p>
                                <p><?= $userID ?></p>
                            </div>
                        </div>

                        <!-- Logout Button -->
                        <button onclick="confirmLogout(event)"
                            class="w-full mt-4 py-2 bg-company_red/30 text-company_red rounded-lg hover:cursor-pointer hover:bg-company_red/40 transition flex items-center justify-center gap-2">
                            <i class="ph ph-sign-out text-lg"></i>
                            <span>Logout</span>
                        </button>

                        <!-- Edit Profile Button (Floating) -->
                        <button onclick="alertEditProfile()"
                            class="absolute top-4 right-4 p-2 bg-secondary text-white rounded-lg hover:bg-company_black transition">
                            <i class="ph ph-note-pencil text-xl"></i>
                        </button>
                    </section>

                    <!-- Language Section -->
                    <section class="bg-company_white rounded-xl p-4 md:p-6">
                        <h3 class="text-center font-medium mb-4">Language</h3>
                        <div class="flex justify-center gap-2">
                            <button id="englishBtn"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 transition">
                                <i class="ph-light ph-globe text-xl"></i>
                                <span>English</span>
                            </button>
                            <button id="filipinoBtn"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-200 transition">
                                <i class="ph-light ph-globe text-xl"></i>
                                <span>Filipino</span>
                            </button>
                        </div>
                    </section>
                </div>

                <!-- Right Column -->
                <div class="flex flex-col gap-6 w-full lg:w-2/3">
                    <!-- Change Password -->
                    <section class="section-card-inputs">
                        <h2 class="text-lg font-medium mb-4 text-center md:text-left">Change Password</h2>
                        <form onsubmit="confirmPasswordChange(event)" action="../../php/change-password.php" method="post" class="space-y-4">
                            <div class="space-y-3">
                                <div>
                                    <label for="current-password" class="block font-medium text-sm mb-1">Current Password</label>
                                    <input type="password" name="current-password" id="current-password"
                                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-secondary"
                                        placeholder="Enter current password" required>
                                </div>
                                <div>
                                    <label for="new-password" class="block font-medium text-sm mb-1">New Password</label>
                                    <input type="password" name="new-password" id="new-password"
                                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-secondary"
                                        placeholder="Enter new password" required>
                                </div>
                                <div>
                                    <label for="confirm-password" class="block font-medium text-sm mb-1">Confirm Password</label>
                                    <input type="password" name="confirm-password" id="confirm-password"
                                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-secondary"
                                        placeholder="Confirm new password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn-secondary group">
                                <i class="ph ph-floppy-disk text-[24px] group-hover:hidden"></i>
                                <i class="ph-duotone ph-floppy-disk text-[24px] hidden group-hover:block"></i>
                                <span>Save Changes</span>
                            </button>
                        </form>
                    </section>

                    <!-- Proficiency Progress -->
                    <section class="bg-company_white rounded-xl p-4 md:p-6">
                        <div class="space-y-6">
                            <div class="text-center">
                                <h2 class="text-lg font-medium mb-2">Proficiency Progress</h2>
                                <div class="flex justify-center gap-1 my-4">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <div class="w-4 h-2 <?= $i <= $userLevel ? 'bg-secondary' : 'bg-primary' ?> rounded-sm"></div>
                                    <?php endfor; ?>
                                </div>
                                <p class="font-medium">Current Level: <span class="font-bold">Level <?= $userLevel ?></span></p>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                <div class="text-center">
                                    <div class="inline-block px-3 py-1 bg-company_black text-white rounded-lg mb-1">
                                        <?= $userProficiency ?>
                                    </div>
                                    <p class="text-sm">Level <?= floor(($userLevel - 1) / 10) * 10 + 1 ?>-<?= floor(($userLevel - 1) / 10) * 10 + 10 ?></p>
                                </div>
                                <i class="ph ph-caret-double-right text-2xl text-gray-400"></i>
                                <div class="text-center text-gray-400">
                                    <div class="inline-block px-3 py-1 bg-primary rounded-lg mb-1">
                                        Next Level
                                    </div>
                                    <p class="text-sm">Level <?= $userLevel + 1 ?>-<?= $userLevel + 10 ?></p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Back to Top Button -->
<button type="button" onclick="scrollToTop()"
    class="fixed bottom-4 right-4 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition"
    id="scroll-to-top">
    <i class="ph ph-caret-up text-xl"></i>
</button>

<?php include '../../components/footer.php'; ?>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Set English as default
    document.getElementById('englishBtn').classList.add('bg-gray-200');

    // Language toggle
    document.getElementById('englishBtn').addEventListener('click', () => {
        document.getElementById('englishBtn').classList.add('bg-gray-200');
        document.getElementById('filipinoBtn').classList.remove('bg-gray-200');
    });

    document.getElementById('filipinoBtn').addEventListener('click', () => {
        document.getElementById('filipinoBtn').classList.add('bg-gray-200');
        document.getElementById('englishBtn').classList.remove('bg-gray-200');
    });

    // Logout confirmation
    function confirmLogout(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Logout',
            text: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = '../../php/signout.php';
        });
    }

    // Edit profile alert
    function alertEditProfile() {
        Swal.fire({
            title: 'Edit Profile',
            text: 'This feature will be available soon!',
            icon: 'info',
            confirmButtonText: 'OK'
        });
    }

    // Password change confirmation
    function confirmPasswordChange(event) {
        event.preventDefault();
        const form = event.target;
        const newPassword = form['new-password'].value;
        const confirmPassword = form['confirm-password'].value;

        if (newPassword !== confirmPassword) {
            Swal.fire({
                title: 'Error',
                text: 'Passwords do not match!',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Change Password',
            text: 'Are you sure you want to change your password?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    }
</script>
</body>

</html>