<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Statistics';
$currentUser = getCurrentUser();

// Get overall statistics
$stats = [
    'residents' => [
        'total' => 0,
        'male_count' => 0,
        'female_count' => 0,
        'avg_age' => 0
    ],
    'applications' => [
        'total' => 0,
        'pending_count' => 0,
        'processing_count' => 0,
        'completed_count' => 0,
        'rejected_count' => 0,
        'total_revenue' => 0
    ],
    'documents' => [],
    'monthly_trend' => []
];

// Total Residents
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_count,
        SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_count,
        COALESCE(AVG(NULLIF(age, 0)), 0) as avg_age
    FROM users 
    WHERE role = 'resident' AND status = 'approved'
");
$stmt->execute();
$residentStats = $stmt->fetch();
if ($residentStats) {
    $stats['residents'] = $residentStats;
}

// Total Applications with proper null handling
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
        COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing_count,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_count,
        COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) as rejected_count,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN payment_amount ELSE 0 END), 0) as total_revenue
    FROM applications
");
$stmt->execute();
$appStats = $stmt->fetch();
if ($appStats) {
    $stats['applications'] = $appStats;
}

// Document Type Distribution
$stmt = $pdo->prepare("
    SELECT dt.type_name, COUNT(a.id) as count
    FROM document_types dt
    LEFT JOIN applications a ON dt.id = a.document_type_id
    GROUP BY dt.id, dt.type_name
    ORDER BY count DESC
");
$stmt->execute();
$stats['documents'] = $stmt->fetchAll();

// Monthly Application Trend
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM applications
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute();
$stats['monthly_trend'] = $stmt->fetchAll();

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
                        <h1 class="h3 mb-2">System Statistics</h1>
                        <p class="text-muted mb-0">Overview of system metrics and analytics</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <!-- Residents Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2">Total Residents</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['residents']['total']); ?></h3>
                            </div>
                            <div class="icon-shape bg-primary text-white rounded-3">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-between">
                            <small class="text-muted">
                                Male: <?php echo number_format((float)$stats['residents']['male_count']); ?>
                            </small>
                            <small class="text-muted">
                                Female: <?php echo number_format((float)$stats['residents']['female_count']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2">Total Applications</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['applications']['total']); ?></h3>
                            </div>
                            <div class="icon-shape bg-success text-white rounded-3">
                                <i class="bi bi-file-text"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <?php
                                $total = $stats['applications']['total'] ?: 1;
                                $completedPercent = ($stats['applications']['completed_count'] / $total) * 100;
                                $pendingPercent = ($stats['applications']['pending_count'] / $total) * 100;
                                $processingPercent = ($stats['applications']['processing_count'] / $total) * 100;
                                ?>
                                <div class="progress-bar bg-success" style="width: <?php echo $completedPercent; ?>%"></div>
                                <div class="progress-bar bg-warning" style="width: <?php echo $pendingPercent; ?>%"></div>
                                <div class="progress-bar bg-info" style="width: <?php echo $processingPercent; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2">Total Revenue</h6>
                                <h3 class="mb-0">₱<?php echo number_format((float)$stats['applications']['total_revenue'], 2); ?></h3>
                            </div>
                            <div class="icon-shape bg-warning text-white rounded-3">
                                <i class="bi bi-currency-exchange"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-success">
                                <i class="bi bi-arrow-up me-1"></i>
                                <?php 
                                $avgPerApplication = $stats['applications']['total'] > 0 ? 
                                    $stats['applications']['total_revenue'] / $stats['applications']['total'] : 0;
                                echo '₱' . number_format($avgPerApplication, 2);
                                ?> per application
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Age Stats -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2">Average Age</h6>
                                <h3 class="mb-0"><?php echo number_format((float)$stats['residents']['avg_age'], 1); ?></h3>
                            </div>
                            <div class="icon-shape bg-info text-white rounded-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Monthly Trend -->
            <div class="col-xl-8 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Monthly Application Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyTrendChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Document Distribution -->
            <div class="col-xl-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Document Type Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="documentDistributionChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Application Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <canvas id="statusDistributionChart" height="200"></canvas>
                            </div>
                            <div class="col-lg-4">
                                <div class="mt-4">
                                    <?php
                                    // Calculate percentages with proper null handling
                                    $total = max((int)$stats['applications']['total'], 1); // Prevent division by zero
                                    
                                    $statuses = [
                                        'completed' => [
                                            'label' => 'Completed',
                                            'count' => (int)$stats['applications']['completed_count'],
                                            'class' => 'success'
                                        ],
                                        'pending' => [
                                            'label' => 'Pending',
                                            'count' => (int)$stats['applications']['pending_count'],
                                            'class' => 'warning'
                                        ],
                                        'processing' => [
                                            'label' => 'Processing',
                                            'count' => (int)$stats['applications']['processing_count'],
                                            'class' => 'info'
                                        ],
                                        'rejected' => [
                                            'label' => 'Rejected',
                                            'count' => (int)$stats['applications']['rejected_count'],
                                            'class' => 'danger'
                                        ]
                                    ];

                                    foreach ($statuses as $status => $info): 
                                        $percentage = ($info['count'] / $total) * 100;
                                    ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <span class="badge bg-<?php echo $info['class']; ?> me-2">
                                                    <?php echo $info['label']; ?>
                                                </span>
                                                <span class="text-muted">
                                                    <?php echo number_format($info['count']); ?>
                                                </span>
                                            </div>
                                            <span class="text-<?php echo $info['class']; ?>">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-shape {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.icon-shape i {
    font-size: 1.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Chart
    const monthlyData = <?php echo json_encode(array_column($stats['monthly_trend'], 'count')); ?>;
    const monthlyLabels = <?php echo json_encode(array_map(function($item) {
        return date('M Y', strtotime($item['month'] . '-01'));
    }, $stats['monthly_trend'])); ?>;

    new Chart(document.getElementById('monthlyTrendChart'), {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Applications',
                data: monthlyData,
                borderColor: '#2c5aa0',
                backgroundColor: '#2c5aa033',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Document Distribution Chart
    const docLabels = <?php echo json_encode(array_column($stats['documents'], 'type_name')); ?>;
    const docData = <?php echo json_encode(array_column($stats['documents'], 'count')); ?>;

    new Chart(document.getElementById('documentDistributionChart'), {
        type: 'doughnut',
        data: {
            labels: docLabels,
            datasets: [{
                data: docData,
                backgroundColor: [
                    '#2c5aa0', '#3d6db0', '#4d80c0', '#5e93d0', '#6ea6e0', '#7fb9f0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Status Distribution Chart
    const statusData = [
        <?php echo $stats['applications']['completed_count']; ?>,
        <?php echo $stats['applications']['pending_count']; ?>,
        <?php echo $stats['applications']['processing_count']; ?>,
        <?php echo $stats['applications']['rejected_count']; ?>
    ];

    new Chart(document.getElementById('statusDistributionChart'), {
        type: 'bar',
        data: {
            labels: ['Completed', 'Pending', 'Processing', 'Rejected'],
            datasets: [{
                data: statusData,
                backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>

<?php include 'scripts.php'; ?> 