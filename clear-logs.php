<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Delete logs older than 30 days
    $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    // Log the activity
    if (function_exists('logActivity')) {
        logActivity($_SESSION['user_id'], "Cleared $deletedCount old activity logs", 'activity_logs', null);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Successfully deleted $deletedCount old log entries"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
