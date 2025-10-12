<?php
/**
 * Al-Ghaya Database Connection
 * Secured with environment variables
 */

require_once __DIR__ . '/config.php';

try {
    // Validate required database configuration
    Config::validateRequired([
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME'
    ]);
    
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
        
        if (Config::get('APP_DEBUG', false)) {
            die("Database connection failed: " . $conn->connect_error);
        } else {
            die("Database connection failed. Please try again later.");
        }
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
    // Set timezone (optional)
    $timezone = Config::get('DB_TIMEZONE', 'UTC');
    $conn->query("SET time_zone = '$timezone'");
    
    if (Config::get('APP_DEBUG', false)) {
        error_log("Database connected successfully to: $host:$port/$database");
    }
    
} catch (Exception $e) {
    error_log("Database configuration error: " . $e->getMessage());
    
    if (Config::get('APP_DEBUG', false)) {
        die("Database configuration error: " . $e->getMessage());
    } else {
        die("Database configuration error. Please check your settings.");
    }
}

/**
 * Close database connection safely
 */
function closeDbConnection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Register shutdown function to close connection
register_shutdown_function('closeDbConnection');
?>
