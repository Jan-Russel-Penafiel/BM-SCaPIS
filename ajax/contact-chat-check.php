<?php
/**
 * Contact Chat - Check for new messages (for guests/outsiders)
 */

require_once '../config.php';

header('Content-Type: application/json');

$guestId = isset($_GET['guest_id']) ? trim($_GET['guest_id']) : '';
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if (empty($guestId)) {
    echo json_encode(['success' => false, 'error' => 'Missing guest ID']);
    exit;
}

// Get conversation file
$chatDir = __DIR__ . '/../storage/contact_chats';
$conversationFile = $chatDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $guestId) . '.json';

if (!file_exists($conversationFile)) {
    echo json_encode(['success' => true, 'messages' => []]);
    exit;
}

$conversation = json_decode(file_get_contents($conversationFile), true);
if (!$conversation || empty($conversation['messages'])) {
    echo json_encode(['success' => true, 'messages' => []]);
    exit;
}

// Get new messages after last_id
$newMessages = [];
foreach ($conversation['messages'] as $msg) {
    if ($msg['id'] > $lastId) {
        $newMessages[] = $msg;
    }
}

echo json_encode([
    'success' => true,
    'messages' => $newMessages
]);
