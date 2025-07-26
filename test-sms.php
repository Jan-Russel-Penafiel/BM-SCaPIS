<?php
require_once 'config.php';

// Test SMS functionality
echo "<h2>SMS Notification Test</h2>";

// Test phone number formatting
$testNumbers = [
    '09123456789',
    '9123456789',
    '+639123456789',
    '639123456789'
];

echo "<h3>Phone Number Formatting Test:</h3>";
foreach ($testNumbers as $number) {
    $formatted = formatPhoneNumber($number);
    echo "Original: {$number} -> Formatted: {$formatted}<br>";
}

// Test SMS sending (only if API key is configured)
$stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'philsms_api_key'");
$stmt->execute();
$apiKey = $stmt->fetchColumn();

if (!empty($apiKey) && $apiKey !== 'your_philsms_api_key_here') {
    echo "<h3>SMS Sending Test:</h3>";
    
    // Test with a sample number (replace with actual test number)
    $testNumber = '09123456789'; // Replace with actual test number
    $testMessage = "Test SMS from BM-SCaPIS system. Time: " . date('Y-m-d H:i:s');
    
    try {
        // Get a valid user_id for testing
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $testUserId = $stmt->fetchColumn();
        
        $result = sendSMSNotification($testNumber, $testMessage, $testUserId);
        if ($result['success']) {
            echo "<p style='color: green;'>✓ SMS sent successfully!</p>";
        } else {
            echo "<p style='color: red;'>✗ SMS sending failed: " . $result['message'] . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ SMS Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h3>SMS Configuration:</h3>";
    echo "<p style='color: orange;'>⚠ PhilSMS API key not configured. Please set up the API key in system settings.</p>";
}

// Show recent SMS notifications
echo "<h3>Recent SMS Notifications:</h3>";
$stmt = $pdo->prepare("
    SELECT * FROM sms_notifications 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute();
$notifications = $stmt->fetchAll();

if ($notifications) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Phone</th><th>Message</th><th>Status</th><th>Created</th></tr>";
    foreach ($notifications as $notif) {
        $statusColor = $notif['status'] === 'sent' ? 'green' : ($notif['status'] === 'failed' ? 'red' : 'orange');
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['phone_number']}</td>";
        echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
        echo "<td style='color: {$statusColor};'>{$notif['status']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No SMS notifications found.</p>";
}
?> 