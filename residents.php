<?php
require_once 'config.php';

// Require login and must be admin or purok leader
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'purok_leader'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Manage Residents';
$currentUser = getCurrentUser();

// Get residents based on role
$params = [];
$whereClause = "WHERE u.role = 'resident' AND u.status = 'approved'";

// Get current user's purok information
$stmt = $pdo->prepare("
    SELECT u.*, p.purok_name 
    FROM users u 
    LEFT JOIN puroks p ON u.purok_id = p.id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SESSION['role'] === 'purok_leader') {
    $whereClause .= " AND u.purok_id = ?";
    $params[] = $currentUser['purok_id'];
}

// Get residents with their purok information
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

// Get puroks for filter/assignment
$stmt = $pdo->prepare("SELECT * FROM puroks ORDER BY purok_name");
$stmt->execute();
$puroks = $stmt->fetchAll();

// Get residents eligible for purok assignment (approved residents without a purok or with different purok)
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.middle_name, u.last_name, u.suffix, p.purok_name
    FROM users u
    LEFT JOIN puroks p ON u.purok_id = p.id
    WHERE u.role = 'resident' 
    AND u.purok_leader_approval = 'approved' 
    AND u.admin_approval = 'approved'
    ORDER BY u.last_name, u.first_name
");
$stmt->execute();
$eligibleResidents = $stmt->fetchAll();

// Debug output
echo "<!-- Debug: Found " . count($residents) . " approved residents -->";

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
                                <h1 class="h3 mb-2">Manage Residents</h1>
                                <p class="text-muted mb-0">
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        View and manage all registered residents
                                    <?php else: ?>
                                        View and manage residents 
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <div>
                                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#assignPurokModal">
                                        <i class="bi bi-people me-2"></i>Assign to Purok
                                    </button>
                                    <a href="export-residents.php" class="btn btn-success">
                                        <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                                    </a>
                                </div>
                            <?php endif; ?>
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

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Residents</h6>
                                <h3 class="mb-0"><?php echo count($residents); ?></h3>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-people" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Male</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    echo count(array_filter($residents, function($r) {
                                        return $r['gender'] === 'Male';
                                    }));
                                    ?>
                                </h3>
                            </div>
                            <div class="text-info">
                                <i class="bi bi-gender-male" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Female</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    echo count(array_filter($residents, function($r) {
                                        return $r['gender'] === 'Female';
                                    }));
                                    ?>
                                </h3>
                            </div>
                            <div class="text-danger">
                                <i class="bi bi-gender-female" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Average Age</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    $ages = array_column($residents, 'age');
                                    echo $ages ? round(array_sum($ages) / count($ages)) : 0;
                                    ?>
                                </h3>
                            </div>
                            <div class="text-success">
                                <i class="bi bi-calendar" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Residents List -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>Residents List
                            <?php if ($_SESSION['role'] === 'purok_leader'): ?>
                               
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
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
                                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                                        <button type="button" 
                                                               class="btn btn-sm btn-outline-secondary"
                                                               onclick="editResident(<?php echo $resident['id']; ?>)"
                                                               title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="confirmDelete(<?php echo $resident['id']; ?>)"
                                                                title="Delete">
                                                            <i class="bi bi-trash"></i>
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

