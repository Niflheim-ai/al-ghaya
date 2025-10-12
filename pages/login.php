<?php
session_start();
include('../php/dbConnection.php');
require '../php/login-api.php';

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

// Handle error messages from OAuth
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMessage = '';
if ($error === 'account_not_found') {
    $errorMessage = 'Your Google account is not registered. Please use regular login or contact admin.';
} elseif ($error === 'auth_failed') {
    $errorMessage = 'Google authentication failed. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google API -->
    <meta name="google-signin-client_id" content="457808994183-37j34305f5792o6shmp802frjlm3b0dd.apps.googleusercontent.com">
    <!-- CSS -->
    <link rel="stylesheet" href="../dist/css/login.css">
    <!-- Tailwind -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="icon" type="image/x-icon" href="../images/al-ghaya_logoForPrint.svg">
    <title>Al-Ghaya - Login</title>
</head>
<body class="bg-[#f3f3fc]">
    <!-- Content -->
    <div class="content flex justify-center items-center min-h-screen px-6">
        <div class="flex max-w-6xl w-full bg-neutral-100 rounded-xl p-6 flex-wrap items-center justify-center shadow-[0_3px_10px_rgb(0,0,0,0.2)]">
            <!-- Back Button -->
            <button class="absolute top-4 left-4 hover:cursor-pointer hover:bg-white transition-all text-[#A58618] font-medium rounded-full p-2 shadow-md border border-md">
                <a href="index.php"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg></a>
            </button>

            <!-- Error Message -->
            <?php if (!empty($errorMessage)): ?>
                <div class="w-full p-4 mb-4 text-red-700 bg-red-100 rounded-lg">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Left column container -->
            <div class="hidden lg:flex lg:flex-col gap-5 lg:py-40 md:w-6/12 lg:w-7/12 px-25">
                <img class="max-w-20 mb-10 -mt-25" src="../images/Al-ghaya_logoForPrint.svg" alt="">
                <p class="text-6xl font-black text-[#05051A]">Login</p>
                <p class="text-xl text-[#05051A]">You may use your Gmail account or the Al-Ghaya account</p>
            </div>

            <!-- Right column container -->
            <div class="w-full md:w-9/12 lg:w-5/12 p-4">
                <form action="login.php" method="POST" class="space-y-4">
                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-gray-700 font-medium">Email Address</label>
                        <div class="relative">
                            <input type="email" id="email" name="email" placeholder="johndoe@gmail.com" required class="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <img src="https://img.icons8.com/ios-filled/50/new-post.png" class="absolute left-3 top-4 w-5 h-5 text-gray-500">
                        </div>
                    </div>
                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-gray-700 font-medium">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" placeholder="********" required class="w-full p-3 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <img src="https://img.icons8.com/?size=100&id=2862&format=png&color=000000" class="absolute left-3 top-3 w-6 h-6 text-gray-500">
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-3 cursor-pointer">
                                <img id="eye-icon" src="https://img.icons8.com/?size=100&id=0ciqibcg6iLl&format=png&color=000000" class="w-7 h-7 text-gray-500">
                            </button>
                        </div>
                    </div>
                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="form-checkbox text-blue-500 hover:cursor-pointer">
                            <span class="ml-2 text-gray-600">Remember Me</span>
                        </label>
                        <a href="javascript:void(0)" onclick="toggleForgotModal(true)" class="text-blue-500 hover:underline text-sm">Forgot Password?</a>
                    </div>
                    <!-- Login Button -->
                    <button type="submit" class="w-full bg-[#10375b] text-white py-3 rounded-lg hover:bg-blue-700 transition duration-300 hover:cursor-pointer">
                        Login
                    </button>
                    <!-- Signup Link -->
                    <p class="text-center text-gray-600 text-sm">
                        Don't have an account?
                        <a href="register.php" class="text-blue-500 font-medium hover:underline">Sign Up</a>
                    </p>
                </form>
                <div class="my-4 flex items-center">
                    <hr class="flex-grow border-gray-400">
                    <span class="mx-3 text-gray-900">or</span>
                    <hr class="flex-grow border-gray-400">
                </div>
                <!-- Social Login Buttons -->
                <div class="flex flex-col space-y-3" data-onsuccess="onSignIn">
                    <a href="<?= $client->createAuthUrl(); ?>">
                        <button class="flex items-center justify-center w-full bg-white border border-gray-300 text-gray-700 py-2 rounded-lg shadow-md hover:bg-gray-100 transition hover:cursor-pointer">
                            <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5 mr-2" alt="Google Icon">
                            Login with Google
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg p-6 w-96">
            <h2 class="text-xl font-semibold mb-4">Reset Password</h2>
            <p class="text-gray-600 mb-4">Enter your email and we'll send you reset instructions.</p>
            <form method="POST" action="forgot-password.php">
                <input type="email" name="resetEmail" placeholder="Enter your email" class="w-full border rounded-lg p-2 mb-4 focus:ring-2 focus:ring-blue-500" required>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="toggleForgotModal(false)"
                        class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Send</button>
                </div>
            </form>
        </div>
    </div>

    <?php
        $alertScript = '';
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            if (empty($email) || empty($password)) {
                $alertScript = "
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: 'All fields are required.',
                        showConfirmButton: true
                    });
                ";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $alertScript = "
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: 'Invalid email format.',
                        showConfirmButton: true
                    });
                ";
            } else {
                $tables = ['student', 'teacher', 'admin'];
                $userFound = false;
                foreach ($tables as $table) {
                    $stmt = $conn->prepare("SELECT {$table}ID, email, password FROM $table WHERE email = ?");
                    if (!$stmt) continue;
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $stmt->bind_result($userID, $dbEmail, $hashedPassword);
                        $stmt->fetch();
                        $userFound = true;
                        if (password_verify($password, $hashedPassword)) {
                            $_SESSION['userID'] = $userID;
                            $_SESSION['role'] = $table;
                            $alertScript = "
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Login Successful',
                                    text: 'Welcome back!',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = '{$table}/{$table}-dashboard.php';
                                });
                            ";
                        } else {
                            $alertScript = "
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Login Failed',
                                    text: 'Invalid password.',
                                    showConfirmButton: true
                                });
                            ";
                        }
                        $stmt->close();
                        break; // stop loop, user found
                    }
                    $stmt->close();
                }
                if (!$userFound) {
                    $alertScript = "
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: 'No user found with this email.',
                            showConfirmButton: true
                        });
                    ";
                }
            }
        }
    ?>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (!empty($alertScript)) : ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?= $alertScript ?>
        });
    </script>
    <?php endif; ?>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.src = 'https://img.icons8.com/?size=100&id=30M9wv1iFkcH&format=png&color=000000';
            } else {
                passwordInput.type = 'password';
                eyeIcon.src = 'https://img.icons8.com/?size=100&id=0ciqibcg6iLl&format=png&color=000000';
            }
        }
    </script>
    <script>
        function toggleForgotModal(show) {
            const modal = document.getElementById('forgotModal');
            modal.classList.toggle('hidden', !show);
        }
    </script>
    <!-- Google Sign In -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
</body>
</html>