<?php
/**
 * Google OAuth Authorization Callback Handler
 * Fixed to work independently and allow new user registration
 */

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/dbConnection.php';
require __DIR__ . "/../vendor/autoload.php";

// Check if config is available
$appDebug = false;
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $appDebug = Config::get('APP_DEBUG', false);
}

// Enable error reporting in debug mode
if ($appDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/**
 * Local function to determine redirect URI (same logic as in login-api.php)
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
    
    // Default fallback
    return "$scheme://$host$basePath/php/authorized.php";
}

/**
 * Get base path for redirects
 */
function getBasePath() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    if (strpos($requestUri, '/al-ghaya/') !== false || strpos($scriptName, '/al-ghaya/') !== false) {
        return '/al-ghaya';
    }
    
    return '';
}

/**
 * Create new user account from Google OAuth data
 */
function createGoogleUser($conn, $googleEmail, $googleGivenName, $googleFamilyName, $googleName) {
    // Default values for new Google users
    $defaultRole = 'student'; // New users default to student role
    $defaultPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT); // Random password (OAuth users won't use it)
    $isActive = 1;
    $level = 1;
    $points = 0;
    $proficiency = 'beginner';
    
    // Clean up names
    $firstName = !empty($googleGivenName) ? $googleGivenName : explode(' ', $googleName)[0] ?? 'User';
    $lastName = !empty($googleFamilyName) ? $googleFamilyName : (explode(' ', $googleName)[1] ?? '');
    
    // Insert new user
    $insertQuery = $conn->prepare("
        INSERT INTO user (fname, lname, email, password, role, isActive, level, points, proficiency, dateCreated) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $insertQuery->bind_param(
        "sssssiiss", 
        $firstName, 
        $lastName, 
        $googleEmail, 
        $defaultPassword, 
        $defaultRole, 
        $isActive, 
        $level, 
        $points, 
        $proficiency
    );
    
    if ($insertQuery->execute()) {
        $newUserID = $conn->insert_id;
        
        // Log the successful registration
        error_log("New Google OAuth user registered: " . $googleEmail . " (ID: $newUserID)");
        
        return [
            'userID' => $newUserID,
            'fname' => $firstName,
            'lname' => $lastName,
            'role' => $defaultRole,
            'isActive' => $isActive,
            'level' => $level,
            'points' => $points
        ];
    }
    
    return false;
}

