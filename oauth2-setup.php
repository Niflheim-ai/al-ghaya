<?php
/**
 * Gmail OAuth2 Setup for Al-Ghaya LMS
 * Run this once to get the refresh token
 */

require_once 'php/config.php';
require_once 'vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

session_start();

$clientId = Config::get('GOOGLE_CLIENT_ID');
$clientSecret = Config::get('GOOGLE_CLIENT_SECRET');
$redirectUri = 'http://localhost:8080/al-ghaya/oauth2-setup.php';

if (empty($clientId) || empty($clientSecret)) {
    die('Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file');
}

$provider = new Google([
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri' => $redirectUri
]);

echo "<h1>Gmail OAuth2 Setup - Al-Ghaya LMS</h1>";

if (!isset($_GET['code'])) {
    // Step 1: Get authorization code
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => ['https://www.googleapis.com/auth/gmail.send']
    ]);
    $_SESSION['oauth2state'] = $provider->getState();
    
    echo "<p>Click the link below to authorize Al-Ghaya LMS to send emails via your Gmail account:</p>";
    echo "<p><a href='{$authUrl}' target='_blank' style='background: #10375b; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px;'>Authorize Gmail Access</a></p>";
    echo "<p><strong>Note:</strong> You'll be redirected back to this page after authorization.</p>";
    
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    echo "<p style='color: red;'>Error: Invalid state parameter</p>";
    
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
        
        echo "<h2>✅ Setup Complete!</h2>";
        echo "<p>Add these lines to your <strong>.env</strong> file:</p>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 6px; font-family: monospace;'>";
        echo "GMAIL_REFRESH_TOKEN=" . htmlspecialchars($refreshToken) . "<br>";
        if ($userEmail) {
            echo "GMAIL_USER_EMAIL=" . htmlspecialchars($userEmail) . "<br>";
        }
        echo "</div>";
        
        echo "<p><strong>Important:</strong></p>";
        echo "<ul>";
        echo "<li>Copy the above lines to your .env file</li>";
        echo "<li>Restart Apache to reload environment variables</li>";
        echo "<li>Test with the OAuth2 email test script</li>";
        echo "<li>Delete this oauth2-setup.php file after setup</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    unset($_SESSION['oauth2state']);
}
?>
