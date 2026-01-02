<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: document-types.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: document-types.php');
    exit;
}

try {
    // Validate required fields
    if (empty($_POST['type_name'])) {
        throw new Exception('Document type name is required');
    }
    
    if (!isset($_POST['fee']) || $_POST['fee'] < 0) {
        throw new Exception('Valid processing fee is required');
    }
    
    if (!isset($_POST['processing_days']) || $_POST['processing_days'] < 1) {
        throw new Exception('Valid processing days is required');
    }
    
    $typeName = trim($_POST['type_name']);
    $description = trim($_POST['description'] ?? '');
    $fee = floatval($_POST['fee']);
    $processingDays = intval($_POST['processing_days']);
    $requirements = trim($_POST['requirements'] ?? '');
    
    // Check if document type already exists
    $stmt = $pdo->prepare("SELECT id FROM document_types WHERE type_name = ?");
    $stmt->execute([$typeName]);
    if ($stmt->fetch()) {
        throw new Exception('A document type with this name already exists');
    }
    
    // Insert new document type
    $stmt = $pdo->prepare("
        INSERT INTO document_types (type_name, description, fee, processing_days, requirements, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$typeName, $description, $fee, $processingDays, $requirements]);
    
    $_SESSION['success'] = 'Document type added successfully';
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: document-types.php');
exit;
