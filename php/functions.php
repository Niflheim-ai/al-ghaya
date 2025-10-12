<?php
    include('dbConnection.php');

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require '../../vendor/phpmailer/PHPMailer/src/Exception.php';
    require '../../vendor/phpmailer/PHPMailer/src/PHPMailer.php';
    require '../../vendor/phpmailer/PHPMailer/src/SMTP.php';
    require '../../vendor/autoload.php';

    // Create Account
    function createAccount($conn) {
        if (isset($_POST['create-account'])) {
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
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "register.php";
                            }
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
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "register.php";
                            }
                        });
                    </script>';
                    return;
                }
    
                // Check if email already exists
                $checkEmail = mysqli_query($conn, "SELECT email FROM user WHERE email = '$inputEmail'");
                $numberOfUser = mysqli_num_rows($checkEmail);
    
                if ($numberOfUser < 1) {
                    // Hash password
                    $hashedPassword = password_hash($inputPassword, PASSWORD_BCRYPT, array('cost' => 12));
    
                    // Default values
                    $role = 'student';
                    $level = 1;
                    $points = 0;
                    $proficiency = 'beginner';
    
                    $saveRecord = $conn->prepare("INSERT INTO user (email, password, fname, lname, role, level, points, proficiency, dateCreated)
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
                    $saveRecord->bind_param("sssssiis", $inputEmail, $hashedPassword, $inputFirstName, $inputLastName, $role, $level, $points, $proficiency);
    
                    if ($saveRecord->errno) {
                        echo '<script>
                            Swal.fire({
                                title: "Error",
                                text: "Account creation failed",
                                icon: "error"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "register.php";
                                }
                            });
                        </script>';
                    } else {
                        echo '<script>
                            Swal.fire({
                                title: "Success",
                                text: "Account successfully created",
                                icon: "success"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = "register.php";
                                }
                            });
                        </script>';
    
                        $saveRecord->execute();
                        $saveRecord->close();
                        $conn->close();
                    }
                } else {
                    echo '<script>
                        Swal.fire({
                            title: "Error",
                            text: "Email is already in use",
                            icon: "error"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = "register.php";
                            }
                        });
                    </script>';
                }
            } else {
                echo '<script>
                    Swal.fire({
                        title: "Error",
                        text: "All fields are required",
                        icon: "error"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "register.php";
                        }
                    });
                </script>';
            }
        }
    }    

    // Create Program
    function handleCreateProgram($conn, $postData, $teacherID) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method.'];
        }
    
        // Sanitize and validate inputs
        $title = trim($postData['title'] ?? '');
        $category = trim($postData['category'] ?? '');
        $description = trim($postData['description'] ?? '');
        $price = isset($postData['price']) ? floatval($postData['price']) : 0;
    
        if (empty($title) || empty($category) || empty($description) || !$teacherID) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }
    
        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO programs (title, category, description, price, teacherID, dateCreated) VALUES (?, ?, ?, ?, ?, NOW())");
    
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
        }
    
        $stmt->bind_param("sssdi", $title, $category, $description, $price, $teacherID);
    
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Program created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Execute failed: ' . $stmt->error];
        }
    }

    // Fetching program data
    function fetchProgramData($conn) {
        if (isset($_GET['title']) && isset($_GET['category'])) {
            $title = trim($_GET['title']);
            $category = trim($_GET['category']);
    
            $programQuery = "SELECT `image`, `title`, `description`, `category` FROM `programs` WHERE `title` = '$title' AND `category` = '$category'";
            $programResult = mysqli_query($conn, $programQuery);
    
            if ($programResult && $programResult->num_rows > 0) {
                while($row = mysqli_fetch_assoc($programResult)) {
                    
                    $program_title = $row['title'];
                    $program_level = $row['category'];
                    $program_description = $row['description'];
                    $program_image = $row['image'];

                    $levelClass = '';

                    switch (strtolower($program_level)) {
                        case 'beginner':
                            $levelClass = 'text-white bg-black px-3 rounded-lg font-semibold';
                            break;
                        case 'intermediate':
                            $levelClass = 'text-white bg-[#10375B] px-3 rounded-lg font-semibold';
                            break;
                        case 'advanced':
                            $levelClass = 'text-white bg-[#A58618] px-3 rounded-lg font-semibold';
                            break;
                        default:
                            $levelClass = 'text-gray-600'; // fallback
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
                // No data found, redirect early
                header("Location: 404.php");
                exit();
            }
        }
    }

    function createTeacherAccount($teacherEmail, $fname = null, $lname = null, $adminID = null) {
        $length = 12; // length of password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $conn = new mysqli("localhost", "root", "", "al_ghaya_lms");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $username = $teacherEmail; // email as username

        $stmt = $conn->prepare("INSERT INTO teacher (email, username, password, dateCreated) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $teacherEmail, $username, $hashedPassword);
        $stmt->execute();
        $stmt->close();
        $conn->close();


        $mail = new PHPMailer(true);
        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'fmanaois4@gmail.com';
            $mail->Password = 'xtmr pend jhgn zzjz';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Recipient
            $mail->setFrom('al-ghaya-admin@gmail.com', 'Al-Ghaya - Admin');
            $mail->addAddress($teacherEmail);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Your Teacher Account Details';
            $mail->Body = "
                <p>Hello,</p>
                <p>Your teacher account has been created.</p>
                <p><strong>Username:</strong> {$teacherEmail}</p>
                <p><strong>Password:</strong> {$password}</p>
                <p><strong>Please change your password after logging in.</p>
                <p>This is an automated email, do not reply.</p>
            ";

            $mail->send();
            return true; // success
        } catch (Exception $e) {
            return "Mailer Error: {$mail->ErrorInfo}";
        }
    }

    // Get all programs for a teacher
    function getTeacherPrograms($conn, $teacher_id, $sortBy = 'dateCreated') {
        // Validate $sortBy to prevent SQL injection
        $allowedSorts = ['dateCreated', 'dateUpdated', 'title', 'price'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'dateCreated'; // Default
        }

        $stmt = $conn->prepare("SELECT * FROM programs WHERE teacherID = ? ORDER BY $sortBy DESC");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $programs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $programs;
    }

    // Create Program with Initial Chapter
    function createProgramWithChapter($conn, $teacher_id, $title, $description, $category, $video_link, $price, $image, $status = 'draft') {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Create program
            $stmt = $conn->prepare("INSERT INTO programs (teacherID, title, description, category, video_link, price, image, dateCreated, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->bind_param("issssdss", $teacher_id, $title, $description, $category, $video_link, $price, $image, $status);
            $stmt->execute();
            $program_id = $stmt->insert_id;
            $stmt->close();

            // Create initial chapter
            $stmt = $conn->prepare("INSERT INTO program_chapters (program_id, title, content, question, chapter_order)
                                VALUES (?, 'Introduction', '', '', 1)");
            $stmt->bind_param("i", $program_id);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();
            return $program_id;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            return false;
        }
    }

    // Create new Program
    function createProgram($conn, $teacher_id, $title, $description, $category, $video_link, $price, $image) {
        $stmt = $conn->prepare("INSERT INTO programs (teacherID, title, description, category, video_link, price, image, dateCreated)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssds", $teacher_id, $title, $description, $category, $video_link, $price, $image);

        if ($stmt->execute()) {
            $program_id = $stmt->insert_id;
            $stmt->close();
            return $program_id;
        } else {
            $error = $stmt->error;
            $stmt->close();
            return false;
        }
    }

    // Update a Program
    function updateProgram($conn, $program_id, $teacher_id, $title, $description, $category, $video_link, $price, $status = null) {
        if ($status) {
            $stmt = $conn->prepare("UPDATE programs SET title = ?, description = ?, category = ?, video_link = ?, price = ?, status = ?
                                WHERE programID = ? AND teacherID = ?");
            $stmt->bind_param("ssssdssi", $title, $description, $category, $video_link, $price, $status, $program_id, $teacher_id);
        } else {
            $stmt = $conn->prepare("UPDATE programs SET title = ?, description = ?, category = ?, video_link = ?, price = ?
                                WHERE programID = ? AND teacherID = ?");
            $stmt->bind_param("ssssdii", $title, $description, $category, $video_link, $price, $program_id, $teacher_id);
        }

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        } else {
            $error = $stmt->error;
            $stmt->close();
            return false;
        }
    }

    // Update Program Status
    function updateProgramStatus($conn, $program_id, $teacher_id, $status) {
        $stmt = $conn->prepare("UPDATE programs SET status = ? WHERE programID = ? AND teacherID = ?");
        $stmt->bind_param("sii", $status, $program_id, $teacher_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    // Get a Program by ID
    function getProgram($conn, $program_id, $teacher_id) {
        $stmt = $conn->prepare("SELECT * FROM programs WHERE programID = ? AND teacherID = ?");
        $stmt->bind_param("ii", $program_id, $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $program = $result->fetch_assoc();
        $stmt->close();
        return $program;
    }

    // Verify Program Ownership
    function verifyProgramOwnership($conn, $program_id, $teacher_id) {
        $stmt = $conn->prepare("SELECT 1 FROM programs WHERE programID = ? AND teacherID = ?");
        $stmt->bind_param("ii", $program_id, $teacher_id);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    // Add a Chapter to a Program
    function addChapter($conn, $program_id, $title, $content, $question) {
        // Get next chapter order
        $stmt = $conn->prepare("SELECT MAX(chapter_order) FROM program_chapters WHERE program_id = ?");
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $max_order = $stmt->get_result()->fetch_array()[0];
        $chapter_order = $max_order ? $max_order + 1 : 1;
        $stmt->close();

        // Insert chapter
        $stmt = $conn->prepare("INSERT INTO program_chapters (program_id, title, content, question, chapter_order)
                            VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $program_id, $title, $content, $question, $chapter_order);

        if ($stmt->execute()) {
            $chapter_id = $stmt->insert_id;
            $stmt->close();
            return $chapter_id;
        } else {
            $error = $stmt->error;
            $stmt->close();
            return false;
        }
    }

    // Update a Chapter
    function updateChapter($conn, $chapter_id, $title, $content, $question) {
        $stmt = $conn->prepare("UPDATE program_chapters SET title = ?, content = ?, question = ?
                            WHERE chapter_id = ?");
        $stmt->bind_param("sssi", $title, $content, $question, $chapter_id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        } else {
            $error = $stmt->error;
            $stmt->close();
            return false;
        }
    }

    // Delete a Chapter
    function deleteChapter($conn, $chapter_id) {
        $stmt = $conn->prepare("DELETE FROM program_chapters WHERE chapter_id = ?");
        $stmt->bind_param("i", $chapter_id);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected > 0;
        } else {
            $error = $stmt->error;
            $stmt->close();
            return false;
        }
    }

    // Get Chapter for a Program
    function getChapters($conn, $program_id) {
        $stmt = $conn->prepare("SELECT * FROM program_chapters WHERE program_id = ? ORDER BY chapter_order");
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $chapters = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $chapters;
    }

    // Upload a file
    function uploadFile($file, $upload_dir, $allowed_types = ['jpg', 'jpeg', 'png']) {
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            return false;
        }

        $filename = uniqid() . '.' . $file_ext;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }

        return false;
    }

    function getStudentPrograms($conn, $student_id) {
        $sql = "SELECT p.programID, p.title, p.description, p.price, p.category, p.image, p.dateCreated
                FROM programs p
                JOIN student_program sp ON p.programID = sp.programID
                WHERE sp.studentID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $programs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $programs;
    }

    function getPublishedPrograms($conn) {
        $sql = "SELECT programID, title, description, price, category, image, dateCreated
                FROM programs
                WHERE status = 'published'";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch enrolled programs for a student with filters and search
    function fetchEnrolledPrograms($conn, $student_id, $difficulty, $status, $search) {
        $sql = "SELECT p.programID, p.title, p.description, p.price, p.category, p.image, p.dateCreated
                FROM programs p
                JOIN student_program sp ON p.programID = sp.programID
                WHERE sp.studentID = ? AND p.status = 'published'";

        $params = [$student_id];
        $types = "i";

        if ($difficulty !== 'all') {
            $sql .= " AND p.category = ?";
            $params[] = $difficulty;
            $types .= "s";
        }

        if ($status !== 'all') {
            $statusCondition = ($status === 'in-progress') ? "AND sp.progress < 100" : "AND sp.progress = 100";
            $sql .= $statusCondition;
        }

        if (!empty($search)) {
            $sql .= " AND p.title LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch all published programs with filters and search
    function fetchPublishedPrograms($conn, $student_id, $difficulty, $status, $search) {
        $sql = "SELECT p.programID, p.title, p.description, p.price, p.category, p.image, p.dateCreated
                FROM programs p
                WHERE p.status = 'published'
                AND p.programID NOT IN (
                    SELECT programID FROM student_program WHERE studentID = ?
                )";

        $params = [$student_id];
        $types = "i";

        if ($difficulty !== 'all') {
            $sql .= " AND p.category = ?";
            $params[] = $difficulty;
            $types .= "s";
        }

        if (!empty($search)) {
            $sql .= " AND p.title LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }


    // Fetch a single program
    function fetchProgram($conn, $program_id) {
        $sql = "SELECT * FROM programs WHERE programID = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Fetch chapters for a program
    function fetchChapters($conn, $program_id) {
        // Check if the chapters table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'chapters'");
        if ($tableCheck->num_rows == 0) {
            return []; // Return empty array if the table doesn't exist
        }

        $sql = "SELECT * FROM program_chapters WHERE program_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return []; // Return empty array if there's an error preparing the statement
        }
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
?>