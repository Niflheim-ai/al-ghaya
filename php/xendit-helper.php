<?php
/**
 * Xendit Payment Integration
 * Supports: GCash, PayMaya, GrabPay, QR Codes (InstaPay/PESONet)
 * 
 * Xendit is a popular payment gateway in the Philippines
 * More reliable than PayMongo for Filipino payment methods
 */

require_once 'payment-config.php';
require_once 'dbConnection.php';

class XenditHelper {
    
    private static function getConfig() {
        return [
            'secret_key' => Config::get('XENDIT_SECRET_KEY'),
            'public_key' => Config::get('XENDIT_PUBLIC_KEY'),
            'test_mode' => Config::get('XENDIT_TEST_MODE', 'true') === 'true',
            'api_url' => 'https://api.xendit.co'
        ];
    }
    
    /**
     * Make API request to Xendit
     */
    private static function makeRequest($method, $endpoint, $data = null) {
        $config = self::getConfig();
        $url = $config['api_url'] . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Basic ' . base64_encode($config['secret_key'] . ':'),
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Al-Ghaya-LMS/1.0 (PHP/' . PHP_VERSION . ')'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Xendit cURL Error: {$curlError}");
            return [
                'success' => false,
                'error' => ['Connection error: ' . $curlError]
            ];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $result];
        }
        
        error_log("Xendit API Error (HTTP {$httpCode}): {$response}");
        return [
            'success' => false,
            'error' => $result['error_code'] ?? ['Payment gateway error'],
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Create Invoice (supports GCash, PayMaya, GrabPay, QR, etc.)
     * This is the easiest way to accept multiple payment methods
     */
    public static function createInvoice($amount, $description, $successUrl, $failureUrl, $metadata = []) {
        $studentId = $metadata['student_id'] ?? '';
        $studentEmail = $metadata['student_email'] ?? '';
        $studentName = $metadata['student_name'] ?? 'Student';
        $programId = $metadata['program_id'] ?? '';
        
        $data = [
            'external_id' => 'alghaya-' . $programId . '-' . $studentId . '-' . time(),
            'amount' => $amount,
            'description' => $description,
            'invoice_duration' => 86400, // 24 hours
            'currency' => 'PHP',
            'reminder_time' => 1,
            'success_redirect_url' => $successUrl,
            'failure_redirect_url' => $failureUrl,
            'payment_methods' => [
                'GCASH',
                'PAYMAYA',
                'GRABPAY',
                'SHOPEEPAY',
                'PH_PROMPT_PAY', // QR Code
                'CREDIT_CARD',
                'DEBIT_CARD'
            ],
            'customer' => [
                'given_names' => $studentName,
                'email' => $studentEmail
            ],
            'customer_notification_preference' => [
                'invoice_created' => ['email'],
                'invoice_reminder' => ['email'],
                'invoice_paid' => ['email']
            ],
            'items' => [[
                'name' => $description,
                'quantity' => 1,
                'price' => $amount,
                'category' => 'Education'
            ]]
        ];
        
        return self::makeRequest('POST', '/v2/invoices', $data);
    }
    
    /**
     * Create E-Wallet Charge (for direct GCash/PayMaya/GrabPay payment)
     */
    public static function createEWalletCharge($amount, $type, $successUrl, $failureUrl, $metadata = []) {
        // Type can be: GCASH, PAYMAYA, GRABPAY
        $data = [
            'reference_id' => 'alg-' . $metadata['program_id'] . '-' . $metadata['student_id'] . '-' . time(),
            'currency' => 'PHP',
            'amount' => $amount,
            'checkout_method' => 'ONE_TIME_PAYMENT',
            'channel_code' => $type,
            'channel_properties' => [
                'success_redirect_url' => $successUrl,
                'failure_redirect_url' => $failureUrl,
                'cancel_redirect_url' => $failureUrl
            ],
            'metadata' => $metadata
        ];
        
        return self::makeRequest('POST', '/ewallets/charges', $data);
    }
    
    /**
     * Create QR Code (InstaPay/PESONet)
     */
    public static function createQRCode($amount, $description, $metadata = []) {
        $data = [
            'external_id' => 'qr-' . $metadata['program_id'] . '-' . $metadata['student_id'] . '-' . time(),
            'type' => 'DYNAMIC',
            'callback_url' => Config::get('APP_URL', 'http://localhost/al-ghaya') . '/php/xendit-callback.php',
            'amount' => $amount,
            'currency' => 'PHP',
            'description' => $description,
            'metadata' => $metadata
        ];
        
        return self::makeRequest('POST', '/qr_codes', $data);
    }
    
    /**
     * Get Invoice details
     */
    public static function getInvoice($invoiceId) {
        return self::makeRequest('GET', '/v2/invoices/' . $invoiceId);
    }
    
    /**
     * Create payment transaction in database
     */
    public static function createPaymentTransaction($studentId, $programId, $amount, $invoiceId, $paymentType = 'invoice') {
        global $conn;
        
        $stmt = $conn->prepare("
            INSERT INTO payment_transactions 
            (student_id, program_id, amount, currency, payment_provider, payment_method, 
            payment_session_id, status, dateCreated) 
            VALUES (?, ?, ?, 'PHP', 'xendit', ?, ?, 'pending', NOW())
        ");
        
        $stmt->bind_param("iidss", $studentId, $programId, $amount, $paymentType, $invoiceId);
        $stmt->execute();
        $paymentId = $stmt->insert_id;
        $stmt->close();
        
        return $paymentId;
    }
    
    /**
     * Update payment status
     */
    public static function updatePaymentStatus($paymentId, $status, $details = null) {
        global $conn;
        
        if ($status === 'paid') {
            $stmt = $conn->prepare("
                UPDATE payment_transactions 
                SET status = ?, payment_details = ?, datePaid = NOW() 
                WHERE payment_id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE payment_transactions 
                SET status = ?, payment_details = ? 
                WHERE payment_id = ?
            ");
        }
        
        $detailsJson = $details ? json_encode($details) : null;
        $stmt->bind_param("ssi", $status, $detailsJson, $paymentId);
        $ok = $stmt->execute();
        $stmt->close();
        
        return $ok;
    }
    
    /**
     * Get payment by invoice ID
     */
    public static function getPaymentByInvoiceId($invoiceId) {
        global $conn;
        
        $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE payment_session_id = ? LIMIT 1");
        $stmt->bind_param("s", $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
}
?>