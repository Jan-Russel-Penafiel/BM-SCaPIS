<?php
require_once 'config.php';

// Require login and must be purok leader
requireLogin();
if ($_SESSION['role'] !== 'purok_leader') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Resident Applications';
$currentUser = getCurrentUser();

// Only show applications for residents in this purok leader's purok
$params = [$currentUser['purok_id']];
$stmt = $pdo->prepare("
    SELECT a.*, dt.type_name, dt.processing_days,
           u.first_name, u.last_name, u.contact_number,
           p.purok_name,
           CONCAT(pb.first_name, ' ', pb.last_name) as processed_by_name
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN document_types dt ON a.document_type_id = dt.id
    LEFT JOIN puroks p ON u.purok_id = p.id
    LEFT JOIN users pb ON a.processed_by = pb.id
    WHERE u.purok_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
$applications = $stmt->fetchAll();

include 'header.php';
include 'sidebar.php';
?>
<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h1 class="h3 mb-2">Resident Applications</h1>
                        <p class="text-muted mb-0">Applications from residents in your purok</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>All Applications
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="applicationsTable">
                                <thead>
                                    <tr>
                                        <th>Application #</th>
                                        <th>Applicant</th>
                                        <th>Document</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Date Applied</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($app['application_number']); ?></strong>
                                                <?php if ($app['urgency'] === 'Rush'): ?>
                                                    <span class="badge bg-danger ms-1">Rush</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>
                                                        <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                                    </strong>
                                                    <?php if ($app['contact_number']): ?>
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-telephone me-1"></i>
                                                            <?php echo htmlspecialchars($app['contact_number']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($app['type_name']); ?>
                                                <small class="text-muted d-block">
                                                    <?php 
                                                    $purpose = htmlspecialchars($app['purpose']);
                                                    echo strlen($purpose) > 30 ? substr($purpose, 0, 27) . '...' : $purpose;
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge status-<?php echo $app['status']; ?><?php if ($app['status'] === 'ready_for_pickup') echo ' bg-primary text-white fw-bold ready-pickup-badge'; ?>">
                                                    <?php if ($app['status'] === 'ready_for_pickup'): ?>
                                                        <i class="bi bi-check-circle me-1"></i>
                                                    <?php endif; ?>
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $app['payment_status'] === 'paid' ? 'success' : 
                                                        ($app['payment_status'] === 'waived' ? 'info' : 'warning');
                                                ?>">
                                                    <?php echo ucfirst($app['payment_status']); ?>
                                                </span>
                                                <?php if ($app['payment_amount']): ?>
                                                    <small class="d-block">₱<?php echo number_format($app['payment_amount'], 2); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y g:i A', strtotime($app['created_at'])); ?>
                                                <?php if ($app['processed_by_name']): ?>
                                                    <small class="text-muted d-block">
                                                        By: <?php echo htmlspecialchars($app['processed_by_name']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view-application.php?id=<?php echo $app['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#applicationsTable').DataTable({
        order: [[5, 'desc']], // Sort by date applied
        pageLength: 25,
        responsive: true,
        language: {
            search: '<i class="bi bi-search"></i>',
            searchPlaceholder: 'Search applications...'
        }
    });
});
</script>
<?php include 'scripts.php'; ?> 
<style>
.ready-pickup-badge {
    animation: pickupPulse 1.2s infinite alternate;
}
@keyframes pickupPulse {
    0% { box-shadow: 0 0 0 0 rgba(13,110,253,0.5); }
    100% { box-shadow: 0 0 10px 4px rgba(13,110,253,0.3); }
}
</style> 