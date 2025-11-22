<?php
session_start();
require_once 'config.php';

// Simulate being logged in as user ID 1 (assuming this exists)
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Now test the chat endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/muhai_malangit/ajax/chat-check-messages.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['last_check' => 0, 'is_admin' => true]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;
?>