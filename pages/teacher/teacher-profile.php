<?php
session_start();
include('../../php/dbConnection.php');

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Get user information
$userQuery = $conn->prepare("SELECT * FROM user WHERE userID = ?");
$userQuery->bind_param("i", $userID);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $experience = trim($_POST['experience']);
    $bio = trim($_POST['bio']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $errors[] = "First name, last name, and email are required.";
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Check if email is already taken by another user
    $emailCheck = $conn->prepare("SELECT userID FROM user WHERE email = ? AND userID != ?");
    $emailCheck->bind_param("si", $email, $userID);
    $emailCheck->execute();
    if ($emailCheck->get_result()->num_rows > 0) {
        $errors[] = "Email is already taken by another user.";
    }
    
    // Password validation if provided
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $errors[] = "Current password is required to set a new password.";
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($newPassword) < 8) {
            $errors[] = "New password must be at least 8 characters long.";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New password and confirmation do not match.";
        }
    }
    
    if (empty($errors)) {
        // Update user information
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateQuery = $conn->prepare("UPDATE user SET fname = ?, lname = ?, email = ?, phone = ?, department = ?, experience = ?, bio = ?, password = ? WHERE userID = ?");
            $updateQuery->bind_param("ssssssssi", $firstName, $lastName, $email, $phone, $department, $experience, $bio, $hashedPassword, $userID);
        } else {
            $updateQuery = $conn->prepare("UPDATE user SET fname = ?, lname = ?, email = ?, phone = ?, department = ?, experience = ?, bio = ? WHERE userID = ?");
            $updateQuery->bind_param("sssssssi", $firstName, $lastName, $email, $phone, $department, $experience, $bio, $userID);
        }
        
        if ($updateQuery->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            header("Location: teacher-profile.php");
            exit();
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

// Get teaching statistics (you can expand this based on your LMS data)
$courseStats = [
    'total_courses' => 5,
    'active_students' => 127,
    'completed_lessons' => 45,
    'average_rating' => 4.8
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile - Al-Ghaya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="../../images/al-ghaya_logoForPrint.svg">
    <style>
        .profile-card { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img class="h-8 w-auto" src="../../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya">
                    <span class="ml-2 text-xl font-semibold text-gray-900">Al-Ghaya Teacher</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="teacher-dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="teacher-profile.php" class="bg-green-100 text-green-700 px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                    <button onclick="confirmTeacherSignOut()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Overview Card -->
            <div class="lg:col-span-1">
                <div class="profile-card rounded-xl shadow-lg overflow-hidden text-white">
                    <div class="p-8 text-center">
                        <div class="w-32 h-32 rounded-full bg-white bg-opacity-20 flex items-center justify-center mx-auto mb-4">
                            <span class="text-4xl font-bold text-white">
                                <?= strtoupper(substr($user['fname'], 0, 1) . substr($user['lname'], 0, 1)) ?>
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></h2>
                        <p class="text-green-100 mb-2"><?= htmlspecialchars($user['email']) ?></p>
                        <p class="text-green-200 text-sm mb-4"><?= htmlspecialchars($user['department'] ?? 'General Studies') ?></p>
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <p class="text-sm text-green-100">Member since</p>
                            <p class="font-semibold"><?= date('F Y', strtotime($user['dateCreated'])) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Teaching Stats -->
                <!-- <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Teaching Statistics</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600"><?= $courseStats['total_courses'] ?></p>
                            <p class="text-sm text-gray-600">Courses</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600"><?= $courseStats['active_students'] ?></p>
                            <p class="text-sm text-gray-600">Students</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-2xl font-bold text-purple-600"><?= $courseStats['completed_lessons'] ?></p>
                            <p class="text-sm text-gray-600">Lessons</p>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <p class="text-2xl font-bold text-yellow-600"><?= $courseStats['average_rating'] ?></p>
                            <p class="text-sm text-gray-600">Rating</p>
                        </div>
                    </div>
                </div> -->
            </div>

            <!-- Profile Edit Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Teacher Profile</h3>
                        <p class="text-gray-600">Update your professional information and settings</p>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6">
                        <?php if (isset($errors) && !empty($errors)): ?>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex">
                                    <svg class="h-5 w-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <div>
                                        <ul class="list-disc list-inside text-red-700">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?= htmlspecialchars($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Personal Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Personal Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" id="first_name" name="first_name" required
                                        value="<?= htmlspecialchars($user['fname']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" required
                                        value="<?= htmlspecialchars($user['lname']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Contact Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                    <input type="email" id="email" name="email" required
                                        value="<?= htmlspecialchars($user['email']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="tel" id="phone" name="phone"
                                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>

                        <!-- Professional Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Professional Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                    <select id="department" name="department"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                        <option value="">Select Department</option>
                                        <option value="Arabic Language" <?= ($user['department'] === 'Arabic Language') ? 'selected' : '' ?>>Arabic Language</option>
                                        <option value="Islamic Studies" <?= ($user['department'] === 'Islamic Studies') ? 'selected' : '' ?>>Islamic Studies</option>
                                        <option value="Quran Studies" <?= ($user['department'] === 'Quran Studies') ? 'selected' : '' ?>>Quran Studies</option>
                                        <option value="Islamic History" <?= ($user['department'] === 'Islamic History') ? 'selected' : '' ?>>Islamic History</option>
                                        <option value="Hadith Studies" <?= ($user['department'] === 'Hadith Studies') ? 'selected' : '' ?>>Hadith Studies</option>
                                        <option value="Islamic Jurisprudence" <?= ($user['department'] === 'Islamic Jurisprudence') ? 'selected' : '' ?>>Islamic Jurisprudence</option>
                                        <option value="General Studies" <?= ($user['department'] === 'General Studies') ? 'selected' : '' ?>>General Studies</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="experience" class="block text-sm font-medium text-gray-700 mb-2">Teaching Experience</label>
                                    <select id="experience" name="experience"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                        <option value="">Select Experience Level</option>
                                        <option value="Entry Level" <?= ($user['experience'] === 'Entry Level') ? 'selected' : '' ?>>Entry Level (0-2 years)</option>
                                        <option value="Experienced" <?= ($user['experience'] === 'Experienced') ? 'selected' : '' ?>>Experienced (3-5 years)</option>
                                        <option value="Senior" <?= ($user['experience'] === 'Senior') ? 'selected' : '' ?>>Senior (6-10 years)</option>
                                        <option value="Expert" <?= ($user['experience'] === 'Expert') ? 'selected' : '' ?>>Expert (10+ years)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Bio -->
                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Professional Bio</label>
                            <textarea id="bio" name="bio" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                placeholder="Share your teaching philosophy, qualifications, and experience..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>

                        <!-- Password Section -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Change Password</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password <span class="text-red-800 text-xs">*</span></label>
                                    <input type="password" id="current_password" name="current_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" id="new_password" name="new_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Leave blank if you are not changing password. Current password is required when changing password</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-6 border-t border-gray-200">
                            <button type="submit" name="update_profile"
                                class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition duration-200">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // SweetAlert confirmation for teacher sign out
        function confirmTeacherSignOut() {
            Swal.fire({
                title: 'End Teaching Session?',
                text: 'Are you sure you want to sign out and end your teaching session?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Sign Out',
                cancelButtonText: 'Continue Teaching',
                reverseButtons: true,
                customClass: {
                    popup: 'swal2-popup-custom',
                    title: 'swal2-title-custom',
                    content: 'swal2-content-custom'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show appreciation message for teachers
                    Swal.fire({
                        title: 'Thank You for Teaching!',
                        html: 'Your dedication to educating students is truly appreciated.<br><br><div class="text-sm text-gray-600">Signing out securely...</div>',
                        icon: 'success',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        timer: 2500,
                        didOpen: () => {
                            setTimeout(() => {
                                Swal.showLoading();
                            }, 1500);
                        }
                    });
                    
                    // Redirect to logout after the appreciation message
                    setTimeout(() => {
                        window.location.href = '../logout.php';
                    }, 2500);
                }
            });
        }
    </script>

    <?php if (isset($_SESSION['success_message'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?= $_SESSION['success_message'] ?>',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
    <?php unset($_SESSION['success_message']); endif; ?>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword && confirmPassword.length > 0) {
                this.setCustomValidity('Passwords do not match');
                this.classList.add('border-red-500');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-500');
            }
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
            }
            this.value = value;
        });
    </script>
</body>
</html>