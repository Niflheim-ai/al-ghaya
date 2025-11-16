<?php
session_start();
include('../../php/dbConnection.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$page_title = "Admin Profile";

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
            $updateQuery = $conn->prepare("UPDATE user SET fname = ?, lname = ?, email = ?, phone = ?, bio = ?, password = ? WHERE userID = ?");
            $updateQuery->bind_param("ssssssi", $firstName, $lastName, $email, $phone, $bio, $hashedPassword, $userID);
        } else {
            $updateQuery = $conn->prepare("UPDATE user SET fname = ?, lname = ?, email = ?, phone = ?, bio = ? WHERE userID = ?");
            $updateQuery->bind_param("sssssi", $firstName, $lastName, $email, $phone, $bio, $userID);
        }
        
        if ($updateQuery->execute()) {
            $_SESSION['success_message'] = "Administrator profile updated successfully!";
            $_SESSION['success_type'] = 'admin_profile_update';
            header("Location: admin-profile.php");
            exit();
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
    
    // Store errors in session for JavaScript access
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
    }
}

// Get system statistics
$statsQuery = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM user WHERE isActive = 1")->fetch_assoc()['count'],
    'total_students' => $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'student' AND isActive = 1")->fetch_assoc()['count'],
    'total_teachers' => $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'teacher' AND isActive = 1")->fetch_assoc()['count'],
    'total_admins' => $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'admin' AND isActive = 1")->fetch_assoc()['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Al-Ghaya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="../../images/al-ghaya_logoForPrint.svg">
    <style>
        .profile-card { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); }
        .swal2-popup-admin {
            border-radius: 12px !important;
            padding: 2rem !important;
        }
        .swal2-title-admin {
            font-size: 1.5rem !important;
            font-weight: 600 !important;
            color: #1f2937 !important;
        }
        .swal2-content-admin {
            font-size: 1rem !important;
            color: #6b7280 !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img class="h-8 w-auto" src="../../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya">
                    <span class="ml-2 text-xl font-semibold text-gray-900">Al-Ghaya Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="admin-dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="admin-profile.php" class="bg-red-100 text-red-700 px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                    <button onclick="confirmAdminSignOut()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200">
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
                        <p class="text-red-100 mb-2"><?= htmlspecialchars($user['email']) ?></p>
                        <div class="bg-white bg-opacity-10 rounded-lg p-3 mb-4">
                            <p class="text-sm text-red-100">System Administrator</p>
                            <p class="font-semibold">Al-Ghaya LMS</p>
                        </div>
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <p class="text-sm text-red-100">Admin since</p>
                            <p class="font-semibold"><?= date('F Y', strtotime($user['dateCreated'])) ?></p>
                        </div>
                    </div>
                </div>

                <!-- System Stats -->
                <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">System Overview</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600"><?= $statsQuery['total_users'] ?></p>
                            <p class="text-sm text-gray-600">Total Users</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600"><?= $statsQuery['total_students'] ?></p>
                            <p class="text-sm text-gray-600">Students</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-2xl font-bold text-purple-600"><?= $statsQuery['total_teachers'] ?></p>
                            <p class="text-sm text-gray-600">Teachers</p>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <p class="text-2xl font-bold text-red-600"><?= $statsQuery['total_admins'] ?></p>
                            <p class="text-sm text-gray-600">Admins</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Administrator Profile</h3>
                        <p class="text-gray-600">Update your administrative account information</p>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6" id="adminProfileForm">
                        <!-- Personal Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Personal Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" id="first_name" name="first_name" required
                                        value="<?= htmlspecialchars($user['fname']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" required
                                        value="<?= htmlspecialchars($user['lname']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
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
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="tel" id="phone" name="phone"
                                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>

                        <!-- Administrative Notes -->
                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Administrative Notes</label>
                            <textarea id="bio" name="bio" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                placeholder="Administrative notes, responsibilities, or other information..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>

                        <!-- Security Settings -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-yellow-800">Security Notice</h4>
                                    <p class="text-sm text-yellow-600 mt-1">As an administrator, ensure you use a strong password and keep your account secure. Consider enabling two-factor authentication.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Change Password</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password <span class="text-red-800 text-xs">*</span></label>
                                    <input type="password" id="current_password" name="current_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" id="new_password" name="new_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Leave blank if you are not changing password. Current password is required when changing password</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-6 border-t border-gray-200">
                            <button type="submit" name="update_profile"
                                class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition duration-200"
                                onclick="return confirmProfileUpdate()">
                                Update Administrator Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Enhanced SweetAlert functions
    function confirmAdminSignOut() {
        Swal.fire({
            title: 'Administrator Sign Out',
            text: 'Are you sure you want to sign out from the admin panel?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Sign Out',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'swal2-popup-admin',
                title: 'swal2-title-admin',
                content: 'swal2-content-admin'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Signing Out...',
                    text: 'Thank you for maintaining the Al-Ghaya system.',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    timer: 2000,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                setTimeout(() => {
                    window.location.href = '../logout.php';
                }, 2000);
            }
        });
    }

    function confirmProfileUpdate() {
        Swal.fire({
            title: 'Update Administrator Profile?',
            text: 'This will update your administrative account information.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Update Profile',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'swal2-popup-admin',
                title: 'swal2-title-admin',
                content: 'swal2-content-admin'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Updating Profile...',
                    text: 'Please wait while we update your administrator profile.',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit the form
                document.getElementById('adminProfileForm').submit();
            }
        });
        
        return false; // Prevent default form submission
    }

    // Handle success/error messages
    <?php if (isset($_SESSION['success_message'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($_SESSION['success_type'] === 'admin_profile_update'): ?>
                Swal.fire({
                    title: 'Profile Updated Successfully! 🎉',
                    html: 'Your administrator profile has been updated.<br><small class="text-gray-600">All changes have been saved to the system.</small>',
                    icon: 'success',
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Continue',
                    customClass: {
                        popup: 'swal2-popup-admin',
                        title: 'swal2-title-admin',
                        content: 'swal2-content-admin'
                    }
                });
            <?php else: ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?= $_SESSION['success_message'] ?>',
                    confirmButtonColor: '#dc2626',
                    customClass: {
                        popup: 'swal2-popup-admin',
                        title: 'swal2-title-admin',
                        content: 'swal2-content-admin'
                    }
                });
            <?php endif; ?>
        });
    <?php unset($_SESSION['success_message'], $_SESSION['success_type']); endif; ?>

    <?php if (isset($_SESSION['form_errors'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const errors = <?= json_encode($_SESSION['form_errors']) ?>;
            const errorList = errors.map(error => `<li class="text-left">${error}</li>`).join('');
            
            Swal.fire({
                title: 'Profile Update Failed',
                html: `<div class="text-left"><p class="mb-3">Please correct the following issues:</p><ul class="list-disc list-inside text-red-600">${errorList}</ul></div>`,
                icon: 'error',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Fix Issues',
                customClass: {
                    popup: 'swal2-popup-admin',
                    title: 'swal2-title-admin',
                    content: 'swal2-content-admin'
                }
            });
        });
    <?php unset($_SESSION['form_errors']); endif; ?>

    // Form validation
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