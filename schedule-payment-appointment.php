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
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate inputs
    if (empty($applicationId) || empty($appointmentDate)) {
        throw new Exception('Please fill in all required fields.');
    }
    
    // Validate appointment date (must be in the future)
    $appointmentDateTime = new DateTime($appointmentDate);
    $now = new DateTime();
    if ($appointmentDateTime <= $now) {
        throw new Exception('Appointment date must be in the future.');
    }
    
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.*, u.first_name, u.last_name, u.contact_number, u.email, u.sms_notifications, u.email_notifications,
               dt.type_name, dt.fee
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'pending' AND a.payment_status = 'unpaid'
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and is eligible for payment appointment
    if (!$application) {
        throw new Exception('Application not found or not eligible for payment appointment.');
    }
    
    // Check if there's already a payment appointment for this application
    $stmt = $pdo->prepare("
        SELECT id FROM appointments 
        WHERE application_id = ? AND appointment_type = 'payment' AND status IN ('scheduled', 'rescheduled')
    ");
    $stmt->execute([$applicationId]);
    if ($stmt->fetch()) {
        throw new Exception('A payment appointment already exists for this application.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Create appointment
    $stmt = $pdo->prepare("
        INSERT INTO appointments (
            application_id, user_id, appointment_type, appointment_date, notes, created_by
        ) VALUES (?, ?, 'payment', ?, ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        $application['user_id'],
        $appointmentDate,
        $notes,
        $_SESSION['user_id']
    ]);
    $appointmentId = $pdo->lastInsertId();
    
    // Update application to indicate payment appointment is scheduled
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        date('Y-m-d H:i:s') . ' - Payment appointment scheduled for ' . $appointmentDate . ' by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
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
        'Payment appointment scheduled for ' . date('M j, Y g:i A', strtotime($appointmentDate)),
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        $message = "Hi {$application['first_name']}, your payment appointment for {$application['type_name']} application #{$application['application_number']} has been scheduled for " . date('M j, Y g:i A', strtotime($appointmentDate)) . ". Please bring ₱" . number_format($application['fee'], 2) . " for payment.";
        
        // Use the proper SMS function
        require_once 'sms_functions.php';
        $sms_result = sendSMSNotification(
            $application['contact_number'],
            $message,
            $application['user_id'],
            'payment_appointment_scheduled'
        );
        
        if (!$sms_result['success']) {
            error_log('SMS notification failed for payment appointment: ' . $sms_result['message']);
        }
    }
    
    // Send email notification if enabled
    if ($application['email_notifications'] && $application['email']) {
        $subject = "Payment Appointment Scheduled - Application #{$application['application_number']}";
        $message = "
        Dear {$application['first_name']} {$application['last_name']},
        
        Your payment appointment for the following application has been scheduled:
        
        Application Details:
        - Application Number: {$application['application_number']}
        - Document Type: {$application['type_name']}
        - Amount Due: ₱" . number_format($application['fee'], 2) . "
        - Appointment Date: " . date('F j, Y g:i A', strtotime($appointmentDate)) . "
        
        Please bring the exact amount for payment. If you have any questions, please contact the barangay office.
        
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
            'payment_appointment_scheduled',
            $subject,
            $message,
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'appointment_id' => $appointmentId,
                'appointment_date' => $appointmentDate,
                'amount' => $application['fee']
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Scheduled payment appointment for application #' . $application['application_number'],
        'appointments',
        $appointmentId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = 'Payment appointment scheduled successfully for ' . date('M j, Y g:i A', strtotime($appointmentDate)) . '.';
    header('Location: applications.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error scheduling payment appointment: ' . $e->getMessage();
    header('Location: applications.php');
    exit;
}
?> 