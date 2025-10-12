    <?php
/**
 * Al-Ghaya LMS Configuration Manager
 * Loads environment variables and provides configuration access
 */

class Config {
    private static $config = [];
    private static $loaded = false;
    
    /**
     * Load environment configuration
     */
    public static function load() {
        if (self::$loaded) {
            return;
        }
        
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            throw new Exception("Environment file (.env) not found. Please copy .env.example to .env and configure your settings.");
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) {
                continue; // Skip comments
            }
            
            if (strpos($line, '=') === false) {
                continue; // Skip invalid lines
            }
            
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            self::$config[$key] = $value;
            
            // Set as environment variable if not already set
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get configuration value
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null) {
        self::load();
        return self::$config[$key] ?? $_ENV[$key] ?? $default;
    }
    
    /**
     * Set configuration value
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public static function set($key, $value) {
        self::$config[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
    
    /**
     * Check if configuration key exists
     * @param string $key Configuration key
     * @return bool
     */
    public static function has($key) {
        self::load();
        return isset(self::$config[$key]) || isset($_ENV[$key]);
    }
    
    /**
     * Get all configuration values
     * @return array All configuration values
     */
    public static function all() {
        self::load();
        return array_merge($_ENV, self::$config);
    }
    
    /**
     * Validate required configuration keys
     * @param array $requiredKeys Array of required configuration keys
     * @throws Exception If required keys are missing
     */
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

// Load configuration on include
Config::load();
?>