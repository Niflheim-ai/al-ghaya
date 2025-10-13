<?php
require_once 'php/mail-config.php';

echo "<h1>OAuth2 Email Test - Al-Ghaya</h1>";

try {
    $result = sendPasswordResetEmailOAuth2('your-test-email@gmail.com', 'Test User', 'http://example.com/reset');
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ " . htmlspecialchars($result['message']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ " . htmlspecialchars($result['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
