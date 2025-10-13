<?php
session_start();
include('../php/dbConnection.php');

$token = $_GET['token'] ?? '';
$errors = [];
$success = false;
$tokenValid = false;
$userEmail = '';
$debugInfo = [];

// Debug mode (remove in production)
$debugMode = false; // Set to false in production

// Validate token
if (empty($token)) {
    $errors[] = "Reset token is missing.";
    if ($debugMode) $debugInfo[] = "No token provided in URL";
} else {
    if ($debugMode) {
        $debugInfo[] = "Token received: " . substr($token, 0, 10) . "...";
        $debugInfo[] = "Token length: " . strlen($token);
    }
    
    // First, let's check if the token exists at all
    $checkTokenQuery = $conn->prepare("SELECT email, expires_at, used_at, created_at FROM password_resets WHERE token = ?");
    $checkTokenQuery->bind_param("s", $token);
    $checkTokenQuery->execute();
    $checkResult = $checkTokenQuery->get_result();
    
    if ($checkResult->num_rows === 0) {
        $errors[] = "Invalid reset token. The token may have been deleted or never existed.";
        if ($debugMode) $debugInfo[] = "Token not found in database";
    } else {
        $tokenData = $checkResult->fetch_assoc();
        $userEmail = $tokenData['email'];
        
        if ($debugMode) {
            $debugInfo[] = "Token found for email: " . $userEmail;
            $debugInfo[] = "Token created: " . $tokenData['created_at'];
            $debugInfo[] = "Token expires: " . $tokenData['expires_at'];
            $debugInfo[] = "Token used: " . ($tokenData['used_at'] ?? 'No');
            $debugInfo[] = "Current time: " . date('Y-m-d H:i:s');
        }
        
        // Check if token is already used
        if ($tokenData['used_at'] !== null) {
            $errors[] = "This reset link has already been used. Please request a new password reset.";
            if ($debugMode) $debugInfo[] = "Token already used at: " . $tokenData['used_at'];
        }
        // Check if token is expired
        else if (strtotime($tokenData['expires_at']) < time()) {
            $errors[] = "This reset link has expired. Please request a new password reset.";
            if ($debugMode) {
                $debugInfo[] = "Token expired. Expires: " . strtotime($tokenData['expires_at']) . ", Current: " . time();
                $debugInfo[] = "Time difference: " . (time() - strtotime($tokenData['expires_at'])) . " seconds";
            }
        }
        else {
            $tokenValid = true;
            if ($debugMode) $debugInfo[] = "Token is valid and not expired";
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newPassword']) && $tokenValid) {
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validate passwords
    if (empty($newPassword)) {
        $errors[] = "New password is required.";
    } elseif (strlen($newPassword) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
        $errors[] = "Password must contain at least one lowercase letter, one uppercase letter, and one number.";
    }
    
    if (empty($errors)) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update user password
        $updateQuery = $conn->prepare("UPDATE user SET password = ? WHERE email = ? AND isActive = 1");
        $updateQuery->bind_param("ss", $hashedPassword, $userEmail);
        
        if ($updateQuery->execute() && $updateQuery->affected_rows > 0) {
            // Mark token as used
            $markUsedQuery = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
            $markUsedQuery->bind_param("s", $token);
            $markUsedQuery->execute();
            
            // Clear any existing sessions for this user for security
            if (isset($_SESSION['userID'])) {
                session_destroy();
                session_start();
            }
            
            $success = true;
            $successMessage = "Your password has been successfully updated! You can now log in with your new password.";
            
            // Log the password reset for security
            error_log("Password successfully reset for user: " . $userEmail);
        } else {
            $errors[] = "Failed to update password. Please try again or contact support.";
            if ($debugMode) $debugInfo[] = "Database update failed or no user found with email: " . $userEmail;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Al-Ghaya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="../images/al-ghaya_logoForPrint.svg">
    <style>
        .content { min-height: 100vh; }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="content flex justify-center items-center min-h-screen px-6">
        <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <img class="h-12 mx-auto mb-4" src="../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Reset Password</h1>
                <p class="text-gray-600">Enter your new password below</p>
            </div>

            <?php if ($debugMode && !empty($debugInfo)): ?>
                <!-- Debug Information (Remove in Production) -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <h4 class="text-sm font-bold text-yellow-800 mb-2">Debug Information:</h4>
                    <ul class="text-xs text-yellow-700 space-y-1">
                        <?php foreach ($debugInfo as $info): ?>
                            <li><?= htmlspecialchars($info) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors) || !$tokenValid): ?>
                <!-- Error Message -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <ul class="list-disc list-inside text-red-700 text-sm">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="text-center space-y-4">
                    <a href="forgot-password.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Request New Reset Link
                    </a>
                    <div>
                        <a href="login.php" class="text-gray-600 hover:text-gray-800 text-sm">← Back to Login</a>
                    </div>
                </div>
            <?php elseif ($success): ?>
                <!-- Success Message -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-green-700">
                            <?= $successMessage ?? '' ?>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="login.php" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 0a5 5 0 11-10 0 5 5 0 0110 0z"></path>
                        </svg>
                        Sign In Now
                    </a>
                </div>
            <?php else: ?>
                <!-- Reset Form -->
                <form method="POST" class="space-y-6" id="resetForm">
                    <!-- User Email Display -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                            <span class="text-blue-800 text-sm">Resetting password for: <strong><?= htmlspecialchars($userEmail) ?></strong></span>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <div class="relative">
                            <input type="password" id="newPassword" name="newPassword" 
                                   placeholder="Enter your new password" required minlength="8"
                                   class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                   oninput="checkPasswordStrength()">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <button type="button" onclick="togglePassword('newPassword', 'eyeIcon1')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg id="eyeIcon1" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        <!-- Password Strength Indicator -->
                        <div class="mt-2">
                            <div id="passwordStrength" class="password-strength bg-gray-200"></div>
                            <p id="strengthText" class="text-xs text-gray-500 mt-1">Password strength will appear here</p>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <div class="relative">
                            <input type="password" id="confirmPassword" name="confirmPassword" 
                                   placeholder="Confirm your new password" required minlength="8"
                                   class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                   oninput="checkPasswordMatch()">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <button type="button" onclick="togglePassword('confirmPassword', 'eyeIcon2')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg id="eyeIcon2" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        <p id="matchText" class="text-xs text-gray-500 mt-1"></p>
                    </div>

                    <!-- Password Requirements -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-800 mb-2">Password Requirements:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li id="req-length" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                At least 8 characters long
                            </li>
                            <li id="req-lower" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                One lowercase letter
                            </li>
                            <li id="req-upper" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                One uppercase letter
                            </li>
                            <li id="req-number" class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                One number
                            </li>
                        </ul>
                    </div>

                    <button type="submit" id="submitBtn" disabled
                        class="w-full bg-gray-400 text-white font-medium py-3 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 cursor-not-allowed">
                        Update Password
                    </button>
                </form>

                <!-- Security Notice -->
                <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Security Notice</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                After updating your password, you'll need to sign in again. For your security, all existing sessions will be terminated.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>`;
            } else {
                input.type = 'password';
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('newPassword').value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            // Check requirements
            const requirements = [
                { id: 'req-length', test: password.length >= 8, text: 'At least 8 characters' },
                { id: 'req-lower', test: /[a-z]/.test(password), text: 'One lowercase letter' },
                { id: 'req-upper', test: /[A-Z]/.test(password), text: 'One uppercase letter' },
                { id: 'req-number', test: /\d/.test(password), text: 'One number' }
            ];
            
            requirements.forEach(req => {
                const element = document.getElementById(req.id);
                const icon = element.querySelector('svg');
                
                if (req.test) {
                    strength++;
                    element.classList.remove('text-gray-600');
                    element.classList.add('text-green-600');
                    icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>`;
                    icon.classList.remove('text-gray-400');
                    icon.classList.add('text-green-500');
                } else {
                    element.classList.remove('text-green-600');
                    element.classList.add('text-gray-600');
                    icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>`;
                    icon.classList.remove('text-green-500');
                    icon.classList.add('text-gray-400');
                }
            });
            
            // Update strength bar
            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
            const texts = ['Very Weak', 'Weak', 'Fair', 'Strong'];
            const widths = ['25%', '50%', '75%', '100%'];
            
            strengthBar.className = 'password-strength ' + (colors[strength - 1] || 'bg-gray-200');
            strengthBar.style.width = strength > 0 ? widths[strength - 1] : '0%';
            strengthText.textContent = strength > 0 ? texts[strength - 1] : 'Enter a password';
            
            checkFormValid();
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const matchText = document.getElementById('matchText');
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    matchText.textContent = '✓ Passwords match';
                    matchText.className = 'text-xs text-green-600 mt-1';
                } else {
                    matchText.textContent = '✗ Passwords do not match';
                    matchText.className = 'text-xs text-red-600 mt-1';
                }
            } else {
                matchText.textContent = '';
            }
            
            checkFormValid();
        }
        
        function checkFormValid() {
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const submitBtn = document.getElementById('submitBtn');
            
            const isValid = password.length >= 8 && 
                           /[a-z]/.test(password) && 
                           /[A-Z]/.test(password) && 
                           /\d/.test(password) && 
                           password === confirm;
            
            if (isValid) {
                submitBtn.disabled = false;
                submitBtn.className = 'w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2';
                submitBtn.textContent = 'Update Password';
            } else {
                submitBtn.disabled = true;
                submitBtn.className = 'w-full bg-gray-400 text-white font-medium py-3 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 cursor-not-allowed';
                submitBtn.textContent = 'Complete Requirements';
            }
        }
        
        // Auto-focus first password field
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('newPassword');
            if (passwordInput) {
                passwordInput.focus();
            }
        });
        
        // Form submission confirmation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Update Password?',
                text: 'Are you sure you want to change your password? You will need to log in again.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Update Password',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    </script>
</body>
</html>