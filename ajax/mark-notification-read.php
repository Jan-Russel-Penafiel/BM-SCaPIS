<?php
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
    $notificationId = $_POST['notification_id'] ?? 0;
    
    if (empty($notificationId)) {
        throw new Exception('Notification ID is required');
    }
    
    // Verify notification belongs to user
    $stmt = $pdo->prepare("
        SELECT id 
        FROM system_notifications 
        WHERE id = ? AND 
              (target_user_id = ? OR 
               (target_role = ? AND target_user_id IS NULL) OR 
               target_role = 'all')
    ");
    $stmt->execute([$notificationId, $_SESSION['user_id'], $_SESSION['role']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Notification not found or access denied');
    }
    
    // Mark notification as read
    $stmt = $pdo->prepare("
        UPDATE system_notifications 
        SET is_read = 1,
            read_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$notificationId]);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
