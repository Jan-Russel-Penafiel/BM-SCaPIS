<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Barangay Malangit Smart Clearance and Permit Issuance System">
    <meta name="author" content="BM-SCaPIS Development Team">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SYSTEM_NAME : SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="assets/css/sidebar.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5aa0;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            padding-top: 56px; /* Account for fixed navbar */
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #3d6db0);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #3d6db0);
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(44, 90, 160, 0.3);
        }
        
        .btn-success {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-danger {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
        }
        
        .status-pending {
            background-color: var(--warning-color);
            color: #000;
        }
        
        .status-approved {
            background-color: var(--success-color);
        }
        
        .status-processing {
            background-color: var(--info-color);
        }
        
        .status-completed {
            background-color: var(--success-color);
        }
        
        .status-rejected {
            background-color: var(--danger-color);
        }
        
        .status-disapproved {
            background-color: var(--danger-color);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
            background-color: rgba(44, 90, 160, 0.05);
        }
        
        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background-color: rgba(44, 90, 160, 0.1);
        }
        
        .timeline {
            position: relative;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20px;
            height: 100%;
            width: 2px;
            background-color: #ddd;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 14px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 56px;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .sidebar {
                position: fixed;
                top: 56px;
                left: -100%;
                width: 280px;
                height: calc(100vh - 56px);
                transition: left 0.3s ease;
                z-index: 1000;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .sidebar-backdrop {
                position: fixed;
                top: 56px;
                left: 0;
                width: 100%;
                height: calc(100vh - 56px);
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }
            
            .sidebar-backdrop.show {
                display: block;
            }
        }
        
        /* Loading spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                padding-top: 0;
            }
            
            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* PWA specific styles */
        .pwa-install-banner {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--primary-color), #3d6db0);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(44, 90, 160, 0.3);
            z-index: 1000;
            transform: translateY(100px);
            transition: transform 0.3s ease;
        }
        
        .pwa-install-banner.show {
            transform: translateY(0);
        }
    </style>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2c5aa0">
    
    <!-- PWA Icons -->
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/icons/icon-512x512.png">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, var(--primary-color), #3d6db0);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-building"></i>
                <?php echo SYSTEM_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="bi bi-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">
                            <i class="bi bi-list-ul"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="bi bi-telephone"></i> Contact
                        </a>
                    </li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light ms-2" href="register.php">
                                <i class="bi bi-person-plus"></i> Register
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Notifications Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell"></i>
                                <span class="notification-badge d-none" id="notificationBadge">0</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm" 
                                 aria-labelledby="notificationsDropdown" 
                                 style="width: 300px; max-height: 400px; overflow-y: auto;">
                                <div class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-bell me-2"></i>Notifications</span>
                                    <a href="#" class="text-primary text-decoration-none" id="markAllRead">
                                        <small>Mark all as read</small>
                                    </a>
                                </div>
                                <div class="dropdown-divider"></div>
                                <div id="notificationsList">
                                    <!-- Notifications will be loaded here -->
                                    <div class="text-center p-3">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center" href="notifications.php">
                                    <small>View All Notifications</small>
                                </a>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Notification Sound -->
    <audio id="notificationSound" preload="auto">
        <source src="assets/sounds/notification.mp3" type="audio/mpeg">
    </audio>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('notificationsDropdown')) {
            const notificationSound = document.getElementById('notificationSound');
            const notificationBadge = document.getElementById('notificationBadge');
            const notificationsList = document.getElementById('notificationsList');
            let lastNotificationCount = 0;

            // Function to format time ago
            function timeAgo(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const seconds = Math.floor((now - date) / 1000);
                
                let interval = Math.floor(seconds / 31536000);
                if (interval >= 1) return interval + ' year' + (interval === 1 ? '' : 's') + ' ago';
                
                interval = Math.floor(seconds / 2592000);
                if (interval >= 1) return interval + ' month' + (interval === 1 ? '' : 's') + ' ago';
                
                interval = Math.floor(seconds / 86400);
                if (interval >= 1) return interval + ' day' + (interval === 1 ? '' : 's') + ' ago';
                
                interval = Math.floor(seconds / 3600);
                if (interval >= 1) return interval + ' hour' + (interval === 1 ? '' : 's') + ' ago';
                
                interval = Math.floor(seconds / 60);
                if (interval >= 1) return interval + ' minute' + (interval === 1 ? '' : 's') + ' ago';
                
                return 'just now';
            }

            // Function to update notifications
            function updateNotifications() {
                fetch('ajax/get-notification-count.php')
                    .then(response => response.json())
                    .then(data => {
                        // Update badge
                        if (data.count > 0) {
                            notificationBadge.textContent = data.count;
                            notificationBadge.classList.remove('d-none');
                            
                            // Play sound if count increased
                            if (data.count > lastNotificationCount) {
                                notificationSound.play().catch(e => console.log('Error playing sound:', e));
                            }
                        } else {
                            notificationBadge.classList.add('d-none');
                        }
                        lastNotificationCount = data.count;

                        // Update notifications list
                        if (data.notifications) {
                            notificationsList.innerHTML = data.notifications.length > 0 ? 
                                data.notifications.map(notification => `
                                    <a class="dropdown-item py-2 ${!notification.is_read ? 'bg-light' : ''}" 
                                       href="#" 
                                       onclick="markNotificationRead(${notification.id}, '${notification.link || '#'}'); return false;">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 ${!notification.is_read ? 'fw-bold' : ''}">${notification.title}</h6>
                                            <small class="text-muted">${timeAgo(notification.created_at)}</small>
                                        </div>
                                        <p class="mb-1 small text-muted">${notification.message}</p>
                                    </a>
                                `).join('') :
                                '<div class="text-center p-3 text-muted">No new notifications</div>';
                        }
                    })
                    .catch(error => console.error('Error fetching notifications:', error));
            }

            // Mark single notification as read
            window.markNotificationRead = function(id, link) {
                fetch('ajax/mark-notification-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notification_id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotifications();
                        if (link && link !== '#') {
                            window.location.href = link;
                        }
                    }
                })
                .catch(error => console.error('Error marking notification as read:', error));
            };

            // Mark all notifications as read
            document.getElementById('markAllRead').addEventListener('click', function(e) {
                e.preventDefault();
                fetch('ajax/mark-all-notifications-read.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotifications();
                    }
                })
                .catch(error => console.error('Error marking all notifications as read:', error));
            });

            // Initial update and set interval
            updateNotifications();
            setInterval(updateNotifications, 30000); // Check every 30 seconds
        }
    });
    </script>
    <!-- Loading Spinner -->
    <div class="spinner-overlay d-none" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</body>
