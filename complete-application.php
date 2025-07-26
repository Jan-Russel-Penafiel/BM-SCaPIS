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
               dt.type_name, CONCAT(u.first_name, ' ', u.last_name) as resident_name
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'ready_for_pickup'
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and is ready for pickup
    if (!$application) {
        throw new Exception('Application not found or not ready for pickup.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update application status
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET status = 'completed',
            admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        date('Y-m-d H:i:s') . ' - Application completed by ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        $applicationId
    ]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'completed', ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        'Application completed and document delivered',
        $_SESSION['user_id']
    ]);
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        $result = sendApplicationStatusSMS($applicationId, 'completed');
        if (!$result['success']) {
            error_log('SMS notification failed: ' . $result['message']);
        }
    }
    
    // Send email notification if enabled
    if ($application['email_notifications'] && $application['email']) {
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type, title, message, target_role, target_user_id, metadata
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'application_completed',
            'Application Completed',
            "Your {$application['type_name']} application has been completed.",
            'resident',
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'application_number' => $application['application_number'],
                'document_type' => $application['type_name']
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Completed application #' . $application['application_number'],
        'applications',
        $applicationId
    );
    
    // Commit transaction
    $pdo->commit();
    
    // Set success message
    $_SESSION['success'] = "Application #{$application['application_number']} has been completed successfully.";
    
    // Redirect back to applications page
    header('Location: applications.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error completing application: ' . $e->getMessage();
    header('Location: applications.php');
    exit;
}
?> 