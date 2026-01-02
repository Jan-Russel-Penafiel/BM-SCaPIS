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

// Session lifetime in seconds (idle timeout). Adjust as needed.
define('SESSION_LIFETIME', 1800); // 1800s = 30 minutes

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Include SMS functions
require_once __DIR__ . '/sms_functions.php';

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

    // Ensure session garbage collection respects our lifetime
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

    // Set session cookie params (lifetime, path, domain, secure, httponly)
    // domain left empty so it defaults to current host
    session_set_cookie_params(SESSION_LIFETIME, '/', '', ini_get('session.cookie_secure'), true);

    session_start();

    // Enforce idle session timeout: destroy session if inactive too long
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        // Remove session cookie from client
        setcookie(session_name(), '', time() - 3600, '/');
    }
    $_SESSION['last_activity'] = time();

    // Regenerate session ID periodically to mitigate fixation (every 5 minutes)
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 300) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Helper Functions
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    // Check if user still exists in database
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            // User no longer exists, destroy session
            session_destroy();
            return false;
        }
        
        return true;
    } catch (PDOException $e) {
        // On database error, assume not logged in
        return false;
    }
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
    
    // Skip logging if userId is null or empty
    if (empty($userId)) {
        return;
    }
    
    // Check if user exists before logging activity
    try {
        $userCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $userCheck->execute([$userId]);
        
        if (!$userCheck->fetch()) {
            // User doesn't exist, skip logging or destroy invalid session
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                // Clear invalid session
                session_destroy();
            }
            return;
        }
        
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
    } catch (PDOException $e) {
        // Log error but don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Include unified SMS functions
require_once 'sms_functions.php';

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
    
    // Use hash_equals for timing attack protection
    return hash_equals($_SESSION['csrf_token'], $token);
}

// GCash Payment Verification
function verifyGCashPayment($referenceNumber, $expectedAmount) {
    // In a real implementation, this would call GCash API to verify payment
    // For now, we'll simulate verification with a simple check
    
    // Simulate API call delay
    usleep(500000); // 0.5 seconds
    
    // For demo purposes, we'll accept any reference number that starts with 'GC'
    // In production, this would validate against GCash's actual API
    if (strpos($referenceNumber, 'GC') === 0) {
        return true;
    }
    
    return false;
}

// Payment Session Management
function createPaymentSession($applicationId, $amount, $reference) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO payment_verifications (
            application_id, reference_number, amount, status, created_at
        ) VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$applicationId, $reference, $amount]);
    
    return $pdo->lastInsertId();
}

function getPaymentSession($paymentId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM payment_verifications WHERE id = ?
    ");
    $stmt->execute([$paymentId]);
    
    return $stmt->fetch();
}

function updatePaymentStatus($paymentId, $status, $verifiedAt = null) {
    global $pdo;
    
    $sql = "UPDATE payment_verifications SET status = ?";
    $params = [$status];
    
    if ($verifiedAt) {
        $sql .= ", verified_at = ?";
        $params[] = $verifiedAt;
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $paymentId;
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

// Content Security Policy
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
?>
