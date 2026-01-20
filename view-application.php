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
            u.birthdate as applicant_birthdate, u.gender as applicant_gender, u.civil_status as applicant_civil_status, u.address as applicant_address,
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
                            
                            <?php if (!empty($application['payment_receipt'])): ?>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Payment Receipt</h6>
                                <button type="button" class="btn btn-sm btn-outline-info" 
                                        onclick="viewReceipt('<?php echo htmlspecialchars($application['payment_receipt']); ?>')">
                                    <i class="bi bi-image"></i> View Receipt
                                </button>
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
                <div id="printableContent" class="docu-page">
                    <?php
                        $seriesYear = date('Y');
                        $issuedLong = date('jS \d\a\y \o\f F Y');
                        $issuedShort = date('F j, Y');
                        $issuedDay = date('jS');
                        $issuedMonthYear = date('F Y');
                        $captainName = strtoupper(htmlspecialchars($application['processed_by_name'] ?? 'HON. MOHAMAD S. ABDULKASAN'));

                        $civil = trim((string)($application['applicant_civil_status'] ?? ''));
                        $civilText = $civil !== '' ? strtolower($civil) : '';
                        $purokText = trim((string)($application['purok_name'] ?? ''));
                        $residentOf = $purokText !== '' ? ('of ' . htmlspecialchars($purokText)) : '';

                        $applicantUpper = strtoupper(htmlspecialchars($application['applicant_name']));
                    ?>

                    <!-- Header (match docu-format.jpg) -->
                    <div class="docu-header">
                        <div class="docu-header-row">
                            <div class="docu-logo">
                                <img src="assets/images/logo-512x512.png" alt="Barangay Malangit Seal">
                            </div>
                            <div class="docu-header-text">
                                <div class="docu-rp">Republic of the Philippines</div>
                                <div class="docu-region">BANGSAMORO AUTONOMOUS REGION IN MUSLIM MINDANAO</div>
                                <div class="docu-province">Province of Maguindanao Del Sur</div>
                                <div class="docu-muni">Municipality of Pandag</div>
                                <div class="docu-brgy">Barangay Government of Malangit</div>
                                <div class="docu-email">Email address: pandagmalangit@gmail.com / 09569610560</div>
                            </div>
                            <div class="docu-logo docu-logo-right">
                                <img src="panadg.jpg" alt="Municipality Seal">
                            </div>
                        </div>

                        <div class="docu-office">OFFICE OF THE PUNONG BARANGAY</div>
                        <div class="docu-title-row">
                            <div class="docu-title">BARANGAY CLEARANCE</div>
                            <div class="docu-series">Series of <?php echo htmlspecialchars($seriesYear); ?></div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="docu-body">
                        <!-- Left officials panel -->
                        <div class="docu-left">
                            <div class="docu-officials-head">BRGY. OFFICIALS</div>

                            <div class="docu-officials-captain">
                                <div class="docu-official">HON. MOHAMAD S. ABDULKASAN</div>
                                <div class="docu-officials-captain-role">Barangay Captain</div>
                            </div>

                            <div class="docu-officials-list">
                                <div class="docu-officials-section">KAGAWAD</div>
                                <div class="docu-official">HON. SURATO B. SABANG</div>
                                <div class="docu-official">HON. MANSOR B. ABDULKARIM</div>
                                <div class="docu-official">HON. NORMAN S. ABDULKASAN</div>
                                <div class="docu-official">HON. SAMSUDIN U. ALAMANSA</div>
                                <div class="docu-official">HON. ZAHABUDIN L. ABDULRADZAK</div>
                                <div class="docu-official">HON. ROHOLLAH K. USOP</div>
                                <div class="docu-official">HON. RAMSAN A. SALENDAB</div>

                                <div class="docu-officials-bottom">
                                    <div class="docu-official docu-official-bottom">
                                        ALMAIRA A. BUALAN<br><span>BRGY. Secretary</span>
                                    </div>
                                    <div class="docu-official docu-official-bottom">
                                        MOHAMAD P. GUIAPAL<br><span>BRGY. Treasurer</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right content -->
                        <div class="docu-right">
                            <p class="docu-paragraph">
                                This is to certify that according to records available in this Barangay that
                                <strong><?php echo $applicantUpper; ?></strong>
                                of legal age<?php echo $civilText !== '' ? (', ' . htmlspecialchars($civilText) . ',') : ','; ?>
                                is a Bonafide resident <?php echo $residentOf !== '' ? $residentOf : ''; ?>
                                <?php if ($residentOf !== ''): ?>,<?php endif; ?>
                                Barangay Malangit, Pandag Maguindanao del Sur.
                            </p>

                            <p class="docu-paragraph">
                                This clearance is being issued upon the request of the above-named person as per requesting for whatever legal
                                purposes it may serve him/her best.
                            </p>

                            <p class="docu-paragraph">
                                Issued this <strong><?php echo htmlspecialchars($issuedDay); ?></strong> day of
                                <strong><?php echo htmlspecialchars($issuedMonthYear); ?></strong> at the office of the Punong Barangay,
                                Barangay Malangit, Pandag Maguindanao del Sur, Philippines.
                            </p>

                            <div class="docu-signatures">
                                <div class="docu-signature-right">
                                    <div class="docu-sig-line"></div>
                                    <div class="docu-sig-name"><strong><?php echo $captainName; ?></strong></div>
                                    <div class="docu-sig-role">Punong Barangay</div>
                                </div>

                                <div class="docu-signature-left">
                                    <div class="docu-app-line"></div>
                                    <div class="docu-app-label">Applicant's signature</div>
                                </div>
                            </div>

                            <div class="docu-notes">
                                <div class="docu-note-label">Note:</div>
                                <div class="docu-note-red">Not valid without the official dry seal</div>
                                <div class="docu-note-gray">Valid until Dec. <?php echo htmlspecialchars($seriesYear); ?></div>
                            </div>
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
/* =========================================================
   Print Document - Barangay Clearance (docu-format.jpg)
   ========================================================= */
