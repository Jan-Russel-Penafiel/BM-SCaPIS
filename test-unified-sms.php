<?php
require_once 'config.php';

echo "<h2>Unified SMS Functions Test</h2>";

// Test 1: Basic SMS sending
echo "<h3>Test 1: Basic SMS Sending</h3>";
try {
    $result = sendSMSNotification('09123456789', 'Test unified SMS function', 1);
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Basic SMS sent successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Basic SMS failed: " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Basic SMS error: " . $e->getMessage() . "</p>";
}

// Test 2: Phone number formatting
echo "<h3>Test 2: Phone Number Formatting</h3>";
$testNumbers = [
    '09123456789',
    '9123456789',
    '+639123456789',
    '639123456789'
];

foreach ($testNumbers as $number) {
    $formatted = formatPhoneNumber($number);
    echo "<p>Original: {$number} -> Formatted: {$formatted}</p>";
}

// Test 3: SMS Configuration
echo "<h3>Test 3: SMS Configuration</h3>";
try {
    $config = getSMSConfig($pdo);
    echo "<p>API Key: " . (empty($config['philsms_api_key']) ? 'Not configured' : 'Configured') . "</p>";
    echo "<p>Sender Name: " . ($config['philsms_sender_name'] ?? 'Not configured') . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Config error: " . $e->getMessage() . "</p>";
}

// Test 4: Application Status SMS (if application exists)
echo "<h3>Test 4: Application Status SMS</h3>";
try {
    $stmt = $pdo->prepare("SELECT id FROM applications LIMIT 1");
    $stmt->execute();
    $appId = $stmt->fetchColumn();
    
    if ($appId) {
        $result = sendApplicationStatusSMS($appId, 'processing');
        if ($result['success']) {
            echo "<p style='color: green;'>✓ Application status SMS sent successfully</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Application status SMS failed: " . $result['message'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No applications found for testing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Application status SMS error: " . $e->getMessage() . "</p>";
}

// Test 5: Payment Notification SMS (if application exists)
echo "<h3>Test 5: Payment Notification SMS</h3>";
try {
    if ($appId) {
        $result = sendPaymentNotificationSMS($appId, 'gcash', 100.00, 'TEST123');
        if ($result['success']) {
            echo "<p style='color: green;'>✓ Payment notification SMS sent successfully</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Payment notification SMS failed: " . $result['message'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No applications found for testing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Payment notification SMS error: " . $e->getMessage() . "</p>";
}

// Test 6: Admin Notification SMS
echo "<h3>Test 6: Admin Notification SMS</h3>";
try {
    $result = sendAdminNotificationSMS('Test admin notification from unified SMS system', 'test');
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Admin notification SMS sent successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Admin notification SMS failed: " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Admin notification SMS error: " . $e->getMessage() . "</p>";
}

// Test 7: SMS Statistics
echo "<h3>Test 7: SMS Statistics</h3>";
try {
    $stats = getSMSStatistics();
    if (isset($stats['error'])) {
        echo "<p style='color: red;'>✗ Statistics error: " . $stats['error'] . "</p>";
    } else {
        echo "<p>Total Sent: " . $stats['total_sent'] . "</p>";
        echo "<p>Total Failed: " . $stats['total_failed'] . "</p>";
        echo "<p>Total Pending: " . $stats['total_pending'] . "</p>";
        echo "<p>Recent SMS (7 days): " . $stats['recent_sms'] . "</p>";
        echo "<p>Success Rate: " . $stats['success_rate'] . "%</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Statistics error: " . $e->getMessage() . "</p>";
}

// Test 8: Recent SMS Notifications
echo "<h3>Test 8: Recent SMS Notifications</h3>";
$stmt = $pdo->prepare("
    SELECT sn.*, u.username, u.first_name, u.last_name 
    FROM sms_notifications sn 
    LEFT JOIN users u ON sn.user_id = u.id 
    ORDER BY sn.created_at DESC 
    LIMIT 10
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
echo "<p>All unified SMS functions have been tested. Check the results above for any issues.</p>";
?> 