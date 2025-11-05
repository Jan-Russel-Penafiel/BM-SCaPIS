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
    echo json_encode(['success' => false, 'message' => 'Not authenticated - please log in']);
    exit;
}

if (!isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid session - missing role']);
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
    
    // Log the received data for debugging
    error_log("Chat send message - Received data: " . json_encode($input));
    error_log("Chat send message - User ID: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['role']);
    
    $message = trim($input['message'] ?? '');
    $senderType = $input['sender_type'] ?? '';
    $conversationId = $input['conversation_id'] ?? null;
    $applicationId = $input['application_id'] ?? null;
    $context = $input['context'] ?? 'application';
    
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    // Validate input
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }
    
    if ($senderType !== $userRole) {
        echo json_encode(['success' => false, 'message' => 'Invalid sender type']);
        exit;
    }
    
    // Rate limiting check
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM chat_rate_limits 
        WHERE user_id = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$userId]);
    $rateLimitCheck = $stmt->fetch();
    
    if ($rateLimitCheck['count'] >= 10) { // Max 10 messages per minute
        echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please wait before sending another message.']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Create conversation if it doesn't exist
        if (!$conversationId) {
            // Determine subject based on context
            $subject = 'General Support';
            if ($context === 'payment') {
                $subject = 'Payment Support';
            } elseif ($applicationId) {
                // Get application document type
                $stmt = $pdo->prepare("SELECT document_type FROM applications WHERE id = ?");
                $stmt->execute([$applicationId]);
                $app = $stmt->fetch();
                if ($app) {
                    $subject = $app['document_type'] . ' Support';
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO chat_conversations (resident_id, application_id, subject, status, created_at) 
                VALUES (?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $userRole === 'resident' ? $userId : null,
                $applicationId,
                $subject
            ]);
            $conversationId = $pdo->lastInsertId();
        }
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message_type, message_content, created_at) 
            VALUES (?, ?, ?, 'text', ?, NOW())
        ");
        $stmt->execute([$conversationId, $userId, $senderType, $message]);
        $messageId = $pdo->lastInsertId();
        
        // Update conversation last activity
        $stmt = $pdo->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$conversationId]);
        
        // Add to rate limit
        $stmt = $pdo->prepare("
            INSERT INTO chat_rate_limits (user_id, created_at) VALUES (?, NOW())
        ");
        $stmt->execute([$userId]);
        
        // Clean old rate limit records
        $stmt = $pdo->prepare("
            DELETE FROM chat_rate_limits 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        
        $pdo->commit();
        
        // Get the created message with sender name
        $stmt = $pdo->prepare("
            SELECT cm.*, u.first_name, u.last_name
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            WHERE cm.id = ?
        ");
        $stmt->execute([$messageId]);
        $messageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Add sender name
        if ($messageData) {
            if ($messageData['sender_type'] === 'admin') {
                $messageData['sender_name'] = 'Admin (' . $messageData['first_name'] . ' ' . $messageData['last_name'] . ')';
            } else {
                $messageData['sender_name'] = $messageData['first_name'] . ' ' . $messageData['last_name'];
            }
            // Remove unnecessary fields
            unset($messageData['first_name'], $messageData['last_name']);
        }
        
        echo json_encode([
            'success' => true,
            'conversation_id' => $conversationId,
            'message' => $messageData
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Send message SQL error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Send message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>