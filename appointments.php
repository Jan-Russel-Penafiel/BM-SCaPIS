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

// Group appointments by user
$groupedAppointments = [];
foreach ($appointments as $apt) {
    $userId = $apt['user_id'];
    if (!isset($groupedAppointments[$userId])) {
        $groupedAppointments[$userId] = [
            'user_info' => [
                'first_name' => $apt['first_name'],
                'last_name' => $apt['last_name'],
                'contact_number' => $apt['contact_number']
            ],
            'appointments' => []
        ];
    }
    $groupedAppointments[$userId]['appointments'][] = $apt;
}

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
                            <div class="table-container">
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
                        <div class="table-container">
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
                                    <?php foreach ($groupedAppointments as $userId => $userData): ?>
                                        <?php 
                                        $appointments = $userData['appointments'];
                                        $userInfo = $userData['user_info'];
                                        $hasMultipleApts = count($appointments) > 1;
                                        
                                        // Show the first appointment by default
                                        $mainApt = $appointments[0];
                                        ?>
                                        <tr data-user-id="<?php echo $userId; ?>">
                                            <td class="datetime-column">
                                                <?php echo date('M j, Y', strtotime($mainApt['appointment_date'])); ?>
                                                <small class="text-muted d-block">
                                                    <?php echo date('g:i A', strtotime($mainApt['appointment_date'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>
                                                        <?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?>
                                                    </strong>
                                                    <?php if ($userInfo['contact_number']): ?>
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-telephone me-1"></i>
                                                            <?php echo htmlspecialchars($userInfo['contact_number']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hasMultipleApts): ?>
                                                        <!-- Dropup for multiple appointments -->
                                                        <div class="dropup mt-2">
                                                            <button class="btn btn-sm btn-primary dropdown-toggle" 
                                                                    type="button" 
                                                                    id="aptDropup<?php echo $userId; ?>" 
                                                                    data-bs-toggle="dropdown" 
                                                                    data-bs-auto-close="true"
                                                                    data-bs-boundary="viewport"
                                                                    data-bs-placement="top"
                                                                    aria-expanded="false">
                                                                <i class="bi bi-calendar-event me-1"></i>
                                                                <?php echo count($appointments); ?> Appointments
                                                            </button>
                                                            <ul class="dropdown-menu shadow" aria-labelledby="aptDropup<?php echo $userId; ?>">
                                                                <li><h6 class="dropdown-header">Select Appointment:</h6></li>
                                                                <?php foreach ($appointments as $index => $apt): ?>
                                                                    <li>
                                                                        <a class="dropdown-item appointment-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                                           href="#" 
                                                                           data-user-id="<?php echo $userId; ?>"
                                                                           data-apt-index="<?php echo $index; ?>"
                                                                           data-apt-id="<?php echo $apt['id']; ?>">
                                                                            <div class="d-flex flex-column">
                                                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 180px;">
                                                                                        <?php echo htmlspecialchars($apt['document_type']); ?>
                                                                                    </div>
                                                                                    <span class="badge bg-info ms-2 flex-shrink-0">
                                                                                        <?php echo ucfirst($apt['appointment_type']); ?>
                                                                                    </span>
                                                                                </div>
                                                                                <small class="text-muted d-block text-truncate">
                                                                                    <i class="bi bi-hash me-1"></i><?php echo htmlspecialchars($apt['application_number']); ?>
                                                                                </small>
                                                                                <small class="text-muted d-block">
                                                                                    <i class="bi bi-calendar me-1"></i><?php echo date('M j, Y g:i A', strtotime($apt['appointment_date'])); ?>
                                                                                </small>
                                                                                <span class="badge bg-<?php 
                                                                                    echo $apt['status'] === 'completed' ? 'success' : 
                                                                                        ($apt['status'] === 'cancelled' ? 'danger' : 
                                                                                        ($apt['status'] === 'rescheduled' ? 'warning' : 'info'));
                                                                                ?> mt-1" style="width: fit-content;">
                                                                                    <?php echo ucfirst($apt['status']); ?>
                                                                                </span>
                                                                            </div>
                                                                        </a>
                                                                    </li>
                                                                    <?php if ($index < count($appointments) - 1): ?>
                                                                        <li><hr class="dropdown-divider"></li>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="document-column">
                                                <?php echo htmlspecialchars($mainApt['document_type']); ?>
                                                <small class="text-muted d-block">
                                                    #<?php echo htmlspecialchars($mainApt['application_number']); ?>
                                                </small>
                                            </td>
                                            <td class="type-column">
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($mainApt['appointment_type']); ?>
                                                </span>
                                            </td>
                                            <td class="status-column">
                                                <span class="badge bg-<?php 
                                                    echo $mainApt['status'] === 'completed' ? 'success' : 
                                                        ($mainApt['status'] === 'cancelled' ? 'danger' : 
                                                        ($mainApt['status'] === 'rescheduled' ? 'warning' : 'info'));
                                                ?>">
                                                    <?php echo ucfirst($mainApt['status']); ?>
                                                </span>
                                            </td>
                                            <td class="actions-column">
                                                <div class="btn-group">
                                                    <?php if ($mainApt['status'] === 'scheduled'): ?>
                                                        <button type="button" class="btn btn-sm btn-success complete-btn" 
                                                                onclick="completeAppointment(<?php echo $mainApt['id']; ?>)"
                                                                title="Mark as Completed">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning reschedule-btn" 
                                                                onclick="rescheduleAppointment(<?php echo $mainApt['id']; ?>)"
                                                                title="Reschedule">
                                                            <i class="bi bi-calendar"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger cancel-btn" 
                                                                onclick="cancelAppointment(<?php echo $mainApt['id']; ?>)"
                                                                title="Cancel">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <?php if ($hasMultipleApts): ?>
                                            <!-- Hidden appointment data for dropdown switching -->
                                            <script type="text/javascript">
                                                window.appointmentData = window.appointmentData || {};
                                                window.appointmentData[<?php echo $userId; ?>] = <?php echo json_encode($appointments); ?>;
                                            </script>
                                        <?php endif; ?>
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
        paging: false,
        info: false,
        responsive: true,
        language: {
            search: '<i class="bi bi-search"></i>',
            searchPlaceholder: 'Search appointments...'
        }
    });

    // Application select is now a simple HTML select - no additional initialization needed

    // Initialize dropdowns with proper positioning
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(dropdownToggle) {
        new bootstrap.Dropdown(dropdownToggle, {
            boundary: 'viewport',
            placement: 'top-start'
        });
        
        // Handle dropdown positioning
        dropdownToggle.addEventListener('show.bs.dropdown', function (e) {
            const dropdown = this.nextElementSibling;
            if (dropdown) {
                // Ensure dropdown doesn't get clipped
                dropdown.style.position = 'fixed';
                dropdown.style.zIndex = '1050';
                
                // Position dropdown above the button
                const rect = this.getBoundingClientRect();
                const dropdownHeight = dropdown.offsetHeight || 300; // estimated height
                
                // Check if there's enough space above
                if (rect.top > dropdownHeight + 20) {
                    dropdown.style.top = (rect.top - dropdownHeight - 5) + 'px';
                } else {
                    // If not enough space above, show below
                    dropdown.style.top = (rect.bottom + 5) + 'px';
                }
                
                dropdown.style.left = rect.left + 'px';
                dropdown.style.minWidth = '280px';
                dropdown.style.maxWidth = '350px';
            }
        });
        
        dropdownToggle.addEventListener('hide.bs.dropdown', function (e) {
            const dropdown = this.nextElementSibling;
            if (dropdown) {
                // Reset positioning
                dropdown.style.position = '';
                dropdown.style.top = '';
                dropdown.style.left = '';
                dropdown.style.zIndex = '';
            }
        });
    });

    // Handle dropdown appointment switching
    document.querySelectorAll('.appointment-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userId = this.dataset.userId;
            const aptIndex = parseInt(this.dataset.aptIndex);
            const appointments = window.appointmentData[userId];
            
            if (appointments && appointments[aptIndex]) {
                // Remove active class from all items in this dropdown
                const dropdown = this.closest('.dropdown-menu');
                dropdown.querySelectorAll('.appointment-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                
                // Add active class to clicked item
                this.classList.add('active');
                
                switchToAppointment(userId, appointments[aptIndex]);
                
                // Close the dropdown
                const dropdownToggle = dropdown.previousElementSibling;
                if (dropdownToggle && dropdownToggle.classList.contains('dropdown-toggle')) {
                    bootstrap.Dropdown.getInstance(dropdownToggle).hide();
                }
            }
        });
    });
});

