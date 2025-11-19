<?php
/**
 * Create Payment Handler: PayMongo → Xendit fallback only
 * Handles enrollment and payment creation for programs
 * Supports: PayMongo, Xendit (GCash/PayMaya/GrabPay/QR)
 */

session_start();
require_once 'dbConnection.php';
require_once 'payment-config.php';

header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
    exit;
}

$student_id = intval($_SESSION['userID']);
$program_id = intval($_POST['program_id'] ?? 0);
$preferred_provider = $_POST['payment_provider'] ?? null; // Optional: let user choose

// Validate program ID
if (!$program_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid program ID']);
    exit;
}

try {
    // Get program details
    $stmt = $conn->prepare("
        SELECT programID, title, price, currency, status 
        FROM programs 
        WHERE programID = ?
    ");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$program) {
        echo json_encode(['success' => false, 'message' => 'Program not found']);
        exit;
    }
    
    // Check program status
    $programStatus = strtolower(trim($program['status'] ?? ''));
    if ($programStatus !== 'published') {
        echo json_encode([
            'success' => false, 
            'message' => 'This program is not available for enrollment.'
        ]);
        exit;
    }
    
    // Check if already enrolled
    $checkStmt = $conn->prepare("
        SELECT enrollment_id 
        FROM student_program_enrollments 
        WHERE student_id = ? AND program_id = ?
    ");
    $checkStmt->bind_param("ii", $student_id, $program_id);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'You are already enrolled in this program']);
        exit;
    }
    
    $amount = floatval($program['price'] ?? 0);
    $currency = $program['currency'] ?? 'PHP';
    $title = $program['title'];
    
    // Handle FREE programs
    if ($amount <= 0) {
        $stmt = $conn->prepare("
            INSERT INTO student_program_enrollments 
            (student_id, program_id, enrollment_date, completion_percentage, last_accessed) 
            VALUES (?, ?, NOW(), 0.00, NOW())
        ");
        $stmt->bind_param("ii", $student_id, $program_id);
        $ok = $stmt->execute();
        $enrollmentId = $stmt->insert_id;
        $stmt->close();
        
        if ($ok) {
            echo json_encode([
                'success' => true,
                'free_enrollment' => true,
                'enrollment_id' => $enrollmentId,
                'redirect_url' => '../pages/student/student-program-view.php?program_id=' . $program_id,
                'message' => 'You have been successfully enrolled!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to enroll.']);
        }
        exit;
    }
    
    // Handle PAID programs
    $description = "Al-Ghaya LMS - " . $title;
    
    // Get student info
    $stmtStudent = $conn->prepare("SELECT fname, lname, email FROM user WHERE userID = ?");
    $stmtStudent->bind_param("i", $student_id);
    $stmtStudent->execute();
    $student = $stmtStudent->get_result()->fetch_assoc();
    $stmtStudent->close();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student account not found']);
        exit;
    }
    
    // Create metadata
    $metadata = [
        'student_id' => (string)$student_id,
        'student_name' => trim($student['fname'] . ' ' . $student['lname']),
        'student_email' => $student['email'],
        'program_id' => (string)$program_id,
        'program_title' => $title
    ];
    
    // Build URLs
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . '/al-ghaya';
    $successUrl = $baseUrl . '/php/payment-verify.php';
    $cancelUrl = $baseUrl . '/pages/student/student-program-view.php?program_id=' . $program_id . '&payment=cancelled';
    
    // Determine which payment provider to use
    $provider = $preferred_provider ?? PaymentConfig::getActiveProvider();
    $result = null;
    $paymentId = null;
    $checkoutUrl = null;
    $providersTried = [];
    $fallbackOrder = ['paymongo', 'xendit'];
    
    while (!$result || !$result['success']) {
        $providersTried[] = $provider;
        try {
            if ($provider === 'paymongo') {
                require_once 'paymongo-helper.php';
                $result = PayMongo::createCheckoutSession($amount, $description, $successUrl, $cancelUrl, $metadata);
                if ($result['success']) {
                    $session = $result['data']['data'];
                    $sessionId = $session['id'];
                    $checkoutUrl = $session['attributes']['checkout_url'];
                    $paymentId = PayMongo::createPaymentTransaction($student_id, $program_id, $amount, $currency, 'checkout_session', $sessionId);
                }
            } elseif ($provider === 'xendit') {
                require_once 'xendit-helper.php';
                $result = XenditHelper::createInvoice($amount, $description, $successUrl, $cancelUrl, $metadata);
                if ($result['success']) {
                    $invoice = $result['data'];
                    $invoiceId = $invoice['id'];
                    $checkoutUrl = $invoice['invoice_url'];
                    $paymentId = XenditHelper::createPaymentTransaction($student_id, $program_id, $amount, $invoiceId, 'invoice');
                }
            }
        } catch (Exception $e) {
            error_log("Payment provider '{$provider}' failed: " . $e->getMessage());
            $result = ['success' => false, 'error' => [$e->getMessage()]];
        }
        if (!$result['success']) {
            $nextProvider = null;
            foreach ($fallbackOrder as $p) {
                if (!in_array($p, $providersTried) && PaymentConfig::isProviderAvailable($p)) {
                    $nextProvider = $p;
                    break;
                }
            }
            if (!$nextProvider) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment processing is temporarily unavailable. Please try again later or contact support.',
                    'providers_tried' => $providersTried,
                    'error_details' => Config::get('APP_DEBUG', false) ? $result['error'] : null
                ]);
                exit;
            }
            $provider = $nextProvider;
        } else {
            break;
        }
    }

    // Store session info
    $_SESSION['pending_payment'] = [
        'payment_id' => $paymentId,
        'program_id' => $program_id,
        'provider' => $provider,
        'amount' => $amount,
        'currency' => $currency
    ];
    error_log("Payment created: Provider={$provider}, Payment ID={$paymentId}, Student={$student_id}, Program={$program_id}");
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkoutUrl,
        'payment_id' => $paymentId,
        'provider' => $provider,
        'provider_name' => PaymentConfig::getProviderName($provider),
        'amount' => $amount,
        'currency' => $currency
    ]);
} catch (Exception $e) {
    error_log("Payment creation error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred. Please try again later.',
        'error_details' => Config::get('APP_DEBUG', false) ? $e->getMessage() : null
    ]);
}
?>