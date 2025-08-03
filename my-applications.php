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

// Get all applications for the current user with payment appointment info
$stmt = $pdo->prepare("
    SELECT a.*, dt.type_name, dt.fee,
           apt.id as payment_appointment_id,
           apt.appointment_date as payment_appointment_date,
           apt.status as payment_appointment_status
    FROM applications a
    JOIN document_types dt ON a.document_type_id = dt.id
    LEFT JOIN appointments apt ON a.id = apt.application_id AND apt.appointment_type = 'payment'
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
                            <!-- Desktop Table View -->
                            <div class="d-none d-lg-block">
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
                                                                <?php if ($app['payment_appointment_id'] && $app['payment_appointment_status'] === 'payment_allowed'): ?>
                                                                    <a href="pay-application.php?id=<?php echo $app['id']; ?>" 
                                                                       class="btn btn-sm btn-outline-success"
                                                                       title="Payment is now allowed. Click to proceed with payment.">
                                                                        <i class="bi bi-credit-card"></i>
                                                                    </a>
                                                                <?php elseif ($app['payment_appointment_id'] && $app['payment_appointment_status'] === 'scheduled'): ?>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-secondary"
                                                                            disabled
                                                                            title="Payment appointment scheduled for <?php echo date('M j, Y g:i A', strtotime($app['payment_appointment_date'])); ?>. Waiting for admin to allow payment.">
                                                                        <i class="bi bi-credit-card"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-secondary"
                                                                            disabled
                                                                            title="Payment appointment required. Please contact the barangay office.">
                                                                        <i class="bi bi-credit-card"></i>
                                                                    </button>
                                                                <?php endif; ?>
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
                            </div>

                            <!-- Mobile Card View -->
                            <div class="d-lg-none">
                                <div class="row g-3">
                                    <?php foreach ($applications as $app): ?>
                                        <div class="col-12">
                                            <div class="card border shadow-sm">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <?php echo htmlspecialchars($app['application_number']); ?>
                                                            </h6>
                                                            <small class="text-muted">
                                                                <?php echo date('M j, Y g:i A', strtotime($app['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <span class="badge status-<?php echo $app['status']; ?>
                                                            <?php if ($app['status'] === 'ready_for_pickup') echo ' bg-primary text-white fw-bold ready-pickup-badge'; ?>">
                                                            <?php if ($app['status'] === 'ready_for_pickup'): ?>
                                                                <i class="bi bi-check-circle me-1"></i>
                                                            <?php endif; ?>
                                                            <?php echo ucfirst($app['status']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Document Type</small>
                                                        <strong><?php echo htmlspecialchars($app['type_name']); ?></strong>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Purpose</small>
                                                        <p class="mb-0 small">
                                                            <?php 
                                                            $purpose = htmlspecialchars($app['purpose']);
                                                            echo strlen($purpose) > 100 ? substr($purpose, 0, 97) . '...' : $purpose;
                                                            ?>
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Payment Status</small>
                                                        <span class="badge bg-<?php 
                                                            echo $app['payment_status'] === 'paid' ? 'success' : 
                                                                ($app['payment_status'] === 'waived' ? 'info' : 'warning');
                                                        ?>">
                                                            <?php echo ucfirst($app['payment_status']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="d-flex gap-2">
                                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary flex-fill">
                                                            <i class="bi bi-eye me-1"></i>View
                                                        </a>
                                                        <?php if ($app['status'] === 'pending' && $app['payment_status'] === 'unpaid'): ?>
                                                            <?php if ($app['payment_appointment_id'] && $app['payment_appointment_status'] === 'payment_allowed'): ?>
                                                                <a href="pay-application.php?id=<?php echo $app['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-success flex-fill"
                                                                   title="Payment is now allowed. Click to proceed with payment.">
                                                                    <i class="bi bi-credit-card me-1"></i>Pay
                                                                </a>
                                                            <?php elseif ($app['payment_appointment_id'] && $app['payment_appointment_status'] === 'scheduled'): ?>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-secondary flex-fill"
                                                                        disabled
                                                                        title="Payment appointment scheduled for <?php echo date('M j, Y g:i A', strtotime($app['payment_appointment_date'])); ?>. Waiting for admin to allow payment.">
                                                                    <i class="bi bi-credit-card me-1"></i>Pay
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-secondary flex-fill"
                                                                        disabled
                                                                        title="Payment appointment required. Please contact the barangay office.">
                                                                    <i class="bi bi-credit-card me-1"></i>Pay
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <?php if ($app['status'] === 'ready_for_pickup'): ?>
                                                            <a href="schedule-pickup.php?id=<?php echo $app['id']; ?>" 
                                                               class="btn btn-sm btn-outline-info flex-fill">
                                                                <i class="bi bi-calendar-check me-1"></i>Schedule
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
    // Only initialize DataTable for desktop view
    if (window.innerWidth >= 992) {
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
    }
});
</script>

<?php include 'scripts.php'; ?> 