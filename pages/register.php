<?php
session_start();
include('../php/dbConnection.php');
require '../php/login-api.php';

// If already logged in, redirect
if (isset($_SESSION['userID'])) {
    $role = $_SESSION['role'] ?? 'student';
    header("Location: {$role}/{$role}-dashboard.php");
    exit();
}

// Generate OAuth URL safely
$googleOAuthUrl = '';
try {
    $googleOAuthUrl = getSecureAuthUrl();
} catch (Exception $e) {
    error_log("OAuth URL generation error in register: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="../images/al-ghaya_logoForPrint.svg">
    <title>Al-Ghaya - Register</title>
    <style>
        .content { min-height: 100vh; }
        .form-container { max-width: 1200px; }
        .step { display: none; }
        .step.active { display: block; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="content flex justify-center items-center min-h-screen px-6">
        <div class="form-container flex w-full bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Back Button -->
            <a href="index.php" class="absolute top-4 left-4 z-10 hover:bg-white transition-all text-yellow-600 font-medium rounded-full p-2 shadow-md border">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>

            <!-- Left column - Welcome Section -->
            <div class="hidden lg:flex lg:flex-col justify-center items-start w-1/2 bg-gradient-to-br from-green-600 to-blue-700 text-white p-12">
                <div class="mb-8">
                    <img class="h-16 mb-6" src="../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                </div>
                <h1 class="text-5xl font-bold mb-4">Join Al-Ghaya!</h1>
                <p class="text-xl text-green-100 leading-relaxed">Start your Arabic and Islamic studies journey with our gamified learning platform.</p>
            </div>

            <!-- Right column - Registration Form -->
            <div class="w-full lg:w-1/2 p-8 lg:p-12">
                <div class="max-w-md mx-auto">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Account</h2>
                        <p class="text-gray-600">Start your learning journey</p>
                    </div>

                    <!-- Step 1: Registration Info -->
                    <div id="step1" class="step active">
                        <form id="registerForm" class="space-y-6">
                            <!-- Name Fields -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="first-name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input type="text" id="first-name" name="first-name" placeholder="John" required 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                </div>
                                <div>
                                    <label for="last-name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input type="text" id="last-name" name="last-name" placeholder="Doe" required 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="johndoe@gmail.com" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            </div>

                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                <div class="relative">
                                    <input type="password" id="password" name="password" placeholder="••••••••" required 
                                           class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                    <button type="button" onclick="togglePassword('password', 'eye-icon-1')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg id="eye-icon-1" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                                <div class="relative">
                                    <input type="password" id="confirm-password" name="confirm-password" placeholder="••••••••" required 
                                           class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                    <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg id="eye-icon-2" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="flex items-start">
                                <input id="terms" name="terms" type="checkbox" required 
                                       class="w-4 h-4 mt-1 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <label for="terms" class="ml-2 text-sm text-gray-600">
                                    I agree to the <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">Terms of Service</a>
                                </label>
                            </div>

                            <!-- Continue Button -->
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200">
                                Continue to Verification
                            </button>

                            <!-- Sign In Link -->
                            <p class="text-center text-sm text-gray-600">
                                Already have an account?
                                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-800">Sign in</a>
                            </p>
                        </form>
                    </div>

                    <!-- Step 2: OTP Verification -->
                    <div id="step2" class="step">
                        <div class="text-center mb-6">
                            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Verify Your Email</h3>
                            <p class="text-sm text-gray-600">We've sent a 6-digit code to <span id="emailDisplay" class="font-medium"></span></p>
                        </div>

                        <form id="otpForm" class="space-y-6">
                            <div>
                                <label for="otp" class="block text-sm font-medium text-gray-700 mb-2 text-center">Enter Verification Code</label>
                                <input type="text" id="otp" name="otp" maxlength="6" placeholder="000000" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center text-2xl font-bold tracking-widest focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200">
                                Verify & Create Account
                            </button>

                            <div class="text-center">
                                <button type="button" id="resendOTP" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Resend Code
                                </button>
                            </div>

                            <div class="text-center">
                                <button type="button" onclick="goBackToStep1()" class="text-sm text-gray-600 hover:text-gray-800">
                                    ← Back to registration
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Google OAuth (only if URL is available) -->
                    <?php if (!empty($googleOAuthUrl)): ?>
                    <div id="oauthSection" class="mt-8 mb-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or register with</span>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <a href="<?= $googleOAuthUrl ?>" class="w-full">
                                <button type="button" class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                                    <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    Continue with Google
                                </button>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let formData = {};
        
        function showAlert(icon, title, text, callback) {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                showConfirmButton: true,
                timer: icon === 'success' ? 3000 : undefined
            }).then(() => {
                if (callback) callback();
            });
        }
        
        function togglePassword(fieldId, iconId) {
            const passwordInput = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }
        
        function goToStep2() {
            document.getElementById('step1').classList.remove('active');
            document.getElementById('step2').classList.add('active');
            document.getElementById('oauthSection')?.classList.add('hidden');
            
            // Smooth scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function goBackToStep1() {
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step1').classList.add('active');
            document.getElementById('oauthSection')?.classList.remove('hidden');
            
            // Clear OTP input
            document.getElementById('otp').value = '';
            
            // Smooth scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Step 1: Registration Form
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('first-name').value.trim();
            const lastName = document.getElementById('last-name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            
            // Validation
            if (password.length < 8) {
                showAlert('error', 'Invalid Password', 'Password must be at least 8 characters long.');
                return;
            }
            
            if (password !== confirmPassword) {
                showAlert('error', 'Password Mismatch', 'Passwords do not match.');
                return;
            }
            
            // Store form data
            formData = { firstName, lastName, email, password };
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending verification code...
            `;
            
            try {
                // Send OTP
                const response = await fetch('../php/otp-handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=send_otp&email=${encodeURIComponent(email)}&firstName=${encodeURIComponent(firstName)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('emailDisplay').textContent = email;
                    
                    // Show success message then transition
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent!',
                        text: 'Please check your email for the verification code.',
                        timer: 2000,
                        showConfirmButton: false,
                        didClose: () => {
                            goToStep2();
                        }
                    });
                } else {
                    showAlert('error', 'Error', result.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Network Error', 'Failed to send verification code. Please check your connection.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        });
        
        // Step 2: OTP Verification
        document.getElementById('otpForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const otp = document.getElementById('otp').value.trim();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            
            if (otp.length !== 6) {
                showAlert('error', 'Invalid Code', 'Please enter a 6-digit code.');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Verifying...
            `;
            
            try {
                // Verify OTP
                const verifyResponse = await fetch('../php/otp-handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=verify_otp&email=${encodeURIComponent(formData.email)}&otp=${otp}`
                });
                
                const verifyResult = await verifyResponse.json();
                
                if (verifyResult.success) {
                    // Update button to show creating account
                    submitBtn.innerHTML = `
                        <svg class="animate-spin inline-block w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creating your account...
                    `;
                    
                    // Create account
                    const createResponse = await fetch('register.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `create-account=1&first-name=${encodeURIComponent(formData.firstName)}&last-name=${encodeURIComponent(formData.lastName)}&email=${encodeURIComponent(formData.email)}&password=${encodeURIComponent(formData.password)}&confirm-password=${encodeURIComponent(formData.password)}&terms=on&verified=1`
                    });
                    
                    // Show success and redirect
                    Swal.fire({
                        icon: 'success',
                        title: 'Account Created!',
                        html: 'Welcome to Al-Ghaya!<br><small>Redirecting to login...</small>',
                        timer: 2500,
                        showConfirmButton: false,
                        willClose: () => {
                            window.location.href = 'login.php';
                        }
                    });
                } else {
                    showAlert('error', 'Verification Failed', verifyResult.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('error', 'Network Error', 'Failed to verify code. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            }
        });
        
        // Resend OTP
        document.getElementById('resendOTP').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.textContent;
            
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin inline-block w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending...
            `;
            
            try {
                const response = await fetch('../php/otp-handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=send_otp&email=${encodeURIComponent(formData.email)}&firstName=${encodeURIComponent(formData.firstName)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Code Resent!', 'A new verification code has been sent to your email.');
                } else {
                    showAlert('error', 'Error', result.message);
                }
            } catch (error) {
                showAlert('error', 'Network Error', 'Failed to resend code. Please try again.');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
        
        // Auto-focus OTP input and format
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Limit to 6 digits
                if (this.value.length > 6) {
                    this.value = this.value.slice(0, 6);
                }
            });
        }
    </script>

    
    <?php
        // Handle final account creation (after OTP verification)
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create-account']) && isset($_POST['verified'])) {
            $inputFirstName = trim($_POST['first-name'] ?? '');
            $inputLastName = trim($_POST['last-name'] ?? '');
            $inputEmail = trim($_POST['email'] ?? '');
            $inputPassword = $_POST['password'] ?? '';
            
            // Create account
            $hashedPassword = password_hash($inputPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $role = 'student';
            $level = 1;
            $points = 0;
            $proficiency = 'beginner';
            
            $insertStmt = $conn->prepare("INSERT INTO user (email, password, fname, lname, role, level, points, proficiency, dateCreated, isActive) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)");
            $insertStmt->bind_param("sssssiis", $inputEmail, $hashedPassword, $inputFirstName, $inputLastName, $role, $level, $points, $proficiency);
            $insertStmt->execute();
            $insertStmt->close();
            
            // Delete used OTP
            $deleteStmt = $conn->prepare("DELETE FROM email_verification_otps WHERE email = ?");
            $deleteStmt->bind_param("s", $inputEmail);
            $deleteStmt->execute();
            $deleteStmt->close();
        }
    ?>
</body>
</html>
