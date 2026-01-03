<?php
/**
 * Contact Chat - Admin API for managing guest conversations
 */

require_once '../config.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

$chatDir = __DIR__ . '/../storage/contact_chats';
$indexFile = $chatDir . '/index.json';

switch ($action) {
    case 'list':
        // List all contact conversations
        listConversations();
        break;
        
    case 'get':
        // Get specific conversation
        getConversation();
        break;
        
    case 'send':
        // Send admin reply
        sendAdminReply();
        break;
        
    case 'close':
        // Close conversation
        closeConversation();
        break;
        
    case 'mark_read':
        // Mark messages as read
        markAsRead();
        break;
        
    case 'stats':
        // Get stats
        getStats();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function listConversations() {
    global $indexFile;
    
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    if (!file_exists($indexFile)) {
        echo json_encode(['success' => true, 'conversations' => []]);
        return;
    }
    
    $index = json_decode(file_get_contents($indexFile), true) ?: [];
    
    // Filter by status if needed
    $conversations = [];
    foreach ($index as $conv) {
        if ($status === 'all' || $conv['status'] === $status) {
            $conversations[] = $conv;
        }
    }
    
    // Sort by updated_at desc
    usort($conversations, function($a, $b) {
        return strtotime($b['updated_at']) - strtotime($a['updated_at']);
    });
    
    echo json_encode([
        'success' => true,
        'conversations' => $conversations
    ]);
}

function getConversation() {
    global $chatDir;
    
    $guestId = isset($_GET['guest_id']) ? trim($_GET['guest_id']) : '';
    if (empty($guestId)) {
        echo json_encode(['success' => false, 'error' => 'Missing guest ID']);
        return;
    }
    
    $conversationFile = $chatDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $guestId) . '.json';
    
    if (!file_exists($conversationFile)) {
        echo json_encode(['success' => false, 'error' => 'Conversation not found']);
        return;
    }
    
    $conversation = json_decode(file_get_contents($conversationFile), true);
    
    echo json_encode([
        'success' => true,
        'conversation' => $conversation
    ]);
}

function sendAdminReply() {
    global $chatDir, $indexFile;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $guestId = isset($input['guest_id']) ? trim($input['guest_id']) : '';
    $message = isset($input['message']) ? trim($input['message']) : '';
    
    if (empty($guestId) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    $conversationFile = $chatDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $guestId) . '.json';
    
    if (!file_exists($conversationFile)) {
        echo json_encode(['success' => false, 'error' => 'Conversation not found']);
        return;
    }
    
    $conversation = json_decode(file_get_contents($conversationFile), true);
    
    // Add admin message
    $messageId = count($conversation['messages']) + 1;
    $newMessage = [
        'id' => $messageId,
        'message' => $message,
        'sender_type' => 'admin',
        'admin_name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $conversation['messages'][] = $newMessage;
    $conversation['updated_at'] = date('Y-m-d H:i:s');
    
    if (file_put_contents($conversationFile, json_encode($conversation, JSON_PRETTY_PRINT))) {
        // Update index
        $index = json_decode(file_get_contents($indexFile), true) ?: [];
        if (isset($index[$guestId])) {
            $index[$guestId]['last_message'] = substr($message, 0, 100);
            $index[$guestId]['last_message_type'] = 'admin';
            $index[$guestId]['updated_at'] = date('Y-m-d H:i:s');
            file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
        }
        
        echo json_encode(['success' => true, 'message_id' => $messageId]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }
}

function closeConversation() {
    global $chatDir, $indexFile;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $guestId = isset($input['guest_id']) ? trim($input['guest_id']) : '';
    
    if (empty($guestId)) {
        echo json_encode(['success' => false, 'error' => 'Missing guest ID']);
        return;
    }
    
    $conversationFile = $chatDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $guestId) . '.json';
    
    if (file_exists($conversationFile)) {
        $conversation = json_decode(file_get_contents($conversationFile), true);
        $conversation['status'] = 'closed';
        $conversation['updated_at'] = date('Y-m-d H:i:s');
        file_put_contents($conversationFile, json_encode($conversation, JSON_PRETTY_PRINT));
        
        // Update index
        $index = json_decode(file_get_contents($indexFile), true) ?: [];
        if (isset($index[$guestId])) {
            $index[$guestId]['status'] = 'closed';
            $index[$guestId]['updated_at'] = date('Y-m-d H:i:s');
            file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
        }
    }
    
    echo json_encode(['success' => true]);
}

function markAsRead() {
    global $chatDir, $indexFile;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $guestId = isset($input['guest_id']) ? trim($input['guest_id']) : '';
    
    if (empty($guestId)) {
        echo json_encode(['success' => false, 'error' => 'Missing guest ID']);
        return;
    }
    
    $conversationFile = $chatDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '', $guestId) . '.json';
    
    if (file_exists($conversationFile)) {
        $conversation = json_decode(file_get_contents($conversationFile), true);
        
        // Mark all guest messages as read
        foreach ($conversation['messages'] as &$msg) {
            if ($msg['sender_type'] === 'guest') {
                $msg['is_read'] = 1;
            }
        }
        
        file_put_contents($conversationFile, json_encode($conversation, JSON_PRETTY_PRINT));
        
        // Update index
        $index = json_decode(file_get_contents($indexFile), true) ?: [];
        if (isset($index[$guestId])) {
            $index[$guestId]['unread_count'] = 0;
            file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
        }
    }
    
    echo json_encode(['success' => true]);
}

function getStats() {
    global $indexFile;
    
    if (!file_exists($indexFile)) {
        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => 0,
                'active' => 0,
                'closed' => 0,
                'unread' => 0
            ]
        ]);
        return;
    }
    
    $index = json_decode(file_get_contents($indexFile), true) ?: [];
    
    $stats = [
        'total' => count($index),
        'active' => 0,
        'closed' => 0,
        'unread' => 0
    ];
    
    foreach ($index as $conv) {
        if ($conv['status'] === 'active') {
            $stats['active']++;
        } else {
            $stats['closed']++;
        }
        $stats['unread'] += $conv['unread_count'];
    }
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}
