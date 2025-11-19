<?php
require_once 'config.php';
require_once 'classes/Settings.php';

// Require login and must be a resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Payment Successful';
$applicationId = $_GET['id'] ?? 0;

// Get application details
$stmt = $pdo->prepare("
    SELECT a.*, dt.type_name, dt.fee
    FROM applications a
    JOIN document_types dt ON a.document_type_id = dt.id
    WHERE a.id = ? AND a.user_id = ? AND a.payment_status = 'paid'
");
$stmt->execute([$applicationId, $_SESSION['user_id']]);
$application = $stmt->fetch();

// Get GCash settings
$settings = Settings::getInstance($pdo);
$gcashNumber = $settings->get('gcash_number', '0912-345-6789');
$gcashAccountName = $settings->get('gcash_account_name', 'BRGY MALANGIT');

if (!$application) {
    header('Location: my-applications.php');
    exit;
}

include 'header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <!-- Success Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="success-animation mb-4">
                            <div class="success-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <h1 class="h2 mb-3 text-success">Payment Successful!</h1>
                        <p class="text-muted mb-4">
                            Your GCash payment has been verified and your application is now being processed.
                        </p>
                        <div class="alert alert-info">
                            <i class="bi bi-clock-history me-2"></i><strong>Processing Time:</strong> 3 to 5 working days (except holidays)<br>
                            <small>Processing started: <?php echo date('F j, Y', strtotime($application['payment_date'])); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Payment Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-receipt me-2"></i>Payment Receipt
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Application Number</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($application['application_number']); ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Document Type</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($application['type_name']); ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Amount Paid</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control" 
                                           value="<?php echo number_format($application['fee'], 2); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Payment Method</label>
                                <input type="text" class="form-control" value="GCash" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Payment Date</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo date('F j, Y g:i A', strtotime($application['payment_date'])); ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($application['payment_reference']); ?>" readonly>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Application Status</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">
                                        <i class="bi bi-gear"></i>
                                    </span>
                                    <input type="text" class="form-control" value="Processing" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-right me-2"></i>What Happens Next?
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-icon bg-primary">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Payment Verified</h6>
                                    <p class="text-muted mb-0">Your GCash payment has been successfully verified</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-icon bg-warning">
                                    <i class="bi bi-gear"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Application Processing</h6>
                                    <p class="text-muted mb-0">Your application is now being processed by our staff</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-icon bg-info">
                                    <i class="bi bi-bell"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Status Updates</h6>
                                    <p class="text-muted mb-0">You'll receive SMS notifications about your application progress</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-icon bg-success">
                                    <i class="bi bi-file-earmark-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Document Ready</h6>
                                    <p class="text-muted mb-0">Once processed, you can download your document</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="view-application.php?id=<?php echo $application['id']; ?>" 
                               class="btn btn-primary">
                                <i class="bi bi-eye me-2"></i>View Application
                            </a>
                            
                            <a href="my-applications.php" class="btn btn-outline-primary">
                                <i class="bi bi-list me-2"></i>My Applications
                            </a>
                            
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-house me-2"></i>Dashboard
                            </a>
                            
                            <button type="button" class="btn btn-outline-success" onclick="downloadReceipt()">
                                <i class="bi bi-download me-2"></i>Download Receipt
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Support -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-question-circle me-2"></i>Need Help?
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            If you have any questions about your application or payment, please contact us:
                        </p>
                        
                        <div class="support-info">
                            <div class="support-item">
                                <i class="bi bi-telephone text-primary"></i>
                                <div>
                                    <h6>Phone</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($gcashNumber); ?></p>
                                </div>
                            </div>
                            
                            <div class="support-item">
                                <i class="bi bi-envelope text-primary"></i>
                                <div>
                                    <h6>Email</h6>
                                    <p class="mb-0">support@barangaymalangit.gov.ph</p>
                                </div>
                            </div>
                            
                            <div class="support-item">
                                <i class="bi bi-geo-alt text-primary"></i>
                                <div>
                                    <h6>Office</h6>
                                    <p class="mb-0">Barangay Hall, Malangit</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .success-animation {
                animation: bounceIn 1s ease-out;
            }
            
            .success-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                margin: 0 auto;
            }
            
            .timeline {
                position: relative;
                padding-left: 50px;
            }
            
            .timeline-item {
                position: relative;
                padding-bottom: 2rem;
            }
            
            .timeline-item:last-child {
                padding-bottom: 0;
            }
            
            .timeline-icon {
                position: absolute;
                left: -50px;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1.2rem;
            }
            
            .timeline-item:not(:last-child)::after {
                content: '';
                position: absolute;
                left: -30px;
                top: 40px;
                bottom: 0;
                width: 2px;
                background-color: #e9ecef;
            }
            
            .timeline-content h6 {
                margin-bottom: 0.25rem;
                color: var(--primary-color);
            }
            
            .support-info {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .support-item {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .support-item i {
                font-size: 1.25rem;
                margin-top: 0.125rem;
            }
            
            .support-item h6 {
                margin-bottom: 0.25rem;
                color: var(--primary-color);
            }
            
            @keyframes bounceIn {
                0% {
                    transform: scale(0.3);
                    opacity: 0;
                }
                50% {
                    transform: scale(1.05);
                }
                70% {
                    transform: scale(0.9);
                }
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
            </style>
        </div>
    </div>
</div>

<script>
// Download receipt function
function downloadReceipt() {
    // Create receipt content
    const receiptContent = `
        BARANGAY MALANGIT
        Pandag, Maguindanao Del Sur
        Payment Receipt
        
        Application Number: <?php echo $application['application_number']; ?>
        Document Type: <?php echo $application['type_name']; ?>
        Amount Paid: ₱<?php echo number_format($application['fee'], 2); ?>
        Payment Method: GCash
        Payment Date: <?php echo date('F j, Y g:i A', strtotime($application['payment_date'])); ?>
        Reference Number: <?php echo $application['payment_reference']; ?>
        
        Status: Processing
        
        Thank you for your payment!
    `;
    
    // Create blob and download
    const blob = new Blob([receiptContent], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'payment-receipt-<?php echo $application['application_number']; ?>.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Auto-scroll to top
window.scrollTo(0, 0);

// Show success message
setTimeout(() => {
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '5';
    toast.innerHTML = `
        <div class="toast show align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i>Payment completed successfully!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}, 1000);
</script>

<?php include 'scripts.php'; ?> 