<?php
require_once 'config.php';

// Require login and must be admin or purok leader
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'purok_leader'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Document Applications';
$currentUser = getCurrentUser();

// Get applications based on role
$params = [];
$whereClause = '';

if ($_SESSION['role'] === 'purok_leader') {
    $whereClause = 'WHERE u.purok_id = ?';
    $params[] = $currentUser['purok_id'];
}

$stmt = $pdo->prepare("
    SELECT a.*, dt.type_name, dt.processing_days,
           u.first_name, u.last_name, u.contact_number,
           p.purok_name,
           CONCAT(pb.first_name, ' ', pb.last_name) as processed_by_name,
           apt.id as payment_appointment_id,
           apt.appointment_date as payment_appointment_date,
           apt.status as payment_appointment_status
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN document_types dt ON a.document_type_id = dt.id
    LEFT JOIN puroks p ON u.purok_id = p.id
    LEFT JOIN users pb ON a.processed_by = pb.id
    LEFT JOIN appointments apt ON a.id = apt.application_id AND apt.appointment_type = 'payment'
    $whereClause
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
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
                                <h1 class="h3 mb-2">Document Applications</h1>
                                <p class="text-muted mb-0">
                                    <?php echo $_SESSION['role'] === 'admin' ? 
                                        'Manage and process all document applications' : 
                                        'View applications from residents in your purok'; ?>
                                </p>
                            </div>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="export-applications.php" class="btn btn-success">
                                    <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Filters -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <a href="?status=pending" class="card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Pending</h6>
                                <h3 class="mb-0 text-warning">
                                    <?php 
                                    echo count(array_filter($applications, function($a) {
                                        return $a['status'] === 'pending';
                                    }));
                                    ?>
                                </h3>
                            </div>
                            <div class="text-warning">
                                <i class="bi bi-clock" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6">
                <a href="?status=processing" class="card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Processing</h6>
                                <h3 class="mb-0 text-info">
                                    <?php 
                                    echo count(array_filter($applications, function($a) {
                                        return $a['status'] === 'processing';
                                    }));
                                    ?>
                                </h3>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-gear" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6">
                <a href="?status=ready_for_pickup" class="card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Ready for Pickup</h6>
                                <h3 class="mb-0 text-primary">
                                    <?php 
                                    echo count(array_filter($applications, function($a) {
                                        return $a['status'] === 'ready_for_pickup';
                                    }));
                                    ?>
                                </h3>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6">
                <a href="?status=completed" class="card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Completed</h6>
                                <h3 class="mb-0 text-success">
                                    <?php 
                                    echo count(array_filter($applications, function($a) {
                                        return $a['status'] === 'completed';
                                    }));
                                    ?>
                                </h3>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-trophy" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

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
                        <div class="table-responsive">
                            <table class="table table-hover" id="applicationsTable">
                                <thead>
                                    <tr>
                                        <th>Application #</th>
                                        <th>Applicant</th>
                                        <th>Document</th>
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
                                                <?php if ($app['urgency'] === 'Rush'): ?>
                                                    <span class="badge bg-danger ms-1">Rush</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>
                                                        <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                                    </strong>
                                                    <?php if ($app['contact_number']): ?>
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-telephone me-1"></i>
                                                            <?php echo htmlspecialchars($app['contact_number']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($app['type_name']); ?>
                                                <small class="text-muted d-block">
                                                    <?php 
                                                    $purpose = htmlspecialchars($app['purpose']);
                                                    echo strlen($purpose) > 30 ? substr($purpose, 0, 27) . '...' : $purpose;
                                                    ?>
                                                </small>
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
                                                <?php if ($app['payment_amount']): ?>
                                                    <small class="d-block">₱<?php echo number_format($app['payment_amount'], 2); ?></small>
                                                <?php endif; ?>
                                                <?php if ($app['payment_appointment_id'] && $app['payment_appointment_status'] === 'scheduled'): ?>
                                                    <small class="d-block text-info">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        Payment appt: <?php echo date('M j, g:i A', strtotime($app['payment_appointment_date'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y g:i A', strtotime($app['created_at'])); ?>
                                                <?php if ($app['processed_by_name']): ?>
                                                    <small class="text-muted d-block">
                                                        By: <?php echo htmlspecialchars($app['processed_by_name']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view-application.php?id=<?php echo $app['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                                        <?php if ($app['status'] === 'pending'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="processApplication(<?php echo $app['id']; ?>)"
                                                                    title="Process">
                                                                <i class="bi bi-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($app['status'] === 'processing'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-info"
                                                                    onclick="openReadyAppointmentModal(<?php echo $app['id']; ?>)"
                                                                    title="Mark as Ready & Schedule Appointment">
                                                                <i class="bi bi-box-seam"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($app['status'] === 'ready_for_pickup'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="completeApplication(<?php echo $app['id']; ?>)"
                                                                    title="Complete">
                                                                <i class="bi bi-check-all"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($app['status'] === 'pending' && $app['payment_status'] === 'unpaid'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-warning"
                                                                    onclick="waivePayment(<?php echo $app['id']; ?>)"
                                                                    title="Waive Payment">
                                                                <i class="bi bi-cash-stack"></i>
                                                            </button>
                                                            <?php if (!$app['payment_appointment_id'] || $app['payment_appointment_status'] !== 'scheduled'): ?>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-info"
                                                                        onclick="schedulePaymentAppointment(<?php echo $app['id']; ?>)"
                                                                        title="Schedule Payment Appointment">
                                                                    <i class="bi bi-calendar-plus"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <?php 
                                                                // Check if today is the payment appointment date
                                                                $appointmentDate = new DateTime($app['payment_appointment_date']);
                                                                $today = new DateTime();
                                                                $isAppointmentToday = $appointmentDate->format('Y-m-d') === $today->format('Y-m-d');
                                                                ?>
                                                                <?php if ($isAppointmentToday): ?>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-success"
                                                                            onclick="allowPayment(<?php echo $app['id']; ?>)"
                                                                            title="Allow Payment - Appointment is today">
                                                                        <i class="bi bi-check-circle"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-secondary"
                                                                            disabled
                                                                            title="Payment appointment scheduled for <?php echo date('M j, Y g:i A', strtotime($app['payment_appointment_date'])); ?>. Payment can only be allowed on the appointment date.">
                                                                        <i class="bi bi-calendar-x"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <?php if ($app['status'] === 'pending' && $app['payment_status'] === 'paid'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="autoProcessApplication(<?php echo $app['id']; ?>)"
                                                                    title="Start Processing (Payment Received)">
                                                                <i class="bi bi-play-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process Application Modal -->
<div class="modal fade" id="processModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process-application.php" method="POST">
                <input type="hidden" name="application_id" id="processApplicationId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" 
                                placeholder="Enter processing remarks or instructions"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Start Processing</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ready for Pickup & Appointment Modal -->
<div class="modal fade" id="readyAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark as Ready & Schedule Pickup Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="mark-ready.php" method="POST">
                <input type="hidden" name="application_id" id="readyAppointmentApplicationId">
                <input type="hidden" name="appointment_type" value="pickup">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="appointment_date" class="form-control" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes / Pickup Instructions</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Enter notes or pickup instructions"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Mark as Ready & Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Appointment Modal -->
<div class="modal fade" id="paymentAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Payment Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="schedule-payment-appointment.php" method="POST">
                <input type="hidden" name="application_id" id="paymentAppointmentApplicationId">
                <input type="hidden" name="appointment_type" value="payment">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="appointment_date" class="form-control" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Instructions</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Enter payment instructions or requirements"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Schedule Payment Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    $('#applicationsTable').DataTable({
        order: [[5, 'desc']], // Sort by date applied
        pageLength: 25,
        responsive: true,
        language: {
            search: '<i class="bi bi-search"></i>',
            searchPlaceholder: 'Search applications...'
        }
    });
});

function processApplication(id) {
    document.getElementById('processApplicationId').value = id;
    new bootstrap.Modal(document.getElementById('processModal')).show();
}

function completeApplication(id) {
    if (confirm('Are you sure you want to mark this application as completed?')) {
        window.location.href = `complete-application.php?id=${id}`;
    }
}

function waivePayment(id) {
    if (confirm('Are you sure you want to waive the payment for this application?')) {
        window.location.href = `waive-payment.php?id=${id}`;
    }
}

// Auto-process applications when payment is made
function autoProcessApplication(id) {
    if (confirm('Payment received. Start processing this application?')) {
        // Submit the process form automatically
        document.getElementById('processApplicationId').value = id;
        document.querySelector('#processModal form').submit();
    }
}

function openReadyAppointmentModal(appId) {
    document.getElementById('readyAppointmentApplicationId').value = appId;
    new bootstrap.Modal(document.getElementById('readyAppointmentModal')).show();
}

function schedulePaymentAppointment(appId) {
    document.getElementById('paymentAppointmentApplicationId').value = appId;
    new bootstrap.Modal(document.getElementById('paymentAppointmentModal')).show();
}

function allowPayment(appId) {
    if (confirm('Are you sure you want to allow payment for this application? The resident will be able to make payment through the system.')) {
        window.location.href = `allow-payment.php?id=${appId}`;
    }
}
</script>

<?php include 'scripts.php'; ?> 