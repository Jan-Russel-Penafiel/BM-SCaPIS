<?php
require_once 'config.php';

echo "<h2>CSRF Token Test</h2>";

// Generate a new token
if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
    (time() - $_SESSION['csrf_token_time']) > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

echo "<p><strong>Session Token:</strong> " . $_SESSION['csrf_token'] . "</p>";
echo "<p><strong>Token Time:</strong> " . date('Y-m-d H:i:s', $_SESSION['csrf_token_time']) . "</p>";

// Test validation
$testToken = $_SESSION['csrf_token'];
$isValid = validateCSRFToken($testToken);
echo "<p><strong>Token Validation Test:</strong> " . ($isValid ? 'PASS' : 'FAIL') . "</p>";

// Test with wrong token
$wrongToken = bin2hex(random_bytes(32));
$isValidWrong = validateCSRFToken($wrongToken);
echo "<p><strong>Wrong Token Test:</strong> " . ($isValidWrong ? 'FAIL' : 'PASS') . "</p>";

echo "<hr>";
echo "<h3>Form Test</h3>";
echo "<form method='POST'>";
echo "<input type='hidden' name='csrf_token' value='" . $_SESSION['csrf_token'] . "'>";
echo "<input type='submit' value='Test Form Submission'>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data:</h3>";
    echo "<p><strong>Posted Token:</strong> " . ($_POST['csrf_token'] ?? 'NOT SET') . "</p>";
    echo "<p><strong>Session Token:</strong> " . ($_SESSION['csrf_token'] ?? 'NOT SET') . "</p>";
    echo "<p><strong>Validation Result:</strong> " . (validateCSRFToken($_POST['csrf_token'] ?? '') ? 'VALID' : 'INVALID') . "</p>";
}
?> 