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
    SELECT a.*, dt.type_name, dt.fee, dt.processing_days,
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
        
        /* Ready for pickup badge animation */
        .ready-pickup-badge {
            animation: pickupPulse 1.2s infinite alternate;
        }
        @keyframes pickupPulse {
            0% { box-shadow: 0 0 0 0 rgba(13,110,253,0.5); }
            100% { box-shadow: 0 0 10px 4px rgba(13,110,253,0.3); }
        }
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
                                                <th>Processing Time</th>
                                                <th>Payment</th>
                                                <th>Receipt</th>
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
                                                        <small class="text-muted d-block"><i class="bi bi-clock-history me-1"></i>Processing Time</small>
                                                        <?php if ($app['status'] === 'processing' && $app['payment_date'] && ($app['payment_status'] === 'paid' || $app['payment_status'] === 'waived')): ?>
                                                            <?php
                                                            try {
                                                                $paymentDate = new DateTime($app['payment_date']);
                                                                ?>
                                                                <strong class="d-block processing-countdown-timer" data-payment-date="<?php echo $paymentDate->format('Y-m-d H:i:s'); ?>" data-status="<?php echo $app['status']; ?>" data-processing-hours="<?php echo $app['processing_days'] * 24; ?>">
                                                                    <i class="bi bi-hourglass-split me-1"></i><span class="countdown-timer-text">00:00:00</span>
                                                                </strong>
                                                                <?php
                                                                $today = new DateTime();
                                                                $today->setTime(0, 0, 0);
                                                                
                                                                // Calculate expected date based on processing_days
                                                                $expectedDate = clone $paymentDate;
                                                                $daysAdded = 0;
                                                                while ($daysAdded < $app['processing_days']) {
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
                                                                    while ($daysAdded < $app['processing_days']) {
                                                                        $expectedDate->modify('+1 day');
                                                                        if ($expectedDate->format('N') < 6) {
                                                                            $daysAdded++;
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                                <br><small class="text-muted">
                                                                    <i class="bi bi-calendar-check me-1"></i>
                                                                    Ready by: <span class="processing-countdown" data-expected-date="<?php echo $expectedDate->format('Y-m-d H:i:s'); ?>"><?php echo $expectedDate->format('M j, Y'); ?></span>
                                                                </small>
                                                                <?php
                                                            } catch (Exception $e) {
                                                                // Skip if date is invalid
                                                            }
                                                            ?>
                                                        <?php else: ?>
                                                            <strong><?php echo $app['processing_days'] * 24; ?> hours</strong>
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
                                                    <td>
                                                        <?php if (!empty($app['payment_receipt'])): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                                    onclick="viewReceipt('<?php echo htmlspecialchars($app['payment_receipt']); ?>')">
                                                                <i class="bi bi-image"></i> View
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
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
                                                                   class="btn btn-sm btn-outline-success"
                                                                   title="Click to proceed with payment">
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
                                                        <small class="text-muted d-block"><i class="bi bi-clock-history me-1"></i>Processing Time</small>
                                                        <?php if ($app['status'] === 'processing' && $app['payment_date'] && ($app['payment_status'] === 'paid' || $app['payment_status'] === 'waived')): ?>
                                                            <?php
                                                            try {
                                                                $paymentDate = new DateTime($app['payment_date']);
                                                                ?>
                                                                <strong class="d-block processing-countdown-timer" data-payment-date="<?php echo $paymentDate->format('Y-m-d H:i:s'); ?>" data-status="<?php echo $app['status']; ?>" data-processing-hours="<?php echo $app['processing_days'] * 24; ?>">
                                                                    <i class="bi bi-hourglass-split me-1"></i><span class="countdown-timer-text">00:00:00</span>
                                                                </strong>
                                                                <?php
                                                                $today = new DateTime();
                                                                $today->setTime(0, 0, 0);
                                                                
                                                                // Calculate expected date based on processing_days
                                                                $expectedDate = clone $paymentDate;
                                                                $daysAdded = 0;
                                                                while ($daysAdded < $app['processing_days']) {
                                                                    $expectedDate->modify('+1 day');
                                                                    if ($expectedDate->format('N') < 6) {
                                                                        $daysAdded++;
                                                                    }
                                                                }
                                                                
                                                                if ($expectedDate < $today) {
                                                                    $expectedDate = clone $today;
                                                                    $daysAdded = 0;
                                                                    while ($daysAdded < $app['processing_days']) {
                                                                        $expectedDate->modify('+1 day');
                                                                        if ($expectedDate->format('N') < 6) {
                                                                            $daysAdded++;
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                                <br><small class="text-muted">Ready by: <span class="processing-countdown" data-expected-date="<?php echo $expectedDate->format('Y-m-d H:i:s'); ?>"><?php echo $expectedDate->format('M j, Y'); ?></span></small>
                                                                <?php
                                                            } catch (Exception $e) {
                                                                // Skip if date is invalid
                                                            }
                                                            ?>
                                                        <?php else: ?>
                                                            <strong><?php echo $app['processing_days'] * 24; ?> hours</strong>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Payment Status</small>
                                                        <span class="badge bg-<?php 
                                                            echo $app['payment_status'] === 'paid' ? 'success' : 
                                                                ($app['payment_status'] === 'waived' ? 'info' : 'warning');
                                                        ?>">
                                                            <?php echo ucfirst($app['payment_status']); ?>
                                                        </span>
                                                        <?php if (!empty($app['payment_receipt'])): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-info ms-2" 
                                                                    onclick="viewReceipt('<?php echo htmlspecialchars($app['payment_receipt']); ?>')">
                                                                <i class="bi bi-image"></i> View Receipt
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="d-flex gap-2">
                                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary flex-fill">
                                                            <i class="bi bi-eye me-1"></i>View
                                                        </a>
                                                        <?php if ($app['status'] === 'pending' && $app['payment_status'] === 'unpaid'): ?>
                                                            <a href="pay-application.php?id=<?php echo $app['id']; ?>" 
                                                               class="btn btn-sm btn-outline-success flex-fill"
                                                               title="Click to proceed with payment">
                                                                <i class="bi bi-credit-card me-1"></i>Pay
                                                            </a>
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
                            <p class="mb-2">
                                <strong>When does processing start?</strong> Processing begins after the admin confirms your payment and starts the processing. 
                                The countdown starts from when processing begins, not the application submission date.
                            </p>
                            <p class="mb-0">
                                <strong>Workflow:</strong> Submit Application → Pay at Barangay → Payment Confirmed → Processing Starts → Ready for Pickup → Completed
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
                                <h6 class="text-muted mb-3"><i class="bi bi-file-earmark-text me-1"></i>Application Status</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge status-pending me-2">Pending</span>
                                    <span>Waiting for payment confirmation</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge status-processing me-2">Processing</span>
                                    <span>Document is being processed (3-5 working days)</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted mb-3"><i class="bi bi-check-circle me-1"></i>Completion Status</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-primary text-white fw-bold ready-pickup-badge me-2">Ready</span>
                                    <span>Document is ready for pickup</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge status-completed me-2">Completed</span>
                                    <span>Document has been released</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted mb-3"><i class="bi bi-cash-coin me-1"></i>Payment Status</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-success me-2">Paid</span>
                                    <span>Payment confirmed - Processing will start</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning me-2">Unpaid</span>
                                    <span>Awaiting payment - Processing on hold</span>
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
// Real-time countdown timer for processing time (time remaining)
function updateElapsedTime() {
    const countdownTimers = document.querySelectorAll('.processing-countdown-timer');
    
    countdownTimers.forEach(timer => {
        // Only run timer if status is 'processing'
        const status = timer.getAttribute('data-status');
        if (status !== 'processing') {
            return; // Skip if status is not processing
        }
        
        const paymentDateStr = timer.getAttribute('data-payment-date');
        const processingHours = parseInt(timer.getAttribute('data-processing-hours')) || 0;
        
        if (!paymentDateStr || processingHours <= 0) return;
        
        const paymentDate = new Date(paymentDateStr);
        const now = new Date();
        const elapsedMs = now - paymentDate; // Time elapsed since payment/processing started
        
        if (elapsedMs < 0) {
            // Processing hasn't started yet
            const totalMs = processingHours * 60 * 60 * 1000;
            const remainingMs = totalMs;
            const hours = Math.floor(remainingMs / (1000 * 60 * 60));
            const minutes = Math.floor((remainingMs % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((remainingMs % (1000 * 60)) / 1000);
            timer.querySelector('.countdown-timer-text').textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            return;
        }
        
        // Calculate total processing time in milliseconds
        const totalMs = processingHours * 60 * 60 * 1000;
        
        // Calculate remaining time
        const remainingMs = totalMs - elapsedMs;
        
        if (remainingMs <= 0) {
            // Processing time is complete
            timer.querySelector('.countdown-timer-text').textContent = '00:00:00';
            timer.classList.add('text-danger');
            return;
        }
        
        // Calculate days, hours, minutes, seconds remaining
        const days = Math.floor(remainingMs / (1000 * 60 * 60 * 24));
        const hours = Math.floor((remainingMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((remainingMs % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((remainingMs % (1000 * 60)) / 1000);
        
        // Format as countdown timer: HH:MM:SS or Dd HH:MM:SS
        let countdownText = '';
        if (days > 0) {
            countdownText = `${days}d ${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        } else {
            countdownText = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }
        
        timer.querySelector('.countdown-timer-text').textContent = countdownText;
    });
}

// Real-time countdown timer for expected completion
function updateCountdowns() {
    const timers = document.querySelectorAll('.processing-timer');
    
    timers.forEach(timer => {
        const expectedDateStr = timer.getAttribute('data-expected-date');
        if (!expectedDateStr) return;
        
        const expectedDate = new Date(expectedDateStr);
        const now = new Date();
        const diff = expectedDate - now;
        
        if (diff <= 0) {
            timer.querySelector('.countdown-text').textContent = 'Processing complete!';
            timer.classList.remove('text-success');
            timer.classList.add('text-info');
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        let countdownText = '';
        if (days > 0) {
            countdownText = `${days}d ${hours}h ${minutes}m remaining`;
        } else if (hours > 0) {
            countdownText = `${hours}h ${minutes}m ${seconds}s remaining`;
        } else if (minutes > 0) {
            countdownText = `${minutes}m ${seconds}s remaining`;
        } else {
            countdownText = `${seconds}s remaining`;
        }
        
        timer.querySelector('.countdown-text').textContent = countdownText;
    });
}

// Update all timers on page load and every second
document.addEventListener('DOMContentLoaded', function() {
    updateElapsedTime();
    updateCountdowns();
    setInterval(function() {
        updateElapsedTime();
        updateCountdowns();
    }, 1000);
    
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

<!-- Receipt Preview Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="receiptImage" src="" alt="Payment Receipt" class="img-fluid" style="max-height: 75vh; width: auto; max-width: 100%;">
                <div id="receiptPdf" style="display: none;">
                    <iframe id="pdfFrame" style="width: 100%; height: 75vh; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewReceipt(receiptPath) {
    const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
    const img = document.getElementById('receiptImage');
    const pdfDiv = document.getElementById('receiptPdf');
    const pdfFrame = document.getElementById('pdfFrame');
    
    if (receiptPath.toLowerCase().endsWith('.pdf')) {
        img.style.display = 'none';
        pdfDiv.style.display = 'block';
        pdfFrame.src = receiptPath;
    } else {
        pdfDiv.style.display = 'none';
        img.style.display = 'block';
        img.src = receiptPath;
    }
    
    modal.show();
}
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