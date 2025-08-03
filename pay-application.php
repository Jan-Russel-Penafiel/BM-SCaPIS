<?php
require_once 'config.php';
require_once 'classes/Settings.php';

// Require login and must be a resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Pay Application';
$currentUser = getCurrentUser();
$error = '';
$success = '';

// Get application ID from URL
$applicationId = $_GET['id'] ?? 0;

// Get application details
$stmt = $pdo->prepare("
    SELECT a.*, dt.type_name, dt.fee
    FROM applications a
    JOIN document_types dt ON a.document_type_id = dt.id
    WHERE a.id = ? AND a.user_id = ? AND a.payment_status = 'unpaid'
");
$stmt->execute([$applicationId, $_SESSION['user_id']]);
$application = $stmt->fetch();

// Get GCash settings
$settings = Settings::getInstance($pdo);
$gcashNumber = $settings->get('gcash_number', '0912-345-6789');
$gcashAccountName = $settings->get('gcash_account_name', 'BRGY MALANGIT');
$gcashEnabled = $settings->getBool('gcash_enabled', true);

// Check if GCash payments are enabled
if (!$gcashEnabled) {
    header('Location: my-applications.php');
    exit;
}

// If application not found, already paid, or doesn't belong to user
if (!$application) {
    header('Location: my-applications.php');
    exit;
}

// Handle payment verification
if (isset($_GET['verify_payment'])) {
    $paymentId = $_GET['verify_payment'];
    
    // Check if payment exists and is pending
    $stmt = $pdo->prepare("
        SELECT * FROM payment_verifications 
        WHERE id = ? AND application_id = ? AND status = 'pending'
    ");
    $stmt->execute([$paymentId, $applicationId]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        // Simulate payment verification (in real implementation, this would call GCash API)
        $isPaymentVerified = verifyGCashPayment($payment['reference_number'], $application['fee']);
        
        if ($isPaymentVerified) {
            // Update payment verification status
            $stmt = $pdo->prepare("
                UPDATE payment_verifications 
                SET status = 'verified', verified_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$paymentId]);
            
            // Update application payment status
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET payment_status = 'paid',
                    payment_date = CURRENT_TIMESTAMP,
                    payment_method = 'gcash',
                    payment_reference = ?,
                    status = 'processing',
                    processed_by = NULL
                WHERE id = ?
            ");
            $stmt->execute([$payment['reference_number'], $applicationId]);
            
            // Add to application history
            $stmt = $pdo->prepare("
                INSERT INTO application_history (
                    application_id, status, remarks, changed_by
                ) VALUES (?, 'processing', ?, ?)
            ");
            $stmt->execute([
                $applicationId,
                'GCash payment verified. Reference: ' . $payment['reference_number'],
                $_SESSION['user_id']
            ]);
            
            // Send notifications
            sendPaymentNotificationSMS($applicationId, 'gcash', $application['fee'], $payment['reference_number']);
            
            // Log activity
            logActivity(
                $_SESSION['user_id'],
                'GCash payment verified for application #' . $application['application_number'],
                'applications',
                $applicationId
            );
            
            // Redirect to success page
            header('Location: payment-success.php?id=' . $applicationId);
            exit;
        } else {
            $error = 'Payment verification failed. Please try again or contact support.';
        }
    } else {
        $error = 'Invalid payment verification request.';
    }
}

// Handle payment initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate_payment'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            // Generate unique payment reference
            $paymentReference = 'GC' . time() . rand(1000, 9999);
            
            // Create payment verification record
            $stmt = $pdo->prepare("
                INSERT INTO payment_verifications (
                    application_id, reference_number, amount, status, created_at
                ) VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$applicationId, $paymentReference, $application['fee']]);
            $paymentId = $pdo->lastInsertId();
            
            // Store payment session
            $_SESSION['payment_session'] = [
                'payment_id' => $paymentId,
                'application_id' => $applicationId,
                'reference' => $paymentReference,
                'amount' => $application['fee'],
                'started_at' => time()
            ];
            
            // Redirect to GCash payment flow
            header('Location: gcash-payment.php?payment_id=' . $paymentId);
            exit;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2">Pay Application</h1>
                                <p class="text-muted mb-0">
                                    Application #<?php echo htmlspecialchars($application['application_number']); ?>
                                </p>
                            </div>
                            <a href="view-application.php?id=<?php echo $application['id']; ?>" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Application
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Payment Form -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-credit-card me-2"></i>Real-time GCash Payment
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Document Type</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($application['type_name']); ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Amount to Pay</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control" 
                                           value="<?php echo number_format($application['fee'], 2); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="assets/images/gcash-logo.png" alt="GCash" height="24" class="me-2">
                                        <h6 class="mb-0">Real-time GCash Payment</h6>
                                    </div>
                                    <p class="mb-0">Click the button below to open GCash and complete your payment securely.</p>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <form method="POST" id="paymentForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="initiate_payment" value="1">
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-text">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Secure payment powered by GCash
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-phone me-2"></i>Pay with GCash
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Payment Benefits -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-check-circle me-2"></i>Why Pay with GCash?
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="benefits">
                            <div class="benefit-item">
                                <i class="bi bi-lightning text-primary"></i>
                                <div>
                                    <h6>Instant Processing</h6>
                                    <p class="text-muted mb-0">Your application starts processing immediately after payment</p>
                                </div>
                            </div>
                            
                            <div class="benefit-item">
                                <i class="bi bi-shield-check text-success"></i>
                                <div>
                                    <h6>Secure Payment</h6>
                                    <p class="text-muted mb-0">Bank-level security with GCash's encryption</p>
                                </div>
                            </div>
                            
                            <div class="benefit-item">
                                <i class="bi bi-clock text-info"></i>
                                <div>
                                    <h6>Real-time Verification</h6>
                                    <p class="text-muted mb-0">Automatic payment verification and status updates</p>
                                </div>
                            </div>
                            
                            <div class="benefit-item">
                                <i class="bi bi-receipt text-warning"></i>
                                <div>
                                    <h6>Digital Receipt</h6>
                                    <p class="text-muted mb-0">Get instant confirmation and digital receipt</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-4 mb-0">
                            <div class="d-flex">
                                <i class="bi bi-exclamation-triangle fs-4 me-2"></i>
                                <div>
                                    <h6 class="mb-1">Important:</h6>
                                    <ul class="mb-0 ps-3">
                                        <li>Ensure you have sufficient GCash balance</li>
                                        <li>Complete the payment within 15 minutes</li>
                                        <li>Don't close the browser during payment</li>
                                        <li>Keep your phone nearby for GCash app</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .benefits {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .benefit-item {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .benefit-item i {
                font-size: 1.25rem;
                margin-top: 0.125rem;
            }
            
            .benefit-item h6 {
                margin-bottom: 0.25rem;
                color: var(--primary-color);
            }
            </style>
        </div>
    </div>
</div>

<script>
// Form submission
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Preparing Payment...';
});

// Prevent back navigation during payment
window.addEventListener('beforeunload', function(e) {
    if (window.location.href.includes('gcash-payment.php')) {
        e.preventDefault();
        e.returnValue = 'Payment in progress. Are you sure you want to leave?';
        return e.returnValue;
    }
});

// Disable back button during payment
if (window.location.href.includes('gcash-payment.php')) {
    history.pushState(null, null, location.href);
    window.addEventListener('popstate', function() {
        history.pushState(null, null, location.href);
        alert('Please complete your payment before leaving this page.');
    });
}
</script>

<?php include 'scripts.php'; ?> 