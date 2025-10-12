<?php
/**
 * Al-Ghaya Google OAuth API Configuration
 * Secured with environment variables
 */

require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . '/config.php';

// Validate required OAuth configuration
try {
    Config::validateRequired([
        'GOOGLE_CLIENT_ID',
        'GOOGLE_CLIENT_SECRET'
    ]);
} catch (Exception $e) {
    error_log("OAuth Configuration Error: " . $e->getMessage());
    throw new Exception("Google OAuth is not properly configured. Please check your environment settings.");
}

// Initialize Google Client with secured credentials
$client = new Google\Client();
$client->setClientId(Config::get('GOOGLE_CLIENT_ID'));
$client->setClientSecret(Config::get('GOOGLE_CLIENT_SECRET'));

// Set redirect URI based on environment
$redirectUri = getOAuthRedirectUri();
$client->setRedirectUri($redirectUri);

// Add required scopes
$client->addScope("email");
$client->addScope("profile");

// Optional: Set additional OAuth parameters
$client->setAccessType('offline');
$client->setPrompt('select_account');

/**
 * Generate appropriate OAuth redirect URI based on environment
 * @return string Redirect URI
 */
function getOAuthRedirectUri() {
    $host = $_SERVER['HTTP_HOST'];
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    
    // Development environments
    if ($host === 'localhost:8000' || $host === 'localhost:8080' || $host === '127.0.0.1:8000') {
        return "http://$host/php/authorized.php";
    }
    
    // GitHub Codespaces
    if (strpos($host, 'github.dev') !== false || strpos($host, 'githubpreview.dev') !== false) {
        return "https://$host/php/authorized.php";
    }
    
    // Vercel deployment
    if (strpos($host, 'vercel.app') !== false) {
        return "https://$host/php/authorized.php";
    }
    
    // Netlify deployment
    if (strpos($host, 'netlify.app') !== false) {
        return "https://$host/php/authorized.php";
    }
    
    // Custom domain or production
    $appUrl = Config::get('APP_URL', "https://$host");
    return rtrim($appUrl, '/') . '/php/authorized.php';
}

/**
 * Get OAuth authorization URL with state parameter for CSRF protection
 * @return string Authorization URL
 */
function getSecureAuthUrl() {
    global $client;
    
    // Generate state parameter for CSRF protection
    $state = bin2hex(random_bytes(32));
    $_SESSION['oauth_state'] = $state;
    
    $client->setState($state);
    return $client->createAuthUrl();
}

/**
 * Validate OAuth state parameter to prevent CSRF attacks
 * @param string $receivedState State parameter from OAuth callback
 * @return bool True if state is valid
 */
function validateOAuthState($receivedState) {
    return isset($_SESSION['oauth_state']) && 
           hash_equals($_SESSION['oauth_state'], $receivedState);
}

/**
 * Clean up OAuth session data after use
 */
function cleanupOAuthSession() {
    unset($_SESSION['oauth_state']);
}

// Error handling for OAuth configuration
if (Config::get('APP_DEBUG', false)) {
    // In debug mode, log configuration status
    error_log("Google OAuth Configuration Loaded:");
    error_log("Client ID: " . (Config::has('GOOGLE_CLIENT_ID') ? 'Set' : 'Missing'));
    error_log("Client Secret: " . (Config::has('GOOGLE_CLIENT_SECRET') ? 'Set' : 'Missing'));
    error_log("Redirect URI: " . $redirectUri);
}
?>