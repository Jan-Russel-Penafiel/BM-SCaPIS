<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bm_scapis');

// System Configuration
define('SYSTEM_NAME', 'BM-SCaPIS');
define('BARANGAY_NAME', 'Barangay Malangit');
define('SYSTEM_VERSION', '1.0.0');
define('TIMEZONE', 'Asia/Manila');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function checkRole($allowedRoles) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: dashboard.php');
        exit();
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function generateUsername($firstName, $lastName, $purokId) {
    // Format: firstlast + purok + random 3 digits
    $base = strtolower(substr($firstName, 0, 4) . substr($lastName, 0, 4));
    $base = preg_replace('/[^a-z0-9]/', '', $base);
    $purokSuffix = str_pad($purokId, 2, '0', STR_PAD_LEFT);
    $randomSuffix = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    return $base . $purokSuffix . $randomSuffix;
}

function generateApplicationNumber() {
    $year = date('Y');
    $month = date('m');
    $randomNumber = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
    return 'BM' . $year . $month . $randomNumber;
}

function logActivity($userId, $action, $tableAffected = null, $recordId = null, $oldValues = null, $newValues = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, table_affected, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $action,
        $tableAffected,
        $recordId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function sendSMSNotification($phoneNumber, $message, $userId = null) {
    global $pdo;
    
    // Get PhilSMS API key from database
    $stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'philsms_api_key'");
    $stmt->execute();
    $apiKey = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'philsms_sender_name'");
    $stmt->execute();
    $senderName = $stmt->fetchColumn() ?: 'PhilSMS';
    
    // Insert into SMS notifications table
    $stmt = $pdo->prepare("INSERT INTO sms_notifications (user_id, phone_number, message, status) VALUES (?, ?, ?, 'pending')");
    $smsId = $stmt->execute([$userId, $phoneNumber, $message]);
    $smsId = $pdo->lastInsertId();
    
    if (empty($apiKey) || $apiKey === 'your_philsms_api_key_here') {
        // Update status to failed if no API key
        $stmt = $pdo->prepare("UPDATE sms_notifications SET status = 'failed', api_response = 'No API key configured' WHERE id = ?");
        $stmt->execute([$smsId]);
        return false;
    }
    
    // PhilSMS API call (simplified - replace with actual PhilSMS implementation)
    $data = [
        'recipient' => $phoneNumber,
        'message' => $message,
        'sender_name' => $senderName
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.philsms.com/v3/sms/send'); // Replace with actual PhilSMS endpoint
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Update SMS status
    if ($httpCode === 200) {
        $stmt = $pdo->prepare("UPDATE sms_notifications SET status = 'sent', api_response = ?, sent_at = NOW() WHERE id = ?");
        $stmt->execute([$response, $smsId]);
        return true;
    } else {
        $stmt = $pdo->prepare("UPDATE sms_notifications SET status = 'failed', api_response = ? WHERE id = ?");
        $stmt->execute([$response, $smsId]);
        return false;
    }
}

function createNotification($type, $title, $message, $targetRole = 'all', $targetUserId = null, $metadata = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO system_notifications (type, title, message, target_role, target_user_id, metadata) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $type,
        $title,
        $message,
        $targetRole,
        $targetUserId,
        $metadata ? json_encode($metadata) : null
    ]);
    
    return $pdo->lastInsertId();
}

function getUnreadNotifications($userId = null, $role = null) {
    global $pdo;
    
    $sql = "SELECT * FROM system_notifications WHERE is_read = 0";
    $params = [];
    
    if ($userId) {
        $sql .= " AND (target_user_id = ? OR target_role = ? OR target_role = 'all')";
        $params = [$userId, $role];
    } elseif ($role) {
        $sql .= " AND (target_role = ? OR target_role = 'all')";
        $params = [$role];
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function markNotificationAsRead($notificationId, $userId = null) {
    global $pdo;
    
    $sql = "UPDATE system_notifications SET is_read = 1, read_at = NOW() WHERE id = ?";
    $params = [$notificationId];
    
    if ($userId) {
        $sql .= " AND (target_user_id = ? OR target_role IN (SELECT role FROM users WHERE id = ?))";
        $params[] = $userId;
        $params[] = $userId;
    }
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function formatPhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert to international format for Philippines
    if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
        $phone = '63' . substr($phone, 1);
    } elseif (strlen($phone) === 10) {
        $phone = '63' . $phone;
    }
    
    return $phone;
}

function calculateAge($birthdate) {
    $today = new DateTime();
    $birthday = new DateTime($birthdate);
    $age = $today->diff($birthday);
    return $age->y;
}

function uploadFile($file, $uploadDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    
    if ($fileError !== 0) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($fileSize > 5000000) { // 5MB limit
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $newFileName = uniqid('', true) . '.' . $fileExt;
    $uploadPath = $uploadDir . '/' . $newFileName;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $uploadPath];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > 3600) { // Token expires after 1 hour
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token has expired (1 hour)
    if ((time() - $_SESSION['csrf_token_time']) > 3600) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Content Security Policy
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
?>
