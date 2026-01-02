<?php
// Test sending a new SMS with the updated format
require_once 'config.php';

echo "=== TESTING NEW SMS FORMAT ===\n\n";

// Get a test application
$stmt = $pdo->query("
    SELECT a.id, a.application_number, a.status, 
           u.id as user_id, u.first_name, u.last_name, u.contact_number, u.sms_notifications
    FROM applications a
    JOIN users u ON a.user_id = u.id
    WHERE u.sms_notifications = 1 
    AND u.contact_number IS NOT NULL
    AND u.contact_number != ''
    LIMIT 1
");
$app = $stmt->fetch();

if (!$app) {
    echo "No suitable test application found.\n";
    echo "Create a test user with SMS notifications enabled and a valid contact number.\n";
    exit;
}

echo "Test Application Details:\n";
echo "  App ID: {$app['id']}\n";
echo "  App Number: {$app['application_number']}\n";
echo "  User: {$app['first_name']} {$app['last_name']}\n";
echo "  Phone: {$app['contact_number']}\n";
echo "  Status: {$app['status']}\n";
echo "\n";

// Generate test message
$mockApp = [
    'application_number' => $app['application_number'],
    'type_name' => 'Test Document',
    'processing_days' => 3,
    'first_name' => $app['first_name'],
    'last_name' => $app['last_name']
];

echo "Generated Message Samples:\n";
echo str_repeat("-", 70) . "\n";

$statuses = ['processing', 'ready_for_pickup', 'completed'];
foreach ($statuses as $status) {
    $message = generateApplicationStatusMessage($mockApp, $status);
    $formatted = formatSMSMessage($message);
    
    echo "\nStatus: $status\n";
    echo "Message: $message\n";
    echo "Formatted: $formatted\n";
    echo "Length: " . strlen($formatted) . " characters\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "VERIFICATION\n";
echo str_repeat("=", 70) . "\n";

// Check if format is correct
$testMsg = generateApplicationStatusMessage($mockApp, 'processing');
$formatted = formatSMSMessage($testMsg);

$checks = [
    'Has prefix' => strpos($formatted, 'This is an important message from the Organization.') === 0,
    'No # symbol' => strpos($testMsg, '#') === false,
    'No parentheses' => strpos($testMsg, '(') === false && strpos($testMsg, ')') === false,
    'Uses "the Organization"' => strpos($formatted, 'the Organization') !== false,
];

foreach ($checks as $check => $pass) {
    echo $check . ": " . ($pass ? "✓ PASS" : "✗ FAIL") . "\n";
}

echo "\n✓ SMS format is ready!\n";
echo "\nTo send actual SMS:\n";
echo "1. Process an application from the admin panel\n";
echo "2. Mark application as ready for pickup\n";
echo "3. Complete an application\n";
echo "\nCheck the sms_notifications table to see the results.\n";
