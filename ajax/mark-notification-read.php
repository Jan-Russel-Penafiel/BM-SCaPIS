<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $notificationId = intval($_POST['id'] ?? 0);
    
    if (!$notificationId) {
        throw new Exception('Notification ID is required');
    }
    
    // Mark notification as read (only for the current user)
    $stmt = $pdo->prepare("
        UPDATE system_notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE id = ? AND (target_user_id = ? OR target_user_id IS NULL)
    ");
    $stmt->execute([$notificationId, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
