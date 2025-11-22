<?php
require_once '../config.php';

// Set JSON header first
header('Content-Type: application/json');

// Check authentication for AJAX calls
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error marking notifications as read: ' . $e->getMessage()
    ]);
}
?>
