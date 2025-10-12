<?php
/**
 * Admin Login Diagnostic Tool
 * For Al-Ghaya LMS (Transferred from ai-development branch)
 * 
 * This script helps diagnose and fix admin login issues
 * REMOVE FROM PRODUCTION AFTER USE!
 */

require_once 'php/dbConnection.php';
require_once 'php/auth-system.php';

header('Content-Type: text/html; charset=utf-8');

// Helper function to create admin account
function createAdminAccount($conn) {
    $email = 'admin@al-ghaya.com';
    $password = 'Admin@2025';
    $hashedPassword = '$2y$12$mGY.5vP0qVy.rVq1rBv8FeQVKjmm9QZZvbZKfRKRqfzJfbj8/XHQy';
    
    // Check if admin exists
    $checkStmt = $conn->prepare("SELECT userID, email, role FROM user WHERE email = ? OR role = 'admin'");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<div class='info'>📋 Existing admin accounts found:</div>";
        while ($row = $result->fetch_assoc()) {
            echo "<div class='account'>• ID: {$row['userID']}, Email: {$row['email']}, Role: {$row['role']}</div>";
        }
        return false;
    }
    
    // Create admin account
    $stmt = $conn->prepare("INSERT INTO user (email, password, fname, lname, role, level, points, proficiency, isActive, dateCreated) VALUES (?, ?, 'System', 'Administrator', 'admin', 99, 99999, 'advanced', 1, NOW())");
    $stmt->bind_param("ss", $email, $hashedPassword);
    
    if ($stmt->execute()) {
        echo "<div class='success'>✅ Admin account created successfully!</div>";
        echo "<div class='credentials'>📧 Email: {$email}<br>🔑 Password: {$password}</div>";
        return true;
    } else {
        echo "<div class='error'>❌ Failed to create admin account: " . $conn->error . "</div>";
        return false;
    }
}

// Helper function to test login
function testAdminLogin($auth) {
    $email = 'admin@al-ghaya.com';
    $password = 'Admin@2025';
    
    echo "<div class='test-section'>🧪 Testing Admin Login:</div>";
    echo "<div class='test-details'>Email: {$email}<br>Password: {$password}</div>";
    
    $result = $auth->manualLogin($email, $password);
    
    if ($result['success']) {
        echo "<div class='success'>✅ Login test successful!</div>";
        echo "<div class='info'>Redirect: {$result['redirect']}</div>";
    } else {
        echo "<div class='error'>❌ Login test failed: {$result['message']}</div>";
    }
    
    return $result;
}

// Helper function to check database structure
function checkDatabaseStructure($conn) {
    echo "<div class='test-section'>🏗️ Database Structure Check:</div>";
    
    // Check user table structure
    $result = $conn->query("DESCRIBE user");
    if ($result) {
        echo "<div class='success'>✅ User table exists</div>";
        $hasRole = false;
        while ($row = $result->fetch_assoc()) {
            if ($row['Field'] === 'role') {
                $hasRole = true;
                break;
            }
        }
        
        if ($hasRole) {
            echo "<div class='success'>✅ Role column exists in user table</div>";
        } else {
            echo "<div class='error'>❌ Role column missing in user table</div>";
        }
    } else {
        echo "<div class='error'>❌ User table does not exist</div>";
    }
    
    // Check for admin accounts
    $result = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'admin'");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<div class='info'>📊 Admin accounts in database: {$row['count']}</div>";
    }
}

// Process actions
if ($_POST['action'] ?? '' === 'create_admin') {
    createAdminAccount($conn);
}

if ($_POST['action'] ?? '' === 'test_login') {
    $auth = getAuthSystem();
    testAdminLogin($auth);
}

