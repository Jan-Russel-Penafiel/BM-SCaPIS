<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

try {
    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode([
            'success' => false, 
            'message' => 'Not authorized',
            'debug' => [
                'session_exists' => isset($_SESSION['user_id']),
                'user_id' => $_SESSION['user_id'] ?? 'none',
                'role' => $_SESSION['role'] ?? 'none'
            ]
        ]);
        exit;
    }

    // Test basic database connection
    $testStmt = $pdo->query("SELECT 1 as test");
    $testResult = $testStmt->fetch();
    
    if (!$testResult) {
        throw new Exception('Database connection failed');
    }

    // Test chat_conversations table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM chat_conversations");
    $convCount = $stmt->fetch();
    
    // Test users table join
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM chat_conversations cc 
        JOIN users u ON cc.resident_id = u.id
    ");
    $joinCount = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin conversations endpoint working',
        'debug' => [
            'conversations_total' => $convCount['count'],
            'conversations_with_users' => $joinCount['count'],
            'database_working' => true
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'debug' => [
            'error_line' => $e->getLine(),
            'error_file' => basename($e->getFile())
        ]
    ]);
}
?>