<?php
require_once '../config.php';

// Set JSON header first
header('Content-Type: application/json');

// Require login
try {
    requireLogin();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

try {
    // Check if session variables are set
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        throw new Exception('Session variables not set');
    }

    // Get unread notification count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM system_notifications 
        WHERE (target_user_id = ? OR (target_role = ? AND target_user_id IS NULL) OR target_role = 'all')
        AND is_read = 0
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role']]);
    $result = $stmt->fetch();
    $count = $result ? (int)$result['count'] : 0;

    // Get recent notifications (limit to 10)
    $stmt = $pdo->prepare("
        SELECT id, type, title, message, is_read, created_at, metadata
        FROM system_notifications 
        WHERE (target_user_id = ? OR (target_role = ? AND target_user_id IS NULL) OR target_role = 'all')
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['role']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process notifications and add links
    foreach ($notifications as &$notification) {
        $notification['link'] = null;
        if ($notification['metadata']) {
            $metadata = json_decode($notification['metadata'], true);
            if ($metadata) {
                switch ($notification['type']) {
                    case 'application_processing':
                    case 'document_ready':
                        if (isset($metadata['application_id'])) {
                            $notification['link'] = 'view-application.php?id=' . $metadata['application_id'];
                        }
                        break;
                    case 'new_registration':
                        if (isset($metadata['user_id'])) {
                            $notification['link'] = 'pending-registrations.php?user_id=' . $metadata['user_id'];
                        }
                        break;
                }
            }
        }
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'count' => $count,
        'notifications' => $notifications ?: []
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching notifications: ' . $e->getMessage()
    ]);
}
?>
