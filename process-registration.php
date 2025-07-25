<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Set JSON content type header
header('Content-Type: application/json');

require_once 'config.php';
require_once 'classes/Settings.php';

try {
    // Ensure this is accessed via POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required parameters
    if (!isset($_POST['user_id']) || !isset($_POST['action']) || !isset($_POST['remarks'])) {
        throw new Exception('Missing required parameters');
    }

    // Get and sanitize input parameters
    $userId = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);
    $remarks = trim(filter_var($_POST['remarks'], FILTER_SANITIZE_STRING));

    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        throw new Exception('Please log in to continue');
    }

    // Must be admin or purok leader
    if (!in_array($_SESSION['role'], ['admin', 'purok_leader'])) {
        throw new Exception('Unauthorized access');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Get user details first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'resident'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Resident not found');
    }

    // Verify purok leader can only act on their purok
    if ($_SESSION['role'] === 'purok_leader' && $user['purok_id'] !== $_SESSION['purok_id']) {
        throw new Exception('You can only act on residents in your purok');
    }

    if ($action === 'disapprove') {
        // Validate remarks for disapproval
        if (empty($remarks)) {
            throw new Exception('Please provide a reason for disapproval');
        }

        // Check for whitespace-only input
        if (trim($remarks) === '') {
            throw new Exception('Please provide a valid reason, not just whitespace');
        }

        // Get the actual length after trimming
        $remarksLength = mb_strlen(trim($remarks));

        // Validate minimum length after trimming
        if ($remarksLength < 5) {
            throw new Exception(
                sprintf(
                    'Your reason is too short (%d characters). Please provide at least 5 characters. Example: "Not a resident of this purok"',
                    $remarksLength
                )
            );
        }

        // Maximum length validation
        if ($remarksLength > 255) {
            throw new Exception(
                sprintf(
                    'Your reason is too long (%d characters). Please keep it under 255 characters.',
                    $remarksLength
                )
            );
        }

        // Store disapproval info for logging
        $disapprovalInfo = [
            'user_id' => $userId,
            'disapproved_by' => $_SESSION['user_id'],
            'disapproved_by_name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
            'remarks' => $remarks,
            'disapproved_at' => date('Y-m-d H:i:s')
        ];

        // Delete profile picture and IDs if they exist
        $filesToDelete = [
            'uploads/profiles/' . $user['profile_picture'],
            'uploads/ids/' . $user['valid_id_front'],
            'uploads/ids/' . $user['valid_id_back']
        ];

        foreach ($filesToDelete as $file) {
            if (!empty($file) && file_exists($file)) {
                unlink($file);
            }
        }

        // Delete records in correct order based on foreign key dependencies
        
        // 1. Delete notifications first as they reference the user
        $stmt = $pdo->prepare("DELETE FROM sms_notifications WHERE user_id = ?");
        $stmt->execute([$userId]);

        $stmt = $pdo->prepare("DELETE FROM system_notifications WHERE target_user_id = ?");
        $stmt->execute([$userId]);

        // 2. Delete activity logs
        $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE user_id = ?");
        $stmt->execute([$userId]);

        // 3. Delete appointments and applications
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE user_id = ?");
        $stmt->execute([$userId]);

        $stmt = $pdo->prepare("DELETE FROM applications WHERE user_id = ?");
        $stmt->execute([$userId]);

        // 4. Delete reports cache entries
        $stmt = $pdo->prepare("DELETE FROM reports_cache WHERE generated_by = ?");
        $stmt->execute([$userId]);

        // 5. Remove user references from other tables
        $stmt = $pdo->prepare("
            UPDATE users 
            SET approved_by_purok_leader = CASE WHEN approved_by_purok_leader = ? THEN NULL ELSE approved_by_purok_leader END,
                approved_by_admin = CASE WHEN approved_by_admin = ? THEN NULL ELSE approved_by_admin END
            WHERE id != ?
        ");
        $stmt->execute([$userId, $userId, $userId]);

        // 6. Update purok leader references
        $stmt = $pdo->prepare("UPDATE puroks SET purok_leader_id = NULL WHERE purok_leader_id = ?");
        $stmt->execute([$userId]);

        // Log the final activity before deleting the user
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                table_affected,
                record_id,
                old_values,
                ip_address,
                user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            'disapprove_registration',
            'users',
            $userId,
            json_encode($disapprovalInfo),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);

        // Create final system notification
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type,
                title,
                message,
                target_role,
                metadata,
                target_user_id
            ) VALUES (
                'registration_disapproved',
                'Registration Disapproved',
                ?,
                'admin',
                ?,
                NULL
            )
        ");

        $notificationMessage = "Registration for {$user['first_name']} {$user['last_name']} has been disapproved by " . 
                             ($_SESSION['role'] === 'admin' ? 'Admin' : 'Purok Leader') . 
                             " ({$_SESSION['first_name']} {$_SESSION['last_name']})";
        
        $metadata = json_encode([
            'user_id' => $userId,
            'disapproved_by' => $_SESSION['user_id'],
            'disapproved_at' => date('Y-m-d H:i:s'),
            'remarks' => $remarks
        ]);

        $stmt->execute([$notificationMessage, $metadata]);

        // Send SMS notification to user if phone number exists
        if (!empty($user['contact_number'])) {
            $message = "Your registration at " . BARANGAY_NAME . " has been disapproved.\n" .
                      "Reason: " . $remarks . "\n\n" .
                      "You may register again with the correct information.";
            
            sendSMSNotification($user['contact_number'], $message, $userId);
        }

        // Finally, delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        // Commit transaction
        $pdo->commit();

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Registration has been disapproved and deleted successfully'
        ]);
        exit;

    } else if ($action === 'approve') {
        // Update user approval status based on role
        if ($_SESSION['role'] === 'admin') {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET admin_approval = 'approved',
                    admin_remarks = ?,
                    approved_by_admin = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET purok_leader_approval = 'approved',
                    purok_leader_remarks = ?,
                    approved_by_purok_leader = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
        }

        $stmt->execute([$remarks, $_SESSION['user_id'], $userId]);

        // Log the approval
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                table_affected,
                record_id,
                old_values,
                ip_address,
                user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $approvalInfo = [
            'user_id' => $userId,
            'approved_by' => $_SESSION['user_id'],
            'approved_by_name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
            'remarks' => $remarks,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        $stmt->execute([
            $_SESSION['user_id'],
            'approve_registration',
            'users',
            $userId,
            json_encode($approvalInfo),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);

        // Commit transaction
        $pdo->commit();

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Registration has been approved successfully'
        ]);
        exit;

    } else {
        throw new Exception('Invalid action. Must be either approve or disapprove.');
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Clean output buffer and send error response
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} 