function switchToAppointment(userId, aptData) {
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) return;
    
    // Update datetime column
    const datetimeColumn = row.querySelector('.datetime-column');
    datetimeColumn.innerHTML = generateDateTimeColumnContent(aptData);
    
    // Update document column
    const documentColumn = row.querySelector('.document-column');
    documentColumn.innerHTML = generateDocumentColumnContent(aptData);
    
    // Update type column
    const typeColumn = row.querySelector('.type-column');
    typeColumn.innerHTML = generateTypeColumnContent(aptData);
    
    // Update status column
    const statusColumn = row.querySelector('.status-column');
    statusColumn.innerHTML = generateStatusColumnContent(aptData);
    
    // Update actions column
    const actionsColumn = row.querySelector('.actions-column');
    actionsColumn.innerHTML = generateActionsColumnContent(aptData);
}

function generateDateTimeColumnContent(apt) {
    const appointmentDate = new Date(apt.appointment_date);
    return `${appointmentDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}
        <small class="text-muted d-block">
            ${appointmentDate.toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true})}
        </small>`;
}

function generateDocumentColumnContent(apt) {
    return `${escapeHtml(apt.document_type)}
        <small class="text-muted d-block">
            #${escapeHtml(apt.application_number)}
        </small>`;
}

function generateTypeColumnContent(apt) {
    return `<span class="badge bg-info">
        ${capitalizeFirst(apt.appointment_type)}
    </span>`;
}

