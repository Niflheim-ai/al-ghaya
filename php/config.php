<?php
/**
 * Al-Ghaya Configuration Manager
 * Simplified version with fallbacks
 */

class Config {
    private static $config = [];
    private static $loaded = false;
    
    public static function load() {
        if (self::$loaded) {
            return;
        }
        
        $envFile = __DIR__ . '/../.env';
        
        // Load .env file if it exists
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                    continue;
                }
                
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$config[$key] = $value;
                $_ENV[$key] = $value;
            }
        }
        
        // Set default values if not configured
        self::setDefaults();
        self::$loaded = true;
    }
    
    private static function setDefaults() {
        $defaults = [
            'APP_NAME' => 'Al-Ghaya LMS',
            'APP_ENV' => 'development',
            'APP_DEBUG' => 'true',
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'DB_TIMEZONE' => '+08:00', // Philippines time
            'SESSION_LIFETIME' => '1440'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset(self::$config[$key])) {
                self::$config[$key] = $value;
                $_ENV[$key] = $value;
            }
        }
    }
    
    public static function get($key, $default = null) {
        self::load();
        return self::$config[$key] ?? $_ENV[$key] ?? $default;
    }
    
    public static function has($key) {
        self::load();
        return isset(self::$config[$key]) || isset($_ENV[$key]);
    }
    
    public static function validateRequired($requiredKeys) {
        self::load();
        $missing = [];
        
        foreach ($requiredKeys as $key) {
            if (!self::has($key) || empty(self::get($key))) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("Missing required configuration: " . implode(', ', $missing));
        }
    }
}

// Auto-load configuration
Config::load();
?>
