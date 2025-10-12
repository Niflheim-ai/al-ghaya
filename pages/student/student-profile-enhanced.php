<?php
session_start();
require_once '../../php/session-manager.php';

// Check if user is logged in and is a student
$userData = checkUserSession(['student'], '../login.php');
checkSessionTimeout();
updateUserActivity();

$current_page = "student-profile";
$page_title = "Student Profile";

// Include functions
require_once '../../php/functions.php';

$userID = $_SESSION['userID'];

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
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
        } elseif (!password_verify($currentPassword, $userData['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($newPassword) < 8) {
            $errors[] = "New password must be at least 8 characters long.";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New password and confirmation do not match.";
        }
    }
    
    if (empty($errors)) {
        try {
            // Build dynamic SQL based on available columns
            $updateFields = ['fname = ?', 'lname = ?', 'email = ?'];
            $params = [$firstName, $lastName, $email];
            $types = 'sss';
            
            // Check if phone column exists
            $columnsQuery = $conn->query("DESCRIBE user");
            $availableColumns = [];
            while ($row = $columnsQuery->fetch_assoc()) {
                $availableColumns[] = $row['Field'];
            }
            
            if (in_array('phone', $availableColumns)) {
                $updateFields[] = 'phone = ?';
                $params[] = $phone;
                $types .= 's';
            }
            
            if (in_array('bio', $availableColumns)) {
                $updateFields[] = 'bio = ?';
                $params[] = $bio;
                $types .= 's';
            }
            
            // Add password if updating
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateFields[] = 'password = ?';
                $params[] = $hashedPassword;
                $types .= 's';
            }
            
            // Add userID for WHERE clause
            $params[] = $userID;
            $types .= 'i';
            
            $sql = "UPDATE user SET " . implode(', ', $updateFields) . " WHERE userID = ?";
            $updateQuery = $conn->prepare($sql);
            $updateQuery->bind_param($types, ...$params);
            
            if ($updateQuery->execute()) {
                $_SESSION['success_message'] = "Profile updated successfully! Keep up the great learning progress!";
                $_SESSION['success_type'] = 'student_profile_update';
                header("Location: student-profile-enhanced.php");
                exit();
            } else {
                $errors[] = "Failed to update profile. Please try again.";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // Store errors in session for JavaScript access
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
    }
}

// Get learning progress stats
$progressStats = [
    'current_level' => $userData['level'] ?? 1,
    'total_points' => $userData['points'] ?? 0,
    'proficiency' => $userData['proficiency'] ?? 'beginner',
    'courses_completed' => 3, // placeholder
    'achievements_earned' => 7, // placeholder
    'study_streak' => 12 // placeholder
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Al-Ghaya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="../../images/al-ghaya_logoForPrint.svg">
    <style>
        .profile-card { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
        .swal2-popup-student {
            border-radius: 12px !important;
            padding: 2rem !important;
        }
        .swal2-title-student {
            font-size: 1.5rem !important;
            font-weight: 600 !important;
            color: #1f2937 !important;
        }
        .swal2-content-student {
            font-size: 1rem !important;
            color: #6b7280 !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include Student Navigation -->
    <?php include '../../components/student-nav.php'; ?>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Overview Card -->
            <div class="lg:col-span-1">
                <div class="profile-card rounded-xl shadow-lg overflow-hidden text-white">
                    <div class="p-8 text-center">
                        <div class="w-32 h-32 rounded-full bg-white bg-opacity-20 flex items-center justify-center mx-auto mb-4">
                            <span class="text-4xl font-bold text-white">
                                <?= getUserInitials($userData['fname'], $userData['lname']) ?>
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold mb-2"><?= htmlspecialchars($userData['fname'] . ' ' . $userData['lname']) ?></h2>
                        <p class="text-blue-100 mb-2"><?= htmlspecialchars($userData['email']) ?></p>
                        <div class="bg-white bg-opacity-10 rounded-lg p-3 mb-4">
                            <p class="text-sm text-blue-100">Level <?= $progressStats['current_level'] ?> Student</p>
                            <p class="font-semibold"><?= number_format($progressStats['total_points']) ?> Points</p>
                        </div>
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <p class="text-sm text-blue-100">Learning since</p>
                            <p class="font-semibold"><?= formatDate($userData['dateCreated'], 'F Y') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Learning Stats -->
                <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Learning Progress</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600"><?= $progressStats['courses_completed'] ?></p>
                            <p class="text-sm text-gray-600">Completed</p>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <p class="text-2xl font-bold text-yellow-600"><?= $progressStats['achievements_earned'] ?></p>
                            <p class="text-sm text-gray-600">Achievements</p>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <p class="text-2xl font-bold text-purple-600"><?= $progressStats['study_streak'] ?></p>
                            <p class="text-sm text-gray-600">Day Streak</p>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600"><?= ucfirst($progressStats['proficiency']) ?></p>
                            <p class="text-sm text-gray-600">Proficiency</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Student Profile</h3>
                        <p class="text-gray-600">Update your personal information and learning preferences</p>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6" id="studentProfileForm">
                        <!-- Personal Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Personal Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" id="first_name" name="first_name" required
                                        value="<?= htmlspecialchars($userData['fname']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" required
                                        value="<?= htmlspecialchars($userData['lname']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                                        value="<?= htmlspecialchars($userData['email']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number (Optional)</label>
                                    <input type="tel" id="phone" name="phone"
                                        value="<?= htmlspecialchars($userData['phone'] ?? '') ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>
                        </div>

                        <!-- About Me -->
                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">About Me</label>
                            <textarea id="bio" name="bio" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Tell us about yourself, your learning goals, interests..."><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                        </div>

                        <!-- Learning Goals Info -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-blue-800">Learning Tip</h4>
                                    <p class="text-sm text-blue-600 mt-1">Keep your profile updated to help us personalize your learning experience. Set clear goals and track your progress!</p>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Change Password</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <input type="password" id="current_password" name="current_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" id="new_password" name="new_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Leave blank to keep current password</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-6 border-t border-gray-200">
                            <button type="submit" name="update_profile"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition duration-200"
                                onclick="return confirmStudentProfileUpdate()">
                                Update My Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Enhanced SweetAlert functions for students
    function confirmStudentProfileUpdate() {
        Swal.fire({
            title: 'Update Learning Profile? 🎓',
            text: 'This will update your student profile and learning information.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Update Profile!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'swal2-popup-student',
                title: 'swal2-title-student',
                content: 'swal2-content-student'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show motivational loading state
                Swal.fire({
                    title: 'Updating Your Profile... ✏️',
                    html: 'Making your learning experience even better!<br><small class="text-blue-600">Please wait...</small>',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit the form
                document.getElementById('studentProfileForm').submit();
            }
        });
        
        return false; // Prevent default form submission
    }

    // Handle success/error messages
    <?php if (isset($_SESSION['success_message'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($_SESSION['success_type'] === 'student_profile_update'): ?>
                Swal.fire({
                    title: 'Profile Updated Successfully! 🎆',
                    html: 'Great job! Your learning profile has been updated.<br><small class="text-gray-600">Keep up the excellent progress in your Arabic studies!</small>',
                    icon: 'success',
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Continue Learning!',
                    customClass: {
                        popup: 'swal2-popup-student',
                        title: 'swal2-title-student',
                        content: 'swal2-content-student'
                    }
                });
            <?php else: ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?= $_SESSION['success_message'] ?>',
                    confirmButtonColor: '#2563eb',
                    customClass: {
                        popup: 'swal2-popup-student',
                        title: 'swal2-title-student',
                        content: 'swal2-content-student'
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
                title: 'Profile Update Failed 😔',
                html: `<div class="text-left"><p class="mb-3">Please fix these issues to continue:</p><ul class="list-disc list-inside text-red-600">${errorList}</ul></div>`,
                icon: 'error',
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Fix Issues',
                customClass: {
                    popup: 'swal2-popup-student',
                    title: 'swal2-title-student',
                    content: 'swal2-content-student'
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