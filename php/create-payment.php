<?php
/**
 * Create Payment Handler
 * Handles enrollment and payment creation for programs
 * Supports both free and paid enrollments
 */

session_start();
require_once 'dbConnection.php';
require_once 'paymongo-helper.php';

header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
    exit;
}

$student_id = intval($_SESSION['userID']);
$program_id = intval($_POST['program_id'] ?? 0);

// Validate program ID
if (!$program_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid program ID']);
    exit;
}

try {
    // Check if already enrolled
    if (PayMongo::isStudentEnrolled($student_id, $program_id)) {
        echo json_encode(['success' => false, 'message' => 'You are already enrolled in this program']);
        exit;
    }
    
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
    
    // Check program status - only allow 'published' programs
    $programStatus = strtolower(trim($program['status'] ?? ''));
    
    if ($programStatus !== 'published') {
        error_log("Enrollment blocked: Program {$program_id} status is '{$programStatus}', expected 'published'");
        echo json_encode([
            'success' => false, 
            'message' => 'This program is not available for enrollment. Please contact support.'
        ]);
        exit;
    }
    
    $amount = floatval($program['price'] ?? 0);
    $currency = $program['currency'] ?? 'PHP';
    $title = $program['title'];
    
    // Handle FREE programs (price = 0 or NULL)
    if ($amount <= 0) {
        // Enroll student directly without payment
        $enrollmentId = PayMongo::enrollStudentInProgram($student_id, $program_id, null);
        
        if ($enrollmentId) {
            error_log("Free enrollment: Student {$student_id} enrolled in program {$program_id}");
            echo json_encode([
                'success' => true,
                'free_enrollment' => true,
                'enrollment_id' => $enrollmentId,
                'redirect_url' => 'student-program-view.php?program_id=' . $program_id,
                'message' => 'You have been successfully enrolled!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to enroll. Please try again.'
            ]);
        }
        exit;
    }
    
    // Handle PAID programs
    $description = "Al-Ghaya LMS - " . $title;
    
    // Get student info
    $stmtStudent = $conn->prepare("
        SELECT fname, lname, email 
        FROM user 
        WHERE userID = ?
    ");
    $stmtStudent->bind_param("i", $student_id);
    $stmtStudent->execute();
    $student = $stmtStudent->get_result()->fetch_assoc();
    $stmtStudent->close();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student account not found']);
        exit;
    }
    
    // Create metadata for tracking
    $metadata = [
        'student_id' => (string)$student_id,
        'student_name' => trim($student['fname'] . ' ' . $student['lname']),
        'student_email' => $student['email'],
        'program_id' => (string)$program_id,
        'program_title' => $title
    ];
    
    // ✅ FIXED: Correct URL path (student/pages not pages/student)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . '/al-ghaya';
    
    $successUrl = $baseUrl . '/php/payment-verify.php';  // ✅ Changed to payment-verify.php
    $cancelUrl = $baseUrl . '../pages/student/student-program-view.php?program_id=' . $program_id . '&payment=cancelled';
    
    // Create PayMongo checkout session
    $result = PayMongo::createCheckoutSession($amount, $description, $successUrl, $cancelUrl, $metadata);
    
    if ($result['success']) {
        $session = $result['data']['data'];
        $sessionId = $session['id'];
        $checkoutUrl = $session['attributes']['checkout_url'];
        
        // Save payment record to database
        $paymentId = PayMongo::createPaymentTransaction(
            $student_id, 
            $program_id, 
            $amount, 
            $currency, 
            'checkout_session', 
            $sessionId
        );
        
        // ✅ ADDED: Store session info in PHP session for verification page
        $_SESSION['pending_payment'] = [
            'session_id' => $sessionId,
            'program_id' => $program_id,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'currency' => $currency
        ];
        
        error_log("Payment session created: Session ID {$sessionId}, Payment ID {$paymentId}");
        
        echo json_encode([
            'success' => true,
            'checkout_url' => $checkoutUrl,
            'session_id' => $sessionId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'currency' => $currency
        ]);
        
    } else {
        // Payment session creation failed
        $errorMsg = 'Failed to create payment session. Please try again.';
        $errorDetails = $result['error'] ?? 'Unknown error';
        
        error_log("PayMongo checkout creation failed for program {$program_id}: " . print_r($result, true));
        
        echo json_encode([
            'success' => false, 
            'message' => $errorMsg,
            'error_details' => Config::get('APP_DEBUG', false) ? $errorDetails : null
        ]);
    }
    
} catch (Exception $e) {
    // Catch any unexpected errors
    error_log("Payment creation error for program {$program_id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred. Please try again later.',
        'error_details' => Config::get('APP_DEBUG', false) ? $e->getMessage() : null
    ]);
}
?>
