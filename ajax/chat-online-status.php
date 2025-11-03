<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $isOnline = $input['is_online'] ?? true;
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['role'] === 'admin' ? 'admin' : 'resident';
    
    // Update or insert online status
    $stmt = $pdo->prepare("
        INSERT INTO chat_online_status (user_id, user_type, is_online, last_seen) 
        VALUES (?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
        is_online = VALUES(is_online), 
        last_seen = VALUES(last_seen)
    ");
    $stmt->execute([$userId, $userType, $isOnline ? 1 : 0]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Online status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>