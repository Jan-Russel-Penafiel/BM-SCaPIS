<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: applications.php');
    exit;
}

try {
    // Get form data
    $applicationId = $_POST['application_id'] ?? 0;
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Validate application ID
    if (empty($applicationId)) {
        throw new Exception('Invalid application ID.');
    }
    
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.*, u.contact_number, u.email, u.sms_notifications, u.email_notifications,
               dt.type_name, dt.processing_days
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        WHERE a.id = ? AND a.status = 'pending'
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    
    // Check if application exists and is pending
    if (!$application) {
        throw new Exception('Application not found or already being processed.');
    }
    
    // Check payment status
    if ($application['payment_status'] === 'unpaid') {
        throw new Exception('Cannot process application. Payment is still pending.');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update application status
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET status = 'processing',
            processed_by = ?,
            admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?\n)
        WHERE id = ?
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        date('Y-m-d H:i:s') . ' - Started processing: ' . $remarks,
        $applicationId
    ]);
    
    // Add to application history
    $stmt = $pdo->prepare("
        INSERT INTO application_history (
            application_id, status, remarks, changed_by
        ) VALUES (?, 'processing', ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        $remarks,
        $_SESSION['user_id']
    ]);
    
    // Calculate estimated completion date
    $processingDays = $application['processing_days'];
    $estimatedDate = date('Y-m-d H:i:s', strtotime("+$processingDays weekdays"));
    
    // Send SMS notification if enabled
    if ($application['sms_notifications'] && $application['contact_number']) {
        $result = sendApplicationStatusSMS($applicationId, 'processing');
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
                'application_processing',
                'Application Processing Started',
                ?,
                'resident',
                ?,
                ?
            )
        ");
        $stmt->execute([
            "Your {$application['type_name']} application is now being processed.",
            $application['user_id'],
            json_encode([
                'application_id' => $applicationId,
                'application_number' => $application['application_number'],
                'estimated_completion' => $estimatedDate,
                'remarks' => $remarks
            ])
        ]);
    }
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Started processing application #' . $application['application_number'],
        'applications',
        $applicationId
    );
    
    $pdo->commit();
    
    // Set success message and redirect
    $_SESSION['success'] = 'Application is now being processed.';
    header('Location: applications.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = 'Error processing application: ' . $e->getMessage();
    header('Location: applications.php');
    exit;
}
?> 