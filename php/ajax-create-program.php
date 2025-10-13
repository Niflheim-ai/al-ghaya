<?php
// ajax-create-program.php - Clean AJAX endpoint for Quick Access New Program button
error_reporting(0); // Suppress all errors for clean JSON
ini_set('display_errors', 0);

session_start();

// Set JSON header immediately
header('Content-Type: application/json');

try {
    // Database connection
    require_once __DIR__ . '/dbConnection.php';
    
    // Check authentication
    if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
        throw new Exception('Authentication required');
    }
    
    $user_id = $_SESSION['userID'];
    
    // Get teacher ID
    $stmt = $conn->prepare("SELECT teacherID FROM teacher WHERE userID = ? AND isActive = 1");
    if (!$stmt) {
        throw new Exception('Database error');
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Teacher profile not found');
    }
    
    $teacher_id = $result->fetch_assoc()['teacherID'];
    
    // Create program with default values
    $stmt = $conn->prepare("INSERT INTO programs (teacherID, title, description, category, price, status, thumbnail, dateCreated, dateUpdated) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    
    if (!$stmt) {
        throw new Exception('Database prepare error');
    }
    
    $title = 'New Program';
    $description = 'Program description';
    $category = 'beginner';
    $price = 500.00;
    $status = 'draft';
    $thumbnail = 'default-thumbnail.jpg';
    
    $stmt->bind_param("isssiss", $teacher_id, $title, $description, $category, $price, $status, $thumbnail);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create program');
    }
    
    $program_id = $stmt->insert_id;
    
    if ($program_id > 0) {
        echo json_encode([
            'success' => true,
            'program_id' => $program_id,
            'message' => 'Program created successfully'
        ]);
    } else {
        throw new Exception('Failed to get program ID');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>