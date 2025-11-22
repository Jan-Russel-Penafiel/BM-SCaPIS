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
    $lastCheck = $input['last_check'] ?? 0;
    $conversationId = $input['conversation_id'] ?? null;
    $isAdmin = $input['is_admin'] ?? false;
    $userId = $_SESSION['user_id'];
    
    $response = [
        'success' => true,
        'new_messages' => [],
        'typing_status' => null,
        'unread_count' => 0
    ];
    
    // Check for new messages
    if ($conversationId) {
        // Get messages newer than last check
        $stmt = $pdo->prepare("
            SELECT cm.*, cc.resident_id, cc.subject,
                   CASE 
                       WHEN cm.sender_type = 'admin' THEN CONCAT('Admin (', u.first_name, ' ', u.last_name, ')')
                       ELSE CONCAT(ur.first_name, ' ', ur.last_name)
                   END as sender_name
            FROM chat_messages cm
            JOIN chat_conversations cc ON cm.conversation_id = cc.id
            LEFT JOIN users u ON cm.sender_id = u.id AND cm.sender_type = 'admin'
            LEFT JOIN users ur ON cc.resident_id = ur.id
            WHERE cm.conversation_id = ? 
            AND cm.created_at > FROM_UNIXTIME(?)
            ORDER BY cm.created_at ASC
        ");
        $stmt->execute([$conversationId, $lastCheck / 1000]);
        $response['new_messages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check typing status
        $stmt = $pdo->prepare("
            SELECT cti.is_typing, 
                   CASE 
                       WHEN cti.user_type = 'admin' THEN CONCAT('Admin (', u.first_name, ' ', u.last_name, ')')
                       ELSE CONCAT(ur.first_name, ' ', ur.last_name)
                   END as user_name
            FROM chat_typing_indicators cti
            LEFT JOIN users u ON cti.user_id = u.id AND cti.user_type = 'admin'
            LEFT JOIN chat_conversations cc ON cti.conversation_id = cc.id
            LEFT JOIN users ur ON cc.resident_id = ur.id AND cti.user_type = 'resident'
            WHERE cti.conversation_id = ? 
            AND cti.user_id != ?
            AND cti.user_type != ?
            AND cti.updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
            ORDER BY cti.updated_at DESC
            LIMIT 1
        ");
        $stmt->execute([
            $conversationId, 
            $userId, 
            $isAdmin ? 'admin' : 'resident'
        ]);
        $typingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($typingUser && $typingUser['is_typing']) {
            $response['typing_status'] = [
                'is_typing' => true,
                'user_name' => $typingUser['user_name']
            ];
        } else {
            $response['typing_status'] = ['is_typing' => false];
        }
    }
    
    // Get unread count
    if ($isAdmin) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages cm 
            JOIN chat_conversations cc ON cm.conversation_id = cc.id 
            WHERE cm.sender_type = 'resident' AND cm.is_read = 0
        ");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages cm 
            JOIN chat_conversations cc ON cm.conversation_id = cc.id 
            WHERE cc.resident_id = ? AND cm.sender_type = 'admin' AND cm.is_read = 0
        ");
        $stmt->execute([$userId]);
    }
    $result = $stmt->fetch();
    $response['unread_count'] = (int)$result['count'];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Chat check messages error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error', 
        'debug' => $e->getMessage()
    ]);
}
?>