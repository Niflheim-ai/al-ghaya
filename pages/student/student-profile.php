<?php
session_start();
include('../../php/dbConnection.php');

// Check if user is logged in and is a student
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$userID = $_SESSION['userID'];

// Get user information
$userQuery = $conn->prepare("SELECT * FROM user WHERE userID = ?");
$userQuery->bind_param("i", $userID);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

// Handle form submission for profile update (basic info only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);
    
    $errors = [];
    
    // Validate required fields
    if (empty($firstName) || empty($lastName)) {
        $errors[] = "First name and last name are required.";
    }
    
    if (empty($errors)) {
        // Update user information (without email and password)
        $updateQuery = $conn->prepare("UPDATE user SET fname = ?, lname = ?, phone = ?, bio = ? WHERE userID = ?");
        $updateQuery->bind_param("ssssi", $firstName, $lastName, $phone, $bio, $userID);
        
        if ($updateQuery->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            header("Location: student-profile.php");
            exit();
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

// Get user statistics
$statsQuery = $conn->prepare("SELECT level, points, proficiency FROM user WHERE userID = ?");
$statsQuery->bind_param("i", $userID);
$statsQuery->execute();
$stats = $statsQuery->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Al-Ghaya Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="../../images/al-ghaya_logoForPrint.svg">
    <style>
        .profile-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .nav-link.active { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img class="h-8 w-auto" src="../../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya">
                    <span class="ml-2 text-xl font-semibold text-gray-900">Al-Ghaya</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="student-dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="student-profile.php" class="bg-blue-100 text-blue-700 px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                    <button onclick="confirmStudentSignOut()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200">
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
                        <p class="text-blue-100 mb-4"><?= htmlspecialchars($user['email']) ?></p>
                        <div class="flex justify-center space-x-4 text-sm">
                            <div class="text-center">
                                <p class="font-semibold text-lg"><?= $stats['level'] ?></p>
                                <p class="text-blue-100">Level</p>
                            </div>
                            <div class="text-center">
                                <p class="font-semibold text-lg"><?= number_format($stats['points']) ?></p>
                                <p class="text-blue-100">Points</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Form -->
            <div class="lg:col-span-2 space-y-6">
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
                
                <!-- Basic Profile Information -->
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                        <p class="text-gray-600 text-sm">Update your personal information</p>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                <input type="text" id="first_name" name="first_name" required
                                    value="<?= htmlspecialchars($user['fname']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required
                                    value="<?= htmlspecialchars($user['lname']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                            <textarea id="bio" name="bio" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" name="update_profile"
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition duration-200">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Email Section -->
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Change Email</h3>
                        <p class="text-gray-600 text-sm">Update your email address with verification</p>
                    </div>
                    
                    <div class="p-6">
                        <form id="changeEmailForm" class="space-y-4">
                            <div>
                                <label for="current-email" class="block text-sm font-medium text-gray-700 mb-2">Current Email</label>
                                <input type="email" id="current-email" value="<?= htmlspecialchars($user['email']) ?>" disabled 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                            </div>
                            
                            <div>
                                <label for="new-email" class="block text-sm font-medium text-gray-700 mb-2">New Email</label>
                                <input type="email" id="new-email" name="new_email" placeholder="newemail@example.com" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                                Send Verification Code
                            </button>
                        </form>
                        
                        <!-- OTP Verification (Hidden initially) -->
                        <div id="emailOtpSection" class="hidden mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h3 class="font-semibold text-blue-900 mb-3">Verify Your New Email</h3>
                            <p class="text-sm text-blue-800 mb-4">We've sent a verification code to <span id="newEmailDisplay" class="font-medium"></span></p>
                            <form id="verifyEmailForm" class="space-y-4">
                                <div>
                                    <label for="email-otp" class="block text-sm font-medium text-gray-700 mb-2">Verification Code</label>
                                    <input type="text" id="email-otp" name="otp" maxlength="6" placeholder="000000" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-center text-2xl font-bold tracking-widest">
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                                        Verify & Update Email
                                    </button>
                                    <button type="button" id="resendEmailOTP" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                        Resend Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password Section -->
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
                        <p class="text-gray-600 text-sm">Update your password with email verification</p>
                    </div>
                    
                    <div class="p-6">
                        <form id="changePasswordForm" class="space-y-4">
                            <div>
                                <label for="current-password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <div class="relative">
                                    <input type="password" id="current-password" name="current_password" required 
                                           class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <button type="button" onclick="togglePasswordVisibility('current-password', 'eye-current')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg id="eye-current" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label for="new-password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <div class="relative">
                                    <input type="password" id="new-password" name="new_password" required 
                                           class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <button type="button" onclick="togglePasswordVisibility('new-password', 'eye-new')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg id="eye-new" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Minimum 8 characters</p>
                            </div>
                            
                            <div>
                                <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <div class="relative">
                                    <input type="password" id="confirm-password" name="confirm_password" required 
                                           class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <button type="button" onclick="togglePasswordVisibility('confirm-password', 'eye-confirm')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg id="eye-confirm" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                                Request Verification Code
                            </button>
                        </form>
                        
                        <!-- Password OTP Verification (Hidden initially) -->
                        <div id="passwordOtpSection" class="hidden mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h3 class="font-semibold text-blue-900 mb-3">Verify Your Identity</h3>
                            <p class="text-sm text-blue-800 mb-4">We've sent a verification code to <span class="font-medium"><?= htmlspecialchars($user['email']) ?></span></p>
                            <form id="verifyPasswordForm" class="space-y-4">
                                <div>
                                    <label for="password-otp" class="block text-sm font-medium text-gray-700 mb-2">Verification Code</label>
                                    <input type="text" id="password-otp" name="otp" maxlength="6" placeholder="000000" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-center text-2xl font-bold tracking-widest">
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                                        Verify & Change Password
                                    </button>
                                    <button type="button" id="resendPasswordOTP" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                        Resend Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // SweetAlert confirmation for student sign out
        function confirmStudentSignOut() {
            Swal.fire({
                title: 'End Learning Session?',
                text: 'Are you sure you want to sign out and end your learning session?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Sign Out',
                cancelButtonText: 'Stay Learning',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Great Progress Today!',
                        html: 'Keep up the excellent work on your Arabic learning journey!<br><br><div class="text-sm text-gray-600">Signing out securely...</div>',
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
                    
                    setTimeout(() => {
                        window.location.href = '../logout.php';
                    }, 2500);
                }
            });
        }

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
        let newEmailValue = '';
        
        function togglePasswordVisibility(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>`;
            } else {
                field.type = 'password';
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }
        
        // Change Email Form
        document.getElementById('changeEmailForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const newEmail = document.getElementById('new-email').value.trim();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            if (!newEmail) {
                Swal.fire('Error', 'Please enter a new email address', 'error');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending code...
            `;
            
            try {
                const response = await fetch('../../php/otp-handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=send_otp&email=${encodeURIComponent(newEmail)}&firstName=<?= htmlspecialchars($user['fname']) ?>`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    newEmailValue = newEmail;
                    document.getElementById('newEmailDisplay').textContent = newEmail;
                    document.getElementById('emailOtpSection').classList.remove('hidden');
                    Swal.fire('Success', 'Verification code sent to your new email', 'success');
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Failed to send verification code', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
        
        // Verify Email OTP
        document.getElementById('verifyEmailForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const otp = document.getElementById('email-otp').value.trim();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            if (otp.length !== 6) {
                Swal.fire('Error', 'Please enter a 6-digit code', 'error');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Updating...
            `;
            
            try {
                const response = await fetch('../../php/profile-update-handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=verify_and_update_email&new_email=${encodeURIComponent(newEmailValue)}&otp=${otp}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Updated!',
                        text: 'Your email has been successfully updated.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', result.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                Swal.fire('Error', 'Failed to update email', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
        
        // Resend Email OTP
        document.getElementById('resendEmailOTP').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.textContent;
            
            btn.disabled = true;
            btn.textContent = 'Sending...';
            
            try {
                const response = await fetch('../../php/otp-handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=send_otp&email=${encodeURIComponent(newEmailValue)}&firstName=<?= htmlspecialchars($user['fname']) ?>`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire('Success', 'New code sent to your email', 'success');
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Failed to resend code', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
        
        // Change Password Form - Step 1: Request OTP
        document.getElementById('verifyPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const otp = document.getElementById('password-otp').value.trim();
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        if (otp.length !== 6) {
            Swal.fire('Error', 'Please enter a 6-digit code', 'error');
            return;
        }
        
        // Make sure we have the password data
        if (!window.passwordChangeData) {
            Swal.fire('Error', 'Session expired. Please try again.', 'error');
            document.getElementById('passwordOtpSection').classList.add('hidden');
            document.getElementById('changePasswordForm').reset();
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Updating password...
        `;
        
        try {
            const response = await fetch('../../php/profile-update-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=verify_password_change&current_password=${encodeURIComponent(window.passwordChangeData.current_password)}&new_password=${encodeURIComponent(window.passwordChangeData.new_password)}&confirm_password=${encodeURIComponent(window.passwordChangeData.confirm_password)}&otp=${encodeURIComponent(otp)}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Clear stored data
                delete window.passwordChangeData;
                
                // Reset forms
                document.getElementById('changePasswordForm').reset();
                document.getElementById('passwordOtpSection').classList.add('hidden');
                document.getElementById('password-otp').value = '';
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Password Updated!',
                    text: 'Your password has been successfully changed.',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to update password. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
        
        // Verify Password OTP and Change Password
        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Client-side validation
        if (newPassword.length < 8) {
            Swal.fire('Error', 'New password must be at least 8 characters', 'error');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            Swal.fire('Error', 'New passwords do not match', 'error');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Verifying password...
        `;
        
        try {
            // Step 1: Verify current password FIRST
            const verifyResponse = await fetch('../../php/profile-update-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=verify_current_password&current_password=${encodeURIComponent(currentPassword)}`
            });
            
            const verifyResult = await verifyResponse.json();
            
            if (!verifyResult.success) {
                Swal.fire('Error', verifyResult.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }
            
            // Step 2: If password is correct, send OTP
            submitBtn.innerHTML = `
                <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending code...
            `;
            
            // Store password data temporarily
            window.passwordChangeData = {
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            };
            
            // Send OTP to current email (SKIP duplicate check)
            const otpResponse = await fetch('../../php/otp-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_otp&email=${encodeURIComponent('<?= htmlspecialchars($user['email']) ?>')}&firstName=<?= htmlspecialchars($user['fname']) ?>&skip_duplicate_check=true`
            });
            
            const otpResult = await otpResponse.json();
            
            if (otpResult.success) {
                // Show OTP section
                document.getElementById('passwordOtpSection').classList.remove('hidden');
                Swal.fire('Success', 'Verification code sent to your email', 'success');
            } else {
                Swal.fire('Error', otpResult.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to process request', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
        
        // Resend Password OTP
        document.getElementById('resendPasswordOTP').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.textContent;
            
            btn.disabled = true;
            btn.textContent = 'Sending...';
            
            try {
                const response = await fetch('../../php/otp-handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=send_otp&email=${encodeURIComponent('<?= htmlspecialchars($user['email']) ?>')}&firstName=<?= htmlspecialchars($user['fname']) ?>&skip_duplicate_check=true`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire('Success', 'New code sent to your email', 'success');
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Failed to resend code', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
        
        // Auto-format OTP inputs
        document.getElementById('email-otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
        
        document.getElementById('password-otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>
</html>
