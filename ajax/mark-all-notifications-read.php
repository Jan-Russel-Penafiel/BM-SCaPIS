<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Build the query based on user role
    $sql = "UPDATE system_notifications SET is_read = 1, read_at = NOW() 
            WHERE (target_role = ? OR target_role = 'all'";
    
    if ($role == 'resident') {
        $sql .= " OR target_user_id = ?)";
        $params = [$role, $user_id];
    } else {
        $sql .= ")";
        $params = [$role];
    }

    // Execute the update
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Commit transaction
    $pdo->commit();

    // Log the action
    $action = "Mark all notifications as read";
    $logSql = "INSERT INTO activity_logs (user_id, action, table_affected, ip_address, user_agent) 
               VALUES (?, ?, 'system_notifications', ?, ?)";
    $stmt = $pdo->prepare($logSql);
    $stmt->execute([
        $user_id,
        $action,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
require_once '../config.php';

// Require login
requireLogin();

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Mark all applicable notifications as read
    $stmt = $pdo->prepare("
        UPDATE system_notifications 
        SET is_read = 1,
            read_at = CURRENT_TIMESTAMP
        WHERE (target_user_id = ? OR 
               (target_role = ? AND target_user_id IS NULL) OR 
               target_role = 'all')
        AND is_read = 0
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role']]);
    
    // Log activity
    logActivity(
        $_SESSION['user_id'],
        'Marked all notifications as read',
        'system_notifications',
        null
    );
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error marking notifications as read: ' . $e->getMessage()
    ]);
}
?>
