<?php
session_start();
require_once '../../php/dbConnection.php';
require_once '../../php/paymongo-helper.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header('Location: ../pages/login.php');
    exit;
}

$student_id = intval($_SESSION['userID']);
$program_id = intval($_GET['program_id'] ?? 0);
$session_id = $_GET['session_id'] ?? '';

error_log("Payment Success Page: Student ID = {$student_id}, Program ID = {$program_id}, Session ID = {$session_id}");

if (!$program_id) {
    error_log("No program_id provided, redirecting to programs");
    header('Location: student-programs.php');
    exit;
}

$enrollmentSuccess = false;
$programTitle = '';
$debugInfo = [];

try {
    // If session_id is provided, verify the payment
    if ($session_id) {
        error_log("Retrieving checkout session: {$session_id}");
        $result = PayMongo::retrieveCheckoutSession($session_id);
        
        $debugInfo['checkout_result'] = $result;
        
        if ($result['success']) {
            $session = $result['data']['data'];
            $paymentStatus = $session['attributes']['payment_intent']['attributes']['status'] ?? 'unknown';
            
            error_log("Payment status from PayMongo: {$paymentStatus}");
            $debugInfo['payment_status'] = $paymentStatus;
            
            if ($paymentStatus === 'succeeded' || $paymentStatus === 'awaiting_payment_method' || $paymentStatus === 'processing') {
                // Get payment record
                $payment = PayMongo::getPaymentBySessionId($session_id);
                
                error_log("Payment record from DB: " . print_r($payment, true));
                $debugInfo['payment_record'] = $payment;
                
                if ($payment) {
                    if ($payment['status'] !== 'paid') {
                        // Update payment status
                        $updated = PayMongo::updatePaymentStatus($payment['payment_id'], 'paid', $session['attributes']);
                        error_log("Payment status updated: " . ($updated ? 'yes' : 'no'));
                        $debugInfo['payment_updated'] = $updated;
                    }
                    
                    // Enroll student
                    error_log("Enrolling student {$student_id} in program {$program_id}");
                    $enrollmentId = PayMongo::enrollStudentInProgram($student_id, $program_id, $payment['payment_id']);
                    
                    error_log("Enrollment ID: {$enrollmentId}");
                    $debugInfo['enrollment_id'] = $enrollmentId;
                    
                    $enrollmentSuccess = (bool)$enrollmentId;
                } else {
                    error_log("ERROR: Payment record not found in database");
                    $debugInfo['error'] = 'Payment record not found';
                    
                    // Check if already enrolled
                    $enrollmentSuccess = PayMongo::isStudentEnrolled($student_id, $program_id);
                    error_log("Already enrolled check: " . ($enrollmentSuccess ? 'yes' : 'no'));
                }
            } else {
                error_log("Payment status is not successful: {$paymentStatus}");
                $debugInfo['error'] = "Payment status: {$paymentStatus}";
            }
        } else {
            error_log("Failed to retrieve checkout session: " . print_r($result, true));
            $debugInfo['error'] = 'Failed to retrieve session';
        }
    } else {
        error_log("No session_id, checking if already enrolled");
        // No session_id - check if already enrolled (for free programs)
        $enrollmentSuccess = PayMongo::isStudentEnrolled($student_id, $program_id);
        $debugInfo['no_session'] = true;
        $debugInfo['already_enrolled'] = $enrollmentSuccess;
    }
    
    // Get program details
    $stmt = $conn->prepare("SELECT title FROM programs WHERE programID = ?");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $programTitle = $program['title'] ?? 'the program';
    
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    $debugInfo['exception'] = $e->getMessage();
}

error_log("Final enrollment success status: " . ($enrollmentSuccess ? 'TRUE' : 'FALSE'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $enrollmentSuccess ? 'Payment Successful' : 'Enrollment Failed' ?> - Al-Ghaya LMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #A58618 0%, #10375B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            animation: scaleIn 0.5s ease-out;
        }
        .error-icon {
            background: #ef4444;
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        h1 {
            color: #10b981;
            font-size: 32px;
            margin-bottom: 15px;
        }
        h1.error {
            color: #ef4444;
        }
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .program-name {
            font-size: 20px;
            color: #333;
            font-weight: bold;
            margin: 20px 0;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 10px;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #A58618;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 30px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #8a6f15;
        }
        .debug {
            margin-top: 30px;
            padding: 20px;
            background: #f3f4f6;
            border-radius: 10px;
            text-align: left;
            font-size: 12px;
        }
        .debug pre {
            overflow-x: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($enrollmentSuccess): ?>
            <div class="success-icon">✓</div>
            <h1>Payment Successful!</h1>
            <p>Thank you for your payment. You have been successfully enrolled in:</p>
            <div class="program-name"><?= htmlspecialchars($programTitle) ?></div>
            <p>You can now start learning immediately!</p>
            <a href="student-program-view.php?program_id=<?= $program_id ?>" class="btn">Start Learning</a>
        <?php else: ?>
            <div class="success-icon error-icon">✗</div>
            <h1 class="error">Enrollment Failed</h1>
            <p>There was an issue processing your enrollment.</p>
            
            <!-- Debug Info -->
            <div class="debug">
                <strong>Debug Information:</strong>
                <pre><?= print_r($debugInfo, true) ?></pre>
                <p><strong>Session ID:</strong> <?= htmlspecialchars($session_id) ?></p>
                <p><strong>Program ID:</strong> <?= $program_id ?></p>
                <p><strong>Student ID:</strong> <?= $student_id ?></p>
            </div>
            
            <a href="student-programs.php" class="btn">Back to Programs</a>
        <?php endif; ?>
    </div>
</body>
</html>
