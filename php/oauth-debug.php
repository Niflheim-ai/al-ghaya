<?php
/**
 * OAuth Configuration Debug Helper
 * Use this to verify your OAuth setup
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/login-api.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Only allow in debug mode
if (!Config::get('APP_DEBUG', false)) {
    die('Debug mode is disabled');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Debug - Al-Ghaya</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .debug-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        code { background: #e9ecef; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        .url-box { background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 OAuth Configuration Debug</h1>
        
        <div class="debug-section">
            <h2>Environment Information</h2>
            <p><strong>Host:</strong> <code><?= $_SERVER['HTTP_HOST'] ?></code></p>
            <p><strong>Request URI:</strong> <code><?= $_SERVER['REQUEST_URI'] ?></code></p>
            <p><strong>Script Name:</strong> <code><?= $_SERVER['SCRIPT_NAME'] ?></code></p>
            <p><strong>Current Base URL:</strong> <code><?= getCurrentBaseUrl() ?></code></p>
        </div>

        <div class="debug-section">
            <h2>OAuth Configuration</h2>
            <p><strong>Google Client ID:</strong> 
                <span class="<?= Config::has('GOOGLE_CLIENT_ID') ? 'success' : 'error' ?>">
                    <?= Config::has('GOOGLE_CLIENT_ID') ? 'Set ✓' : 'Missing ✗' ?>
                </span>
            </p>
            <p><strong>Google Client Secret:</strong> 
                <span class="<?= Config::has('GOOGLE_CLIENT_SECRET') ? 'success' : 'error' ?>">
                    <?= Config::has('GOOGLE_CLIENT_SECRET') ? 'Set ✓' : 'Missing ✗' ?>
                </span>
            </p>
        </div>

        <div class="debug-section">
            <h2>Generated Redirect URI</h2>
            <div class="url-box">
                <code><?= getOAuthRedirectUri() ?></code>
            </div>
        </div>

        <div class="debug-section">
            <h2>Expected Redirect URIs (from Google Console)</h2>
            <div class="url-box">
                <code>http://localhost:8080/al-ghaya/php/authorized.php</code>
            </div>
            <div class="url-box">
                <code>https://alghaya-2.loca.lt/al-ghaya/php/authorized.php</code>
            </div>
            
            <p class="<?= (getOAuthRedirectUri() === 'http://localhost:8080/al-ghaya/php/authorized.php' || 
                         getOAuthRedirectUri() === 'https://alghaya-2.loca.lt/al-ghaya/php/authorized.php') ? 'success' : 'error' ?>">
                <?= (getOAuthRedirectUri() === 'http://localhost:8080/al-ghaya/php/authorized.php' || 
                     getOAuthRedirectUri() === 'https://alghaya-2.loca.lt/al-ghaya/php/authorized.php') ? '✅ URI matches Google Console configuration' : '❌ URI does not match Google Console configuration' ?>
            </p>
        </div>

        <div class="debug-section">
            <h2>Test OAuth URL</h2>
            <?php
            try {
                $authUrl = getSecureAuthUrl();
                echo '<p><strong>OAuth URL:</strong></p>';
                echo '<div class="url-box"><a href="' . $authUrl . '" target="_blank">Test OAuth Login</a></div>';
                echo '<p class="success">✅ OAuth URL generated successfully</p>';
            } catch (Exception $e) {
                echo '<p class="error">❌ Error generating OAuth URL: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <div class="debug-section">
            <h2>Session Information</h2>
            <p><strong>Session ID:</strong> <code><?= session_id() ?></code></p>
            <p><strong>Session Status:</strong> 
                <span class="<?= session_status() === PHP_SESSION_ACTIVE ? 'success' : 'error' ?>">
                    <?= session_status() === PHP_SESSION_ACTIVE ? 'Active ✓' : 'Inactive ✗' ?>
                </span>
            </p>
            
            <?php if (!empty($_SESSION)): ?>
                <p><strong>Session Data:</strong></p>
                <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;">
<?= htmlspecialchars(print_r($_SESSION, true)) ?>
                </pre>
            <?php else: ?>
                <p class="warning">⚠️ No session data found</p>
            <?php endif; ?>
        </div>

        <div class="debug-section">
            <h2>File Permissions</h2>
            <p><strong>authorized.php:</strong> 
                <span class="<?= file_exists(__DIR__ . '/authorized.php') ? 'success' : 'error' ?>">
                    <?= file_exists(__DIR__ . '/authorized.php') ? 'Exists ✓' : 'Missing ✗' ?>
                </span>
            </p>
            <p><strong>.env file:</strong> 
                <span class="<?= file_exists(__DIR__ . '/../.env') ? 'success' : 'error' ?>">
                    <?= file_exists(__DIR__ . '/../.env') ? 'Exists ✓' : 'Missing ✗' ?>
                </span>
            </p>
        </div>

        <div class="debug-section">
            <h2>Quick Fix Instructions</h2>
            <?php
            $currentUri = getOAuthRedirectUri();
            $expectedUri1 = 'http://localhost:8080/al-ghaya/php/authorized.php';
            $expectedUri2 = 'https://alghaya-2.loca.lt/al-ghaya/php/authorized.php';
            
            if ($currentUri !== $expectedUri1 && $currentUri !== $expectedUri2):
            ?>
                <div class="error">
                    <p><strong>❌ Redirect URI Mismatch Detected!</strong></p>
                    <p>Your generated URI: <code><?= $currentUri ?></code></p>
                    <p>Expected URIs: <code><?= $expectedUri1 ?></code> or <code><?= $expectedUri2 ?></code></p>
                    
                    <h4>To Fix:</h4>
                    <ol>
                        <li>Update your <code>.env</code> file with: <code>APP_URL=http://localhost:8080/al-ghaya</code></li>
                        <li>Or add the current URI to your Google OAuth Console</li>
                        <li>Make sure you're accessing the site via the correct URL</li>
                    </ol>
                </div>
            <?php else: ?>
                <div class="success">
                    <p><strong>✅ Configuration looks correct!</strong></p>
                    <p>You can now try the OAuth login.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px;">
            <p><strong>⚠️ Security Notice:</strong> This debug page should only be used in development. Make sure to set <code>APP_DEBUG=false</code> in production.</p>
        </div>
    </div>
</body>
</html>