<!-- Assign Purok Modal -->
<div class="modal fade" id="assignPurokModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Residents to Purok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="assign-purok.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Purok <span class="text-danger">*</span></label>
                        <select name="purok_id" id="assignPurokSelect" class="form-select" required>
                            <option value="">Choose a purok...</option>
                            <?php foreach ($puroks as $purok): ?>
                                <option value="<?php echo $purok['id']; ?>">
                                    <?php echo htmlspecialchars($purok['purok_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Residents <span class="text-danger">*</span></label>
                        <select name="resident_ids[]" id="assignResidentsSelect" class="form-select" multiple required>
                            <?php foreach ($eligibleResidents as $resident): ?>
                                <option value="<?php echo $resident['id']; ?>" 
                                        <?php echo $resident['purok_name'] ? 'data-current-purok="' . htmlspecialchars($resident['purok_name']) . '"' : ''; ?>>
                                    <?php 
                                    echo htmlspecialchars(
                                        $resident['last_name'] . ', ' . 
                                        $resident['first_name'] . ' ' . 
                                        ($resident['middle_name'] ? substr($resident['middle_name'], 0, 1) . '.' : '') .
                                        ($resident['suffix'] ? ' ' . $resident['suffix'] : '') .
                                        ($resident['purok_name'] ? ' (Current: ' . $resident['purok_name'] . ')' : ' (No Purok)')
                                    ); 
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">You can select multiple residents. Current purok assignments are shown in parentheses.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign to Purok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Resident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this resident? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete-resident.php" method="POST" class="d-inline">
                    <input type="hidden" name="resident_id" id="deleteResidentId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
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
                    <div id="viewProfilePicture" class="mx-auto mb-3" style="width: 120px; height: 120px;">
                        <!-- Profile picture will be set via JavaScript -->
                    </div>
                    <h4 id="viewResidentName" class="mb-1"></h4>
                    <p id="viewResidentPurok" class="text-muted"></p>
                </div>

                <div class="row g-4">
                    <!-- Personal Information -->
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

                    <!-- Contact Information -->
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

                    <!-- Valid IDs -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-card-image me-2"></i>Valid IDs</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Front</label>
                                        <div id="viewValidIdFront" class="border rounded p-2 text-center">
                                            <!-- ID front image will be set via JavaScript -->
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Back</label>
                                        <div id="viewValidIdBack" class="border rounded p-2 text-center">
                                            <!-- ID back image will be set via JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button type="button" class="btn btn-primary" onclick="editResident(currentResidentId)">
                        <i class="bi bi-pencil me-2"></i>Edit Resident
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Resident Modal -->
<div class="modal fade" id="editResidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Resident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editResidentForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="resident_id" id="editResidentId">
                <div class="modal-body">
                    <!-- Personal Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" id="editMiddleName" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" id="editLastName" class="form-control" required>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Suffix</label>
                                    <input type="text" name="suffix" id="editSuffix" class="form-control">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Birthdate <span class="text-danger">*</span></label>
                                    <input type="date" name="birthdate" id="editBirthdate" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" id="editGender" class="form-select" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                                    <select name="civil_status" id="editCivilStatus" class="form-select" required>
                                        <option value="">Select Status</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-telephone me-2"></i>Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number</label>
                                    <input type="tel" name="contact_number" id="editContactNumber" class="form-control" placeholder="09XXXXXXXXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" id="editEmail" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" id="editAddress" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Additional Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Occupation</label>
                                    <input type="text" name="occupation" id="editOccupation" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Monthly Income</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="monthly_income" id="editMonthlyIncome" class="form-control" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Emergency Contact</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Contact Name</label>
                                    <input type="text" name="emergency_contact_name" id="editEmergencyName" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number</label>
                                    <input type="tel" name="emergency_contact_number" id="editEmergencyNumber" class="form-control" placeholder="09XXXXXXXXX">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    $('#residentsTable').DataTable({
        paging: false,
        info: false,
        responsive: true,
        language: {
            search: '<i class="bi bi-search"></i>',
            searchPlaceholder: 'Search residents...'
        }
    });

    // Initialize Select2 for purok select
    $('#assignPurokSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#assignPurokModal'),
        placeholder: 'Choose a purok...',
        allowClear: true
    });

    // Initialize Select2 for residents multiple select with custom templates
    $('#assignResidentsSelect').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#assignPurokModal'),
        placeholder: 'Select residents to assign...',
        allowClear: true,
        templateResult: function(data) {
            if (data.loading) return data.text;
            if (!data.id) return data.text;
            
            return $(`
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <img src="${data.profile_picture ? 'uploads/profiles/' + data.profile_picture : 'assets/images/default-avatar.png'}" 
                             class="rounded-circle" width="32" height="32" alt="Profile">
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <div class="fw-bold">${data.text}</div>
                        <small class="text-muted">${data.purok_name || 'No purok assigned'}</small>
                    </div>
                </div>
            `);
        },
        templateSelection: function(data) {
            if (!data.id) return data.text;
            return data.text;
        }
    });
    
    // Phone number formatting
    document.getElementById('editContactNumber').addEventListener('input', function(e) {
        formatPhoneNumber(e.target);
    });
    
    document.getElementById('editEmergencyNumber').addEventListener('input', function(e) {
        formatPhoneNumber(e.target);
    });
});

