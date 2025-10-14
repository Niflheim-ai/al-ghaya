<?php
/**
 * Comprehensive Teacher Mapping Fix Script
 * This script ensures all users with role='teacher' have corresponding records in the teacher table
 * and fixes any missing connections between user and teacher tables.
 * 
 * Run this script to fix teacher authentication issues in the al-ghaya system.
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'al_ghaya_lms';

echo "<html><head><title>Al-Ghaya Teacher Mapping Fix</title></head><body>";
echo "<h1>Al-Ghaya Teacher Mapping Fix Script</h1>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; margin: 20px; border-radius: 5px;'>";

try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>✅ <strong>Connected to database successfully</strong></p>";
    
    // Check if tables exist
    echo "<h3>🔍 Checking Database Structure</h3>";
    
    $tables_to_check = ['user', 'teacher', 'programs'];
    $missing_tables = [];
    
    foreach ($tables_to_check as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' missing</p>";
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo "<p><strong>❌ Missing tables detected. Please ensure your database is properly set up.</strong></p>";
        echo "</div></body></html>";
        exit();
    }
    
    // Step 1: Find all users with teacher role
    echo "<h3>👨‍🏫 Finding Teacher Users</h3>";
    
    $teacher_users_query = "SELECT userID, email, fname, lname FROM user WHERE role = 'teacher' AND isActive = 1";
    $teacher_users_result = $conn->query($teacher_users_query);
    
    if (!$teacher_users_result) {
        throw new Exception("Error fetching teacher users: " . $conn->error);
    }
    
    $teacher_users = $teacher_users_result->fetch_all(MYSQLI_ASSOC);
    echo "<p>Found " . count($teacher_users) . " teacher users:</p>";
    
    if (empty($teacher_users)) {
        echo "<p>⚠️ No teacher users found. The fix cannot proceed without teacher users.</p>";
        echo "<p>Please ensure you have users with role='teacher' in the user table.</p>";
        echo "</div></body></html>";
        exit();
    }
    
    foreach ($teacher_users as $user) {
        echo "<p>- User ID: {$user['userID']}, Email: {$user['email']}, Name: {$user['fname']} {$user['lname']}</p>";
    }
    
    // Step 2: Check existing teacher records
    echo "<h3>🔍 Checking Existing Teacher Records</h3>";
    
    $existing_teachers_query = "SELECT teacherID, userID, email FROM teacher WHERE isActive = 1";
    $existing_teachers_result = $conn->query($existing_teachers_query);
    
    if (!$existing_teachers_result) {
        throw new Exception("Error fetching existing teachers: " . $conn->error);
    }
    
    $existing_teachers = $existing_teachers_result->fetch_all(MYSQLI_ASSOC);
    $existing_user_ids = array_column($existing_teachers, 'userID');
    
    echo "<p>Found " . count($existing_teachers) . " existing teacher records:</p>";
    foreach ($existing_teachers as $teacher) {
        echo "<p>- Teacher ID: {$teacher['teacherID']}, User ID: {$teacher['userID']}, Email: {$teacher['email']}</p>";
    }
    
    // Step 3: Identify missing teacher records
    echo "<h3>🔧 Identifying Missing Teacher Records</h3>";
    
    $missing_teachers = [];
    foreach ($teacher_users as $user) {
        if (!in_array($user['userID'], $existing_user_ids)) {
            $missing_teachers[] = $user;
        }
    }
    
    echo "<p>Found " . count($missing_teachers) . " users that need teacher records created:</p>";
    
    if (empty($missing_teachers)) {
        echo "<p>✅ All teacher users already have corresponding teacher records!</p>";
    } else {
        foreach ($missing_teachers as $user) {
            echo "<p>- Missing: User ID {$user['userID']}, Email: {$user['email']}</p>";
        }
    }
    
    // Step 4: Create missing teacher records
    if (!empty($missing_teachers)) {
        echo "<h3>➕ Creating Missing Teacher Records</h3>";
        
        $created_count = 0;
        $failed_count = 0;
        
        foreach ($missing_teachers as $user) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO teacher (userID, email, username, fname, lname, dateCreated, isActive) " .
                    "VALUES (?, ?, ?, ?, ?, NOW(), 1)"
                );
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $username = $user['email']; // Use email as username
                $stmt->bind_param(
                    "issss",
                    $user['userID'],
                    $user['email'],
                    $username,
                    $user['fname'],
                    $user['lname']
                );
                
                if ($stmt->execute()) {
                    $new_teacher_id = $stmt->insert_id;
                    echo "<p>✅ Created teacher record for {$user['email']} (Teacher ID: {$new_teacher_id})</p>";
                    $created_count++;
                } else {
                    echo "<p>❌ Failed to create teacher record for {$user['email']}: " . $stmt->error . "</p>";
                    $failed_count++;
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                echo "<p>❌ Error creating teacher record for {$user['email']}: " . $e->getMessage() . "</p>";
                $failed_count++;
            }
        }
        
        echo "<p><strong>Summary: {$created_count} teacher records created, {$failed_count} failed</strong></p>";
    }
    
    // Step 5: Validate the fix
    echo "<h3>✅ Validation</h3>";
    
    // Re-check teacher mappings
    $validation_query = "
        SELECT u.userID, u.email, u.fname, u.lname, t.teacherID 
        FROM user u 
        LEFT JOIN teacher t ON u.userID = t.userID AND t.isActive = 1 
        WHERE u.role = 'teacher' AND u.isActive = 1
    ";
    
    $validation_result = $conn->query($validation_query);
    
    if (!$validation_result) {
        throw new Exception("Validation query failed: " . $conn->error);
    }
    
    $validation_data = $validation_result->fetch_all(MYSQLI_ASSOC);
    $all_mapped = true;
    
    echo "<p><strong>Final Teacher Mapping Status:</strong></p>";
    foreach ($validation_data as $row) {
        if ($row['teacherID']) {
            echo "<p>✅ {$row['email']} (User: {$row['userID']}, Teacher: {$row['teacherID']})</p>";
        } else {
            echo "<p>❌ {$row['email']} (User: {$row['userID']}, No Teacher Record)</p>";
            $all_mapped = false;
        }
    }
    
    if ($all_mapped) {
        echo "<p style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px;'>";
        echo "<strong>🎉 SUCCESS: All teacher users now have proper teacher records!</strong><br>";
        echo "You can now use the Create Program functionality without authentication issues.";
        echo "</p>";
    } else {
        echo "<p style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px;'>";
        echo "<strong>⚠️ PARTIAL SUCCESS: Some teacher users still don't have teacher records.</strong><br>";
        echo "Please check the errors above and fix them manually.";
        echo "</p>";
    }
    
    // Step 6: Additional checks and recommendations
    echo "<h3>🔍 Additional Checks</h3>";
    
    // Check for orphaned programs
    $orphaned_programs_query = "
        SELECT p.programID, p.title, p.teacherID 
        FROM programs p 
        LEFT JOIN teacher t ON p.teacherID = t.teacherID AND t.isActive = 1 
        WHERE t.teacherID IS NULL
    ";
    
    $orphaned_result = $conn->query($orphaned_programs_query);
    if ($orphaned_result && $orphaned_result->num_rows > 0) {
        echo "<p>⚠️ Found " . $orphaned_result->num_rows . " programs with invalid teacher references:</p>";
        while ($row = $orphaned_result->fetch_assoc()) {
            echo "<p>- Program: '{$row['title']}' (ID: {$row['programID']}, Teacher ID: {$row['teacherID']})</p>";
        }
        echo "<p>These programs may need to be reassigned to valid teachers.</p>";
    } else {
        echo "<p>✅ No orphaned programs found.</p>";
    }
    
    // Check database structure for missing columns
    echo "<h3>📋 Database Structure Check</h3>";
    
    $teacher_columns = $conn->query("DESCRIBE teacher");
    $required_columns = ['teacherID', 'userID', 'email', 'username', 'fname', 'lname', 'isActive'];
    $existing_columns = [];
    
    while ($row = $teacher_columns->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach ($required_columns as $column) {
        if (in_array($column, $existing_columns)) {
            echo "<p>✅ Column 'teacher.{$column}' exists</p>";
        } else {
            echo "<p>❌ Column 'teacher.{$column}' missing</p>";
        }
    }
    
    echo "<h3>🚀 Next Steps</h3>";
    echo "<p>1. Test the Create Program functionality by logging in as a teacher</p>";
    echo "<p>2. Navigate to Teacher Dashboard > My Programs > Create New Program</p>";
    echo "<p>3. If issues persist, check the error logs and contact system administrator</p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px;'>";
    echo "<strong>❌ ERROR: " . $e->getMessage() . "</strong>";
    echo "</p>";
    
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

echo "</div>";
echo "<p style='text-align: center; margin-top: 40px;'><a href='../pages/teacher/teacher-programs.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Go to Teacher Programs</a></p>";
echo "</body></html>";
?>