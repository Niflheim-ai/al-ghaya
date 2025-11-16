<?php
session_start();
require_once 'dbConnection.php';
require_once 'paymongo-helper.php';

// Check if user is logged in
if (!isset($_SESSION['userID']) || $_SESSION['role'] !== 'student') {
    header('Location: ../pages/login.php');
    exit;
}

$student_id = intval($_SESSION['userID']);

// Get pending payment from session
$pendingPayment = $_SESSION['pending_payment'] ?? null;

if (!$pendingPayment) {
    header('Location: ../pages/student/student-programs.php');
    exit;
}

$session_id = $pendingPayment['session_id'];
$program_id = $pendingPayment['program_id'];
$payment_id = $pendingPayment['payment_id'];

// Clear pending payment
unset($_SESSION['pending_payment']);

$enrollmentSuccess = false;
$programTitle = '';
$paymentStatus = 'unknown';

try {
    // Verify payment with PayMongo
    $result = PayMongo::retrieveCheckoutSession($session_id);
    
    if ($result['success']) {
        $session = $result['data']['data'];
        $paymentStatus = $session['attributes']['payment_intent']['attributes']['status'] ?? 'unknown';
        
        if ($paymentStatus === 'succeeded' || $paymentStatus === 'processing' || $paymentStatus === 'awaiting_payment_method') {
            // Update payment status in database
            PayMongo::updatePaymentStatus($payment_id, 'paid', $session['attributes']);
            
            // Enroll student
            $enrollmentId = PayMongo::enrollStudentInProgram($student_id, $program_id, $payment_id);
            
            $enrollmentSuccess = (bool)$enrollmentId;
            
            error_log("Enrollment successful: Student {$student_id}, Program {$program_id}, Enrollment {$enrollmentId}");
        }
    }
    
    // Get program details
    $stmt = $conn->prepare("SELECT title FROM programs WHERE programID = ?");
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $programTitle = $program['title'] ?? 'the program';
    
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $enrollmentSuccess ? 'Payment Successful' : 'Payment Failed' ?> - Al-Ghaya LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#A58618',
                        secondary: '#10375B',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .animate-scale-in {
            animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary via-secondary to-primary/90 flex items-center justify-center p-4">
    
    <div class="w-full max-w-2xl">
        <?php if ($enrollmentSuccess): ?>
            <!-- Success State -->
            <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 text-center">
                <!-- Success Icon -->
                <div class="w-24 h-24 md:w-32 md:h-32 mx-auto mb-6 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center animate-scale-in shadow-lg">
                    <i class="ph-fill ph-check text-white text-5xl md:text-6xl"></i>
                </div>
                
                <!-- Success Message -->
                <h1 class="text-3xl md:text-4xl font-bold text-white-600 mb-4 bg-green rounded-lg">
                    Payment Successful!
                </h1>
                
                <p class="text-gray-600 text-lg mb-6">
                    Thank you for your payment. You have been successfully enrolled in:
                </p>
                
                <!-- Program Name Card -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 mb-8 border-2 border-blue-100">
                    <div class="flex items-center justify-center gap-3 mb-2">
                        <i class="ph-fill ph-graduation-cap text-primary text-3xl"></i>
                        <h2 class="text-2xl font-bold text-gray-900">
                            <?= htmlspecialchars($programTitle) ?>
                        </h2>
                    </div>
                    <p class="text-sm text-gray-600">Program Enrollment Confirmed</p>
                </div>
                
                <!-- Benefits List -->
                <div class="bg-green-50 rounded-xl p-6 mb-8 text-left">
                    <p class="font-semibold text-green-800 mb-3 flex items-center gap-2">
                        <i class="ph-fill ph-check-circle"></i>
                        What's Next:
                    </p>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start gap-2">
                            <i class="ph-fill ph-check text-green-600 mt-1"></i>
                            <span>Access all program chapters and materials</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ph-fill ph-check text-green-600 mt-1"></i>
                            <span>Take interactive quizzes and track your progress</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ph-fill ph-check text-green-600 mt-1"></i>
                            <span>Earn achievements and certificates</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Start Learning Button -->
                <a href="../pages/student/student-program-view.php?program_id=<?= $program_id ?>" 
                   class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-primary to-yellow-600 hover:from-yellow-600 hover:to-primary text-white rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <i class="ph-fill ph-play-circle text-2xl"></i>
                    Start Learning Now
                </a>
            </div>
            
        <?php else: ?>
            <!-- Failed State -->
            <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 text-center">
                <!-- Error Icon -->
                <div class="w-24 h-24 md:w-32 md:h-32 mx-auto mb-6 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center animate-scale-in shadow-lg">
                    <i class="ph-fill ph-x text-white text-5xl md:text-6xl"></i>
                </div>
                
                <!-- Error Message -->
                <h1 class="text-3xl md:text-4xl font-bold text-red-600 mb-4">
                    Enrollment Failed
                </h1>
                
                <!-- Payment Status -->
                <div class="bg-red-50 rounded-xl p-4 mb-6 inline-block">
                    <p class="text-gray-700">
                        Payment Status: 
                        <span class="font-bold text-red-600 uppercase">
                            <?= htmlspecialchars($paymentStatus) ?>
                        </span>
                    </p>
                </div>
                
                <p class="text-gray-600 text-lg mb-8">
                    There was an issue processing your enrollment. 
                    <?php if ($paymentStatus === 'succeeded'): ?>
                        Your payment was successful but enrollment failed. Please contact support.
                    <?php else: ?>
                        Please try again or contact support if the issue persists.
                    <?php endif; ?>
                </p>
                
                <!-- Support Info -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 text-left">
                    <div class="flex items-start gap-3">
                        <i class="ph-fill ph-info text-yellow-600 text-2xl mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-yellow-800 mb-1">Need Help?</p>
                            <p class="text-sm text-yellow-700">
                                If payment was deducted from your account, please contact our support team with your transaction details.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="student-programs.php" 
                       class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-semibold shadow-lg transition-colors">
                        <i class="ph ph-arrow-left"></i>
                        Back to Programs
                    </a>
                    <a href="mailto:support@al-ghaya.com" 
                       class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary hover:bg-yellow-600 text-white rounded-xl font-semibold shadow-lg transition-colors">
                        <i class="ph ph-envelope"></i>
                        Contact Support
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
