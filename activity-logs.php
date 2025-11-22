<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Activity Logs';
$currentUser = getCurrentUser();

// Get filters from request
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$userFilter = $_GET['user_id'] ?? '';
$actionFilter = $_GET['action'] ?? '';

// Get all users for filter dropdown
$stmt = $pdo->prepare("SELECT id, username, CONCAT(first_name, ' ', last_name) as full_name FROM users ORDER BY full_name");
$stmt->execute();
$users = $stmt->fetchAll();

// Get unique actions for filter dropdown
$stmt = $pdo->prepare("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$stmt->execute();
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Build the activity logs query
$params = [];
$whereConditions = ["DATE(al.created_at) BETWEEN ? AND ?"];
$params[] = $startDate;
$params[] = $endDate;

if ($userFilter) {
    $whereConditions[] = "al.user_id = ?";
    $params[] = $userFilter;
}

if ($actionFilter) {
    $whereConditions[] = "al.action = ?";
    $params[] = $actionFilter;
}

$whereClause = implode(" AND ", $whereConditions);

// Get activity logs with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Create the base query
$query = "
    SELECT 
        al.*,
        CONCAT(u.first_name, ' ', u.last_name) as user_name,
        u.username,
        u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE {$whereClause}
    ORDER BY al.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
";

// Execute the query without adding LIMIT and OFFSET as parameters
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al WHERE {$whereClause}");
$stmt->execute($params);
$totalLogs = $stmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

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
                                <h1 class="h3 mb-2">Activity Logs</h1>
                                <p class="text-muted mb-0">View system activity and audit trail</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" 
                                       value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" 
                                       value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">User</label>
                                <select name="user_id" class="form-select">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo $userFilter == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Action</label>
                                <select name="action" class="form-select">
                                    <option value="">All Actions</option>
                                    <?php foreach ($actions as $action): ?>
                                        <option value="<?php echo $action; ?>" 
                                                <?php echo $actionFilter === $action ? 'selected' : ''; ?>>
                                            <?php echo ucwords(str_replace('_', ' ', $action)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Logs Table -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="logsTable">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?>
                                            </td>
                                            <td>
                                                <?php if ($log['user_id']): ?>
                                                    <div>
                                                        <?php echo htmlspecialchars($log['user_name']); ?>
                                                        <small class="text-muted d-block">
                                                            @<?php echo htmlspecialchars($log['username']); ?>
                                                            <span class="badge bg-secondary"><?php echo ucfirst($log['role']); ?></span>
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo strpos($log['action'], 'create') !== false ? 'success' : 
                                                        (strpos($log['action'], 'update') !== false ? 'info' : 
                                                        (strpos($log['action'], 'delete') !== false ? 'danger' : 'secondary'));
                                                ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $log['action'])); ?>
                                                </span>
                                                <small class="text-muted d-block">
                                                    <?php echo $log['table_affected']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($log['old_values'] || $log['new_values']): ?>
                                                    <button type="button" class="btn btn-sm btn-light" 
                                                            onclick="showLogDetails(<?php echo htmlspecialchars(json_encode([
                                                                'old' => json_decode($log['old_values'], true),
                                                                'new' => json_decode($log['new_values'], true)
                                                            ])); ?>)">
                                                        <i class="bi bi-eye me-1"></i>View Changes
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $log['ip_address']; ?>
                                                    <?php if ($log['user_agent']): ?>
                                                        <i class="bi bi-info-circle ms-1" 
                                                           title="<?php echo htmlspecialchars($log['user_agent']); ?>"></i>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&user_id=<?php echo $userFilter; ?>&action=<?php echo $actionFilter; ?>">
                                            Previous
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&user_id=<?php echo $userFilter; ?>&action=<?php echo $actionFilter; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&user_id=<?php echo $userFilter; ?>&action=<?php echo $actionFilter; ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Old Values</h6>
                        <pre id="oldValues" class="bg-light p-3 rounded"></pre>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">New Values</h6>
                        <pre id="newValues" class="bg-light p-3 rounded"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select filters are now simple HTML selects - no additional initialization needed
});

function showLogDetails(data) {
    document.getElementById('oldValues').textContent = 
        data.old ? JSON.stringify(data.old, null, 2) : 'No old values';
    document.getElementById('newValues').textContent = 
        data.new ? JSON.stringify(data.new, null, 2) : 'No new values';
    new bootstrap.Modal(document.getElementById('logDetailsModal')).show();
}


</script>

<?php include 'scripts.php'; ?> 