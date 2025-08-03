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
    $appointmentType = $_POST['appointment_type'] ?? 'pickup';
    $appointmentDate = $_POST['appointment_date'] ?? '';
    
    // Validate application ID
    if (empty($applicationId)) {
        throw new Exception('Invalid application ID.');
    }
    if (empty($appointmentType) || empty($appointmentDate)) {
        throw new Exception('Appointment type and date/time are required.');
    }
    
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.*, u.contact_number, u.email, u.sms_notifications, u.email_notifications,
               dt.type_name, CONCAT(u.first_name, ' ', u.last_name) as resident_name
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'processing'
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and is in processing
    if (!$application) {
        throw new Exception('Application not found or not in processing state.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Use the chosen appointment date
    $pickupDate = date('Y-m-d H:i:s', strtotime($appointmentDate));
    
    // Update application status
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET status = 'ready_for_pickup',
            pickup_date = ?,
            admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        $pickupDate,
        date('Y-m-d H:i:s') . ' - Ready for pickup: ' . $remarks,
        $applicationId
    ]);
    
    // Create appointment for pickup
    $stmt = $pdo->prepare("
        INSERT INTO appointments (
            application_id, user_id, appointment_type,
            appointment_date, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        $application['user_id'],
        $appointmentType,
        $pickupDate,
        $remarks,
        $_SESSION['user_id']
    ]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'ready_for_pickup', ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        $remarks,
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        // Use the updated SMS function with the actual appointment date
        require_once 'sms_functions.php';
        $result = sendApplicationStatusSMS($applicationId, 'ready_for_pickup', null, $pickupDate);
        if (!$result['success']) {
            error_log('SMS notification failed: ' . $result['message']);
        }
    }
    
    // Send email notification if enabled
    if ($application['email_notifications'] && $application['email']) {
        // Add email to notification queue
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type, title, message, target_role, target_user_id, metadata
            ) VALUES (
                'document_ready',
                'Document Ready for Pickup',
                ?,
                'resident',
                ?,
                ?
            )
        ");
        $stmt->execute([
            "Your {$application['type_name']} is ready for pickup.",
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'application_number' => $application['application_number'],
                'pickup_date' => $pickupDate,
                'remarks' => $remarks
            ])
        ]);
    }
    
    // Notify admin about new document ready for pickup
    $stmt = $pdo->prepare("
        INSERT INTO system_notifications (
            type, title, message, target_role, metadata
        ) VALUES (
            'document_ready_admin',
            'Document Ready for Pickup',
            ?,
            'admin',
            ?
        )
    ");
    $stmt->execute([
        "Document for {$application['resident_name']} is ready for pickup.",
        json_encode([
            'application_id' => $applicationId,
            'application_number' => $application['application_number'],
            'resident_name' => $application['resident_name'],
            'pickup_date' => $pickupDate
        ])
    ]);
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Marked application #' . $application['application_number'] . ' as ready for pickup',
        'applications',
        $applicationId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = 'Application marked as ready for pickup.';
    header('Location: applications.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error marking application as ready: ' . $e->getMessage();
    header('Location: applications.php');
    exit;
}
?> 