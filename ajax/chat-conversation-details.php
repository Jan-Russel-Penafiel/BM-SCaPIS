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
    
    // Get conversation details
    $stmt = $pdo->prepare("
        SELECT cc.*, 
               CONCAT(u.first_name, ' ', u.last_name) as resident_name,
               u.email as resident_email,
               u.phone as resident_phone,
               app.document_type,
               app.status as application_status
        FROM chat_conversations cc
        JOIN users u ON cc.resident_id = u.id
        LEFT JOIN applications app ON cc.application_id = app.id
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
        'context' => $conversation['context'],
        'application_id' => $conversation['application_id'],
        'application_type' => $conversation['document_type'],
        'application_status' => $conversation['application_status']
    ]);
    
} catch (Exception $e) {
    error_log("Conversation details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>