function formatResidentOption(resident) {
    if (!resident.id) return resident.text;
    
    var $resident = $(resident.element);
    var currentPurok = $resident.data('current-purok');
    
    var $container = $(
        '<div class="d-flex justify-content-between align-items-center">' +
            '<div>' +
                '<strong>' + resident.text.split('(Current:')[0].trim() + '</strong>' +
                (currentPurok ? '<br><small class="text-muted">Currently in: ' + currentPurok + '</small>' : '') +
            '</div>' +
        '</div>'
    );
    
    return $container;
}

function formatResidentSelection(resident) {
    if (!resident.id) return resident.text;
    return resident.text.split('(Current:')[0].trim();
}

function confirmDelete(residentId) {
    document.getElementById('deleteResidentId').value = residentId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

let currentResidentId = null;

function viewResident(residentId) {
    // Store the current resident ID
    currentResidentId = residentId;
    
    // Make an AJAX request to get resident data
    $.ajax({
        url: 'ajax/get-resident-details.php',
        type: 'GET',
        data: { id: residentId },
        success: function(response) {
            if (!response.success) {
                showToast('error', 'Error', response.message);
                return;
            }

            const resident = response.data;
            
            // Set profile picture
            const profileContainer = document.getElementById('viewProfilePicture');
            if (resident.profile_picture) {
                profileContainer.innerHTML = `<img src="uploads/profiles/${resident.profile_picture}" 
                    class="rounded-circle img-fluid" alt="Profile Picture">`;
            } else {
                profileContainer.innerHTML = `<div class="bg-secondary rounded-circle d-flex align-items-center 
                    justify-content-center" style="width: 120px; height: 120px;">
                    <i class="bi bi-person text-white" style="font-size: 3rem;"></i>
                </div>`;
            }

            // Set resident name and purok
            document.getElementById('viewResidentName').textContent = 
                `${resident.first_name} ${resident.middle_name ? resident.middle_name + ' ' : ''}${resident.last_name}${resident.suffix ? ' ' + resident.suffix : ''}`;
            document.getElementById('viewResidentPurok').textContent = resident.purok_name || 'No Purok Assigned';

            // Set personal information
            document.getElementById('viewAge').textContent = resident.age;
            document.getElementById('viewGender').textContent = resident.gender;
            document.getElementById('viewCivilStatus').textContent = resident.civil_status;
            document.getElementById('viewBirthdate').textContent = new Date(resident.birthdate).toLocaleDateString();
            document.getElementById('viewOccupation').textContent = resident.occupation || 'Not specified';
            document.getElementById('viewMonthlyIncome').textContent = resident.monthly_income ? 
                `₱${parseFloat(resident.monthly_income).toLocaleString(undefined, {minimumFractionDigits: 2})}` : 
                'Not specified';

            // Set contact information
            document.getElementById('viewContactNumber').textContent = resident.contact_number || 'Not provided';
            document.getElementById('viewEmail').textContent = resident.email || 'Not provided';
            document.getElementById('viewAddress').textContent = resident.address || 'Not provided';
            document.getElementById('viewEmergencyName').textContent = resident.emergency_contact_name || 'Not provided';
            document.getElementById('viewEmergencyNumber').textContent = resident.emergency_contact_number || '';

            // Set valid IDs
            const frontIdContainer = document.getElementById('viewValidIdFront');
            const backIdContainer = document.getElementById('viewValidIdBack');

            function renderValidId(container, filename, label) {
                if (!filename) {
                    container.innerHTML = `<p class="text-muted mb-0">No ${label} ID uploaded</p>`;
                    return;
                }
                const ext = filename.split('.').pop().toLowerCase();
                if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
                    container.innerHTML = `<img src="uploads/ids/${filename}" class="img-fluid rounded border" alt="${label} ID" style="max-width: 220px;">`;
                } else if (ext === "pdf") {
                    container.innerHTML = `<a href="uploads/ids/${filename}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-2"></i>View ${label} (PDF)</a>`;
                } else {
                    container.innerHTML = `<a href="uploads/ids/${filename}" target="_blank" class="btn btn-outline-secondary">Download ${label} ID</a>`;
                }
            }
            renderValidId(frontIdContainer, resident.valid_id_front, 'Front');
            renderValidId(backIdContainer, resident.valid_id_back, 'Back');

            // Show the modal
            new bootstrap.Modal(document.getElementById('viewResidentModal')).show();
        },
        error: function(xhr, status, error) {
            showToast('error', 'Error', 'Failed to load resident details');
            console.error('Error:', error);
        }
    });
}

