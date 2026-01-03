<?php
/**
 * Contact Chat - Register guest info
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
$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';

if (empty($guestId) || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Store in session
$_SESSION['guest_name'] = $name;
$_SESSION['guest_email'] = $email;
$_SESSION['guest_phone'] = $phone;
$_SESSION['guest_chat_id'] = $guestId;

// Initialize conversation file
$chatDir = __DIR__ . '/../storage/contact_chats';
if (!is_dir($chatDir)) {
    mkdir($chatDir, 0755, true);
}

$conversationFile = $chatDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $guestId) . '.json';

$conversation = [
    'guest_id' => $guestId,
    'guest_name' => $name,
    'guest_email' => $email,
    'guest_phone' => $phone,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'status' => 'active',
    'messages' => []
];

if (file_put_contents($conversationFile, json_encode($conversation, JSON_PRETTY_PRINT))) {
    // Update index
    $indexFile = $chatDir . '/index.json';
    $index = [];
    if (file_exists($indexFile)) {
        $index = json_decode(file_get_contents($indexFile), true) ?: [];
    }
    
    $index[$guestId] = [
        'guest_id' => $guestId,
        'guest_name' => $name,
        'guest_email' => $email,
        'status' => 'active',
        'unread_count' => 0,
        'last_message' => '',
        'last_message_type' => '',
        'updated_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create conversation']);
}
