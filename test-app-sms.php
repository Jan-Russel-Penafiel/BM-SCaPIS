<?php
/**
 * Test Application SMS Flow
 */
require_once 'config.php';
require_once 'sms_functions.php';

echo "<h1>Application SMS Flow Test</h1>";
echo "<pre>";

// Get latest application
$stmt = $pdo->query("
    SELECT a.id, a.application_number, a.status, 
           u.first_name, u.last_name, u.contact_number, u.sms_notifications,
           dt.type_name, dt.processing_days
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN document_types dt ON a.document_type_id = dt.id
    WHERE u.sms_notifications = 1 AND u.contact_number IS NOT NULL
    ORDER BY a.created_at DESC
    LIMIT 1
");
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if ($app) {
    echo "=== Test Application ===\n";
    echo "ID: {$app['id']}\n";
    echo "Number: {$app['application_number']}\n";
    echo "Type: {$app['type_name']}\n";
    echo "Applicant: {$app['first_name']} {$app['last_name']}\n";
    echo "Phone: {$app['contact_number']}\n";
    echo "SMS Enabled: " . ($app['sms_notifications'] ? 'Yes' : 'No') . "\n\n";

    // Test sending application status SMS
    echo "=== Testing sendApplicationStatusSMS() ===\n";
    
    // Only send if ?test=true is passed
    if (isset($_GET['test']) && $_GET['test'] === 'true') {
        echo "Sending SMS for 'processing' status...\n";
        $result = sendApplicationStatusSMS($app['id'], 'processing');
        print_r($result);
    } else {
        echo "Add ?test=true to URL to send actual SMS\n\n";
        
        // Show what message would be sent
        echo "=== Message Previews ===\n";
        $statuses = ['processing', 'ready_for_pickup', 'completed', 'payment_received'];
        
        foreach ($statuses as $status) {
            $message = generateApplicationStatusMessage($app, $status);
            $formatted = formatSMSTemplate($message);
            echo "\nStatus: {$status}\n";
            echo "Message: {$formatted}\n";
            echo str_repeat('-', 60) . "\n";
        }
    }
} else {
    echo "No applications found with SMS enabled.\n";
}

// Show recent SMS log
echo "\n=== Recent SMS Log ===\n";
$stmt = $pdo->query("SELECT id, phone_number, status, LEFT(message, 80) as msg, created_at FROM sms_notifications ORDER BY id DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "[{$row['id']}] {$row['status']} - {$row['phone_number']} - {$row['created_at']}\n";
    echo "    {$row['msg']}...\n\n";
}

echo "</pre>";
echo "<p><a href='?test=true'>Click to send actual test SMS</a></p>";
?>
