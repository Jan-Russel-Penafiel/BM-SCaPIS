<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = "Notifications";
require_once 'header.php';
require_once 'sidebar.php';

// Add custom styles for content layout
echo '
<style>
    .content-wrapper {
        margin-left: 250px;
        padding: 20px;
        transition: margin-left 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .content-wrapper {
            margin-left: 0;
        }
        
        .sidebar.show + .content-wrapper {
            margin-left: 250px;
        }
    }
</style>
';

// Get user's role
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch notifications based on user's role
$sql = "SELECT sn.*, u.first_name, u.last_name, DATE_FORMAT(sn.created_at, '%M %d, %Y %h:%i %p') as formatted_date
        FROM system_notifications sn
        LEFT JOIN users u ON u.id = sn.target_user_id
        WHERE (sn.target_role = ? OR sn.target_role = 'all'";

if ($role == 'resident') {
    $sql .= " OR sn.target_user_id = ?)";
    $params = [$role, $user_id];
} else {
    $sql .= ")";
    $params = [$role];
}

$sql .= " ORDER BY sn.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Play notification sound only if there are unread notifications
// This will play the sound when user visits the notifications page and there are unread notifications
$hasUnread = false;
$unreadCount = 0;
foreach ($notifications as $notification) {
    if (!$notification['is_read']) {
        $hasUnread = true;
        $unreadCount++;
    }
}

// Play sound automatically if there are unread notifications
if ($hasUnread) {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Play notification sound automatically when page loads with unread notifications
            function playNotificationSound() {
                if (typeof window.NotificationSound !== "undefined" && window.NotificationSound.shouldPlayForNewNotifications(' . $unreadCount . ')) {
                    window.NotificationSound.play();
                    window.NotificationSound.markSoundPlayed();
                } else if (typeof window.playNotificationSound !== "undefined") {
                    window.playNotificationSound();
                } else if (typeof window.NotificationFallback !== "undefined" && window.NotificationFallback.play) {
                    window.NotificationFallback.play();
                }
            }
            
            // Play sound immediately when page loads
            playNotificationSound();
        });
    </script>';
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-bell me-2"></i>Notifications
                        </h5>
                    </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">No notifications found</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="timeline-item <?php echo $notification['is_read'] ? 'text-muted' : 'fw-bold'; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo $notification['formatted_date']; ?>
                                            </small>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <button class="btn btn-sm btn-outline-primary mark-read" 
                                                    data-notification-id="<?php echo $notification['id']; ?>">
                                                Mark as read
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark single notification as read
    document.querySelectorAll('.mark-read').forEach(button => {
        button.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            markNotificationRead(notificationId, this);
        });
    });

    function markNotificationRead(notificationId, button) {
        fetch('ajax/mark-notification-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                button.closest('.timeline-item').classList.add('text-muted');
                button.closest('.timeline-item').classList.remove('fw-bold');
                button.remove();
                
                // Update notification count in header
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent) - 1;
                    if (currentCount > 0) {
                        badge.textContent = currentCount;
                    } else {
                        badge.classList.add('d-none');
                        // Reset sound flag when all notifications are read
                        if (typeof window.NotificationSound !== 'undefined') {
                            window.NotificationSound.resetSoundFlag();
                        }
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }
});
</script>

