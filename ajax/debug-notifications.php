<?php
// Debug version of get-notification-count.php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode([
    'debug' => 'Starting AJAX call',
    'file_exists' => file_exists('../config.php'),
    'timestamp' => date('Y-m-d H:i:s')
]);

try {
    require_once '../config.php';
    
    echo json_encode([
        'debug' => 'Config loaded',
        'session_started' => session_status() === PHP_SESSION_ACTIVE,
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'role' => $_SESSION['role'] ?? 'not set'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'debug' => 'Error in config',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>