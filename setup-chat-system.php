<?php
require_once 'config.php';

echo "<h1>Chat System Setup</h1>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/migrations/create_support_chat_system.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    if (!$sql) {
        throw new Exception("Could not read migration file");
    }
    
    echo "<p>Setting up chat system database tables...</p>";
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "<p>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
        } catch (PDOException $e) {
            $errorCount++;
            echo "<p>❌ Error: " . $e->getMessage() . "</p>";
            echo "<p>Statement: " . substr($statement, 0, 100) . "...</p>";
        }
    }
    
    echo "<h2>Setup Results</h2>";
    echo "<p>✅ Successful operations: $successCount</p>";
    echo "<p>❌ Failed operations: $errorCount</p>";
    
    if ($errorCount === 0) {
        echo "<h3>🎉 Chat System Successfully Set Up!</h3>";
        echo "<p>All database tables have been created. The chat system should now work properly.</p>";
        echo "<a href='applications.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Chat System</a>";
    } else {
        echo "<h3>⚠️ Setup Completed with Errors</h3>";
        echo "<p>Some operations failed. Please check the errors above and try again.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Setup Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<br><br><a href='check-chat-tables.php'>Check Table Status</a>";
?>