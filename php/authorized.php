<?php
/**
 * Google OAuth Authorization Callback Handler
 * Standardized to APP_URL and secured
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/dbConnection.php';
require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . '/config.php';

$appDebug = Config::get('APP_DEBUG', false);

if ($appDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

function buildRedirectUri() {
    $appUrl = rtrim(Config::get('APP_URL', ''), '/');
    if (empty($appUrl)) {
        // Fallback to dynamic building only if APP_URL not set
        $host = $_SERVER['HTTP_HOST'];
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $basePath = (strpos($_SERVER['SCRIPT_NAME'], '/al-ghaya/') !== false) ? '/al-ghaya' : '';
        return "$scheme://$host$basePath/php/authorized.php";
    }
    return $appUrl . '/php/authorized.php';
}

function getBasePath() {
    $appUrl = Config::get('APP_URL', '');
    if (!empty($appUrl)) {
        $parts = parse_url($appUrl);
        return isset($parts['path']) ? rtrim($parts['path'], '/') : '';
    }
    return (strpos($_SERVER['SCRIPT_NAME'], '/al-ghaya/') !== false) ? '/al-ghaya' : '';
}

try {
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $error = $_GET['error'] ?? '';
    $errorDescription = $_GET['error_description'] ?? '';

    $basePath = getBasePath();

    if (!empty($error)) {
        error_log("OAuth Error: $error - $errorDescription");
        header("Location: {$basePath}/pages/login.php?error=oauth_error");
        exit();
    }

    if (empty($code)) {
        error_log("OAuth callback missing authorization code");
        header("Location: {$basePath}/pages/login.php?error=invalid_oauth_response");
        exit();
    }

    // Require client credentials from .env
    $googleClientId = Config::get('GOOGLE_CLIENT_ID');
    $googleClientSecret = Config::get('GOOGLE_CLIENT_SECRET');
    if (empty($googleClientId) || empty($googleClientSecret)) {
        throw new Exception('Missing GOOGLE_CLIENT_ID or GOOGLE_CLIENT_SECRET');
    }

    $client = new Google\Client();
    $client->setClientId($googleClientId);
    $client->setClientSecret($googleClientSecret);
    $client->setRedirectUri(buildRedirectUri());
    $client->addScope("email");
    $client->addScope("profile");

    // Optional CSRF validation
    if (!empty($state) && isset($_SESSION['oauth_state'])) {
        if (!hash_equals($_SESSION['oauth_state'], $state)) {
            error_log('OAuth CSRF state validation failed');
            header("Location: {$basePath}/pages/login.php?error=csrf_validation_failed");
            exit();
        }
    }

    $client->authenticate($code);
    $accessToken = $client->getAccessToken();
    if (!$accessToken) {
        throw new Exception('Failed to obtain access token');
    }

    $client->setAccessToken($accessToken);
    $service = new Google\Service\Oauth2($client);
    $userProfile = $service->userinfo->get();

    $googleEmail = $userProfile->email;
    $googleName = $userProfile->name ?? '';
    $googleGivenName = $userProfile->givenName ?? '';
    $googleFamilyName = $userProfile->familyName ?? '';

    // Look up user
    $userQuery = $conn->prepare("SELECT userID, fname, lname, role, isActive, level, points FROM user WHERE email = ?");
    $userQuery->bind_param("s", $googleEmail);
    $userQuery->execute();
    $userResult = $userQuery->get_result();

    if ($userResult->num_rows > 0) {
        $userData = $userResult->fetch_assoc();
        if (!$userData['isActive']) {
            header("Location: {$basePath}/pages/login.php?error=account_deactivated");
            exit();
        }
        $_SESSION['userID'] = $userData['userID'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['email'] = $googleEmail;
        $_SESSION['user_name'] = trim(($userData['fname'] ?? '') . ' ' . ($userData['lname'] ?? ''));
        $_SESSION['user_fname'] = $userData['fname'] ?? '';
        $_SESSION['user_lname'] = $userData['lname'] ?? '';
        $_SESSION['user_level'] = $userData['level'] ?? 1;
        $_SESSION['user_points'] = $userData['points'] ?? 0;
        $_SESSION['oauth_login'] = true;
        $_SESSION['last_activity'] = time();

        $updateLogin = $conn->prepare("UPDATE user SET lastLogin = NOW() WHERE userID = ?");
        $updateLogin->bind_param("i", $userData['userID']);
        $updateLogin->execute();

        header("Location: {$basePath}/pages/{$userData['role']}/{$userData['role']}-dashboard.php");
        exit();
    }

    // Create new user
    $firstName = !empty($googleGivenName) ? $googleGivenName : (explode(' ', $googleName)[0] ?? 'User');
    $lastName = !empty($googleFamilyName) ? $googleFamilyName : (explode(' ', $googleName)[1] ?? '');
    $defaultPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

    $insertQuery = $conn->prepare("INSERT INTO user (fname, lname, email, password, role, isActive, level, points, proficiency, dateCreated) VALUES (?, ?, ?, ?, 'student', 1, 1, 0, 'beginner', NOW())");
    $insertQuery->bind_param("ssss", $firstName, $lastName, $googleEmail, $defaultPassword);

    if ($insertQuery->execute()) {
        $newUserID = $conn->insert_id;
        $_SESSION['userID'] = $newUserID;
        $_SESSION['role'] = 'student';
        $_SESSION['email'] = $googleEmail;
        $_SESSION['user_name'] = trim($firstName . ' ' . $lastName);
        $_SESSION['user_fname'] = $firstName;
        $_SESSION['user_lname'] = $lastName;
        $_SESSION['user_level'] = 1;
        $_SESSION['user_points'] = 0;
        $_SESSION['oauth_login'] = true;
        $_SESSION['new_oauth_user'] = true;
        $_SESSION['last_activity'] = time();

        header("Location: {$basePath}/pages/student/student-dashboard.php?welcome=new_oauth_user");
        exit();
    }

    header("Location: {$basePath}/pages/login.php?error=account_creation_failed");
    exit();

} catch (Exception $e) {
    error_log('OAuth callback error: ' . $e->getMessage());
    $basePath = getBasePath();
    if ($appDebug) {
        die('OAuth Error: ' . htmlspecialchars($e->getMessage()) . "<br><a href='{$basePath}/pages/login.php'>← Back to Login</a>");
    }
    header("Location: {$basePath}/pages/login.php?error=oauth_processing_error");
    exit();
}
