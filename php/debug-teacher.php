<?php
session_start();
require_once 'dbConnection.php';
require_once 'program-handler.php';

// Only allow access if logged in as teacher
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'teacher') {
    die('<h1>Access Denied</h1><p>This debug page is only for teachers.</p><a href="../pages/login.php">Login</a>');
}

$user_id = $_SESSION['userID'];
$teacher_id = getTeacherIdFromSession($conn, $user_id);
$test_program_id = isset($_GET['test_program']) ? (int)$_GET['test_program'] : 0;

echo "<h1>Teacher Debug Information</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .box{background:#f0f8ff;border:1px solid #007cba;padding:15px;margin:10px 0;} .error{background:#ffe6e6;border:1px solid #ff0000;} .success{background:#e6ffe6;border:1px solid #00aa00;}</style>";

echo "<div class='box'>";
echo "<h2>Session Information</h2>";
echo "<p><strong>User ID:</strong> " . $user_id . "</p>";
echo "<p><strong>Role:</strong> " . ($_SESSION['role'] ?? 'Not set') . "</p>";
echo "<p><strong>Teacher ID:</strong> " . ($teacher_id ?: 'Not found/inactive') . "</p>";
echo "</div>";

if ($teacher_id) {
    echo "<div class='box success'>";
    echo "<h2>✅ Teacher Profile Status</h2>";
    echo "<p>Teacher profile exists and is active.</p>";
    
    // Show teacher's programs
    $programs = getTeacherPrograms($conn, $teacher_id);
    echo "<p><strong>Your Programs:</strong> " . count($programs) . " total</p>";
    
    if (!empty($programs)) {
        echo "<ul>";
        foreach ($programs as $p) {
            echo "<li>Program ID: {$p['programID']}, Title: {$p['title']}, Status: {$p['status']}</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // Test program ownership if provided
    if ($test_program_id) {
        $owns = program_verifyOwnership($conn, $test_program_id, $teacher_id);
        echo "<div class='box " . ($owns ? 'success' : 'error') . "'>";
        echo "<h2>Program Ownership Test</h2>";
        echo "<p><strong>Program ID:</strong> {$test_program_id}</p>";
        echo "<p><strong>Ownership:</strong> " . ($owns ? '✅ You own this program' : '❌ You do not own this program') . "</p>";
        
        if ($owns) {
            $program = getProgram($conn, $test_program_id, $teacher_id);
            if ($program) {
                echo "<p><strong>Program Title:</strong> {$program['title']}</p>";
                echo "<p><strong>Status:</strong> {$program['status']}</p>";
                
                $chapters = getChapters($conn, $test_program_id);
                echo "<p><strong>Chapters:</strong> " . count($chapters) . "</p>";
                
                if (!empty($chapters)) {
                    echo "<ul>";
                    foreach ($chapters as $ch) {
                        echo "<li>Chapter ID: {$ch['chapter_id']}, Title: {$ch['title']}</li>";
                    }
                    echo "</ul>";
                }
            }
        }
        echo "</div>";
    }
} else {
    echo "<div class='box error'>";
    echo "<h2>❌ Teacher Profile Issue</h2>";
    echo "<p>Teacher profile not found or inactive.</p>";
    echo "<p>Check if your user account has role='teacher' and isActive=1 in the database.</p>";
    echo "</div>";
}

echo "<div class='box'>";
echo "<h2>Test Program Ownership</h2>";
echo "<form method='GET'>";
echo "<label>Program ID to test: <input type='number' name='test_program' value='{$test_program_id}' min='1'></label>";
echo "<button type='submit'>Test Ownership</button>";
echo "</form>";
echo "<p><em>Enter a program ID to verify if you have ownership access.</em></p>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>Navigation</h2>";
echo "<p><a href='../pages/teacher/teacher-programs.php'>← Back to Teacher Programs</a></p>";
echo "<p><a href='../pages/teacher/teacher-dashboard.php'>← Back to Teacher Dashboard</a></p>";
echo "</div>";
?>