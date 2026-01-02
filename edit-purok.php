<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: purok-leaders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: purok-leaders.php');
    exit;
}

try {
    // Validate required fields
    if (empty($_POST['purok_id'])) {
        throw new Exception('Purok ID is required');
    }
    
    if (empty($_POST['purok_name'])) {
        throw new Exception('Purok name is required');
    }
    
    $purokId = intval($_POST['purok_id']);
    $purokName = trim($_POST['purok_name']);
    
    // Check if purok exists
    $stmt = $pdo->prepare("SELECT id FROM puroks WHERE id = ?");
    $stmt->execute([$purokId]);
    if (!$stmt->fetch()) {
        throw new Exception('Purok not found');
    }
    
    // Check if another purok with the same name exists
    $stmt = $pdo->prepare("SELECT id FROM puroks WHERE purok_name = ? AND id != ?");
    $stmt->execute([$purokName, $purokId]);
    if ($stmt->fetch()) {
        throw new Exception('Another purok with this name already exists');
    }
    
    // Update purok
    $stmt = $pdo->prepare("UPDATE puroks SET purok_name = ? WHERE id = ?");
    $stmt->execute([$purokName, $purokId]);
    
    $_SESSION['success'] = 'Purok updated successfully';
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: purok-leaders.php');
exit;
