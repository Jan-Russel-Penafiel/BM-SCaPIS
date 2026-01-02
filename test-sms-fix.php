<?php
/**
 * Quick SMS Test - After Fix
 */
require_once 'config.php';
require_once 'sms_functions.php';

echo "<h1>SMS Test After Fix</h1>";
echo "<pre>";

$config = getSMSConfig($pdo);
echo "Testing sendSMSUsingIPROG()...\n\n";

$result = sendSMSUsingIPROG('09677726912', 'Test SMS notification after fix', $config['iprog_api_key']);

echo "Result:\n";
print_r($result);

echo "\n\nTesting sendSMSNotification()...\n\n";
$result2 = sendSMSNotification('09677726912', 'Full notification test after fix', null, 'test');

echo "Result:\n";
print_r($result2);

// Check if SMS was saved to database
echo "\n\nChecking latest SMS in database...\n";
$stmt = $pdo->query("SELECT id, phone_number, status, message, api_response FROM sms_notifications ORDER BY id DESC LIMIT 1");
$latest = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($latest);

echo "</pre>";
?>
