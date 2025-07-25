<?php
require_once 'config.php';

// Require login and must be a resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Track Application';
$currentUser = getCurrentUser();

$application = null;
$history = null;
$error = null;

// Handle search
if (isset($_GET['application_number']) && !empty($_GET['application_number'])) {
    $stmt = $pdo->prepare("
        SELECT a.*, dt.type_name, dt.processing_days,
               u.first_name, u.last_name
        FROM applications a
        JOIN document_types dt ON a.document_type_id = dt.id
        JOIN users u ON a.user_id = u.id
        WHERE a.application_number = ? AND a.user_id = ?
    ");
    $stmt->execute([$_GET['application_number'], $_SESSION['user_id']]);
    $application = $stmt->fetch();

    if ($application) {
        // Get application history
        $stmt = $pdo->prepare("
            SELECT ah.*, u.first_name, u.last_name
            FROM application_history ah
            LEFT JOIN users u ON ah.changed_by = u.id
            WHERE ah.application_id = ?
            ORDER BY ah.created_at ASC
        ");
        $stmt->execute([$application['id']]);
        $history = $stmt->fetchAll();
    } else {
        $error = 'Application not found. Please check the application number and try again.';
    }
}

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
                        <h1 class="h3 mb-2">Track Application</h1>
                        <p class="text-muted mb-0">Enter your application number to track its status</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Form -->
        <div class="row mb-4">
            <div class="col-lg-8 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-9">
                                <label class="form-label">Application Number</label>
                                <input type="text" name="application_number" 
                                       class="form-control form-control-lg" 
                                       placeholder="Enter application number (e.g., APP-20240101-1234)"
                                       value="<?php echo isset($_GET['application_number']) ? htmlspecialchars($_GET['application_number']) : ''; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-search me-2"></i>Track
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($application): ?>
            <!-- Application Details -->
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-file-earmark-text me-2"></i>Application Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Application Number</small>
                                <strong><?php echo htmlspecialchars($application['application_number']); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Document Type</small>
                                <strong><?php echo htmlspecialchars($application['type_name']); ?></strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Purpose</small>
                                <p class="mb-0"><?php echo htmlspecialchars($application['purpose']); ?></p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Processing Type</small>
                                <span class="badge bg-info"><?php echo $application['urgency']; ?></span>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Status</small>
                                <span class="badge status-<?php echo $application['status']; ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Payment Status</small>
                                <span class="badge bg-<?php 
                                    echo $application['payment_status'] === 'paid' ? 'success' : 
                                        ($application['payment_status'] === 'waived' ? 'info' : 'warning');
                                ?>">
                                    <?php echo ucfirst($application['payment_status']); ?>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Date Applied</small>
                                <strong><?php echo date('F j, Y g:i A', strtotime($application['created_at'])); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <!-- Progress Timeline -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history me-2"></i>Application Progress
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php
                                $statuses = [
                                    'pending' => [
                                        'icon' => 'bi-file-earmark-text',
                                        'title' => 'Application Submitted',
                                        'desc' => 'Your application has been received and is pending review.'
                                    ],
                                    'processing' => [
                                        'icon' => 'bi-gear',
                                        'title' => 'Processing',
                                        'desc' => 'Your application is being processed.'
                                    ],
                                    'ready_for_pickup' => [
                                        'icon' => 'bi-check-circle',
                                        'title' => 'Ready for Pickup',
                                        'desc' => 'Your document is ready for pickup.'
                                    ],
                                    'completed' => [
                                        'icon' => 'bi-trophy',
                                        'title' => 'Completed',
                                        'desc' => 'Your document has been released.'
                                    ],
                                    'rejected' => [
                                        'icon' => 'bi-x-circle',
                                        'title' => 'Rejected',
                                        'desc' => 'Your application has been rejected.'
                                    ]
                                ];

                                $currentFound = false;
                                foreach ($statuses as $status => $info):
                                    $isCurrent = $application['status'] === $status;
                                    $isPast = !$currentFound && !$isCurrent;
                                    if ($isCurrent) $currentFound = true;
                                ?>
                                    <div class="timeline-item">
                                        <div class="timeline-icon <?php echo $isPast ? 'bg-success' : ($isCurrent ? 'bg-primary' : ''); ?>">
                                            <i class="bi <?php echo $info['icon']; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1"><?php echo $info['title']; ?></h6>
                                            <p class="mb-0 text-muted"><?php echo $info['desc']; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Application History -->
                    <?php if ($history): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history me-2"></i>Application History
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php foreach ($history as $item): ?>
                                        <div class="timeline-item">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0">
                                                    <i class="bi bi-circle-fill text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1"><?php echo ucfirst($item['status']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <?php if ($item['remarks']): ?>
                                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars($item['remarks']); ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($item['first_name']): ?>
                                                        <small class="text-muted">
                                                            By: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Timeline styles */
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 50px;
    margin-bottom: 30px;
}

.timeline-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.timeline-icon i {
    font-size: 1rem;
}

.timeline-content {
    position: relative;
}

.timeline-content::before {
    content: '';
    position: absolute;
    left: -30px;
    top: 18px;
    width: 20px;
    height: 2px;
    background-color: #e9ecef;
}

.bg-success .bi,
.bg-primary .bi {
    color: white;
}
</style>

<?php include 'scripts.php'; ?> 