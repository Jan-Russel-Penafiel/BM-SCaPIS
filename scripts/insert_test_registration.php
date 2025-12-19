<?php
require_once dirname(__DIR__) . '/config.php';

try {
    $pdo->beginTransaction();
    $username = 'testreg' . time();
    $password = 'testpass' . rand(100,999); // plaintext per DB default
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, status, first_name, last_name, gender, civil_status, contact_number, email, purok_id, address) VALUES (:username, :password, 'resident', 'pending', :first, :last, :gender, :civil, :contact, :email, :purok, :address)");
    $stmt->execute([
        ':username' => $username,
        ':password' => $password,
        ':first' => 'Test',
        ':last' => 'Resident',
        ':gender' => 'Male',
        ':civil' => 'Single',
        ':contact' => '09123456789',
        ':email' => $username . '@example.com',
        ':purok' => 1,
        ':address' => 'Test address'
    ]);
    $id = $pdo->lastInsertId();
    $pdo->commit();
    echo "INSERTED:$id|$username|$password\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'ERROR: ' . $e->getMessage();
}
