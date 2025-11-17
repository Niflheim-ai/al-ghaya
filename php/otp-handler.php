<?php
// Prevent any output before JSON
ob_start();

session_start();
require_once 'dbConnection.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendOTPEmail($email, $otp, $firstName) {
    try {
        $mail = new PHPMailer(true);
        
        // SMTP Configuration - Simple Authentication
        $mail->isSMTP();
        $mail->Host = Config::get('MAIL_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth = true;
        $mail->Username = Config::get('MAIL_USERNAME');
        $mail->Password = Config::get('MAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = Config::get('MAIL_PORT', 587);
        
        // From address
        $fromEmail = Config::get('MAIL_FROM_ADDRESS', Config::get('MAIL_USERNAME'));
        $fromName = Config::get('MAIL_FROM_NAME', 'Al-Ghaya LMS');
        $mail->setFrom($fromEmail, $fromName);
        
        // Add recipient
        $mail->addAddress($email, $firstName);
        
        // Email content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Verify Your Al-Ghaya Account';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #ffffff; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #2563eb; margin: 0; font-size: 28px; }
                .otp-box { background-color: #f3f4f6; padding: 20px; text-align: center; margin: 30px 0; border-radius: 8px; }
                .otp-code { color: #2563eb; font-size: 36px; margin: 0; letter-spacing: 8px; font-weight: bold; }
                .footer { color: #999; font-size: 12px; text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
                .warning { color: #666; font-size: 14px; margin-top: 30px; background-color: #fef3c7; padding: 12px; border-radius: 6px; border-left: 4px solid #f59e0b; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🌙 Al-Ghaya</h1>
                </div>
                <h2 style='color: #10375b;'>Hello {$firstName}!</h2>
                <p>Thank you for registering with Al-Ghaya. To complete your registration, please use the following verification code:</p>
                <div class='otp-box'>
                    <h1 class='otp-code'>{$otp}</h1>
                </div>
                <p>This code will expire in <strong>10 minutes</strong>.</p>
                <div class='warning'>
                    <strong>⚠️ Security Tip:</strong> Never share this code with anyone. Al-Ghaya staff will never ask for your verification code.
                </div>
                <p style='color: #666; font-size: 14px; margin-top: 30px;'>If you didn't request this code, please ignore this email.</p>
                <div class='footer'>
                    <p>© 2025 Al-Ghaya Learning Management System</p>
                    <p>Empowering Arabic and Islamic Education</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text alternative
        $mail->AltBody = "Hello {$firstName}!\n\n"
            . "Your Al-Ghaya verification code is: {$otp}\n\n"
            . "This code will expire in 10 minutes.\n\n"
            . "If you didn't request this code, please ignore this email.\n\n"
            . "© 2025 Al-Ghaya Learning Management System";
        
        $mail->send();
        error_log("OTP email sent successfully to: " . $email);
        return true;
        
    } catch (Exception $e) {
        error_log("OTP Email sending failed: " . $e->getMessage());
        return false;
    }
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Clear any buffered output and set JSON header
ob_end_clean();
header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');
        $firstName = trim($_POST['firstName'] ?? '');
        $skipDuplicateCheck = isset($_POST['skip_duplicate_check']) && $_POST['skip_duplicate_check'] === 'true';
        
        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }
        
        if (empty($firstName)) {
            echo json_encode(['success' => false, 'message' => 'First name is required']);
            exit;
        }
        
        // Check if email already exists (ONLY for new registrations)
        if (!$skipDuplicateCheck) {
            $checkStmt = $conn->prepare("SELECT userID FROM user WHERE email = ? LIMIT 1");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'An account with this email already exists. Please log in instead.']);
                $checkStmt->close();
                exit;
            }
            $checkStmt->close();
        }
        
        // Generate OTP
        $otp = generateOTP();
        
        // Delete old OTPs for this email (cleanup)
        $deleteStmt = $conn->prepare("DELETE FROM email_verification_otps WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        // Insert new OTP - Let MySQL calculate expiry time
        $insertStmt = $conn->prepare("
            INSERT INTO email_verification_otps 
            (email, otp, expires_at) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ");
        if (!$insertStmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $insertStmt->bind_param("ss", $email, $otp);
        
        if ($insertStmt->execute()) {
            $insertStmt->close();
            
            // Send OTP email
            if (sendOTPEmail($email, $otp, $firstName)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Verification code sent to your email. Please check your inbox.'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to send verification email. Please check your email configuration.'
                ]);
            }
        } else {
            throw new Exception("Failed to save OTP");
        }
        exit;
    }
    
    if ($action === 'verify_otp') {
        $email = trim($_POST['email'] ?? '');
        $otp = trim($_POST['otp'] ?? '');
        
        // Validation
        if (empty($email) || empty($otp)) {
            echo json_encode(['success' => false, 'message' => 'Email and verification code are required']);
            exit;
        }
        
        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code format']);
            exit;
        }
        
        // Verify OTP
        $verifyStmt = $conn->prepare("
            SELECT id, created_at 
            FROM email_verification_otps 
            WHERE email = ? 
            AND otp = ? 
            AND expires_at > NOW() 
            AND is_verified = 0 
            LIMIT 1
        ");
        
        if (!$verifyStmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $verifyStmt->bind_param("ss", $email, $otp);
        $verifyStmt->execute();
        $result = $verifyStmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Mark as verified
            $updateStmt = $conn->prepare("UPDATE email_verification_otps SET is_verified = 1 WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("i", $row['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Email verified successfully! Creating your account...'
            ]);
        } else {
            // Check if OTP exists but expired
            $expiredStmt = $conn->prepare("
                SELECT id 
                FROM email_verification_otps 
                WHERE email = ? 
                AND otp = ? 
                AND expires_at <= NOW()
                LIMIT 1
            ");
            
            if ($expiredStmt) {
                $expiredStmt->bind_param("ss", $email, $otp);
                $expiredStmt->execute();
                $expiredResult = $expiredStmt->get_result();
                
                if ($expiredResult->num_rows > 0) {
                    echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please check and try again.']);
                }
                $expiredStmt->close();
            }
        }
        $verifyStmt->close();
        exit;
    }
    
    // Unknown action
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
    
} catch (Exception $e) {
    error_log("OTP Handler Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'System error occurred. Please try again later.'
    ]);
    exit;
}
?>
