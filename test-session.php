<?php
require_once 'config.php';

echo "<h2>Session Test</h2>";

echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";

// Test session data
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;

echo "<p><strong>Test Counter:</strong> " . $_SESSION['test_counter'] . "</p>";

// Test CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    echo "<p><strong>CSRF Token Generated:</strong> " . $_SESSION['csrf_token'] . "</p>";
} else {
    echo "<p><strong>CSRF Token Exists:</strong> " . $_SESSION['csrf_token'] . "</p>";
}

echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<p><a href='test-session.php'>Refresh to test session persistence</a></p>";
?> 