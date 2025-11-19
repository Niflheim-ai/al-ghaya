<?php
require_once 'paymongo-config.php';
require_once 'dbConnection.php';

class PayMongo {
    
    // =============================================
    // PAYMONGO API METHODS
    // =============================================
    
    /**
     * Create QRPh Payment Source
     * Generates a QR code for InstaPay/PESONet payments
     */
    public static function createQRPhSource($amount, $description = '', $metadata = []) {
        $url = PAYMONGO_API_URL . '/sources';
        
        $attributes = [
            'type' => 'gcash',  // Use gcash as base (supports QR)
            'amount' => $amount * 100, // Convert to centavos
            'currency' => 'PHP',
            'redirect' => [
                'success' => Config::get('APP_URL', 'http://localhost/al-ghaya') . '/pages/student/payment-success.php',
                'failed' => Config::get('APP_URL', 'http://localhost/al-ghaya') . '/pages/student/payment-failed.php'
            ]
        ];
        
        if (!empty($description)) {
            $attributes['description'] = $description;
        }
        
        if (!empty($metadata)) {
            $attributes['metadata'] = $metadata;
        }
        
        $data = [
            'data' => [
                'attributes' => $attributes
            ]
        ];
        
        return self::makeRequest('POST', $url, $data);
    }

    /**
     * Create Checkout Session with multiple payment options including QRPh
     */
    public static function createCheckoutSession($amount, $description, $successUrl, $failedUrl, $metadata = []) {
        $url = PAYMONGO_API_URL . '/checkout_sessions';
        
        $attributes = [
            'send_email_receipt' => false,
            'show_description' => true,
            'show_line_items' => true,
            'line_items' => [
                [
                    'currency' => 'PHP',
                    'amount' => $amount * 100,
                    'description' => $description,
                    'name' => 'Al-Ghaya LMS - Program Enrollment',
                    'quantity' => 1
                ]
            ],
            'payment_method_types' => [
                'qrph',      // ✅ QRPh - InstaPay/PESONet QR codes
                'gcash',     // GCash
                'paymaya',   // PayMaya
                'grab_pay',  // GrabPay
                'card'       // Credit/Debit cards
            ],
            'success_url' => $successUrl,
            'cancel_url' => $failedUrl,
            'description' => $description
        ];
        
        if (!empty($metadata)) {
            $attributes['metadata'] = $metadata;
        }
        
        $data = [
            'data' => [
                'attributes' => $attributes
            ]
        ];
        
        return self::makeRequest('POST', $url, $data);
    }

    /**
     * Retrieve Source (for checking QRPh payment status)
     */
    public static function retrieveSource($sourceId) {
        $url = PAYMONGO_API_URL . "/sources/{$sourceId}";
        return self::makeRequest('GET', $url);
    }
    
    /**
     * Retrieve Checkout Session
     */
    public static function retrieveCheckoutSession($sessionId) {
        $url = PAYMONGO_API_URL . "/checkout_sessions/{$sessionId}";
        return self::makeRequest('GET', $url);
    }
    
    /**
     * Create Payment Intent
     */
    public static function createPaymentIntent($amount, $currency = 'PHP', $description = '', $metadata = []) {
        $url = PAYMONGO_API_URL . '/payment_intents';
        
        $attributes = [
            'amount' => $amount * 100,
            'payment_method_allowed' => ['card', 'gcash', 'paymaya'],
            'payment_method_options' => [
                'card' => ['request_three_d_secure' => 'any']
            ],
            'currency' => $currency,
            'description' => $description,
            'statement_descriptor' => 'Al-Ghaya LMS'
        ];
        
        if (!empty($metadata)) {
            $attributes['metadata'] = $metadata;
        }
        
        $data = [
            'data' => [
                'attributes' => $attributes
            ]
        ];
        
        return self::makeRequest('POST', $url, $data);
    }
    
    /**
     * Retrieve Payment Intent
     */
    public static function retrievePaymentIntent($intentId) {
        $url = PAYMONGO_API_URL . "/payment_intents/{$intentId}";
        return self::makeRequest('GET', $url);
    }
    
