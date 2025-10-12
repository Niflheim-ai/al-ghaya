<?php
/**
 * Al-Ghaya Database Connection
 * Simplified version without timezone issues
 */

require_once __DIR__ . '/config.php';

try {
    // Get database configuration from environment
    $host = Config::get('DB_HOST', 'localhost');
    $port = Config::get('DB_PORT', '3306');
    $database = Config::get('DB_DATABASE');
    $username = Config::get('DB_USERNAME');
    $password = Config::get('DB_PASSWORD', '');
    
    // Create database connection
    $conn = new mysqli($host, $username, $password, $database, $port);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please check your configuration.");
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
    // Optional: Set timezone only if explicitly configured and valid
    $timezone = Config::get('DB_TIMEZONE');
    if (!empty($timezone)) {
        // Only try to set timezone if it's in the correct offset format
        if (preg_match('/^[+-]\d{2}:\d{2}$/', $timezone)) {
            $timezoneResult = $conn->query("SET time_zone = '$timezone'");
            if (!$timezoneResult && Config::get('APP_DEBUG', false)) {
                error_log("Warning: Could not set timezone to $timezone. Using MySQL default.");
            }
        } elseif (Config::get('APP_DEBUG', false)) {
            error_log("Invalid timezone format: $timezone. Use format like +08:00 or -05:00");
        }
    }
    
    if (Config::get('APP_DEBUG', false)) {
        error_log("Database connected successfully to: $host:$port/$database");
    }
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

/**
 * Close database connection safely
 */
function closeDbConnection() {
    global $conn;
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $conn->close();
    }
}

// Register shutdown function
register_shutdown_function('closeDbConnection');
?>
