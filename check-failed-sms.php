<?php
// Check recent failed SMS
require_once 'config.php';

echo "=== CHECKING FAILED SMS NOTIFICATIONS ===\n\n";

$stmt = $pdo->query("
    SELECT sn.*, u.first_name, u.last_name
    FROM sms_notifications sn
    LEFT JOIN users u ON sn.user_id = u.id
    WHERE sn.status = 'failed'
    ORDER BY sn.created_at DESC
    LIMIT 10
");
$failed = $stmt->fetchAll();

if (empty($failed)) {
    echo "No failed SMS found!\n";
} else {
    echo "Recent failed SMS notifications:\n";
    echo str_repeat("=", 70) . "\n\n";
    
    foreach ($failed as $sms) {
        $userName = $sms['first_name'] ? "{$sms['first_name']} {$sms['last_name']}" : "Unknown";
        
        echo "SMS ID: {$sms['id']}\n";
        echo "To: {$sms['phone_number']} ({$userName})\n";
        echo "Created: {$sms['created_at']}\n";
        echo "Message: {$sms['message']}\n";
        
        if ($sms['api_response']) {
            $response = json_decode($sms['api_response'], true);
            if ($response) {
                echo "API Response:\n";
                echo "  Success: " . ($response['success'] ? 'Yes' : 'No') . "\n";
                echo "  Message: " . ($response['message'] ?? 'N/A') . "\n";
                if (isset($response['errors'])) {
                    echo "  Errors: " . print_r($response['errors'], true) . "\n";
                }
            } else {
                echo "API Response: " . $sms['api_response'] . "\n";
            }
        }
        
        echo str_repeat("-", 70) . "\n\n";
    }
}

// Summary
echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 70) . "\n";

$stmt = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count,
        DATE(created_at) as date
    FROM sms_notifications
    GROUP BY status, DATE(created_at)
    ORDER BY date DESC, status
    LIMIT 10
");
$summary = $stmt->fetchAll();

foreach ($summary as $row) {
    echo "{$row['date']} - {$row['status']}: {$row['count']} SMS\n";
}
