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
    header('Location: appointments.php');
    exit;
}

try {
    // Get form data
    $appointmentId = $_POST['appointment_id'] ?? 0;
    $newAppointmentDate = $_POST['new_appointment_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate inputs
    if (empty($appointmentId) || empty($newAppointmentDate)) {
        throw new Exception('Please fill in all required fields.');
    }
    
    // Validate appointment date (must be in the future)
    $appointmentDateTime = new DateTime($newAppointmentDate);
    $now = new DateTime();
    if ($appointmentDateTime <= $now) {
        throw new Exception('Appointment date must be in the future.');
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
        throw new Exception('Appointment not found or not eligible for rescheduling.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update appointment
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET appointment_date = ?, 
            status = 'rescheduled',
            notes = CONCAT(COALESCE(notes, ''), '\nRescheduled on ', NOW(), ' to ', ?, ' by ', ?),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $newAppointmentDate,
        $newAppointmentDate,
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
        date('Y-m-d H:i:s') . ' - ' . ucfirst($appointment['appointment_type']) . ' appointment rescheduled to ' . $newAppointmentDate . ' by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
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
        ucfirst($appointment['appointment_type']) . ' appointment rescheduled to ' . date('M j, Y g:i A', strtotime($newAppointmentDate)),
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($appointment['sms_notifications'] && $appointment['contact_number']) {
        $appointmentTypeText = ucfirst($appointment['appointment_type']);
        $message = "Hi {$appointment['first_name']}, your {$appointmentTypeText} appointment for {$appointment['type_name']} application #{$appointment['application_number']} has been rescheduled to " . date('M j, Y g:i A', strtotime($newAppointmentDate)) . ".";
        
        if ($appointment['appointment_type'] === 'payment' && $appointment['fee'] > 0) {
            $message .= " Please bring ₱" . number_format($appointment['fee'], 2) . " for payment.";
        }
        
        if (!empty($notes)) {
            $message .= " Note: " . $notes;
        }
        
        // Use the proper SMS function
        require_once 'sms_functions.php';
        $sms_result = sendSMSNotification(
            $appointment['contact_number'],
            $message,
            $appointment['user_id'],
            'appointment_rescheduled'
        );
        
        if (!$sms_result['success']) {
            error_log('SMS notification failed for appointment rescheduling: ' . $sms_result['message']);
        }
    }
    
    // Send email notification if enabled
    if ($appointment['email_notifications'] && $appointment['email']) {
        $appointmentTypeText = ucfirst($appointment['appointment_type']);
        $subject = "{$appointmentTypeText} Appointment Rescheduled - Application #{$appointment['application_number']}";
        $message = "
        Dear {$appointment['first_name']} {$appointment['last_name']},
        
        Your {$appointmentTypeText} appointment for the following application has been rescheduled:
        
        Application Details:
        - Application Number: {$appointment['application_number']}
        - Document Type: {$appointment['type_name']}
        - Appointment Type: {$appointmentTypeText}
        - New Appointment Date: " . date('F j, Y g:i A', strtotime($newAppointmentDate)) . "
        ";
        
        if ($appointment['appointment_type'] === 'payment' && $appointment['fee'] > 0) {
            $message .= "- Amount Due: ₱" . number_format($appointment['fee'], 2) . "\n";
        }
        
        if (!empty($notes)) {
            $message .= "- Reason: {$notes}\n";
        }
        
        $message .= "
        Please note the new appointment time. If you have any questions, please contact the barangay office.
        
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
            'appointment_rescheduled',
            $subject,
            $message,
            $appointment['user_id'],
            json_encode([
                'application_id' => $appointment['application_id'],
                'appointment_id' => $appointmentId,
                'appointment_type' => $appointment['appointment_type'],
                'new_appointment_date' => $newAppointmentDate,
                'notes' => $notes
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Rescheduled ' . $appointment['appointment_type'] . ' appointment for application #' . $appointment['application_number'],
        'appointments',
        $appointmentId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = ucfirst($appointment['appointment_type']) . ' appointment rescheduled successfully to ' . date('M j, Y g:i A', strtotime($newAppointmentDate)) . '.';
    header('Location: appointments.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error rescheduling appointment: ' . $e->getMessage();
    header('Location: appointments.php');
    exit;
}
?> 