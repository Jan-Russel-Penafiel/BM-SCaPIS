<?php
/**
 * Test all application statuses SMS sending
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'sms_functions.php';

echo "<h1>Test All Application Status SMS</h1>";
echo "<pre>";

// Get latest application with SMS enabled
$stmt = $pdo->query("
    SELECT a.id, a.application_number, a.status, 
           u.first_name, u.last_name, u.contact_number, u.sms_notifications, u.id as user_id,
           dt.type_name, dt.processing_days
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN document_types dt ON a.document_type_id = dt.id
    WHERE u.sms_notifications = 1 AND u.contact_number IS NOT NULL AND u.contact_number != ''
    ORDER BY a.created_at DESC
    LIMIT 1
");
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    echo "No application found with SMS enabled!\n";
    exit;
}

echo "=== Application Details ===\n";
echo "ID: {$app['id']}\n";
echo "Number: {$app['application_number']}\n";
echo "Type: {$app['type_name']}\n";
echo "Phone: {$app['contact_number']}\n";
echo "SMS Enabled: " . ($app['sms_notifications'] ? 'Yes' : 'No') . "\n";
echo "Processing Days: {$app['processing_days']}\n\n";

$statuses = ['processing', 'ready_for_pickup', 'completed'];

foreach ($statuses as $status) {
    echo "=== Testing Status: {$status} ===\n";
    
    // Generate the message first
    $message = generateApplicationStatusMessage($app, $status, date('Y-m-d H:i:s', strtotime('+3 days')));
    echo "Generated Message: {$message}\n";
    
    // Format it
    $formatted = formatSMSTemplate($message);
    echo "Formatted Message: {$formatted}\n";
    
    if (isset($_GET['send']) && ($_GET['send'] === 'all' || $_GET['send'] === $status)) {
        echo "\nSending SMS...\n";
        $result = sendApplicationStatusSMS($app['id'], $status, null, date('Y-m-d H:i:s', strtotime('+3 days')));
        echo "Result: ";
        print_r($result);
        if (isset($result['duplicate']) && $result['duplicate']) {
            echo "\n*** DUPLICATE BLOCKED - No SMS credit used! ***\n";
        }
    }
    
    echo "\n" . str_repeat('-', 70) . "\n\n";
}

// Check database for latest SMS
echo "=== Latest SMS in Database ===\n";
$stmt = $pdo->query("SELECT id, phone_number, status, LEFT(message, 100) as msg, api_response FROM sms_notifications ORDER BY id DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "[{$row['id']}] {$row['status']} - {$row['phone_number']}\n";
    echo "Message: {$row['msg']}...\n";
    if ($row['api_response']) {
        $resp = json_decode($row['api_response'], true);
        echo "API Success: " . ($resp['success'] ?? 'N/A') . "\n";
    }
    echo "\n";
}

echo "</pre>";

if (!isset($_GET['send'])) {
    echo "<p><strong><a href='?send=all'>Click here to send SMS for ALL statuses (Processing, Ready, Completed)</a></strong></p>";
}
?>
