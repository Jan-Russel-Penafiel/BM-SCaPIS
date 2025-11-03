<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

try {
    // Basic test endpoint
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Not authenticated',
            'session_id' => session_id(),
            'session_data' => $_SESSION ?? 'No session data'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Chat system is working',
        'user_id' => $_SESSION['user_id'],
        'user_role' => $_SESSION['role'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>