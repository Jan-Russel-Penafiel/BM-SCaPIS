<?php
require_once 'config.php';

// Require login and must be resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'My Appointments';
$currentUser = getCurrentUser();

// Get all appointments for the logged-in resident
$stmt = $pdo->prepare("
    SELECT a.*, 
           app.application_number,
           dt.type_name as document_type
    FROM appointments a
    JOIN applications app ON a.application_id = app.id
    JOIN document_types dt ON app.document_type_id = dt.id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll();

include 'header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h1 class="h3 mb-2">My Appointments</h1>
                        <p class="text-muted mb-0">View your scheduled appointments for document verification or pickup.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-check me-2"></i>Appointments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($appointments)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar2-check text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No Appointments Found</h5>
                                <p class="text-muted">You have no scheduled appointments yet.</p>
                            </div>
                        <?php else: ?>
                            <!-- Desktop Table View -->
                            <div class="d-none d-lg-block">
                                <div class="table-responsive">
                                    <table class="table data-table" id="myAppointmentsTable">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Type</th>
                                                <th>Document</th>
                                                <th>Status</th>
                                                <th>Notes</th>
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
                                                        <span class="badge bg-info">
                                                            <?php echo ucfirst($apt['appointment_type']); ?>
                                                        </span>
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
                                                        <?php echo $apt['notes'] ? htmlspecialchars($apt['notes']) : '<span class="text-muted">-</span>'; ?>
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
                                    <?php foreach ($appointments as $apt): ?>
                                        <div class="col-12">
                                            <div class="card border shadow-sm">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?>
                                                            </h6>
                                                            <small class="text-muted">
                                                                <?php echo date('g:i A', strtotime($apt['appointment_date'])); ?>
                                                            </small>
                                                        </div>
                                                        <span class="badge bg-<?php 
                                                            echo $apt['status'] === 'completed' ? 'success' : 
                                                                ($apt['status'] === 'cancelled' ? 'danger' : 
                                                                ($apt['status'] === 'rescheduled' ? 'warning' : 'info'));
                                                        ?>">
                                                            <?php echo ucfirst($apt['status']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Type</small>
                                                        <span class="badge bg-info">
                                                            <?php echo ucfirst($apt['appointment_type']); ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Document</small>
                                                        <strong><?php echo htmlspecialchars($apt['document_type']); ?></strong>
                                                        <small class="text-muted d-block">
                                                            #<?php echo htmlspecialchars($apt['application_number']); ?>
                                                        </small>
                                                    </div>
                                                    
                                                    <?php if ($apt['notes']): ?>
                                                        <div>
                                                            <small class="text-muted d-block">Notes</small>
                                                            <p class="mb-0 small"><?php echo htmlspecialchars($apt['notes']); ?></p>
                                                        </div>
                                                    <?php endif; ?>
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
    </div>
</div>

<script>
$(document).ready(function() {
    // Only initialize DataTable for desktop view
    if (window.innerWidth >= 992) {
        $('#myAppointmentsTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search appointments...'
            }
        });
    }
});
</script>

<?php include 'scripts.php'; ?> 