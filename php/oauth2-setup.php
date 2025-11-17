<?php
/**
 * Gmail OAuth2 Refresh Token Generator
 * Run this ONCE to get your refresh token
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

// Get your OAuth2 credentials from config
$clientId = Config::get('GOOGLE_CLIENT_ID');
$clientSecret = Config::get('GOOGLE_CLIENT_SECRET');
$redirectUri = 'http://localhost/al-ghaya/php/oauth2-setup.php'; // Must match Google Cloud Console

if (empty($clientId) || empty($clientSecret)) {
    die('Error: GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET must be set in your config.');
}

// Create Google OAuth2 provider
$provider = new Google([
    'clientId'     => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri'  => $redirectUri,
    'accessType'   => 'offline',
    'prompt'       => 'consent'
]);

// Step 1: Get authorization code
if (!isset($_GET['code'])) {
    
    // Get authorization URL
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => [
            'https://mail.google.com/'
        ],
        'access_type' => 'offline',
        'prompt' => 'consent' // Force to show consent screen
    ]);
    
    // Store state in session for security
    session_start();
    $_SESSION['oauth2state'] = $provider->getState();
    
    echo '<html>
    <head>
        <title>Gmail OAuth2 Setup</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .container { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 30px; }
            h1 { color: #1f2937; }
            .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; }
            .btn:hover { background: #1d4ed8; }
            .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 20px 0; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 Gmail OAuth2 Setup</h1>
            <p>This will generate a refresh token for sending emails from Al-Ghaya LMS.</p>
            
            <div class="warning">
                <strong>⚠️ Important:</strong>
                <ul>
                    <li>Make sure you have set up OAuth2 credentials in Google Cloud Console</li>
                    <li>Add <code>' . htmlspecialchars($redirectUri) . '</code> as an authorized redirect URI</li>
                    <li>You only need to do this ONCE</li>
                </ul>
            </div>
            
            <h3>Steps:</h3>
            <ol>
                <li>Click the button below</li>
                <li>Sign in with your Gmail account</li>
                <li>Grant permissions</li>
                <li>Copy the refresh token and add it to your config</li>
            </ol>
            
            <p><a href="' . htmlspecialchars($authUrl) . '" class="btn">Authorize with Google</a></p>
        </div>
    </body>
    </html>';
    exit;
}

// Step 2: Exchange authorization code for refresh token
session_start();

// Validate state
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    die('Error: Invalid state. Please try again.');
}

try {
    // Get access token
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
    
    // Get refresh token
    $refreshToken = $token->getRefreshToken();
    
    if (empty($refreshToken)) {
        die('Error: No refresh token received. Make sure you revoked previous access and try again with prompt=consent.');
    }
    
    // Get user email
    $resourceOwner = $provider->getResourceOwner($token);
    $email = $resourceOwner->getEmail();
    
    echo '<html>
    <head>
        <title>OAuth2 Setup Complete</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .container { background: #f0fdf4; border: 2px solid #22c55e; border-radius: 8px; padding: 30px; }
            h1 { color: #166534; }
            .success { background: #dcfce7; border-left: 4px solid #22c55e; padding: 12px; margin: 20px 0; }
            .code-block { background: #1f2937; color: #f9fafb; padding: 20px; border-radius: 6px; overflow-x: auto; margin: 20px 0; font-family: monospace; font-size: 14px; }
            .token { color: #fbbf24; word-break: break-all; }
            .copy-btn { background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
            .copy-btn:hover { background: #1d4ed8; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ Success!</h1>
            
            <div class="success">
                <strong>OAuth2 authorization successful for:</strong> ' . htmlspecialchars($email) . '
            </div>
            
            <h3>Your Refresh Token:</h3>
            <div class="code-block">
                <div class="token" id="refreshToken">' . htmlspecialchars($refreshToken) . '</div>
                <button class="copy-btn" onclick="copyToken()">Copy Token</button>
            </div>
            
            <h3>Next Steps:</h3>
            <ol>
                <li><strong>Add to your config:</strong>
                    <div class="code-block">
Config::set(\'GMAIL_REFRESH_TOKEN\', \'' . htmlspecialchars($refreshToken) . '\');
Config::set(\'GMAIL_USER_EMAIL\', \'' . htmlspecialchars($email) . '\');
                    </div>
                </li>
                <li><strong>Or add to php/config.php:</strong>
                    <div class="code-block">
\'GMAIL_REFRESH_TOKEN\' => \'' . htmlspecialchars($refreshToken) . '\',
\'GMAIL_USER_EMAIL\' => \'' . htmlspecialchars($email) . '\',
                    </div>
                </li>
                <li><strong>Delete this file (oauth2-setup.php)</strong> for security</li>
                <li>Test sending emails!</li>
            </ol>
        </div>
        
        <script>
        function copyToken() {
            const token = document.getElementById("refreshToken").textContent;
            navigator.clipboard.writeText(token).then(() => {
                alert("Refresh token copied to clipboard!");
            });
        }
        </script>
    </body>
    </html>';
    
} catch (Exception $e) {
    echo '<html>
    <body style="font-family: Arial; padding: 50px;">
        <div style="background: #fee; border: 2px solid #f00; padding: 20px; border-radius: 8px;">
            <h2 style="color: #c00;">❌ Error</h2>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p><a href="oauth2-setup.php">Try Again</a></p>
        </div>
    </body>
    </html>';
}
?>
