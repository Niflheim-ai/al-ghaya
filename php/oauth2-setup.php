<?php
/**
 * Gmail OAuth2 Setup for Al-Ghaya LMS
 * Run this once to get the refresh token for OAuth2 email sending
 */

require_once 'config.php';
require_once '../vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

session_start();

$clientId = Config::get('GOOGLE_CLIENT_ID');
$clientSecret = Config::get('GOOGLE_CLIENT_SECRET');
$redirectUri = 'http://localhost:8080/al-ghaya/php/oauth2-setup.php';

// Check if running on tunnel
if (strpos($_SERVER['HTTP_HOST'], '.loca.lt') !== false) {
    $redirectUri = 'https://' . $_SERVER['HTTP_HOST'] . '/al-ghaya/php/oauth2-setup.php';
}

if (empty($clientId) || empty($clientSecret)) {
    die('<div style="font-family: Arial; padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 5px; color: #900;"><strong>Error:</strong> Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file</div>');
}

$provider = new Google([
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri' => $redirectUri
]);

echo '<html><head><title>Gmail OAuth2 Setup - Al-Ghaya LMS</title><style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;line-height:1.6}.success{background:#dfd;border:1px solid #4f8a10;color:#4f8a10;padding:15px;border-radius:5px;margin:20px 0}.info{background:#e7f3ff;border:1px solid #b3d4fc;color:#31708f;padding:15px;border-radius:5px;margin:20px 0}.error{background:#fee;border:1px solid #fcc;color:#900;padding:15px;border-radius:5px;margin:20px 0}.code{background:#f8f9fa;border:1px solid #e9ecef;border-radius:4px;padding:15px;font-family:monospace;font-size:14px;white-space:pre-wrap;margin:10px 0}.btn{display:inline-block;background:#10375b;color:white;padding:15px 30px;text-decoration:none;border-radius:6px;font-weight:bold;margin:10px 0}</style></head><body>';

echo "<h1>Gmail OAuth2 Setup - Al-Ghaya LMS</h1>";

if (!isset($_GET['code'])) {
    // Step 1: Get authorization code
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => [
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/userinfo.email'
        ],
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]);
    $_SESSION['oauth2state'] = $provider->getState();
    
    echo "<div class='info'>";
    echo "<h3>Step 1: Authorize Al-Ghaya LMS</h3>";
    echo "<p>Click the link below to authorize Al-Ghaya LMS to send emails via your Gmail account:</p>";
    echo "<p><a href='" . htmlspecialchars($authUrl) . "' target='_blank' class='btn'>Authorize Gmail Access</a></p>";
    echo "<p><strong>Note:</strong> You'll be redirected back to this page after authorization.</p>";
    echo "<p><strong>Important:</strong> Make sure to grant permissions for sending emails when prompted by Google.</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>Setup Instructions:</h4>";
    echo "<ol>";
    echo "<li>Click the 'Authorize Gmail Access' button above</li>";
    echo "<li>Sign in to your Gmail account if prompted</li>";
    echo "<li>Review and accept the permissions (Al-Ghaya needs to send emails)</li>";
    echo "<li>You'll be redirected back here with your OAuth2 credentials</li>";
    echo "<li>Copy the provided configuration to your .env file</li>";
    echo "</ol>";
    echo "</div>";
    
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    echo "<div class='error'><strong>Error:</strong> Invalid state parameter. Please try again.</div>";
    echo "<p><a href='oauth2-setup.php' class='btn'>Start Over</a></p>";
    
} else {
    // Step 2: Exchange code for tokens
    try {
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);
        
        $refreshToken = $token->getRefreshToken();
        $userEmail = '';
        
        // Get user email
        try {
            $resourceOwner = $provider->getResourceOwner($token);
            $userEmail = $resourceOwner->getEmail();
        } catch (Exception $e) {
            error_log("Could not get user email: " . $e->getMessage());
        }
        
        echo "<div class='success'>";
        echo "<h2>✅ Setup Complete!</h2>";
        echo "<p>OAuth2 credentials have been successfully generated.</p>";
        echo "</div>";
        
        echo "<h3>Step 2: Add to your .env file</h3>";
        echo "<p>Add these lines to your <strong>.env</strong> file:</p>";
        echo "<div class='code'>";
        echo "# Gmail OAuth2 Configuration\n";
        echo "GMAIL_REFRESH_TOKEN=" . htmlspecialchars($refreshToken) . "\n";
        if ($userEmail) {
            echo "GMAIL_USER_EMAIL=" . htmlspecialchars($userEmail) . "\n";
        }
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h4>Next Steps:</h4>";
        echo "<ol>";
        echo "<li><strong>Copy the above configuration</strong> to your .env file</li>";
        echo "<li><strong>Restart Apache</strong> to reload environment variables</li>";
        echo "<li><strong>Test email sending</strong> using the forgot password feature</li>";
        echo "<li><strong>Delete this file</strong> (oauth2-setup.php) after setup for security</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h4>Verification:</h4>";
        echo "<p>Your Gmail account <strong>" . htmlspecialchars($userEmail) . "</strong> is now authorized to send emails for Al-Ghaya LMS.</p>";
        echo "<p>The refresh token will automatically handle authentication renewal.</p>";
        echo "</div>";
        
        // Security warning
        echo "<div class='error'>";
        echo "<h4>⚠️ Security Warning</h4>";
        echo "<p><strong>Important:</strong> Delete this setup file after completing the configuration for security reasons.</p>";
        echo "<p>Command: <code>rm php/oauth2-setup.php</code> or delete it manually.</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<p><a href='oauth2-setup.php' class='btn'>Try Again</a></p>";
    }
    
    unset($_SESSION['oauth2state']);
}

echo "</body></html>";
?>