function editResident(residentId) {
    if (!residentId) return;
    
    // Hide view modal if open
    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewResidentModal'));
    if (viewModal) viewModal.hide();
    
    // Make an AJAX request to get resident data
    $.ajax({
        url: 'ajax/get-resident-details.php',
        type: 'GET',
        data: { id: residentId },
        success: function(response) {
            if (!response.success) {
                showToast('error', 'Error', response.message);
                return;
            }

            const resident = response.data;
            
            // Set form values
            document.getElementById('editResidentId').value = resident.id;
            document.getElementById('editFirstName').value = resident.first_name;
            document.getElementById('editMiddleName').value = resident.middle_name || '';
            document.getElementById('editLastName').value = resident.last_name;
            document.getElementById('editSuffix').value = resident.suffix || '';
            document.getElementById('editBirthdate').value = resident.birthdate;
            document.getElementById('editGender').value = resident.gender;
            document.getElementById('editCivilStatus').value = resident.civil_status;
            document.getElementById('editContactNumber').value = resident.contact_number || '';
            document.getElementById('editEmail').value = resident.email || '';
            document.getElementById('editAddress').value = resident.address || '';
            document.getElementById('editOccupation').value = resident.occupation || '';
            document.getElementById('editMonthlyIncome').value = resident.monthly_income || '';
            document.getElementById('editEmergencyName').value = resident.emergency_contact_name || '';
            document.getElementById('editEmergencyNumber').value = resident.emergency_contact_number || '';

            // Show the modal
            new bootstrap.Modal(document.getElementById('editResidentModal')).show();
        },
        error: function(xhr, status, error) {
            showToast('error', 'Error', 'Failed to load resident details for editing');
            console.error('Error:', error);
        }
    });
}

// Handle edit form submission
$('#editResidentForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Show loading state
    const submitBtn = $(this).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
    
    $.ajax({
        url: 'ajax/update-resident.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showToast('success', 'Success', response.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('error', 'Error', response.message);
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        },
        error: function(xhr, status, error) {
            showToast('error', 'Error', 'An error occurred while saving changes');
            console.error('Error:', error);
            submitBtn.prop('disabled', false).html(originalBtnText);
        }
    });
});

// Handle delete form submission
$('#deleteModal form').on('submit', function(e) {
    e.preventDefault();
    
    const residentId = $('#deleteResidentId').val();
    const submitBtn = $(this).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();
    
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');
    
    $.ajax({
        url: 'ajax/delete-resident.php',
        type: 'POST',
        data: { resident_id: residentId },
        success: function(response) {
            if (response.success) {
                showToast('success', 'Success', response.message);
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('error', 'Error', response.message);
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        },
        error: function(xhr, status, error) {
            showToast('error', 'Error', 'An error occurred while deleting the resident');
            console.error('Error:', error);
            submitBtn.prop('disabled', false).html(originalBtnText);
        }
    });
});

// Function to show toast notifications
function showToast(type, title, message) {
    // You can implement this based on your preferred toast library
    // For example, using Bootstrap's toast:
    const toast = new bootstrap.Toast(document.createElement('div'));
    toast._element.classList.add('toast', 'position-fixed', 'bottom-0', 'end-0', 'm-3');
    toast._element.innerHTML = `
        <div class="toast-header bg-${type === 'success' ? 'success' : 'danger'} text-white">
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    document.body.appendChild(toast._element);
    toast.show();
    
    // Remove toast after it's hidden
    toast._element.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}
</script>

<style>
.ready-pickup-badge {
    animation: pickupPulse 1.2s infinite alternate;
}
@keyframes pickupPulse {
    0% { box-shadow: 0 0 0 0 rgba(13,110,253,0.5); }
    100% { box-shadow: 0 0 10px 4px rgba(13,110,253,0.3); }
}

.table-container {
    overflow: hidden;
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

/* Specific column widths */
.table-container th:nth-child(1) { width: 25%; }
.table-container th:nth-child(2) { width: 15%; }
.table-container th:nth-child(3) { width: 20%; }
.table-container th:nth-child(4) { width: 8%; }
.table-container th:nth-child(5) { width: 10%; }
.table-container th:nth-child(6) { width: 12%; }
.table-container th:nth-child(7) { width: 10%; }

.table-container td:nth-child(1),
.table-container td:nth-child(2),
.table-container td:nth-child(3) {
    white-space: normal;
}
</style>

<?php include 'scripts.php'; ?> 