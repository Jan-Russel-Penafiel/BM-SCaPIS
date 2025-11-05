<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Check authentication and admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

try {
    // First check if chat tables exist
    $checkStmt = $pdo->query("SHOW TABLES LIKE 'chat_conversations'");
    if ($checkStmt->rowCount() === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Chat system not initialized. Please run the database setup.',
            'error_type' => 'missing_tables',
            'setup_url' => '../setup-chat-system.php'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $status = $input['status'] ?? 'active';
    
    // Validate status
    $validStatuses = ['active', 'waiting', 'closed'];
    if (!in_array($status, $validStatuses)) {
        $status = 'active';
    }
    
    // Get conversations based on status
    $stmt = $pdo->prepare("
        SELECT cc.*, 
               CONCAT(u.first_name, ' ', u.last_name) as resident_name,
               u.email as resident_email,
               u.contact_number as resident_phone,
               (SELECT message_content FROM chat_messages 
                WHERE conversation_id = cc.id 
                ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM chat_messages 
                WHERE conversation_id = cc.id 
                ORDER BY created_at DESC LIMIT 1) as last_message_time,
               (SELECT COUNT(*) FROM chat_messages 
                WHERE conversation_id = cc.id 
                AND sender_type = 'resident' 
                AND is_read = 0) as unread_count
        FROM chat_conversations cc
        JOIN users u ON cc.resident_id = u.id
        WHERE cc.status = ?
        ORDER BY cc.updated_at DESC, cc.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$status]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) 
             FROM chat_online_status cos 
             JOIN users u ON cos.user_id = u.id 
             WHERE cos.is_online = 1 AND u.role = 'resident') as online_users,
            (SELECT COUNT(*) FROM chat_conversations WHERE DATE(created_at) = CURDATE()) as today_chats
    ");
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Admin conversations SQL error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Admin conversations error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>