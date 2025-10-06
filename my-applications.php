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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c5aa0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 8px;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-processing { background-color: #17a2b8; color: #fff; }
        .status-ready_for_pickup { background-color: #28a745; color: #fff; }
        .status-completed { background-color: #6c757d; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back
                            </a>
                            <div>
                                <h1 class="h3 mb-2">My Applications</h1>
                                <p class="text-muted mb-0">Track and manage your document applications</p>
                            </div>
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
                                                        <?php if ($app['status'] === 'processing' && $app['payment_date'] && ($app['payment_status'] === 'paid' || $app['payment_status'] === 'waived')): ?>
                                                            <?php
                                                            // Calculate expected completion date (5 working days from payment date)
                                                            try {
                                                                $paymentDate = new DateTime($app['payment_date']);
                                                                $today = new DateTime();
                                                                $today->setTime(0, 0, 0); // Reset to start of day for comparison
                                                                
                                                                // Calculate expected date from payment date
                                                                $expectedDate = clone $paymentDate;
                                                                $daysAdded = 0;
                                                                while ($daysAdded < 5) {
                                                                    $expectedDate->modify('+1 day');
                                                                    // Skip weekends
                                                                    if ($expectedDate->format('N') < 6) {
                                                                        $daysAdded++;
                                                                    }
                                                                }
                                                                
                                                                // If expected date is in the past, recalculate from today
                                                                if ($expectedDate < $today) {
                                                                    $expectedDate = clone $today;
                                                                    $daysAdded = 0;
                                                                    while ($daysAdded < 5) {
                                                                        $expectedDate->modify('+1 day');
                                                                        if ($expectedDate->format('N') < 6) {
                                                                            $daysAdded++;
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                                <small class="d-block text-muted mt-1">
                                                                    <i class="bi bi-calendar-check me-1"></i>Expected: <?php echo $expectedDate->format('M j, Y'); ?>
                                                                </small>
                                                                <?php
                                                            } catch (Exception $e) {
                                                                // Skip if date is invalid
                                                            }
                                                            ?>
                                                        <?php endif; ?>
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
                                                                <?php 
                                                                // Allow payment if:
                                                                // 1. No appointment required (no appointment exists), OR
                                                                // 2. Appointment exists and is allowed by admin
                                                                $canPay = !$app['payment_appointment_id'] || $app['payment_appointment_status'] === 'payment_allowed';
                                                                ?>
                                                                <?php if ($canPay): ?>
                                                                    <a href="pay-application.php?id=<?php echo $app['id']; ?>" 
                                                                       class="btn btn-sm btn-outline-success"
                                                                       title="Click to proceed with payment">
                                                                        <i class="bi bi-credit-card"></i>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-secondary"
                                                                            disabled
                                                                            title="Payment appointment scheduled for <?php echo date('M j, Y g:i A', strtotime($app['payment_appointment_date'])); ?>. Waiting for admin to allow payment.">
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
                                                        <?php if ($app['status'] === 'processing' && $app['payment_date'] && ($app['payment_status'] === 'paid' || $app['payment_status'] === 'waived')): ?>
                                                            <?php
                                                            // Calculate expected completion date (5 working days from payment date)
                                                            try {
                                                                $paymentDate = new DateTime($app['payment_date']);
                                                                $today = new DateTime();
                                                                $today->setTime(0, 0, 0); // Reset to start of day for comparison
                                                                
                                                                // Calculate expected date from payment date
                                                                $expectedDate = clone $paymentDate;
                                                                $daysAdded = 0;
                                                                while ($daysAdded < 5) {
                                                                    $expectedDate->modify('+1 day');
                                                                    // Skip weekends
                                                                    if ($expectedDate->format('N') < 6) {
                                                                        $daysAdded++;
                                                                    }
                                                                }
                                                                
                                                                // If expected date is in the past, recalculate from today
                                                                if ($expectedDate < $today) {
                                                                    $expectedDate = clone $today;
                                                                    $daysAdded = 0;
                                                                    while ($daysAdded < 5) {
                                                                        $expectedDate->modify('+1 day');
                                                                        if ($expectedDate->format('N') < 6) {
                                                                            $daysAdded++;
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                                <br><small class="text-muted">
                                                                    <i class="bi bi-calendar-check me-1"></i>Expected: <?php echo $expectedDate->format('M j, Y'); ?>
                                                                </small>
                                                                <?php
                                                            } catch (Exception $e) {
                                                                // Skip if date is invalid
                                                            }
                                                            ?>
                                                        <?php endif; ?>
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
                                                            <?php 
                                                            // Allow payment if:
                                                            // 1. No appointment required (no appointment exists), OR
                                                            // 2. Appointment exists and is allowed by admin
                                                            $canPay = !$app['payment_appointment_id'] || $app['payment_appointment_status'] === 'payment_allowed';
                                                            ?>
                                                            <?php if ($canPay): ?>
                                                                <a href="pay-application.php?id=<?php echo $app['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-success flex-fill"
                                                                   title="Click to proceed with payment">
                                                                    <i class="bi bi-credit-card me-1"></i>Pay
                                                                </a>
                                                            <?php else: ?>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-secondary flex-fill"
                                                                        disabled
                                                                        title="Payment appointment scheduled for <?php echo date('M j, Y g:i A', strtotime($app['payment_appointment_date'])); ?>. Waiting for admin to allow payment.">
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
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Processing Time Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <h6 class="mb-2"><i class="bi bi-info-circle me-2"></i>Important Information</h6>
                            <p class="mb-2">
                                <strong>Processing Time:</strong> All document applications take <strong>3 to 5 working days (except holidays)</strong> to process.
                            </p>
                            <p class="mb-0">
                                <strong>When does processing start?</strong> Processing begins automatically after your payment is confirmed. 
                                The countdown starts from the payment date, not the application submission date.
                            </p>
                        </div>
                    </div>
                </div>
                
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
                                    <span>Waiting for payment</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge status-processing me-2">Processing</span>
                                    <span>Document is being processed (3-5 days)</span>
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
                                    <span>Payment received - Processing started</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning me-2">Unpaid</span>
                                    <span>Payment pending - Processing not yet started</span>
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

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

</body>
</html> 