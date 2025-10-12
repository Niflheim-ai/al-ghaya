<?php
/**
 * Al-Ghaya Database Connection
 * Fixed to prevent redeclaration errors
 */

// Prevent multiple includes
if (defined('DB_CONNECTION_LOADED')) {
    return;
}
define('DB_CONNECTION_LOADED', true);

// Check if config is available
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $host = Config::get('DB_HOST', 'localhost');
    $port = Config::get('DB_PORT', '3306');
    $database = Config::get('DB_DATABASE', 'al_ghaya_lms');
    $username = Config::get('DB_USERNAME', 'root');
    $password = Config::get('DB_PASSWORD', '');
    $debug = Config::get('APP_DEBUG', false);
} else {
    // Fallback configuration
    $host = 'localhost';
    $port = '3306';
    $database = 'al_ghaya_lms';
    $username = 'root';
    $password = '';
    $debug = true;
}

// Only create connection if it doesn't exist
if (!isset($GLOBALS['conn']) || !$GLOBALS['conn']) {
    try {
        // Create database connection
        $conn = new mysqli($host, $username, $password, $database, $port);
        
        // Check connection
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            
            if ($debug) {
                die("Database connection failed: " . $conn->connect_error);
            } else {
                die("Database connection failed. Please check your configuration.");
            }
        }
        
        // Set charset to UTF-8
        if (!$conn->set_charset("utf8mb4")) {
            if ($debug) {
                error_log("Warning: Could not set charset to utf8mb4");
            }
        }
        
        // Store connection globally
        $GLOBALS['conn'] = $conn;
        
        if ($debug) {
            error_log("Database connected successfully to: $host:$port/$database");
        }
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        
        if ($debug) {
            die("Database connection error: " . $e->getMessage());
        } else {
            die("Database connection error. Please try again later.");
        }
    }
} else {
    // Use existing connection
    $conn = $GLOBALS['conn'];
}

/**
 * Close database connection safely
 * Only declare if not already declared
 */
if (!function_exists('closeDbConnection')) {
    function closeDbConnection() {
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            $GLOBALS['conn']->close();
            $GLOBALS['conn'] = null;
        }
    }
}

/**
 * Test database connection
 * Only declare if not already declared
 */
if (!function_exists('testDbConnection')) {
    function testDbConnection() {
        global $conn;
        
        if (!$conn || $conn->connect_error) {
            return false;
        }
        
        try {
            $result = $conn->query("SELECT 1");
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Get database connection instance
 * Only declare if not already declared
 */
if (!function_exists('getDbConnection')) {
    function getDbConnection() {
        global $conn;
        return $conn;
    }
}

// Register shutdown function only once
if (!defined('DB_SHUTDOWN_REGISTERED')) {
    register_shutdown_function('closeDbConnection');
    define('DB_SHUTDOWN_REGISTERED', true);
}
?>
