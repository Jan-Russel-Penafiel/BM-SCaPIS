<?php
require_once 'config.php';

// Require login
requireLogin();

$pageTitle = 'View Application';
$currentUser = getCurrentUser();
$error = '';
$success = '';

// Get application ID from URL
$applicationId = $_GET['id'] ?? 0;

// Check if user has permission to view this application
$stmt = $pdo->prepare("
    SELECT a.user_id, u.purok_id 
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.id = ?
");
$stmt->execute([$applicationId]);
$applicationAccess = $stmt->fetch();

// Verify access based on role
if (!$applicationAccess) {
    $_SESSION['error'] = 'Application not found.';
    header('Location: ' . ($_SESSION['role'] === 'resident' ? 'my-applications.php' : 'applications.php'));
    exit;
}

// Check permissions
if ($_SESSION['role'] === 'resident' && $applicationAccess['user_id'] !== $_SESSION['user_id']) {
    $_SESSION['error'] = 'You do not have permission to view this application.';
    header('Location: my-applications.php');
    exit;
} elseif ($_SESSION['role'] === 'purok_leader' && $applicationAccess['purok_id'] !== $currentUser['purok_id']) {
    $_SESSION['error'] = 'This application is not from your purok.';
    header('Location: applications.php');
    exit;
}

// Get application details
$stmt = $pdo->prepare("
    SELECT a.*, dt.type_name, dt.fee, dt.processing_days,
           CONCAT(pb.first_name, ' ', pb.last_name) as processed_by_name,
           CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
           u.contact_number, u.email, p.purok_name
    FROM applications a
    JOIN document_types dt ON a.document_type_id = dt.id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users pb ON a.processed_by = pb.id
    LEFT JOIN puroks p ON u.purok_id = p.id
    WHERE a.id = ?
");
$stmt->execute([$applicationId]);
$application = $stmt->fetch();

// If application not found or doesn't belong to user
if (!$application) {
    header('Location: my-applications.php');
    exit;
}

// Get application history
$stmt = $pdo->prepare("
    SELECT ah.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
    FROM application_history ah
    LEFT JOIN users u ON ah.changed_by = u.id
    WHERE ah.application_id = ?
    ORDER BY ah.created_at DESC
");
$stmt->execute([$applicationId]);
$history = $stmt->fetchAll();

// Get appointment if exists
$stmt = $pdo->prepare("
    SELECT * FROM appointments 
    WHERE application_id = ? 
    ORDER BY appointment_date DESC 
    LIMIT 1
");
$stmt->execute([$applicationId]);
$appointment = $stmt->fetch();

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
                                <h1 class="h3 mb-2">Application Details</h1>
                                <p class="text-muted mb-0">
                                    Application #<?php echo htmlspecialchars($application['application_number']); ?>
                                </p>
                            </div>
                            <a href="<?php echo $_SESSION['role'] === 'resident' ? 'my-applications.php' : 'applications.php'; ?>" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Applications
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

        <div class="row">
            <!-- Application Details -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-text me-2"></i>Application Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php if ($_SESSION['role'] !== 'resident'): ?>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Applicant</h6>
                                <p class="mb-0">
                                    <?php echo htmlspecialchars($application['applicant_name']); ?>
                                    <?php if ($application['contact_number']): ?>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($application['contact_number']); ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($application['purok_name']): ?>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($application['purok_name']); ?>
                                        </small>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php endif; ?>

                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Document Type</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($application['type_name']); ?></p>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Status</h6>
                                <span class="badge status-<?php echo $application['status']; ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Purpose</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($application['purpose'])); ?></p>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Urgency</h6>
                                <span class="badge bg-<?php echo $application['urgency'] === 'Rush' ? 'danger' : 'info'; ?>">
                                    <?php echo $application['urgency']; ?>
                                </span>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Processing Time</h6>
                                <p class="mb-0">3 to 5 working days<br><small class="text-muted">(except holidays)</small></p>
                                <?php if ($application['payment_status'] === 'paid' && $application['payment_date']): ?>
                                    <small class="text-success d-block mt-1">
                                        <i class="bi bi-clock-history me-1"></i>Started: <?php echo date('M j, Y', strtotime($application['payment_date'])); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Date Applied</h6>
                                <p class="mb-0"><?php echo date('F j, Y g:i A', strtotime($application['created_at'])); ?></p>
                            </div>
                            
                            <?php if ($application['processed_by']): ?>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Processed By</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($application['processed_by_name']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($application['admin_remarks']): ?>
                            <div class="col-12">
                                <h6 class="text-muted mb-1">Remarks</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($application['admin_remarks'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-credit-card me-2"></i>Payment Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Payment Status</h6>
                                <span class="badge bg-<?php 
                                    echo $application['payment_status'] === 'paid' ? 'success' : 
                                        ($application['payment_status'] === 'waived' ? 'info' : 'warning');
                                ?>">
                                    <?php echo ucfirst($application['payment_status']); ?>
                                </span>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Amount</h6>
                                <p class="mb-0">₱<?php echo number_format($application['fee'], 2); ?></p>
                            </div>
                            
                            <?php if ($application['payment_date']): ?>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Payment Date</h6>
                                <p class="mb-0"><?php echo date('F j, Y g:i A', strtotime($application['payment_date'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($application['payment_reference']): ?>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Reference Number</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($application['payment_reference']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($application['payment_status'] === 'unpaid'): ?>
                            <div class="col-12">
                                <a href="pay-application.php?id=<?php echo $application['id']; ?>" 
                                   class="btn btn-success">
                                    <i class="bi bi-credit-card me-2"></i>Pay Now
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Supporting Documents -->
                <?php if ($application['supporting_documents']): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark me-2"></i>Supporting Documents
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php 
                            $documents = json_decode($application['supporting_documents'], true);
                            foreach ($documents as $doc): 
                            ?>
                            <div class="col-md-4">
                                <div class="card">
                                    <img src="uploads/documents/<?php echo htmlspecialchars($doc); ?>" 
                                         class="card-img-top" alt="Document">
                                    <div class="card-body">
                                        <a href="uploads/documents/<?php echo htmlspecialchars($doc); ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-eye me-2"></i>View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Application Status -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Application Status
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="application-timeline">
                            <!-- Submitted -->
                            <div class="timeline-item <?php echo $application['status'] !== '' ? 'completed' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="bi bi-file-earmark-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Application Submitted</h6>
                                    <p class="text-muted mb-0">
                                        <?php echo date('M j, Y g:i A', strtotime($application['created_at'])); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Payment -->
                            <div class="timeline-item <?php echo $application['payment_status'] === 'paid' ? 'completed' : ($application['payment_status'] === 'unpaid' ? 'current' : ''); ?>">
                                <div class="timeline-icon">
                                    <i class="bi bi-credit-card"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Payment</h6>
                                    <?php if ($application['payment_status'] === 'paid'): ?>
                                        <p class="text-success mb-0">
                                            <i class="bi bi-check-circle me-1"></i>Paid on 
                                            <?php echo date('M j, Y g:i A', strtotime($application['payment_date'])); ?>
                                        </p>
                                    <?php elseif ($application['payment_status'] === 'waived'): ?>
                                        <p class="text-info mb-0">
                                            <i class="bi bi-info-circle me-1"></i>Fee Waived
                                        </p>
                                    <?php else: ?>
                                        <p class="text-warning mb-0">
                                            <i class="bi bi-exclamation-circle me-1"></i>Payment Pending
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Processing -->
                            <div class="timeline-item <?php echo $application['status'] === 'processing' ? 'current' : ($application['status'] === 'completed' || $application['status'] === 'ready_for_pickup' ? 'completed' : ''); ?>">
                                <div class="timeline-icon">
                                    <i class="bi bi-gear"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Processing</h6>
                                    <?php if ($application['status'] === 'processing'): ?>
                                        <p class="text-primary mb-0">
                                            <i class="bi bi-arrow-repeat me-1"></i>Document is being processed
                                        </p>
                                        <?php if ($application['payment_date']): ?>
                                            <small class="text-muted d-block">
                                                Processing time: 3 to 5 working days (except holidays)<br>
                                                Started: <?php echo date('M j, Y', strtotime($application['payment_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php elseif ($application['status'] === 'completed' || $application['status'] === 'ready_for_pickup'): ?>
                                        <p class="text-success mb-0">
                                            <i class="bi bi-check-circle me-1"></i>Processing completed
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Waiting for processing<br>
                                        <small>Will start after payment is confirmed</small></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Ready for Pickup -->
                            <div class="timeline-item <?php echo $application['status'] === 'ready_for_pickup' ? 'current' : ($application['status'] === 'completed' ? 'completed' : ''); ?>">
                                <div class="timeline-icon">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Ready for Pickup</h6>
                                    <?php if ($application['status'] === 'ready_for_pickup'): ?>
                                        <?php if ($appointment): ?>
                                            <p class="text-primary mb-0">
                                                <i class="bi bi-calendar-check me-1"></i>Scheduled for 
                                                <?php echo date('M j, Y g:i A', strtotime($appointment['appointment_date'])); ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="text-warning mb-0">
                                                <i class="bi bi-calendar-plus me-1"></i>Schedule pickup
                                            </p>
                                        <?php endif; ?>
                                    <?php elseif ($application['status'] === 'completed'): ?>
                                        <p class="text-success mb-0">
                                            <i class="bi bi-check-circle me-1"></i>Document released
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Not yet ready</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Completed -->
                            <div class="timeline-item <?php echo $application['status'] === 'completed' ? 'completed' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Completed</h6>
                                    <?php if ($application['status'] === 'completed'): ?>
                                        <p class="text-success mb-0">
                                            <i class="bi bi-check-circle me-1"></i>Document released on 
                                            <?php 
                                            $completedHistory = array_filter($history, function($item) {
                                                return $item['status'] === 'completed';
                                            });
                                            $completedItem = reset($completedHistory);
                                            echo $completedItem ? date('M j, Y g:i A', strtotime($completedItem['created_at'])) : 'N/A';
                                            ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">Not yet completed</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pickup Schedule -->
                <?php if ($application['status'] === 'ready_for_pickup'): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-check me-2"></i>Pickup Schedule
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($appointment): ?>
                            <div class="text-center">
                                <i class="bi bi-calendar-date text-primary" style="font-size: 2rem;"></i>
                                <h5 class="mt-3"><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></h5>
                                <p class="mb-0"><?php echo date('g:i A', strtotime($appointment['appointment_date'])); ?></p>
                                <?php if ($appointment['notes']): ?>
                                    <p class="text-muted mt-2"><?php echo htmlspecialchars($appointment['notes']); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($appointment['status'] === 'scheduled'): ?>
                                    <div class="mt-3">
                                        <a href="reschedule-pickup.php?id=<?php echo $application['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-calendar me-2"></i>Reschedule
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <i class="bi bi-calendar-plus text-muted" style="font-size: 2rem;"></i>
                                <h5 class="mt-3">No Schedule Yet</h5>
                                <p class="text-muted">Schedule your document pickup</p>
                                <a href="schedule-pickup.php?id=<?php echo $application['id']; ?>" 
                                   class="btn btn-primary">
                                    <i class="bi bi-calendar-plus me-2"></i>Schedule Pickup
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Application Timeline Styles */
.application-timeline {
    position: relative;
    padding: 0;
}

.timeline-item {
    position: relative;
    padding-left: 3rem;
    padding-bottom: 2rem;
    opacity: 0.5;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-item.current {
    opacity: 1;
}

.timeline-item.completed {
    opacity: 1;
}

.timeline-icon {
    position: absolute;
    left: 0;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
    z-index: 1;
}

.timeline-item.current .timeline-icon {
    background-color: var(--primary-color);
    color: white;
    box-shadow: 0 0 0 2px var(--primary-color);
}

.timeline-item.completed .timeline-icon {
    background-color: #198754;
    color: white;
    box-shadow: 0 0 0 2px #198754;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 0.9rem;
    top: 2rem;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item.completed:not(:last-child)::after {
    background-color: #198754;
}

.timeline-content {
    padding-left: 1rem;
}

.timeline-content h6 {
    margin-bottom: 0.5rem;
    color: #344767;
}

.timeline-item.current .timeline-content h6 {
    color: var(--primary-color);
}

.timeline-item.completed .timeline-content h6 {
    color: #198754;
}
</style>

<?php include 'scripts.php'; ?> 