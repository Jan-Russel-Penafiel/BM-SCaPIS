<?php
/**
 * Test SMS Plain Text Conversion and Sending
 * This script tests the SMS notification system
 */

require_once 'config.php';
require_once 'sms_functions.php';

echo "<html><head><title>SMS Test</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;} .error{color:red;} .box{background:#f5f5f5;padding:15px;margin:10px 0;border-radius:5px;} pre{background:#fff;padding:10px;overflow-x:auto;}</style></head><body>";

echo "<h1>SMS Functions Test</h1>";

// Test 1: Check if functions exist
echo "<h2>1. Checking functions exist</h2>";
echo "<div class='box'>";
$functions = ['formatSMSTemplate', 'convertToPlainMessage', 'sendSMSUsingIPROG', 'sendApplicationStatusSMS', 'sendSMSNotification'];
foreach($functions as $func) {
    $status = function_exists($func) ? "<span class='success'>✓ OK</span>" : "<span class='error'>✗ MISSING</span>";
    echo "<p><code>{$func}()</code>: {$status}</p>";
}
echo "</div>";

// Test 2: Test convertToPlainMessage
echo "<h2>2. Testing convertToPlainMessage()</h2>";
echo "<div class='box'>";
$test_messages = [
    'Your application_number is ready',
    'document_type_id has been processed',
    'payment_status is paid',
    'contact_number: 09123456789',
    'user_id record found in database',
    'Check your appointment_date',
    'transaction_id: TXN12345',
    'gcash_reference: GCASH789'
];

echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>Original (with database terms)</th><th>Converted (plain text)</th></tr>";
foreach($test_messages as $msg) {
    $converted = convertToPlainMessage($msg);
    echo "<tr><td><code>{$msg}</code></td><td>{$converted}</td></tr>";
}
echo "</table>";
echo "</div>";

// Test 3: Test formatSMSTemplate
echo "<h2>3. Testing formatSMSTemplate()</h2>";
echo "<div class='box'>";
$test_bodies = [
    'Your Barangay Clearance APP-2024-001 is ready for pickup.',
    'Payment received for application_number APP-2024-002',
    'Your document_type_id request has been processed'
];

foreach($test_bodies as $body) {
    $formatted = formatSMSTemplate($body);
    echo "<p><strong>Input:</strong> <code>{$body}</code></p>";
    echo "<p><strong>Output:</strong> {$formatted}</p><hr>";
}
echo "</div>";

// Test 4: Check SMS configuration
echo "<h2>4. SMS Configuration</h2>";
echo "<div class='box'>";
try {
    $config = getSMSConfig($pdo);
    $apiKeyDisplay = isset($config['iprog_api_key']) ? substr($config['iprog_api_key'], 0, 10) . '...' : 'NOT SET';
    echo "<p><strong>API Key:</strong> <code>{$apiKeyDisplay}</code></p>";
    echo "<p><strong>Sender Name:</strong> " . ($config['iprog_sender_name'] ?? 'NOT SET') . "</p>";
} catch(Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Check recent SMS notifications in database
echo "<h2>5. Recent SMS Notifications in Database</h2>";
echo "<div class='box'>";
try {
    $stmt = $pdo->query("SELECT id, phone_number, status, message, api_response, created_at, sent_at FROM sms_notifications ORDER BY created_at DESC LIMIT 10");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($notifications) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;font-size:12px;'>";
        echo "<tr><th>ID</th><th>Phone</th><th>Status</th><th>Message</th><th>Created</th></tr>";
        foreach($notifications as $n) {
            $statusClass = $n['status'] === 'sent' ? 'success' : ($n['status'] === 'failed' ? 'error' : '');
            $msgPreview = strlen($n['message']) > 60 ? substr($n['message'], 0, 60) . '...' : $n['message'];
            echo "<tr>";
            echo "<td>{$n['id']}</td>";
            echo "<td>{$n['phone_number']}</td>";
            echo "<td class='{$statusClass}'>{$n['status']}</td>";
            echo "<td>{$msgPreview}</td>";
            echo "<td>{$n['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No SMS notifications found in database.</p>";
    }
} catch(Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 6: Test actual SMS sending (dry run)
echo "<h2>6. Test SMS Sending (Simulation)</h2>";
echo "<div class='box'>";

// Get a test application from database
try {
    $stmt = $pdo->query("
        SELECT a.id, a.application_number, a.status, 
               u.first_name, u.last_name, u.contact_number, u.sms_notifications,
               dt.type_name
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN document_types dt ON a.document_type_id = dt.id
        ORDER BY a.created_at DESC
        LIMIT 1
    ");
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($app) {
        echo "<p><strong>Test Application Found:</strong></p>";
        echo "<ul>";
        echo "<li>Application Number: {$app['application_number']}</li>";
        echo "<li>Document Type: {$app['type_name']}</li>";
        echo "<li>Applicant: {$app['first_name']} {$app['last_name']}</li>";
        echo "<li>Contact: {$app['contact_number']}</li>";
        echo "<li>SMS Enabled: " . ($app['sms_notifications'] ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
        
        // Show what message would be sent
        $testStatuses = ['processing', 'ready_for_pickup', 'completed'];
        echo "<p><strong>Message previews for different statuses:</strong></p>";
        
        foreach ($testStatuses as $status) {
            $mockApp = [
                'application_number' => $app['application_number'],
                'type_name' => $app['type_name'],
                'first_name' => $app['first_name'],
                'last_name' => $app['last_name'],
                'processing_days' => 5
            ];
            $message = generateApplicationStatusMessage($mockApp, $status);
            $formattedMessage = formatSMSTemplate($message);
            echo "<p><strong>Status '{$status}':</strong><br><code>{$formattedMessage}</code></p>";
        }
        
        // Option to send actual test SMS
        if (isset($_GET['send_test']) && $_GET['send_test'] === 'yes' && !empty($app['contact_number'])) {
            echo "<hr><p><strong>Sending actual test SMS...</strong></p>";
            
            $testMessage = "Test notification: Your application {$app['application_number']} system is working correctly. This is a test message.";
            $result = sendSMSNotification($app['contact_number'], $testMessage, null, 'test');
            
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            
            if ($result['success']) {
                echo "<p class='success'>✓ SMS sent successfully!</p>";
            } else {
                echo "<p class='error'>✗ SMS failed: {$result['message']}</p>";
            }
        } else {
            echo "<p><a href='?send_test=yes' onclick=\"return confirm('This will send a real SMS to {$app['contact_number']}. Continue?');\">➤ Click here to send a real test SMS</a></p>";
        }
    } else {
        echo "<p>No applications found in database for testing.</p>";
    }
} catch(Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 7: SMS Statistics
echo "<h2>7. SMS Statistics</h2>";
echo "<div class='box'>";
try {
    $stats = getSMSStatistics();
    if (!isset($stats['error'])) {
        echo "<ul>";
        echo "<li><strong>Total Sent:</strong> {$stats['total_sent']}</li>";
        echo "<li><strong>Total Failed:</strong> {$stats['total_failed']}</li>";
        echo "<li><strong>Total Pending:</strong> {$stats['total_pending']}</li>";
        echo "<li><strong>Recent (7 days):</strong> {$stats['recent_sms']}</li>";
        echo "<li><strong>Success Rate:</strong> {$stats['success_rate']}%</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'>Error: {$stats['error']}</p>";
    }
} catch(Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>
