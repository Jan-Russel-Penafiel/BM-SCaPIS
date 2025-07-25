<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the user ID from POST data
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    // Validate input
    if ($userId <= 0) {
        $response['message'] = 'Invalid user ID provided.';
    } elseif (empty($remarks)) {
        $response['message'] = 'Remarks are required.';
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // First, verify this is actually a purok leader
            $stmt = $pdo->prepare("
                SELECT u.*, p.id as purok_id, p.purok_name 
                FROM users u 
                JOIN puroks p ON p.purok_leader_id = u.id 
                WHERE u.id = ? AND u.role = 'purok_leader'
            ");
            $stmt->execute([$userId]);
            $leader = $stmt->fetch();

            if (!$leader) {
                throw new Exception('User is not a purok leader or does not exist.');
            }

            // Update the puroks table to remove the leader
            $stmt = $pdo->prepare("
                UPDATE puroks 
                SET purok_leader_id = NULL 
                WHERE purok_leader_id = ?
            ");
            $stmt->execute([$userId]);

            // Update the user's role to resident
            $stmt = $pdo->prepare("
                UPDATE users 
                SET role = 'resident',
                    status = 'approved'
                WHERE id = ?
            ");
            $stmt->execute([$userId]);

            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (
                    user_id, action, table_affected, record_id,
                    old_values, new_values, ip_address, user_agent
                ) VALUES (
                    ?, 'remove_purok_leader', 'users', ?,
                    ?, ?, ?, ?
                )
            ");

            $oldValues = [
                'role' => 'purok_leader',
                'purok_id' => $leader['purok_id'],
                'purok_name' => $leader['purok_name']
            ];

            $newValues = [
                'role' => 'resident',
                'remarks' => $remarks
            ];

            $stmt->execute([
                $_SESSION['user_id'],
                $userId,
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
                    'leader_removed',
                    'Purok Leader Removed',
                    ?,
                    'admin',
                    ?
                )
            ");
            $stmt->execute([
                "Purok leader position removed from {$leader['first_name']} {$leader['last_name']} for {$leader['purok_name']}. Reason: {$remarks}",
                $userId
            ]);

            // If user has a contact number, send SMS notification
            if (!empty($leader['contact_number'])) {
                $message = "Dear {$leader['first_name']}, you have been removed from your position as purok leader of {$leader['purok_name']}. Reason: {$remarks}";
                
                $stmt = $pdo->prepare("
                    INSERT INTO sms_notifications (
                        user_id, phone_number, message, status
                    ) VALUES (?, ?, ?, 'pending')
                ");
                $stmt->execute([$userId, $leader['contact_number'], $message]);
            }

            // Commit transaction
            $pdo->commit();

            $response['success'] = true;
            $response['message'] = 'Purok leader has been successfully removed and converted to resident.';

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $response['message'] = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 