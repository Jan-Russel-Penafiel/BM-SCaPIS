<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: purok-leaders.php');
    exit;
}

// Get POST data
$purokId = isset($_POST['purok_id']) ? intval($_POST['purok_id']) : 0;
$residentId = isset($_POST['resident_id']) ? intval($_POST['resident_id']) : 0;

// Validate input
if ($purokId <= 0 || $residentId <= 0) {
    $_SESSION['error'] = 'Invalid input parameters.';
    header('Location: purok-leaders.php');
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if purok exists and has no leader
    $stmt = $pdo->prepare("
        SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as current_leader_name 
        FROM puroks p 
        LEFT JOIN users u ON p.purok_leader_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$purokId]);
    $purok = $stmt->fetch();
    
    if (!$purok) {
        throw new Exception('Purok not found.');
    }
    
    if ($purok['purok_leader_id']) {
        throw new Exception('This purok already has a leader assigned: ' . $purok['current_leader_name']);
    }
    
    // Check if resident exists, is approved, and not already a leader
    $stmt = $pdo->prepare("
        SELECT u.*, p.purok_name 
        FROM users u 
        LEFT JOIN puroks p ON u.purok_id = p.id 
        WHERE u.id = ? 
        AND u.role = 'resident' 
        AND u.status = 'approved'
        AND NOT EXISTS (
            SELECT 1 FROM puroks 
            WHERE purok_leader_id = u.id
        )
    ");
    $stmt->execute([$residentId]);
    $resident = $stmt->fetch();
    
    if (!$resident) {
        throw new Exception('Selected resident is not eligible to be a purok leader.');
    }
    
    // Update the resident's role to purok_leader
    $stmt = $pdo->prepare("
        UPDATE users 
        SET role = 'purok_leader',
            purok_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$purokId, $residentId]);
    
    // Assign the resident as purok leader
    $stmt = $pdo->prepare("
        UPDATE puroks 
        SET purok_leader_id = ? 
        WHERE id = ?
    ");
    $stmt->execute([$residentId, $purokId]);
    
    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id, action, table_affected, record_id,
            old_values, new_values, ip_address, user_agent
        ) VALUES (
            ?, 'assign_purok_leader', 'puroks', ?,
            ?, ?, ?, ?
        )
    ");
    
    $oldValues = ['purok_leader_id' => null];
    $newValues = [
        'purok_leader_id' => $residentId,
        'resident_name' => $resident['first_name'] . ' ' . $resident['last_name'],
        'purok_name' => $purok['purok_name']
    ];
    
    $stmt->execute([
        $_SESSION['user_id'],
        $purokId,
        json_encode($oldValues),
        json_encode($newValues),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    // Create system notification
    $stmt = $pdo->prepare("
        INSERT INTO system_notifications (
            type, title, message, target_role, target_user_id
        ) VALUES (
            'leader_assigned',
            'New Purok Leader Assigned',
            ?,
            'admin',
            ?
        )
    ");
    $stmt->execute([
        $resident['first_name'] . ' ' . $resident['last_name'] . 
        ' has been assigned as leader of ' . $purok['purok_name'],
        $residentId
    ]);
    
    // Send SMS notification if resident has contact number
    if (!empty($resident['contact_number'])) {
        $message = "Dear {$resident['first_name']}, you have been assigned as the leader of {$purok['purok_name']}. Please log in to your account to manage your purok.";
        
        $stmt = $pdo->prepare("
            INSERT INTO sms_notifications (
                user_id, phone_number, message, status
            ) VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$residentId, $resident['contact_number'], $message]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success'] = 'Purok leader assigned successfully.';
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

// Redirect back to purok leaders page
header('Location: purok-leaders.php');
exit;
?> 