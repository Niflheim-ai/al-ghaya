<?php
    require_once __DIR__ . '/config.php';

    // Validate PayMongo keys are in .env
    Config::validateRequired([
        'PAYMONGO_SECRET_KEY',
        'PAYMONGO_PUBLIC_KEY'
    ]);

    // PayMongo Configuration from Config class
    define('PAYMONGO_SECRET_KEY', Config::get('PAYMONGO_SECRET_KEY'));
    define('PAYMONGO_PUBLIC_KEY', Config::get('PAYMONGO_PUBLIC_KEY'));
    define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');
    define('PAYMONGO_TEST_MODE', filter_var(Config::get('PAYMONGO_TEST_MODE', 'true'), FILTER_VALIDATE_BOOLEAN));

    // Validate keys are not empty
    if (empty(PAYMONGO_SECRET_KEY) || empty(PAYMONGO_PUBLIC_KEY)) {
        error_log('PayMongo API keys are empty in .env file');
        throw new Exception('PayMongo API keys not configured properly');
    }
?>