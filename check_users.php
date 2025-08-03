<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('SELECT id, username FROM users ORDER BY id');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Users in database:' . PHP_EOL;
    foreach ($users as $user) {
        echo 'ID: ' . $user['id'] . ', Username: ' . $user['username'] . PHP_EOL;
    }
    if (empty($users)) {
        echo 'No users found in the database.' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