.docu-page{
    padding: 18px 18px;
    font-family: "Times New Roman", serif;
    font-size: 12px;
    line-height: 1.35;
    color: #000;
    background: #fff;
    min-height: 100vh;
}
.docu-header{
    border: 2px solid #1f3f66;
    border-bottom: none;
}
.docu-header-row{
    background: linear-gradient(180deg, #2f5f91 0%, #2a5786 55%, #234a73 100%);
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.docu-logo{
    width: 86px;
    flex: 0 0 86px;
    display: flex;
    justify-content: center;
    align-items: center;
}
.docu-logo img{
    width: 72px;
    height: 72px;
    object-fit: contain;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
}
.docu-logo-right{
    justify-content: flex-end;
}
.docu-header-text{
    flex: 1 1 auto;
    text-align: center;
    color: #fff;
}
.docu-rp{ font-size: 12px; font-weight: 700; }
.docu-region{ font-size: 12px; font-weight: 700; text-transform: uppercase; }
.docu-province{ font-size: 12px; margin-top: 1px; }
.docu-muni{ font-size: 13px; font-weight: 700; margin-top: 4px; }
.docu-brgy{ font-size: 15px; font-weight: 700; margin-top: 2px; }
.docu-email{ font-size: 11px; margin-top: 3px; }
.docu-office{
    background: #2e6aa1;
    color: #fff;
    text-align: center;
    font-weight: 700;
    padding: 6px 10px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.docu-title-row{
    border-left: 2px solid #1f3f66;
    border-right: 2px solid #1f3f66;
    border-bottom: 2px solid #1f3f66;
    padding: 10px 12px 12px 12px;
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}
.docu-title{
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 5px;
    text-decoration: underline;
    text-underline-offset: 4px;
}
.docu-series{
    position: absolute;
    right: 12px;
    bottom: 12px;
    color: #b11a1a;
    font-weight: 700;
    font-size: 12px;
}
.docu-body{
    border: 2px solid #1f3f66;
    border-top: none;
    padding: 14px 12px 14px 12px;
    display: grid;
    grid-template-columns: 230px 1fr;
    gap: 16px;
    min-height: 660px;
}
.docu-left{
    background: #5d7fa0;
    color: #0b1a28;
    padding: 0;
}
.docu-officials-head{
    background: #caa64f;
    color: #000;
    font-weight: 800;
    text-align: center;
    padding: 10px 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.docu-officials-captain{
    background: #caa64f;
    margin: 0 10px 10px 10px;
    padding: 10px 10px;
    border-radius: 2px;
}
.docu-officials-captain-name{ font-weight: 800; text-transform: uppercase; font-size: 12px; }
.docu-officials-captain-role{ font-size: 11px; margin-top: 2px; font-weight: 700; }
.docu-officials-list{
    margin: 0 10px 12px 10px;
    background: rgba(255,255,255,0.15);
    padding: 10px 10px 12px 10px;
}
.docu-officials-section{
    text-align: center;
    font-weight: 800;
    color: #e8f0f8;
    margin-bottom: 6px;
    letter-spacing: 1px;
}
.docu-official{
    font-size: 11px;
    padding: 6px 0;
    border-top: 1px solid rgba(255,255,255,0.25);
    color: #0b1a28;
    font-weight: 700;
}
.docu-official:first-of-type{
    border-top: none;
}
.docu-officials-bottom{
    margin-top: 10px;
}
.docu-official-bottom{
    font-size: 11px;
    font-weight: 800;
}
.docu-official-bottom span{
    font-weight: 700;
    font-size: 10px;
}
.docu-right{
    padding-top: 2px;
    font-size: 14px;
}
.docu-paragraph{
    margin: 0 0 18px 0;
    text-align: left;
}
.docu-signatures{
    margin-top: 26px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: end;
    gap: 18px;
}
.docu-signature-right{
    text-align: center;
}
.docu-sig-line{
    height: 34px;
    border-bottom: 2px solid #000;
    margin: 0 24px 6px 24px;
}
.docu-sig-name{
    text-transform: uppercase;
}
.docu-sig-role{
    margin-top: 2px;
}
.docu-signature-left{
    text-align: left;
    padding-left: 10px;
}
.docu-app-line{
    height: 34px;
    border-bottom: 2px solid #000;
    width: 220px;
    margin-bottom: 6px;
}
.docu-app-label{
    font-size: 12px;
}
.docu-notes{
    margin-top: 46px;
    font-size: 12px;
}
.docu-note-label{
    font-weight: 700;
    margin-bottom: 8px;
}
.docu-note-red{
    color: #b11a1a;
    font-weight: 800;
    margin-bottom: 10px;
}
.docu-note-gray{
    color: #333;
    font-weight: 700;
}

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
        padding: 0 !important;
        background: white !important;
        font-size: 12px !important;
        line-height: 1.35 !important;
        color: black !important;
        page-break-inside: avoid !important;
    }

    /* Keep our document colors */
    .docu-header-row,
    .docu-office,
    .docu-left,
    .docu-officials-head,
    .docu-officials-captain {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
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