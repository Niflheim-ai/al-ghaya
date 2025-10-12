<?php
/**
 * Al-Ghaya Google OAuth API Configuration
 * Fixed redirect URI mismatch issue
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
 * Fixed to match your Google OAuth console configuration
 * @return string Redirect URI
 */
function getOAuthRedirectUri() {
    $host = $_SERVER['HTTP_HOST'];
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    
    // Get the base path of your application
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // Extract the base directory path
    $basePath = '';
    if (strpos($requestUri, '/al-ghaya/') !== false) {
        $basePath = '/al-ghaya';
    } elseif (strpos($scriptName, '/al-ghaya/') !== false) {
        $basePath = '/al-ghaya';
    }
    
    // Development environments
    if ($host === 'localhost:8080' || $host === 'localhost:8000' || $host === '127.0.0.1:8080' || $host === '127.0.0.1:8000') {
        return "http://$host$basePath/php/authorized.php";
    }
    
    // LocalTunnel or similar tunneling services
    if (strpos($host, '.loca.lt') !== false) {
        return "https://$host$basePath/php/authorized.php";
    }
    
    // Ngrok tunneling
    if (strpos($host, '.ngrok.') !== false) {
        return "https://$host$basePath/php/authorized.php";
    }
    
    // GitHub Codespaces
    if (strpos($host, 'github.dev') !== false || strpos($host, 'githubpreview.dev') !== false) {
        return "https://$host$basePath/php/authorized.php";
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
    $appUrl = Config::get('APP_URL');
    if ($appUrl) {
        return rtrim($appUrl, '/') . '/php/authorized.php';
    }
    
    // Fallback
    return "$scheme://$host$basePath/php/authorized.php";
}

/**
 * Get current base URL for debugging
 * @return string Current base URL
 */
function getCurrentBaseUrl() {
    $host = $_SERVER['HTTP_HOST'];
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $requestUri = $_SERVER['REQUEST_URI'];
    
    $basePath = '';
    if (strpos($requestUri, '/al-ghaya/') !== false) {
        $basePath = '/al-ghaya';
    }
    
    return "$scheme://$host$basePath";
}

/**
 * Get OAuth authorization URL with state parameter for CSRF protection
 * @return string Authorization URL
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
 * Validate OAuth state parameter to prevent CSRF attacks
 * @param string $receivedState State parameter from OAuth callback
 * @return bool True if state is valid
 */
function validateOAuthState($receivedState) {
    if (!isset($_SESSION['oauth_state'])) {
        return false;
    }
    
    // Check state match
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
 * Clean up OAuth session data after use
 */
function cleanupOAuthSession() {
    unset($_SESSION['oauth_state']);
    unset($_SESSION['oauth_timestamp']);
}

// Debug information (only in development)
if (Config::get('APP_DEBUG', false)) {
    error_log("=== OAuth Configuration Debug ===");
    error_log("Current Host: " . $_SERVER['HTTP_HOST']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    error_log("Script Name: " . $_SERVER['SCRIPT_NAME']);
    error_log("Generated Redirect URI: " . getOAuthRedirectUri());
    error_log("Current Base URL: " . getCurrentBaseUrl());
    error_log("Client ID: " . (Config::has('GOOGLE_CLIENT_ID') ? 'Set ✓' : 'Missing ✗'));
    error_log("Client Secret: " . (Config::has('GOOGLE_CLIENT_SECRET') ? 'Set ✓' : 'Missing ✗'));
}
?>