try {
    // Debug: Log the callback details
    if ($appDebug) {
        error_log("=== OAuth Callback Debug ===");
        error_log("Full URL: " . $_SERVER['REQUEST_URI']);
        error_log("Host: " . $_SERVER['HTTP_HOST']);
        error_log("GET parameters: " . json_encode($_GET));
        error_log("Expected redirect URI: " . determineRedirectUri());
    }
    
    // Get callback parameters
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $error = $_GET['error'] ?? '';
    $errorDescription = $_GET['error_description'] ?? '';
    
    $basePath = getBasePath();
    
    // Check for OAuth errors
    if (!empty($error)) {
        error_log("OAuth Error: $error - $errorDescription");
        
        if ($error === 'access_denied') {
            header("Location: {$basePath}/pages/login.php?error=oauth_cancelled");
        } else {
            header("Location: {$basePath}/pages/login.php?error=oauth_error");
        }
        exit();
    }
    
    // Validate required parameters
    if (empty($code)) {
        error_log("OAuth callback missing authorization code");
        header("Location: {$basePath}/pages/login.php?error=invalid_oauth_response");
        exit();
    }
    
    // Initialize Google Client
    $googleClientId = '704460822405-0gjtdkl1acustankf6k9p3o3444lpb7g.apps.googleusercontent.com';
    $googleClientSecret = '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';
    
    // Use config values if available
    if (file_exists(__DIR__ . '/config.php')) {
        $googleClientId = Config::get('GOOGLE_CLIENT_ID', $googleClientId);
        $googleClientSecret = Config::get('GOOGLE_CLIENT_SECRET', $googleClientSecret);
    }
    
    $client = new Google\Client();
    $client->setClientId($googleClientId);
    $client->setClientSecret($googleClientSecret);
    $client->setRedirectUri(determineRedirectUri());
    $client->addScope("email");
    $client->addScope("profile");
    
    // Validate CSRF state (optional for now to avoid blocking)
    if (!empty($state) && isset($_SESSION['oauth_state'])) {
        if (!hash_equals($_SESSION['oauth_state'], $state)) {
            error_log("OAuth CSRF state validation failed");
            // Log but don't block in debug mode
            if (!$appDebug) {
                header("Location: {$basePath}/pages/login.php?error=csrf_validation_failed");
                exit();
            }
        }
    }
    
    // Exchange authorization code for access token
    try {
        $client->authenticate($code);
        $accessToken = $client->getAccessToken();
        
        if (!$accessToken) {
            throw new Exception("Failed to get access token");
        }
        
        if ($appDebug) {
            error_log("Access token obtained successfully");
        }
        
    } catch (Exception $e) {
        error_log("Token exchange failed: " . $e->getMessage());
        header("Location: {$basePath}/pages/login.php?error=token_exchange_failed");
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
        
        if ($appDebug) {
            error_log("Google user info retrieved: " . $googleEmail);
        }
        
    } catch (Exception $e) {
        error_log("Failed to get user info from Google: " . $e->getMessage());
        header("Location: {$basePath}/pages/login.php?error=user_info_failed");
        exit();
    }
    
    // Check if user exists in database
    $userQuery = $conn->prepare("SELECT userID, fname, lname, role, isActive, level, points FROM user WHERE email = ?");
    $userQuery->bind_param("s", $googleEmail);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    
    if ($userResult->num_rows > 0) {
        // Existing user - proceed with login
        $userData = $userResult->fetch_assoc();
        
        // Check if account is active
        if (!$userData['isActive']) {
            header("Location: {$basePath}/pages/login.php?error=account_deactivated");
            exit();
        }
        
        // Create session
        $_SESSION['userID'] = $userData['userID'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['email'] = $googleEmail;
        $_SESSION['user_name'] = $userData['fname'] . ' ' . $userData['lname'];
        $_SESSION['user_fname'] = $userData['fname'];
        $_SESSION['user_lname'] = $userData['lname'];
        $_SESSION['user_level'] = $userData['level'] ?? 1;
        $_SESSION['user_points'] = $userData['points'] ?? 0;
        $_SESSION['oauth_login'] = true;
        $_SESSION['last_activity'] = time();
        
        // Clean up OAuth session data
        unset($_SESSION['oauth_state']);
        unset($_SESSION['oauth_timestamp']);
        
        // Update user's last login time
        $updateLogin = $conn->prepare("UPDATE user SET lastLogin = NOW() WHERE userID = ?");
        $updateLogin->bind_param("i", $userData['userID']);
        $updateLogin->execute();
        
        error_log("OAuth login successful for existing user: " . $googleEmail);
        
        // Redirect to appropriate dashboard
        $role = $userData['role'];
        $redirectUrl = "{$basePath}/pages/{$role}/{$role}-dashboard.php";
        
        if ($appDebug) {
            error_log("Redirecting to: " . $redirectUrl);
        }
        
        header("Location: $redirectUrl");
        exit();
        
    } else {
        // User not found in database - create new account
        error_log("Google account not found in database, creating new user: " . $googleEmail);
        
        $newUserData = createGoogleUser($conn, $googleEmail, $googleGivenName, $googleFamilyName, $googleName);
        
        if ($newUserData) {
            // Create session for new user
            $_SESSION['userID'] = $newUserData['userID'];
            $_SESSION['role'] = $newUserData['role'];
            $_SESSION['email'] = $googleEmail;
            $_SESSION['user_name'] = $newUserData['fname'] . ' ' . $newUserData['lname'];
            $_SESSION['user_fname'] = $newUserData['fname'];
            $_SESSION['user_lname'] = $newUserData['lname'];
            $_SESSION['user_level'] = $newUserData['level'];
            $_SESSION['user_points'] = $newUserData['points'];
            $_SESSION['oauth_login'] = true;
            $_SESSION['new_oauth_user'] = true; // Flag for welcome message
            $_SESSION['last_activity'] = time();
            
            // Clean up OAuth session data
            unset($_SESSION['oauth_state']);
            unset($_SESSION['oauth_timestamp']);
            
            error_log("New OAuth user account created and logged in: " . $googleEmail);
            
            // Redirect to student dashboard with welcome message
            $role = $newUserData['role'];
            $redirectUrl = "{$basePath}/pages/{$role}/{$role}-dashboard.php?welcome=new_oauth_user";
            
            if ($appDebug) {
                error_log("Redirecting new user to: " . $redirectUrl);
            }
            
            header("Location: $redirectUrl");
            exit();
            
        } else {
            error_log("Failed to create new user account for: " . $googleEmail);
            header("Location: {$basePath}/pages/login.php?error=account_creation_failed");
            exit();
        }
    }
    
} catch (Exception $e) {
    error_log("OAuth callback fatal error: " . $e->getMessage());
    
    $basePath = getBasePath();
    
    if ($appDebug) {
        // Show detailed error in debug mode
        die("OAuth Error: " . $e->getMessage() . "<br><br><a href='{$basePath}/pages/login.php'>← Back to Login</a>");
    } else {
        header("Location: {$basePath}/pages/login.php?error=oauth_processing_error");
    }
    exit();
}
?>