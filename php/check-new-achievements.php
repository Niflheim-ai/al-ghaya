<?php
session_start();
require_once 'achievement-handler.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'achievements' => []]);
    exit();
}

$studentID = (int)$_SESSION['userID'];
$handler = new AchievementHandler($conn, $studentID);

// Get achievements from last 10 seconds
$result = $handler->checkAndGetNew(10);

echo json_encode($result);
?>
