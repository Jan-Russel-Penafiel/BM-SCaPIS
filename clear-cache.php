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
    // Clear PHP opcache if available
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Clear session cache
    if (isset($_SESSION['cache'])) {
        unset($_SESSION['cache']);
    }
    
    // Clear any temp files in uploads/temp if exists
    $tempDir = 'uploads/temp/';
    if (is_dir($tempDir)) {
        $files = glob($tempDir . '*');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                // Delete files older than 1 hour
                if ($now - filemtime($file) > 3600) {
                    unlink($file);
                }
            }
        }
    }
    
    // Log the cache clear action
    if (function_exists('logActivity')) {
        logActivity($_SESSION['user_id'], 'Cleared system cache', 'system', null);
    }
    
    echo json_encode(['success' => true, 'message' => 'Cache cleared successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
