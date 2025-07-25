<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Manage Purok Leaders';
$currentUser = getCurrentUser();

// Get all puroks with their leaders
$stmt = $pdo->prepare("
    SELECT p.*, 
           CONCAT(u.first_name, ' ', u.last_name) as leader_name,
           u.contact_number as leader_contact,
           u.email as leader_email,
           u.profile_picture as leader_profile,
           u.id as leader_id
    FROM puroks p
    LEFT JOIN users u ON p.purok_leader_id = u.id
    ORDER BY p.purok_name
");
$stmt->execute();
$puroks = $stmt->fetchAll();

// Get eligible residents (approved residents not already assigned as leaders)
$stmt = $pdo->prepare("
    SELECT u.*, p.purok_name 
    FROM users u
    LEFT JOIN puroks p ON u.purok_id = p.id
    WHERE u.role = 'resident' 
    AND u.status = 'approved'
    AND u.id NOT IN (
        SELECT purok_leader_id 
        FROM puroks 
        WHERE purok_leader_id IS NOT NULL
    )
    ORDER BY u.last_name, u.first_name
");
$stmt->execute();
$eligibleResidents = $stmt->fetchAll();
$residentCount = count($eligibleResidents);

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
                                <h1 class="h3 mb-2">Manage Purok Leaders</h1>
                                <p class="text-muted mb-0">Assign and manage purok leaders for each area</p>
                            </div>
                            <a href="create_purok_leader.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Add New Purok Leader
                            </a>
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

        <!-- Puroks Grid -->
        <div class="row g-4">
            <?php foreach ($puroks as $purok): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-geo-alt me-2"></i><?php echo htmlspecialchars($purok['purok_name']); ?>
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-link text-dark p-0" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" onclick="editPurok(<?php echo htmlspecialchars(json_encode($purok)); ?>)">
                                            <i class="bi bi-pencil me-2"></i>Edit Purok
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deletePurok(<?php echo $purok['id']; ?>)">
                                            <i class="bi bi-trash me-2"></i>Delete Purok
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($purok['leader_name']): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <?php if ($purok['leader_profile']): ?>
                                        <img src="uploads/profiles/<?php echo htmlspecialchars($purok['leader_profile']); ?>" 
                                             alt="Leader" class="rounded-circle me-3" width="64" height="64">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 64px; height: 64px;">
                                            <i class="bi bi-person text-white fs-4"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($purok['leader_name']); ?></h6>
                                        <p class="text-muted small mb-0">Purok Leader</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <?php if ($purok['leader_contact']): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-telephone me-2 text-primary"></i>
                                            <span><?php echo htmlspecialchars($purok['leader_contact']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($purok['leader_email']): ?>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-envelope me-2 text-primary"></i>
                                            <span><?php echo htmlspecialchars($purok['leader_email']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        onclick="removeLeader(<?php echo $purok['leader_id']; ?>, '<?php echo htmlspecialchars($purok['leader_name']); ?>')">
                                    <i class="bi bi-person-x me-2"></i>Remove as Leader
                                </button>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="bi bi-person-plus text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <p class="text-muted mb-3">No leader assigned</p>
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="showAssignLeaderModal(<?php echo $purok['id']; ?>)">
                                        <i class="bi bi-person-plus me-2"></i>Assign Leader
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Assign Leader Modal -->
<div class="modal fade" id="assignLeaderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Purok Leader</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="assign-leader.php" method="POST">
                <input type="hidden" name="purok_id" id="assignPurokId">
                <div class="modal-body">
                    <?php if ($residentCount > 0): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Found <?php echo $residentCount; ?> eligible resident<?php echo $residentCount !== 1 ? 's' : ''; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="resident_id" class="form-label">Select Resident <span class="text-danger">*</span></label>
                            <select name="resident_id" id="resident_id" class="form-select" required>
                                <option value="">Choose a resident...</option>
                                <?php foreach ($eligibleResidents as $resident): ?>
                                    <option value="<?php echo $resident['id']; ?>">
                                        <?php 
                                        echo htmlspecialchars(
                                            $resident['last_name'] . ', ' . 
                                            $resident['first_name'] . ' ' . 
                                            ($resident['middle_name'] ? substr($resident['middle_name'], 0, 1) . '.' : '') .
                                            ($resident['suffix'] ? ' ' . $resident['suffix'] : '')
                                        ); 
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Only approved residents who are not currently assigned as purok leaders are shown</div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No eligible residents found. Please ensure there are approved residents who are not already assigned as purok leaders.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" <?php echo $residentCount === 0 ? 'disabled' : ''; ?>>
                        <i class="bi bi-person-plus me-2"></i>Assign Leader
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Purok Modal -->
<div class="modal fade" id="editPurokModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Purok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="edit-purok.php" method="POST">
                <input type="hidden" name="purok_id" id="editPurokId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Purok Name <span class="text-danger">*</span></label>
                        <input type="text" name="purok_name" id="editPurokName" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Purok Modal -->
<div class="modal fade" id="deletePurokModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Purok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this purok? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete-purok.php" method="POST" class="d-inline">
                    <input type="hidden" name="purok_id" id="deletePurokId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Remove Leader Modal -->
<div class="modal fade" id="removeLeaderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Purok Leader</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="removeLeaderForm">
                <input type="hidden" name="user_id" id="removeLeaderId">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Are you sure you want to remove this purok leader? This action will:
                        <ul class="mb-0 mt-2">
                            <li>Remove their purok leader privileges</li>
                            <li>Convert their account to a regular resident</li>
                            <li>Remove them from their assigned purok</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" id="removeRemarks" class="form-control" rows="3" required
                                placeholder="Please provide the reason for removal"></textarea>
                        <div class="form-text">This will be included in the notification to the user.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-person-x me-2"></i>Remove Leader
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap modals
    const modals = {
        assign: new bootstrap.Modal(document.getElementById('assignLeaderModal')),
        edit: new bootstrap.Modal(document.getElementById('editPurokModal')),
        delete: new bootstrap.Modal(document.getElementById('deletePurokModal')),
        remove: new bootstrap.Modal(document.getElementById('removeLeaderModal'))
    };
    window.purokModals = modals;

    // Initialize form handlers
    const removeLeaderForm = document.getElementById('removeLeaderForm');
    if (removeLeaderForm) {
        removeLeaderForm.addEventListener('submit', handleRemoveLeaderSubmit);
    }
});

function showAssignLeaderModal(purokId) {
    document.getElementById('assignPurokId').value = purokId;
    window.purokModals.assign.show();
}

function editPurok(purok) {
    document.getElementById('editPurokId').value = purok.id;
    document.getElementById('editPurokName').value = purok.purok_name;
    window.purokModals.edit.show();
}

function deletePurok(purokId) {
    document.getElementById('deletePurokId').value = purokId;
    window.purokModals.delete.show();
}

function removeLeader(userId, leaderName) {
    document.getElementById('removeLeaderId').value = userId;
    window.purokModals.remove.show();
}

function handleRemoveLeaderSubmit(e) {
    e.preventDefault();
    
    const userId = document.getElementById('removeLeaderId').value;
    const remarks = document.getElementById('removeRemarks').value;
    
    if (!remarks.trim()) {
        alert('Please provide remarks for the removal.');
        return;
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    
    fetch('remove-leader.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=${userId}&remarks=${encodeURIComponent(remarks)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Success', data.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('error', 'Error', data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Error', 'An error occurred while processing your request.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}

function showToast(type, title, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'success' ? 'success' : 'error',
            title: title,
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    } else {
        alert(`${title}: ${message}`);
    }
}
</script>

<?php include 'scripts.php'; ?>
