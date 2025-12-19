<?php
require_once __DIR__ . '/../config.php';
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo $count;
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
