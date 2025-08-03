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
    
    // Check if today is the payment appointment date
    $appointmentDate = new DateTime($application['payment_appointment_date']);
    $today = new DateTime();
    if ($appointmentDate->format('Y-m-d') !== $today->format('Y-m-d')) {
        throw new Exception('Payment can only be allowed on the scheduled appointment date (' . $appointmentDate->format('M j, Y') . ').');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update payment appointment status to allow payment
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'payment_allowed',
            notes = CONCAT(COALESCE(notes, ''), '\nPayment allowed on ', NOW(), ' by ', ?)
        WHERE id = ?
    ");
    $stmt->execute([
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
        $message = "Hi {$application['first_name']}, payment for your {$application['type_name']} application #{$application['application_number']} is now allowed. You can proceed with payment through the system.";
        
        $stmt = $pdo->prepare("
            INSERT INTO sms_notifications (user_id, phone_number, message, status)
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$application['user_id'], $application['contact_number'], $message]);
    }
    
    // Send email notification if enabled
    if ($application['email_notifications'] && $application['email']) {
        $subject = "Payment Now Allowed - Application #{$application['application_number']}";
        $message = "
        Dear {$application['first_name']} {$application['last_name']},
        
        Payment for your application is now allowed:
        
        Application Details:
        - Application Number: {$application['application_number']}
        - Document Type: {$application['type_name']}
        - Amount Due: ₱" . number_format($application['fee'], 2) . "
        
        You can now proceed with payment through the system. Please log in to your account to make the payment.
        
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
            'payment_allowed',
            $subject,
            $message,
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'appointment_id' => $application['payment_appointment_id'],
                'amount' => $application['fee']
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Allowed payment for application #' . $application['application_number'],
        'applications',
        $applicationId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = 'Payment has been allowed for application #' . $application['application_number'] . '. The resident can now make payment.';
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