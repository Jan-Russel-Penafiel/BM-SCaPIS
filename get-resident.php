<?php
require_once 'config.php';

// Require login and must be admin or purok leader
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'purok_leader'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get resident ID from query string
$residentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($residentId <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid resident ID']);
    exit;
}

// Get resident data
$params = [$residentId];
$whereClause = "WHERE u.id = ?";

// Purok leaders can only view residents in their purok
if ($_SESSION['role'] === 'purok_leader') {
    $whereClause .= " AND u.purok_id = ?";
    $params[] = $_SESSION['purok_id'];
}

$stmt = $pdo->prepare("
    SELECT u.*, 
           p.purok_name,
           CONCAT(pl.first_name, ' ', pl.last_name) as purok_leader_name,
           pl.contact_number as purok_leader_contact,
           pl.email as purok_leader_email
    FROM users u
    LEFT JOIN puroks p ON u.purok_id = p.id
    LEFT JOIN users pl ON p.purok_leader_id = pl.id
    $whereClause
");

$stmt->execute($params);
$resident = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resident) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Resident not found']);
    exit;
}

// Return resident data as JSON
header('Content-Type: application/json');
echo json_encode($resident);
?> 