function generateStatusColumnContent(apt) {
    let badgeClass = 'info';
    if (apt.status === 'completed') badgeClass = 'success';
    else if (apt.status === 'cancelled') badgeClass = 'danger';
    else if (apt.status === 'rescheduled') badgeClass = 'warning';
    
    return `<span class="badge bg-${badgeClass}">
        ${capitalizeFirst(apt.status)}
    </span>`;
}

function generateActionsColumnContent(apt) {
    let html = '<div class="btn-group">';
    
    if (apt.status === 'scheduled') {
        html += `<button type="button" class="btn btn-sm btn-success complete-btn" 
                        onclick="completeAppointment(${apt.id})"
                        title="Mark as Completed">
                    <i class="bi bi-check-lg"></i>
                </button>`;
        html += `<button type="button" class="btn btn-sm btn-warning reschedule-btn" 
                        onclick="rescheduleAppointment(${apt.id})"
                        title="Reschedule">
                    <i class="bi bi-calendar"></i>
                </button>`;
        html += `<button type="button" class="btn btn-sm btn-danger cancel-btn" 
                        onclick="cancelAppointment(${apt.id})"
                        title="Cancel">
                    <i class="bi bi-x-lg"></i>
                </button>`;
    }
    
    html += '</div>';
    return html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

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

<style>
.table-container {
    overflow: visible !important;
    position: relative;
}

.table-container table {
    table-layout: fixed;
    width: 100%;
    font-size: 0.85rem;
}

.table-container th,
.table-container td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
    padding: 0.5rem 0.75rem;
}

.table-container small {
    font-size: 0.75rem;
}

/* Specific column widths for appointments table */
.table-container th:nth-child(1) { width: 18%; } /* Date & Time */
.table-container th:nth-child(2) { width: 20%; } /* Resident */
.table-container th:nth-child(3) { width: 20%; } /* Document */
.table-container th:nth-child(4) { width: 12%; } /* Type */
.table-container th:nth-child(5) { width: 12%; } /* Status */
.table-container th:nth-child(6) { width: 18%; } /* Actions */

.table-container td:nth-child(1),
.table-container td:nth-child(2),
.table-container td:nth-child(3) {
    white-space: normal;
}

/* Dropdown styles for multiple appointments */
.dropdown-menu {
    max-height: 400px;
    overflow-y: auto;
    overflow-x: hidden;
    min-width: 280px;
    max-width: 350px;
    border: 1px solid #dee2e6;
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
    z-index: 1050 !important;
    position: absolute !important;
    background: white;
    border-radius: 0.375rem;
    word-wrap: break-word;
}

.dropup {
    position: static !important;
}

.dropup .dropdown-menu {
    bottom: 100%;
    top: auto;
    margin-bottom: 0.125rem;
    position: absolute !important;
    transform: translate3d(0px, 0px, 0px) !important;
}

.dropdown-menu-end {
    --bs-position: end;
    right: 0;
    left: auto;
}

.card-body {
    overflow: visible !important;
    position: relative;
    z-index: 1;
}

.table-responsive {
    overflow: visible !important;
}

/* Fix for Bootstrap's overflow hidden on cards */
.card {
    overflow: visible !important;
}

.main-content {
    overflow: visible !important;
}

/* Ensure dropdowns appear above everything */
.dropdown-menu.show {
    z-index: 1060 !important;
    position: fixed !important;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f4;
    transition: all 0.2s ease-in-out;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.dropdown-item.active {
    background-color: #e3f2fd;
    color: #1976d2;
    border-left: 3px solid #1976d2;
}

.dropdown-item .badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    white-space: nowrap;
}

.dropdown-header {
    font-size: 0.8rem;
    font-weight: 600;
    color: #495057;
    padding: 0.5rem 1rem;
    margin-bottom: 0;
    border-bottom: 1px solid #dee2e6;
    white-space: nowrap;
}

.appointment-item {
    cursor: pointer;
}

.appointment-item:hover {
    color: inherit !important;
    text-decoration: none;
}

.dropdown-toggle {
    font-size: 0.8rem;
    padding: 0.375rem 0.75rem;
    font-weight: 500;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease-in-out;
}

.dropdown-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.dropdown-toggle i {
    font-size: 0.85rem;
}

.dropdown-divider {
    margin: 0.5rem 0;
}

/* Text truncation for long content */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Ensure flex items don't overflow */
.d-flex {
    min-width: 0;
}

.flex-grow-1 {
    min-width: 0;
}

.flex-shrink-0 {
    flex-shrink: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dropdown-menu {
        min-width: 280px;
        max-height: 300px;
    }
    
    .dropdown-item {
        padding: 0.5rem 0.75rem;
    }
    
    .dropdown-toggle {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>

<?php include 'scripts.php'; ?> 