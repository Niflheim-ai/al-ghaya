<?php
/**
 * Multi-Provider Payment Configuration
 * Supports: PayMongo, PayPal, Stripe, Manual Bank Transfer
 */

require_once __DIR__ . '/config.php';

class PaymentConfig {
    
    /**
     * Get active payment provider based on configuration priority
     * Returns: 'paymongo', 'paypal', 'stripe', or 'manual'
     */
    public static function getActiveProvider() {
        $preferredProvider = Config::get('PAYMENT_PROVIDER', 'paymongo');
        
        // Check if preferred provider is available
        if (self::isProviderAvailable($preferredProvider)) {
            return $preferredProvider;
        }
        
        // Fallback chain: PayMongo -> PayPal -> Stripe -> Manual
        $fallbackOrder = ['paymongo', 'paypal', 'stripe', 'manual'];
        
        foreach ($fallbackOrder as $provider) {
            if (self::isProviderAvailable($provider)) {
                error_log("Payment provider '{$preferredProvider}' unavailable, using fallback: {$provider}");
                return $provider;
            }
        }
        
        // Last resort: manual payment
        return 'manual';
    }
    
    /**
     * Check if a payment provider is properly configured and available
     */
    public static function isProviderAvailable($provider) {
        switch ($provider) {
            case 'paymongo':
                return !empty(Config::get('PAYMONGO_SECRET_KEY')) 
                    && !empty(Config::get('PAYMONGO_PUBLIC_KEY'));
                
            case 'paypal':
                return !empty(Config::get('PAYPAL_CLIENT_ID')) 
                    && !empty(Config::get('PAYPAL_CLIENT_SECRET'));
                
            case 'stripe':
                return !empty(Config::get('STRIPE_SECRET_KEY')) 
                    && !empty(Config::get('STRIPE_PUBLIC_KEY'));
                
            case 'manual':
                // Manual payment is always available
                return true;
                
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
            'paypal' => 'PayPal',
            'stripe' => 'Stripe (Cards)',
            'manual' => 'Bank Transfer (Manual)'
        ];
        
        return $names[$provider] ?? $provider;
    }
    
    /**
     * Get all available payment providers
     */
    public static function getAvailableProviders() {
        $providers = [];
        $allProviders = ['paymongo', 'paypal', 'stripe', 'manual'];
        
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
                
            case 'paypal':
                return [
                    'client_id' => Config::get('PAYPAL_CLIENT_ID'),
                    'client_secret' => Config::get('PAYPAL_CLIENT_SECRET'),
                    'test_mode' => Config::get('PAYPAL_TEST_MODE', 'true') === 'true',
                    'api_url' => Config::get('PAYPAL_TEST_MODE', 'true') === 'true' 
                        ? 'https://api-m.sandbox.paypal.com' 
                        : 'https://api-m.paypal.com'
                ];
                
            case 'stripe':
                return [
                    'secret_key' => Config::get('STRIPE_SECRET_KEY'),
                    'public_key' => Config::get('STRIPE_PUBLIC_KEY'),
                    'test_mode' => Config::get('STRIPE_TEST_MODE', 'true') === 'true',
                    'api_url' => 'https://api.stripe.com/v1'
                ];
                
            case 'manual':
                return [
                    'bank_name' => Config::get('BANK_NAME', 'Not configured'),
                    'account_name' => Config::get('BANK_ACCOUNT_NAME', 'Not configured'),
                    'account_number' => Config::get('BANK_ACCOUNT_NUMBER', 'Not configured'),
                    'instructions' => Config::get('BANK_TRANSFER_INSTRUCTIONS', 
                        'Please transfer to the bank account details shown and upload proof of payment.')
                ];
                
            default:
                return null;
        }
    }
}
?>