<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Appointments';
$currentUser = getCurrentUser();

// Get all appointments with related information
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.first_name, u.last_name, u.contact_number,
           app.application_number,
           dt.type_name as document_type
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    JOIN applications app ON a.application_id = app.id
    JOIN document_types dt ON app.document_type_id = dt.id
    ORDER BY a.appointment_date DESC
");
$stmt->execute();
$appointments = $stmt->fetchAll();

// Get applications eligible for appointment scheduling (pending, processing, ready_for_pickup, and not already scheduled)
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.first_name, u.last_name,
           dt.type_name as document_type
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN document_types dt ON a.document_type_id = dt.id
    WHERE a.status IN ('pending', 'processing', 'ready_for_pickup')
    AND NOT EXISTS (
        SELECT 1 FROM appointments apt 
        WHERE apt.application_id = a.id 
        AND apt.status IN ('scheduled', 'rescheduled')
    )
    ORDER BY a.updated_at DESC
");
$stmt->execute();
$readyApplications = $stmt->fetchAll();

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
                                <h1 class="h3 mb-2">Appointments</h1>
                                <p class="text-muted mb-0">Manage document pickup appointments</p>
                            </div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleAppointmentModal">
                                <i class="bi bi-calendar-plus me-2"></i>Schedule Appointment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Today's Appointments -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-check me-2"></i>Today's Appointments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $todayAppointments = array_filter($appointments, function($apt) {
                            return date('Y-m-d', strtotime($apt['appointment_date'])) === date('Y-m-d');
                        });
                        ?>
                        <?php if (empty($todayAppointments)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar2-check text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No Appointments Today</h5>
                                <p class="text-muted">There are no scheduled appointments for today.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Resident</th>
                                            <th>Document</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todayAppointments as $apt): ?>
                                            <tr>
                                                <td><?php echo date('g:i A', strtotime($apt['appointment_date'])); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?>
                                                    <?php if ($apt['contact_number']): ?>
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($apt['contact_number']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($apt['document_type']); ?>
                                                    <small class="text-muted d-block">
                                                        #<?php echo htmlspecialchars($apt['application_number']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $apt['status'] === 'completed' ? 'success' : 
                                                            ($apt['status'] === 'cancelled' ? 'danger' : 
                                                            ($apt['status'] === 'rescheduled' ? 'warning' : 'info'));
                                                    ?>">
                                                        <?php echo ucfirst($apt['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if ($apt['status'] === 'scheduled'): ?>
                                                            <?php if ($apt['appointment_type'] === 'payment'): ?>
                                                                <button type="button" class="btn btn-sm btn-success" 
                                                                        onclick="completePaymentAppointment(<?php echo $apt['id']; ?>)"
                                                                        title="Complete Payment">
                                                                    <i class="bi bi-cash-coin"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-sm btn-success" 
                                                                        onclick="completeAppointment(<?php echo $apt['id']; ?>)"
                                                                        title="Mark as Completed">
                                                                    <i class="bi bi-check-lg"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm btn-warning" 
                                                                    onclick="rescheduleAppointment(<?php echo $apt['id']; ?>)"
                                                                    title="Reschedule">
                                                                <i class="bi bi-calendar"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" 
                                                                    onclick="cancelAppointment(<?php echo $apt['id']; ?>)"
                                                                    title="Cancel">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
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

        <!-- Upcoming Appointments -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar3 me-2"></i>All Appointments
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="appointmentsTable">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Resident</th>
                                        <th>Document</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $apt): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?>
                                                <small class="text-muted d-block">
                                                    <?php echo date('g:i A', strtotime($apt['appointment_date'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?>
                                                <?php if ($apt['contact_number']): ?>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($apt['contact_number']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($apt['document_type']); ?>
                                                <small class="text-muted d-block">
                                                    #<?php echo htmlspecialchars($apt['application_number']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($apt['appointment_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $apt['status'] === 'completed' ? 'success' : 
                                                        ($apt['status'] === 'cancelled' ? 'danger' : 
                                                        ($apt['status'] === 'rescheduled' ? 'warning' : 'info'));
                                                ?>">
                                                    <?php echo ucfirst($apt['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($apt['status'] === 'scheduled'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" 
                                                                onclick="completeAppointment(<?php echo $apt['id']; ?>)"
                                                                title="Mark as Completed">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" 
                                                                onclick="rescheduleAppointment(<?php echo $apt['id']; ?>)"
                                                                title="Reschedule">
                                                            <i class="bi bi-calendar"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="cancelAppointment(<?php echo $apt['id']; ?>)"
                                                                title="Cancel">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
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

<!-- Schedule Appointment Modal -->
<div class="modal fade" id="scheduleAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="schedule-appointment.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Application <span class="text-danger">*</span></label>
                        <select name="application_id" class="form-select" required>
                            <option value="">Select application...</option>
                            <?php foreach ($readyApplications as $app): ?>
                                <option value="<?php echo $app['id']; ?>">
                                    <?php 
                                    echo htmlspecialchars($app['document_type'] . ' - ' . 
                                        $app['first_name'] . ' ' . $app['last_name'] . 
                                        ' (#' . $app['application_number'] . ')'); 
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Appointment Type <span class="text-danger">*</span></label>
                        <select name="appointment_type" class="form-select" required>
                            <option value="pickup">Document Pickup</option>
                            <option value="verification">Document Verification</option>
                            <option value="interview">Interview</option>
                            <option value="payment">Payment</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="appointment_date" class="form-control" required
                               min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                placeholder="Enter any additional notes or instructions"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reschedule Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="reschedule-appointment.php" method="POST">
                <input type="hidden" name="appointment_id" id="rescheduleAppointmentId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="new_appointment_date" class="form-control" required
                               min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rescheduling</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                placeholder="Enter reason for rescheduling"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reschedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    $('#appointmentsTable').DataTable({
        order: [[0, 'desc']], // Sort by date
        pageLength: 25,
        responsive: true,
        language: {
            search: '<i class="bi bi-search"></i>',
            searchPlaceholder: 'Search appointments...'
        }
    });

    // Initialize Select2 for application select
    $('select[name="application_id"]').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select an application',
        width: '100%'
    });
});

function completeAppointment(id) {
    if (confirm('Mark this appointment as completed?')) {
        window.location.href = `complete-appointment.php?id=${id}`;
    }
}

function completePaymentAppointment(id) {
    if (confirm('Confirm that payment has been received and mark this appointment as completed?')) {
        window.location.href = `complete-payment-appointment.php?id=${id}`;
    }
}

function rescheduleAppointment(id) {
    document.getElementById('rescheduleAppointmentId').value = id;
    new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
}

function cancelAppointment(id) {
    if (confirm('Are you sure you want to cancel this appointment?')) {
        window.location.href = `cancel-appointment.php?id=${id}`;
    }
}
</script>

<?php include 'scripts.php'; ?> 