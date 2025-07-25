<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$residentId = isset($_POST['resident_id']) ? intval($_POST['resident_id']) : 0;

if ($residentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resident ID']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Validate required fields
    $requiredFields = ['first_name', 'last_name', 'birthdate', 'gender', 'civil_status'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
        }
    }
    
    // Calculate age from birthdate
    $birthdate = new DateTime($_POST['birthdate']);
    $now = new DateTime();
    if ($birthdate > $now) {
        throw new Exception('Birthdate cannot be in the future.');
    }
    $age = $now->diff($birthdate)->y;
    
    // Validate email if provided
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }
    
    // Update user
    $stmt = $pdo->prepare("
        UPDATE users SET
            first_name = ?,
            middle_name = ?,
            last_name = ?,
            suffix = ?,
            birthdate = ?,
            age = ?,
            gender = ?,
            civil_status = ?,
            contact_number = ?,
            email = ?,
            address = ?,
            occupation = ?,
            monthly_income = ?,
            emergency_contact_name = ?,
            emergency_contact_number = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['first_name'],
        $_POST['middle_name'] ?? null,
        $_POST['last_name'],
        $_POST['suffix'] ?? null,
        $_POST['birthdate'],
        $age,
        $_POST['gender'],
        $_POST['civil_status'],
        $_POST['contact_number'] ?? null,
        $_POST['email'] ?? null,
        $_POST['address'] ?? null,
        $_POST['occupation'] ?? null,
        $_POST['monthly_income'] ? floatval($_POST['monthly_income']) : null,
        $_POST['emergency_contact_name'] ?? null,
        $_POST['emergency_contact_number'] ?? null,
        $residentId
    ]);
    
    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (
            user_id, action, table_affected, record_id,
            old_values, new_values, ip_address, user_agent
        ) VALUES (
            ?, 'update_resident', 'users', ?,
            ?, ?, ?, ?
        )
    ");
    
    // Get old values for logging
    $oldValues = $pdo->query("SELECT * FROM users WHERE id = $residentId")->fetch(PDO::FETCH_ASSOC);
    unset($oldValues['password']); // Remove sensitive data
    
    $newValues = [
        'first_name' => $_POST['first_name'],
        'middle_name' => $_POST['middle_name'] ?? null,
        'last_name' => $_POST['last_name'],
        'suffix' => $_POST['suffix'] ?? null,
        'birthdate' => $_POST['birthdate'],
        'age' => $age,
        'gender' => $_POST['gender'],
        'civil_status' => $_POST['civil_status'],
        'contact_number' => $_POST['contact_number'] ?? null,
        'email' => $_POST['email'] ?? null,
        'address' => $_POST['address'] ?? null,
        'occupation' => $_POST['occupation'] ?? null,
        'monthly_income' => $_POST['monthly_income'] ? floatval($_POST['monthly_income']) : null,
        'emergency_contact_name' => $_POST['emergency_contact_name'] ?? null,
        'emergency_contact_number' => $_POST['emergency_contact_number'] ?? null
    ];
    
    $stmt->execute([
        $_SESSION['user_id'],
        $residentId,
        json_encode($oldValues),
        json_encode($newValues),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    // Create system notification
    $stmt = $pdo->prepare("
        INSERT INTO system_notifications (
            type, title, message, target_role, target_user_id
        ) VALUES (
            'resident_updated',
            'Resident Information Updated',
            ?,
            'admin',
            ?
        )
    ");
    $stmt->execute([
        "Resident {$_POST['first_name']} {$_POST['last_name']}'s information has been updated.",
        $residentId
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Resident information updated successfully.'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 