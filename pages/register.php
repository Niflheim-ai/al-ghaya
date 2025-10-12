<?php
session_start();
include('../php/dbConnection.php');
require '../php/login-api.php';
include('../php/gamification.php');

// If already logged in, redirect
if (isset($_SESSION['userID'])) {
    $role = $_SESSION['role'] ?? 'student';
    header("Location: {$role}/{$role}-dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="../images/al-ghaya_logoForPrint.svg">
    <title>Al-Ghaya - Register</title>
    <style>
        .content { min-height: 100vh; }
        .form-container { max-width: 1200px; }
        .bg-primary { background-color: #10375b; }
        .text-primary { color: #10375b; }
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

            <!-- Left column - Welcome Section -->
            <div class="hidden lg:flex lg:flex-col justify-center items-start w-1/2 bg-gradient-to-br from-green-600 to-blue-700 text-white p-12">
                <div class="mb-8">
                    <img class="h-16 mb-6" src="../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                </div>
                <h1 class="text-5xl font-bold mb-4">Join Al-Ghaya!</h1>
                <p class="text-xl text-green-100 leading-relaxed">Start your Arabic and Islamic studies journey with our gamified learning platform.</p>
                <div class="mt-8 text-green-200">
                    <p>✓ Interactive lessons</p>
                    <p>✓ Achievement system</p>
                    <p>✓ Progress tracking</p>
                    <p>✓ Personalized learning</p>
                </div>
            </div>

            <!-- Right column - Register Form -->
            <div class="w-full lg:w-1/2 p-8 lg:p-12">
                <div class="max-w-md mx-auto">
                    <div class="text-center mb-8 lg:hidden">
                        <img class="h-12 mx-auto mb-4" src="../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                        <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
                    </div>
                    
                    <div class="hidden lg:block mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Get Started</h2>
                        <p class="text-gray-600">Create your learning account</p>
                    </div>

                    <form action="register.php" method="POST" class="space-y-6">
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
                                <button type="button" onclick="togglePassword('password', 'eye-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg id="eye-icon" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                        </div>

                        <!-- Confirm Password Input -->
                        <div>
                            <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="confirm-password" name="confirm-password" placeholder="••••••••" required 
                                       class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <button type="button" onclick="togglePassword('confirm-password', 'eye-icon2')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg id="eye-icon2" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Terms Checkbox -->
                        <div class="flex items-start">
                            <input id="terms" name="terms" type="checkbox" required 
                                   class="w-4 h-4 mt-1 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <label for="terms" class="ml-2 text-sm text-gray-600">
                                I agree to the <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">Terms of Service</a> 
                                and <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">Privacy Policy</a>
                            </label>
                        </div>

                        <!-- Register Button -->
                        <button name="create-account" type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Create Account
                        </button>

                        <!-- Sign In Link -->
                        <p class="text-center text-sm text-gray-600">
                            Already have an account?
                            <a href="login.php" class="font-medium text-blue-600 hover:text-blue-800">Sign in</a>
                        </p>
                    </form>

                    <!-- Divider -->
                    <div class="mt-8 mb-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or register with</span>
                            </div>
                        </div>
                    </div>

                    <!-- Google OAuth Button -->
                    <div class="space-y-3">
                        <a href="<?= $client->createAuthUrl(); ?>" class="w-full">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create-account'])) {
            // Sanitize and validate input
            $inputFirstName = trim($_POST['first-name'] ?? '');
            $inputLastName = trim($_POST['last-name'] ?? '');
            $inputEmail = trim($_POST['email'] ?? '');
            $inputPassword = $_POST['password'] ?? '';
            $inputConfirmPassword = $_POST['confirm-password'] ?? '';

            if (empty($inputFirstName) || empty($inputLastName) || empty($inputEmail) || empty($inputPassword) || empty($inputConfirmPassword)) {
                echo '<script>showAlert("error", "Registration Failed", "All fields are required.");</script>';
            } elseif (!filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
                echo '<script>showAlert("error", "Registration Failed", "Invalid email format.");</script>';
            } elseif (strlen($inputPassword) < 8) {
                echo '<script>showAlert("error", "Registration Failed", "Password must be at least 8 characters long.");</script>';
            } elseif ($inputPassword !== $inputConfirmPassword) {
                echo '<script>showAlert("error", "Registration Failed", "Passwords do not match.");</script>';
            } else {
                // Check if email already exists in unified user table
                $checkEmail = $conn->prepare("SELECT userID FROM user WHERE email = ?");
                $checkEmail->bind_param("s", $inputEmail);
                $checkEmail->execute();
                $checkEmail->store_result();

                if ($checkEmail->num_rows > 0) {
                    echo '<script>showAlert("error", "Registration Failed", "Email is already registered.");</script>';
                } else {
                    // Hash password
                    $hashedPassword = password_hash($inputPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                    // Default values for new student
                    $role = 'student';
                    $level = 1;
                    $points = 0;
                    $proficiency = 'beginner';
                    $isActive = 1;

                    // Insert new user
                    $saveRecord = $conn->prepare("INSERT INTO user (email, password, fname, lname, role, level, points, proficiency, isActive, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $saveRecord->bind_param("sssssiisi", $inputEmail, $hashedPassword, $inputFirstName, $inputLastName, $role, $level, $points, $proficiency, $isActive);

                    if ($saveRecord->execute()) {
                        $newUserID = $conn->insert_id;
                        
                        // Initialize gamification data
                        if (function_exists('initializeNewUser')) {
                            initializeNewUser($conn, $newUserID);
                        }
                        
                        // Award welcome achievement
                        if (function_exists('awardAchievement')) {
                            awardAchievement($conn, $newUserID, 'welcome_aboard');
                        }
                        
                        // Set session for auto-login
                        $_SESSION['userID'] = $newUserID;
                        $_SESSION['role'] = $role;
                        $_SESSION['email'] = $inputEmail;
                        
                        echo '<script>showAlert("success", "Account Created!", "Welcome to Al-Ghaya! Your account has been created successfully.", function() { window.location.href = "student/student-dashboard.php"; });</script>';
                    } else {
                        echo '<script>showAlert("error", "Registration Failed", "Unable to create account. Please try again.");</script>';
                    }
                    $saveRecord->close();
                }
                $checkEmail->close();
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
                timer: icon === 'success' ? 3000 : undefined
            }).then(() => {
                if (callback) callback();
            });
        }
        
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }
        
        // Client-side password validation
        document.getElementById('confirm-password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword && confirmPassword.length > 0) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            if (password.length > 0 && password.length < 8) {
                this.setCustomValidity('Password must be at least 8 characters long');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>