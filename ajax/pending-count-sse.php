<?php
// SSE endpoint that pushes the current pending registrations count to authorized reviewers.
// Long-running script: queries DB periodically and sends events when count changes.
require_once __DIR__ . '/../config.php';

// Ensure session is available to check role
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? null;
$purok_id = $_SESSION['purok_id'] ?? null;

// Only allow admin and purok_leader
if (!in_array($role, ['admin', 'purok_leader'])) {
    // Don't return HTTP 403 (which surfaces in browser network logs for EventSource).
    // Instead, return a short SSE stream with a zero count and close. This prevents
    // noisy 403 errors in client consoles while keeping pending counts private.
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: close');
    echo "data: 0\n\n";
    @ob_flush(); @flush();
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 0);
set_time_limit(0);
ignore_user_abort(true);

// Close session lock to avoid blocking other requests
session_write_close();

$lastCount = null;
$id = 0;

// Helper to send SSE event
function sse_send($id, $data) {
    echo "id: {$id}\n";
    // send as simple data payload (number)
    echo "data: {$data}\n\n";
    @ob_flush(); @flush();
}

// Send a retry suggestion (ms)
echo "retry: 5000\n\n";
@ob_flush(); @flush();

while (!connection_aborted()) {
    try {
        if ($role === 'admin') {
            $sql = "SELECT COUNT(*) FROM users u WHERE u.role = 'resident' AND (u.admin_approval = 'pending' OR u.purok_leader_approval = 'pending') AND (u.status != 'approved' OR (u.purok_leader_approval = 'pending' OR u.admin_approval = 'pending'))";
            $stmt = $pdo->query($sql);
            $count = (int) $stmt->fetchColumn();
        } else {
            $sql = "SELECT COUNT(*) FROM users u WHERE u.role = 'resident' AND u.purok_id = ? AND (u.purok_leader_approval = 'pending' OR u.admin_approval = 'pending') AND (u.status != 'approved' OR (u.purok_leader_approval = 'pending' OR u.admin_approval = 'pending'))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$purok_id]);
            $count = (int) $stmt->fetchColumn();
        }

        if ($lastCount === null) {
            $id++;
            sse_send($id, $count);
            $lastCount = $count;
        } elseif ($count !== $lastCount) {
            $id++;
            sse_send($id, $count);
            $lastCount = $count;
        } else {
            // keep-alive comment to prevent proxies from closing
            echo ": keep-alive\n\n";
            @ob_flush(); @flush();
        }
    } catch (Exception $e) {
        // If DB error, send a comment and continue
        echo ": db-error\n\n";
        @ob_flush(); @flush();
    }

    // Wait a few seconds before checking again
    sleep(3);
}

// Connection closed
exit;
