<?php
/**
 * Al-Ghaya Email Configuration
 * Clean OAuth2 implementation for Gmail
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

/**
 * Create OAuth2 PHPMailer instance for Gmail
 * @return PHPMailer Configured PHPMailer instance with OAuth2
 */
function createOAuth2Mailer() {
    $mail = new PHPMailer(true);
    
    try {
        // OAuth2 settings
        $clientId = Config::get('GOOGLE_CLIENT_ID');
        $clientSecret = Config::get('GOOGLE_CLIENT_SECRET');
        $refreshToken = Config::get('GMAIL_REFRESH_TOKEN');
        $userEmail = Config::get('GMAIL_USER_EMAIL', Config::get('MAIL_USERNAME'));
        
        if (empty($refreshToken)) {
            throw new Exception('Gmail refresh token not found. Please run OAuth2 setup.');
        }
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth = true;
        $mail->AuthType = 'XOAUTH2';
        
        // Create OAuth2 provider
        $provider = new Google([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret
        ]);
        
        // Set up OAuth2
        $mail->setOAuth(
            new OAuth([
                'provider' => $provider,
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'refreshToken' => $refreshToken,
                'userName' => $userEmail,
            ])
        );
        
        // Set from address
        $mail->setFrom($userEmail, Config::get('MAIL_FROM_NAME', 'Al-Ghaya LMS'));
        $mail->CharSet = 'UTF-8';
        
        return $mail;
        
    } catch (Exception $e) {
        error_log("OAuth2 Mail configuration error: " . $e->getMessage());
        throw new Exception("OAuth2 email system configuration failed: " . $e->getMessage());
    }
}

/**
 * Fallback SMTP mailer for environments without OAuth2
 * @return PHPMailer Basic SMTP PHPMailer instance
 */
function createSMTPMailer() {
    $mail = new PHPMailer(true);
    
    try {
        $host = Config::get('MAIL_HOST', 'smtp.gmail.com');
        $port = (int)Config::get('MAIL_PORT', 587);
        $username = Config::get('MAIL_USERNAME', '');
        $password = Config::get('MAIL_PASSWORD', '');
        $encryption = strtolower(Config::get('MAIL_ENCRYPTION', 'tls'));
        $fromAddress = Config::get('MAIL_FROM_ADDRESS', $username);
        $fromName = Config::get('MAIL_FROM_NAME', Config::get('APP_NAME', 'Al-Ghaya LMS'));
        
        if (empty($username) || empty($password)) {
            throw new Exception('MAIL_USERNAME or MAIL_PASSWORD is empty.');
        }
        
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = trim($username);
        $mail->Password = trim($password);
        
        if ($encryption === 'ssl' || $port == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        }
        
        $mail->setFrom($fromAddress, $fromName);
        $mail->CharSet = 'UTF-8';
        
        return $mail;
        
    } catch (Exception $e) {
        error_log("SMTP Mail configuration error: " . $e->getMessage());
        throw new Exception("SMTP email system configuration failed: " . $e->getMessage());
    }
}

/**
 * Create appropriate mailer based on configuration
 * @return PHPMailer Configured PHPMailer instance
 */
function createMailer() {
    // Try OAuth2 first, fallback to SMTP
    if (Config::get('GMAIL_REFRESH_TOKEN')) {
        try {
            return createOAuth2Mailer();
        } catch (Exception $e) {
            error_log("OAuth2 failed, falling back to SMTP: " . $e->getMessage());
        }
    }
    
    return createSMTPMailer();
}

/**
 * Send password reset email
 * @param string $email Recipient email
 * @param string $name Recipient name
 * @param string $resetLink Password reset link
 * @return array Result array with success status and message
 */
function sendPasswordResetEmail($email, $name, $resetLink) {
    try {
        $mail = createMailer();
        
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - Al-Ghaya LMS';
        $mail->Body = generatePasswordResetEmailTemplate($name, $resetLink);
        
        $mail->send();
        return ['success' => true, 'message' => 'Password reset email sent successfully'];
        
    } catch (Exception $e) {
        error_log("Password reset email failed: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Generate password reset email template
 * @param string $firstName Recipient's first name
 * @param string $resetLink Password reset link
 * @return string HTML email template
 */
function generatePasswordResetEmailTemplate($firstName, $resetLink) {
    $appName = Config::get('APP_NAME', 'Al-Ghaya LMS');
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #10375b; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #10375b; color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Password Reset</h1>
                <p>Reset your {$appName} password</p>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($firstName) . ",</h2>
                
                <p>We received a request to reset your password for your {$appName} account.</p>
                
                <p style='text-align: center;'>
                    <a href='{$resetLink}' class='button'>Reset My Password</a>
                </p>
                
                <div class='warning'>
                    <h4>⚠️ Security Notice:</h4>
                    <ul>
                        <li>This link expires in <strong>1 hour</strong></li>
                        <li>If you didn't request this, ignore this email</li>
                        <li>Never share this link with anyone</li>
                    </ul>
                </div>
                
                <p>If the button doesn't work, copy this link:</p>
                <p style='word-break: break-all; font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 4px;'>{$resetLink}</p>
                
                <div class='footer'>
                    <p><strong>{$appName}</strong></p>
                    <p>© " . date('Y') . " Al-Ghaya LMS</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Send welcome email to new teacher
 * @param string $email Teacher's email address
 * @param string $password Generated password
 * @param string $firstName Teacher's first name
 * @param string $lastName Teacher's last name
 * @return array Result array with success status and message
 */
function sendTeacherWelcomeEmail($email, $password, $firstName = '', $lastName = '') {
    try {
        $mail = createMailer();
        
        $mail->addAddress($email, trim($firstName . ' ' . $lastName));
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to ' . Config::get('APP_NAME', 'Al-Ghaya LMS') . ' - Your Teacher Account';
        
        $displayName = !empty($firstName) ? $firstName . ' ' . $lastName : 'Teacher';
        $loginUrl = Config::get('APP_URL', 'http://localhost:8080/al-ghaya') . '/pages/login.php';
        
        $emailBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #10375b; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .credentials { background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10375b; }
                .button { display: inline-block; background: #10375b; color: white !important; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .code { background: #f8f9fa; padding: 8px 12px; border-radius: 4px; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎓 Welcome to " . Config::get('APP_NAME', 'Al-Ghaya LMS') . "!</h1>
                    <p>Your Teacher Account Has Been Created</p>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($displayName) . ",</h2>
                    <p>Welcome to Al-Ghaya Learning Management System!</p>
                    
                    <div class='credentials'>
                        <h3>🔐 Your Login Credentials:</h3>
                        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                        <p><strong>Temporary Password:</strong> <span class='code'>" . htmlspecialchars($password) . "</span></p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$loginUrl' class='button'>🚀 Login to Al-Ghaya LMS</a>
                    </div>
                    
                    <div class='footer'>
                        <p>© " . date('Y') . " Al-Ghaya LMS. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $emailBody;
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Welcome email sent successfully'
        ];
        
    } catch (Exception $e) {
        error_log("Failed to send teacher welcome email: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to send welcome email',
            'error' => $e->getMessage()
        ];
    }
}
?>