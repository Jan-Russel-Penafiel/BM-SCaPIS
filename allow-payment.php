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
    
    // Get application details with payment appointment
    $stmt = $pdo->prepare("
        SELECT a.*, u.contact_number, u.email, u.sms_notifications, u.email_notifications,
               dt.type_name, dt.fee, CONCAT(u.first_name, ' ', u.last_name) as resident_name,
               apt.id as payment_appointment_id,
               apt.appointment_date as payment_appointment_date,
               apt.status as payment_appointment_status
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        LEFT JOIN appointments apt ON a.id = apt.application_id AND apt.appointment_type = 'payment'
        WHERE a.id = ? AND a.status = 'pending' AND a.payment_status = 'unpaid'
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and has payment appointment
    if (!$application) {
        throw new Exception('Application not found or not eligible for payment.');
    }
    
    if (!$application['payment_appointment_id'] || $application['payment_appointment_status'] !== 'scheduled') {
        throw new Exception('No payment appointment found or appointment not scheduled.');
    }
    
    // Check if this is an "appointment done" action (for advance payment scenarios)
    $appointmentDone = isset($_GET['appointment_done']) && $_GET['appointment_done'] === 'true';
    
    // Check if today is on or after the payment appointment date
    $appointmentDate = new DateTime($application['payment_appointment_date']);
    $appointmentDate->setTime(0, 0, 0); // Reset to start of day
    $today = new DateTime();
    $today->setTime(0, 0, 0); // Reset to start of day
    
    // Only check date if it's not an "appointment done" action
    if (!$appointmentDone && $today < $appointmentDate) {
        throw new Exception('Payment can only be allowed on or after the scheduled appointment date (' . $appointmentDate->format('M j, Y') . '). Use "Appointment Done" button for advance payment scenarios.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update payment appointment status to allow payment
    $actionNote = $appointmentDone ? 'Appointment marked as done (advance payment)' : 'Payment allowed on scheduled date';
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'payment_allowed',
            notes = CONCAT(COALESCE(notes, ''), '\n', ?, ' on ', NOW(), ' by ', ?)
        WHERE id = ?
    ");
    $stmt->execute([
        $actionNote,
        $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        $application['payment_appointment_id']
    ]);
    
    // Update application remarks
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        date('Y-m-d H:i:s') . ' - Payment allowed by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        $applicationId
    ]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'pending', ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        'Payment allowed - Resident can now make payment',
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        $paymentMessage = $appointmentDone ? 
            "Hi {$application['first_name']}, your payment appointment for {$application['type_name']} application #{$application['application_number']} has been marked as completed. You can now proceed with payment through the system." :
            "Hi {$application['first_name']}, payment for your {$application['type_name']} application #{$application['application_number']} is now allowed. You can proceed with payment through the system.";
        
        $stmt = $pdo->prepare("
            INSERT INTO sms_notifications (user_id, phone_number, message, status)
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$application['user_id'], $application['contact_number'], $paymentMessage]);
    }
    
    // Send email notification if enabled
    if ($application['email_notifications'] && $application['email']) {
        $emailSubject = $appointmentDone ? 
            "Payment Appointment Completed - Application #{$application['application_number']}" :
            "Payment Now Allowed - Application #{$application['application_number']}";
        
        $emailMessage = $appointmentDone ?
            "Dear {$application['first_name']} {$application['last_name']},\n\nYour payment appointment has been marked as completed:\n\nApplication Details:\n- Application Number: {$application['application_number']}\n- Document Type: {$application['type_name']}\n- Amount Due: ₱" . number_format($application['fee'], 2) . "\n\nYou can now proceed with payment through the system. Please log in to your account to make the payment.\n\nThank you,\nBarangay Malangit Administration" :
            "Dear {$application['first_name']} {$application['last_name']},\n\nPayment for your application is now allowed:\n\nApplication Details:\n- Application Number: {$application['application_number']}\n- Document Type: {$application['type_name']}\n- Amount Due: ₱" . number_format($application['fee'], 2) . "\n\nYou can now proceed with payment through the system. Please log in to your account to make the payment.\n\nThank you,\nBarangay Malangit Administration";
        
        // Add to notification queue
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type, title, message, target_role, target_user_id, metadata
            ) VALUES (?, ?, ?, 'resident', ?, ?)
        ");
        $stmt->execute([
            $appointmentDone ? 'appointment_completed' : 'payment_allowed',
            $emailSubject,
            $emailMessage,
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'appointment_id' => $application['payment_appointment_id'],
                'amount' => $application['fee'],
                'appointment_done' => $appointmentDone
            ])
        ]);
    }
    
    // Log activity
    $logMessage = $appointmentDone ? 
        'Marked payment appointment as done (advance payment) for application #' . $application['application_number'] :
        'Allowed payment for application #' . $application['application_number'];
    
    logActivity(
        $_SESSION['user_id'],
        $logMessage,
        'applications',
        $applicationId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $successMessage = $appointmentDone ? 
        'Payment appointment marked as done for application #' . $application['application_number'] . '. The resident can now make payment.' :
        'Payment has been allowed for application #' . $application['application_number'] . '. The resident can now make payment.';
    
    $_SESSION['success'] = $successMessage;
    header('Location: applications.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error allowing payment: ' . $e->getMessage();
    header('Location: applications.php');
    exit;
}
?> 