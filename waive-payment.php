<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Check if GET request with application ID
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    header('Location: applications.php');
    exit;
}

try {
    // Get application ID
    $applicationId = (int)$_GET['id'];
    
    // Validate application ID
    if (empty($applicationId)) {
        throw new Exception('Invalid application ID.');
    }
    
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.*, u.contact_number, u.email, u.sms_notifications, u.email_notifications,
               dt.type_name, dt.fee, CONCAT(u.first_name, ' ', u.last_name) as resident_name
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'pending' AND a.payment_status = 'unpaid'
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and is eligible for waiver
    if (!$application) {
        throw new Exception('Application not found or not eligible for payment waiver.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update application payment status
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET payment_status = 'waived',
            payment_date = CURRENT_TIMESTAMP,
            payment_reference = 'Waived by Admin',
            admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        date('Y-m-d H:i:s') . ' - Payment waived by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        $applicationId
    ]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'payment_waived', ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        'Payment waived by administrator',
        $_SESSION['user_id']
    ]);
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id, action, table_affected, record_id, 
            old_values, new_values, ip_address
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        'waive_payment',
        'applications',
        $applicationId,
        json_encode(['payment_status' => 'unpaid', 'payment_amount' => $application['payment_amount']]),
        json_encode(['payment_status' => 'waived', 'payment_amount' => $application['payment_amount']]),
        $_SERVER['REMOTE_ADDR']
    ]);
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        $result = sendPaymentNotificationSMS($applicationId, 'waived');
        if (!$result['success']) {
            error_log('SMS notification failed: ' . $result['message']);
        }
    }
    
    // Send email notification if enabled
    if ($application['email_notifications'] && $application['email']) {
        $subject = "Payment Waived - Application #{$application['application_number']}";
        $message = "
        Dear {$application['resident_name']},
        
        Your payment for {$application['type_name']} application #{$application['application_number']} has been waived.
        
        Application Details:
        - Application Number: {$application['application_number']}
        - Document Type: {$application['type_name']}
        - Original Fee: ₱" . number_format($application['fee'], 2) . "
        - Status: Payment Waived
        
        Your application is now being processed. You will be notified once it's ready for pickup.
        
        Thank you,
        Barangay Malangit Administration
        ";
        
        // Note: In a real implementation, you would use a proper email library
        // For now, we'll just log the email notification
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type, title, message, target_role, target_user_id, metadata
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'payment_waived',
            $subject,
            $message,
            'all',
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'document_type' => $application['type_name'],
                'original_fee' => $application['fee']
            ])
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Set success message
    $_SESSION['success'] = "Payment for application #{$application['application_number']} has been waived successfully.";
    
    // Redirect back to applications page
    header('Location: applications.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = $e->getMessage();
    header('Location: applications.php');
    exit;
}
?> 