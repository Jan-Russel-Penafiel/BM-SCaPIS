<?php
require_once 'config.php';

// Require login and must be a resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'My Applications';
$currentUser = getCurrentUser();

// Get all applications for the current user
$stmt = $pdo->prepare("
    SELECT a.*, dt.type_name, dt.fee
    FROM applications a
    JOIN document_types dt ON a.document_type_id = dt.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll();

include 'header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2">My Applications</h1>
                                <p class="text-muted mb-0">Track and manage your document applications</p>
                            </div>
                            <a href="apply.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>New Application
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Applications Table -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>All Applications
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($applications)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-file-earmark-text text-muted" style="font-size: 4rem;"></i>
                                <h5 class="mt-3">No Applications Yet</h5>
                                <p class="text-muted">Start by applying for your first document</p>
                                <a href="apply.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Apply Now
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="applicationsTable">
                                    <thead>
                                        <tr>
                                            <th>Application #</th>
                                            <th>Document Type</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Date Applied</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($app['application_number']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($app['type_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $purpose = htmlspecialchars($app['purpose']);
                                                    echo strlen($purpose) > 50 ? substr($purpose, 0, 47) . '...' : $purpose;
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge status-<?php echo $app['status']; ?>
                                                        <?php if ($app['status'] === 'ready_for_pickup') echo ' bg-primary text-white fw-bold ready-pickup-badge'; ?>">
                                                        <?php if ($app['status'] === 'ready_for_pickup'): ?>
                                                            <i class="bi bi-check-circle me-1"></i>
                                                        <?php endif; ?>
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $app['payment_status'] === 'paid' ? 'success' : 
                                                            ($app['payment_status'] === 'waived' ? 'info' : 'warning');
                                                    ?>">
                                                        <?php echo ucfirst($app['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($app['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if ($app['status'] === 'pending' && $app['payment_status'] === 'unpaid'): ?>
                                                            <a href="pay-application.php?id=<?php echo $app['id']; ?>" 
                                                               class="btn btn-sm btn-outline-success">
                                                                <i class="bi bi-credit-card"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($app['status'] === 'ready_for_pickup'): ?>
                                                            <a href="schedule-pickup.php?id=<?php echo $app['id']; ?>" 
                                                               class="btn btn-sm btn-outline-info">
                                                                <i class="bi bi-calendar-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Status Guide -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>Application Status Guide
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge status-pending me-2">Pending</span>
                                    <span>Application is under initial review</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge status-processing me-2">Processing</span>
                                    <span>Document is being processed</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge status-ready_for_pickup me-2">Ready</span>
                                    <span>Document is ready for pickup</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge status-completed me-2">Completed</span>
                                    <span>Document has been released</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-success me-2">Paid</span>
                                    <span>Payment has been received</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning me-2">Unpaid</span>
                                    <span>Payment is pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    if (document.getElementById('applicationsTable')) {
        $('#applicationsTable').DataTable({
            order: [[5, 'desc']], // Sort by date applied
            pageLength: 10,
            responsive: true,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search applications...'
            }
        });
    }
});
</script>

<?php include 'scripts.php'; ?> 