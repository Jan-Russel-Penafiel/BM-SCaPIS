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
    $userRole = $_SESSION['role'];
    
    // Mark all notifications as read for the current user based on their role
    $stmt = $pdo->prepare("
        UPDATE system_notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE is_read = 0 
        AND (
            target_user_id = ? 
            OR (target_role = ? AND target_user_id IS NULL)
            OR (target_role = 'all' AND target_user_id IS NULL)
        )
    ");
    $stmt->execute([$_SESSION['user_id'], $userRole]);
    
    $updatedCount = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => "$updatedCount notification(s) marked as read",
        'count' => $updatedCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
