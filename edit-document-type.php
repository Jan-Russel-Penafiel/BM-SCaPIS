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
    if (empty($_POST['id'])) {
        throw new Exception('Document type ID is required');
    }
    
    if (empty($_POST['type_name'])) {
        throw new Exception('Document type name is required');
    }
    
    if (!isset($_POST['fee']) || $_POST['fee'] < 0) {
        throw new Exception('Valid processing fee is required');
    }
    
    if (!isset($_POST['processing_days']) || $_POST['processing_days'] < 1) {
        throw new Exception('Valid processing days is required');
    }
    
    $id = intval($_POST['id']);
    $typeName = trim($_POST['type_name']);
    $description = trim($_POST['description'] ?? '');
    $fee = floatval($_POST['fee']);
    $processingDays = intval($_POST['processing_days']);
    $requirements = trim($_POST['requirements'] ?? '');
    
    // Check if document type exists
    $stmt = $pdo->prepare("SELECT id FROM document_types WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Document type not found');
    }
    
    // Check if another document type with the same name exists
    $stmt = $pdo->prepare("SELECT id FROM document_types WHERE type_name = ? AND id != ?");
    $stmt->execute([$typeName, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Another document type with this name already exists');
    }
    
    // Update document type
    $stmt = $pdo->prepare("
        UPDATE document_types 
        SET type_name = ?, description = ?, fee = ?, processing_days = ?, requirements = ?
        WHERE id = ?
    ");
    $stmt->execute([$typeName, $description, $fee, $processingDays, $requirements, $id]);
    
    $_SESSION['success'] = 'Document type updated successfully';
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: document-types.php');
exit;
