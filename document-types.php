<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Document Types';
$currentUser = getCurrentUser();

// Get all document types
$stmt = $pdo->prepare("SELECT * FROM document_types ORDER BY type_name");
$stmt->execute();
$documentTypes = $stmt->fetchAll();

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
                                <h1 class="h3 mb-2">Document Types</h1>
                                <p class="text-muted mb-0">Manage available document types and their requirements</p>
                            </div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDocumentTypeModal">
                                <i class="bi bi-plus-lg me-2"></i>Add Document Type
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

        <!-- Document Types Grid -->
        <div class="row g-4">
            <?php foreach ($documentTypes as $type): ?>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-link text-dark p-0" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" onclick="editDocumentType(<?php echo htmlspecialchars(json_encode($type)); ?>)">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item <?php echo $type['is_active'] ? 'text-danger' : 'text-success'; ?>" 
                                                onclick="toggleStatus(<?php echo $type['id']; ?>, <?php echo $type['is_active']; ?>)">
                                            <i class="bi bi-<?php echo $type['is_active'] ? 'x-circle' : 'check-circle'; ?> me-2"></i>
                                            <?php echo $type['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($type['description']); ?></p>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <small class="text-muted d-block">Processing Fee</small>
                                    <strong class="text-primary">₱<?php echo number_format($type['fee'], 2); ?></strong>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">Processing Time</small>
                                    <strong><?php echo $type['processing_days']; ?> working days<br><small class="text-muted">(except holidays)</small></strong>
                                </div>
                            </div>

                            <?php if ($type['requirements']): ?>
                                <div class="mt-3">
                                    <h6 class="mb-2">Requirements:</h6>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach (explode(',', $type['requirements']) as $requirement): ?>
                                            <li class="mb-1">
                                                <i class="bi bi-dot me-2"></i><?php echo htmlspecialchars(trim($requirement)); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="mt-3">
                                <span class="badge bg-<?php echo $type['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $type['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add Document Type Modal -->
<div class="modal fade" id="addDocumentTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Document Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add-document-type.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Document Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="type_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Processing Fee (₱) <span class="text-danger">*</span></label>
                            <input type="number" name="fee" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Processing Days <span class="text-danger">*</span></label>
                            <input type="number" name="processing_days" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requirements</label>
                        <textarea name="requirements" class="form-control" rows="4" 
                                placeholder="Enter requirements separated by commas"></textarea>
                        <small class="text-muted">Example: Valid ID, Cedula, Proof of Residence</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Document Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Document Type Modal -->
<div class="modal fade" id="editDocumentTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Document Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="edit-document-type.php" method="POST">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Document Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="type_name" id="editTypeName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Processing Fee (₱) <span class="text-danger">*</span></label>
                            <input type="number" name="fee" id="editFee" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Processing Days <span class="text-danger">*</span></label>
                            <input type="number" name="processing_days" id="editProcessingDays" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requirements</label>
                        <textarea name="requirements" id="editRequirements" class="form-control" rows="4" 
                                placeholder="Enter requirements separated by commas"></textarea>
                        <small class="text-muted">Example: Valid ID, Cedula, Proof of Residence</small>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any Bootstrap tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
});

function editDocumentType(type) {
    document.getElementById('editId').value = type.id;
    document.getElementById('editTypeName').value = type.type_name;
    document.getElementById('editDescription').value = type.description;
    document.getElementById('editFee').value = type.fee;
    document.getElementById('editProcessingDays').value = type.processing_days;
    document.getElementById('editRequirements').value = type.requirements;
    
    new bootstrap.Modal(document.getElementById('editDocumentTypeModal')).show();
}

function toggleStatus(id, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this document type?`)) {
        window.location.href = `toggle-document-type.php?id=${id}`;
    }
}
</script>

<?php include 'scripts.php'; ?> 