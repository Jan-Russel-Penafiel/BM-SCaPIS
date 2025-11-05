<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

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
    
    // Load messages
    $stmt = $pdo->prepare("
        SELECT cm.*, 
               CASE 
                   WHEN cm.sender_type = 'admin' THEN CONCAT('Admin (', u.first_name, ' ', u.last_name, ')')
                   ELSE CONCAT(ur.first_name, ' ', ur.last_name)
               END as sender_name
        FROM chat_messages cm
        LEFT JOIN users u ON cm.sender_id = u.id AND cm.sender_type = 'admin'
        LEFT JOIN chat_conversations cc ON cm.conversation_id = cc.id
        LEFT JOIN users ur ON cc.resident_id = ur.id
        WHERE cm.conversation_id = ?
        ORDER BY cm.created_at ASC
        LIMIT 100
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    error_log("Load messages error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>