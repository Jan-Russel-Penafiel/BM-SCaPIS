<?php
/**
 * Contact Chat - Send Message (for guests/outsiders)
 * Stores messages in session-based storage without requiring database changes
 */

require_once '../config.php';

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$guestId = isset($input['guest_id']) ? trim($input['guest_id']) : '';
$guestName = isset($input['guest_name']) ? trim($input['guest_name']) : '';
$guestEmail = isset($input['guest_email']) ? trim($input['guest_email']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$senderType = isset($input['sender_type']) ? trim($input['sender_type']) : 'guest';

if (empty($guestId) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Store message in a file-based system (no database changes required)
$chatDir = __DIR__ . '/../storage/contact_chats';
if (!is_dir($chatDir)) {
    mkdir($chatDir, 0755, true);
}

// Create/update conversation file
$conversationFile = $chatDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $guestId) . '.json';

$conversation = [];
if (file_exists($conversationFile)) {
    $content = file_get_contents($conversationFile);
    $conversation = json_decode($content, true) ?: [];
}

// Initialize conversation if new
if (empty($conversation)) {
    $conversation = [
        'guest_id' => $guestId,
        'guest_name' => $guestName,
        'guest_email' => $guestEmail,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'status' => 'active',
        'messages' => []
    ];
} else {
    // Update guest info if provided
    if (!empty($guestName)) $conversation['guest_name'] = $guestName;
    if (!empty($guestEmail)) $conversation['guest_email'] = $guestEmail;
}

// Add message
$messageId = count($conversation['messages']) + 1;
$newMessage = [
    'id' => $messageId,
    'message' => $message,
    'sender_type' => $senderType,
    'is_read' => $senderType === 'admin' ? 1 : 0,
    'created_at' => date('Y-m-d H:i:s')
];

$conversation['messages'][] = $newMessage;
$conversation['updated_at'] = date('Y-m-d H:i:s');

// Save conversation
if (file_put_contents($conversationFile, json_encode($conversation, JSON_PRETTY_PRINT))) {
    // Update conversations index for admin dashboard
    updateConversationsIndex($guestId, $conversation);
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save message']);
}

/**
 * Update the conversations index file for quick admin access
 */
function updateConversationsIndex($guestId, $conversation) {
    $chatDir = __DIR__ . '/../storage/contact_chats';
    $indexFile = $chatDir . '/index.json';
    
    $index = [];
    if (file_exists($indexFile)) {
        $index = json_decode(file_get_contents($indexFile), true) ?: [];
    }
    
    // Count unread messages (from guest)
    $unreadCount = 0;
    foreach ($conversation['messages'] as $msg) {
        if ($msg['sender_type'] === 'guest' && empty($msg['is_read'])) {
            $unreadCount++;
        }
    }
    
    // Get last message
    $lastMessage = end($conversation['messages']);
    
    // Update index entry
    $index[$guestId] = [
        'guest_id' => $guestId,
        'guest_name' => $conversation['guest_name'],
        'guest_email' => $conversation['guest_email'],
        'status' => $conversation['status'],
        'unread_count' => $unreadCount,
        'last_message' => $lastMessage ? substr($lastMessage['message'], 0, 100) : '',
        'last_message_type' => $lastMessage ? $lastMessage['sender_type'] : '',
        'updated_at' => $conversation['updated_at'],
        'created_at' => $conversation['created_at']
    ];
    
    file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
}
