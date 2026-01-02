<?php
// Simulate what happens when you click "Process Application" or "Complete Application"
require_once 'config.php';

echo "=== SIMULATING APPLICATION ACTIONS ===\n\n";

// Find a ready_for_pickup application to complete
$stmt = $pdo->query("
    SELECT a.id, a.application_number, a.status,
           u.first_name, u.last_name, u.contact_number, u.sms_notifications
    FROM applications a
    JOIN users u ON a.user_id = u.id
    WHERE a.status = 'ready_for_pickup'
    AND u.sms_notifications = 1
    LIMIT 1
");
$app = $stmt->fetch();

if ($app) {
    echo "Found Application to Test:\n";
    echo "  ID: {$app['id']}\n";
    echo "  Number: {$app['application_number']}\n";
    echo "  Status: {$app['status']}\n";
    echo "  User: {$app['first_name']} {$app['last_name']}\n";
    echo "  Phone: {$app['contact_number']}\n\n";
    
    echo "ACTION: Simulating 'Complete Application' click...\n";
    echo str_repeat("-", 70) . "\n";
    
    // Get SMS count before
    $smsBefore = $pdo->query("SELECT COUNT(*) FROM sms_notifications")->fetchColumn();
    
    // Send SMS (this is what complete-application.php does)
    echo "Calling sendApplicationStatusSMS({$app['id']}, 'completed')...\n";
    $result = sendApplicationStatusSMS($app['id'], 'completed');
    
    echo "\nResult:\n";
    echo "  Success: " . ($result['success'] ? '✓ YES' : '✗ NO') . "\n";
    echo "  Message: {$result['message']}\n";
    if (isset($result['reference_id'])) {
        echo "  Reference ID: {$result['reference_id']}\n";
    }
    
    // Get SMS count after
    $smsAfter = $pdo->query("SELECT COUNT(*) FROM sms_notifications")->fetchColumn();
    $newSMS = $smsAfter - $smsBefore;
    
    echo "\nDatabase Changes:\n";
    echo "  SMS before: $smsBefore\n";
    echo "  SMS after: $smsAfter\n";
    echo "  New SMS records: $newSMS\n";
    
    if ($newSMS > 0) {
        // Show the new SMS record
        $stmt = $pdo->prepare("
            SELECT * FROM sms_notifications 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $sms = $stmt->fetch();
        
        echo "\nLatest SMS Record:\n";
        echo "  ID: {$sms['id']}\n";
        echo "  Phone: {$sms['phone_number']}\n";
        echo "  Status: {$sms['status']}\n";
        echo "  Created: {$sms['created_at']}\n";
        echo "  Message: " . substr($sms['message'], 0, 80) . "...\n";
    }
    
} else {
    echo "No ready_for_pickup application found\n";
    echo "Looking for processing applications instead...\n\n";
    
    $stmt = $pdo->query("
        SELECT a.id, a.application_number, a.status,
               u.first_name, u.last_name, u.contact_number, u.sms_notifications
        FROM applications a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'processing'
        AND u.sms_notifications = 1
        LIMIT 1
    ");
    $app = $stmt->fetch();
    
    if ($app) {
        echo "Found Processing Application:\n";
        echo "  ID: {$app['id']}\n";
        echo "  Number: {$app['application_number']}\n\n";
        
        echo "Simulating update to ready_for_pickup...\n";
        $result = sendApplicationStatusSMS($app['id'], 'ready_for_pickup');
        
        echo "Result: " . ($result['success'] ? '✓ SUCCESS' : '✗ FAILED') . "\n";
        echo "Message: {$result['message']}\n";
    } else {
        echo "No processing applications found\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "CONCLUSION:\n";
echo "If you see '✓ SUCCESS' above, SMS notifications ARE working!\n";
echo "When you use the actual application interface, the same thing happens.\n";
echo "\nIf SMS are not arriving on your phone:\n";
echo "1. Check IPROG dashboard for delivery status\n";
echo "2. Verify phone number is correct\n";
echo "3. Check if number is registered with mobile network\n";
echo "4. SMS may take 1-5 minutes to deliver\n";
