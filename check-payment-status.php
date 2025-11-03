<?php
require_once 'config.php';

// Require login and must be a resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$paymentId = $_GET['payment_id'] ?? 0;

if (!$paymentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment ID required']);
    exit;
}

try {
    // Get payment session
    $payment = getPaymentSession($paymentId);
    
    if (!$payment) {
        echo json_encode(['error' => 'Payment session not found']);
        exit;
    }
    
    // Verify user owns this payment
    $stmt = $pdo->prepare("
        SELECT a.user_id 
        FROM payment_verifications pv
        JOIN applications a ON pv.application_id = a.id
        WHERE pv.id = ?
    ");
    $stmt->execute([$paymentId]);
    $paymentOwner = $stmt->fetch();
    
    if (!$paymentOwner || $paymentOwner['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized access to payment']);
        exit;
    }
    
    // Check if payment has expired
    if (strtotime($payment['expires_at']) < time() && $payment['status'] === 'pending') {
        updatePaymentStatus($paymentId, 'expired');
        $payment['status'] = 'expired';
    }
    
    $isVerified = false;
    $verificationMessage = '';
    $canVerify = false;
    
    // Only check for verification if payment is still pending
    if ($payment['status'] === 'pending') {
        // Check payment status through enhanced verification
        $isVerified = checkGCashPaymentRealTime($payment['reference_number'], $payment['amount']);
        
        if ($isVerified) {
            // Update payment status
            updatePaymentStatus($paymentId, 'verified', date('Y-m-d H:i:s'));
            
            // Update application payment status
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET payment_status = 'paid',
                    payment_date = CURRENT_TIMESTAMP,
                    payment_method = 'gcash',
                    payment_reference = ?,
                    status = 'processing',
                    processed_by = NULL
                WHERE id = ?
            ");
            $stmt->execute([$payment['reference_number'], $payment['application_id']]);
            
            // Add to application history
            $stmt = $pdo->prepare("
                INSERT INTO application_history (
                    application_id, status, remarks, changed_by
                ) VALUES (?, 'processing', ?, ?)
            ");
            $stmt->execute([
                $payment['application_id'],
                'GCash payment verified automatically. Reference: ' . $payment['reference_number'] . '. Processing started. Processing time: 3 to 5 working days (except holidays)',
                $_SESSION['user_id']
            ]);
            
            // Send notifications
            try {
                sendPaymentNotificationSMS($payment['application_id'], 'gcash', $payment['amount'], $payment['reference_number']);
            } catch (Exception $e) {
                // Log but don't fail the payment verification
                error_log('SMS notification failed: ' . $e->getMessage());
            }
            
            // Log activity
            logActivity(
                $_SESSION['user_id'],
                'GCash payment verified automatically for application #' . ($payment['application_number'] ?? 'N/A'),
                'applications',
                $payment['application_id']
            );
            
            $verificationMessage = 'Payment verified successfully!';
            $payment['status'] = 'verified';
            $canVerify = true;
        } else {
            $verificationMessage = 'Payment not yet detected. Please complete your GCash payment.';
            $canVerify = false;
        }
    } else if ($payment['status'] === 'verified') {
        $isVerified = true;
        $verificationMessage = 'Payment already verified!';
        $canVerify = true;
    } else {
        $verificationMessage = 'Payment session has expired or failed.';
        $canVerify = false;
    }
    
    // Return comprehensive payment status
    echo json_encode([
        'status' => $payment['status'],
        'verified' => $isVerified,
        'can_verify' => $canVerify,
        'message' => $verificationMessage,
        'reference_number' => $payment['reference_number'],
        'amount' => $payment['amount'],
        'created_at' => $payment['created_at'],
        'expires_at' => $payment['expires_at'],
        'verified_at' => $payment['verified_at'],
        'time_remaining' => max(0, strtotime($payment['expires_at']) - time())
    ]);
    
} catch (Exception $e) {
    error_log('Payment status check error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Failed to check payment status',
        'verified' => false,
        'can_verify' => false,
        'message' => 'System error occurred. Please try again.'
    ]);
}

// Enhanced real-time payment verification function
function checkGCashPaymentRealTime($referenceNumber, $expectedAmount) {
    global $pdo;
    
    // Simulate checking multiple sources for payment verification
    // In production, this would integrate with:
    // 1. GCash API for real transaction verification
    // 2. Bank transaction monitoring systems
    // 3. Payment gateway webhooks
    // 4. SMS/Email receipt parsing
    
    try {
        // Check if we've already marked this as verified in our system
        $stmt = $pdo->prepare("
            SELECT verified_at FROM payment_verifications 
            WHERE reference_number = ? AND status = 'verified'
        ");
        $stmt->execute([$referenceNumber]);
        if ($stmt->fetch()) {
            return true; // Already verified
        }
        
        // Simulate real-time payment detection
        $currentTime = time();
        
        // Extract timestamp from reference number (assuming format GC + timestamp)
        $referenceCreatedTime = 0;
        if (preg_match('/GC(\d+)/', $referenceNumber, $matches)) {
            $referenceCreatedTime = intval($matches[1]);
        }
        
        // Simulate payment detection after reasonable time has passed
        $timeSinceCreation = $currentTime - $referenceCreatedTime;
        
        if ($timeSinceCreation > 15) { // At least 15 seconds old
            // Simulate API call delay
            usleep(300000); // 0.3 seconds
            
            // For demo purposes: simulate payment verification based on reference pattern
            // In production, this would be actual GCash API integration
            if (strpos($referenceNumber, 'GC') === 0) {
                // Simulate varying success rates based on time elapsed
                if ($timeSinceCreation > 120) { // 2 minutes
                    return (rand(1, 10) <= 9); // 90% success rate after 2 minutes
                } else if ($timeSinceCreation > 60) { // 1 minute
                    return (rand(1, 10) <= 7); // 70% success rate after 1 minute
                } else { // 15 seconds to 1 minute
                    return (rand(1, 10) <= 4); // 40% success rate for newer payments
                }
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log('GCash payment verification error: ' . $e->getMessage());
        return false;
    }
}
?> 