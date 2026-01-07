<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: applications.php');
    exit;
}

try {
    // Get form data
    $applicationId = $_POST['application_id'] ?? 0;
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Validate application ID
    if (empty($applicationId)) {
        throw new Exception('Invalid application ID.');
    }
    
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.*, u.contact_number, u.email, u.sms_notifications, u.email_notifications,
               dt.type_name, dt.fee
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'pending' AND a.payment_status = 'unpaid'
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and payment is still unpaid
    if (!$application) {
        throw new Exception('Application not found or payment already received.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update payment status to paid
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET payment_status = 'paid',
            payment_date = NOW(),
            payment_amount = ?,
            admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?)
        WHERE id = ?
    ");
    $stmt->execute([
        $application['fee'],
        date('Y-m-d H:i:s') . ' - Payment received: ' . ($remarks ?: 'No remarks') . "\n",
        $applicationId
    ]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'payment_received', ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        'Payment received. ' . $remarks,
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        $message = "Payment received for your {$application['type_name']} application (App #{$application['application_number']}). Your application is now ready for processing. Thank you! - Barangay Office";
        
        // Use the sendSMSNotification function if available
        if (function_exists('sendSMSNotification')) {
            $result = sendSMSNotification($application['contact_number'], $message, $application['user_id'], 'payment');
            if (!$result['success']) {
                error_log('SMS notification failed: ' . $result['message']);
            }
        }
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Marked payment as received for application #' . $application['application_number'],
        'applications',
        $applicationId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = 'Payment has been marked as received. You can now start processing the application.';
    header('Location: applications.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error updating payment status: ' . $e->getMessage();
    header('Location: applications.php');
    exit;
}
?>
