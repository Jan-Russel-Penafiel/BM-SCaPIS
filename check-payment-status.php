<?php
require_once 'config.php';

// Require login
requireLogin();

// Set JSON content type
header('Content-Type: application/json');

// Get payment ID
$paymentId = $_GET['payment_id'] ?? 0;

if (!$paymentId) {
    echo json_encode(['error' => 'Payment ID required']);
    exit;
}

try {
    // Get payment status
    $payment = getPaymentSession($paymentId);
    
    if (!$payment) {
        echo json_encode(['error' => 'Payment not found']);
        exit;
    }
    
    // Check if payment has expired
    if (strtotime($payment['expires_at']) < time() && $payment['status'] === 'pending') {
        updatePaymentStatus($paymentId, 'expired');
        $payment['status'] = 'expired';
    }
    
    // Return payment status
    echo json_encode([
        'status' => $payment['status'],
        'reference_number' => $payment['reference_number'],
        'amount' => $payment['amount'],
        'created_at' => $payment['created_at'],
        'expires_at' => $payment['expires_at'],
        'verified_at' => $payment['verified_at']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to check payment status']);
}
?> 