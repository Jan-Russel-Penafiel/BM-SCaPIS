<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Check authentication and resident role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

try {
    // First check if chat tables exist
    $checkStmt = $pdo->query("SHOW TABLES LIKE 'chat_conversations'");
    if ($checkStmt->rowCount() === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Chat system not initialized. Please contact administrator.',
            'error_type' => 'missing_tables'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $applicationId = $input['application_id'] ?? null;
    $context = $input['context'] ?? 'application';
    $userId = $_SESSION['user_id'];
    
    // Find or create conversation
    $conversationId = null;
    
    if ($applicationId) {
        // Look for existing conversation for this application
        $stmt = $pdo->prepare("
            SELECT id FROM chat_conversations 
            WHERE resident_id = ? AND application_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId, $applicationId]);
        $existing = $stmt->fetch();
        if ($existing) {
            $conversationId = $existing['id'];
        }
    } else {
        // Look for general conversation
        $stmt = $pdo->prepare("
            SELECT id FROM chat_conversations 
            WHERE resident_id = ? AND (application_id IS NULL OR application_id = 0)
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();
        if ($existing) {
            $conversationId = $existing['id'];
        }
    }
    
    $messages = [];
    
    if ($conversationId) {
        // Load existing messages
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
            LIMIT 50
        ");
        $stmt->execute([$conversationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId,
        'messages' => $messages
    ]);
    
} catch (PDOException $e) {
    error_log("Resident conversation SQL error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Resident conversation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>