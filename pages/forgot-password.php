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
                // Build reset URL
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                
                // Detect base path
                $basePath = '';
                if (strpos($_SERVER['REQUEST_URI'], '/al-ghaya/') !== false) {
                    $basePath = '/al-ghaya';
                }
                
                $resetLink = $protocol . '://' . $host . $basePath . '/pages/reset-password.php?token=' . $resetToken;
                
                // Send email
                try {
                    $result = sendPasswordResetEmail($resetEmail, $userData['fname'], $resetLink);
                    if (!$result['success']) {
                        error_log("Password reset email failed: " . $result['error']);
                    }
                } catch (Exception $e) {
                    error_log("Failed to send password reset email: " . $e->getMessage());
                }
            }
        }
        // Always show success message regardless of whether email exists
    }
}