    /**
     * Make API Request to PayMongo
     * FIXED: Added User-Agent header and improved error handling for CloudFront 403 errors
     */
    private static function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        
        // ✅ FIX: Add User-Agent header to prevent CloudFront 403 errors
        $headers = [
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Al-Ghaya-LMS/1.0 (PHP/' . PHP_VERSION . ')'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // ✅ FIX: Always verify SSL in production, but allow testing in dev mode
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        // ✅ FIX: Set timeout to prevent hanging requests
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        // ✅ FIX: Follow redirects (CloudFront may redirect)
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                
                // ✅ Log request for debugging (remove in production)
                if (PAYMONGO_TEST_MODE) {
                    error_log("PayMongo API Request: {$url}");
                    error_log("Request Data: " . $jsonData);
                }
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        
        curl_close($ch);
        
        // ✅ Enhanced error logging
        if ($curlError) {
            error_log("PayMongo cURL Error ({$curlErrno}): {$curlError}");
            return [
                'success' => false, 
                'error' => ['cURL error: ' . $curlError],
                'error_type' => 'curl_error',
                'http_code' => $httpCode
            ];
        }
        
        // ✅ Log full response for debugging 403 errors
        if ($httpCode === 403) {
            error_log("PayMongo 403 Error - Full Response: " . substr($response, 0, 500));
            error_log("Request URL: {$url}");
            error_log("Request Method: {$method}");
            
            return [
                'success' => false,
                'error' => [
                    'CloudFront blocked the request. Possible causes:',
                    '1. Invalid or test API keys being used in production',
                    '2. API key doesn\'t have required permissions',
                    '3. Request blocked by PayMongo\'s security',
                    'Please verify your API keys in the .env file'
                ],
                'http_code' => 403,
                'error_type' => 'cloudfront_blocked'
            ];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $result];
        } else {
            // ✅ Better error formatting
            $errorMessage = 'Unknown error occurred';
            $errors = [];
            
            if (is_array($result) && isset($result['errors'])) {
                $errors = $result['errors'];
            } elseif (is_string($response)) {
                $errors = [$response];
            }
            
            error_log("PayMongo API Error (HTTP {$httpCode}): " . json_encode($errors));
            
            return [
                'success' => false, 
                'error' => $errors,
                'http_code' => $httpCode,
                'error_type' => 'api_error'
            ];
        }
    }
    
    /**
     * Format amount for display (centavos to PHP)
     */
    public static function formatAmount($centavos) {
        return number_format($centavos / 100, 2);
    }
    
    /**
     * Get payment status from checkout session
     */
    public static function getPaymentStatus($sessionId) {
        $result = self::retrieveCheckoutSession($sessionId);
        
        if ($result['success']) {
            return $result['data']['data']['attributes']['payment_intent']['attributes']['status'] ?? 'unknown';
        }
        
        return 'error';
    }
    
    // =============================================
    // DATABASE FUNCTIONS
    // =============================================

    /**
     * Create a new payment transaction in database
     */
    public static function createPaymentTransaction($studentId, $programId, $amount, $currency, $method, $sessionOrSourceId) {
        global $conn;
        
        $sessionId = ($method === 'checkout_session') ? $sessionOrSourceId : null;
        $sourceId = ($method === 'qrph' || $method === 'gcash') ? $sessionOrSourceId : null;
        
        $stmt = $conn->prepare("
            INSERT INTO payment_transactions 
            (student_id, program_id, amount, currency, payment_provider, payment_method, 
            payment_session_id, payment_source_id, status, dateCreated) 
            VALUES (?, ?, ?, ?, 'paymongo', ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->bind_param("iidssss", $studentId, $programId, $amount, $currency, $method, $sessionId, $sourceId);
        $stmt->execute();
        $paymentId = $stmt->insert_id;
        $stmt->close();
        
        return $paymentId;
    }

    /**
     * Update payment status in database
     */
    public static function updatePaymentStatus($paymentId, $status, $paymentDetails = null) {
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
        
        $detailsJson = $paymentDetails ? json_encode($paymentDetails) : null;
        $stmt->bind_param("ssi", $status, $detailsJson, $paymentId);
        $ok = $stmt->execute();
        $stmt->close();
        
        return $ok;
    }

    /**
     * Enroll student in program (uses your existing student_program_enrollments table)
     */
    public static function enrollStudentInProgram($studentId, $programId, $paymentId = null) {
        global $conn;
        
        // Check if already enrolled
        $checkStmt = $conn->prepare("
            SELECT enrollment_id 
            FROM student_program_enrollments 
            WHERE student_id = ? AND program_id = ?
        ");
        $checkStmt->bind_param("ii", $studentId, $programId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if ($existing) {
            // Already enrolled - return existing enrollment ID
            return $existing['enrollment_id'];
        }
        
        // Create new enrollment
        $stmt = $conn->prepare("
            INSERT INTO student_program_enrollments 
            (student_id, program_id, enrollment_date, completion_percentage, last_accessed) 
            VALUES (?, ?, NOW(), 0.00, NOW())
        ");
        $stmt->bind_param("ii", $studentId, $programId);
        $ok = $stmt->execute();
        $enrollmentId = $stmt->insert_id;
        $stmt->close();
        
        if ($ok && $paymentId) {
            // Link payment to enrollment if payment_id column exists in your table
            // Note: your current table doesn't have payment_id, so we'll skip this
            // If you want to track payments, add a payment_id column to student_program_enrollments
        }
        
        return $enrollmentId;
    }

    /**
     * Check if student is enrolled in program
     */
    public static function isStudentEnrolled($studentId, $programId) {
        global $conn;
        
        $stmt = $conn->prepare("
            SELECT enrollment_id 
            FROM student_program_enrollments 
            WHERE student_id = ? AND program_id = ?
        ");
        $stmt->bind_param("ii", $studentId, $programId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return (bool)$result;
    }

    /**
     * Get payment by source ID
     */
    public static function getPaymentBySourceId($sourceId) {
        global $conn;
        
        $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE payment_source_id = ? LIMIT 1");
        $stmt->bind_param("s", $sourceId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }

    /**
     * Get payment by session ID
     */
    public static function getPaymentBySessionId($sessionId) {
        global $conn;
        
        $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE payment_session_id = ? LIMIT 1");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
}
?>