<?php
session_start();
require '../php/login-api.php';
require '../php/dbConnection.php'; // Add this line to include database connection

if (!isset($_GET["code"])) {
    header("Location: 404.php");
    exit();
}

// Exchange code for token
$token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);
if (!isset($token['access_token'])) {
    header("Location: login.php?error=auth_failed");
    exit();
}

$client->setAccessToken($token['access_token']);
// Get user info
$oauth = new Google\Service\Oauth2($client);
$userInfo = $oauth->userinfo->get();

// Check if user exists in any table
$email = $userInfo->email;
$tables = ['student', 'teacher', 'admin'];
$userFound = false;
$role = '';

foreach ($tables as $table) {
    $stmt = $conn->prepare("SELECT {$table}ID FROM $table WHERE email = ?");
    if (!$stmt) {
        // Log error and continue to next table
        error_log("Failed to prepare statement for table $table");
        continue;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userID);
        $stmt->fetch();
        $_SESSION['userID'] = $userID;
        $_SESSION['role'] = $table;
        $_SESSION['user_name'] = $userInfo->name;
        $_SESSION['user_email'] = $userInfo->email;
        $_SESSION['user_avatar'] = $userInfo->picture;
        $userFound = true;
        $role = $table;
        $stmt->close();
        break;
    }
    $stmt->close();
}

if ($userFound) {
    // Redirect to appropriate dashboard based on role
    header("Location: ../pages/{$role}/{$role}-dashboard.php");
    exit();
} else {
    // User not found in any table, redirect to login with error
    header("Location: ../pages/login.php?error=account_not_found");
    exit();
}
?>
