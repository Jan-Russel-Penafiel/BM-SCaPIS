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
               app.application_number, dt.type_name, dt.fee
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN applications app ON a.application_id = app.id
        JOIN document_types dt ON app.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'scheduled'
    ");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch();
    
    // Check if appointment exists and is scheduled
    if (!$appointment) {
        throw new Exception('Appointment not found or not eligible for completion.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update appointment status
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'completed',
            notes = CONCAT(COALESCE(notes, ''), '\nCompleted on ', NOW(), ' by ', ?),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        $appointmentId
    ]);
    
    // Update application remarks
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        date('Y-m-d H:i:s') . ' - ' . ucfirst($appointment['appointment_type']) . ' appointment completed by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        $appointment['application_id']
    ]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'pending', ?, ?)
    ");
    $stmt->execute([
        $appointment['application_id'],
        ucfirst($appointment['appointment_type']) . ' appointment completed',
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($appointment['sms_notifications'] && $appointment['contact_number']) {
        $appointmentTypeText = ucfirst($appointment['appointment_type']);
        $message = "Hi {$appointment['first_name']}, your {$appointmentTypeText} appointment for {$appointment['type_name']} application #{$appointment['application_number']} has been completed successfully. Thank you for visiting the barangay office.";
        
        // Use the proper SMS function
        require_once 'sms_functions.php';
        $sms_result = sendSMSNotification(
            $appointment['contact_number'],
            $message,
            $appointment['user_id'],
            'appointment_completed'
        );
        
        if (!$sms_result['success']) {
            error_log('SMS notification failed for appointment completion: ' . $sms_result['message']);
        }
    }
    
    // Send email notification if enabled
    if ($appointment['email_notifications'] && $appointment['email']) {
        $appointmentTypeText = ucfirst($appointment['appointment_type']);
        $subject = "{$appointmentTypeText} Appointment Completed - Application #{$appointment['application_number']}";
        $message = "
        Dear {$appointment['first_name']} {$appointment['last_name']},
        
        Your {$appointmentTypeText} appointment for the following application has been completed:
        
        Application Details:
        - Application Number: {$appointment['application_number']}
        - Document Type: {$appointment['type_name']}
        - Appointment Type: {$appointmentTypeText}
        - Completion Date: " . date('F j, Y g:i A') . "
        
        Thank you for visiting the barangay office. If you have any questions, please contact us.
        
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
            'appointment_completed',
            $subject,
            $message,
            $appointment['user_id'],
            json_encode([
                'application_id' => $appointment['application_id'],
                'appointment_id' => $appointmentId,
                'appointment_type' => $appointment['appointment_type'],
                'completion_date' => date('Y-m-d H:i:s')
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Completed ' . $appointment['appointment_type'] . ' appointment for application #' . $appointment['application_number'],
        'appointments',
        $appointmentId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = ucfirst($appointment['appointment_type']) . ' appointment completed successfully.';
    header('Location: appointments.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error completing appointment: ' . $e->getMessage();
    header('Location: appointments.php');
    exit;
}
?> 