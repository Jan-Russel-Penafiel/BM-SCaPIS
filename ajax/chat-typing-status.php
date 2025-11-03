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
    $conversationId = $input['conversation_id'] ?? null;
    $isTyping = $input['is_typing'] ?? false;
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['role'] === 'admin' ? 'admin' : 'resident';
    
    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Missing conversation ID']);
        exit;
    }
    
    // Update or insert typing indicator
    $stmt = $pdo->prepare("
        INSERT INTO chat_typing_indicators (conversation_id, user_id, user_type, is_typing, updated_at) 
        VALUES (?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
        is_typing = VALUES(is_typing), 
        updated_at = VALUES(updated_at)
    ");
    $stmt->execute([$conversationId, $userId, $userType, $isTyping ? 1 : 0]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Chat typing status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>