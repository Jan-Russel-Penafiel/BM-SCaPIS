<?php
require_once 'config.php';

// Require login
requireLogin();

$pageTitle = 'Dashboard';
$currentUser = getCurrentUser();

// Get statistics based on user role
$stats = [];

if ($_SESSION['role'] === 'admin') {
    // Admin dashboard statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'resident' AND status = 'pending'");
    $stmt->execute();
    $stats['pending_registrations'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'resident' AND status = 'approved'");
    $stmt->execute();
    $stats['total_residents'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_applications'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'completed' AND DATE(updated_at) = CURDATE()");
    $stmt->execute();
    $stats['completed_today'] = $stmt->fetchColumn();
    
    // Recent activities
    $stmt = $pdo->prepare("
        SELECT al.*, u.first_name, u.last_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll();
    
} elseif ($_SESSION['role'] === 'purok_leader') {
    // Purok leader dashboard statistics
    $purokId = $currentUser['purok_id'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE purok_id = ? AND role = 'resident' AND status = 'pending'");
    $stmt->execute([$purokId]);
    $stats['pending_registrations'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE purok_id = ? AND role = 'resident' AND status = 'approved'");
    $stmt->execute([$purokId]);
    $stats['total_residents'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM applications a 
        JOIN users u ON a.user_id = u.id 
        WHERE u.purok_id = ? AND a.status = 'pending'
    ");
    $stmt->execute([$purokId]);
    $stats['pending_applications'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM applications a 
        JOIN users u ON a.user_id = u.id 
        WHERE u.purok_id = ? AND a.status = 'completed' AND DATE(a.updated_at) = CURDATE()
    ");
    $stmt->execute([$purokId]);
    $stats['completed_today'] = $stmt->fetchColumn();
    
    // Recent activities for purok leaders
    $stmt = $pdo->prepare("
        SELECT al.*, u.first_name, u.last_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        WHERE al.user_id = ? OR u.purok_id = ?
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $purokId]);
    $recentActivities = $stmt->fetchAll();
    
} elseif ($_SESSION['role'] === 'resident') {
    // Resident dashboard statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_applications'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['pending_applications'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['completed_applications'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status = 'processing'");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['processing_applications'] = $stmt->fetchColumn();
    
    // Recent applications
    $stmt = $pdo->prepare("
        SELECT a.*, dt.type_name 
        FROM applications a 
        JOIN document_types dt ON a.document_type_id = dt.id 
        WHERE a.user_id = ? 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recentApplications = $stmt->fetchAll();
}

include 'header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0">
                            Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>!
                        </h1>
                        <p class="text-muted mb-0">
                            <?php 
                            switch ($_SESSION['role']) {
                                case 'admin':
                                    echo 'Manage the system and oversee all barangay operations.';
                                    break;
                                case 'purok_leader':
                                    echo 'Manage residents and applications in your purok.';
                                    break;
                                case 'resident':
                                    echo 'Request documents and track your transactions here.';
                                    break;
                            }
                            ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block"><?php echo date('l, F j, Y'); ?></small>
                        <small class="text-muted"><?php echo date('g:i A'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card primary border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Pending Registrations</h6>
                                    <h2 class="mb-0 text-primary"><?php echo number_format($stats['pending_registrations']); ?></h2>
                                </div>
                                <div class="text-primary">
                                    <i class="bi bi-person-check" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="pending-registrations.php" class="btn btn-sm btn-outline-primary">
                                    View All <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card success border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Total Residents</h6>
                                    <h2 class="mb-0 text-success"><?php echo number_format($stats['total_residents']); ?></h2>
                                </div>
                                <div class="text-success">
                                    <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="residents.php" class="btn btn-sm btn-outline-success">
                                    View All <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card warning border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Pending Applications</h6>
                                    <h2 class="mb-0 text-warning"><?php echo number_format($stats['pending_applications']); ?></h2>
                                </div>
                                <div class="text-warning">
                                    <i class="bi bi-clock" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="applications.php" class="btn btn-sm btn-outline-warning">
                                    View All <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card info border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Completed Today</h6>
                                    <h2 class="mb-0 text-info"><?php echo number_format($stats['completed_today']); ?></h2>
                                </div>
                                <div class="text-info">
                                    <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">Applications processed today</small>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($_SESSION['role'] === 'purok_leader'): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card primary border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Pending Registrations</h6>
                                    <h2 class="mb-0 text-primary"><?php echo number_format($stats['pending_registrations']); ?></h2>
                                </div>
                                <div class="text-primary">
                                    <i class="bi bi-person-check" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="pending-registrations.php" class="btn btn-sm btn-outline-primary">
                                    Review <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card success border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Purok Residents</h6>
                                    <h2 class="mb-0 text-success"><?php echo number_format($stats['total_residents']); ?></h2>
                                </div>
                                <div class="text-success">
                                    <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card warning border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Pending Applications</h6>
                                    <h2 class="mb-0 text-warning"><?php echo number_format($stats['pending_applications']); ?></h2>
                                </div>
                                <div class="text-warning">
                                    <i class="bi bi-clock" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card info border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Completed Today</h6>
                                    <h2 class="mb-0 text-info"><?php echo number_format($stats['completed_today']); ?></h2>
                                </div>
                                <div class="text-info">
                                    <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: // Resident ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card primary border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Total Documents</h6>
                                    <h2 class="mb-0 text-primary"><?php echo number_format($stats['total_applications']); ?></h2>
                                </div>
                                <div class="text-primary">
                                    <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="my-applications.php" class="btn btn-sm btn-outline-primary">
                                    View All <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card warning border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Pending</h6>
                                    <h2 class="mb-0 text-warning"><?php echo number_format($stats['pending_applications']); ?></h2>
                                </div>
                                <div class="text-warning">
                                    <i class="bi bi-clock" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card info border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Processing</h6>
                                    <h2 class="mb-0 text-info"><?php echo number_format($stats['processing_applications']); ?></h2>
                                </div>
                                <div class="text-info">
                                    <i class="bi bi-arrow-repeat" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card success border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-2">Completed</h6>
                                    <h2 class="mb-0 text-success"><?php echo number_format($stats['completed_applications']); ?></h2>
                                </div>
                                <div class="text-success">
                                    <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="pending-registrations.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-person-check fs-1 mb-2"></i>
                                        <span class="small">Approve Registrations</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="applications.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-file-earmark-check fs-1 mb-2"></i>
                                        <span class="small">Process Applications</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="document-types.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-file-earmark-text fs-1 mb-2"></i>
                                        <span class="small">Manage Documents</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="reports.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-graph-up fs-1 mb-2"></i>
                                        <span class="small">View Reports</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="purok-leaders.php" class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-person-badge fs-1 mb-2"></i>
                                        <span class="small">Manage Leaders</span>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="system-settings.php" class="btn btn-outline-dark w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-gear fs-1 mb-2"></i>
                                        <span class="small">System Settings</span>
                                    </a>
                                </div>
                                
                            <?php elseif ($_SESSION['role'] === 'purok_leader'): ?>
                                <div class="col-lg-3 col-md-6">
                                    <a href="pending-registrations.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-person-check fs-1 mb-2"></i>
                                        <span class="small">Review Registrations</span>
                                    </a>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <a href="applications.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-file-earmark-text fs-1 mb-2"></i>
                                        <span class="small">View Applications</span>
                                    </a>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <a href="residents.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-people fs-1 mb-2"></i>
                                        <span class="small">Purok Residents</span>
                                    </a>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <a href="profile.php" class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-person fs-1 mb-2"></i>
                                        <span class="small">My Profile</span>
                                    </a>
                                </div>
                                
                            <?php else: // Resident ?>
                                <div class="col-lg-3 col-md-6">
                                    <a href="apply.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-file-earmark-plus fs-1 mb-2"></i>
                                        <span class="small">Request Document</span>
                                    </a>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <a href="my-applications.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-list-ul fs-1 mb-2"></i>
                                        <span class="small">Documents Status</span>
                                    </a>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <a href="track-application.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-search fs-1 mb-2"></i>
                                        <span class="small">Track Application</span>
                                    </a>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <a href="my_appointments.php" class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3">
                                        <i class="bi bi-calendar-check fs-1 mb-2"></i>
                                        <span class="small">My Appointments</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity / Applications -->
        <div class="row g-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'purok_leader'): ?>
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history me-2"></i>Recent System Activity
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentActivities)): ?>
                                <div class="timeline">
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <div class="timeline-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                                    <p class="text-muted mb-1">
                                                        <?php if ($activity['first_name']): ?>
                                                            by <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                                        <?php else: ?>
                                                            System action
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="activity-logs.php" class="btn btn-outline-primary">
                                        View All Activity <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-clock-history fs-1 mb-3 d-block"></i>
                                    <p>No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($_SESSION['role'] === 'resident'): ?>
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-file-earmark-text me-2"></i>Recent Transactions
                            </h5>
                            <a href="my-applications.php" class="btn btn-sm btn-outline-primary">
                                View All <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentApplications)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Application #</th>
                                                <th>Document Type</th>
                                                <th>Status</th>
                                                <th>Date Applied</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentApplications as $app): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($app['application_number'] ?? 'N/A'); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($app['type_name'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge status-<?php echo $app['status']; ?>">
                                                            <?php echo ucfirst($app['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($app['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-file-earmark-plus text-muted" style="font-size: 4rem;"></i>
                                    <h5 class="text-muted mt-3">No Transactions Yet</h5>
                                    <p class="text-muted">Start by requesting your first document</p>
                                    <a href="apply.php" class="btn btn-primary">
                                        <i class="bi bi-file-earmark-plus me-2"></i>Request Now
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<script>
    // Auto-refresh dashboard data every 60 seconds
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            location.reload();
        }
    }, 60000);
    
    // Add fade-in animation to cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 100);
        });
    });
</script>

<?php include 'scripts.php'; ?>
