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
    $input = json_decode(file_get_contents('php://input'), true);
    $conversationId = $input['conversation_id'] ?? null;
    
    if (!$conversationId) {
        echo json_encode(['success' => false, 'message' => 'Missing conversation ID']);
        exit;
    }
    
    // Get conversation details
    $stmt = $pdo->prepare("
        SELECT cc.*, 
               CONCAT(u.first_name, ' ', u.last_name) as resident_name,
               u.email as resident_email,
               u.contact_number as resident_phone
        FROM chat_conversations cc
        JOIN users u ON cc.resident_id = u.id
        WHERE cc.id = ?
    ");
    $stmt->execute([$conversationId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'resident_name' => $conversation['resident_name'],
        'subject' => $conversation['subject'] ?: 'General Support',
        'created_at' => $conversation['created_at'],
        'context' => $conversation['context'] ?? null,
        'application_id' => $conversation['application_id']
    ]);
    
} catch (Exception $e) {
    error_log("Conversation details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>