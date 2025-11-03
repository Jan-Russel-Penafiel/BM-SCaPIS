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
    $userId = $_SESSION['user_id'];
    $isAdmin = $_SESSION['role'] === 'admin';
    
    if ($isAdmin) {
        // Admin sees all unread messages from residents
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages cm 
            JOIN chat_conversations cc ON cm.conversation_id = cc.id 
            WHERE cm.sender_type = 'resident' AND cm.is_read = 0
        ");
        $stmt->execute();
    } else {
        // Resident sees unread messages from admin in their conversations
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages cm 
            JOIN chat_conversations cc ON cm.conversation_id = cc.id 
            WHERE cc.resident_id = ? AND cm.sender_type = 'admin' AND cm.is_read = 0
        ");
        $stmt->execute([$userId]);
    }
    
    $result = $stmt->fetch();
    $count = (int)$result['count'];
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    error_log("Chat unread count error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>