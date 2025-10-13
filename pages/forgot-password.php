<?php
session_start();
include('../php/dbConnection.php');

// Check if config is available for email settings
$emailEnabled = false;
if (file_exists('../php/config.php')) {
    require_once '../php/config.php';
    $emailEnabled = Config::get('EMAIL_ENABLED', false);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetEmail'])) {
    $resetEmail = trim($_POST['resetEmail']);
    $errors = [];
    $success = false;
    
    // Validate email
    if (empty($resetEmail)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($resetEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    if (empty($errors)) {
        // Check if user exists
        $userQuery = $conn->prepare("SELECT userID, fname, lname, email FROM user WHERE email = ? AND isActive = 1");
        $userQuery->bind_param("s", $resetEmail);
        $userQuery->execute();
        $userResult = $userQuery->get_result();
        
        if ($userResult->num_rows > 0) {
            $userData = $userResult->fetch_assoc();
            
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
            
            // Store reset token in database
            $tokenQuery = $conn->prepare("
                INSERT INTO password_resets (email, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                expires_at = VALUES(expires_at), 
                created_at = NOW()
            ");
            $tokenQuery->bind_param("sss", $resetEmail, $resetToken, $resetExpiry);
            
            if ($tokenQuery->execute()) {
                if ($emailEnabled) {
                    // Send email with reset link
                    $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                                 '://' . $_SERVER['HTTP_HOST'] . 
                                 dirname($_SERVER['REQUEST_URI']) . '/reset-password.php?token=' . $resetToken;
                    
                    $subject = 'Password Reset - Al-Ghaya LMS';
                    $message = generateEmailTemplate($userData['fname'], $resetLink);
                    $headers = [
                        'MIME-Version: 1.0',
                        'Content-type: text/html; charset=UTF-8',
                        'From: Al-Ghaya LMS <noreply@al-ghaya.edu>',
                        'Reply-To: support@al-ghaya.edu',
                        'X-Mailer: PHP/' . phpversion()
                    ];
                    
                    if (mail($resetEmail, $subject, $message, implode("\r\n", $headers))) {
                        $success = true;
                        $successMessage = "Password reset link has been sent to your email address.";
                    } else {
                        $errors[] = "Failed to send reset email. Please contact support.";
                    }
                } else {
                    // For demo/development - show the reset link directly
                    $resetLink = '/al-ghaya/pages/reset-password.php?token=' . $resetToken;
                    $success = true;
                    $successMessage = "Password reset token generated. Use this link: <a href='$resetLink' class='text-blue-600 underline'>Reset Password</a>";
                }
            } else {
                $errors[] = "Failed to generate reset token. Please try again.";
            }
        } else {
            // Don't reveal if email exists for security reasons, but still show success message
            $success = true;
            $successMessage = "If an account with that email exists, a password reset link has been sent.";
        }
    }
}

/**
 * Generate HTML email template for password reset
 */
function generateEmailTemplate($firstName, $resetLink) {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset - Al-Ghaya</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; }
            .logo { max-width: 150px; height: auto; }
            .title { color: #2563eb; font-size: 24px; margin: 10px 0; font-weight: bold; }
            .content { font-size: 16px; line-height: 1.8; }
            .button { display: inline-block; background-color: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; text-align: center; }
            .button:hover { background-color: #1d4ed8; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef; font-size: 14px; color: #666; text-align: center; }
            .warning { background-color: #fef3cd; color: #664d03; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #f0ad4e; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1 class="title">🔐 Password Reset Request</h1>
            </div>
            
            <div class="content">
                <p>Hello <strong>' . htmlspecialchars($firstName) . '</strong>,</p>
                
                <p>We received a request to reset the password for your Al-Ghaya LMS account.</p>
                
                <p>To reset your password, click the button below:</p>
                
                <p style="text-align: center;">
                    <a href="' . $resetLink . '" class="button">Reset My Password</a>
                </p>
                
                <div class="warning">
                    <strong>⚠️ Security Notice:</strong>
                    <ul>
                        <li>This link will expire in 1 hour for security reasons</li>
                        <li>If you didn\'t request this reset, please ignore this email</li>
                        <li>Never share this link with anyone</li>
                    </ul>
                </div>
                
                <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                <p style="word-break: break-all; font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 4px;">' . $resetLink . '</p>
            </div>
            
            <div class="footer">
                <p><strong>Al-Ghaya Learning Management System</strong></p>
                <p>This is an automated message, please do not reply to this email.</p>
                <p>If you need help, contact our support team.</p>
            </div>
        </div>
    </body>
    </html>';
}

// Create password_resets table if it doesn't exist
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            used_at TIMESTAMP NULL,
            INDEX idx_email (email),
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        )
    ");
} catch (Exception $e) {
    error_log("Failed to create password_resets table: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Al-Ghaya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="../images/al-ghaya_logoForPrint.svg">
    <style>
        .content { min-height: 100vh; }
        .bg-primary { background-color: #10375b; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="content flex justify-center items-center min-h-screen px-6">
        <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="login.php" class="inline-flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Login
                </a>
            </div>

            <!-- Header -->
            <div class="text-center mb-8">
                <img class="h-12 mx-auto mb-4" src="../images/al-ghaya_logoForPrint.svg" alt="Al-Ghaya Logo">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Forgot Password?</h1>
                <p class="text-gray-600">No worries! Enter your email and we'll send you reset instructions.</p>
            </div>

            <?php if (isset($success) && $success): ?>
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
                    <a href="login.php" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                        </svg>
                        Return to Login
                    </a>
                </div>
            <?php else: ?>
                <!-- Reset Form -->
                <form method="POST" class="space-y-6">
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
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
                    <?php endif; ?>

                    <div>
                        <label for="resetEmail" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <div class="relative">
                            <input type="email" id="resetEmail" name="resetEmail" 
                                   value="<?= htmlspecialchars($_POST['resetEmail'] ?? $_GET['email'] ?? '') ?>"
                                   placeholder="Enter your email address" required 
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Send Reset Instructions
                    </button>
                </form>

                <!-- Help Text -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        Remember your password? 
                        <a href="login.php" class="font-medium text-blue-600 hover:text-blue-800">Sign in here</a>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Security Notice -->
            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-yellow-800">Security Notice</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            Reset links expire in 1 hour for your security. 
                            <?php if (!$emailEnabled): ?>
                                <br><strong>Demo Mode:</strong> Email sending is disabled. Reset links will be displayed directly.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus email input if not already filled
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('resetEmail');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>