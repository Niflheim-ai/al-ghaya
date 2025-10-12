<?php
session_start();
include('../../php/dbConnection.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$current_page = "admin-faculty";
$page_title = "Faculty Management";

// Include PHPMailer
require_once '../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include database connection
include('../../php/dbConnection.php');

// Function to generate random password
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

// Function to send email using PHPMailer
function sendLoginCredentials($email, $password, $firstName = '', $lastName = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@al-ghaya.com'; // Replace with your Gmail
        $mail->Password   = 'xtmr pend jhgn zzjz'; // Replace with your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('alghayalms@gmail.com', 'Al-Ghaya LMS');
        $mail->addAddress($email, $firstName . ' ' . $lastName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Al-Ghaya LMS - Your Teacher Account';
        
        $displayName = !empty($firstName) ? $firstName . ' ' . $lastName : 'Teacher';
        
        $emailBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10375b 0%, #0d2a47 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .credentials { background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10375b; }
                .button { display: inline-block; background: #10375b; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎓 Welcome to Al-Ghaya LMS!</h1>
                    <p>Your Teacher Account Has Been Created</p>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($displayName) . ",</h2>
                    <p>Welcome to Al-Ghaya Learning Management System! An administrator has created a teacher account for you.</p>
                    
                    <div class='credentials'>
                        <h3>🔐 Your Login Credentials:</h3>
                        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                        <p><strong>Temporary Password:</strong> <code style='background: #e8e8e8; padding: 2px 8px; border-radius: 4px; font-size: 14px; font-family: monospace;'>" . htmlspecialchars($password) . "</code></p>
                    </div>
                    
                    <p><strong>⚠️ Important Security Notes:</strong></p>
                    <ul>
                        <li>Please change your password immediately after your first login</li>
                        <li>Do not share your login credentials with anyone</li>
                        <li>Keep your account information secure</li>
                    </ul>
                    
                    <p><strong>📚 Getting Started:</strong></p>
                    <ol>
                        <li>Visit the Al-Ghaya LMS login page</li>
                        <li>Use the credentials provided above</li>
                        <li>Complete your profile setup</li>
                        <li>Change your password in account settings</li>
                        <li>Start creating courses and content</li>
                    </ol>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='https://al-ghaya.vercel.app/pages/login.php' class='button' style='color: white; text-decoration: none;'>🚀 Login to Al-Ghaya LMS</a>
                    </div>
                    
                    <p>If you have any questions or need assistance, please contact our support team.</p>
                    
                    <div class='footer'>
                        <p>This email was sent from Al-Ghaya Learning Management System</p>
                        <p>© 2025 Al-Ghaya LMS. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $emailBody;

        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// Handle AJAX request for adding faculty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_faculty') {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    
    // Validation
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required.']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }
    
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT userID FROM user WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    
    if ($checkEmail->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
        $checkEmail->close();
        exit;
    }
    
    // Generate random password
    $tempPassword = generateRandomPassword(12);
    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Default values for teacher
    $role = 'teacher';
    $level = 1;
    $points = 0;
    $proficiency = 'teacher';
    $isActive = 1;
    
    // Insert new teacher
    $insertTeacher = $conn->prepare("INSERT INTO user (email, password, fname, lname, role, level, points, proficiency, isActive, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $insertTeacher->bind_param("sssssiisi", $email, $hashedPassword, $firstName, $lastName, $role, $level, $points, $proficiency, $isActive);
    
    if ($insertTeacher->execute()) {
        $newUserID = $conn->insert_id;
        
        // Send email with login credentials
        $emailResult = sendLoginCredentials($email, $tempPassword, $firstName, $lastName);
        
        if ($emailResult['success']) {
            echo json_encode([
                'success' => true, 
                'message' => 'Faculty account created successfully! Login credentials have been sent to ' . $email . '.',
                'credentials' => [
                    'email' => $email,
                    'password' => $tempPassword
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Faculty account created but email failed to send.',
                'credentials' => [
                    'email' => $email,
                    'password' => $tempPassword
                ],
                'email_error' => $emailResult['error'] ?? 'Unknown email error'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create faculty account.']);
    }
    
    $insertTeacher->close();
    $checkEmail->close();
    exit;
}

// Get faculty statistics (you can expand this based on your needs)
$totalTeachers = 0;
$publishRequests = 0;
$updateRequests = 0;
$archivedTeachers = 0;

// Get total teachers
$teacherCount = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'teacher' AND isActive = 1");
if ($teacherCount) {
    $totalTeachers = $teacherCount->fetch_assoc()['count'];
}
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<div class="page-container">
    <div class="page-content">
        <!-- 1ST SECTION: Charts library template -->
        <section class="content-section">
            <h1 class="section-title">Faculty Management</h1>
            <div class="w-full flex gap-[10px]">
                <!-- Add Faculty Account -->
                <button type="button" onclick="openAddFacultyModal()" class="group flex flex-grow p-[25px] gap-[10px] rounded-[10px] text-company_white bg-secondary flex flex-col items-center justify-center hover:bg-company_black transition-all duration-200">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph ph-user-plus text-[40px] group-hover:hidden"></i>
                        <i class="ph-duotone ph-user-plus text-[40px] hidden group-hover:block"></i>
                    </div>
                    <p>Add Faculty Account</p>
                </button>

                <!-- Total Teachers -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] bg-company_white flex flex-col items-center justify-center">
                    <div class="text-tertiary flex items-center gap-[10px]">
                        <i class="ph-duotone ph-chalkboard-simple text-[40px]"></i>
                        <p class="sub-header"><?= sprintf('%02d', $totalTeachers) ?></p>
                    </div>
                    <p>Total # of Teachers</p>
                </div>

                <!-- Publish Requests -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_green flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-seal-check text-[40px]"></i>
                        <p class="sub-header"><?= sprintf('%02d', $publishRequests) ?></p>
                    </div>
                    <p>Publish Requests</p>
                </div>

                <!-- Update Requests -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_orange flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-traffic-cone text-[40px]"></i>
                        <p class="sub-header"><?= sprintf('%02d', $updateRequests) ?></p>
                    </div>
                    <p>Update Requests</p>
                </div>

                <!-- Archived Teachers -->
                <div class="size-fit p-[25px] gap-[10px] rounded-[10px] text-company_white bg-company_red flex flex-col items-center justify-center">
                    <div class="flex items-center gap-[10px]">
                        <i class="ph-duotone ph-archive text-[40px]"></i>
                        <p class="sub-header"><?= sprintf('%02d', $archivedTeachers) ?></p>
                    </div>
                    <p>Archived Teachers</p>
                </div>
            </div>
            <!-- 2ND SECTION: Recent Transactions with Sort and Download -->
            <div class="section-card flex-col">
                <div class="w-full flex items-center justify-between mt-[16px]">
                    <!-- Left: Sort controls -->
                    <div class="flex flex-col items-start gap-[20px]">
                        <div class="flex gap-[10px] items-center">
                            <i class="ph ph-arrows-down-up text-[24px]"></i>
                            <p class="body-text2-semibold">Sort</p>
                        </div>
                        <div class="flex gap-[10px] items-center">
                            <label class="inline-flex items-center">
                                <input type="radio" name="transactions-sort" value="recent" class="form-radio h-4 w-4" checked>
                                <span class="ml-2">Recent</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="transactions-sort" value="ascending" class="form-radio h-4 w-4">
                                <span class="ml-2">Ascending</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="transactions-sort" value="descending" class="form-radio h-4 w-4">
                                <span class="ml-2">Descending</span>
                            </label>
                        </div>
                    </div>
                    <!-- Right: Download button -->
                    <div>
                        <button type="button" class="group btn-secondary">
                            <i class="ph ph-download text-[20px] group-hover:hidden"></i>
                            <i class="ph-duotone ph-download text-[20px] hidden group-hover:block"></i>
                            <p class="font-medium">Download Faculty Records</p>
                        </button>
                    </div>
                </div>
                <!-- Optional: search / table placeholders -->
                <div>
                    <div class="w-full flex items-center gap-[10px]">
                        <i class="ph ph-magnifying-glass text-[30px]"></i>
                        <input type="text" placeholder="Search Faculty" class="w-[500px] h-[40px] border border-company_black rounded-[10px] p-[12px] focus:outline-offset-2 focus:accent-tertiary">
                    </div>
                    <div class="w-full h-[220px] bg-primary/5 border border-primary/10 rounded-[10px] flex items-center justify-center">
                        <p class="text-company_grey">Faculty records table placeholder</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Add Faculty Modal -->
<div id="addFacultyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="container bg-white rounded-lg shadow-xl p-8 w-lg mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Add Faculty Account</h2>
            <button onclick="closeAddFacultyModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ph ph-x text-[24px]"></i>
            </button>
        </div>
        
        <form id="addFacultyForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" id="firstName" name="first_name" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="John">
                </div>
                <div>
                    <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input type="text" id="lastName" name="last_name"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Doe">
                </div>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="teacher@example.com">
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="ph ph-info text-blue-600 text-[20px] mr-2"></i>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">Automatic Setup</p>
                        <p class="text-xs text-blue-600">A secure password will be generated and sent to the teacher's email.</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4">
                <button type="button" onclick="closeAddFacultyModal()" 
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition duration-200">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition duration-200 flex items-center">
                    <i class="ph ph-user-plus text-[18px] mr-2"></i>
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-60">
    <div class="bg-white rounded-lg p-8 flex items-center space-x-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="text-gray-700">Creating faculty account...</p>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function openAddFacultyModal() {
    document.getElementById('addFacultyModal').classList.remove('hidden');
    document.getElementById('email').focus();
}

function closeAddFacultyModal() {
    document.getElementById('addFacultyModal').classList.add('hidden');
    document.getElementById('addFacultyForm').reset();
}

// Handle form submission
document.getElementById('addFacultyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add_faculty');
    
    const email = formData.get('email').trim();
    if (!email) {
        Swal.fire({
            icon: 'error',
            title: 'Email Required',
            text: 'Please enter the teacher\'s email address.'
        });
        return;
    }
    
    // Show loading overlay
    document.getElementById('loadingOverlay').classList.remove('hidden');
    document.getElementById('addFacultyModal').classList.add('hidden');
    
    // Send AJAX request
    fetch('admin-faculty.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingOverlay').classList.add('hidden');
        
        if (data.success) {
            if (data.credentials && data.email_error) {
                // Account created but email failed
                Swal.fire({
                    icon: 'warning',
                    title: 'Account Created - Email Failed',
                    html: `
                        <p>Faculty account has been created but failed to send email.</p>
                        <br>
                        <div class="bg-gray-100 p-4 rounded text-left">
                            <p><strong>Please provide these credentials manually:</strong></p>
                            <p><strong>Email:</strong> ${data.credentials.email}</p>
                            <p><strong>Password:</strong> <code style="background: #e5e7eb; padding: 2px 8px; border-radius: 4px; font-family: monospace;">${data.credentials.password}</code></p>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload(); // Refresh to update teacher count
                });
            } else {
                // Success
                Swal.fire({
                    icon: 'success',
                    title: 'Faculty Account Created!',
                    text: data.message,
                    showConfirmButton: true,
                    timer: 4000
                }).then(() => {
                    location.reload(); // Refresh to update teacher count
                });
            }
        } else {
            // Error
            Swal.fire({
                icon: 'error',
                title: 'Failed to Create Account',
                text: data.message
            });
        }
        
        // Reset form
        document.getElementById('addFacultyForm').reset();
    })
    .catch(error => {
        document.getElementById('loadingOverlay').classList.add('hidden');
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Failed to create faculty account. Please try again.'
        });
    });
});

// Email validation
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        this.setCustomValidity('Please enter a valid email address');
        this.classList.add('border-red-500');
        this.classList.remove('border-gray-300');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-500');
        this.classList.add('border-gray-300');
    }
});

// Close modal when clicking outside
document.getElementById('addFacultyModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddFacultyModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddFacultyModal();
    }
});
</script>

</body>
</html>