<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = "Newly Registered Residents";
require_once 'header.php';
require_once 'sidebar.php';

// Add custom styles for content layout
echo '
<style>
    .content-wrapper {
        margin-left: 250px;
        padding: 20px;
        transition: margin-left 0.3s ease;
    }
    @media (max-width: 768px) {
        .content-wrapper {
            margin-left: 0;
        }
        .sidebar.show + .content-wrapper {
            margin-left: 250px;
        }
    }
</style>
';

// Fetch newly registered residents (assuming 'users' table and 'created_at' field)
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, created_at FROM users WHERE status = 'pending' OR status = 'new' ORDER BY created_at DESC");
$stmt->execute();
$new_residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-plus me-2"></i>Newly Registered Residents
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($new_residents)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-3 text-muted">No new registrations found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Date Registered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($new_residents as $resident): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($resident['email']); ?></td>
                                                <td><?php echo htmlspecialchars(date('F d, Y h:i A', strtotime($resident['created_at']))); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
