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
           u.contact_number, u.email, p.purok_name,
           apt.id as payment_appointment_id,
           apt.appointment_date as payment_appointment_date,
           apt.status as payment_appointment_status
    FROM applications a
    JOIN document_types dt ON a.document_type_id = dt.id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN users pb ON a.processed_by = pb.id
    LEFT JOIN puroks p ON u.purok_id = p.id
    LEFT JOIN appointments apt ON a.id = apt.application_id AND apt.appointment_type = 'payment'
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
                            <div>
                                <?php if ($application['status'] === 'completed'): ?>
                                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#printModal">
                                    <i class="bi bi-printer me-2"></i>Print Document
                                </button>
                                <?php endif; ?>
                                <a href="<?php echo $_SESSION['role'] === 'resident' ? 'my-applications.php' : 'applications.php'; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Applications
                                </a>
                            </div>
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
                            
                            <?php if ($application['payment_method']): ?>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Payment Method</h6>
                                <p class="mb-0"><?php echo ucfirst(htmlspecialchars($application['payment_method'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Payment Appointment Information -->
                            <?php if ($application['payment_appointment_id']): ?>
                            <div class="col-12">
                                <hr class="my-3">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-calendar-check me-2"></i>Payment Appointment
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-1">Appointment Date</h6>
                                        <p class="mb-0">
                                            <?php echo date('F j, Y g:i A', strtotime($application['payment_appointment_date'])); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-1">Appointment Status</h6>
                                        <span class="badge bg-<?php 
                                            echo $application['payment_appointment_status'] === 'payment_allowed' ? 'success' : 
                                                ($application['payment_appointment_status'] === 'scheduled' ? 'warning' : 'secondary');
                                        ?>">
                                            <?php 
                                            switch($application['payment_appointment_status']) {
                                                case 'payment_allowed':
                                                    echo 'Payment Allowed';
                                                    break;
                                                case 'scheduled':
                                                    echo 'Waiting for Approval';
                                                    break;
                                                case 'completed':
                                                    echo 'Completed';
                                                    break;
                                                default:
                                                    echo ucfirst($application['payment_appointment_status']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($application['payment_status'] === 'unpaid'): ?>
                            <div class="col-12">
                                <hr class="my-3">
                                <?php 
                                // Allow payment only if:
                                // 1. Payment appointment is scheduled AND
                                // 2. Appointment is allowed by admin (status = 'payment_allowed')
                                $canPay = $application['payment_appointment_id'] && $application['payment_appointment_status'] === 'payment_allowed';
                                $hasScheduledAppointment = $application['payment_appointment_id'] && $application['payment_appointment_status'] === 'scheduled';
                                ?>
                                
                                <?php if ($canPay): ?>
                                    <a href="pay-application.php?id=<?php echo $application['id']; ?>" 
                                       class="btn btn-success">
                                        <i class="bi bi-credit-card me-2"></i>Pay Now
                                    </a>
                                    <p class="text-success mt-2 mb-0">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Payment has been approved and is now available for processing.
                                    </p>
                                    
                                <?php elseif ($hasScheduledAppointment): ?>
                                    <button type="button" 
                                            class="btn btn-secondary" 
                                            disabled
                                            title="Payment is scheduled for <?php echo date('M j, Y g:i A', strtotime($application['payment_appointment_date'])); ?>. Waiting for admin approval.">
                                        <i class="bi bi-lock me-2"></i>Payment Pending Approval
                                    </button>
                                    
                                    <div class="alert alert-info mt-3 mb-0">
                                        <h6 class="alert-heading">
                                            <i class="bi bi-info-circle me-2"></i>Payment Appointment Scheduled
                                        </h6>
                                        <p class="mb-2">
                                            Your payment appointment is scheduled for <strong><?php echo date('F j, Y \a\t g:i A', strtotime($application['payment_appointment_date'])); ?></strong>.
                                        </p>
                                        <p class="mb-0">
                                            <small class="text-muted">
                                                The "Pay Now" button will be enabled once the administrator approves your payment after the scheduled appointment time.
                                            </small>
                                        </p>
                                    </div>
                                    
                                <?php else: ?>
                                    <button type="button" 
                                            class="btn btn-secondary" 
                                            disabled
                                            title="Payment appointment not scheduled yet. Please wait for admin to schedule your payment appointment.">
                                        <i class="bi bi-lock me-2"></i>Payment Not Available
                                    </button>
                                    
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <h6 class="alert-heading">
                                            <i class="bi bi-exclamation-triangle me-2"></i>Payment Appointment Required
                                        </h6>
                                        <p class="mb-2">
                                            A payment appointment must be scheduled before you can make payment.
                                        </p>
                                        <p class="mb-0">
                                            <small class="text-muted">
                                                Please wait for the administrator to schedule your payment appointment. You will be notified once it's available.
                                            </small>
                                        </p>
                                    </div>
                                <?php endif; ?>
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
                                        <?php if ($application['payment_method']): ?>
                                            <small class="text-muted d-block">
                                                via <?php echo ucfirst($application['payment_method']); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php elseif ($application['payment_status'] === 'waived'): ?>
                                        <p class="text-info mb-0">
                                            <i class="bi bi-info-circle me-1"></i>Fee Waived
                                        </p>
                                    <?php else: ?>
                                        <?php if ($application['payment_appointment_id']): ?>
                                            <?php if ($application['payment_appointment_status'] === 'payment_allowed'): ?>
                                                <p class="text-success mb-0">
                                                    <i class="bi bi-check-circle me-1"></i>Payment Approved
                                                </p>
                                                <small class="text-muted d-block">Ready to pay online</small>
                                            <?php elseif ($application['payment_appointment_status'] === 'scheduled'): ?>
                                                <p class="text-warning mb-0">
                                                    <i class="bi bi-calendar-clock me-1"></i>Appointment Scheduled
                                                </p>
                                                <small class="text-muted d-block">
                                                    <?php echo date('M j, Y g:i A', strtotime($application['payment_appointment_date'])); ?>
                                                </small>
                                            <?php else: ?>
                                                <p class="text-info mb-0">
                                                    <i class="bi bi-hourglass-split me-1"></i>Appointment Processing
                                                </p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-warning mb-0">
                                                <i class="bi bi-exclamation-circle me-1"></i>Payment Pending
                                            </p>
                                            <small class="text-muted d-block">Ready to pay online</small>
                                        <?php endif; ?>
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

<?php if ($application['status'] === 'completed'): ?>
<!-- Print Modal -->
<div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printModalLabel">Print Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="printableContent" style="padding: 20px; font-family: 'Times New Roman', serif; font-size: 12px; line-height: 1.5; color: black; background: white; min-height: 100vh; display: flex; flex-direction: column;">
                    <!-- Print Content -->
                    <div style="text-align: center; margin-bottom: 25px;">
                        <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;">REPUBLIC OF THE PHILIPPINES</div>
                        <div style="font-size: 16px; font-weight: bold; margin-bottom: 4px;">BARANGAY MALANGIT</div>
                        <div style="font-size: 11px; margin-bottom: 4px;">Pandag, Maguindanao Del Sur</div>
                        <div style="font-size: 10px; font-style: italic;">OFFICE OF THE PUNONG BARANGAY</div>
                        <hr style="border: none; border-top: 2px solid black; margin: 12px auto; width: 80%;">
                    </div>
                    
                    <div style="text-align: center; margin-bottom: 25px;">
                        <div style="font-size: 14px; font-weight: bold; text-decoration: underline;"><?php echo strtoupper(htmlspecialchars($application['type_name'])); ?></div>
                    </div>
                    
                    <div style="margin-bottom: 25px; flex-grow: 1;">
                        <div style="font-weight: bold; margin-bottom: 15px; font-size: 13px;">TO WHOM IT MAY CONCERN:</div>
                        
                        <div style="margin-bottom: 15px; text-indent: 40px; text-align: justify; line-height: 1.6;">
                            This is to certify that <strong><?php echo strtoupper(htmlspecialchars($application['applicant_name'])); ?></strong><?php if ($application['purok_name']): ?>, a resident of <?php echo htmlspecialchars($application['purok_name']); ?><?php endif; ?>, Barangay Malangit, Pandag, Maguindanao Del Sur, Philippines, has requested this document for the following purpose:
                        </div>
                        
                        <div style="text-align: center; margin: 20px 0; font-weight: bold; font-style: italic; font-size: 13px; padding: 10px; border: 1px dashed black;">
                            "<?php echo strtoupper(htmlspecialchars($application['purpose'])); ?>"
                        </div>
                        
                        <div style="margin-bottom: 15px; text-indent: 40px; text-align: justify; line-height: 1.6;">
                            This certification is issued upon the request of the above-mentioned person for whatever legal purpose it may serve him/her best.
                        </div>
                        
                        <div style="margin-bottom: 15px; text-indent: 40px; line-height: 1.6;">
                            This document is valid and issued with the authority vested in me as Punong Barangay of this community.
                        </div>
                        
                        <div style="margin-bottom: 30px; text-indent: 40px; line-height: 1.6;">
                            Given this <?php echo date('jS \d\a\y \o\f F, Y'); ?> at Barangay Malangit, Pandag, Maguindanao Del Sur, Philippines.
                        </div>
                    </div>
                    
                    <!-- Signatures section with more space -->
                    <div style="margin-top: 40px;">
                        <table style="width: 100%; margin-bottom: 30px;">
                            <tr>
                                <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Conforme:</div>
                                    <div style="margin-top: 30px; margin-bottom: 5px;">
                                        <div style="border-bottom: 1px solid black; height: 25px; margin-bottom: 5px; text-align: center; line-height: 25px;">
                                            <?php echo strtoupper(htmlspecialchars($application['applicant_name'])); ?>
                                        </div>
                                    </div>
                                    <div style="font-size: 10px; text-align: center;">Applicant's Signature over Printed Name</div>
                                    <div style="font-size: 10px; text-align: center; margin-top: 5px;">Date: ______________</div>
                                </td>
                                <td style="width: 50%; vertical-align: top; text-align: center; padding-left: 15px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Certified Correct:</div>
                                    <div style="margin-top: 30px; margin-bottom: 5px;">
                                        <div style="border-bottom: 1px solid black; height: 25px; margin-bottom: 5px;">
                                            &nbsp;
                                        </div>
                                    </div>
                                    <div style="font-size: 10px; font-weight: bold;">HON. PUNONG BARANGAY</div>
                                    <div style="font-size: 10px; margin-top: 5px;">Date: ______________</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Document details section -->
                    <div style="border: 2px solid black; padding: 12px; margin-top: 20px;">
                        <div style="text-align: center; font-weight: bold; margin-bottom: 8px; font-size: 11px;">DOCUMENT INFORMATION</div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="font-size: 10px; padding: 2px;"><strong>Application Number:</strong></td>
                                <td style="font-size: 10px; padding: 2px;"><?php echo htmlspecialchars($application['application_number']); ?></td>
                                <td style="font-size: 10px; padding: 2px;"><strong>Date Issued:</strong></td>
                                <td style="font-size: 10px; padding: 2px;"><?php echo date('F j, Y'); ?></td>
                            </tr>
                            <tr>
                                <td style="font-size: 10px; padding: 2px;"><strong>Document Fee:</strong></td>
                                <td style="font-size: 10px; padding: 2px;">₱<?php echo number_format($application['fee'], 2); ?></td>
                                <td style="font-size: 10px; padding: 2px;"><strong>Payment Status:</strong></td>
                                <td style="font-size: 10px; padding: 2px;"><?php echo ucfirst($application['payment_status']); ?></td>
                            </tr>
                            <?php if ($application['payment_reference']): ?>
                            <tr>
                                <td style="font-size: 10px; padding: 2px;"><strong>Reference No:</strong></td>
                                <td style="font-size: 10px; padding: 2px;" colspan="3"><?php echo htmlspecialchars($application['payment_reference']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <!-- Footer -->
                    <div style="text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid black;">
                        <div style="font-size: 10px; font-style: italic; margin-bottom: 3px;">
                            "Serbisyong mabilis, tapat at mapagkakatiwalaan"
                        </div>
                        <div style="font-size: 9px; color: #666;">
                            This document is computer-generated and not valid without official seal and signature
                        </div>
                        <div style="font-size: 8px; margin-top: 5px;">
                            Generated on: <?php echo date('F j, Y g:i A'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printDocument()">Print</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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

/* Print Styles */
@media print {
    @page {
        size: A4;
        margin: 0.3in;
    }
    
    * {
        box-shadow: none !important;
        text-shadow: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    body {
        font-family: 'Times New Roman', serif !important;
        font-size: 12px !important;
        line-height: 1.4 !important;
        color: #000 !important;
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Hide everything first */
    body > * {
        display: none !important;
    }
    
    /* Show only the modal */
    #printModal {
        display: block !important;
        position: static !important;
        z-index: auto !important;
        background: none !important;
    }
    
    .modal-dialog {
        margin: 0 !important;
        max-width: none !important;
        width: auto !important;
    }
    
    .modal-content {
        border: none !important;
        box-shadow: none !important;
        background: white !important;
    }
    
    .modal-header,
    .modal-footer {
        display: none !important;
    }
    
    .modal-body {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    #printableContent {
        display: block !important;
        visibility: visible !important;
        position: static !important;
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 15px !important;
        background: white !important;
        font-size: 12px !important;
        line-height: 1.4 !important;
        color: black !important;
        page-break-inside: avoid !important;
    }
    
    /* Typography */
    h1, h2, h3, h4, h5, h6 {
        color: black !important;
        margin: 5px 0 !important;
        padding: 0 !important;
        page-break-after: avoid !important;
    }
    
    p {
        margin: 3px 0 !important;
        padding: 0 !important;
        orphans: 2 !important;
        widows: 2 !important;
    }
    
    /* Layout */
    .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin: 0 !important;
    }
    
    .col-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 0 5px !important;
    }
    
    .col-12 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
    }
    
    /* Borders and lines */
    hr {
        border: none !important;
        border-top: 2px solid black !important;
        margin: 5px 0 !important;
    }
    
    .border-bottom {
        border-bottom: 1px solid black !important;
        display: inline-block !important;
        min-width: 150px !important;
        min-height: 16px !important;
        padding-bottom: 2px !important;
    }
    
    .border {
        border: 1px solid black !important;
        padding: 5px !important;
    }
    
    /* Text alignment */
    .text-center {
        text-align: center !important;
    }
    
    .text-end {
        text-align: right !important;
    }
    
    /* Spacing */
    .mb-1 { margin-bottom: 2px !important; }
    .mb-2 { margin-bottom: 4px !important; }
    .mb-3 { margin-bottom: 6px !important; }
    .mb-4 { margin-bottom: 8px !important; }
    
    .mt-3 { margin-top: 6px !important; }
    .mt-4 { margin-top: 8px !important; }
    
    .small {
        font-size: 10px !important;
    }
    
    /* Font weights */
    strong, b {
        font-weight: bold !important;
        color: black !important;
    }
    
    em, i {
        font-style: italic !important;
    }
}
</style>

<script>
function printDocument() {
    // Ensure modal is visible for printing
    const modal = document.getElementById('printModal');
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
    }
    
    // Small delay to ensure content is rendered
    setTimeout(function() {
        window.print();
    }, 100);
}

// Handle after print to hide modal
window.addEventListener('afterprint', function() {
    // Optionally close modal after printing
    // const modal = bootstrap.Modal.getInstance(document.getElementById('printModal'));
    // if (modal) modal.hide();
});
</script>

<?php include 'scripts.php'; ?>

<!-- Support Chat Widget -->
<?php include 'includes/support-widget.php'; ?>
<script src="includes/support-chat-functions.js"></script> 