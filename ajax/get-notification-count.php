<?php
require_once '../config.php';

// Require login
requireLogin();

try {
    // Get unread notification count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM system_notifications 
        WHERE (target_user_id = ? OR (target_role = ? AND target_user_id IS NULL) OR target_role = 'all')
        AND is_read = 0
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role']]);
    $count = $stmt->fetch()['count'];

    // Get recent notifications (limit to 10)
    $stmt = $pdo->prepare("
        SELECT id, type, title, message, is_read, created_at,
               CASE 
                   WHEN type = 'application_processing' THEN CONCAT('view-application.php?id=', JSON_EXTRACT(metadata, '$.application_id'))
                   WHEN type = 'document_ready' THEN CONCAT('view-application.php?id=', JSON_EXTRACT(metadata, '$.application_id'))
                   WHEN type = 'new_registration' THEN CONCAT('pending-registrations.php?user_id=', JSON_EXTRACT(metadata, '$.user_id'))
                   ELSE NULL
               END as link
        FROM system_notifications 
        WHERE (target_user_id = ? OR (target_role = ? AND target_user_id IS NULL) OR target_role = 'all')
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role']]);
    $notifications = $stmt->fetchAll();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $count,
        'notifications' => $notifications
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching notifications: ' . $e->getMessage()
    ]);
}
?>
