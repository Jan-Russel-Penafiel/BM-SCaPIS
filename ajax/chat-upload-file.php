<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $conversationId = $_POST['conversation_id'] ?? null;
    $applicationId = $_POST['application_id'] ?? null;
    $context = $_POST['context'] ?? 'application';
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    
    // Validate file
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($fileSize > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
        exit;
    }
    
    // Allowed file types
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'File type not allowed']);
        exit;
    }
    
    // Rate limiting check
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM chat_rate_limits 
        WHERE user_id = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$userId]);
    $rateLimitCheck = $stmt->fetch();
    
    if ($rateLimitCheck['count'] >= 5) { // Max 5 file uploads per minute
        echo json_encode(['success' => false, 'message' => 'Upload rate limit exceeded. Please wait before uploading another file.']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Create conversation if it doesn't exist
        if (!$conversationId) {
            // Determine subject based on context
            $subject = 'General Support';
            if ($context === 'payment') {
                $subject = 'Payment Support';
            } elseif ($applicationId) {
                // Get application document type
                $stmt = $pdo->prepare("SELECT document_type FROM applications WHERE id = ?");
                $stmt->execute([$applicationId]);
                $app = $stmt->fetch();
                if ($app) {
                    $subject = $app['document_type'] . ' Support';
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO chat_conversations (resident_id, application_id, subject, context, status, created_at) 
                VALUES (?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $userRole === 'resident' ? $userId : null,
                $applicationId,
                $subject,
                $context
            ]);
            $conversationId = $pdo->lastInsertId();
        }
        
        // Generate unique file name
        $uniqueFileName = time() . '_' . uniqid() . '.' . $fileExt;
        $uploadDir = '../uploads/chat/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filePath = $uploadDir . $uniqueFileName;
        
        // Move uploaded file
        if (!move_uploaded_file($fileTmpName, $filePath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Insert message with file
        $messageContent = "📎 Shared a file: " . $fileName;
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (conversation_id, sender_id, sender_type, message_type, message_content, file_path, file_name, created_at) 
            VALUES (?, ?, ?, 'file', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $conversationId, 
            $userId, 
            $userRole, 
            $messageContent, 
            'uploads/chat/' . $uniqueFileName, 
            $fileName
        ]);
        $messageId = $pdo->lastInsertId();
        
        // Update conversation last activity
        $stmt = $pdo->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$conversationId]);
        
        // Add to rate limit
        $stmt = $pdo->prepare("
            INSERT INTO chat_rate_limits (user_id, created_at) VALUES (?, NOW())
        ");
        $stmt->execute([$userId]);
        
        $pdo->commit();
        
        // Get the created message with sender name
        $stmt = $pdo->prepare("
            SELECT cm.*, 
                   CASE 
                       WHEN cm.sender_type = 'admin' THEN CONCAT('Admin (', u.first_name, ' ', u.last_name, ')')
                       ELSE CONCAT(ur.first_name, ' ', ur.last_name)
                   END as sender_name
            FROM chat_messages cm
            LEFT JOIN users u ON cm.sender_id = u.id AND cm.sender_type = 'admin'
            LEFT JOIN chat_conversations cc ON cm.conversation_id = cc.id
            LEFT JOIN users ur ON cc.resident_id = ur.id
            WHERE cm.id = ?
        ");
        $stmt->execute([$messageId]);
        $messageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'conversation_id' => $conversationId,
            'message' => $messageData
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        // Clean up uploaded file if database insertion failed
        if (isset($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("File upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
}
?>