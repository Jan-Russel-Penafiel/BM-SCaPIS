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
<?php include 'scripts.php'; ?> 