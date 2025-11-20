<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "dbConnection.php";
header('Content-Type: application/json');

if (!isset($_SESSION['userID'])) {
    echo json_encode([]);
    exit;
}
$userId = intval($_SESSION['userID']);

// Get teacherID for this user
$teacherStmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ?");
$teacherStmt->bind_param("i", $userId);
$teacherStmt->execute();
$teacherRes = $teacherStmt->get_result();
if ($teacherRes->num_rows == 0) {
    echo json_encode([]);
    exit;
}
$teacherRow = $teacherRes->fetch_assoc();
$teacherId = intval($teacherRow['teacherID']);
$teacherStmt->close();

// Properly quoted, no $status param needed
$stmt = $conn->prepare("SELECT programID, title FROM programs WHERE status = 'published' AND teacherID = ?");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
$programs = [];
while ($row = $result->fetch_assoc()) {
    $programs[] = [
        'programID' => $row['programID'],
        'title' => $row['title']
    ];
}
$stmt->close();

echo json_encode($programs);
?>
