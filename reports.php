<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Reports';
$currentUser = getCurrentUser();

// Get report types
$reportTypes = [
    'applications_status' => 'Applications by Status',
    'clearances_summary' => 'Clearances and Permits Summary',
    'transaction_logs' => 'Transaction Logs',
    'applications_by_zone' => 'Applications by Zone/Purok',
    'residents_demographics' => 'Residents Demographics'
];

// Get date range from request
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$reportType = $_GET['type'] ?? 'applications_status';
$timeframe = $_GET['timeframe'] ?? 'daily'; // daily, weekly, monthly

// Initialize report data
$reportData = [];
$chartData = [];

// Generate report based on type
switch ($reportType) {
    case 'applications_status':
        // Get applications by status
        $stmt = $pdo->prepare("
            SELECT 
                a.status,
                a.created_at as date,
                a.purpose,
                dt.type_name as document_type,
                CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                p.purok_name,
                COUNT(*) as count
            FROM applications a
            JOIN users u ON a.user_id = u.id
            JOIN document_types dt ON a.document_type_id = dt.id
            LEFT JOIN puroks p ON u.purok_id = p.id
            WHERE DATE(a.created_at) BETWEEN ? AND ?
            GROUP BY a.status, DATE(a.created_at), a.purpose, dt.type_name, u.id, p.purok_name
            ORDER BY date DESC, status
        ");
        $stmt->execute([$startDate, $endDate]);
        $reportData = $stmt->fetchAll();

        // Prepare chart data
        $statusData = [];
        foreach ($reportData as $row) {
            $date = date('M j', strtotime($row['date']));
            if (!isset($statusData[$date])) {
                $statusData[$date] = [
                    'pending' => 0,
                    'processing' => 0,
                    'ready_for_pickup' => 0,
                    'completed' => 0,
                    'rejected' => 0
                ];
            }
            $statusData[$date][$row['status']] += (int)$row['count'];
        }
        
        $chartData = [
            'labels' => array_keys($statusData),
            'datasets' => [
                'pending' => array_values(array_column($statusData, 'pending')),
                'processing' => array_values(array_column($statusData, 'processing')),
                'ready_for_pickup' => array_values(array_column($statusData, 'ready_for_pickup')),
                'completed' => array_values(array_column($statusData, 'completed')),
                'rejected' => array_values(array_column($statusData, 'rejected'))
            ]
        ];
        break;

    case 'clearances_summary':
        // Get summary of issued clearances and permits
        $stmt = $pdo->prepare("
            SELECT 
                dt.type_name,
                a.status,
                COUNT(*) as total_count,
                SUM(CASE WHEN a.payment_status = 'paid' THEN a.payment_amount ELSE 0 END) as total_revenue,
                DATE(a.created_at) as date
            FROM applications a
            JOIN document_types dt ON a.document_type_id = dt.id
            WHERE DATE(a.created_at) BETWEEN ? AND ?
            GROUP BY dt.type_name, a.status, DATE(a.created_at)
            ORDER BY date DESC, dt.type_name
        ");
        $stmt->execute([$startDate, $endDate]);
        $reportData = $stmt->fetchAll();

        // Prepare chart data
        $typeData = [];
        foreach ($reportData as $row) {
            if (!isset($typeData[$row['type_name']])) {
                $typeData[$row['type_name']] = ['issued' => 0, 'revenue' => 0];
            }
            if ($row['status'] === 'ready_for_pickup' || $row['status'] === 'completed') {
                $typeData[$row['type_name']]['issued'] += (int)$row['total_count'];
            }
            $typeData[$row['type_name']]['revenue'] += (float)$row['total_revenue'];
        }
        
        $chartData = [
            'labels' => array_keys($typeData),
            'datasets' => [
                'issued' => array_values(array_column($typeData, 'issued')),
                'revenue' => array_values(array_column($typeData, 'revenue'))
            ]
        ];
        break;

    case 'transaction_logs':
        // Get transaction logs based on timeframe
        $groupBy = "DATE(created_at)"; // daily
        if ($timeframe === 'weekly') {
            $groupBy = "YEARWEEK(created_at)";
        } else if ($timeframe === 'monthly') {
            $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
        }

        $stmt = $pdo->prepare("
            SELECT 
                {$groupBy} as period,
                action,
                COUNT(*) as count,
                GROUP_CONCAT(DISTINCT table_affected) as affected_tables
            FROM activity_logs
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY period, action
            ORDER BY period DESC, action
        ");
        $stmt->execute([$startDate, $endDate]);
        $reportData = $stmt->fetchAll();

        // Prepare chart data
        $periodData = [];
        foreach ($reportData as $row) {
            if (!isset($periodData[$row['period']])) {
                $periodData[$row['period']] = 0;
            }
            $periodData[$row['period']] += (int)$row['count'];
        }
        
        $chartData = [
            'labels' => array_keys($periodData),
            'datasets' => [
                'transactions' => array_values($periodData)
            ]
        ];
        break;

    case 'applications_by_zone':
        // Get applications per barangay zone/purok
        $stmt = $pdo->prepare("
            SELECT 
                p.purok_name,
                COUNT(*) as total_applications,
                SUM(CASE WHEN a.status = 'ready_for_pickup' OR a.status = 'completed' THEN 1 ELSE 0 END) as completed_applications,
                SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
                dt.type_name as document_type
            FROM applications a
            JOIN users u ON a.user_id = u.id
            JOIN puroks p ON u.purok_id = p.id
            JOIN document_types dt ON a.document_type_id = dt.id
            WHERE DATE(a.created_at) BETWEEN ? AND ?
            GROUP BY p.purok_name, dt.type_name
            ORDER BY p.purok_name, dt.type_name
        ");
        $stmt->execute([$startDate, $endDate]);
        $reportData = $stmt->fetchAll();

        // Prepare chart data
        $zoneData = [];
        foreach ($reportData as $row) {
            if (!isset($zoneData[$row['purok_name']])) {
                $zoneData[$row['purok_name']] = ['total' => 0, 'completed' => 0, 'pending' => 0];
            }
            $zoneData[$row['purok_name']]['total'] += (int)$row['total_applications'];
            $zoneData[$row['purok_name']]['completed'] += (int)$row['completed_applications'];
            $zoneData[$row['purok_name']]['pending'] += (int)$row['pending_applications'];
        }
        
        $chartData = [
            'labels' => array_keys($zoneData),
            'datasets' => [
                'total' => array_values(array_column($zoneData, 'total')),
                'completed' => array_values(array_column($zoneData, 'completed')),
                'pending' => array_values(array_column($zoneData, 'pending'))
            ]
        ];
        break;

    case 'residents_demographics':
        // Get residents per purok with demographics
        $stmt = $pdo->prepare("
            SELECT 
                p.purok_name,
                COUNT(DISTINCT u.id) as total_residents,
                SUM(CASE WHEN u.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN u.gender = 'Female' THEN 1 ELSE 0 END) as female_count,
                ROUND(AVG(CASE WHEN u.age > 0 THEN u.age ELSE NULL END), 1) as average_age,
                SUM(CASE WHEN u.civil_status = 'Single' THEN 1 ELSE 0 END) as single_count,
                SUM(CASE WHEN u.civil_status = 'Married' THEN 1 ELSE 0 END) as married_count,
                SUM(CASE WHEN u.civil_status = 'Widowed' THEN 1 ELSE 0 END) as widowed_count,
                SUM(CASE WHEN u.civil_status = 'Divorced' THEN 1 ELSE 0 END) as divorced_count,
                SUM(CASE WHEN u.occupation IS NOT NULL AND u.occupation != '' THEN 1 ELSE 0 END) as employed_count,
                ROUND(AVG(CASE WHEN u.monthly_income > 0 THEN u.monthly_income ELSE NULL END), 2) as average_income,
                COUNT(CASE WHEN u.age < 18 THEN 1 END) as minors,
                COUNT(CASE WHEN u.age BETWEEN 18 AND 30 THEN 1 END) as young_adults,
                COUNT(CASE WHEN u.age BETWEEN 31 AND 60 THEN 1 END) as adults,
                COUNT(CASE WHEN u.age > 60 THEN 1 END) as seniors,
                GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') as resident_names
            FROM puroks p
            LEFT JOIN users u ON u.purok_id = p.id 
            WHERE u.role = 'resident' 
            AND u.status = 'approved'
            AND u.purok_leader_approval = 'approved' 
            AND u.admin_approval = 'approved'
            GROUP BY p.id, p.purok_name
            ORDER BY p.purok_name
        ");
        $stmt->execute();
        $reportData = $stmt->fetchAll();

        // Prepare chart data
        $chartData = [
            'labels' => array_column($reportData, 'purok_name'),
            'datasets' => [
                'demographics' => [
                'male' => array_column($reportData, 'male_count'),
                'female' => array_column($reportData, 'female_count')
                ],
                'age_groups' => [
                    'minors' => array_column($reportData, 'minors'),
                    'young_adults' => array_column($reportData, 'young_adults'),
                    'adults' => array_column($reportData, 'adults'),
                    'seniors' => array_column($reportData, 'seniors')
                ],
                'civil_status' => [
                    'single' => array_column($reportData, 'single_count'),
                    'married' => array_column($reportData, 'married_count'),
                    'widowed' => array_column($reportData, 'widowed_count'),
                    'divorced' => array_column($reportData, 'divorced_count')
                ]
            ]
        ];
        break;
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2">Reports</h1>
                                <p class="text-muted mb-0">Generate and view system reports</p>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-danger" onclick="exportToPDF()">
                                    <i class="bi bi-file-pdf me-2"></i>Export PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Report Type</label>
                                <select name="type" class="form-select" onchange="this.form.submit()">
                                    <?php foreach ($reportTypes as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $reportType === $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($reportType === 'transaction_logs'): ?>
                            <div class="col-md-2">
                                <label class="form-label">Timeframe</label>
                                <select name="timeframe" class="form-select" onchange="this.form.submit()">
                                    <option value="daily" <?php echo $timeframe === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo $timeframe === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo $timeframe === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-<?php echo $reportType === 'transaction_logs' ? '2' : '3'; ?>">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" 
                                       value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" 
                                       value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Generate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="row g-4">
            <!-- Chart -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up me-2"></i><?php echo $reportTypes[$reportType]; ?> Chart
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="reportChart" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-clipboard-data me-2"></i>Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($reportType === 'applications_status'): ?>
                            <?php
                            $totalApplications = array_sum(array_column($reportData, 'count'));
                            $pendingCount = array_sum(array_column(array_filter($reportData, function($row) {
                                return $row['status'] === 'pending';
                            }), 'count'));
                            $completedCount = array_sum(array_column(array_filter($reportData, function($row) {
                                return $row['status'] === 'ready_for_pickup' || $row['status'] === 'completed';
                            }), 'count'));
                            ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Total Applications</small>
                                <h3 class="mb-0"><?php echo number_format($totalApplications); ?></h3>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Pending Applications</small>
                                <h3 class="text-warning mb-0"><?php echo number_format($pendingCount); ?></h3>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Completed Applications</small>
                                <h3 class="text-success mb-0"><?php echo number_format($completedCount); ?></h3>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Completion Rate</small>
                                <h3 class="text-info mb-0">
                                    <?php echo $totalApplications > 0 ? 
                                        number_format(($completedCount / $totalApplications) * 100, 1) : 0; ?>%
                                </h3>
                            </div>
                        <?php elseif ($reportType === 'clearances_summary'): ?>
                            <?php
                            $totalIssued = array_sum(array_column($reportData, 'total_count'));
                            $totalRevenue = array_sum(array_column($reportData, 'total_revenue'));
                            ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Total Clearances/Permits Issued</small>
                                <h3 class="mb-0"><?php echo number_format($totalIssued); ?></h3>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Total Revenue from Clearances</small>
                                <h3 class="text-success mb-0">₱<?php echo number_format($totalRevenue, 2); ?></h3>
                            </div>
                        <?php elseif ($reportType === 'transaction_logs'): ?>
                            <?php
                            $totalTransactions = array_sum(array_column($reportData, 'count'));
                            ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Total Transactions</small>
                                <h3 class="mb-0"><?php echo number_format($totalTransactions); ?></h3>
                            </div>
                        <?php elseif ($reportType === 'applications_by_zone'): ?>
                            <?php
                            $totalApplications = array_sum(array_column($reportData, 'total_applications'));
                            $totalCompleted = array_sum(array_column($reportData, 'completed_applications'));
                            $totalPending = array_sum(array_column($reportData, 'pending_applications'));
                            ?>
                            <div class="mb-3">
                                <small class="text-muted d-block">Total Applications</small>
                                <h3 class="mb-0"><?php echo number_format($totalApplications); ?></h3>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Completed Applications</small>
                                <h3 class="text-success mb-0"><?php echo number_format($totalCompleted); ?></h3>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Pending Applications</small>
                                <h3 class="text-warning mb-0"><?php echo number_format($totalPending); ?></h3>
                            </div>
                        <?php elseif ($reportType === 'residents_demographics'): ?>
                            <?php
                            $totalResidents = array_sum(array_column($reportData, 'total_residents'));
                            $totalMale = array_sum(array_column($reportData, 'male_count'));
                            $totalFemale = array_sum(array_column($reportData, 'female_count'));
                            $avgAge = count($reportData) > 0 ? array_sum(array_column($reportData, 'average_age')) / count($reportData) : 0;
                            $totalEmployed = array_sum(array_column($reportData, 'employed_count'));
                            $avgIncome = count($reportData) > 0 ? array_sum(array_column($reportData, 'average_income')) / count($reportData) : 0;
                            
                            // Age group totals
                            $totalMinors = array_sum(array_column($reportData, 'minors'));
                            $totalYoungAdults = array_sum(array_column($reportData, 'young_adults'));
                            $totalAdults = array_sum(array_column($reportData, 'adults'));
                            $totalSeniors = array_sum(array_column($reportData, 'seniors'));
                            
                            // Civil status totals
                            $totalSingle = array_sum(array_column($reportData, 'single_count'));
                            $totalMarried = array_sum(array_column($reportData, 'married_count'));
                            $totalWidowed = array_sum(array_column($reportData, 'widowed_count'));
                            $totalDivorced = array_sum(array_column($reportData, 'divorced_count'));
                            ?>
                            <!-- Total Residents Card -->
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Total Residents</h6>
                                            <h3 class="mb-0"><?php echo number_format($totalResidents); ?></h3>
                                        </div>
                                        <div class="text-primary">
                                            <i class="bi bi-people" style="font-size: 2rem;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Gender Distribution -->
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-2">Male Residents</h6>
                                                    <h3 class="text-primary mb-0"><?php echo number_format($totalMale); ?></h3>
                                                </div>
                                                <div class="text-primary">
                                                    <i class="bi bi-gender-male" style="font-size: 2rem;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="text-muted mb-2">Female Residents</h6>
                                                    <h3 class="text-danger mb-0"><?php echo number_format($totalFemale); ?></h3>
                                                </div>
                                                <div class="text-danger">
                                                    <i class="bi bi-gender-female" style="font-size: 2rem;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Age Groups -->
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Age Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Minors (< 18)</small>
                                            <h5 class="mb-0"><?php echo number_format($totalMinors); ?></h5>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Young Adults (18-30)</small>
                                            <h5 class="mb-0"><?php echo number_format($totalYoungAdults); ?></h5>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Adults (31-60)</small>
                                            <h5 class="mb-0"><?php echo number_format($totalAdults); ?></h5>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Seniors (> 60)</small>
                                            <h5 class="mb-0"><?php echo number_format($totalSeniors); ?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Civil Status -->
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Civil Status Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Single</small>
                                            <h5 class="mb-0"><?php echo number_format($totalSingle); ?></h5>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Married</small>
                                            <h5 class="mb-0"><?php echo number_format($totalMarried); ?></h5>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Widowed</small>
                                            <h5 class="mb-0"><?php echo number_format($totalWidowed); ?></h5>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Divorced</small>
                                            <h5 class="mb-0"><?php echo number_format($totalDivorced); ?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Employment and Income -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-2">Employed Residents</h6>
                                            <h3 class="text-success mb-0"><?php echo number_format($totalEmployed); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-2">Average Monthly Income</h6>
                                            <h3 class="text-success mb-0">₱<?php echo number_format($avgIncome, 2); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Detailed Data -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-table me-2"></i>Detailed Data
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table" id="reportTable">
                                <thead>
                                    <tr>
                                        <?php if ($reportType === 'applications_status'): ?>
                                            <th>Date</th>
                                            <th>Document Type</th>
                                            <th>Status</th>
                                            <th>Count</th>
                                        <?php elseif ($reportType === 'clearances_summary'): ?>
                                            <th>Date</th>
                                            <th>Document Type</th>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Revenue</th>
                                        <?php elseif ($reportType === 'transaction_logs'): ?>
                                            <th>Period</th>
                                            <th>Action</th>
                                            <th>Count</th>
                                            <th>Affected Tables</th>
                                        <?php elseif ($reportType === 'applications_by_zone'): ?>
                                            <th>Purok</th>
                                            <th>Document Type</th>
                                            <th>Total Applications</th>
                                            <th>Completed</th>
                                            <th>Pending</th>
                                        <?php elseif ($reportType === 'residents_demographics'): ?>
                                            <th>Purok</th>
                                            <th>Total Residents</th>
                                            <th>Male</th>
                                            <th>Female</th>
                                            <th>Average Age</th>
                                            <th>Minors</th>
                                            <th>Young Adults</th>
                                            <th>Adults</th>
                                            <th>Seniors</th>
                                            <th>Employed</th>
                                            <th>Avg. Income</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <?php if ($reportType === 'applications_status'): ?>
                                                <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $row['status'] === 'ready_for_pickup' || $row['status'] === 'completed' ? 'success' : 
                                                            ($row['status'] === 'pending' ? 'warning' : 
                                                            ($row['status'] === 'rejected' ? 'danger' : 'info'));
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($row['count']); ?></td>
                                            <?php elseif ($reportType === 'clearances_summary'): ?>
                                                <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $row['status'] === 'ready_for_pickup' || $row['status'] === 'completed' ? 'success' : 'info';
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($row['total_count']); ?></td>
                                                <td>₱<?php echo number_format($row['total_revenue'], 2); ?></td>
                                            <?php elseif ($reportType === 'transaction_logs'): ?>
                                                <td><?php echo htmlspecialchars($row['period']); ?></td>
                                                <td><?php echo htmlspecialchars($row['action']); ?></td>
                                                <td><?php echo number_format($row['count']); ?></td>
                                                <td><?php echo htmlspecialchars($row['affected_tables']); ?></td>
                                            <?php elseif ($reportType === 'applications_by_zone'): ?>
                                                <td><?php echo htmlspecialchars($row['purok_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                                                <td><?php echo number_format($row['total_applications']); ?></td>
                                                <td><?php echo number_format($row['completed_applications']); ?></td>
                                                <td><?php echo number_format($row['pending_applications']); ?></td>
                                            <?php elseif ($reportType === 'residents_demographics'): ?>
                                                <td><?php echo htmlspecialchars($row['purok_name']); ?></td>
                                                <td><?php echo number_format($row['total_residents']); ?></td>
                                                <td><?php echo number_format($row['male_count']); ?></td>
                                                <td><?php echo number_format($row['female_count']); ?></td>
                                                <td><?php echo number_format($row['average_age'], 1); ?></td>
                                                <td><?php echo number_format($row['minors']); ?></td>
                                                <td><?php echo number_format($row['young_adults']); ?></td>
                                                <td><?php echo number_format($row['adults']); ?></td>
                                                <td><?php echo number_format($row['seniors']); ?></td>
                                                <td><?php echo number_format($row['employed_count']); ?></td>
                                                <td>₱<?php echo number_format($row['average_income'], 2); ?></td>
                                            <?php endif; ?>
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
    // Initialize DataTables
    $('#reportTable').DataTable({
        paging: false,
        info: false,
        responsive: true,
        dom: 'frtip', // Removed buttons from dom
        language: {
            search: '<i class="bi bi-search"></i>',
            searchPlaceholder: 'Search data...'
        }
    });

    // Initialize Chart
    const ctx = document.getElementById('reportChart').getContext('2d');
    const chartData = <?php echo json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || {labels: [], datasets: {}};
    
    // Ensure chartData has required structure
    if (!chartData.labels) chartData.labels = [];
    if (!chartData.datasets) chartData.datasets = {};
    
    let chartConfig = {
        type: '<?php echo $reportType === "residents_demographics" ? "bar" : "line"; ?>',
        data: {
            labels: chartData.labels || [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    };

    // Configure datasets based on report type
    <?php if ($reportType === 'applications_status'): ?>
        chartConfig.data.datasets = [
            {
                label: 'Pending',
                data: chartData.datasets.pending || [],
                borderColor: '#ffc107',
                backgroundColor: '#ffc10733',
                fill: false
            },
            {
                label: 'Processing', 
                data: chartData.datasets.processing || [],
                borderColor: '#17a2b8',
                backgroundColor: '#17a2b833',
                fill: false
            },
            {
                label: 'Ready for Pickup',
                data: chartData.datasets.ready_for_pickup || [],
                borderColor: '#28a745',
                backgroundColor: '#28a74533',
                fill: false
            },
            {
                label: 'Completed',
                data: chartData.datasets.completed || [],
                borderColor: '#20c997',
                backgroundColor: '#20c99733',
                fill: false
            },
            {
                label: 'Rejected',
                data: chartData.datasets.rejected || [],
                borderColor: '#dc3545',
                backgroundColor: '#dc354533',
                fill: false
            }
        ];
    <?php elseif ($reportType === 'clearances_summary'): ?>
        chartConfig.data.datasets = [
            {
                label: 'Issued',
                data: chartData.datasets.issued || [],
                borderColor: '#28a745',
                backgroundColor: '#28a74533',
                fill: false
            },
            {
                label: 'Revenue',
                data: chartData.datasets.revenue || [],
                borderColor: '#007bff',
                backgroundColor: '#007bff33',
                fill: false
            }
        ];
    <?php elseif ($reportType === 'transaction_logs'): ?>
        chartConfig.data.datasets = [{
            label: 'Transactions',
            data: chartData.datasets.transactions || [],
            borderColor: '#007bff',
            backgroundColor: '#007bff33',
            fill: false
        }];
    <?php elseif ($reportType === 'applications_by_zone'): ?>
        chartConfig.data.datasets = [
            {
                label: 'Total Applications',
                data: chartData.datasets.total || [],
                borderColor: '#6c757d',
                backgroundColor: '#6c757d33',
                fill: false
            },
            {
                label: 'Completed Applications',
                data: chartData.datasets.completed || [],
                borderColor: '#28a745',
                backgroundColor: '#28a74533',
                fill: false
            },
            {
                label: 'Pending Applications',
                data: chartData.datasets.pending || [],
                borderColor: '#ffc107',
                backgroundColor: '#ffc10733',
                fill: false
            }
        ];
    <?php elseif ($reportType === 'residents_demographics'): ?>
        chartConfig.type = 'bar';
        chartConfig.data.datasets = [
            {
                label: 'Male',
                data: (chartData.datasets.demographics && chartData.datasets.demographics.male) ? chartData.datasets.demographics.male : [],
                backgroundColor: '#007bff'
            },
            {
                label: 'Female',
                data: (chartData.datasets.demographics && chartData.datasets.demographics.female) ? chartData.datasets.demographics.female : [],
                backgroundColor: '#dc3545'
            },
            {
                label: 'Minors',
                data: (chartData.datasets.age_groups && chartData.datasets.age_groups.minors) ? chartData.datasets.age_groups.minors : [],
                backgroundColor: '#28a745'
            },
            {
                label: 'Young Adults',
                data: (chartData.datasets.age_groups && chartData.datasets.age_groups.young_adults) ? chartData.datasets.age_groups.young_adults : [],
                backgroundColor: '#ffc107'
            },
            {
                label: 'Adults',
                data: (chartData.datasets.age_groups && chartData.datasets.age_groups.adults) ? chartData.datasets.age_groups.adults : [],
                backgroundColor: '#17a2b8'
            },
            {
                label: 'Seniors',
                data: (chartData.datasets.age_groups && chartData.datasets.age_groups.seniors) ? chartData.datasets.age_groups.seniors : [],
                backgroundColor: '#6c757d'
            }
        ];
        chartConfig.options = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        };
    <?php endif; ?>

    // Initialize chart with error handling
    try {
        new Chart(ctx, chartConfig);
    } catch (error) {
        console.error('Chart initialization error:', error);
        // Show fallback message if chart fails
        const chartContainer = document.getElementById('reportChart').parentElement;
        chartContainer.innerHTML = '<div class="text-center p-4"><i class="bi bi-exclamation-triangle text-warning"></i><br>Chart could not be loaded</div>';
    }
});

function exportToPDF() {
    // Show loading indicator
    Swal.fire({
        title: 'Generating PDF...',
        text: 'Please wait while we prepare your report',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Check if libraries are already loaded
    if (window.jspdf && window.jspdf.jsPDF) {
        generatePDF();
        return;
    }
    
    // Import jsPDF and autoTable plugin
    const script1 = document.createElement('script');
    script1.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    
    const script2 = document.createElement('script');
    script2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js';
    
    document.head.appendChild(script1);
    
    script1.onload = function() {
        document.head.appendChild(script2);
        
        script2.onload = function() {
            generatePDF();
        };
    };
    
    script1.onerror = function() {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load PDF library. Please check your internet connection.'
        });
    };
}

function generatePDF() {
    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); // landscape orientation
    
    // Get current report details
    const reportType = '<?php echo $reportTypes[$reportType]; ?>';
    const startDate = '<?php echo date("M j, Y", strtotime($startDate)); ?>';
    const endDate = '<?php echo date("M j, Y", strtotime($endDate)); ?>';
    
    // Add header
    doc.setFontSize(20);
    doc.setTextColor(44, 90, 160);
    doc.text('Barangay Malangit - Reports', 20, 20);
    
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text(`Report Type: ${reportType}`, 20, 35);
    doc.text(`Period: ${startDate} to ${endDate}`, 20, 45);
    doc.text(`Generated on: ${new Date().toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })}`, 20, 55);
    
    // Get table data
    const table = document.getElementById('reportTable');
    const headers = [];
    const data = [];
    
    // Extract headers
    const headerCells = table.querySelectorAll('thead tr th');
    headerCells.forEach(cell => {
        headers.push(cell.textContent.trim());
    });
    
    // Extract data rows
    const dataRows = table.querySelectorAll('tbody tr');
    
    if (dataRows.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Data',
            text: 'There is no data to export in the current report.'
        });
        return;
    }
    
    dataRows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            // Clean up cell content (remove HTML tags and extra spaces)
            let text = cell.textContent.trim();
            // Handle badge content specially
            const badge = cell.querySelector('.badge');
            if (badge) {
                text = badge.textContent.trim();
            }
            rowData.push(text);
        });
        data.push(rowData);
    });
    
    // Generate table
    doc.autoTable({
        startY: 65,
        head: [headers],
        body: data,
        styles: {
            fontSize: 8,
            cellPadding: 3,
        },
        headStyles: {
            fillColor: [44, 90, 160],
            textColor: [255, 255, 255],
            fontStyle: 'bold'
        },
        alternateRowStyles: {
            fillColor: [245, 245, 245]
        },
        margin: { top: 65, left: 20, right: 20, bottom: 20 },
        didDrawPage: function (data) {
            // Add footer
            const pageCount = doc.internal.getNumberOfPages();
            const pageSize = doc.internal.pageSize;
            const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
            
            doc.setFontSize(8);
            doc.setTextColor(128, 128, 128);
            doc.text('Barangay Malangit Smart Clearance and Permit Issuance System', 
                20, pageHeight - 10);
            doc.text(`Page ${data.pageNumber} of ${pageCount}`, 
                pageSize.width - 40, pageHeight - 10);
        }
    });
    
    // Add summary stats if available
    let summaryY = doc.lastAutoTable.finalY + 20;
    doc.setFontSize(12);
    doc.setTextColor(0, 0, 0);
    doc.text('Summary Statistics:', 20, summaryY);
    
    doc.setFontSize(10);
    <?php if ($reportType === 'applications_status'): ?>
        doc.text('Total Applications: <?php echo array_sum(array_column($reportData, "count")); ?>', 20, summaryY + 10);
        doc.text('Pending: <?php echo array_sum(array_column(array_filter($reportData, function($row) { return $row["status"] === "pending"; }), "count")); ?>', 20, summaryY + 20);
        doc.text('Completed: <?php echo array_sum(array_column(array_filter($reportData, function($row) { return $row["status"] === "ready_for_pickup" || $row["status"] === "completed"; }), "count")); ?>', 20, summaryY + 30);
    <?php elseif ($reportType === 'clearances_summary'): ?>
        doc.text('Total Issued: <?php echo array_sum(array_column($reportData, "total_count")); ?>', 20, summaryY + 10);
        doc.text('Total Revenue: ₱<?php echo number_format(array_sum(array_column($reportData, "total_revenue")), 2); ?>', 20, summaryY + 20);
    <?php elseif ($reportType === 'residents_demographics'): ?>
        doc.text('Total Residents: <?php echo array_sum(array_column($reportData, "total_residents")); ?>', 20, summaryY + 10);
        doc.text('Male: <?php echo array_sum(array_column($reportData, "male_count")); ?> | Female: <?php echo array_sum(array_column($reportData, "female_count")); ?>', 20, summaryY + 20);
        doc.text('Employment Rate: <?php echo number_format((array_sum(array_column($reportData, "employed_count")) / array_sum(array_column($reportData, "total_residents"))) * 100, 1); ?>%', 20, summaryY + 30);
    <?php elseif ($reportType === 'transaction_logs'): ?>
        doc.text('Total Transactions: <?php echo array_sum(array_column($reportData, "count")); ?>', 20, summaryY + 10);
    <?php elseif ($reportType === 'applications_by_zone'): ?>
        doc.text('Total Applications: <?php echo array_sum(array_column($reportData, "total_applications")); ?>', 20, summaryY + 10);
        doc.text('Completed: <?php echo array_sum(array_column($reportData, "completed_applications")); ?> | Pending: <?php echo array_sum(array_column($reportData, "pending_applications")); ?>', 20, summaryY + 20);
    <?php endif; ?>
    
    // Save the PDF
    const fileName = `${reportType.replace(/\s+/g, '_')}_${startDate.replace(/\s+/g, '_')}_to_${endDate.replace(/\s+/g, '_')}.pdf`;
    doc.save(fileName);
    
        // Close loading indicator and show success message
        Swal.fire({
            icon: 'success',
            title: 'PDF Generated!',
            text: 'Your report has been downloaded successfully.',
            timer: 2000,
            showConfirmButton: false
        });
    } catch (error) {
        console.error('PDF generation error:', error);
        Swal.fire({
            icon: 'error',
            title: 'PDF Generation Failed',
            text: 'An error occurred while generating the PDF. Please try again.'
        });
    }
}
</script>

