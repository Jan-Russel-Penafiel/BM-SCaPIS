<?php
// Prevent any output before headers
ob_start();

require_once '../config.php';

// Set JSON content type header first
header('Content-Type: application/json');

try {
    // Require login and must be admin
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if (!isset($_POST['resident_id'])) {
        throw new Exception('Resident ID is required');
    }

    $residentId = $_POST['resident_id'];

    // Verify resident exists and is a resident
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'resident'");
    $stmt->execute([$residentId]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resident) {
        throw new Exception('Resident not found');
    }

    // Validate required fields based on database schema
    $requiredFields = ['first_name', 'last_name', 'birthdate', 'gender', 'civil_status'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    // Validate gender enum
    if (!in_array($_POST['gender'], ['Male', 'Female', 'Other'])) {
        throw new Exception('Invalid gender value');
    }

    // Validate civil status enum
    if (!in_array($_POST['civil_status'], ['Single', 'Married', 'Divorced', 'Widowed'])) {
        throw new Exception('Invalid civil status value');
    }

    // Validate email if provided
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate phone number format
    if (!empty($_POST['contact_number']) && !preg_match('/^09[0-9]{9}$/', $_POST['contact_number'])) {
        throw new Exception('Please enter a valid Philippine phone number (09XXXXXXXXX)');
    }

    // Validate emergency contact number format
    if (!empty($_POST['emergency_contact_number']) && !preg_match('/^09[0-9]{9}$/', $_POST['emergency_contact_number'])) {
        throw new Exception('Please enter a valid emergency contact number (09XXXXXXXXX)');
    }

    // Calculate age from birthdate
    $birthdate = new DateTime($_POST['birthdate']);
    $today = new DateTime();
    $age = $birthdate->diff($today)->y;

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Update resident information based on database schema
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
                purok_id = ?,
                address = ?,
                occupation = ?,
                monthly_income = ?,
                emergency_contact_name = ?,
                emergency_contact_number = ?,
                sms_notifications = ?,
                email_notifications = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND role = 'resident'
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
            $_POST['purok_id'] ?? $resident['purok_id'],
            $_POST['address'] ?? null,
            $_POST['occupation'] ?? null,
            $_POST['monthly_income'] ? floatval($_POST['monthly_income']) : null,
            $_POST['emergency_contact_name'] ?? null,
            $_POST['emergency_contact_number'] ?? null,
            isset($_POST['sms_notifications']) ? 1 : 0,
            isset($_POST['email_notifications']) ? 1 : 0,
            $residentId
        ]);

        // Log the activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id, 
                action, 
                table_affected, 
                record_id,
                old_values,
                new_values,
                ip_address,
                user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Get old values for logging
        $oldValues = $resident;
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
            'purok_id' => $_POST['purok_id'] ?? $resident['purok_id'],
            'address' => $_POST['address'] ?? null,
            'occupation' => $_POST['occupation'] ?? null,
            'monthly_income' => $_POST['monthly_income'] ? floatval($_POST['monthly_income']) : null,
            'emergency_contact_name' => $_POST['emergency_contact_name'] ?? null,
            'emergency_contact_number' => $_POST['emergency_contact_number'] ?? null,
            'sms_notifications' => isset($_POST['sms_notifications']) ? 1 : 0,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0
        ];

        $stmt->execute([
            $_SESSION['user_id'],
            'update',
            'users',
            $residentId,
            json_encode($oldValues),
            json_encode($newValues),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);

        // Create system notification
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type,
                title,
                message,
                target_role,
                target_user_id,
                metadata
            ) VALUES (
                'resident_updated',
                'Resident Information Updated',
                ?,
                'all',
                ?,
                ?
            )
        ");

        $notificationMessage = "Resident {$_POST['first_name']} {$_POST['last_name']}'s information has been updated by " . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
        $metadata = json_encode([
            'user_id' => $residentId,
            'updated_by' => $_SESSION['user_id'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $stmt->execute([
            $notificationMessage,
            $residentId,
            $metadata
        ]);

        $pdo->commit();

        // Clear any output buffers
        ob_clean();

        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Resident information updated successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Clear any output buffers
    ob_clean();

    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 