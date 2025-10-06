<?php
if (!isLoggedIn()) {
    // Public navigation for non-logged in users
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, var(--primary-color), #3d6db0);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-building me-2"></i><?php echo SYSTEM_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house me-2"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="bi bi-info-circle me-2"></i>About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">
                            <i class="bi bi-list-ul me-2"></i>Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="bi bi-telephone me-2"></i>Contact
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ms-2" href="register.php">
                            <i class="bi bi-person-plus me-2"></i>Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php
} else {
    // Authenticated user navigation
    $currentUser = getCurrentUser();
    $unreadNotifications = getUnreadNotifications($_SESSION['user_id'], $_SESSION['role']);
    $notificationCount = count($unreadNotifications);
    ?>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, var(--primary-color), #3d6db0);">
        <div class="container-fluid">
            <!-- Mobile sidebar toggle -->
            <button class="btn btn-link text-white d-lg-none me-2" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i><?php echo SYSTEM_NAME; ?>
            </a>
            
            <!-- User profile dropdown - moved to left -->
            <div class="dropdown ms-3">
                <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                    <div class="me-2">
                        <?php if ($currentUser['profile_picture']): ?>
                            <img src="uploads/profiles/<?php echo htmlspecialchars($currentUser['profile_picture']); ?>" 
                                 alt="Profile" class="rounded-circle" width="32" height="32">
                        <?php else: ?>
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 32px; height: 32px;">
                                <i class="bi bi-person text-white"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-none d-md-block">
                        <div class="fw-bold" style="font-size: 0.9rem;">
                            <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                        </div>
                        <div class="small text-light">
                            <?php echo ucfirst($currentUser['role']); ?>
                        </div>
                    </div>
                </a>
                <ul class="dropdown-menu shadow">
                    <li>
                        <h6 class="dropdown-header">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                            <small class="d-block text-muted"><?php echo ucfirst($currentUser['role']); ?></small>
                        </h6>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="profile.php">
                            <i class="bi bi-person me-2"></i>My Profile
                        </a>
                    </li>
                    <?php if ($currentUser['role'] === 'resident'): ?>
                        <li>
                            <a class="dropdown-item" href="my-applications.php">
                                <i class="bi bi-file-earmark-text me-2"></i>My Applications
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a class="dropdown-item" href="settings.php">
                            <i class="bi bi-gear me-2"></i>Settings
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="d-flex align-items-center ms-auto">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <a class="nav-link position-relative text-white" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="notification-badge"><?php echo min($notificationCount, 99); ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow" style="width: 350px; max-height: 400px; overflow-y: auto;">
                        <h6 class="dropdown-header">
                            <i class="bi bi-bell me-2"></i>Notifications
                            <?php if ($notificationCount > 0): ?>
                                <span class="badge bg-primary float-end"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </h6>
                        
                        <?php if (empty($unreadNotifications)): ?>
                            <div class="dropdown-item text-center py-3 text-muted">
                                <i class="bi bi-check-circle fs-4 d-block mb-2"></i>
                                No new notifications
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($unreadNotifications, 0, 5) as $notification): ?>
                                <div class="dropdown-item notification-item" data-notification-id="<?php echo $notification['id']; ?>">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-2">
                                            <?php
                                            $iconClass = 'bi-info-circle text-info';
                                            switch ($notification['type']) {
                                                case 'new_registration':
                                                    $iconClass = 'bi-person-plus text-primary';
                                                    break;
                                                case 'application_submitted':
                                                    $iconClass = 'bi-file-earmark-plus text-success';
                                                    break;
                                                case 'status_update':
                                                    $iconClass = 'bi-arrow-repeat text-warning';
                                                    break;
                                            }
                                            ?>
                                            <i class="bi <?php echo $iconClass; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold" style="font-size: 0.85rem;"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <p class="mb-1 text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ($notificationCount > 5): ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center text-primary" href="notifications.php">
                                    View all notifications (<?php echo $notificationCount; ?>)
                                </a>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="#" onclick="markAllNotificationsRead()">
                                <i class="bi bi-check-all me-2"></i>Mark all as read
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar backdrop for mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    
    <!-- Sidebar (Left navigation) -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-inner">
       
                <!-- Main Menu -->
                <div class="nav-section">
                    <div class="nav-section-title">MENU</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" 
                               href="dashboard.php">
                                <i class="bi bi-speedometer2"></i>Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
                
                <?php if ($_SESSION['role'] === 'resident'): ?>
                <!-- Resident Navigation Section -->
                <div class="nav-section">
                    <div class="nav-section-title">DOCUMENTS</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'apply.php' ? 'active' : ''; ?>" 
                               href="apply.php">
                                <i class="bi bi-file-earmark-plus"></i>Apply for Document
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">MY ACCOUNT</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>" 
                               href="profile.php">
                                <i class="bi bi-person"></i>My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" 
                               href="settings.php">
                                <i class="bi bi-gear"></i>Settings
                            </a>
                        </li>
                    </ul>
                </div>
                <?php elseif ($_SESSION['role'] === 'purok_leader'): ?>
                <!-- Purok Leader Navigation Section -->
                <div class="nav-section">
                    <div class="nav-section-title">PUROK LEADER</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_pending_registration.php' ? 'active' : ''; ?>" 
                               href="my_pending_registration.php">
                                <i class="bi bi-person-check"></i>My Pending Registrations
                                <?php
                                // Get count of pending registrations for the purok leader's purok
                                if ($_SESSION['role'] === 'purok_leader' && isset($_SESSION['purok_id'])) {
                                    $stmt = $pdo->prepare("
                                        SELECT COUNT(*) FROM users 
                                        WHERE role = 'resident' 
                                        AND purok_id = ? 
                                        AND (purok_leader_approval = 'pending' OR admin_approval = 'pending') 
                                        AND (status != 'approved' OR (purok_leader_approval = 'pending' OR admin_approval = 'pending'))
                                    ");
                                    $stmt->execute([$_SESSION['purok_id']]);
                                    $pendingCount = $stmt->fetchColumn();
                                    if ($pendingCount > 0): ?>
                                        <span class="badge bg-warning text-dark ms-2"><?php echo $pendingCount; ?></span>
                                    <?php endif;
                                }
                                ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_residents.php' ? 'active' : ''; ?>" 
                               href="my_residents.php">
                                <i class="bi bi-people"></i>My Residents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'my_resident_application.php' ? 'active' : ''; ?>" 
                               href="my_resident_application.php">
                                <i class="bi bi-file-earmark-plus"></i>Resident Applications
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">MY ACCOUNT</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>" 
                               href="profile.php">
                                <i class="bi bi-person"></i>My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" 
                               href="settings.php">
                                <i class="bi bi-gear"></i>Settings
                            </a>
                        </li>
                    </ul>
                </div>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <!-- Admin Navigation Section -->
                <div class="nav-section">
                    <div class="nav-section-title">USER MANAGEMENT</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'pending-registrations.php' ? 'active' : ''; ?>" 
                               href="pending-registrations.php">
                                <i class="bi bi-person-check"></i>Pending Registrations
                                <?php
                                // Get count of pending registrations for admin
                                if ($_SESSION['role'] === 'admin') {
                                    $stmt = $pdo->prepare("
                                        SELECT COUNT(*) FROM users 
                                        WHERE role = 'resident' 
                                        AND (admin_approval = 'pending' OR purok_leader_approval = 'pending') 
                                        AND (status != 'approved' OR (purok_leader_approval = 'pending' OR admin_approval = 'pending'))
                                    ");
                                    $stmt->execute();
                                    $pendingCount = $stmt->fetchColumn();
                                    if ($pendingCount > 0): ?>
                                        <span class="badge bg-warning text-dark ms-2"><?php echo $pendingCount; ?></span>
                                    <?php endif;
                                }
                                ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'residents.php' ? 'active' : ''; ?>" 
                               href="residents.php">
                                <i class="bi bi-people"></i>Residents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'purok-leaders.php' ? 'active' : ''; ?>" 
                               href="purok-leaders.php">
                                <i class="bi bi-person-badge"></i>Purok Leaders
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">DOCUMENT MANAGEMENT</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'document-types.php' ? 'active' : ''; ?>" 
                               href="document-types.php">
                                <i class="bi bi-file-earmark-text"></i>Document Types
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'applications.php' ? 'active' : ''; ?>" 
                               href="applications.php">
                                <i class="bi bi-file-earmark-plus"></i>Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'appointments.php' ? 'active' : ''; ?>" 
                               href="appointments.php">
                                <i class="bi bi-calendar-check"></i>Appointments
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="nav-section py-1">
                    <div class="nav-section-title small py-1">REPORTS & STATISTICS</div>
                    <ul class="nav flex-column nav-compact">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" 
                               href="reports.php">
                                <i class="bi bi-graph-up"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'statistics.php' ? 'active' : ''; ?>" 
                               href="statistics.php">
                                <i class="bi bi-pie-chart"></i>Statistics
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="nav-section py-1">
                    <div class="nav-section-title small py-1">SYSTEM</div>
                    <ul class="nav flex-column nav-compact">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'activity-logs.php' ? 'active' : ''; ?>" 
                               href="activity-logs.php">
                                <i class="bi bi-clock-history"></i>Activity Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'system-settings.php' ? 'active' : ''; ?>" 
                               href="system-settings.php">
                                <i class="bi bi-gear"></i>System Settings
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- System Version -->
            <div class="sidebar-version">
                Version <?php echo SYSTEM_VERSION; ?>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                if (backdrop) backdrop.classList.toggle('show');
            });
            
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                });
            }
        }
        
        // Auto-refresh notifications
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                fetch('ajax/get-notification-count.php')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.querySelector('.notification-badge');
                        if (data.count > 0) {
                            if (badge) {
                                badge.textContent = Math.min(data.count, 99);
                            } else {
                                const bellIcon = document.querySelector('.bi-bell').parentElement;
                                if (bellIcon) {
                                    const newBadge = document.createElement('span');
                                    newBadge.className = 'notification-badge';
                                    newBadge.textContent = Math.min(data.count, 99);
                                    bellIcon.appendChild(newBadge);
                                }
                            }
                        } else if (badge) {
                            badge.remove();
                        }
                    });
            }
        }, 30000);
    });
    </script>
    <?php
}
?>
