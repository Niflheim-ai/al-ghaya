<?php
/**
 * Fix Teacher Mapping Script
 * 
 * This script creates missing teacher records for users who have role='teacher' 
 * but don't have corresponding entries in the teacher table.
 * 
 * Run this once to fix the database inconsistency.
 */

require_once '../php/dbConnection.php';

echo "<h2>Al-Ghaya Teacher Mapping Fix</h2>";
echo "<p>Checking for users with teacher role but missing teacher records...</p>";

// Find users with teacher role but no teacher record
$query = "
    SELECT u.userID, u.email, u.fname, u.lname, u.password
    FROM user u
    LEFT JOIN teacher t ON u.userID = t.userID
    WHERE u.role = 'teacher' 
    AND u.isActive = 1 
    AND t.teacherID IS NULL
";

$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo "<p style='color: green;'>✅ All teacher users have corresponding teacher records. No fix needed.</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Found {$result->num_rows} teacher user(s) without teacher records:</p>";
    echo "<ul>";
    
    $fixed = 0;
    while ($row = $result->fetch_assoc()) {
        echo "<li>User ID {$row['userID']}: {$row['email']} ({$row['fname']} {$row['lname']})";
        
        // Create teacher record
        $stmt = $conn->prepare("
            INSERT INTO teacher (userID, email, username, password, fname, lname, isActive, dateCreated) 
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        
        $username = explode('@', $row['email'])[0]; // Use email prefix as username
        
        if ($stmt->bind_param('isssss', 
            $row['userID'], 
            $row['email'], 
            $username, 
            $row['password'], 
            $row['fname'], 
            $row['lname']
        ) && $stmt->execute()) {
            $teacherID = $stmt->insert_id;
            echo " → ✅ Created teacher record (ID: {$teacherID})</li>";
            $fixed++;
        } else {
            echo " → ❌ Failed to create teacher record: " . $conn->error . "</li>";
        }
        
        $stmt->close();
    }
    
    echo "</ul>";
    echo "<p style='color: green;'><strong>✅ Fixed {$fixed} teacher record(s)!</strong></p>";
}

// Verify the fix
echo "<h3>Verification:</h3>";
$verifyQuery = "
    SELECT u.userID, u.email, u.role, t.teacherID, t.isActive as teacher_active
    FROM user u
    LEFT JOIN teacher t ON u.userID = t.userID
    WHERE u.role = 'teacher' 
    AND u.isActive = 1
    ORDER BY u.userID
";

$verifyResult = $conn->query($verifyQuery);
if ($verifyResult->num_rows > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>User ID</th><th>Email</th><th>Role</th><th>Teacher ID</th><th>Teacher Active</th></tr>";
    
    while ($row = $verifyResult->fetch_assoc()) {
        $status = $row['teacherID'] ? '✅' : '❌';
        echo "<tr>";
        echo "<td>{$row['userID']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>{$status} {$row['teacherID']}</td>";
        echo "<td>{$row['teacher_active']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No teacher users found.</p>";
}

echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>All teacher users should now have corresponding teacher records</li>";
echo "<li>Try accessing teacher-programs.php again</li>";
echo "<li>If you still get redirected, check your session variables (add debug code to see $_SESSION)</li>";
echo "</ol>";

$conn->close();
?>