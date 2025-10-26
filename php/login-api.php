<?php
/**
 * Al-Ghaya Google OAuth API Configuration
 * Simplified version to avoid undefined function errors
 */

require_once __DIR__ . '/config.php';
require __DIR__ . "/../vendor/autoload.php";

// Check if config is available, otherwise use fallback
try {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        
        // Use environment variables if available
        $googleClientId = Config::get('GOOGLE_CLIENT_ID');
        $googleClientSecret = Config::get('GOOGLE_CLIENT_SECRET');
        $appDebug = Config::get('APP_DEBUG', false);
    }
} catch(Exception $e) {
    error_log("Login API Configuration Error: " . $e->getMessage());
    throw new Exception("Login system configuration failed: " . $e->getMessage());
}

// Initialize Google Client
$client = new Google\Client();
$client->setClientId($googleClientId);
$client->setClientSecret($googleClientSecret);

// Set redirect URI based on environment
$redirectUri = determineRedirectUri();
$client->setRedirectUri($redirectUri);

// Add required scopes
$client->addScope("email");
$client->addScope("profile");
$client->setAccessType('offline');
$client->setPrompt('select_account');

/**
 * Determine appropriate OAuth redirect URI
 * @return string Redirect URI
 */
function determineRedirectUri() {
    $host = $_SERVER['HTTP_HOST'];
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    
    // Get the application base path
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $basePath = '';
    
    // Extract base path from script name
    if (strpos($scriptPath, '/al-ghaya/') !== false) {
        $basePath = '/al-ghaya';
    }
    
    // Development environments
    if ($host === 'localhost:8080') {
        return "http://$host$basePath/php/authorized.php";
    }
    
    // LocalTunnel
    if (strpos($host, '.loca.lt') !== false) {
        return "https://$host$basePath/php/authorized.php";
    }
    
    // Ngrok
    if (strpos($host, '.ngrok.') !== false) {
        return "https://$host$basePath/php/authorized.php";
    }
    
    // GitHub Codespaces
    if (strpos($host, 'github.dev') !== false) {
        return "https://$host$basePath/php/authorized.php";
    }
    
    // Vercel
    if (strpos($host, 'vercel.app') !== false) {
        return "https://$host/php/authorized.php";
    }
    
    // Default fallback
    return "$scheme://$host$basePath/php/authorized.php";
}

/**
 * Generate secure OAuth URL with CSRF protection
 * @return string OAuth authorization URL
 */
function getSecureAuthUrl() {
    global $client;
    
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate state parameter for CSRF protection
    $state = bin2hex(random_bytes(32));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_timestamp'] = time();
    
    $client->setState($state);
    return $client->createAuthUrl();
}

/**
 * Validate OAuth state parameter
 * @param string $receivedState State from callback
 * @return bool True if valid
 */
function validateOAuthState($receivedState) {
    if (!isset($_SESSION['oauth_state'])) {
        return false;
    }
    
    if (!hash_equals($_SESSION['oauth_state'], $receivedState)) {
        return false;
    }
    
    // Check timestamp (max 10 minutes)
    if (!isset($_SESSION['oauth_timestamp']) || (time() - $_SESSION['oauth_timestamp']) > 600) {
        return false;
    }
    
    return true;
}

/**
 * Clean up OAuth session data
 */
function cleanupOAuthSession() {
    unset($_SESSION['oauth_state']);
    unset($_SESSION['oauth_timestamp']);
}

// Debug logging
if ($appDebug) {
    error_log("=== OAuth Debug Info ===");
    error_log("Host: " . $_SERVER['HTTP_HOST']);
    error_log("Script: " . $_SERVER['SCRIPT_NAME']);
    error_log("Generated Redirect URI: " . $redirectUri);
}
?>
