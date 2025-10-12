<?php
/**
 * Al-Ghaya Email Configuration
 * Secured PHPMailer setup with environment variables
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Create configured PHPMailer instance
 * @return PHPMailer Configured PHPMailer instance
 */
function createMailer() {
    try {
        // Validate required email configuration
        Config::validateRequired([
            'MAIL_HOST',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_FROM_ADDRESS'
        ]);
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = Config::get('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = Config::get('MAIL_USERNAME');
        $mail->Password = Config::get('MAIL_PASSWORD');
        $mail->SMTPSecure = Config::get('MAIL_ENCRYPTION', 'tls') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = Config::get('MAIL_PORT', 587);
        
        // Default sender
        $mail->setFrom(
            Config::get('MAIL_FROM_ADDRESS'),
            Config::get('MAIL_FROM_NAME', Config::get('APP_NAME', 'Al-Ghaya LMS'))
        );
        
        // Enable debug in development
        if (Config::get('APP_DEBUG', false)) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        
        return $mail;
        
    } catch (Exception $e) {
        error_log("Email configuration error: " . $e->getMessage());
        throw new Exception("Email system is not properly configured.");
    }
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
        
        // Recipients
        $mail->addAddress($email, trim($firstName . ' ' . $lastName));
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to ' . Config::get('APP_NAME', 'Al-Ghaya LMS') . ' - Your Teacher Account';
        
        $displayName = !empty($firstName) ? $firstName . ' ' . $lastName : 'Teacher';
        $loginUrl = Config::get('APP_URL', 'https://al-ghaya.vercel.app') . '/pages/login.php';
        
        $emailBody = "
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
                .credentials { background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10375b; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .button { display: inline-block; background: #10375b; color: white !important; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 15px 0; font-weight: 500; }
                .button:hover { background: #0d2a47; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                .security-note { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0; }
                .code { background: #f8f9fa; padding: 8px 12px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 14px; border: 1px solid #e9ecef; }
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
                    <p>Welcome to Al-Ghaya Learning Management System! An administrator has created a teacher account for you.</p>
                    
                    <div class='credentials'>
                        <h3>🔐 Your Login Credentials:</h3>
                        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                        <p><strong>Temporary Password:</strong> <span class='code'>" . htmlspecialchars($password) . "</span></p>
                    </div>
                    
                    <div class='security-note'>
                        <h4>⚠️ Important Security Notes:</h4>
                        <ul>
                            <li>Please change your password immediately after your first login</li>
                            <li>Do not share your login credentials with anyone</li>
                            <li>Keep your account information secure</li>
                            <li>Report any suspicious activity to the administrator</li>
                        </ul>
                    </div>
                    
                    <h3>📚 Getting Started:</h3>
                    <ol>
                        <li>Visit the Al-Ghaya LMS login page using the button below</li>
                        <li>Sign in with the credentials provided above</li>
                        <li>Complete your profile setup in the dashboard</li>
                        <li>Change your password in Profile Settings</li>
                        <li>Start creating courses and engaging with students</li>
                    </ol>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$loginUrl' class='button'>🚀 Login to Al-Ghaya LMS</a>
                    </div>
                    
                    <p>If you have any questions or need assistance, please contact our support team.</p>
                    
                    <div class='footer'>
                        <p>This email was sent from " . Config::get('APP_NAME', 'Al-Ghaya LMS') . "</p>
                        <p>© " . date('Y') . " Al-Ghaya LMS. All rights reserved.</p>
                        <p style='margin-top: 10px; font-size: 12px;'>
                            This is an automated message. Please do not reply to this email.
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $emailBody;
        
        // Send email
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
            'error' => Config::get('APP_DEBUG', false) ? $e->getMessage() : 'Email service temporarily unavailable'
        ];
    }
}

/**
 * Get OAuth authorization URL with security enhancements
 * @return string Secure authorization URL
 */
function getSecureOAuthUrl() {
    global $client;
    
    // Generate CSRF state parameter
    $state = bin2hex(random_bytes(32));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_timestamp'] = time();
    
    $client->setState($state);
    return $client->createAuthUrl();
}

/**
 * Validate OAuth callback state
 * @param string $receivedState State from OAuth callback
 * @return bool True if valid
 */
function validateOAuthCallback($receivedState) {
    // Check if state exists and matches
    if (!isset($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $receivedState)) {
        return false;
    }
    
    // Check if OAuth request is not too old (5 minutes max)
    if (!isset($_SESSION['oauth_timestamp']) || (time() - $_SESSION['oauth_timestamp']) > 300) {
        return false;
    }
    
    return true;
}

/**
 * Clean up OAuth session data
 */
function cleanupOAuthData() {
    unset($_SESSION['oauth_state']);
    unset($_SESSION['oauth_timestamp']);
}
?>