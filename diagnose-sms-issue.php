<?php
// Comprehensive SMS diagnostic - check why SMS isn't sending in application
require_once 'config.php';

echo "=== COMPREHENSIVE SMS DIAGNOSTIC ===\n\n";

// 1. Check if functions are available
echo "1. Function Availability:\n";
echo str_repeat("-", 70) . "\n";
$functions = ['sendApplicationStatusSMS', 'sendSMSNotification', 'formatSMSTemplate', 'getSMSConfig'];
foreach ($functions as $func) {
    echo "   $func: " . (function_exists($func) ? "✓" : "✗") . "\n";
}
echo "\n";

// 2. Check applications ready to trigger SMS
echo "2. Applications Status:\n";
echo str_repeat("-", 70) . "\n";
$stmt = $pdo->query("
    SELECT a.id, a.application_number, a.status, a.payment_status,
           u.first_name, u.last_name, u.contact_number, u.sms_notifications,
           dt.type_name
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN document_types dt ON a.document_type_id = dt.id
    ORDER BY a.created_at DESC
    LIMIT 10
");
$apps = $stmt->fetchAll();

echo "Recent Applications:\n";
foreach ($apps as $app) {
    $smsStatus = $app['sms_notifications'] ? '✓' : '✗';
    $phoneStatus = !empty($app['contact_number']) ? '✓' : '✗';
    echo "  App {$app['id']} ({$app['application_number']})\n";
    echo "    Status: {$app['status']} | Payment: {$app['payment_status']}\n";
    echo "    User: {$app['first_name']} {$app['last_name']}\n";
    echo "    SMS Enabled: $smsStatus | Has Phone: $phoneStatus ({$app['contact_number']})\n";
    echo "\n";
}

// 3. Check which files call sendApplicationStatusSMS
echo "3. Checking Files That Send SMS:\n";
echo str_repeat("-", 70) . "\n";
$files = [
    'process-application.php',
    'complete-application.php',
    'allow-payment.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hasSMSCall = strpos($content, 'sendApplicationStatusSMS') !== false;
        echo "   $file: " . ($hasSMSCall ? "✓ HAS SMS CALL" : "✗ NO SMS CALL") . "\n";
        
        if ($hasSMSCall) {
            // Check if SMS is conditional
            preg_match_all('/if\s*\([^)]*sms_notifications[^)]*\).*sendApplicationStatusSMS/s', $content, $matches);
            if (!empty($matches[0])) {
                echo "      ⚠ SMS call is conditional on sms_notifications\n";
            }
        }
    } else {
        echo "   $file: ✗ FILE NOT FOUND\n";
    }
}
echo "\n";

// 4. Test sendApplicationStatusSMS directly
echo "4. Direct SMS Send Test:\n";
echo str_repeat("-", 70) . "\n";

