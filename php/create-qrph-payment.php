<?php
    session_start();
    require_once 'dbConnection.php';
    require_once 'paymongo-helper.php';

    header('Content-Type: application/json');

    $program_id = intval($_POST['program_id'] ?? 0);
    $student_id = intval($_SESSION['userID'] ?? 0);

    if (!$program_id || !$student_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    try {
        // Get program details
        $stmt = $conn->prepare("SELECT programID, title, price, currency FROM programs WHERE programID = ?");
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $program = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$program) {
            echo json_encode(['success' => false, 'message' => 'Program not found']);
            exit;
        }
        
        $amount = floatval($program['price']);
        $description = "Enrollment: " . $program['title'];
        
        // Create metadata
        $metadata = [
            'student_id' => $student_id,
            'program_id' => $program_id,
            'program_title' => $program['title']
        ];
        
        // Create QRPh source
        $result = PayMongo::createQRPhSource($amount, $description, $metadata);
        
        if ($result['success']) {
            $source = $result['data']['data'];
            $sourceId = $source['id'];
            $qrCodeUrl = $source['attributes']['data']['checkout_url'] ?? null;
            $qrImageUrl = $source['attributes']['data']['qr_code_url'] ?? null;
            
            // Save payment record
            $stmt = $conn->prepare("
                INSERT INTO payment_transactions 
                (student_id, program_id, amount, currency, payment_provider, payment_method, payment_source_id, status, dateCreated) 
                VALUES (?, ?, ?, ?, 'paymongo', 'qrph', ?, 'pending', NOW())
            ");
            $stmt->bind_param("iidss", $student_id, $program_id, $amount, $program['currency'], $sourceId);
            $stmt->execute();
            $paymentId = $stmt->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'source_id' => $sourceId,
                'checkout_url' => $qrCodeUrl,
                'qr_code_url' => $qrImageUrl,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'source_data' => $source['attributes']
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to create QRPh payment',
                'error' => $result['error']
            ]);
        }
        
    } catch (Exception $e) {
        error_log("QRPh payment error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
?>
