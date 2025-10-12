<?php
/**
 * Google OAuth Authorization Callback Handler
 * Secured implementation with proper session management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session-manager.php';
require_once __DIR__ . '/login-api.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Validate OAuth callback
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $error = $_GET['error'] ?? '';
    
    // Check for OAuth errors
    if (!empty($error)) {
        error_log("OAuth Error: $error");
        header("Location: ../pages/login.php?error=oauth_error");
        exit();
    }
    
    // Validate required parameters
    if (empty($code) || empty($state)) {
        header("Location: ../pages/login.php?error=invalid_oauth_response");
        exit();
    }
    
    // Validate CSRF state
    if (!validateOAuthCallback($state)) {
        error_log("OAuth CSRF validation failed");
        header("Location: ../pages/login.php?error=csrf_validation_failed");
        exit();
    }
    
    // Exchange authorization code for access token
    $client->authenticate($code);
    $accessToken = $client->getAccessToken();
    
    if (!$accessToken) {
        header("Location: ../pages/login.php?error=token_exchange_failed");
        exit();
    }
    
    // Get user information from Google
    $client->setAccessToken($accessToken);
    $service = new Google\Service\Oauth2($client);
    $userProfile = $service->userinfo->get();
    
    $googleEmail = $userProfile->email;
    $googleName = $userProfile->name;
    $googleGivenName = $userProfile->givenName ?? '';
    $googleFamilyName = $userProfile->familyName ?? '';
    $googlePicture = $userProfile->picture ?? '';
    
    // Check if user exists in database
    $userQuery = $conn->prepare("SELECT userID, fname, lname, role, isActive FROM user WHERE email = ?");
    $userQuery->bind_param("s", $googleEmail);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    
    if ($userResult->num_rows > 0) {
        $userData = $userResult->fetch_assoc();
        
        // Check if account is active
        if (!$userData['isActive']) {
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
        $_SESSION['oauth_login'] = true;
        $_SESSION['last_activity'] = time();
        
        // Clean up OAuth session data
        cleanupOAuthData();
        
        // Update user's last login time
        $updateLogin = $conn->prepare("UPDATE user SET lastLogin = NOW() WHERE userID = ?");
        $updateLogin->bind_param("i", $userData['userID']);
        $updateLogin->execute();
        
        // Set success message
        setSessionMessage('success', 'Welcome Back!', 'You have been successfully logged in with Google.');
        
        // Redirect to appropriate dashboard
        $role = $userData['role'];
        header("Location: ../pages/{$role}/{$role}-dashboard.php");
        exit();
        
    } else {
        // User not found - redirect to registration or show error
        header("Location: ../pages/login.php?error=google_account_not_registered&email=" . urlencode($googleEmail));
        exit();
    }
    
} catch (Exception $e) {
    error_log("OAuth callback error: " . $e->getMessage());
    header("Location: ../pages/login.php?error=oauth_processing_error");
    exit();
}
?>