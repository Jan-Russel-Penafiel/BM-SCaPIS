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
    
    $purokId = intval($_POST['purok_id']);
    
    // Check if purok exists
    $stmt = $pdo->prepare("SELECT id, purok_name, purok_leader_id FROM puroks WHERE id = ?");
    $stmt->execute([$purokId]);
    $purok = $stmt->fetch();
    
    if (!$purok) {
        throw new Exception('Purok not found');
    }
    
    // Check if there are residents assigned to this purok
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE purok_id = ?");
    $stmt->execute([$purokId]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        throw new Exception("Cannot delete purok: There are {$result['count']} resident(s) assigned to this purok. Please reassign them first.");
    }
    
    // If purok has a leader, demote them back to resident
    if ($purok['purok_leader_id']) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'resident' WHERE id = ?");
        $stmt->execute([$purok['purok_leader_id']]);
    }
    
    // Delete purok
    $stmt = $pdo->prepare("DELETE FROM puroks WHERE id = ?");
    $stmt->execute([$purokId]);
    
    $_SESSION['success'] = "Purok '{$purok['purok_name']}' has been deleted successfully";
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: purok-leaders.php');
exit;
