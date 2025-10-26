<?php
/**
 * Al-Ghaya Google OAuth API Configuration
 * Standardized redirect using APP_URL
 */

require_once __DIR__ . '/config.php';
require __DIR__ . "/../vendor/autoload.php";

$googleClientId = Config::get('GOOGLE_CLIENT_ID');
$googleClientSecret = Config::get('GOOGLE_CLIENT_SECRET');
$appDebug = Config::get('APP_DEBUG', false);

if (empty($googleClientId) || empty($googleClientSecret)) {
    throw new Exception('Missing GOOGLE_CLIENT_ID or GOOGLE_CLIENT_SECRET');
}

$client = new Google\Client();
$client->setClientId($googleClientId);
$client->setClientSecret($googleClientSecret);

function buildRedirectUri() {
    $appUrl = rtrim(Config::get('APP_URL', ''), '/');
    if (empty($appUrl)) {
        $host = $_SERVER['HTTP_HOST'];
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $basePath = (strpos($_SERVER['SCRIPT_NAME'], '/al-ghaya/') !== false) ? '/al-ghaya' : '';
        return "$scheme://$host$basePath/php/authorized.php";
    }
    return $appUrl . '/php/authorized.php';
}

$redirectUri = buildRedirectUri();
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");
$client->setAccessType('offline');
$client->setPrompt('select_account');

function getSecureAuthUrl() {
    global $client;
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $state = bin2hex(random_bytes(32));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_timestamp'] = time();
    $client->setState($state);
    return $client->createAuthUrl();
}

function validateOAuthState($receivedState) {
    if (!isset($_SESSION['oauth_state'])) return false;
    if (!hash_equals($_SESSION['oauth_state'], $receivedState)) return false;
    if (!isset($_SESSION['oauth_timestamp']) || (time() - $_SESSION['oauth_timestamp']) > 600) return false;
    return true;
}

function cleanupOAuthSession() {
    unset($_SESSION['oauth_state']);
    unset($_SESSION['oauth_timestamp']);
}

if ($appDebug) {
    error_log("=== OAuth Debug Info ===");
    error_log("Redirect URI: " . $redirectUri);
}
?>