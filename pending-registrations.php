<?php
require_once 'config.php';

// Require login and must be admin or purok leader
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'purok_leader'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Pending Registrations';
$currentUser = getCurrentUser();

// Get pending registrations based on role
$params = [];
$whereClause = "WHERE u.role = 'resident' AND (";

if ($_SESSION['role'] === 'admin') {
    // Admin sees registrations that either need admin approval or have pending purok leader approval
    $whereClause .= "(u.admin_approval = 'pending' OR u.purok_leader_approval = 'pending')";
} else {
    // Purok leader sees registrations from their purok that either need their approval or are pending admin approval
    $whereClause .= "u.purok_id = ? AND (u.purok_leader_approval = 'pending' OR u.admin_approval = 'pending')";
    $params[] = $currentUser['purok_id'];
}

$whereClause .= ") AND u.status != 'approved'";

// Get pending registrations with their purok information
$stmt = $pdo->prepare("
    SELECT u.*, 
           p.purok_name,
           CONCAT(pl.first_name, ' ', pl.last_name) as purok_leader_name,
           CASE 
               WHEN u.status = 'approved' THEN 'approved'
               WHEN u.status = 'disapproved' THEN 'disapproved'
               ELSE 'pending'
           END as approval_status,
           CONCAT(apl.first_name, ' ', apl.last_name) as approved_by_purok_leader_name,
           CONCAT(aa.first_name, ' ', aa.last_name) as approved_by_admin_name,
           u.approved_at,
           u.purok_leader_remarks,
           u.admin_remarks
    FROM users u
    LEFT JOIN puroks p ON u.purok_id = p.id
    LEFT JOIN users pl ON p.purok_leader_id = pl.id
    LEFT JOIN users apl ON u.approved_by_purok_leader = apl.id
    LEFT JOIN users aa ON u.approved_by_admin = aa.id
    $whereClause
    ORDER BY 
        CASE 
            WHEN u.purok_leader_approval = 'pending' AND u.admin_approval = 'pending' THEN 1
            WHEN u.purok_leader_approval = 'approved' AND u.admin_approval = 'pending' THEN 2
            WHEN u.purok_leader_approval = 'pending' AND u.admin_approval = 'approved' THEN 2
            ELSE 3
        END,
        u.created_at DESC
");
$stmt->execute($params);
$pendingRegistrations = $stmt->fetchAll();

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
                                <h1 class="h3 mb-2">Pending Registrations</h1>
                                <p class="text-muted mb-0">
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        Review and approve new resident registrations
                                    <?php else: ?>
                                        Review and approve resident registrations from your purok
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="badge bg-warning text-dark fs-5">
                                <?php echo count($pendingRegistrations); ?> Pending
                            </div>
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

        <!-- Registrations List -->
        <div class="row">
            <?php if (empty($pendingRegistrations)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <h4 class="mt-3">No Pending Registrations</h4>
                            <p class="text-muted">All registration requests have been processed.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($pendingRegistrations as $registration): ?>
                    <?php if ($registration['approval_status'] !== 'fully_approved'): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <?php 
                                        echo htmlspecialchars($registration['last_name'] . ', ' . 
                                            $registration['first_name'] . ' ' . 
                                            ($registration['middle_name'] ? substr($registration['middle_name'], 0, 1) . '.' : '') .
                                            ($registration['suffix'] ? ' ' . $registration['suffix'] : '')); 
                                        ?>
                                    </h5>
                                    <span class="badge bg-warning text-dark">
                                        <?php echo date('M j, Y', strtotime($registration['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <small class="text-muted d-block">Purok</small>
                                                <strong><?php echo htmlspecialchars($registration['purok_name'] ?? 'Not assigned'); ?></strong>
                                            </div>
                                            <div class="mb-3">
                                                <small class="text-muted d-block">Contact Number</small>
                                                <strong><?php echo htmlspecialchars($registration['contact_number'] ?? 'N/A'); ?></strong>
                                            </div>
                                            <div class="mb-3">
                                                <small class="text-muted d-block">Email</small>
                                                <strong><?php echo htmlspecialchars($registration['email'] ?? 'N/A'); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <small class="text-muted d-block">Age</small>
                                                <strong><?php echo $registration['age']; ?> years old</strong>
                                            </div>
                                            <div class="mb-3">
                                                <small class="text-muted d-block">Gender</small>
                                                <strong><?php echo $registration['gender']; ?></strong>
                                            </div>
                                            <div class="mb-3">
                                                <small class="text-muted d-block">Civil Status</small>
                                                <strong><?php echo $registration['civil_status']; ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <small class="text-muted d-block">Address</small>
                                        <strong><?php echo htmlspecialchars($registration['address']); ?></strong>
                                    </div>

                                    <?php if ($registration['valid_id_front'] || $registration['valid_id_back']): ?>
                                        <div class="mt-4">
                                            <h6 class="mb-3">Valid ID</h6>
                                            <div class="row g-2">
                                                <?php if ($registration['valid_id_front']): ?>
                                                    <div class="col-6">
                                                        <a href="uploads/ids/<?php echo $registration['valid_id_front']; ?>" 
                                                           target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                            <i class="bi bi-image me-2"></i>View Front
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($registration['valid_id_back']): ?>
                                                    <div class="col-6">
                                                        <a href="uploads/ids/<?php echo $registration['valid_id_back']; ?>" 
                                                           target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                            <i class="bi bi-image me-2"></i>View Back
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Approval Status -->
                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <small class="text-muted">Purok Leader Approval</small>
                                                <span class="ms-2 badge bg-<?php 
                                                    echo $registration['purok_leader_approval'] === 'approved' ? 'success' : 
                                                        ($registration['purok_leader_approval'] === 'disapproved' ? 'danger' : 'warning');
                                                ?>">
                                                    <?php echo ucfirst($registration['purok_leader_approval']); ?>
                                                </span>
                                                <?php if ($registration['purok_leader_approval'] !== 'pending' && $registration['approved_by_purok_leader_name']): ?>
                                                    <small class="d-block text-muted mt-1">
                                                        By: <?php echo htmlspecialchars($registration['approved_by_purok_leader_name']); ?>
                                                        <br>
                                                        <?php if ($registration['purok_leader_remarks']): ?>
                                                            Remarks: <?php echo htmlspecialchars($registration['purok_leader_remarks']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <small class="text-muted">Admin Approval</small>
                                                <span class="ms-2 badge bg-<?php 
                                                    echo $registration['admin_approval'] === 'approved' ? 'success' : 
                                                        ($registration['admin_approval'] === 'disapproved' ? 'danger' : 'warning');
                                                ?>">
                                                    <?php echo ucfirst($registration['admin_approval']); ?>
                                                </span>
                                                <?php if ($registration['admin_approval'] !== 'pending' && $registration['approved_by_admin_name']): ?>
                                                    <small class="d-block text-muted mt-1">
                                                        By: <?php echo htmlspecialchars($registration['approved_by_admin_name']); ?>
                                                        <br>
                                                        <?php if ($registration['admin_remarks']): ?>
                                                            Remarks: <?php echo htmlspecialchars($registration['admin_remarks']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Status Messages -->
                                        <?php if ($registration['status'] === 'approved'): ?>
                                            <div class="alert alert-success mb-0">
                                                <i class="bi bi-check-circle me-2"></i>Registration has been automatically approved
                                                <small class="d-block text-success mt-1">
                                                    Approved on: <?php echo date('M j, Y g:i A', strtotime($registration['approved_at'])); ?>
                                                </small>
                                            </div>
                                        <?php elseif ($registration['status'] === 'disapproved'): ?>
                                            <div class="alert alert-danger mb-0">
                                                <i class="bi bi-x-circle me-2"></i>Registration has been disapproved
                                            </div>
                                        <?php else: ?>
                                            <?php if ($registration['purok_leader_approval'] === 'approved' && $registration['admin_approval'] === 'pending'): ?>
                                                <div class="alert alert-info mb-0">
                                                    <i class="bi bi-info-circle me-2"></i>Approved by Purok Leader, waiting for Admin approval
                                                </div>
                                            <?php elseif ($registration['admin_approval'] === 'approved' && $registration['purok_leader_approval'] === 'pending'): ?>
                                                <div class="alert alert-info mb-0">
                                                    <i class="bi bi-info-circle me-2"></i>Approved by Admin, waiting for Purok Leader approval
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action Buttons -->
                                    <?php if (
                                        ($_SESSION['role'] === 'admin' && $registration['admin_approval'] === 'pending' && 
                                         $registration['status'] !== 'disapproved' && $registration['purok_leader_approval'] !== 'pending') ||
                                        ($_SESSION['role'] === 'purok_leader' && $registration['purok_leader_approval'] === 'pending' && 
                                         $registration['status'] !== 'disapproved')
                                    ): ?>
                                        <div class="mt-4 d-flex gap-2">
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <?php if ($registration['purok_leader_approval'] === 'approved'): ?>
                                                    <button type="button" class="btn btn-success flex-grow-1" 
                                                            onclick="approveRegistration(<?php echo $registration['id']; ?>)">
                                                        <i class="bi bi-check-lg me-2"></i>Approve
                                                    </button>
                                                    <button type="button" class="btn btn-danger flex-grow-1" 
                                                            onclick="disapproveRegistration(<?php echo $registration['id']; ?>)">
                                                        <i class="bi bi-x-lg me-2"></i>Disapprove
                                                    </button>
                                                <?php elseif ($registration['purok_leader_approval'] === 'disapproved'): ?>
                                                    <div class="alert alert-warning mb-0 w-100">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        This registration was not recommended by the Purok Leader.
                                                        <?php if ($registration['purok_leader_remarks']): ?>
                                                            <br>
                                                            <small class="mt-1 d-block">
                                                                <strong>Reason:</strong> <?php echo htmlspecialchars($registration['purok_leader_remarks']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="button" class="btn btn-danger flex-grow-1" 
                                                            onclick="disapproveRegistration(<?php echo $registration['id']; ?>)">
                                                        <i class="bi bi-x-lg me-2"></i>Confirm Disapproval
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-success flex-grow-1" 
                                                        onclick="recommendApproval(<?php echo $registration['id']; ?>)">
                                                    <i class="bi bi-check-lg me-2"></i>Recommend Approval
                                                </button>
                                                <!-- First, update the button that triggers the modal -->
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="confirmDisapproval('<?php echo $registration['id']; ?>')" 
                                                        <?php echo ($registration['purok_leader_approval'] === 'pending' && $_SESSION['role'] === 'admin') ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-x-circle me-1"></i>Not Recommended
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($_SESSION['role'] === 'admin' && $registration['purok_leader_approval'] === 'pending'): ?>
                                        <div class="mt-4">
                                            <div class="alert alert-info mb-0">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Waiting for Purok Leader's decision before admin review.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalTitle">Approve Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process-registration.php" method="POST">
                <input type="hidden" name="user_id" id="registrationId">
                <input type="hidden" name="action" id="approvalAction">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" required
                                placeholder="Enter any additional remarks or reasons (minimum 3 characters)"></textarea>
                        <small class="text-muted">Required: Please provide at least 3 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="approvalSubmitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Disapproval Modal -->
<div class="modal fade" id="disapprovalModal" tabindex="-1" aria-labelledby="disapprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="disapprovalModalLabel">Disapprove Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Reason for Disapproval <span class="text-danger">*</span></label>
                    <select class="form-select" id="reasonSelect">
                        <option value="">-- Select a reason --</option>
                        <option value="Not a resident of this purok">Not a resident of this purok</option>
                        <option value="Invalid documentation provided">Invalid documentation provided</option>
                        <option value="Wrong address information">Wrong address information</option>
                    </select>
                    <div class="form-text mt-2">
                        This reason will be sent to the resident via SMS notification.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDisapprovalBtn">
                    <i class="bi bi-x-circle me-1"></i>Confirm Disapproval
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1050;"></div>

<script>
function showApprovalModal(id, action, title, btnClass) {
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    document.getElementById('registrationId').value = id;
    document.getElementById('approvalAction').value = action;
    document.getElementById('approvalModalTitle').textContent = title;
    const submitBtn = document.getElementById('approvalSubmitBtn');
    submitBtn.className = 'btn ' + btnClass;
    submitBtn.textContent = action === 'approve' ? 'Approve' : 'Disapprove';
    modal.show();
}

function approveRegistration(id) {
    showApprovalModal(id, 'approve', 'Approve Registration', 'btn-success');
}

function disapproveRegistration(id) {
    showApprovalModal(id, 'disapprove', 'Disapprove Registration', 'btn-danger');
}

function recommendApproval(id) {
    showApprovalModal(id, 'approve', 'Recommend for Approval', 'btn-success');
}

function recommendDisapproval(id) {
    showApprovalModal(id, 'disapprove', 'Not Recommended', 'btn-danger');
}

let currentUserId = null;
let disapprovalModal = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the modal
    disapprovalModal = new bootstrap.Modal(document.getElementById('disapprovalModal'));
    
    // Handle confirm button click
    document.getElementById('confirmDisapprovalBtn').addEventListener('click', function() {
        const reasonSelect = document.getElementById('reasonSelect');
        const selectedReason = reasonSelect.value;
        
        if (!selectedReason) {
            showToast('error', 'Error', 'Please select a reason for disapproval');
            return;
        }
        
        // Show loading state
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
        
        // Make the AJAX request
        fetch('process-registration.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                user_id: currentUserId,
                action: 'disapprove',
                remarks: selectedReason
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showToast('success', 'Success', data.message);
                disapprovalModal.hide();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('error', 'Error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Error', 'An error occurred while processing the request');
        })
        .finally(() => {
            // Reset button state
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
});

function confirmDisapproval(userId) {
    if (!userId) {
        console.error('No user ID provided');
        return;
    }
    
    currentUserId = userId;
    
    // Reset form
    const reasonSelect = document.getElementById('reasonSelect');
    reasonSelect.value = '';
    
    // Show modal
    disapprovalModal.show();
}

function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer');
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}:</strong> ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Initialize DataTables if there are registrations
document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('.table');
    if (table) {
        $(table).DataTable({
            pageLength: 10,
            responsive: true,
            language: {
                search: '<i class="bi bi-search"></i>',
                searchPlaceholder: 'Search registrations...'
            }
        });
    }
});
</script>

<?php include 'scripts.php'; ?> 