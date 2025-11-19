<?php
/**
 * PayMongo API Connection Test
 * 
 * This script tests your PayMongo API connection and helps diagnose issues.
 * 
 * Usage:
 * 1. Make sure your .env file has valid PayMongo API keys
 * 2. Navigate to: http://localhost/al-ghaya/php/test-paymongo.php
 * 3. Check the output for connection status and errors
 */

require_once 'paymongo-config.php';

// Prevent running in production
if (!PAYMONGO_TEST_MODE) {
    die('This test script can only run in TEST MODE. Set PAYMONGO_TEST_MODE=true in .env');
}

// Output as plain text for readability
header('Content-Type: text/plain; charset=utf-8');

echo "========================================\n";
echo "PayMongo API Connection Test\n";
echo "========================================\n\n";

// Test 1: Check if API keys are configured
echo "[TEST 1] Checking API Keys Configuration\n";
echo "----------------------------------------\n";

if (empty(PAYMONGO_SECRET_KEY)) {
    echo "\u274c FAILED: PAYMONGO_SECRET_KEY is not set in .env file\n";
    exit(1);
}

if (empty(PAYMONGO_PUBLIC_KEY)) {
    echo "\u274c FAILED: PAYMONGO_PUBLIC_KEY is not set in .env file\n";
    exit(1);
}

// Check if keys look valid (should start with sk_ or pk_)
$secretKeyPrefix = substr(PAYMONGO_SECRET_KEY, 0, 3);
$publicKeyPrefix = substr(PAYMONGO_PUBLIC_KEY, 0, 3);

if ($secretKeyPrefix === 'sk_') {
    echo "\u2705 Secret Key format looks correct (starts with 'sk_')\n";
    
    // Check if it's test or live key
    if (strpos(PAYMONGO_SECRET_KEY, 'sk_test_') === 0) {
        echo "\u2139\ufe0f  Using TEST Secret Key\n";
    } elseif (strpos(PAYMONGO_SECRET_KEY, 'sk_live_') === 0) {
        echo "\u26a0\ufe0f  Using LIVE Secret Key (real payments will be charged!)\n";
    }
} else {
    echo "\u274c WARNING: Secret Key doesn't start with 'sk_' - format may be incorrect\n";
}

if ($publicKeyPrefix === 'pk_') {
    echo "\u2705 Public Key format looks correct (starts with 'pk_')\n";
    
    if (strpos(PAYMONGO_PUBLIC_KEY, 'pk_test_') === 0) {
        echo "\u2139\ufe0f  Using TEST Public Key\n";
    } elseif (strpos(PAYMONGO_PUBLIC_KEY, 'pk_live_') === 0) {
        echo "\u26a0\ufe0f  Using LIVE Public Key\n";
    }
} else {
    echo "\u274c WARNING: Public Key doesn't start with 'pk_' - format may be incorrect\n";
}

echo "\n";

// Test 2: Test checkout session creation (the actual integration)
echo "[TEST 2] Testing Checkout Session Creation\n";
echo "----------------------------------------\n";
echo "This tests the actual PayMongo API that Al-Ghaya uses.\n\n";

$testAmount = 100; // 100 PHP
$testData = [
    'data' => [
        'attributes' => [
            'send_email_receipt' => false,
            'show_description' => true,
            'show_line_items' => true,
            'line_items' => [
                [
                    'currency' => 'PHP',
                    'amount' => $testAmount * 100, // 10,000 centavos = 100 PHP
                    'description' => 'Test Payment - Al-Ghaya LMS',
                    'name' => 'Test Enrollment',
                    'quantity' => 1
                ]
            ],
            'payment_method_types' => ['gcash', 'card'],
            'success_url' => 'http://localhost/al-ghaya/test-success.php',
            'cancel_url' => 'http://localhost/al-ghaya/test-cancel.php',
            'description' => 'Test checkout session'
        ]
    ]
];

$ch = curl_init();
$url = PAYMONGO_API_URL . '/checkout_sessions';

$headers = [
    'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: Al-Ghaya-LMS-Test/1.0 (PHP/' . PHP_VERSION . ')'
];

echo "Sending request to: {$url}\n";
echo "Testing with amount: \u20b1100.00 PHP\n\n";

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);

curl_close($ch);

if ($curlError) {
    echo "\u274c cURL Error ({$curlErrno}): {$curlError}\n";
    echo "\n";
    echo "Possible solutions:\n";
    echo "- Check your internet connection\n";
    echo "- Verify SSL certificates are properly configured\n";
    echo "- Check if your firewall is blocking outbound HTTPS requests\n";
    exit(1);
}

