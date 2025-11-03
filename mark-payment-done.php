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
               dt.type_name, dt.fee, CONCAT(u.first_name, ' ', u.last_name) as resident_name,
               u.first_name, u.last_name
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'pending' AND a.payment_status = 'unpaid'
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and is eligible for payment completion
    if (!$application) {
        throw new Exception('Application not found or not eligible for payment completion.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Mark payment as completed
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET payment_status = 'paid',
            payment_date = NOW(),
            payment_method = 'manual',
            payment_reference = CONCAT('MANUAL-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')),
            payment_amount = ?
        WHERE id = ?
    ");
    $stmt->execute([$application['fee'], $applicationId]);
    
    // Update application status to processing and set processed_by
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET status = 'processing',
            processed_by = ?,
            admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?)
        WHERE id = ?
    ");
    $remarkText = date('Y-m-d H:i:s') . ' - Payment marked as completed manually by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] . '. Processing started automatically.' . "\n";
    $stmt->execute([$_SESSION['user_id'], $remarkText, $applicationId]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'paid', ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        'Payment marked as completed manually - Processing started',
        $_SESSION['user_id']
    ]);
    
    // Add processing history entry
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'processing', ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        'Application processing started after manual payment completion',
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        $message = "Hi {$application['first_name']}, your payment for {$application['type_name']} application #{$application['application_number']} has been confirmed. Your document is now being processed (3-5 working days).";
        
        $stmt = $pdo->prepare("
            INSERT INTO sms_notifications (user_id, phone_number, message, status)
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$application['user_id'], $application['contact_number'], $message]);
    }
    
    // Send email notification if enabled
    if ($application['email_notifications'] && $application['email']) {
        $subject = "Payment Confirmed - Application #{$application['application_number']}";
        $message = "Dear {$application['first_name']} {$application['last_name']},

Your payment has been confirmed and your application is now being processed:

Application Details:
- Application Number: {$application['application_number']}
- Document Type: {$application['type_name']}
- Payment Amount: ₱" . number_format($application['fee'], 2) . "
- Payment Method: Manual Processing
- Processing Time: 3 to 5 working days (except holidays)

You will be notified when your document is ready for pickup.

Thank you,
Barangay Malangit Administration";
        
        // Add to notification queue
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type, title, message, target_role, target_user_id, metadata
            ) VALUES (?, ?, ?, 'resident', ?, ?)
        ");
        $stmt->execute([
            'application_processing',
            $subject,
            $message,
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'payment_method' => 'manual',
                'amount' => $application['fee']
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Marked payment as completed manually for application #' . $application['application_number'] . ' and started processing',
        'applications',
        $applicationId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = 'Payment has been marked as completed for application #' . $application['application_number'] . '. Processing has started automatically.';
    header('Location: applications.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error marking payment as done: ' . $e->getMessage();
    header('Location: applications.php');
    exit;
}
?>