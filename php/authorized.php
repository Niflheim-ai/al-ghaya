<?php
/**
 * Google OAuth Authorization Callback Handler
 * Fixed redirect URI handling
 */

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/login-api.php';

// Enable error reporting in debug mode
if (Config::get('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    // Debug: Log the callback URL and parameters
    if (Config::get('APP_DEBUG', false)) {
        error_log("=== OAuth Callback Debug ===");
        error_log("Full URL: " . $_SERVER['REQUEST_URI']);
        error_log("Host: " . $_SERVER['HTTP_HOST']);
        error_log("GET parameters: " . json_encode($_GET));
        error_log("Expected redirect URI: " . getOAuthRedirectUri());
    }
    
    // Get callback parameters
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $error = $_GET['error'] ?? '';
    $errorDescription = $_GET['error_description'] ?? '';
    
    // Check for OAuth errors
    if (!empty($error)) {
        error_log("OAuth Error: $error - $errorDescription");
        
        // Handle specific error types
        if ($error === 'access_denied') {
            header("Location: ../pages/login.php?error=oauth_cancelled");
        } else {
            header("Location: ../pages/login.php?error=oauth_error&details=" . urlencode($errorDescription));
        }
        exit();
    }
    
    // Validate required parameters
    if (empty($code)) {
        error_log("OAuth callback missing authorization code");
        header("Location: ../pages/login.php?error=invalid_oauth_response");
        exit();
    }
    
    if (empty($state)) {
        error_log("OAuth callback missing state parameter");
        header("Location: ../pages/login.php?error=invalid_oauth_response");
        exit();
    }
    
    // Validate CSRF state
    if (!validateOAuthState($state)) {
        error_log("OAuth CSRF state validation failed");
        error_log("Expected state: " . ($_SESSION['oauth_state'] ?? 'not set'));
        error_log("Received state: " . $state);
        header("Location: ../pages/login.php?error=csrf_validation_failed");
        exit();
    }
    
    // Exchange authorization code for access token
    try {
        $client->authenticate($code);
        $accessToken = $client->getAccessToken();
        
        if (!$accessToken) {
            throw new Exception("Failed to get access token");
        }
        
        if (Config::get('APP_DEBUG', false)) {
            error_log("Access token obtained successfully");
        }
        
    } catch (Exception $e) {
        error_log("Token exchange failed: " . $e->getMessage());
        header("Location: ../pages/login.php?error=token_exchange_failed");
        exit();
    }
    
    // Get user information from Google
    try {
        $client->setAccessToken($accessToken);
        $service = new Google\Service\Oauth2($client);
        $userProfile = $service->userinfo->get();
        
        $googleEmail = $userProfile->email;
        $googleName = $userProfile->name;
        $googleGivenName = $userProfile->givenName ?? '';
        $googleFamilyName = $userProfile->familyName ?? '';
        $googlePicture = $userProfile->picture ?? '';
        
        if (Config::get('APP_DEBUG', false)) {
            error_log("Google user info retrieved: " . $googleEmail);
        }
        
    } catch (Exception $e) {
        error_log("Failed to get user info from Google: " . $e->getMessage());
        header("Location: ../pages/login.php?error=user_info_failed");
        exit();
    }
    
    // Check if user exists in database
    $userQuery = $conn->prepare("SELECT userID, fname, lname, role, isActive, level, points FROM user WHERE email = ?");
    $userQuery->bind_param("s", $googleEmail);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    
    if ($userResult->num_rows > 0) {
        $userData = $userResult->fetch_assoc();
        
        // Check if account is active
        if (!$userData['isActive']) {
            cleanupOAuthSession();
            header("Location: ../pages/login.php?error=account_deactivated");
            exit();
        }
        
        // Create session
        $_SESSION['userID'] = $userData['userID'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['email'] = $googleEmail;
        $_SESSION['user_name'] = $userData['fname'] . ' ' . $userData['lname'];
        $_SESSION['user_fname'] = $userData['fname'];
        $_SESSION['user_lname'] = $userData['lname'];
        $_SESSION['user_level'] = $userData['level'];
        $_SESSION['user_points'] = $userData['points'];
        $_SESSION['oauth_login'] = true;
        $_SESSION['last_activity'] = time();
        
        // Clean up OAuth session data
        cleanupOAuthSession();
        
        // Update user's last login time
        $updateLogin = $conn->prepare("UPDATE user SET lastLogin = NOW() WHERE userID = ?");
        $updateLogin->bind_param("i", $userData['userID']);
        $updateLogin->execute();
        
        // Log successful login
        error_log("OAuth login successful for user: " . $googleEmail);
        
        // Redirect to appropriate dashboard
        $role = $userData['role'];
        
        // Get base path for redirect
        $basePath = '';
        if (strpos($_SERVER['REQUEST_URI'], '/al-ghaya/') !== false) {
            $basePath = '/al-ghaya';
        }
        
        $redirectUrl = $basePath . "/pages/{$role}/{$role}-dashboard.php";
        
        if (Config::get('APP_DEBUG', false)) {
            error_log("Redirecting to: " . $redirectUrl);
        }
        
        header("Location: $redirectUrl");
        exit();
        
    } else {
        // User not found in database
        cleanupOAuthSession();
        
        error_log("Google account not found in database: " . $googleEmail);
        
        // Get base path for redirect
        $basePath = '';
        if (strpos($_SERVER['REQUEST_URI'], '/al-ghaya/') !== false) {
            $basePath = '/al-ghaya';
        }
        
        header("Location: {$basePath}/pages/login.php?error=google_account_not_registered&email=" . urlencode($googleEmail));
        exit();
    }
    
} catch (Exception $e) {
    error_log("OAuth callback fatal error: " . $e->getMessage());
    cleanupOAuthSession();
    
    // Get base path for redirect
    $basePath = '';
    if (strpos($_SERVER['REQUEST_URI'], '/al-ghaya/') !== false) {
        $basePath = '/al-ghaya';
    }
    
    if (Config::get('APP_DEBUG', false)) {
        header("Location: {$basePath}/pages/login.php?error=oauth_processing_error&details=" . urlencode($e->getMessage()));
    } else {
        header("Location: {$basePath}/pages/login.php?error=oauth_processing_error");
    }
    exit();
}
?>