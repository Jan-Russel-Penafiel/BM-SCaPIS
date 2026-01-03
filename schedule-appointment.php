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
    $applicationId = $_POST['application_id'] ?? 0;
    $appointmentType = $_POST['appointment_type'] ?? '';
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate inputs
    if (empty($applicationId) || empty($appointmentType) || empty($appointmentDate)) {
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
        WHERE a.id = ? AND a.status IN ('pending', 'processing', 'ready_for_pickup')
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and is eligible for appointment
    if (!$application) {
        throw new Exception('Application not found or not eligible for appointment scheduling.');
    }
    
    // Check if there's already an appointment for this application and type
    $stmt = $pdo->prepare("
        SELECT id FROM appointments 
        WHERE application_id = ? AND appointment_type = ? AND status IN ('scheduled', 'rescheduled')
    ");
    $stmt->execute([$applicationId, $appointmentType]);
    if ($stmt->fetch()) {
        throw new Exception('An appointment of this type already exists for this application.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Create appointment
    $stmt = $pdo->prepare("
        INSERT INTO appointments (
            application_id, user_id, appointment_type, appointment_date, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        $application['user_id'],
        $appointmentType,
        $appointmentDate,
        $notes,
        $_SESSION['user_id']
    ]);
    $appointmentId = $pdo->lastInsertId();
    
    // Update application remarks
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        date('Y-m-d H:i:s') . ' - ' . ucfirst($appointmentType) . ' appointment scheduled for ' . $appointmentDate . ' by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
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
        ucfirst($appointmentType) . ' appointment scheduled for ' . date('M j, Y g:i A', strtotime($appointmentDate)),
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        $appointmentDateFormatted = date('M j, Y g:i A', strtotime($appointmentDate));
        $message = "Your application {$application['application_number']} appointment is now scheduled on {$appointmentDateFormatted}.";
        
        // Use the proper SMS function
        require_once 'sms_functions.php';
        $sms_result = sendSMSNotification(
            $application['contact_number'],
            $message,
            $application['user_id'],
            'appointment_scheduled'
        );
        
        if (!$sms_result['success']) {
            error_log('SMS notification failed for appointment scheduling: ' . $sms_result['message']);
        }
    }
    
    // Send email notification if enabled
    if ($application['email_notifications'] && $application['email']) {
        $appointmentTypeText = ucfirst($appointmentType);
        $subject = "{$appointmentTypeText} Appointment Scheduled - Application #{$application['application_number']}";
        $message = "
        Dear {$application['first_name']} {$application['last_name']},
        
        Your {$appointmentTypeText} appointment for the following application has been scheduled:
        
        Application Details:
        - Application Number: {$application['application_number']}
        - Document Type: {$application['type_name']}
        - Appointment Type: {$appointmentTypeText}
        - Appointment Date: " . date('F j, Y g:i A', strtotime($appointmentDate)) . "
        ";
        
        if ($appointmentType === 'payment' && $application['fee'] > 0) {
            $message .= "- Amount Due: ₱" . number_format($application['fee'], 2) . "\n";
        }
        
        if (!empty($notes)) {
            $message .= "- Notes: {$notes}\n";
        }
        
        $message .= "
        Please arrive on time for your appointment. If you have any questions, please contact the barangay office.
        
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
            'appointment_scheduled',
            $subject,
            $message,
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'appointment_id' => $appointmentId,
                'appointment_type' => $appointmentType,
                'appointment_date' => $appointmentDate,
                'notes' => $notes
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Scheduled ' . $appointmentType . ' appointment for application #' . $application['application_number'],
        'appointments',
        $appointmentId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = ucfirst($appointmentType) . ' appointment scheduled successfully for ' . date('M j, Y g:i A', strtotime($appointmentDate)) . '.';
    header('Location: appointments.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error scheduling appointment: ' . $e->getMessage();
    header('Location: appointments.php');
    exit;
}
?> 