<?php
// Include configuration and start session
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Barangay Malangit Smart Clearance and Permit Issuance System - Pandag, Maguindanao Del Sur">
    <meta name="author" content="BM-SCaPIS Development Team">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SYSTEM_NAME : SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    

    
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
        
        /* Responsive adjustments */
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
            
            .dropdown-menu {
                min-width: 280px;
                max-height: 300px;
            }
            
            .dropdown-item {
                padding: 0.5rem 0.75rem;
            }
            
            .dropdown-toggle {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
        }
        
        /* Enhanced Dropdown Styles */
        .dropdown-menu {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: hidden;
            min-width: 280px;
            max-width: 350px;
            border: 1px solid #dee2e6;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
            z-index: 1050 !important;
            position: absolute !important;
            background: white;
            border-radius: 0.375rem;
            word-wrap: break-word;
        }
        
        .dropup {
            position: static !important;
        }
        
        .dropup .dropdown-menu {
            bottom: 100%;
            top: auto;
            margin-bottom: 0.125rem;
            position: absolute !important;
            transform: translate3d(0px, 0px, 0px) !important;
        }
        
        .dropdown-menu-end {
            --bs-position: end;
            right: 0;
            left: auto;
        }
        
        .dropdown-menu.show {
            position: absolute !important;
            z-index: 1060 !important;
        }
        
        .dropdown-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.2s ease-in-out;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(2px);
        }
        
        .dropdown-item.active {
            background-color: #e3f2fd;
            color: #1976d2;
            border-left: 3px solid #1976d2;
        }
        
        .dropdown-item .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            white-space: nowrap;
        }
        
        .dropdown-header {
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
            padding: 0.5rem 1rem;
            margin-bottom: 0;
            border-bottom: 1px solid #dee2e6;
            white-space: nowrap;
        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
        }
        
        .dropdown-toggle {
            font-size: 0.8rem;
            padding: 0.375rem 0.75rem;
            font-weight: 500;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease-in-out;
        }
        
        .dropdown-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .dropdown-toggle i {
            font-size: 0.85rem;
        }
        
        /* Text truncation for long content */
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Ensure flex items don't overflow */
        .d-flex {
            min-width: 0;
        }
        
        .flex-grow-1 {
            min-width: 0;
        }
        
        .flex-shrink-0 {
            flex-shrink: 0;
        }
        
        /* Notification specific styles */
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
        
        /* Table container styles for consistency */
        .table-container {
            overflow: visible !important;
            position: relative;
            z-index: 1;
        }
        
        .table-container table {
            table-layout: fixed;
            width: 100%;
            font-size: 0.85rem;
        }
        
        .table-container th,
        .table-container td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            padding: 0.25rem 0.5rem;
        }
        
        .table-container small {
            font-size: 0.75rem;
        }
        
        /* Reduce horizontal gaps between elements */
        .table-container .badge {
            margin-left: 0.25rem;
            margin-right: 0.25rem;
        }
        
        .table-container .d-block {
            margin-bottom: 0.25rem;
        }
        
        .table-container .mt-1 {
            margin-top: 0.25rem !important;
        }
        
        .table-container .me-1 {
            margin-right: 0.25rem !important;
        }
        
        .table-container .ms-1 {
            margin-left: 0.25rem !important;
        }
        
        .table-responsive {
            overflow: visible !important;
        }
        
        .card-body {
            overflow: visible !important;
            position: relative;
            z-index: 1;
        }
        
        /* Ensure dropdowns appear above everything */
        .card {
            overflow: visible !important;
        }
        
        .main-content {
            overflow: visible !important;
        }
        
        /* Button and Badge Styles */
        .ready-pickup-badge {
            animation: pickupPulse 1.2s infinite alternate;
        }
        
        @keyframes pickupPulse {
            0% { box-shadow: 0 0 0 0 rgba(13,110,253,0.5); }
            100% { box-shadow: 0 0 10px 4px rgba(13,110,253,0.3); }
        }
        
        /* Make action buttons smaller */
        .table-container .btn-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .table-container .btn-group .btn i {
            font-size: 0.8rem;
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
    <link rel="manifest" href="manifest.json?v=1.0.6">
    <meta name="theme-color" content="#2c5aa0">
    
    <!-- PWA Icons -->
    <link rel="apple-touch-icon" href="assets/images/logo-192.png?v=1.0.6">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/logo-192.png?v=1.0.6">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/images/logo-512.png?v=1.0.6">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, var(--primary-color), #3d6db0);">
        <div class="container-fluid">
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
                               data-bs-toggle="dropdown" 
                               data-bs-auto-close="true"
                               data-bs-boundary="viewport"
                               aria-expanded="false">
                                <i class="bi bi-bell"></i>
                                <span class="notification-badge d-none" id="notificationBadge">0</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm" 
                                 aria-labelledby="notificationsDropdown"
                                 style="width: 350px; max-height: 400px; overflow-y: auto;">
                                <div class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>Notifications</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary" id="markAllRead">Mark all read</a>
                                </div>
                                <div class="dropdown-divider"></div>
                                <div id="notificationsList">
                                    <div class="dropdown-item text-center py-3 text-muted">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        Loading notifications...
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-item text-center">
                                    <a href="notifications.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Notification Sound -->
    <audio id="notificationSound" preload="auto">
        <!-- Fallback audio element for older browsers -->
    </audio>
    
    <!-- Notification JavaScript Files -->
    <script src="assets/js/notification-fallback.js"></script>
    <script src="assets/js/notification-sound.js"></script>
    <script src="assets/js/notification-manager.js"></script>
    <script src="assets/js/notifications.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dropdowns with proper positioning
        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(dropdownToggle) {
            new bootstrap.Dropdown(dropdownToggle, {
                boundary: 'viewport',
                placement: 'bottom-end'
            });
            
            // Handle dropdown positioning for notifications
            dropdownToggle.addEventListener('show.bs.dropdown', function (e) {
                const dropdown = this.nextElementSibling;
                if (dropdown && this.id === 'notificationsDropdown') {
                    // Ensure dropdown doesn't get clipped
                    dropdown.style.position = 'absolute';
                    dropdown.style.zIndex = '1060';
                    dropdown.style.minWidth = '350px';
                    dropdown.style.maxWidth = '400px';
                    dropdown.style.maxHeight = '400px';
                    dropdown.style.overflowY = 'auto';
                    
                    // Position dropdown
                    const rect = this.getBoundingClientRect();
                    dropdown.style.top = (rect.bottom + 5) + 'px';
                    dropdown.style.right = '20px';
                }
            });
            
            dropdownToggle.addEventListener('hide.bs.dropdown', function (e) {
                const dropdown = this.nextElementSibling;
                if (dropdown && this.id === 'notificationsDropdown') {
                    // Reset positioning
                    dropdown.style.position = '';
                    dropdown.style.top = '';
                    dropdown.style.right = '';
                    dropdown.style.zIndex = '';
                }
            });
        });

        if (document.getElementById('notificationsDropdown')) {
            const notificationSound = document.getElementById('notificationSound');
            const notificationBadge = document.getElementById('notificationBadge');
            const notificationsList = document.getElementById('notificationsList');
            let lastNotificationCount = -1; // Initialize to -1 to indicate no previous count

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

            // Function to play notification sound
            function playNotificationSound() {
                // Try the main notification sound system (preferred method) - only if enabled
                if (typeof window.NotificationSound !== 'undefined' && window.NotificationSound.userInteracted && window.NotificationSound.enabled) {
                    window.NotificationSound.play();
                } 
                // Try the fallback system only if main system is not available
                else if (typeof window.NotificationFallback !== 'undefined' && window.NotificationFallback.userInteracted) {
                    window.NotificationFallback.play();
                } 
                // Try the manager system only if main system is not available
                else if (typeof window.NotificationManager !== 'undefined' && window.NotificationManager.userInteracted) {
                    window.NotificationManager.playSound();
                }
                // Don't fall back to creating new audio contexts - this prevents unwanted sounds
            }

            // Function to update notifications
            function updateNotifications() {
                fetch('ajax/get-notification-count.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.error || 'Unknown error occurred');
                        }
                        
                        const currentCount = parseInt(data.count) || 0;
                        
                        // Update badge
                        if (currentCount > 0) {
                            notificationBadge.textContent = currentCount > 99 ? '99+' : currentCount;
                            notificationBadge.classList.remove('d-none');
                            
                            // Play sound for new notifications only (and only if we had a previous count)
                            if (currentCount > lastNotificationCount && lastNotificationCount >= 0) {
                                // Enable sound system only when we actually need to play a sound
                                if (typeof window.NotificationSound !== 'undefined') {
                                    window.NotificationSound.enable();
                                    window.NotificationSound.playForNotifications(currentCount, lastNotificationCount);
                                } else {
                                    playNotificationSound();
                                }
                            }
                        } else {
                            notificationBadge.classList.add('d-none');
                            // Reset sound flag when no notifications
                            if (typeof window.NotificationSound !== 'undefined') {
                                window.NotificationSound.resetSoundFlag();
                                window.NotificationSound.disable(); // Disable sound when no notifications
                            }
                        }
                        lastNotificationCount = currentCount;

                        // Update notifications list with enhanced formatting
                        if (data.notifications && data.notifications.length > 0) {
                            let html = '';
                            data.notifications.forEach(function(notification, index) {
                                if (index < 5) { // Show only first 5 in dropdown
                                    html += `
                                        <div class="dropdown-item${!notification.is_read ? ' bg-light' : ''}" style="white-space: normal;">
                                            <div class="d-flex">
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-dark">${notification.title || 'Notification'}</div>
                                                    <div class="text-muted small mt-1">${notification.message}</div>
                                                    <div class="text-muted small mt-1">
                                                        <i class="bi bi-clock me-1"></i>${timeAgo(notification.created_at)}
                                                    </div>
                                                </div>
                                                ${!notification.is_read ? '<div class="flex-shrink-0 ms-2"><span class="badge bg-primary">New</span></div>' : ''}
                                            </div>
                                        </div>
                                    `;
                                }
                            });
                            
                            if (data.notifications.length > 5) {
                                html += `<div class="dropdown-item text-center text-muted">
                                    <i class="bi bi-three-dots"></i> ${data.notifications.length - 5} more notifications
                                </div>`;
                            }
                            
                            notificationsList.innerHTML = html;
                        } else {
                            notificationsList.innerHTML = '<div class="dropdown-item text-center py-3 text-muted">No notifications</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error updating notifications:', error);
                        notificationsList.innerHTML = '<div class="dropdown-item text-center py-3 text-muted">Error loading notifications</div>';
                    });
            }

            // Update notifications every 30 seconds
            setInterval(updateNotifications, 30000);
            
            // Initial update (silent - no sounds on page load)
            setTimeout(function() {
                updateNotifications(); // Delay to ensure no sound on page load
            }, 1000);

            // Mark all notifications as read
            document.getElementById('markAllRead').addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show loading spinner
                const loadingSpinner = document.getElementById('loadingSpinner');
                if (loadingSpinner) {
                    loadingSpinner.classList.remove('d-none');
                }
                
                fetch('ajax/mark-all-notifications-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        notificationBadge.classList.add('d-none');
                        
                        // Update dropdown notifications
                        const notificationItems = document.querySelectorAll('.dropdown-item.fw-bold');
                        notificationItems.forEach(item => {
                            item.classList.remove('fw-bold', 'bg-light');
                        });
                        
                        // Update notifications page if it exists
                        const timelineItems = document.querySelectorAll('.timeline-item.fw-bold');
                        timelineItems.forEach(item => {
                            item.classList.remove('fw-bold');
                            item.classList.add('text-muted');
                            const markReadBtn = item.querySelector('.mark-read');
                            if (markReadBtn) {
                                markReadBtn.remove();
                            }
                        });
                        
                        // Update notification count to 0
                        lastNotificationCount = 0;
                        notificationBadge.textContent = '0';
                        
                        // Reset sound flag when all notifications are marked as read
                        if (typeof window.NotificationSound !== 'undefined') {
                            window.NotificationSound.resetSoundFlag();
                        }
                        
                        // Update dropdown list content
                        updateNotifications();
                    } else {
                        console.error('Failed to mark notifications as read:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error marking all notifications as read:', error);
                })
                .finally(() => {
                    // Hide loading spinner
                    if (loadingSpinner) {
                        loadingSpinner.classList.add('d-none');
                    }
                });
            });
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
</html>