if ($_POST['action'] ?? '' === 'reset_password') {
    $newPassword = $_POST['new_password'] ?? 'NewAdmin@2025';
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE role = 'admin'");
    $stmt->bind_param("s", $hashedPassword);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "<div class='success'>✅ Admin password updated!</div>";
        echo "<div class='credentials'>🔑 New Password: {$newPassword}</div>";
    } else {
        echo "<div class='error'>❌ Failed to update password or no admin found</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login Diagnostic - Al-Ghaya LMS</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 10px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 12px; border-radius: 6px; margin: 10px 0; }
        .test-section { background: #e9ecef; padding: 15px; border-radius: 6px; margin: 20px 0; font-weight: bold; }
        .test-details { font-family: 'Courier New', monospace; background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .credentials { background: #fff3cd; padding: 15px; border-radius: 6px; margin: 10px 0; font-family: 'Courier New', monospace; font-weight: bold; }
        .account { font-family: 'Courier New', monospace; background: #f8f9fa; padding: 8px; margin: 5px 0; border-radius: 4px; }
        .form-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        button { background: #007bff; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; margin: 10px 5px; font-size: 14px; }
        button:hover { background: #0056b3; }
        button.danger { background: #dc3545; }
        button.danger:hover { background: #c82333; }
        input[type="password"] { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 200px; margin: 0 10px; }
        .status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .status-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Admin Login Diagnostic Tool</h1>
        <p><em>Al-Ghaya LMS - Transferred from AI Development Branch</em></p>
        
        <div class="warning">
            <strong>⚠️ SECURITY WARNING:</strong> This diagnostic tool should be removed from production servers!
        </div>

        <div class="status-grid">
            <div class="status-card">
                <h3>📊 System Status</h3>
                <?php 
                echo "<div>Database: " . ($conn ? "✅ Connected" : "❌ Not Connected") . "</div>";
                echo "<div>PHP Version: " . PHP_VERSION . "</div>";
                echo "<div>Current Time: " . date('Y-m-d H:i:s') . "</div>";
                ?>
            </div>
            
            <div class="status-card">
                <h3>🔍 Quick Checks</h3>
                <?php checkDatabaseStructure($conn); ?>
            </div>
        </div>

        <div class="form-section">
            <h3>🛠️ Diagnostic Actions</h3>
            
            <form method="post" style="margin: 15px 0;">
                <input type="hidden" name="action" value="create_admin">
                <button type="submit">1. Create Default Admin Account</button>
                <small style="color: #666; margin-left: 10px;">Email: admin@al-ghaya.com | Password: Admin@2025</small>
            </form>
            
            <form method="post" style="margin: 15px 0;">
                <input type="hidden" name="action" value="test_login">
                <button type="submit">2. Test Admin Login</button>
                <small style="color: #666; margin-left: 10px;">Tests login with default credentials</small>
            </form>
            
            <form method="post" style="margin: 15px 0;">
                <input type="hidden" name="action" value="reset_password">
                <input type="password" name="new_password" placeholder="New password" value="NewAdmin@2025">
                <button type="submit" class="danger">3. Reset Admin Password</button>
                <small style="color: #666; margin-left: 10px;">Updates password for existing admin</small>
            </form>
        </div>

        <div class="form-section">
            <h3>📋 Current Admin Accounts</h3>
            <?php
            $result = $conn->query("SELECT userID, email, fname, lname, role, isActive, dateCreated FROM user WHERE role = 'admin' ORDER BY userID");
            if ($result && $result->num_rows > 0) {
                echo "<table style='width: 100%; border-collapse: collapse;'>";
                echo "<tr style='background: #f8f9fa; border-bottom: 2px solid #dee2e6;'><th style='padding: 10px; text-align: left;'>ID</th><th style='padding: 10px; text-align: left;'>Email</th><th style='padding: 10px; text-align: left;'>Name</th><th style='padding: 10px; text-align: left;'>Status</th><th style='padding: 10px; text-align: left;'>Created</th></tr>";
                while ($admin = $result->fetch_assoc()) {
                    $statusIcon = $admin['isActive'] ? '✅' : '❌';
                    $statusText = $admin['isActive'] ? 'Active' : 'Inactive';
                    echo "<tr style='border-bottom: 1px solid #dee2e6;'>";
                    echo "<td style='padding: 10px;'>{$admin['userID']}</td>";
                    echo "<td style='padding: 10px;'>{$admin['email']}</td>";
                    echo "<td style='padding: 10px;'>{$admin['fname']} {$admin['lname']}</td>";
                    echo "<td style='padding: 10px;'>{$statusIcon} {$statusText}</td>";
                    echo "<td style='padding: 10px;'>{$admin['dateCreated']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ No admin accounts found in the database</div>";
            }
            ?>
        </div>

        <div class="form-section">
            <h3>📝 Instructions</h3>
            <ol>
                <li><strong>Create Admin Account:</strong> If no admin exists, click "Create Default Admin Account"</li>
                <li><strong>Test Login:</strong> Use "Test Admin Login" to verify the account works</li>
                <li><strong>Manual Login:</strong> Go to <code>pages/login.php</code> and use:
                    <div class="credentials">Email: admin@al-ghaya.com<br>Password: Admin@2025</div>
                </li>
                <li><strong>Reset Password:</strong> If login fails, use the reset password option</li>
                <li><strong>Clean Up:</strong> Delete this file after fixing the issue!</li>
            </ol>
        </div>

        <div class="warning">
            <strong>🔗 Quick Links:</strong><br>
            <a href="pages/login.php" target="_blank">→ Go to Login Page</a> | 
            <a href="pages/admin/admin-dashboard.php" target="_blank">→ Go to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>