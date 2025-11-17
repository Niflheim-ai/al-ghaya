<?php
session_start();
require_once 'dbConnection.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['userID'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    $userID = (int)$_SESSION['userID'];
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'verify_current_password':
            verifyCurrentPassword($conn, $userID);
            break;
            
        case 'verify_password_change':
            verifyPasswordChange($conn, $userID);
            break;
            
        case 'verify_and_update_email':
            verifyAndUpdateEmail($conn, $userID);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error. Please try again.']);
}

/**
 * Verify current password before sending OTP
 */
function verifyCurrentPassword($conn, $userID) {
    $currentPassword = $_POST['current_password'] ?? '';
    
    if (empty($currentPassword)) {
        echo json_encode(['success' => false, 'message' => 'Password is required']);
        return;
    }
    
    // Get user's hashed password
    $stmt = $conn->prepare("SELECT password FROM user WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    // Verify password
    if (password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => true, 'message' => 'Password verified']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    }
}

/**
 * Verify OTP and change password
 */
function verifyPasswordChange($conn, $userID) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $otp = trim($_POST['otp'] ?? '');
    
    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code format']);
        return;
    }
    
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']);
        return;
    }
    
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        return;
    }
    
    // Get user info
    $stmt = $conn->prepare("SELECT email, password FROM user WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    // Verify current password (double-check for security)
    if (!password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }
    
    // Check if new password is same as current
    if (password_verify($newPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'New password cannot be the same as current password']);
        return;
    }
    
    // Verify OTP (sent to current email for security)
    $verifyStmt = $conn->prepare("
        SELECT id 
        FROM email_verification_otps 
        WHERE email = ? 
        AND otp = ? 
        AND expires_at > NOW() 
        AND is_verified = 0 
        LIMIT 1
    ");
    $verifyStmt->bind_param("ss", $user['email'], $otp);
    $verifyStmt->execute();
    $otpResult = $verifyStmt->get_result();
    
    if ($otpResult->num_rows === 0) {
        // Check if expired
        $expiredStmt = $conn->prepare("
            SELECT id 
            FROM email_verification_otps 
            WHERE email = ? 
            AND otp = ? 
            AND expires_at <= NOW()
            LIMIT 1
        ");
        $expiredStmt->bind_param("ss", $user['email'], $otp);
        $expiredStmt->execute();
        $expiredResult = $expiredStmt->get_result();
        
        if ($expiredResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
        }
        $expiredStmt->close();
        $verifyStmt->close();
        return;
    }
    
    $row = $otpResult->fetch_assoc();
    $verifyStmt->close();
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $updateStmt = $conn->prepare("UPDATE user SET password = ? WHERE userID = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userID);
    
    if ($updateStmt->execute()) {
        // Mark OTP as verified
        $markStmt = $conn->prepare("UPDATE email_verification_otps SET is_verified = 1 WHERE id = ?");
        $markStmt->bind_param("i", $row['id']);
        $markStmt->execute();
        $markStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        throw new Exception("Failed to update password");
    }
    $updateStmt->close();
}

/**
 * Verify OTP and update email
 */
function verifyAndUpdateEmail($conn, $userID) {
    $newEmail = trim($_POST['new_email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    
    // Validation
    if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code format']);
        return;
    }
    
    // Get current email
    $currentStmt = $conn->prepare("SELECT email FROM user WHERE userID = ?");
    $currentStmt->bind_param("i", $userID);
    $currentStmt->execute();
    $currentUser = $currentStmt->get_result()->fetch_assoc();
    $currentStmt->close();
    
    if ($currentUser && $currentUser['email'] === $newEmail) {
        echo json_encode(['success' => false, 'message' => 'New email is the same as current email']);
        return;
    }
    
    // Verify OTP
    $verifyStmt = $conn->prepare("
        SELECT id 
        FROM email_verification_otps 
        WHERE email = ? 
        AND otp = ? 
        AND expires_at > NOW() 
        AND is_verified = 0 
        LIMIT 1
    ");
    $verifyStmt->bind_param("ss", $newEmail, $otp);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    
    if ($result->num_rows === 0) {
        // Check if expired
        $expiredStmt = $conn->prepare("
            SELECT id 
            FROM email_verification_otps 
            WHERE email = ? 
            AND otp = ? 
            AND expires_at <= NOW()
            LIMIT 1
        ");
        $expiredStmt->bind_param("ss", $newEmail, $otp);
        $expiredStmt->execute();
        $expiredResult = $expiredStmt->get_result();
        
        if ($expiredResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
        }
        $expiredStmt->close();
        $verifyStmt->close();
        return;
    }
    
    $row = $result->fetch_assoc();
    $verifyStmt->close();
    
    // Check if email already exists for another user
    $checkStmt = $conn->prepare("SELECT userID FROM user WHERE email = ? AND userID != ? LIMIT 1");
    $checkStmt->bind_param("si", $newEmail, $userID);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
        $checkStmt->close();
        return;
    }
    $checkStmt->close();
    
    // Update email
    $updateStmt = $conn->prepare("UPDATE user SET email = ? WHERE userID = ?");
    $updateStmt->bind_param("si", $newEmail, $userID);
    
    if ($updateStmt->execute()) {
        // Mark OTP as verified
        $markStmt = $conn->prepare("UPDATE email_verification_otps SET is_verified = 1 WHERE id = ?");
        $markStmt->bind_param("i", $row['id']);
        $markStmt->execute();
        $markStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Email updated successfully']);
    } else {
        throw new Exception("Failed to update email");
    }
    $updateStmt->close();
}
?>
