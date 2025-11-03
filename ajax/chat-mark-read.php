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
    $userId = $_SESSION['user_id'];
    $isAdmin = $_SESSION['role'] === 'admin';
    
    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Missing conversation ID']);
        exit;
    }
    
    // Verify access to conversation
    if (!$isAdmin) {
        $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE id = ? AND resident_id = ?");
        $stmt->execute([$conversationId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    // Mark messages as read
    if ($isAdmin) {
        // Admin marks resident messages as read
        $stmt = $pdo->prepare("
            UPDATE chat_messages 
            SET is_read = 1 
            WHERE conversation_id = ? 
            AND sender_type = 'resident' 
            AND is_read = 0
        ");
        $stmt->execute([$conversationId]);
    } else {
        // Resident marks admin messages as read
        $stmt = $pdo->prepare("
            UPDATE chat_messages 
            SET is_read = 1 
            WHERE conversation_id = ? 
            AND sender_type = 'admin' 
            AND is_read = 0
        ");
        $stmt->execute([$conversationId]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Chat mark read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>