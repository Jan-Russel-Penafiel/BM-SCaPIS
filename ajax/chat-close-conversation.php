<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

// Check authentication and admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $conversationId = $input['conversation_id'] ?? null;
    
    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Missing conversation ID']);
        exit;
    }
    
    // Update conversation status to closed
    $stmt = $pdo->prepare("
        UPDATE chat_conversations 
        SET status = 'closed', updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$conversationId]);
    
    if ($stmt->rowCount() > 0) {
        // Add system message
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message_type, message_content, created_at) 
            VALUES (?, ?, 'admin', 'system', 'Conversation closed by administrator', NOW())
        ");
        $stmt->execute([$conversationId, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
    }
    
} catch (Exception $e) {
    error_log("Close conversation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>