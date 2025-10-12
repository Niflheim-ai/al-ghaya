<?php
    session_start();

    include('../php/dbConnection.php');
    require '../php/login-api.php';
    include('../php/functions.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- CSS -->
    <link rel="stylesheet" href="../dist/css/login.css">
    <!-- Tailwind -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="icon" type="image/x-icon" href="../images/Al-ghaya logoForPrint.svg">
    <title>Al-Ghaya - Register</title>
</head>
<body class="bg-[#f3f3fc]">
    <!-- Content -->
    <div class="content flex justify-center items-center min-h-screen px-6">
        <div class="flex max-w-6xl w-full bg-neutral-100 rounded-xl p-6 flex-wrap items-center justify-center shadow-[0_3px_10px_rgb(0,0,0,0.2)]">
            <!-- Back Button -->
            <button class="absolute top-4 left-4 hover:cursor-pointer hover:bg-white transition-all text-[#A58618] font-medium rounded-full p-2 shadow-md border border-md">
                <buttton onclick="window.location.href='index.php'"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg></button>
            </>

            <!-- Left column container -->
            <div class="hidden lg:flex lg:flex-col gap-5 lg:py-40 md:w-6/12 lg:w-7/12 px-25">
                <img class="max-w-20 mb-10 -mt-25" src="../images/Al-ghaya logoForPrint.svg" alt="">
                <p class="text-6xl font-black text-[#05051A]">Register an account</p>
                <p class="text-xl text-[#05051A]">Enter your details or use your Google account</p>
            </div>

            <!-- Right column container -->
            <div class="w-full md:w-9/12 lg:w-5/12 p-4">
                <form action="#" method="POST" class="space-y-4">
                    <!-- Side by Side First Name and Last Name Input -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="first-name" class="block text-gray-700 font-medium">First Name</label>
                            <input type="text" id="first-name" name="first-name" placeholder="John" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label for="last-name" class="block text-gray-700 font-medium">Last Name</label>
                            <input type="text" id="last-name" name="last-name" placeholder="Doe" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

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
                            <img src="https://img.icons8.com/?size=100&id=2862&format=png&color=000000" class="absolute left-3 top-3 w-5 h-5 text-gray-500">
                            <button type="button" onclick="togglePassword()" class="absolute right-3 top-3 cursor-pointer">
                                <img id="eye-icon" src="https://img.icons8.com/?size=100&id=0ciqibcg6iLl&format=png&color=000000" class="w-5 h-5 text-gray-500">
                            </button>
                        </div>
                    </div>

                    <!-- Re-type Password Input -->
                    <div>
                        <label for="confirm-password" class="block text-gray-700 font-medium">Confirm Password</label>
                        <div class="relative">
                            <input type="password" id="confirm-password" name="confirm-password" placeholder="********" required class="w-full p-3 pl-10 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <img src="https://img.icons8.com/?size=100&id=2862&format=png&color=000000" class="absolute left-3 top-3 w-5 h-5 text-gray-500">
                            <button type="button" onclick="togglePassword1()" class="absolute right-3 top-3 cursor-pointer">
                                <img id="eye-icon1" src="https://img.icons8.com/?size=100&id=0ciqibcg6iLl&format=png&color=000000" class="w-5 h-5 text-gray-500">
                            </button>
                        </div>
                    </div>

                    <!-- Register Button -->
                    <button name="create-account" type="submit" class="w-full bg-[#10375b] text-white py-3 rounded-lg hover:bg-blue-700 transition duration-300 hover:cursor-pointer">
                        Register
                    </button>

                    <!-- Signup Link -->
                    <p class="text-center text-gray-600 text-sm">
                        Already have an account?
                        <a href="login.php" class="text-blue-500 font-medium hover:underline">Sign In</a>
                    </p>
                </form>

                <div class="my-4 flex items-center">
                    <hr class="flex-grow border-gray-400">
                    <span class="mx-3 text-gray-900">or</span>
                    <hr class="flex-grow border-gray-400">
                </div>

                <!-- Social Login Buttons -->
                <div class="flex flex-col space-y-3">
                    <a href="<?= $client->createAuthUrl(); ?>">
                        <button class="flex items-center justify-center w-full bg-white border border-gray-300 text-gray-700 py-2 rounded-lg shadow-md hover:bg-gray-100 transition hover:cursor-pointer">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5 mr-2" alt="Google Icon">
                        Register with Google
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php  
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!empty($_POST['first-name']) && !empty($_POST['last-name']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['confirm-password'])) {

                $inputFirstName = $_POST['first-name'];
                $inputLastName = $_POST['last-name'];
                $inputEmail = $_POST['email'];
                $inputPassword = $_POST['password'];
                $inputConfirmPassword = $_POST['confirm-password'];

                // Validate email format
                if (!filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
                    echo '<script>
                            Swal.fire({
                                title: "Error",
                                text: "Invalid email format",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "register.php";
                            });
                        </script>';
                    return;
                }

                // Check if passwords match
                if ($inputPassword !== $inputConfirmPassword) {
                    echo '<script>
                            Swal.fire({
                                title: "Error",
                                text: "Passwords do not match",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "register.php";
                            });
                        </script>';
                    return;
                }

                // Check if email already exists
                $checkEmail = $conn->prepare("SELECT email FROM student WHERE email = ?");
                $checkEmail->bind_param("s", $inputEmail);
                $checkEmail->execute();
                $checkEmail->store_result();

                if ($checkEmail->num_rows > 0) {
                    echo '<script>
                            Swal.fire({
                                title: "Error",
                                text: "Email is already in use",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "register.php";
                            });
                        </script>';
                    $checkEmail->close();
                    return;
                }
                $checkEmail->close();

                // Hash password
                $hashedPassword = password_hash($inputPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                // Default values
                $defaultGender = 'unspecified';
                $level = 1;
                $points = 0;
                $proficiency = 'beginner';

                // Insert new student (gender is NULL for now)
                $saveRecord = $conn->prepare("INSERT INTO student (email, password, fname, lname, gender, level, points, proficiency, dateCreated)
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                $saveRecord->bind_param("sssssiis", $inputEmail, $hashedPassword, $inputFirstName, $inputLastName, $defaultGender, $level, $points, $proficiency);

                if ($saveRecord->execute()) {
                    $_SESSION['student_id'] = $conn->insert_id;

                    echo '<script>
                            Swal.fire({
                                title: "Success",
                                text: "Account created.",
                                icon: "success"
                            }).then(() => {
                                window.location.href = "guide.php";
                            });
                        </script>';
                } else {
                    echo '<script>
                            Swal.fire({
                                title: "Error",
                                text: "Account creation failed",
                                icon: "error"
                            }).then(() => {
                                window.location.href = "register.php";
                            });
                        </script>';
                }

                $saveRecord->close();
                $conn->close();
            } else {
                echo '<script>
                        Swal.fire({
                            title: "Error",
                            text: "All fields are required",
                            icon: "error"
                        }).then(() => {
                            window.location.href = "register.php";
                        });
                    </script>';
            }
        }
    ?>


    <script src="../components/navbar.js"></script>
    <script src="../dist/javascript/register.js"></script>
</body>
</html>