<?php
    include('dbConnection.php');

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/mail-config.php';

    // Create Account - SINGLE DECLARATION
    function createAccount($conn) {
        if (isset($_POST['create-account'])) {
            if (!empty($_POST['first-name']) && !empty($_POST['last-name']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['confirm-password'])) {
                $inputFirstName = trim($_POST['first-name']);
                $inputLastName = trim($_POST['last-name']);
                $inputEmail = trim($_POST['email']);
                $inputPassword = $_POST['password'];
                $inputConfirmPassword = $_POST['confirm-password'];
    
                if (!filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
                    echo '<script>Swal.fire({title:"Error",text:"Invalid email format",icon:"error"}).then(()=>window.location.href="register.php")</script>';
                    return;
                }
                if ($inputPassword !== $inputConfirmPassword) {
                    echo '<script>Swal.fire({title:"Error",text:"Passwords do not match",icon:"error"}).then(()=>window.location.href="register.php")</script>';
                    return;
                }

                // Prepared statement for email existence
                $checkStmt = $conn->prepare("SELECT 1 FROM user WHERE email = ? LIMIT 1");
                $checkStmt->bind_param("s", $inputEmail);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows < 1) {
                    $hashedPassword = password_hash($inputPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                    $role = 'student'; $level = 1; $points = 0; $proficiency = 'beginner';
                    $saveRecord = $conn->prepare("INSERT INTO user (email, password, fname, lname, role, level, points, proficiency, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $saveRecord->bind_param("sssssiis", $inputEmail, $hashedPassword, $inputFirstName, $inputLastName, $role, $level, $points, $proficiency);
                    if (!$saveRecord->execute()) {
                        echo '<script>Swal.fire({title:"Error",text:"Account creation failed",icon:"error"}).then(()=>window.location.href="register.php")</script>';
                    } else {
                        echo '<script>Swal.fire({title:"Success",text:"Account successfully created",icon:"success"}).then(()=>window.location.href="register.php")</script>';
                        $saveRecord->close();
                        $conn->close();
                    }
                } else {
                    echo '<script>Swal.fire({title:"Error",text:"Email is already in use",icon:"error"}).then(()=>window.location.href="register.php")</script>';
                }
                $checkStmt->close();
            } else {
                echo '<script>Swal.fire({title:"Error",text:"All fields are required",icon:"error"}).then(()=>window.location.href="register.php")</script>';
            }
        }
    }

    // Fetching program data
    function fetchProgramData($conn) {
        if (isset($_GET['title']) && isset($_GET['category'])) {
            $title = trim($_GET['title']);
            $category = trim($_GET['category']);

            $programQuery = $conn->prepare("SELECT image, title, description, category FROM programs WHERE title = ? AND category = ?");
            $programQuery->bind_param("ss", $title, $category);
            $programQuery->execute();
            $programResult = $programQuery->get_result();

            if ($programResult && $programResult->num_rows > 0) {
                while($row = $programResult->fetch_assoc()) {
                    $program_title = $row['title'];
                    $program_level = $row['category'];
                    $program_description = $row['description'];
                    $program_image = $row['image'];

                    $levelClass = '';
                    switch (strtolower($program_level)) {
                        case 'beginner': $levelClass = 'text-white bg-black px-3 rounded-lg font-semibold'; break;
                        case 'intermediate': $levelClass = 'text-white bg-[#10375B] px-3 rounded-lg font-semibold'; break;
                        case 'advanced': $levelClass = 'text-white bg-[#A58618] px-3 rounded-lg font-semibold'; break;
                        default: $levelClass = 'text-gray-600';
                    }

                    echo "
                    <div class='container mx-auto'>
                        <div class='max-w-4xl mx-auto bg-white shadow-md rounded-b-lg overflow-hidden'>
                            <img src='../images/$program_image.png' alt='$program_title' class='w-full h-80 object-cover'>
                        </div>
                        <div class='mx-auto max-w-[70%] lg:max-w-[65%] md:max-w-[60%] text-justify mt-15 lg:text-left'>
                            <h1 class='text-4xl font-bold mb-2 text-[#10375B] text-center lg:text-left'>$program_title <span class='text-lg mb-4 ml-2 $levelClass'>$program_level</span></h1> 
                            <p class='text-gray-700 leading-relaxed'>$program_description</p>
                        </div>
                    </div>";
                }
            } else {
                header("Location: 404.php");
                exit();
            }
            $programQuery->close();
        }
    }

    function createTeacherAccount($teacherEmail, $fname = null, $lname = null, $adminID = null) {
        $length = 12; $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) { $password .= $chars[random_int(0, strlen($chars) - 1)]; }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $conn = getDbConnection();
        if (!$conn) { return false; }

        $username = $teacherEmail;
        $stmt = $conn->prepare("INSERT INTO teacher (email, username, password, dateCreated) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $teacherEmail, $username, $hashedPassword);
        $stmt->execute();
        $stmt->close();

        try {
            // Prefer centralized mailer & template
            $result = sendTeacherWelcomeEmail($teacherEmail, $password, $fname ?? '', $lname ?? '');
            return $result['success'] ? true : $result['message'];
        } catch (Exception $e) {
            error_log('Teacher welcome email failed: ' . $e->getMessage());
            return false;
        }
    }

    // Other utility functions can go here...
?>