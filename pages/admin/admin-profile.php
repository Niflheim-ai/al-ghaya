<?php
session_start();
include('../../php/dbConnection.php');
require_once '../../php/config.php';

if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $message = 'Invalid email address.'; }
        else {
            // Ensure email unique (or belongs to current user)
            $stmt = $conn->prepare("SELECT userID FROM user WHERE email = ? AND userID <> ?");
            $stmt->bind_param('si', $email, $_SESSION['userID']);
            $stmt->execute(); $stmt->store_result();
            if ($stmt->num_rows > 0) { $message = 'Email already in use by another account.'; }
            else {
                $up = $conn->prepare("UPDATE user SET fname = ?, lname = ?, email = ? WHERE userID = ? AND role = 'admin'");
                $up->bind_param('sssi', $fname, $lname, $email, $_SESSION['userID']);
                $up->execute(); $message = $up->affected_rows >= 0 ? 'Profile updated.' : 'No changes.';
            }
        }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new !== $confirm) { $message = 'New passwords do not match.'; }
        else if (strlen($new) < 8) { $message = 'Password must be at least 8 characters.'; }
        else {
            $stmt = $conn->prepare("SELECT password FROM user WHERE userID = ? AND role = 'admin'");
            $stmt->bind_param('i', $_SESSION['userID']); $stmt->execute(); $res = $stmt->get_result(); $row = $res->fetch_assoc();
            if (!$row || !password_verify($current, $row['password'])) { $message = 'Current password is incorrect.'; }
            else {
                $hash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]);
                $up = $conn->prepare("UPDATE user SET password = ? WHERE userID = ?");
                $up->bind_param('si', $hash, $_SESSION['userID']); $up->execute(); $message = 'Password updated successfully.';
            }
        }
    }
}

// Load current admin profile
$admin = ['fname'=>'','lname'=>'','email'=>''];
$stmt = $conn->prepare("SELECT fname, lname, email, dateCreated, lastLogin FROM user WHERE userID = ? AND role = 'admin'");
$stmt->bind_param('i', $_SESSION['userID']); $stmt->execute(); $res = $stmt->get_result(); if ($res) { $admin = $res->fetch_assoc(); }
?>
<?php include '../../components/header.php'; ?>
<?php include '../../components/admin-nav.php'; ?>
<div class="page-container"><div class="page-content">
<section class="content-section">
  <h1 class="section-title">Admin Profile</h1>
  <?php if ($message): ?><div class="mb-4 p-3 rounded bg-blue-50 text-blue-800 border border-blue-200"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="section-card">
      <h2 class="text-lg font-semibold mb-4">Profile Information</h2>
      <form method="post">
        <input type="hidden" name="action" value="update_profile">
        <div class="mb-3">
          <label class="block text-sm text-gray-700 mb-1">First Name</label>
          <input name="fname" value="<?= htmlspecialchars($admin['fname'] ?? '') ?>" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-3">
          <label class="block text-sm text-gray-700 mb-1">Last Name</label>
          <input name="lname" value="<?= htmlspecialchars($admin['lname'] ?? '') ?>" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-3">
          <label class="block text-sm text-gray-700 mb-1">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" class="w-full border rounded px-3 py-2">
        </div>
        <button class="btn-secondary" type="submit">Save Changes</button>
      </form>
    </div>
    <div class="section-card">
      <h2 class="text-lg font-semibold mb-4">Change Password</h2>
      <form method="post">
        <input type="hidden" name="action" value="change_password">
        <div class="mb-3">
          <label class="block text-sm text-gray-700 mb-1">Current Password</label>
          <input type="password" name="current_password" class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-3">
          <label class="block text-sm text-gray-700 mb-1">New Password</label>
          <input type="password" name="new_password" class="w-full border rounded px-3 py-2" minlength="8">
        </div>
        <div class="mb-4">
          <label class="block text-sm text-gray-700 mb-1">Confirm New Password</label>
          <input type="password" name="confirm_password" class="w-full border rounded px-3 py-2" minlength="8">
        </div>
        <button class="btn-secondary" type="submit">Update Password</button>
      </form>
    </div>
  </div>
</section>
</div></div>
</body>
</html>