// Find an application to test
$stmt = $pdo->query("
    SELECT a.id, a.application_number, u.contact_number, u.sms_notifications
    FROM applications a
    JOIN users u ON a.user_id = u.id
    WHERE u.sms_notifications = 1 
    AND u.contact_number IS NOT NULL
    AND u.contact_number != ''
    LIMIT 1
");
$testApp = $stmt->fetch();

if ($testApp) {
    echo "Testing with App ID: {$testApp['id']} ({$testApp['application_number']})\n";
    echo "User has SMS: " . ($testApp['sms_notifications'] ? 'YES' : 'NO') . "\n";
    echo "User phone: {$testApp['contact_number']}\n\n";
    
    echo "Attempting to send test SMS...\n";
    try {
        $result = sendApplicationStatusSMS($testApp['id'], 'processing');
        echo "Result: " . ($result['success'] ? '✓ SUCCESS' : '✗ FAILED') . "\n";
        echo "Message: {$result['message']}\n";
        
        if (isset($result['reference_id'])) {
            echo "Reference: {$result['reference_id']}\n";
        }
    } catch (Exception $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        echo "Stack: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "✗ No suitable application found for testing\n";
}
echo "\n";

// 5. Check recent SMS logs
echo "5. Recent SMS Logs (Last 10):\n";
echo str_repeat("-", 70) . "\n";
$stmt = $pdo->query("
    SELECT sn.id, sn.phone_number, sn.status, sn.created_at, sn.sent_at,
           u.first_name, u.last_name,
           SUBSTRING(sn.message, 1, 60) as msg_preview
    FROM sms_notifications sn
    LEFT JOIN users u ON sn.user_id = u.id
    ORDER BY sn.created_at DESC
    LIMIT 10
");

while ($row = $stmt->fetch()) {
    $user = $row['first_name'] ? "{$row['first_name']} {$row['last_name']}" : "Unknown";
    $status = $row['status'];
    $statusIcon = $status === 'sent' ? '✓' : ($status === 'failed' ? '✗' : '○');
    
    echo "  $statusIcon [{$status}] {$row['phone_number']} ($user)\n";
    echo "     Created: {$row['created_at']} | Sent: " . ($row['sent_at'] ?? 'N/A') . "\n";
    echo "     Message: {$row['msg_preview']}...\n\n";
}

// 6. Check error logs
echo "6. Checking for SMS-related errors:\n";
echo str_repeat("-", 70) . "\n";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "Reading error log: $errorLog\n";
    $lines = file($errorLog);
    $smsErrors = array_filter($lines, function($line) {
        return stripos($line, 'sms') !== false || stripos($line, 'iprog') !== false;
    });
    
    if (!empty($smsErrors)) {
        echo "Recent SMS-related errors:\n";
        foreach (array_slice($smsErrors, -5) as $error) {
            echo "  " . trim($error) . "\n";
        }
    } else {
        echo "No SMS-related errors found in log\n";
    }
} else {
    echo "Error log not configured or not found\n";
}
echo "\n";

// 7. Summary and recommendations
echo str_repeat("=", 70) . "\n";
echo "DIAGNOSTIC SUMMARY\n";
echo str_repeat("=", 70) . "\n";

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$smsEnabledUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE sms_notifications = 1")->fetchColumn();
$usersWithPhone = $pdo->query("SELECT COUNT(*) FROM users WHERE contact_number IS NOT NULL AND contact_number != ''")->fetchColumn();
$totalSMS = $pdo->query("SELECT COUNT(*) FROM sms_notifications")->fetchColumn();
$sentSMS = $pdo->query("SELECT COUNT(*) FROM sms_notifications WHERE status = 'sent'")->fetchColumn();
$failedSMS = $pdo->query("SELECT COUNT(*) FROM sms_notifications WHERE status = 'failed'")->fetchColumn();

echo "\nUser Statistics:\n";
echo "  Total Users: $totalUsers\n";
echo "  SMS Enabled: $smsEnabledUsers (" . round($smsEnabledUsers/$totalUsers*100) . "%)\n";
echo "  With Phone: $usersWithPhone (" . round($usersWithPhone/$totalUsers*100) . "%)\n";

echo "\nSMS Statistics:\n";
echo "  Total SMS: $totalSMS\n";
echo "  Sent: $sentSMS (" . ($totalSMS > 0 ? round($sentSMS/$totalSMS*100) : 0) . "%)\n";
echo "  Failed: $failedSMS (" . ($totalSMS > 0 ? round($failedSMS/$totalSMS*100) : 0) . "%)\n";

echo "\nRECOMMENDATIONS:\n";
if ($smsEnabledUsers < $totalUsers) {
    echo "  ⚠ Only $smsEnabledUsers users have SMS enabled. Check if sms_notifications = 1\n";
}
if ($usersWithPhone < $smsEnabledUsers) {
    echo "  ⚠ Some SMS-enabled users don't have phone numbers\n";
}
if ($failedSMS > $sentSMS && $totalSMS > 0) {
    echo "  ⚠ More failed SMS than sent - check IPROG credits and API key\n";
}
if ($totalSMS == 0) {
    echo "  ⚠ No SMS records found - SMS may not be triggered from applications\n";
}

echo "\nTo trigger SMS when processing applications:\n";
echo "1. Ensure user has sms_notifications = 1\n";
echo "2. Ensure user has valid contact_number\n";
echo "3. Process/Complete application from admin panel\n";
echo "4. Check sms_notifications table for new records\n";
