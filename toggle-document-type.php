<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: document-types.php');
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Document type ID is required';
    header('Location: document-types.php');
    exit;
}

try {
    $id = intval($_GET['id']);
    
    // Get current status
    $stmt = $pdo->prepare("SELECT id, type_name, is_active FROM document_types WHERE id = ?");
    $stmt->execute([$id]);
    $docType = $stmt->fetch();
    
    if (!$docType) {
        throw new Exception('Document type not found');
    }
    
    // Toggle status
    $newStatus = $docType['is_active'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE document_types SET is_active = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    
    $action = $newStatus ? 'activated' : 'deactivated';
    $_SESSION['success'] = "Document type '{$docType['type_name']}' has been {$action}";
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: document-types.php');
exit;
