<?php
session_start();
include('../php/dbConnection.php');
require_once '../php/mail-config.php';

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
        
        // Always show success message for security (prevent email enumeration)
        $success = true;
        $successMessage = "If an account with that email exists, a password reset link has been sent.";
        
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
                // Send email using OAuth2
                require_once '../php/mail-config.php';
                
                try {
                    $result = sendPasswordResetEmailOAuth2($resetEmail, $userData['fname'], $resetLink);
                    if (!$result['success']) {
                        error_log("OAuth2 email failed: " . $result['error']);
                    }
                } catch (Exception $e) {
                    error_log("Failed to send password reset email via OAuth2: " . $e->getMessage());
                }
            }
        }
        // Always show success message regardless of whether email exists
    }
}

/**
 * Generate HTML email template for password reset
 */
function generatePasswordResetEmail($firstName, $resetLink) {
    $appName = Config::get('APP_NAME', 'Al-Ghaya LMS');
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #10375b 0%, #0d2a47 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #10375b; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
            .button:hover { background: #0d2a47; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            .link-box { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0; word-break: break-all; font-family: monospace; font-size: 14px; border: 1px solid #e9ecef; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset Request</h1>
                <p>Reset your {$appName} password</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($firstName) . ",</h2>
                
                <p>We received a request to reset the password for your {$appName} account.</p>
                
                <p>To reset your password, click the button below:</p>
                
                <div style='text-align: center;'>
                    <a href='{$resetLink}' class='button'>Reset My Password</a>
                </div>
                
                <div class='warning'>
                    <h4>⚠️ Security Notice:</h4>
                    <ul>
                        <li>This link will expire in <strong>1 hour</strong> for security reasons</li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>Never share this link with anyone</li>
                        <li>Use a strong, unique password for your account</li>
                    </ul>
                </div>
                
                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <div class='link-box'>{$resetLink}</div>
                
                <div class='footer'>
                    <p><strong>{$appName}</strong></p>
                    <p>This is an automated message, please do not reply to this email.</p>
                    <p>© " . date('Y') . " Al-Ghaya LMS. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
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
                            Reset links expire in 1 hour for your security. Check your email inbox and spam folder for the reset instructions.
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
