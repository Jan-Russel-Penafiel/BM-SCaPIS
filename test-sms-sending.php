<?php
// Test SMS sending for muhai_malangit project
require_once 'config.php';

echo "=== MUHAI_MALANGIT SMS SENDING TEST ===\n\n";

// Test 1: Check if functions are loaded
echo "1. Checking if SMS functions are loaded:\n";
echo "   " . str_repeat("-", 60) . "\n";
$functions = ['formatSMSMessage', 'convertToPlainMessage', 'sendSMSUsingIPROG', 'sendApplicationStatusSMS'];
foreach ($functions as $func) {
    $exists = function_exists($func);
    echo "   $func: " . ($exists ? "✓ FOUND" : "✗ NOT FOUND") . "\n";
}
echo "\n";

// Test 2: Check database connection
echo "2. Checking database connection:\n";
echo "   " . str_repeat("-", 60) . "\n";
try {
    $stmt = $pdo->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    echo "   Connected to database: $dbName ✓\n";
} catch (Exception $e) {
    echo "   Database error: " . $e->getMessage() . " ✗\n";
}
echo "\n";

// Test 3: Check if there are applications
echo "3. Checking applications table:\n";
echo "   " . str_repeat("-", 60) . "\n";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
               SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready
        FROM applications
    ");
    $stats = $stmt->fetch();
    echo "   Total applications: {$stats['total']}\n";
    echo "   Pending: {$stats['pending']}\n";
    echo "   Processing: {$stats['processing']}\n";
    echo "   Ready for pickup: {$stats['ready']}\n";
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Check SMS configuration
echo "4. Checking SMS configuration:\n";
echo "   " . str_repeat("-", 60) . "\n";
try {
    $sms_config = getSMSConfig($pdo);
    echo "   API Key: " . (isset($sms_config['iprog_api_key']) ? substr($sms_config['iprog_api_key'], 0, 20) . "..." : "NOT SET") . "\n";
    echo "   Sender Name: " . ($sms_config['iprog_sender_name'] ?? 'BM-SCaPIS') . "\n";
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Using default API key\n";
}
echo "\n";

// Test 5: Check SMS notifications table
echo "5. Checking sms_notifications table:\n";
echo "   " . str_repeat("-", 60) . "\n";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
               SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM sms_notifications
    ");
    $stats = $stmt->fetch();
    echo "   Total SMS: {$stats['total']}\n";
    echo "   Sent: {$stats['sent']}\n";
    echo "   Failed: {$stats['failed']}\n";
    echo "   Pending: {$stats['pending']}\n";
    
    // Get recent SMS logs
    $stmt = $pdo->query("
        SELECT sn.*, u.first_name, u.last_name
        FROM sms_notifications sn
        LEFT JOIN users u ON sn.user_id = u.id
        ORDER BY sn.created_at DESC
        LIMIT 5
    ");
    $recent = $stmt->fetchAll();
    
    if (!empty($recent)) {
        echo "\n   Recent SMS notifications:\n";
        foreach ($recent as $sms) {
            $userName = $sms['first_name'] ? "{$sms['first_name']} {$sms['last_name']}" : "Unknown";
            $messagePreview = substr($sms['message'], 0, 50) . "...";
            echo "   - To: {$sms['phone_number']} ({$userName})\n";
            echo "     Status: {$sms['status']} | Created: {$sms['created_at']}\n";
            echo "     Message: $messagePreview\n";
            if ($sms['status'] == 'failed' && $sms['api_response']) {
                $response = json_decode($sms['api_response'], true);
                echo "     Error: " . ($response['message'] ?? 'Unknown error') . "\n";
            }
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Check users with SMS enabled
echo "6. Checking users with SMS notifications enabled:\n";
echo "   " . str_repeat("-", 60) . "\n";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN sms_notifications = 1 THEN 1 ELSE 0 END) as enabled,
               SUM(CASE WHEN contact_number IS NOT NULL AND contact_number != '' THEN 1 ELSE 0 END) as has_phone
        FROM users
    ");
    $stats = $stmt->fetch();
    echo "   Total users: {$stats['total']}\n";
    echo "   SMS enabled: {$stats['enabled']}\n";
    echo "   Has phone number: {$stats['has_phone']}\n";
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Test message formatting
echo "7. Testing message formatting:\n";
echo "   " . str_repeat("-", 60) . "\n";
$testMessage = "Your Barangay Clearance APP-001 is ready for pickup.";
$formatted = formatSMSMessage($testMessage);
echo "   Original: $testMessage\n";
echo "   Formatted: $formatted\n";
echo "   Length: " . strlen($formatted) . " characters\n";
echo "   Has prefix: " . (strpos($formatted, 'This is an important message from the Organization.') === 0 ? "✓ YES" : "✗ NO") . "\n";
echo "\n";

echo str_repeat("=", 70) . "\n";
echo "DIAGNOSTIC COMPLETE\n";
echo str_repeat("=", 70) . "\n";
echo "\nTo test actual SMS sending, make sure:\n";
echo "1. SMS functions are loaded ✓\n";
echo "2. Database connection works ✓\n";
echo "3. Applications exist in database\n";
echo "4. Users have sms_notifications=1 and valid contact_number\n";
echo "5. When you call sendApplicationStatusSMS(), check sms_notifications table\n";
echo "\nCheck PHP error log for any runtime errors during SMS sending.\n";
