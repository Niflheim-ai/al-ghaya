<?php
session_start();
include('../php/dbConnection.php');
require '../php/login-api.php';
require_once '../php/achievement-handler.php';

// If already logged in, redirect based on role
if (isset($_SESSION['userID']) && isset($_SESSION['role'])) {
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

// Handle error messages from OAuth and other sources
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMessage = '';
$errorType = 'error'; // Default to error type

switch ($error) {
    case 'google_account_not_registered':
    case 'account_not_found':
        $errorMessage = 'Your Google account was not found in our system. Don\'t worry, you can sign up with Google and we\'ll create an account for you!';
        $errorType = 'info';
        break;
    case 'account_creation_failed':
        $errorMessage = 'Failed to create your account. Please try again or contact support.';
        break;
    case 'auth_failed':
    case 'oauth_error':
        $errorMessage = 'Google authentication failed. Please try again.';
        break;
    case 'oauth_cancelled':
        $errorMessage = 'Google authentication was cancelled. You can try again anytime.';
        $errorType = 'warning';
        break;
    case 'csrf_validation_failed':
        $errorMessage = 'Security validation failed. Please try signing in again.';
        break;
    case 'session_timeout':
        $errorMessage = 'Your session has expired. Please login again.';
        break;
    case 'account_deactivated':
        $errorMessage = 'Your account has been deactivated. Please contact support for assistance.';
        break;
    case 'token_exchange_failed':
        $errorMessage = 'Authentication failed during token exchange. Please try again.';
        break;
    case 'user_info_failed':
        $errorMessage = 'Failed to retrieve your Google profile information. Please try again.';
        break;
    case 'oauth_processing_error':
        $errorMessage = 'An error occurred during authentication. Please try again.';
        break;
    case 'invalid_oauth_response':
        $errorMessage = 'Invalid authentication response. Please try signing in again.';
        break;
}

// Generate secure OAuth URL
$googleOAuthUrl = '';
try {
    if (function_exists('getSecureAuthUrl')) {
        $googleOAuthUrl = getSecureAuthUrl();
    } else {
        // Fallback: generate OAuth URL directly
        if (isset($client)) {
            $state = bin2hex(random_bytes(32));
            $_SESSION['oauth_state'] = $state;
            $_SESSION['oauth_timestamp'] = time();
            $client->setState($state);
            $googleOAuthUrl = $client->createAuthUrl();
        }
    }
} catch (Exception $e) {
    error_log("OAuth URL generation error: " . $e->getMessage());
    $errorMessage = 'Google login is temporarily unavailable. Please use email login.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google API -->
    <meta name="google-signin-client_id" content="704460822405-0gjtdkl1acustankf6k9p3o3444lpb7g.apps.googleusercontent.com">
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="../images/al-ghaya_logoForPrint.svg">
    <title>Al-Ghaya - Login</title>
    <style>
        .content { min-height: 100vh; }
        .form-container { max-width: 1200px; }
        .bg-primary { background-color: #10375b; }
        .text-primary { color: #10375b; }
        .border-primary { border-color: #10375b; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Content -->
    <div class="content flex justify-center items-center min-h-screen px-6">
        <div class="form-container flex w-full bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Back Button -->
            <a href="index.php" class="absolute top-4 left-4 z-10 hover:bg-white transition-all text-yellow-600 font-medium rounded-full p-2 shadow-md border">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>

            <!-- Error/Info Message -->
            <?php if (!empty($errorMessage)): ?>
                <div class="fixed top-4 right-4 z-50 p-4 mb-4 rounded-lg border shadow-lg max-w-md <?php 
                    echo $errorType === 'info' ? 'text-blue-700 bg-blue-100 border-blue-300' : 
                        ($errorType === 'warning' ? 'text-yellow-700 bg-yellow-100 border-yellow-300' : 
                         'text-red-700 bg-red-100 border-red-300'); 
                ?>">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <?php if ($errorType === 'info'): ?>
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            <?php elseif ($errorType === 'warning'): ?>
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            <?php else: ?>
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            <?php endif; ?>
                        </svg>
                        <div>
                            <?= htmlspecialchars($errorMessage) ?>
                            <?php if ($error === 'google_account_not_registered'): ?>
                                <div class="mt-2">
                                    <button onclick="showGoogleSignUpInfo()" class="text-blue-800 underline hover:text-blue-900 text-sm font-medium">
                                        Learn more about Google Sign-up →
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-auto pl-3">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Left column - Welcome Section -->
            <div class="hidden lg:flex lg:flex-col justify-center items-start w-1/2 bg-gradient-to-br from-blue-900 to-blue-700 text-white p-12">
                <div class="mb-8">
                    <img class="h-16 mb-6" src="../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                </div>
                <h1 class="text-5xl font-bold mb-4">Welcome Back!</h1>
                <p class="text-xl text-blue-100 leading-relaxed">Sign in to continue your Arabic and Islamic studies journey with Al-Ghaya LMS.</p>
                <div class="mt-8 text-blue-200">
                    <p>✓ Track your progress</p>
                    <p>✓ Earn achievements</p>
                    <p>✓ Interactive learning</p>
                </div>
            </div>

            <!-- Right column - Login Form -->
            <div class="w-full lg:w-1/2 p-8 lg:p-12">
                <div class="max-w-md mx-auto">
                    <div class="text-center mb-8 lg:hidden">
                        <img class="h-12 mx-auto mb-4" src="../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                        <h2 class="text-3xl font-bold text-gray-900">Login</h2>
                    </div>
                    
                    <div class="hidden lg:block mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Sign In</h2>
                        <p class="text-gray-600">Access your learning dashboard</p>
                    </div>

                    <form action="login.php" method="POST" class="space-y-6">
                        <!-- Email Input -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <div class="relative">
                                <input type="email" id="email" name="email" placeholder="johndoe@gmail.com" required 
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" placeholder="••••••••" required 
                                       class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg id="eye-icon" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-600">Remember me</span>
                            </label>
                            <a href="forgot-password.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Forgot Password?</a>
                        </div>

                        <!-- Login Button -->
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Sign In
                        </button>

                        <!-- Sign Up Link -->
                        <p class="text-center text-sm text-gray-600">
                            Don't have an account?
                            <a href="register.php" class="font-medium text-blue-600 hover:text-blue-800">Create account</a>
                        </p>
                    </form>

                    <!-- Divider -->
                    <div class="mt-8 mb-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or continue with</span>
                            </div>
                        </div>
                    </div>

                    <!-- Google OAuth Button -->
                    <div class="space-y-3">
                        <?php if (!empty($googleOAuthUrl)): ?>
                            <a href="<?= $googleOAuthUrl ?>" class="w-full">
                                <button type="button" class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                                    <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    Continue with Google
                                </button>
                            </a>
                        <?php else: ?>
                            <div class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-400 bg-gray-100">
                                <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                                Google Login Temporarily Unavailable
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
        $alertScript = '';
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $alertScript = "showAlert('error', 'Login Failed', 'All fields are required.');";  
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $alertScript = "showAlert('error', 'Login Failed', 'Invalid email format.');";  
            } else {
                // Check unified user table first
                $stmt = $conn->prepare("SELECT userID, email, password, role FROM user WHERE email = ? AND isActive = 1");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();
                    
                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($userID, $dbEmail, $hashedPassword, $role);
                        $stmt->fetch();
                        
                        if (password_verify($password, $hashedPassword)) {
                            $_SESSION['userID'] = $userID;
                            $_SESSION['role'] = $role;
                            $_SESSION['email'] = $dbEmail;
                            $_SESSION['last_activity'] = time();
                            
                            // Update last login
                            $updateLogin = $conn->prepare("UPDATE user SET lastLogin = NOW() WHERE userID = ?");
                            $updateLogin->bind_param("i", $userID);
                            $updateLogin->execute();

                            if ($role === 'student') {
                                require_once '../php/achievement-handler.php';
                                
                                error_log("Checking achievements for student ID: " . $userID);
                                
                                $achievementHandler = new AchievementHandler($conn, $userID);
                                $achievementHandler->checkFirstLogin();
                                
                                // Store newly unlocked for display on dashboard
                                $newlyUnlocked = $achievementHandler->getNewlyUnlocked();
                                $_SESSION['new_achievements'] = $newlyUnlocked;
                                
                                error_log("Newly unlocked on login: " . print_r($newlyUnlocked, true));
                            }
                            
                            $redirectPage = $role . '/' . $role . '-dashboard.php';
                            $alertScript = "showAlert('success', 'Login Successful', 'Welcome back!', function() { window.location.href = '$redirectPage'; });";  
                        } else {
                            $alertScript = "showAlert('error', 'Login Failed', 'Invalid password.');";  
                        }
                    } else {
                        $alertScript = "showAlert('error', 'Login Failed', 'No active account found with this email.');";  
                    }
                    $stmt->close();
                } else {
                    $alertScript = "showAlert('error', 'System Error', 'Database connection error. Please try again.');";  
                }
            }
        }
    ?>

    <script>
        function showAlert(icon, title, text, callback) {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                showConfirmButton: true,
                timer: icon === 'success' ? 2000 : undefined
            }).then(() => {
                if (callback) callback();
            });
        }
        
        function showGoogleSignUpInfo() {
            Swal.fire({
                title: 'Google Sign-up',
                html: `
                    <div class="text-left space-y-3">
                        <p><strong>🎓 New to Al-Ghaya?</strong></p>
                        <p>When you sign in with Google for the first time, we'll automatically create a student account for you with:</p>
                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-600">
                            <li>Your name and email from Google</li>
                            <li>Student role to access learning materials</li>
                            <li>Level 1 beginner status</li>
                            <li>0 points to start your journey</li>
                        </ul>
                        <p class="text-sm text-gray-500 mt-4">You can update your profile and preferences after signing in.</p>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Got it!',
                confirmButtonColor: '#2563eb'
            });
        }
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }
        
        // Auto-dismiss error messages after 10 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.fixed.top-4.right-4');
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    errorMessage.style.transform = 'translateX(100%)';
                    setTimeout(() => errorMessage.remove(), 300);
                }, 10000);
            }
        });
    </script>
    
    <?php if (!empty($alertScript)) : ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?= $alertScript ?>
        });
    </script>
    <?php endif; ?>
</body>
</html>