echo "HTTP Status Code: {$httpCode}\n\n";

if ($httpCode === 200 || $httpCode === 201) {
    echo "\u2705 SUCCESS: Checkout session created successfully!\n";
    echo "\n========================================\n";
    echo "\ud83c\udf89 ALL TESTS PASSED!\n";
    echo "========================================\n";
    echo "\nYour PayMongo integration is fully functional.\n";
    echo "You can now process payments through Al-Ghaya LMS.\n";
    echo "\nWhat this means:\n";
    echo "- API keys are valid and working\n";
    echo "- CloudFront 403 error is FIXED\n";
    echo "- Checkout sessions can be created\n";
    echo "- Students can enroll in paid programs\n";
    echo "\nNext steps:\n";
    echo "1. Test the enrollment flow in your LMS\n";
    echo "2. Try enrolling in a paid program as a student\n";
    echo "3. Complete a test payment using test credentials\n";
    
} elseif ($httpCode === 401) {
    echo "\u274c FAILED: Unauthorized (401)\n";
    echo "\nYour API key is invalid or incorrect.\n";
    echo "\nSolutions:\n";
    echo "1. Verify your PAYMONGO_SECRET_KEY in the .env file\n";
    echo "2. Make sure you copied the entire key including 'sk_test_' or 'sk_live_' prefix\n";
    echo "3. Check if the key is still active in your PayMongo dashboard\n";
    echo "4. Generate a new API key from: https://dashboard.paymongo.com/developers\n";
    echo "\n--- Response Details ---\n";
    echo substr($response, 0, 500);
    
} elseif ($httpCode === 403) {
    echo "\u274c FAILED: Forbidden (403) - CloudFront Error\n";
    echo "\nCloudFront is still blocking your requests.\n";
    echo "\nCommon causes:\n";
    echo "1. Using test API keys in a production environment\n";
    echo "2. API key doesn't have the required permissions\n";
    echo "3. Your PayMongo account needs verification\n";
    echo "4. IP address or domain restrictions on your PayMongo account\n";
    echo "\nSolutions:\n";
    echo "1. Log in to PayMongo dashboard: https://dashboard.paymongo.com/\n";
    echo "2. Check if your account is verified and activated\n";
    echo "3. Verify you're using the correct API keys for your environment\n";
    echo "4. If using test mode, ensure your account has test mode enabled\n";
    echo "5. Generate NEW API keys from: https://dashboard.paymongo.com/developers\n";
    echo "6. Contact PayMongo support at support@paymongo.com\n";
    echo "\n--- Response Details ---\n";
    echo substr($response, 0, 500);
    
} elseif ($httpCode === 400) {
    echo "\u274c FAILED: Bad Request (400)\n";
    echo "\nThe request data is invalid.\n";
    echo "This usually means there's an issue with the checkout session parameters.\n";
    echo "\n--- Response Details ---\n";
    $decoded = json_decode($response, true);
    if ($decoded && isset($decoded['errors'])) {
        echo "Errors:\n";
        foreach ($decoded['errors'] as $error) {
            echo "- " . print_r($error, true) . "\n";
        }
    } else {
        echo substr($response, 0, 500);
    }
    
} else {
    echo "\u274c FAILED: Unexpected HTTP status code {$httpCode}\n";
    echo "\n--- Response Details ---\n";
    echo substr($response, 0, 500);
}

echo "\n\n========================================\n";
echo "Test Complete\n";
echo "========================================\n";

if ($httpCode === 403) {
    echo "\n\u26a0\ufe0f  IMPORTANT NEXT STEPS:\n";
    echo "\n1. Check your PayMongo dashboard at https://dashboard.paymongo.com/\n";
    echo "2. Verify your account is activated and verified\n";
    echo "3. Generate NEW API keys\n";
    echo "4. Update your .env file with the NEW keys\n";
    echo "5. Restart Apache in XAMPP\n";
    echo "6. Re-run this test script to verify the fix\n";
}

if ($httpCode === 401) {
    echo "\n\u26a0\ufe0f  NEXT STEPS:\n";
    echo "\n1. Go to https://dashboard.paymongo.com/developers\n";
    echo "2. Copy your Secret Key (starts with sk_test_)\n";
    echo "3. Update PAYMONGO_SECRET_KEY in your .env file\n";
    echo "4. Restart Apache in XAMPP\n";
    echo "5. Re-run this test script\n";
}
?>