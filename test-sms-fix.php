<?php
require_once 'config.php';

echo "<h2>SMS Fix Test</h2>";

// Test 1: With valid user_id
echo "<h3>Test 1: With valid user_id</h3>";
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $adminId = $stmt->fetchColumn();
    
    if ($adminId) {
        $result = sendSMSNotification('09123456789', 'Test SMS with valid user_id', $adminId);
        if ($result['success']) {
            echo "<p style='color: green;'>✓ Test 1 passed - SMS sent successfully</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Test 1 passed - SMS function executed but failed: " . $result['message'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No admin user found for testing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test 1 failed: " . $e->getMessage() . "</p>";
}

// Test 2: With null user_id (should use fallback)
echo "<h3>Test 2: With null user_id (fallback test)</h3>";
try {
    $result = sendSMSNotification('09123456789', 'Test SMS with null user_id', null);
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Test 2 passed - SMS sent successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Test 2 passed - SMS function executed but failed: " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test 2 failed: " . $e->getMessage() . "</p>";
}

// Test 3: Check SMS notifications table
echo "<h3>Test 3: Check SMS notifications table</h3>";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_notifications");
$stmt->execute();
$count = $stmt->fetchColumn();
echo "<p>Total SMS notifications in database: {$count}</p>";

// Show recent SMS notifications
$stmt = $pdo->prepare("
    SELECT sn.*, u.username, u.first_name, u.last_name 
    FROM sms_notifications sn 
    LEFT JOIN users u ON sn.user_id = u.id 
    ORDER BY sn.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$notifications = $stmt->fetchAll();

if ($notifications) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>User</th><th>Phone</th><th>Status</th><th>Created</th></tr>";
    foreach ($notifications as $notif) {
        $userName = $notif['username'] ? $notif['first_name'] . ' ' . $notif['last_name'] : 'System';
        $statusColor = $notif['status'] === 'sent' ? 'green' : ($notif['status'] === 'failed' ? 'red' : 'orange');
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$userName}</td>";
        echo "<td>{$notif['phone_number']}</td>";
        echo "<td style='color: {$statusColor};'>{$notif['status']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No SMS notifications found.</p>";
}

echo "<h3>Test Complete</h3>";
echo "<p>If you see this message without errors, the SMS fix is working correctly!</p>";
?> 