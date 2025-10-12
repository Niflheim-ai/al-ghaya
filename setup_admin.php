<?php
/**
 * Admin Setup Script for Al-Ghaya LMS
 * This script helps create and manage admin accounts for Google OAuth authentication
 * 
 * SECURITY WARNING: Remove this file from production server after use!
 */

// Include database connection
require_once 'php/dbConnection.php';

// Function to create admin table
function createAdminTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS `admin` (
        `adminID` int(11) NOT NULL AUTO_INCREMENT,
        `email` varchar(255) NOT NULL UNIQUE,
        `name` varchar(255) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `status` enum('active','inactive') DEFAULT 'active',
        PRIMARY KEY (`adminID`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✓ Admin table created successfully</p>";
    } else {
        echo "<p class='error'>✗ Error creating admin table: " . $conn->error . "</p>";
    }
}

// Function to add admin user
function addAdminUser($conn, $email, $name) {
    $stmt = $conn->prepare("INSERT INTO admin (email, name, status) VALUES (?, ?, 'active') ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status), updated_at = CURRENT_TIMESTAMP");
    $stmt->bind_param("ss", $email, $name);
    
    if ($stmt->execute()) {
        echo "<p class='success'>✓ Admin user '$email' added/updated successfully</p>";
    } else {
        echo "<p class='error'>✗ Error adding admin user: " . $conn->error . "</p>";
    }
    $stmt->close();
}

// Function to list all admin users
function listAdminUsers($conn) {
    $result = $conn->query("SELECT * FROM admin WHERE status = 'active' ORDER BY created_at DESC");
    
    if ($result && $result->num_rows > 0) {
        echo "<h3>Current Admin Users:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Created At</th><th>Status</th></tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['adminID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>No admin users found.</p>";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_table':
                createAdminTable($conn);
                break;
            case 'add_admin':
                if (!empty($_POST['email']) && !empty($_POST['name'])) {
                    addAdminUser($conn, $_POST['email'], $_POST['name']);
                } else {
                    echo "<p class='error'>✗ Please provide both email and name</p>";
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Ghaya Admin Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .container { background: #f5f5f5; padding: 20px; border-radius: 8px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        form { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        input, button { padding: 8px; margin: 5px; }
        button { background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0056b3; }
        table { width: 100%; margin-top: 15px; }
        th { background: #f8f9fa; }
        .security-warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Al-Ghaya LMS Admin Setup</h1>
        
        <div class="security-warning">
            <strong>🔒 SECURITY WARNING:</strong> This file should be removed from your production server after setup is complete!
        </div>

        <h2>Instructions:</h2>
        <ol>
            <li><strong>Create Admin Table:</strong> Click the button below to create the admin table in your database.</li>
            <li><strong>Add Your Google Account:</strong> Add your Google account email to the admin table.</li>
            <li><strong>Login via Google OAuth:</strong> Use the regular login page and authenticate with Google.</li>
            <li><strong>Remove This File:</strong> Delete this setup file for security.</li>
        </ol>

        <form method="post">
            <h3>Step 1: Create Admin Table</h3>
            <input type="hidden" name="action" value="create_table">
            <button type="submit">Create Admin Table</button>
        </form>

        <form method="post">
            <h3>Step 2: Add Admin User</h3>
            <input type="hidden" name="action" value="add_admin">
            <label for="email">Google Account Email:</label><br>
            <input type="email" name="email" id="email" placeholder="your-email@gmail.com" required style="width: 300px;"><br>
            <label for="name">Admin Name:</label><br>
            <input type="text" name="name" id="name" placeholder="Administrator Name" required style="width: 300px;"><br>
            <button type="submit">Add Admin User</button>
        </form>

        <div style="margin-top: 30px;">
            <?php listAdminUsers($conn); ?>
        </div>

        <h3>Database Connection Status:</h3>
        <?php if ($conn): ?>
            <p class="success">✓ Database connection successful</p>
            <p><strong>Database:</strong> al_ghaya_lms</p>
            <p><strong>Host:</strong> localhost</p>
        <?php else: ?>
            <p class="error">✗ Database connection failed</p>
        <?php endif; ?>
    </div>
</body>
</html>