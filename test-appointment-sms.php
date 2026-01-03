<?php
/**
 * Test SMS Notification for Appointment Scheduling
 * This script tests the SMS notification format used when scheduling appointments
 */
require_once 'config.php';
require_once 'sms_functions.php';

echo "<h1>Test Appointment SMS Notification</h1>";
echo "<pre>";

// Get a test user with SMS notifications enabled
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.contact_number, u.sms_notifications
    FROM users u 
    WHERE u.contact_number IS NOT NULL 
    AND u.contact_number != ''
    AND u.sms_notifications = 1
    LIMIT 1
");
$stmt->execute();
$testUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$testUser) {
    echo "No user with SMS notifications enabled found. Using test number.\n";
    $testPhone = '09677726912'; // Default test number
    $testUserId = null;
} else {
    echo "Testing with user: {$testUser['first_name']} {$testUser['last_name']}\n";
    echo "Phone: {$testUser['contact_number']}\n";
    $testPhone = $testUser['contact_number'];
    $testUserId = $testUser['id'];
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "TESTING APPOINTMENT SMS FORMAT\n";
echo str_repeat("=", 50) . "\n\n";

// Simulate appointment data
$testAppNumber = 'APP-20260103-TEST';
$appointmentDate = date('M j, Y g:i A', strtotime('+2 days 10:00'));

// This is the exact message format used in schedule-appointment.php
$message = "Your application {$testAppNumber} appointment is now scheduled on {$appointmentDate}.";

echo "Original Message:\n";
echo "\"" . $message . "\"\n\n";

// Show how it will be formatted
$formattedMessage = formatSMSTemplate($message);
echo "Formatted Message (with IPROG template):\n";
echo "\"" . $formattedMessage . "\"\n\n";

echo str_repeat("=", 50) . "\n";
echo "SENDING TEST SMS...\n";
echo str_repeat("=", 50) . "\n\n";

// Send the test SMS
$result = sendSMSNotification(
    $testPhone,
    $message,
    $testUserId,
    'appointment_scheduled'
);

echo "SMS Result:\n";
print_r($result);

echo "\n" . str_repeat("=", 50) . "\n";
echo "CHECKING DATABASE RECORD\n";
echo str_repeat("=", 50) . "\n\n";

// Check the latest SMS in the database
$stmt = $pdo->query("
    SELECT id, phone_number, status, message, api_response, created_at 
    FROM sms_notifications 
    ORDER BY id DESC 
    LIMIT 1
");
$latestSms = $stmt->fetch(PDO::FETCH_ASSOC);

if ($latestSms) {
    echo "Latest SMS Record:\n";
    echo "ID: {$latestSms['id']}\n";
    echo "Phone: {$latestSms['phone_number']}\n";
    echo "Status: {$latestSms['status']}\n";
    echo "Created: {$latestSms['created_at']}\n";
    echo "Message: {$latestSms['message']}\n";
    
    if ($latestSms['api_response']) {
        echo "\nAPI Response:\n";
        $apiResponse = json_decode($latestSms['api_response'], true);
        print_r($apiResponse);
    }
} else {
    echo "No SMS records found in database.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST COMPLETE\n";
echo str_repeat("=", 50) . "\n";

if ($result['success']) {
    echo "\n✅ SMS notification sent successfully!\n";
} else {
    echo "\n❌ SMS notification failed: " . $result['message'] . "\n";
}

echo "</pre>";
?>
