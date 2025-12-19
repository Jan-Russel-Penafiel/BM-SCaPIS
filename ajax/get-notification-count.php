<?php
// Notification count responder
// Returns JSON with expected shape: { success: true, count: int, notifications: [] }
// This is a safe fallback and can be extended to query real notification data.

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
	'success' => true,
	'count' => 0,
	'notifications' => []
];

// TODO: Replace with actual notification query logic. Example:
// $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0");
// $stmt->execute([$_SESSION['user_id']]);
// $rows = $stmt->fetchAll();
// $response['count'] = count($rows);
// $response['notifications'] = $rows;

echo json_encode($response);
exit;
