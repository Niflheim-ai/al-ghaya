<?php
/**
 * Multi-Provider Payment Configuration
 * Supports: PayMongo (primary), Xendit (fallback)
 */

require_once __DIR__ . '/config.php';

class PaymentConfig {
    /**
     * Get active payment provider based on configuration priority
     * Returns: 'paymongo' or 'xendit'
     */
    public static function getActiveProvider() {
        $preferredProvider = Config::get('PAYMENT_PROVIDER', 'paymongo');
        
        // Check if preferred provider is available
        if (self::isProviderAvailable($preferredProvider)) {
            return $preferredProvider;
        }
        
        // Fallback chain: PayMongo -> Xendit
        $fallbackOrder = ['paymongo', 'xendit'];
        
        foreach ($fallbackOrder as $provider) {
            if (self::isProviderAvailable($provider)) {
                error_log("Payment provider '{$preferredProvider}' unavailable, using fallback: {$provider}");
                return $provider;
            }
        }
        // If none configured, fail
        return null;
    }
    
    /**
     * Check if a payment provider is properly configured and available
     */
    public static function isProviderAvailable($provider) {
        switch ($provider) {
            case 'paymongo':
                return !empty(Config::get('PAYMONGO_SECRET_KEY')) 
                    && !empty(Config::get('PAYMONGO_PUBLIC_KEY'));
            case 'xendit':
                return !empty(Config::get('XENDIT_SECRET_KEY'))
                    && !empty(Config::get('XENDIT_PUBLIC_KEY'));
            default:
                return false;
        }
    }

    /**
     * Get display name for payment provider
     */
    public static function getProviderName($provider) {
        $names = [
            'paymongo' => 'PayMongo (Cards, GCash, PayMaya, QRPh)',
            'xendit' => 'Xendit (GCash, PayMaya, QR, etc.)',
        ];
        return $names[$provider] ?? $provider;
    }

    /**
     * Get all available payment providers
     */
    public static function getAvailableProviders() {
        $providers = [];
        $allProviders = ['paymongo', 'xendit'];
        foreach ($allProviders as $provider) {
            if (self::isProviderAvailable($provider)) {
                $providers[] = [
                    'id' => $provider,
                    'name' => self::getProviderName($provider),
                    'enabled' => true
                ];
            }
        }
        return $providers;
    }

    /**
     * Get payment provider configuration
     */
    public static function getProviderConfig($provider) {
        switch ($provider) {
            case 'paymongo':
                return [
                    'secret_key' => Config::get('PAYMONGO_SECRET_KEY'),
                    'public_key' => Config::get('PAYMONGO_PUBLIC_KEY'),
                    'test_mode' => Config::get('PAYMONGO_TEST_MODE', 'true') === 'true',
                    'api_url' => 'https://api.paymongo.com/v1'
                ];
            case 'xendit':
                return [
                    'secret_key' => Config::get('XENDIT_SECRET_KEY'),
                    'public_key' => Config::get('XENDIT_PUBLIC_KEY'),
                    'test_mode' => Config::get('XENDIT_TEST_MODE', 'true') === 'true',
                    'api_url' => 'https://api.xendit.co'
                ];
            default:
                return null;
        }
    }
}
?>