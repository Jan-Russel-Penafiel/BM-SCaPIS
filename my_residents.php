<?php
require_once 'config.php';

// Require login and must be purok leader
requireLogin();
if ($_SESSION['role'] !== 'purok_leader') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'My Residents';
$currentUser = getCurrentUser();

// Only show residents in this purok leader's purok
$params = [$currentUser['purok_id']];
$whereClause = "WHERE u.role = 'resident' AND u.status = 'approved' AND u.purok_id = ?";

$stmt = $pdo->prepare("
    SELECT u.*, 
           p.purok_name,
           CONCAT(pl.first_name, ' ', pl.last_name) as purok_leader_name,
           pl.contact_number as purok_leader_contact,
           pl.email as purok_leader_email
    FROM users u
    LEFT JOIN puroks p ON u.purok_id = p.id
    LEFT JOIN users pl ON p.purok_leader_id = pl.id
    $whereClause
    ORDER BY u.last_name, u.first_name
");
$stmt->execute($params);
$residents = $stmt->fetchAll();

include 'header.php';
include 'sidebar.php';
?>
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2">My Residents</h1>
                                <p class="text-muted mb-0">View and manage residents in your purok</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>Residents List
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="residentsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Purok</th>
                                        <th>Contact</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Civil Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($residents as $resident): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($resident['profile_picture']): ?>
                                                        <img src="uploads/profiles/<?php echo htmlspecialchars($resident['profile_picture']); ?>" 
                                                             alt="Profile" class="rounded-circle me-2" width="32" height="32">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 32px; height: 32px;">
                                                            <i class="bi bi-person text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong>
                                                            <?php 
                                                            echo htmlspecialchars($resident['last_name'] . ', ' . 
                                                                $resident['first_name'] . ' ' . 
                                                                ($resident['middle_name'] ? substr($resident['middle_name'], 0, 1) . '.' : '') .
                                                                ($resident['suffix'] ? ' ' . $resident['suffix'] : '')); 
                                                            ?>
                                                        </strong>
                                                        <?php if ($resident['occupation']): ?>
                                                            <small class="text-muted d-block"><?php echo htmlspecialchars($resident['occupation']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($resident['purok_name']): ?>
                                                    <?php echo htmlspecialchars($resident['purok_name']); ?>
                                                    <?php if ($resident['purok_leader_name']): ?>
                                                        <small class="text-muted d-block">
                                                            Leader: <?php echo htmlspecialchars($resident['purok_leader_name']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($resident['contact_number']): ?>
                                                    <i class="bi bi-telephone me-1"></i>
                                                    <?php echo htmlspecialchars($resident['contact_number']); ?>
                                                <?php endif; ?>
                                                <?php if ($resident['email']): ?>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-envelope me-1"></i>
                                                        <?php echo htmlspecialchars($resident['email']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $resident['age']; ?></td>
                                            <td><?php echo $resident['gender']; ?></td>
                                            <td><?php echo $resident['civil_status']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           onclick="viewResident(<?php echo $resident['id']; ?>)"
                                                           title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
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
<!-- View Resident Modal -->
<div class="modal fade" id="viewResidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resident Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div id="viewProfilePicture" class="mx-auto mb-3" style="width: 120px; height: 120px;"></div>
                    <h4 id="viewResidentName" class="mb-1"></h4>
                    <p id="viewResidentPurok" class="text-muted"></p>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Personal Information</h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Age</dt>
                                    <dd class="col-sm-8" id="viewAge"></dd>
                                    <dt class="col-sm-4">Gender</dt>
                                    <dd class="col-sm-8" id="viewGender"></dd>
                                    <dt class="col-sm-4">Civil Status</dt>
                                    <dd class="col-sm-8" id="viewCivilStatus"></dd>
                                    <dt class="col-sm-4">Birthdate</dt>
                                    <dd class="col-sm-8" id="viewBirthdate"></dd>
                                    <dt class="col-sm-4">Occupation</dt>
                                    <dd class="col-sm-8" id="viewOccupation"></dd>
                                    <dt class="col-sm-4">Monthly Income</dt>
                                    <dd class="col-sm-8" id="viewMonthlyIncome"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-telephone me-2"></i>Contact Information</h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Contact No.</dt>
                                    <dd class="col-sm-8" id="viewContactNumber"></dd>
                                    <dt class="col-sm-4">Email</dt>
                                    <dd class="col-sm-8" id="viewEmail"></dd>
                                    <dt class="col-sm-4">Address</dt>
                                    <dd class="col-sm-8" id="viewAddress"></dd>
                                    <dt class="col-sm-4">Emergency Contact</dt>
                                    <dd class="col-sm-8">
                                        <span id="viewEmergencyName"></span><br>
                                        <small id="viewEmergencyNumber" class="text-muted"></small>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-card-image me-2"></i>Valid IDs</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Front</label>
                                        <div id="viewValidIdFront" class="border rounded p-2 text-center"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Back</label>
                                        <div id="viewValidIdBack" class="border rounded p-2 text-center"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
let currentResidentId = null;
function viewResident(residentId) {
    currentResidentId = residentId;
    $.ajax({
        url: 'ajax/get-resident-details.php',
        type: 'GET',
        data: { id: residentId },
        success: function(response) {
            if (!response.success) {
                alert(response.message || 'Error loading resident details.');
                return;
            }
            const resident = response.data;
            // Profile picture
            const profileContainer = document.getElementById('viewProfilePicture');
            if (resident.profile_picture) {
                profileContainer.innerHTML = `<img src="uploads/profiles/${resident.profile_picture}" class="rounded-circle img-fluid" alt="Profile Picture">`;
            } else {
                profileContainer.innerHTML = `<div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;"><i class="bi bi-person text-white" style="font-size: 3rem;"></i></div>`;
            }
            document.getElementById('viewResidentName').textContent = `${resident.first_name} ${resident.middle_name ? resident.middle_name + ' ' : ''}${resident.last_name}${resident.suffix ? ' ' + resident.suffix : ''}`;
            document.getElementById('viewResidentPurok').textContent = resident.purok_name || 'No Purok Assigned';
            document.getElementById('viewAge').textContent = resident.age;
            document.getElementById('viewGender').textContent = resident.gender;
            document.getElementById('viewCivilStatus').textContent = resident.civil_status;
            document.getElementById('viewBirthdate').textContent = new Date(resident.birthdate).toLocaleDateString();
            document.getElementById('viewOccupation').textContent = resident.occupation || 'Not specified';
            document.getElementById('viewMonthlyIncome').textContent = resident.monthly_income ? `₱${parseFloat(resident.monthly_income).toLocaleString(undefined, {minimumFractionDigits: 2})}` : 'Not specified';
            document.getElementById('viewContactNumber').textContent = resident.contact_number || 'Not provided';
            document.getElementById('viewEmail').textContent = resident.email || 'Not provided';
            document.getElementById('viewAddress').textContent = resident.address || 'Not provided';
            document.getElementById('viewEmergencyName').textContent = resident.emergency_contact_name || 'Not provided';
            document.getElementById('viewEmergencyNumber').textContent = resident.emergency_contact_number || '';
            // Valid IDs
            const frontIdContainer = document.getElementById('viewValidIdFront');
            const backIdContainer = document.getElementById('viewValidIdBack');
            function renderValidId(container, filename, label) {
                if (!filename) {
                    container.innerHTML = `<p class='text-muted mb-0'>No ${label} ID uploaded</p>`;
                    return;
                }
                const ext = filename.split('.').pop().toLowerCase();
                if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
                    container.innerHTML = `<img src='uploads/ids/${filename}' class='img-fluid rounded border' alt='${label} ID' style='max-width: 220px;'>`;
                } else if (ext === "pdf") {
                    container.innerHTML = `<a href='uploads/ids/${filename}' target='_blank' class='btn btn-outline-primary'><i class='bi bi-file-earmark-pdf me-2'></i>View ${label} (PDF)</a>`;
                } else {
                    container.innerHTML = `<a href='uploads/ids/${filename}' target='_blank' class='btn btn-outline-secondary'>Download ${label} ID</a>`;
                }
            }
            renderValidId(frontIdContainer, resident.valid_id_front, 'Front');
            renderValidId(backIdContainer, resident.valid_id_back, 'Back');
            new bootstrap.Modal(document.getElementById('viewResidentModal')).show();
        },
        error: function(xhr, status, error) {
            alert('Failed to load resident details.');
        }
    });
}
</script>
<?php include 'scripts.php'; ?> 