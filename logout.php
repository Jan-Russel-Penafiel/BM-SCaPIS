<?php
require_once 'config.php';

// Log activity before destroying session
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'User logged out', 'users', $_SESSION['user_id']);
}

// Destroy session
session_destroy();

// Clear any remember me cookies
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page with success message
header('Location: login.php?logged_out=1');
exit();
?>
