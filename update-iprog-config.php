<?php
/**
 * Update SMS Configuration for Muhai Malangit Project
 * This script updates the system configuration to use IPROG SMS API
 */

require_once 'config.php';

echo "<h2>Updating Muhai Malangit Project SMS Configuration to IPROG</h2>";

try {
    // IPROG SMS Configuration
    $iprog_api_key = '1ef3b27ea753780a90cbdf07d027fb7b52791004';
    $iprog_settings = [
        'iprog_api_key' => $iprog_api_key,
        'iprog_sender_name' => 'BM-SCaPIS',
        // Keep old keys for backwards compatibility
        'philsms_api_key' => $iprog_api_key,
        'philsms_sender_name' => 'BM-SCaPIS'
    ];
    
    echo "<h3>Updating system configuration...</h3>";
    
    foreach ($iprog_settings as $config_key => $config_value) {
        // Check if setting exists
        $check_query = "SELECT COUNT(*) FROM system_config WHERE config_key = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$config_key]);
        $exists = $check_stmt->fetchColumn();
        
        if ($exists) {
            // Update existing setting
            $update_query = "UPDATE system_config SET config_value = ? WHERE config_key = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$config_value, $config_key]);
            echo "✓ Updated {$config_key}<br>";
        } else {
            // Insert new setting
            $insert_query = "INSERT INTO system_config (config_key, config_value) VALUES (?, ?)";
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([$config_key, $config_value]);
            echo "✓ Added {$config_key}<br>";
        }
    }
    
    echo "<h3>SMS Configuration Update Complete!</h3>";
    echo "<p>The following settings have been configured:</p>";
    echo "<ul>";
    echo "<li><strong>IPROG API Key:</strong> " . substr($iprog_api_key, 0, 8) . "...</li>";
    echo "<li><strong>Sender Name:</strong> BM-SCaPIS</li>";
    echo "<li><strong>Legacy Compatibility:</strong> Maintained for PhilSMS function calls</li>";
    echo "</ul>";
    
    echo "<h3>Current SMS Configuration:</h3>";
    $sms_config = getSMSConfig($pdo);
    echo "<pre>";
    // Mask API key for display
    foreach ($sms_config as $key => $value) {
        if (strpos($key, 'api_key') !== false) {
            $sms_config[$key] = substr($value, 0, 8) . '...';
        }
    }
    print_r($sms_config);
    echo "</pre>";
    
    echo "<p><a href='test-iprog-sms.php'>Click here to test SMS functionality</a></p>";
    
} catch (Exception $e) {
    echo "Error updating SMS configuration: " . $e->getMessage();
    echo "<br><pre>";
    print_r($e->getTraceAsString());
    echo "</pre>";
}
?>