<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access. Only administrators can delete residents.';
    header('Location: residents.php');
    exit;
}

// Check if resident ID is provided
if (!isset($_POST['resident_id']) || empty($_POST['resident_id'])) {
    $_SESSION['error'] = 'Resident ID is required.';
    header('Location: residents.php');
    exit;
}

$residentId = intval($_POST['resident_id']);

try {
    // Verify resident exists and is a resident
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'resident'");
    $stmt->execute([$residentId]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resident) {
        throw new Exception('Resident not found.');
    }

    // Check if resident is a purok leader
    $stmt = $pdo->prepare("SELECT id FROM puroks WHERE purok_leader_id = ?");
    $stmt->execute([$residentId]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Cannot delete resident: User is currently assigned as a Purok Leader.');
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Delete profile picture and IDs if they exist
        $filesToDelete = [
            'uploads/profiles/' . $resident['profile_picture'],
            'uploads/ids/' . $resident['valid_id_front'],
            'uploads/ids/' . $resident['valid_id_back']
        ];

        foreach ($filesToDelete as $file) {
            if (!empty($file) && file_exists($file)) {
                unlink($file);
            }
        }

        // --- Begin manual cascading delete for applications and application_history ---
        // 1. Get all application IDs for this resident
        $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = ?");
        $stmt->execute([$residentId]);
        $applicationIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($applicationIds) {
            // 2. Delete from application_history for these applications
            $in = str_repeat('?,', count($applicationIds) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM application_history WHERE application_id IN ($in)");
            $stmt->execute($applicationIds);

            // 3. Delete from applications
            $stmt = $pdo->prepare("DELETE FROM applications WHERE id IN ($in)");
            $stmt->execute($applicationIds);
        }
        // --- End manual cascading delete ---

        // Delete related records based on database schema
        $relatedTables = [
            'appointments' => 'user_id',
            'sms_notifications' => 'user_id',
            'system_notifications' => 'target_user_id',
            'activity_logs' => 'user_id'
        ];

        foreach ($relatedTables as $table => $column) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $column = ?");
            $stmt->execute([$residentId]);
        }

        // Store resident info for logging
        $deletedResidentInfo = [
            'name' => $resident['first_name'] . ' ' . $resident['last_name'],
            'purok_id' => $resident['purok_id'],
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $_SESSION['user_id'],
            'deleted_by_name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
        ];

        // Delete the resident
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'resident'");
        $stmt->execute([$residentId]);

        // Log the activity
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
            'delete_resident',
            'users',
            $residentId,
            json_encode($deletedResidentInfo),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);

        // Create system notification
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type,
                title,
                message,
                target_role,
                metadata
            ) VALUES (
                'resident_deleted',
                'Resident Deleted',
                ?,
                'admin',
                ?
            )
        ");

        $notificationMessage = "Resident {$resident['first_name']} {$resident['last_name']} has been deleted by " . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
        $metadata = json_encode([
            'deleted_user_id' => $residentId,
            'deleted_by' => $_SESSION['user_id'],
            'deleted_at' => date('Y-m-d H:i:s'),
            'purok_id' => $resident['purok_id']
        ]);

        $stmt->execute([
            $notificationMessage,
            $metadata
        ]);

        // Commit transaction
        $pdo->commit();

        // Set success message
        $_SESSION['success'] = "Resident {$resident['first_name']} {$resident['last_name']} has been successfully deleted.";

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    $_SESSION['error'] = 'Error deleting resident: ' . $e->getMessage();
}

// Redirect back to residents page
header('Location: residents.php');
exit; 