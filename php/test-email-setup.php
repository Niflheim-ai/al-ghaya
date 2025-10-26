<?php
/**
 * Al-Ghaya Email Setup Tester
 * Use this script to test and debug your email configuration
 * Run at: http://localhost:8080/al-ghaya/php/test-email-setup.php
 */

require_once 'config.php';
require_once 'mail-config.php';

// Start output buffering for clean HTML
ob_start();

// HTML Header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Al-Ghaya Email Setup Tester</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; line-height: 1.6; }
        .header { background: #10375b; color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center; }
        .section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #e7f3ff; border-color: #b3d4fc; color: #31708f; }
        .code { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 15px; font-family: monospace; white-space: pre-wrap; }
        .btn { display: inline-block; padding: 10px 20px; background: #10375b; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0d2d47; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f2f2f2; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>';

echo '<div class="header">
    <h1>📧 Al-Ghaya Email Setup Tester</h1>
    <p>Test and debug your Gmail OAuth2 & SMTP configuration</p>
</div>';

/**
 * Check configuration status
 */
function checkConfiguration() {
    $checks = [
        'Environment File' => file_exists(__DIR__ . '/../.env'),
        'Google Client ID' => !empty(Config::get('GOOGLE_CLIENT_ID')),
        'Google Client Secret' => !empty(Config::get('GOOGLE_CLIENT_SECRET')),
        'Gmail Refresh Token' => !empty(Config::get('GMAIL_REFRESH_TOKEN')),
        'Gmail User Email' => !empty(Config::get('GMAIL_USER_EMAIL')),
        'SMTP Username' => !empty(Config::get('MAIL_USERNAME')),
        'SMTP Password' => !empty(Config::get('MAIL_PASSWORD')),
        'From Address' => !empty(Config::get('MAIL_FROM_ADDRESS')),
        'Composer Autoload' => file_exists(__DIR__ . '/../vendor/autoload.php')
    ];
    
    return $checks;
}

/**
 * Display configuration status
 */
function displayConfigStatus() {
    $checks = checkConfiguration();
    
    echo '<div class="section">';
    echo '<h2>🔧 Configuration Status</h2>';
    echo '<table>';
    echo '<thead><tr><th>Setting</th><th>Status</th><th>Notes</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($checks as $setting => $status) {
        $statusText = $status ? '<span class="status-ok">✓ OK</span>' : '<span class="status-error">✗ Missing</span>';
        $notes = '';
        
        switch ($setting) {
            case 'Environment File':
                $notes = $status ? 'Found .env file' : 'Create .env from .env.example';
                break;
            case 'Google Client ID':
                $notes = $status ? 'OAuth2 client configured' : 'Set GOOGLE_CLIENT_ID in .env';
                break;
            case 'Gmail Refresh Token':
                $notes = $status ? 'OAuth2 ready' : 'Run oauth2-setup.php to generate';
                break;
            case 'SMTP Username':
                $notes = $status ? 'SMTP fallback available' : 'Set MAIL_USERNAME for SMTP fallback';
                break;
            case 'Composer Autoload':
                $notes = $status ? 'Dependencies installed' : 'Run: composer install';
                break;
            default:
                $notes = $status ? 'Configured' : 'Not set';
        }
        
        echo "<tr><td>$setting</td><td>$statusText</td><td>$notes</td></tr>";
    }
    
    echo '</tbody></table>';
    echo '</div>';
}

/**
 * Test email functionality
 */
function testEmailSending() {
    if (isset($_POST['test_email'])) {
        $testEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        
        if (!$testEmail) {
            echo '<div class="section error">';
            echo '<h3>❌ Invalid Email Address</h3>';
            echo '<p>Please provide a valid email address for testing.</p>';
            echo '</div>';
            return;
        }
        
        echo '<div class="section">';
        echo '<h2>📤 Email Test Results</h2>';
        
        try {
            $resetLink = 'http://localhost:8080/al-ghaya/test-reset-link';
            $result = sendPasswordResetEmail($testEmail, 'Test User', $resetLink);
            
            if ($result['success']) {
                echo '<div class="success">';
                echo '<h3>✅ Email Sent Successfully!</h3>';
                echo '<p><strong>To:</strong> ' . htmlspecialchars($testEmail) . '</p>';
                echo '<p><strong>Subject:</strong> Password Reset - Al-Ghaya LMS</p>';
                echo '<p>Check the recipient\'s inbox for the test email.</p>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<h3>❌ Email Sending Failed</h3>';
                echo '<p><strong>Error:</strong> ' . htmlspecialchars($result['error'] ?? 'Unknown error') . '</p>';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<h3>❌ Email Configuration Error</h3>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Suggestion:</strong> Check your OAuth2 or SMTP configuration.</p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
}

/**
 * Display current configuration
 */
function displayCurrentConfig() {
    echo '<div class="section info">';
    echo '<h2>⚙️ Current Configuration</h2>';
    echo '<div class="grid">';
    
    // OAuth2 Configuration
    echo '<div>';
    echo '<h3>OAuth2 Settings</h3>';
    echo '<div class="code">';
    echo 'Client ID: ' . (Config::get('GOOGLE_CLIENT_ID') ? substr(Config::get('GOOGLE_CLIENT_ID'), 0, 20) . '...' : 'Not set') . "\n";
    echo 'Client Secret: ' . (Config::get('GOOGLE_CLIENT_SECRET') ? '[SET]' : '[NOT SET]') . "\n";
    echo 'Refresh Token: ' . (Config::get('GMAIL_REFRESH_TOKEN') ? '[SET]' : '[NOT SET]') . "\n";
    echo 'User Email: ' . (Config::get('GMAIL_USER_EMAIL') ?: 'Not set') . "\n";
    echo '</div>';
    echo '</div>';
    
    // SMTP Configuration
    echo '<div>';
    echo '<h3>SMTP Settings</h3>';
    echo '<div class="code">';
    echo 'Host: ' . (Config::get('MAIL_HOST') ?: 'smtp.gmail.com') . "\n";
    echo 'Port: ' . (Config::get('MAIL_PORT') ?: '587') . "\n";
    echo 'Username: ' . (Config::get('MAIL_USERNAME') ?: 'Not set') . "\n";
    echo 'Password: ' . (Config::get('MAIL_PASSWORD') ? '[SET]' : '[NOT SET]') . "\n";
    echo 'From: ' . (Config::get('MAIL_FROM_ADDRESS') ?: 'Not set') . "\n";
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
}

/**
 * Display setup instructions
 */
function displaySetupInstructions() {
    echo '<div class="section warning">';
    echo '<h2>📋 Quick Setup Instructions</h2>';
    
    echo '<h3>Method 1: OAuth2 (Recommended)</h3>';
    echo '<ol>';
    echo '<li>Create Google Cloud Console project</li>';
    echo '<li>Enable Gmail API</li>';
    echo '<li>Create OAuth2 credentials</li>';
    echo '<li>Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env</li>';
    echo '<li>Run: <a href="oauth2-setup.php">oauth2-setup.php</a></li>';
    echo '<li>Add generated GMAIL_REFRESH_TOKEN to .env</li>';
    echo '</ol>';
    
    echo '<h3>Method 2: SMTP App Password</h3>';
    echo '<ol>';
    echo '<li>Enable 2FA on Gmail</li>';
    echo '<li>Generate App Password at: <a href="https://myaccount.google.com/apppasswords" target="_blank">Google App Passwords</a></li>';
    echo '<li>Set MAIL_USERNAME and MAIL_PASSWORD in .env</li>';
    echo '</ol>';
    
    echo '<p><strong>📖 Full Guide:</strong> <a href="../GMAIL_SETUP_GUIDE.md" target="_blank">GMAIL_SETUP_GUIDE.md</a></p>';
    echo '</div>';
}

// Main execution
displayConfigStatus();
displayCurrentConfig();

// Test form
echo '<div class="section">';
echo '<h2>🧪 Test Email Sending</h2>';
echo '<form method="post">';
echo '<p><label for="email">Test Email Address:</label></p>';
echo '<input type="email" id="email" name="email" placeholder="your-email@gmail.com" style="width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>';
echo '<br><br>';
echo '<button type="submit" name="test_email" class="btn">Send Test Email</button>';
echo '</form>';
echo '</div>';

testEmailSending();
displaySetupInstructions();

// Security warning
echo '<div class="section error">';
echo '<h2>⚠️ Security Notice</h2>';
echo '<p><strong>Important:</strong> Delete this test file after completing your email setup!</p>';
echo '<p>This file exposes configuration information and should not be accessible in production.</p>';
echo '<code>rm php/test-email-setup.php</code>';
echo '</div>';

echo '</body></html>';

// Clean up output buffer
ob_end_flush();
?>