<style>
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

/* Default column widths for reports table */
.table-container th {
    width: auto;
}

/* Allow text wrapping for certain cells */
.table-container td:nth-child(1),
.table-container td:nth-child(2) {
    white-space: normal;
}

/* Responsive adjustments for different report types */
.table-container.residents-demographics th:nth-child(1) { width: 15%; } /* Purok */
.table-container.residents-demographics th:nth-child(2) { width: 8%; }  /* Total */
.table-container.residents-demographics th:nth-child(3) { width: 7%; }  /* Male */
.table-container.residents-demographics th:nth-child(4) { width: 7%; }  /* Female */
.table-container.residents-demographics th:nth-child(5) { width: 8%; }  /* Avg Age */
.table-container.residents-demographics th:nth-child(6) { width: 7%; }  /* Minors */
.table-container.residents-demographics th:nth-child(7) { width: 8%; }  /* Young Adults */
.table-container.residents-demographics th:nth-child(8) { width: 7%; }  /* Adults */
.table-container.residents-demographics th:nth-child(9) { width: 7%; }  /* Seniors */
.table-container.residents-demographics th:nth-child(10) { width: 8%; } /* Employed */
.table-container.residents-demographics th:nth-child(11) { width: 10%; } /* Avg Income */

/* Print styles for PDF export fallback */
@media print {
    .no-print, .btn, .dropdown, .navbar, .sidebar {
        display: none !important;
    }
    
    body {
        padding: 0;
        margin: 0;
        font-size: 12pt;
    }
    
    .main-content {
        padding: 0;
        margin: 0;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        margin-bottom: 20px;
        page-break-inside: avoid;
    }
    
    .card-header {
        background: #f8f9fa !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .table {
        font-size: 10pt;
        border-collapse: collapse;
    }
    
    .table th,
    .table td {
        border: 1px solid #ddd !important;
        padding: 4px 6px;
    }
    
    .table th {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .badge {
        border: 1px solid #000;
        padding: 2px 4px;
        font-weight: bold;
    }
    
    h1, h2, h3, h4, h5 {
        color: #000 !important;
        page-break-after: avoid;
    }
    
    .page-break {
        page-break-before: always;
    }
}
</style>

<?php include 'scripts.php'; ?> 