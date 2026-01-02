<?php
// Notification count responder
// Returns JSON with expected shape: { success: true, count: int, notifications: [] }

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
	'success' => true,
	'count' => 0,
	'notifications' => []
];

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode($response);
    exit;
}

try {
    $userRole = $_SESSION['role'];
    $userId = $_SESSION['user_id'];
    
    // Get unread notifications for the current user
    $stmt = $pdo->prepare("
        SELECT id, type, title, message, created_at 
        FROM system_notifications 
        WHERE is_read = 0 
        AND (
            target_user_id = ? 
            OR (target_role = ? AND target_user_id IS NULL)
            OR (target_role = 'all' AND target_user_id IS NULL)
        )
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId, $userRole]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['count'] = count($notifications);
    $response['notifications'] = $notifications;
    
} catch (Exception $e) {
    // Return empty notifications on error
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
