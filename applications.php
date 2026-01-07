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

// Group applications by user
$groupedApplications = [];
foreach ($applications as $app) {
    $userId = $app['user_id'];
    if (!isset($groupedApplications[$userId])) {
        $groupedApplications[$userId] = [
            'user_info' => [
                'first_name' => $app['first_name'],
                'last_name' => $app['last_name'],
                'contact_number' => $app['contact_number']
            ],
            'applications' => []
        ];
    }
    $groupedApplications[$userId]['applications'][] = $app;
}

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
                        <h1 class="h3 mb-2">Document Applications</h1>
                        <p class="text-muted mb-0">
                            <?php echo $_SESSION['role'] === 'admin' ? 
                                'Manage and process all document applications' : 
                                'View applications from residents in your purok'; ?>
                        </p>
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
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i><strong>Note:</strong> When payment is completed, applications automatically start processing. 
                    Processing time is <strong>3 to 5 working days (except holidays)</strong> from the payment date.
                </div>
                <?php endif; ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>All Applications
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-hover" id="applicationsTable">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Document</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupedApplications as $userId => $userData): ?>
                                        <?php 
                                        $applications = $userData['applications'];
                                        $userInfo = $userData['user_info'];
                                        $hasMultipleApps = count($applications) > 1;
                                        
                                        // Show the first application by default
                                        $mainApp = $applications[0];
                                        ?>
                                        <tr data-user-id="<?php echo $userId; ?>">
                                            <td>
                                                <div>
                                                    <strong>
                                                        <?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?>
                                                    </strong>
                                                    <?php if ($mainApp['urgency'] === 'Rush'): ?>
                                                        <span class="badge bg-danger ms-1">Rush</span>
                                                    <?php endif; ?>
                                                    <?php if ($userInfo['contact_number']): ?>
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-telephone me-1"></i>
                                                            <?php echo htmlspecialchars($userInfo['contact_number']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hasMultipleApps): ?>
                                                        <!-- Dropup for multiple applications -->
                                                        <div class="dropup mt-2">
                                                            <button class="btn btn-sm btn-primary dropdown-toggle" 
                                                                    type="button" 
                                                                    id="appDropup<?php echo $userId; ?>" 
                                                                    data-bs-toggle="dropdown" 
                                                                    data-bs-auto-close="true"
                                                                    data-bs-boundary="viewport"
                                                                    data-bs-placement="top"
                                                                    aria-expanded="false">
                                                                <i class="bi bi-layers me-1"></i>
                                                                <?php echo count($applications); ?> Applications
                                                            </button>
                                                            <ul class="dropdown-menu shadow" aria-labelledby="appDropup<?php echo $userId; ?>">
                                                                <li><h6 class="dropdown-header">Select Application:</h6></li>
                                                                <?php foreach ($applications as $index => $app): ?>
                                                                    <li>
                                                                        <a class="dropdown-item application-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                                           href="#" 
                                                                           data-user-id="<?php echo $userId; ?>"
                                                                           data-app-index="<?php echo $index; ?>"
                                                                           data-app-id="<?php echo $app['id']; ?>">
                                                                            <div class="d-flex flex-column">
                                                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 180px;">
                                                                                        <?php echo htmlspecialchars($app['type_name']); ?>
                                                                                    </div>
                                                                                    <span class="badge status-<?php echo $app['status']; ?> ms-2 flex-shrink-0
                                                                                        <?php if ($app['status'] === 'ready_for_pickup') echo 'bg-primary text-white'; ?>
                                                                                        <?php if ($app['status'] === 'pending') echo 'bg-warning text-dark'; ?>
                                                                                        <?php if ($app['status'] === 'processing') echo 'bg-info text-white'; ?>
                                                                                        <?php if ($app['status'] === 'completed') echo 'bg-success text-white'; ?>">
                                                                                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                                                                    </span>
                                                                                </div>
                                                                                <small class="text-muted d-block text-truncate">
                                                                                    <i class="bi bi-hash me-1"></i><?php echo htmlspecialchars($app['application_number']); ?>
                                                                                </small>
                                                                                <small class="text-muted d-block">
                                                                                    <i class="bi bi-clock me-1"></i><?php echo date('M j, Y g:i A', strtotime($app['created_at'])); ?>
                                                                                </small>
                                                                                <?php if ($app['urgency'] === 'Rush'): ?>
                                                                                    <span class="badge bg-danger text-white mt-1" style="width: fit-content;">Rush</span>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </a>
                                                                    </li>
                                                                    <?php if ($index < count($applications) - 1): ?>
                                                                        <li><hr class="dropdown-divider"></li>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-hash me-1"></i><?php echo htmlspecialchars($mainApp['application_number']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="document-column">
                                                <?php echo htmlspecialchars($mainApp['type_name']); ?>
                                                <small class="text-muted d-block">
                                                    <?php 
                                                    $purpose = htmlspecialchars($mainApp['purpose']);
                                                    echo strlen($purpose) > 30 ? substr($purpose, 0, 27) . '...' : $purpose;
                                                    ?>
                                                </small>
                                                <div class="mt-1">
                                                    <span class="badge status-<?php echo $mainApp['status']; ?>
                                                        <?php if ($mainApp['status'] === 'ready_for_pickup') echo ' bg-primary text-white fw-bold ready-pickup-badge'; ?>">
                                                        <?php if ($mainApp['status'] === 'ready_for_pickup'): ?>
                                                            <i class="bi bi-check-circle me-1"></i>
                                                        <?php endif; ?>
                                                        <?php echo ucfirst($mainApp['status']); ?>
                                                    </span>
                                                </div>
                                                <?php if ($mainApp['status'] === 'processing' && $mainApp['payment_date'] && ($mainApp['payment_status'] === 'paid' || $mainApp['payment_status'] === 'waived')): ?>
                                                    <?php
                                                    // Calculate expected completion date (5 working days from payment date)
                                                    try {
                                                        $paymentDate = new DateTime($mainApp['payment_date']);
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
                                            <td class="payment-column">
                                                <span class="badge bg-<?php 
                                                    echo $mainApp['payment_status'] === 'paid' ? 'success' : 
                                                        ($mainApp['payment_status'] === 'waived' ? 'info' : 'warning');
                                                ?>">
                                                    <?php echo ucfirst($mainApp['payment_status']); ?>
                                                </span>
                                                <?php if ($mainApp['payment_amount']): ?>
                                                    <small class="d-block">₱<?php echo number_format($mainApp['payment_amount'], 2); ?></small>
                                                <?php endif; ?>
                                                <?php if ($mainApp['payment_appointment_id'] && $mainApp['payment_appointment_status'] === 'scheduled'): ?>
                                                    <small class="d-block text-info">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        Payment appt: <?php echo date('M j, g:i A', strtotime($mainApp['payment_appointment_date'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($mainApp['payment_receipt'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info mt-1 w-100" 
                                                            onclick="viewReceipt('<?php echo htmlspecialchars($mainApp['payment_receipt']); ?>')">
                                                        <i class="bi bi-image"></i> View Receipt
                                                    </button>
                                                <?php endif; ?>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="bi bi-clock me-1"></i>Applied: <?php echo date('M j, Y', strtotime($mainApp['created_at'])); ?>
                                                </small>
                                                <small class="text-muted d-block">
                                                    <?php echo date('g:i A', strtotime($mainApp['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td class="actions-column">
                                                <div class="btn-group">
                                                    <a href="view-application.php?id=<?php echo $mainApp['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary view-btn"
                                                       title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                                        <?php if ($mainApp['status'] === 'pending' && $mainApp['payment_status'] === 'unpaid'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-warning payment-btn"
                                                                    onclick="markPaymentReceived(<?php echo $mainApp['id']; ?>)"
                                                                    title="Mark Payment as Received">
                                                                <i class="bi bi-cash-coin"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($mainApp['status'] === 'pending' && ($mainApp['payment_status'] === 'paid' || $mainApp['payment_status'] === 'waived')): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success process-btn"
                                                                    onclick="processApplication(<?php echo $mainApp['id']; ?>)"
                                                                    title="Start Processing">
                                                                <i class="bi bi-play-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($mainApp['status'] === 'processing'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-info ready-btn"
                                                                    onclick="openReadyAppointmentModal(<?php echo $mainApp['id']; ?>)"
                                                                    title="Mark as Ready & Schedule Appointment">
                                                                <i class="bi bi-box-seam"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($mainApp['status'] === 'ready_for_pickup'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success complete-btn"
                                                                    onclick="completeApplication(<?php echo $mainApp['id']; ?>)"
                                                                    title="Complete">
                                                                <i class="bi bi-check-all"></i>
                                                            </button>
                                                        <?php endif; ?>



                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <?php if ($hasMultipleApps): ?>
                                            <!-- Hidden application data for dropdown switching -->
                                            <script type="text/javascript">
                                                window.applicationData = window.applicationData || {};
                                                window.applicationData[<?php echo $userId; ?>] = <?php echo json_encode($applications); ?>;
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

<!-- Payment Received Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Mark Payment as Received</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="mark-payment-received.php" method="POST">
                <input type="hidden" name="application_id" id="paymentApplicationId">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        This will mark the payment status as <strong>Paid</strong>. The application status will remain as <strong>Pending</strong> until you start processing.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="3" 
                                placeholder="Enter payment details or remarks (e.g., receipt number, payment method)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle me-1"></i>Confirm Payment Received</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Process Application Modal -->
<div class="modal fade" id="processModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-play-circle me-2"></i>Start Processing Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process-application.php" method="POST">
                <input type="hidden" name="application_id" id="processApplicationId">
                <div class="modal-body">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        Payment has been confirmed. You can now start processing this application.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Processing Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="3" 
                                placeholder="Enter processing remarks or instructions"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-play-circle me-1"></i>Start Processing</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    $('#applicationsTable').DataTable({
        order: [[2, 'desc']], // Sort by payment column (which now contains date)
        paging: false,
        info: false,
        responsive: true,
        language: {
            search: '<i class="bi bi-search"></i>',
            searchPlaceholder: 'Search applications...'
        }
    });

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

    // Handle dropdown application switching
    document.querySelectorAll('.application-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userId = this.dataset.userId;
            const appIndex = parseInt(this.dataset.appIndex);
            const applications = window.applicationData[userId];
            
            if (applications && applications[appIndex]) {
                // Remove active class from all items in this dropdown
                const dropdown = this.closest('.dropdown-menu');
                dropdown.querySelectorAll('.application-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                
                // Add active class to clicked item
                this.classList.add('active');
                
                switchToApplication(userId, applications[appIndex]);
                
                // Close the dropdown
                const dropdownToggle = dropdown.previousElementSibling;
                if (dropdownToggle && dropdownToggle.classList.contains('dropdown-toggle')) {
                    bootstrap.Dropdown.getInstance(dropdownToggle).hide();
                }
            }
        });
    });
});

function switchToApplication(userId, appData) {
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) return;
    
    // Update document column
    const documentColumn = row.querySelector('.document-column');
    documentColumn.innerHTML = generateDocumentColumnContent(appData);
    
    // Update payment column
    const paymentColumn = row.querySelector('.payment-column');
    paymentColumn.innerHTML = generatePaymentColumnContent(appData);
    
    // Update actions column
    const actionsColumn = row.querySelector('.actions-column');
    actionsColumn.innerHTML = generateActionsColumnContent(appData);
}

function generateDocumentColumnContent(app) {
    let html = `${escapeHtml(app.type_name)}`;
    
    if (app.purpose) {
        const purpose = escapeHtml(app.purpose);
        const truncatedPurpose = purpose.length > 30 ? purpose.substring(0, 27) + '...' : purpose;
        html += `<small class="text-muted d-block">${truncatedPurpose}</small>`;
    }
    
    html += `<div class="mt-1">
        <span class="badge status-${app.status}${app.status === 'ready_for_pickup' ? ' bg-primary text-white fw-bold ready-pickup-badge' : ''}">
            ${app.status === 'ready_for_pickup' ? '<i class="bi bi-check-circle me-1"></i>' : ''}
            ${capitalizeFirst(app.status)}
        </span>
    </div>`;
    
    // Add expected completion date logic if needed
    if (app.status === 'processing' && app.payment_date && (app.payment_status === 'paid' || app.payment_status === 'waived')) {
        // Add expected completion date calculation here if needed
    }
    
    return html;
}

function generatePaymentColumnContent(app) {
    let badgeClass = 'warning';
    if (app.payment_status === 'paid') badgeClass = 'success';
    else if (app.payment_status === 'waived') badgeClass = 'info';
    
    let html = `<span class="badge bg-${badgeClass}">${capitalizeFirst(app.payment_status)}</span>`;
    
    if (app.payment_amount) {
        html += `<small class="d-block">₱${parseFloat(app.payment_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</small>`;
    }
    
    if (app.payment_appointment_id && app.payment_appointment_status === 'scheduled') {
        const appointmentDate = new Date(app.payment_appointment_date);
        html += `<small class="d-block text-info">
            <i class="bi bi-calendar-check me-1"></i>
            Payment appt: ${appointmentDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric'})} ${appointmentDate.toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true})}
        </small>`;
    }
    
    const createdDate = new Date(app.created_at);
    html += `<small class="text-muted d-block mt-1">
        <i class="bi bi-clock me-1"></i>Applied: ${createdDate.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}
    </small>`;
    html += `<small class="text-muted d-block">
        ${createdDate.toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true})}
    </small>`;
    
    return html;
}

function generateActionsColumnContent(app) {
    let html = `<div class="btn-group">
        <a href="view-application.php?id=${app.id}" 
           class="btn btn-sm btn-outline-primary view-btn"
           title="View Details">
            <i class="bi bi-eye"></i>
        </a>`;
    
    // Add admin-specific buttons based on application status
    // This would need to check the user's role from PHP session
    <?php if ($_SESSION['role'] === 'admin'): ?>
    if (app.status === 'pending' && app.payment_status === 'unpaid') {
        html += `<button type="button" 
                        class="btn btn-sm btn-outline-warning payment-btn"
                        onclick="markPaymentReceived(${app.id})"
                        title="Mark Payment as Received">
                    <i class="bi bi-cash-coin"></i>
                </button>`;
    }
    
    if (app.status === 'pending' && (app.payment_status === 'paid' || app.payment_status === 'waived')) {
        html += `<button type="button" 
                        class="btn btn-sm btn-outline-success process-btn"
                        onclick="processApplication(${app.id})"
                        title="Start Processing">
                    <i class="bi bi-play-circle"></i>
                </button>`;
    }
    
    if (app.status === 'processing') {
        html += `<button type="button" 
                        class="btn btn-sm btn-outline-info ready-btn"
                        onclick="openReadyAppointmentModal(${app.id})"
                        title="Mark as Ready & Schedule Appointment">
                    <i class="bi bi-box-seam"></i>
                </button>`;
    }
    
    if (app.status === 'ready_for_pickup') {
        html += `<button type="button" 
                        class="btn btn-sm btn-outline-success complete-btn"
                        onclick="completeApplication(${app.id})"
                        title="Complete">
                    <i class="bi bi-check-all"></i>
                </button>`;
    }
    <?php endif; ?>
    
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

function processApplication(id) {
    document.getElementById('processApplicationId').value = id;
    new bootstrap.Modal(document.getElementById('processModal')).show();
}

function completeApplication(id) {
    if (confirm('Are you sure you want to mark this application as completed?')) {
        window.location.href = `complete-application.php?id=${id}`;
    }
}

function openReadyAppointmentModal(appId) {
    document.getElementById('readyAppointmentApplicationId').value = appId;
    new bootstrap.Modal(document.getElementById('readyAppointmentModal')).show();
}

function markPaymentReceived(id) {
    document.getElementById('paymentApplicationId').value = id;
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
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
    padding: 0.25rem 0.5rem;
}

.table-container small {
    font-size: 0.75rem;
}

/* Reduce horizontal gaps between elements */
.table-container .badge {
    margin-left: 0.25rem;
    margin-right: 0.25rem;
}

.table-container .d-block {
    margin-bottom: 0.25rem;
}

.table-container .mt-1 {
    margin-top: 0.25rem !important;
}

.table-container .me-1 {
    margin-right: 0.25rem !important;
}

.table-container .ms-1 {
    margin-left: 0.25rem !important;
}

/* Specific column widths for applications table */
.table-container th:nth-child(1) { width: 30%; } /* Applicant */
.table-container th:nth-child(2) { width: 30%; } /* Document (now includes status) */
.table-container th:nth-child(3) { width: 25%; } /* Payment */
.table-container th:nth-child(4) { width: 15%; } /* Actions */

.table-container td:nth-child(1),
.table-container td:nth-child(2),
.table-container td:nth-child(3) {
    white-space: normal;
}

.ready-pickup-badge {
    animation: pickupPulse 1.2s infinite alternate;
}
@keyframes pickupPulse {
    0% { box-shadow: 0 0 0 0 rgba(13,110,253,0.5); }
    100% { box-shadow: 0 0 10px 4px rgba(13,110,253,0.3); }
}

/* Make action buttons smaller */
.table-container .btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1.2;
}

.table-container .btn-group .btn i {
    font-size: 0.8rem;
}

/* Dropdown styles for multiple applications */
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

/* Ensure table container doesn't clip dropdowns */
.table-container {
    overflow: visible !important;
    position: relative;
    z-index: 1;
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

.application-item {
    cursor: pointer;
}

.application-item:hover {
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

<?php include 'scripts.php'; ?>
 