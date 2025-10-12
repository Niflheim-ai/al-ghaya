<?php
session_start();
require '../../php/dbConnection.php';
require '../../php/functions.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$teacher_id = $_SESSION['userID'];
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

if (!$chapter_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Chapter ID is required']);
    exit();
}

try {
    // Get chapter details and verify teacher ownership through program
    $stmt = $conn->prepare("
        SELECT pc.*, p.teacherID 
        FROM program_chapters pc
        JOIN programs p ON pc.program_id = p.programID
        WHERE pc.chapter_id = ? AND p.teacherID = ?
    ");
    $stmt->bind_param("ii", $chapter_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chapter = $result->fetch_assoc();
    
    if (!$chapter) {
        http_response_code(404);
        echo json_encode(['error' => 'Chapter not found or access denied']);
        exit();
    }
    
    // Remove sensitive data
    unset($chapter['teacherID']);
    
    // Set proper content type
    header('Content-Type: application/json');
    echo json_encode($chapter);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log("Error fetching chapter: " . $e->getMessage());
}
?>
