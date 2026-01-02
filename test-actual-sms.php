<?php
// ACTUAL SMS SENDING TEST - This will send real SMS
require_once 'config.php';

echo "=== ACTUAL SMS SENDING TEST ===\n\n";

// Find an application with SMS-enabled user
$stmt = $pdo->query("
    SELECT a.id, a.application_number, a.status, 
           u.id as user_id, u.first_name, u.last_name, u.contact_number, u.sms_notifications,
           dt.type_name, dt.processing_days
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN document_types dt ON a.document_type_id = dt.id
    WHERE u.sms_notifications = 1 
    AND u.contact_number IS NOT NULL
    AND u.contact_number != ''
    LIMIT 1
");
$app = $stmt->fetch();

if (!$app) {
    echo "ERROR: No suitable test application found.\n";
    echo "Please ensure there's at least one application with SMS-enabled user.\n";
    exit;
}

echo "Found Test Application:\n";
echo str_repeat("-", 70) . "\n";
echo "  App ID: {$app['id']}\n";
echo "  App Number: {$app['application_number']}\n";
echo "  Document Type: {$app['type_name']}\n";
echo "  Current Status: {$app['status']}\n";
echo "  User: {$app['first_name']} {$app['last_name']}\n";
echo "  Phone: {$app['contact_number']}\n";
echo "  SMS Enabled: " . ($app['sms_notifications'] ? 'YES' : 'NO') . "\n";
echo "\n";

// Test 1: Check what message will be generated
echo "TEST 1: Message Generation\n";
echo str_repeat("-", 70) . "\n";
$testMessage = generateApplicationStatusMessage($app, 'processing');
echo "Raw Message: $testMessage\n";
$formattedMessage = formatSMSMessage($testMessage);
echo "Formatted Message: $formattedMessage\n";
echo "Length: " . strlen($formattedMessage) . " characters\n\n";

// Test 2: Check phone number formatting
echo "TEST 2: Phone Number Formatting\n";
echo str_repeat("-", 70) . "\n";
$originalPhone = $app['contact_number'];
$phone = str_replace([' ', '-', '(', ')', '.'], '', $originalPhone);
if (substr($phone, 0, 2) === '09') {
    $phone = '63' . substr($phone, 1);
}
echo "Original: $originalPhone\n";
echo "Formatted: $phone\n";
echo "Valid Format: " . (preg_match('/^63[0-9]{10}$/', $phone) ? 'YES' : 'NO') . "\n\n";

// Test 3: Check SMS config
echo "TEST 3: SMS Configuration\n";
echo str_repeat("-", 70) . "\n";
try {
    $sms_config = getSMSConfig($pdo);
    $api_key = $sms_config['iprog_api_key'] ?? '1ef3b27ea753780a90cbdf07d027fb7b52791004';
    echo "API Key: " . substr($api_key, 0, 20) . "...\n";
    echo "Sender: " . ($sms_config['iprog_sender_name'] ?? 'BM-SCaPIS') . "\n";
} catch (Exception $e) {
    echo "Error getting config: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: ACTUALLY SEND SMS
echo "TEST 4: SENDING ACTUAL SMS\n";
echo str_repeat("=", 70) . "\n";
echo "⚠️  WARNING: This will send a REAL SMS to {$app['contact_number']}\n";
echo str_repeat("=", 70) . "\n\n";

echo "Calling sendApplicationStatusSMS({$app['id']}, 'processing')...\n\n";

try {
    // Actually send the SMS
    $result = sendApplicationStatusSMS($app['id'], 'processing');
    
    echo "RESULT:\n";
    echo str_repeat("-", 70) . "\n";
    echo "Success: " . ($result['success'] ? '✓ YES' : '✗ NO') . "\n";
    echo "Message: " . $result['message'] . "\n";
    
    if (isset($result['reference_id'])) {
        echo "Reference ID: " . $result['reference_id'] . "\n";
    }
    if (isset($result['delivery_status'])) {
        echo "Delivery Status: " . $result['delivery_status'] . "\n";
    }
    
    echo "\n";
    
    // Check database for the SMS record
    echo "Checking sms_notifications table...\n";
    $stmt = $pdo->prepare("
        SELECT * FROM sms_notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$app['user_id']]);
    $smsRecord = $stmt->fetch();
    
    if ($smsRecord) {
        echo "Latest SMS Record:\n";
        echo "  ID: {$smsRecord['id']}\n";
        echo "  Phone: {$smsRecord['phone_number']}\n";
        echo "  Status: {$smsRecord['status']}\n";
        echo "  Created: {$smsRecord['created_at']}\n";
        echo "  Sent At: " . ($smsRecord['sent_at'] ?? 'Not sent yet') . "\n";
        echo "  Message: " . substr($smsRecord['message'], 0, 100) . "...\n";
        
        if ($smsRecord['api_response']) {
            echo "\n  API Response:\n";
            $apiResponse = json_decode($smsRecord['api_response'], true);
            if ($apiResponse) {
                foreach ($apiResponse as $key => $value) {
                    if (!is_array($value)) {
                        echo "    $key: $value\n";
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "TEST COMPLETE\n";
echo str_repeat("=", 70) . "\n";

// Show recent SMS activity
echo "\nRecent SMS Activity (Last 5):\n";
$stmt = $pdo->query("
    SELECT sn.id, sn.phone_number, sn.status, sn.created_at, 
           u.first_name, u.last_name,
           SUBSTRING(sn.message, 1, 60) as message_preview
    FROM sms_notifications sn
    LEFT JOIN users u ON sn.user_id = u.id
    ORDER BY sn.created_at DESC
    LIMIT 5
");

while ($row = $stmt->fetch()) {
    $userName = $row['first_name'] ? "{$row['first_name']} {$row['last_name']}" : "Unknown";
    echo "  [{$row['status']}] {$row['phone_number']} ({$userName}) - {$row['created_at']}\n";
    echo "    Message: {$row['message_preview']}...\n";
}
