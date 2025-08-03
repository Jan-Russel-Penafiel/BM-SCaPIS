<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Check if GET request with appointment ID
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    header('Location: appointments.php');
    exit;
}

try {
    // Get appointment ID
    $appointmentId = (int)$_GET['id'];
    
    // Validate appointment ID
    if (empty($appointmentId)) {
        throw new Exception('Invalid appointment ID.');
    }
    
    // Get appointment details with application and user information
    $stmt = $pdo->prepare("
        SELECT a.*, u.first_name, u.last_name, u.contact_number, u.email, u.sms_notifications, u.email_notifications,
               app.application_number, app.payment_status, app.payment_amount, dt.type_name, dt.fee
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN applications app ON a.application_id = app.id
        JOIN document_types dt ON app.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'scheduled' AND a.appointment_type = 'payment'
    ");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch();
    
    // Check if appointment exists and is a payment appointment
    if (!$appointment) {
        throw new Exception('Payment appointment not found or not eligible for completion.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update appointment status
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'completed',
            notes = CONCAT(COALESCE(notes, ''), '\nPayment completed on ', NOW(), ' by ', ?),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        $appointmentId
    ]);
    
    // Update application payment status and start processing
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET payment_status = 'paid',
            payment_date = NOW(),
            payment_method = 'cash',
            payment_reference = 'Cash payment at appointment',
            status = 'processing',
            processed_by = ?,
            admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        date('Y-m-d H:i:s') . ' - Payment received at appointment. Application moved to processing by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        $appointment['application_id']
    ]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'processing', ?, ?)
    ");
    $stmt->execute([
        $appointment['application_id'],
        'Payment received at appointment. Application moved to processing.',
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($appointment['sms_notifications'] && $appointment['contact_number']) {
        $amount = $appointment['payment_amount'] ?: $appointment['fee'];
        $message = "Hi {$appointment['first_name']}, payment of ₱" . number_format($amount, 2) . " for your {$appointment['type_name']} application #{$appointment['application_number']} has been received. Your application is now being processed. You will be notified when it's ready for pickup.";
        
        // Use the proper SMS function
        require_once 'sms_functions.php';
        $sms_result = sendSMSNotification(
            $appointment['contact_number'],
            $message,
            $appointment['user_id'],
            'payment_received'
        );
        
        if (!$sms_result['success']) {
            error_log('SMS notification failed for payment completion: ' . $sms_result['message']);
        }
    }
    
    // Send email notification if enabled
    if ($appointment['email_notifications'] && $appointment['email']) {
        $amount = $appointment['payment_amount'] ?: $appointment['fee'];
        $subject = "Payment Received - Application #{$appointment['application_number']}";
        $message = "
        Dear {$appointment['first_name']} {$appointment['last_name']},
        
        Payment for your application has been received:
        
        Application Details:
        - Application Number: {$appointment['application_number']}
        - Document Type: {$appointment['type_name']}
        - Amount Paid: ₱" . number_format($amount, 2) . "
        - Payment Date: " . date('F j, Y g:i A') . "
        - Payment Method: Cash
        
        Your application is now being processed. You will be notified when your document is ready for pickup.
        
        Thank you,
        Barangay Malangit Administration
        ";
        
        // Add to notification queue
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type, title, message, target_role, target_user_id, metadata
            ) VALUES (?, ?, ?, 'resident', ?, ?)
        ");
        $stmt->execute([
            'payment_received',
            $subject,
            $message,
            $appointment['user_id'],
            json_encode([
                'application_id' => $appointment['application_id'],
                'appointment_id' => $appointmentId,
                'amount_paid' => $amount,
                'payment_date' => date('Y-m-d H:i:s')
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Payment received for application #' . $appointment['application_number'] . ' at appointment',
        'applications',
        $appointment['application_id']
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = 'Payment received and application moved to processing successfully.';
    header('Location: appointments.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error completing payment appointment: ' . $e->getMessage();
    header('Location: appointments.php');
    exit;
}
?> 