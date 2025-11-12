<?php
/**
 * Test IPROG SMS Integration for Muhai Malangit Project
 */

require_once 'config.php';
require_once 'sms_functions.php';

// Test phone number - replace with a real number for testing
$test_phone = '09123456789';
$test_message = 'Test SMS from BM-SCaPIS using IPROG SMS API';

echo "<h2>Testing IPROG SMS Integration - Muhai Malangit Project</h2>";

// Test sendSMSNotification function
echo "<h3>Testing sendSMSNotification() function:</h3>";
$result = sendSMSNotification($test_phone, $test_message, null, 'test');

echo "<pre>";
print_r($result);
echo "</pre>";

// Test sendSMSUsingIPROG function directly
echo "<h3>Testing sendSMSUsingIPROG() function directly:</h3>";
$api_key = '1ef3b27ea753780a90cbdf07d027fb7b52791004'; // Your IPROG API key
$direct_result = sendSMSUsingIPROG($test_phone, $test_message, $api_key);

echo "<pre>";
print_r($direct_result);
echo "</pre>";

// Test legacy sendSMSUsingPhilSMS function (should redirect to IPROG)
echo "<h3>Testing sendSMSUsingPhilSMS() function (legacy compatibility):</h3>";
$legacy_result = sendSMSUsingPhilSMS($test_phone, $test_message, $api_key);

echo "<pre>";
print_r($legacy_result);
echo "</pre>";

// Test SMS config
echo "<h3>Current SMS Configuration:</h3>";
try {
    $sms_config = getSMSConfig($pdo);
    echo "<pre>";
    print_r($sms_config);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error getting SMS config: " . $e->getMessage();
}

// Check system config in database
echo "<h3>Current System Config in Database:</h3>";
try {
    $query = "SELECT config_key, config_value FROM system_config WHERE config_key LIKE '%api_key%' OR config_key LIKE '%sender%'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Config Key</th><th>Config Value</th></tr>";
    foreach ($settings as $setting) {
        // Mask API key for security
        $value = $setting['config_value'];
        if (strpos($setting['config_key'], 'api_key') !== false) {
            $value = substr($value, 0, 8) . '...';
        }
        echo "<tr><td>{$setting['config_key']}</td><td>{$value}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error fetching settings: " . $e->getMessage();
}

echo "<p><strong>Note:</strong> Replace the test phone number with a real number to test actual SMS sending.</p>";
?>