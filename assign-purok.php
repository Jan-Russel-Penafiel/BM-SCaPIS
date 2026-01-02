<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: residents.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: residents.php');
    exit;
}

try {
    // Validate required fields
    if (empty($_POST['purok_id'])) {
        throw new Exception('Please select a purok');
    }
    
    if (empty($_POST['resident_ids']) || !is_array($_POST['resident_ids'])) {
        throw new Exception('Please select at least one resident');
    }
    
    $purokId = intval($_POST['purok_id']);
    $residentIds = array_map('intval', $_POST['resident_ids']);
    
    // Check if purok exists
    $stmt = $pdo->prepare("SELECT id, purok_name FROM puroks WHERE id = ?");
    $stmt->execute([$purokId]);
    $purok = $stmt->fetch();
    
    if (!$purok) {
        throw new Exception('Selected purok not found');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update residents' purok
        $placeholders = str_repeat('?,', count($residentIds) - 1) . '?';
        $stmt = $pdo->prepare("
            UPDATE users 
            SET purok_id = ?
            WHERE id IN ($placeholders) AND role = 'resident'
        ");
        $stmt->execute(array_merge([$purokId], $residentIds));
        
        $updatedCount = $stmt->rowCount();
        
        // Log the activity
        if (function_exists('logActivity')) {
            logActivity(
                $_SESSION['user_id'],
                "Assigned $updatedCount resident(s) to {$purok['purok_name']}",
                'users',
                null,
                json_encode(['purok_id' => $purokId, 'resident_ids' => $residentIds])
            );
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "$updatedCount resident(s) have been assigned to {$purok['purok_name']}";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: residents.php');
exit;
