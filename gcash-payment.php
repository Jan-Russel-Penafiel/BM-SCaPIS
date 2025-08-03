<?php
require_once 'config.php';
require_once 'classes/Settings.php';

// Require login and must be a resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'GCash Payment';
$error = '';
$success = '';

// Get payment ID from URL
$paymentId = $_GET['payment_id'] ?? 0;

// Get payment session
$payment = getPaymentSession($paymentId);

if (!$payment) {
    header('Location: my-applications.php');
    exit;
}

// Get application details
$stmt = $pdo->prepare("
    SELECT a.*, dt.type_name, dt.fee
    FROM applications a
    JOIN document_types dt ON a.document_type_id = dt.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$payment['application_id'], $_SESSION['user_id']]);
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

if (!$application) {
    header('Location: my-applications.php');
    exit;
}

// Check if payment has expired
if (strtotime($payment['expires_at']) < time()) {
    updatePaymentStatus($paymentId, 'expired');
    $error = 'Payment session has expired. Please try again.';
}

// Handle payment verification
if (isset($_GET['verify'])) {
    $isVerified = verifyGCashPayment($payment['reference_number'], $payment['amount']);
    
    if ($isVerified) {
        updatePaymentStatus($paymentId, 'verified', date('Y-m-d H:i:s'));
        
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
        $stmt->execute([$payment['reference_number'], $payment['application_id']]);
        
        // Add to application history
        $stmt = $pdo->prepare("
            INSERT INTO application_history (
                application_id, status, remarks, changed_by
            ) VALUES (?, 'processing', ?, ?)
        ");
        $stmt->execute([
            $payment['application_id'],
            'GCash payment verified. Reference: ' . $payment['reference_number'],
            $_SESSION['user_id']
        ]);
        
        // Send notifications
        sendPaymentNotificationSMS($payment['application_id'], 'gcash', $payment['amount'], $payment['reference_number']);
        
        // Log activity
        logActivity(
            $_SESSION['user_id'],
            'GCash payment verified for application #' . $application['application_number'],
            'applications',
            $payment['application_id']
        );
        
        // Clear payment session
        unset($_SESSION['payment_session']);
        
        // Redirect to success page
        header('Location: payment-success.php?id=' . $payment['application_id']);
        exit;
    } else {
        $error = 'Payment verification failed. Please try again.';
    }
}

// Handle payment cancellation
if (isset($_GET['cancel'])) {
    updatePaymentStatus($paymentId, 'failed');
    unset($_SESSION['payment_session']);
    header('Location: pay-application.php?id=' . $payment['application_id']);
    exit;
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
                                <h1 class="h3 mb-2">GCash Payment</h1>
                                <p class="text-muted mb-0">
                                    Application #<?php echo htmlspecialchars($application['application_number']); ?>
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="pay-application.php?id=<?php echo $payment['application_id']; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back
                                </a>
                                <a href="?payment_id=<?php echo $paymentId; ?>&cancel=1" 
                                   class="btn btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to cancel this payment?')">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </a>
                            </div>
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
                <!-- Payment Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-credit-card me-2"></i>Payment Details
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
                                <label class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control" 
                                           value="<?php echo number_format($payment['amount'], 2); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment['reference_number']); ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-clock text-warning"></i>
                                    </span>
                                    <input type="text" class="form-control" value="Pending Payment" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GCash Payment Instructions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-phone me-2"></i>Complete Payment in GCash
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center mb-3">
                                <img src="assets/images/gcash-logo.png" alt="GCash" height="32" class="me-3">
                                <div>
                                    <h6 class="mb-1">Send Payment to GCash</h6>
                                    <p class="mb-0" id="gcashInstructions">Click the button below to open GCash and complete your payment</p>
                                    <small class="text-muted" id="deviceInfo">
                                        <i class="bi bi-info-circle me-1"></i>Detecting your device...
                                    </small>
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="bi bi-phone"></i>
                                        </span>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($gcashNumber); ?>" readonly>
                                        <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($gcashNumber); ?>')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">GCash Number</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">₱</span>
                                        <input type="text" class="form-control" 
                                               value="<?php echo number_format($payment['amount'], 2); ?>" readonly>
                                        <button class="btn btn-outline-primary" type="button" 
                                                onclick="copyToClipboard('<?php echo number_format($payment['amount'], 2); ?>')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Amount to Send</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($gcashAccountName); ?>" readonly>
                                        <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($gcashAccountName); ?>')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Account Name</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="bi bi-hash"></i>
                                        </span>
                                        <input type="text" class="form-control" 
                                               value="<?php echo $payment['reference_number']; ?>" readonly>
                                        <button class="btn btn-outline-primary" type="button" 
                                                onclick="copyToClipboard('<?php echo $payment['reference_number']; ?>')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Reference Number</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-primary btn-lg" id="openGCashBtn" onclick="openGCash()">
                                <i class="bi bi-phone me-2"></i><span id="gcashButtonText">Open GCash</span>
                            </button>
                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showPaymentDetails()">
                                    <i class="bi bi-info-circle me-1"></i>Show Payment Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Payment Status -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-clock me-2"></i>Payment Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="payment-timer text-center mb-4">
                            <div class="timer-display">
                                <span id="minutes">15</span>:<span id="seconds">00</span>
                            </div>
                            <p class="text-muted mb-0">Time remaining to complete payment</p>
                        </div>
                        
                        <div class="payment-steps">
                            <div class="step active">
                                <div class="step-icon">
                                    <i class="bi bi-phone"></i>
                                </div>
                                <div class="step-content">
                                    <h6>Open GCash</h6>
                                    <p class="text-muted mb-0">Launch your GCash app</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-send"></i>
                                </div>
                                <div class="step-content">
                                    <h6>Send Payment</h6>
                                    <p class="text-muted mb-0">Send money to the provided number</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h6>Complete Payment</h6>
                                    <p class="text-muted mb-0">Verify payment and return here</p>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-secondary btn-lg" id="verifyPaymentBtn" onclick="verifyPayment()" disabled>
                                <i class="bi bi-lock me-2"></i>I've Completed Payment
                            </button>
                            <div class="form-text mt-2" id="verifyPaymentHint">
                                <i class="bi bi-info-circle me-1"></i>Please click "Open GCash App" first to enable payment verification
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .payment-timer {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 2rem;
                border-radius: 1rem;
                margin-bottom: 2rem;
            }
            
            .timer-display {
                font-size: 3rem;
                font-weight: bold;
                margin-bottom: 0.5rem;
            }
            
            .payment-steps {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .step {
                display: flex;
                align-items: flex-start;
                gap: 1rem;
                opacity: 0.5;
                transition: opacity 0.3s ease;
            }
            
            .step.active {
                opacity: 1;
            }
            
            .step-icon {
                width: 40px;
                height: 40px;
                background-color: var(--primary-color);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
            }
            
            .step-content h6 {
                margin-bottom: 0.25rem;
                color: var(--primary-color);
            }
            </style>
        </div>
    </div>
</div>

<script>
let timeLeft = 15 * 60; // 15 minutes in seconds
let timerInterval;

// Initialize timer
function initTimer() {
    updateTimerDisplay();
    timerInterval = setInterval(() => {
        timeLeft--;
        updateTimerDisplay();
        
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            alert('Payment session has expired. Please try again.');
            window.location.href = 'pay-application.php?id=<?php echo $payment['application_id']; ?>';
        }
    }, 1000);
}

function updateTimerDisplay() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    
    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
}

// Track if GCash app was opened
let gcashAppOpened = false;
let gcashOpenedTime = null;

// Detect if user is on mobile device
function isMobileDevice() {
    // Check user agent for mobile devices
    const mobileUserAgent = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    // Check screen size
    const smallScreen = window.innerWidth <= 768;
    
    // Check for touch capability
    const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    
    // Check for mobile-specific features
    const hasMobileFeatures = 'orientation' in window || 'devicePixelRatio' in window;
    
    return mobileUserAgent || (smallScreen && hasTouch) || hasMobileFeatures;
}

// Open GCash app or website based on device
function openGCash() {
    const isMobile = isMobileDevice();
    
    // Update button appearance to show it's been clicked
    const openBtn = document.getElementById('openGCashBtn');
    if (openBtn) {
        openBtn.disabled = true;
        openBtn.classList.remove('btn-primary');
        openBtn.classList.add('btn-success');
        openBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>GCash Opened';
    }
    
    // Mark that GCash was opened
    gcashAppOpened = true;
    gcashOpenedTime = Date.now();
    
    if (isMobile) {
        // Mobile: Try to open GCash app with multiple methods
        const gcashNumber = '<?php echo htmlspecialchars($gcashNumber); ?>';
        const amount = '<?php echo $payment['amount']; ?>';
        const reference = '<?php echo $payment['reference_number']; ?>';
        
        // Try multiple deep link formats
        const deepLinks = [
            `gcash://send?number=${gcashNumber}&amount=${amount}&reference=${reference}`,
            `https://www.gcash.com/send?number=${gcashNumber}&amount=${amount}&reference=${reference}`,
            `intent://send?number=${gcashNumber}&amount=${amount}&reference=${reference}#Intent;scheme=gcash;package=com.globe.gcash.android;end`
        ];
        
        // Try to open GCash app with first deep link
        tryOpenGCashApp(deepLinks, 0);
        
    } else {
        // Desktop: Open GCash website in new tab
        const gcashWebsite = 'https://www.gcash.com';
        window.open(gcashWebsite, '_blank');
        
        // Show instructions for desktop users
        setTimeout(() => {
            alert('GCash website opened in new tab!\n\nPlease:\n1. Log in to your GCash account\n2. Navigate to "Send Money" or "Pay Bills"\n3. Enter the payment details:\n   - Number: <?php echo htmlspecialchars($gcashNumber); ?>\n   - Amount: ₱<?php echo number_format($payment['amount'], 2); ?>\n   - Reference: <?php echo $payment['reference_number']; ?>');
        }, 500);
    }
    
    // Enable verification button after a short delay
    setTimeout(() => {
        enableVerificationButton();
    }, 2000);
}

// Try to open GCash app with multiple deep link formats
function tryOpenGCashApp(deepLinks, index) {
    if (index >= deepLinks.length) {
        // All deep links failed, show manual instructions
        showManualInstructions();
        return;
    }
    
    const deepLink = deepLinks[index];
    console.log(`Trying deep link ${index + 1}: ${deepLink}`);
    
    // Try to open the app
    window.location.href = deepLink;
    
    // Check if app opened after 2 seconds
    setTimeout(() => {
        if (document.hidden || document.visibilityState === 'hidden') {
            // App opened successfully
            console.log('GCash app opened successfully');
        } else {
            // App didn't open, try next deep link
            console.log(`Deep link ${index + 1} failed, trying next...`);
            tryOpenGCashApp(deepLinks, index + 1);
        }
    }, 2000);
}

// Show manual instructions when GCash app is not found
function showManualInstructions() {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const appStoreLink = isIOS ? 'https://apps.apple.com/ph/app/gcash/id519926866' : 'https://play.google.com/store/apps/details?id=com.globe.gcash.android';
    
    const modalContent = `
        <div class="modal fade" id="manualInstructionsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-phone me-2"></i>GCash App Not Found
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>GCash App Not Installed</h6>
                            <p class="mb-0">The GCash app is not installed on your device. Please install it first to continue with the payment.</p>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-phone fs-1 text-primary mb-3"></i>
                                        <h6>Install GCash App</h6>
                                        <p class="text-muted">Download and install the GCash mobile app</p>
                                        <a href="${appStoreLink}" target="_blank" class="btn btn-primary">
                                            <i class="bi bi-download me-2"></i>Download GCash
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-globe fs-1 text-success mb-3"></i>
                                        <h6>Use GCash Website</h6>
                                        <p class="text-muted">Access GCash through your web browser</p>
                                        <button type="button" class="btn btn-success" onclick="openGCashWebsite()">
                                            <i class="bi bi-globe me-2"></i>Open GCash Website
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6><i class="bi bi-info-circle me-2"></i>Payment Details</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">GCash Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($gcashNumber); ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($gcashNumber); ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control" value="<?php echo number_format($payment['amount'], 2); ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo number_format($payment['amount'], 2); ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $payment['reference_number']; ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo $payment['reference_number']; ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Name</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($gcashAccountName); ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($gcashAccountName); ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="retryOpenGCash()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Try Again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('manualInstructionsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalContent);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('manualInstructionsModal'));
    modal.show();
}

// Show payment details modal
function showPaymentDetails() {
    const isMobile = isMobileDevice();
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const appStoreLink = isIOS ? 'https://apps.apple.com/ph/app/gcash/id519926866' : 'https://play.google.com/store/apps/details?id=com.globe.gcash.android';
    
    const modalContent = `
        <div class="modal fade" id="paymentDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-credit-card me-2"></i>Payment Details
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">GCash Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($gcashNumber); ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($gcashNumber); ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control" value="<?php echo number_format($payment['amount'], 2); ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo number_format($payment['amount'], 2); ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $payment['reference_number']; ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo $payment['reference_number']; ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Name</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($gcashAccountName); ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($gcashAccountName); ?>')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>How to Pay:</h6>
                            ${isMobile ? 
                                `<p class="mb-2">1. Click "Open GCash App" to launch the mobile app</p>
                                 <p class="mb-2">2. If app doesn't open, <a href="${appStoreLink}" target="_blank">install GCash app</a></p>
                                 <p class="mb-0">3. Send money to the number above with the exact amount</p>` :
                                `<p class="mb-2">1. Click "Open GCash Website" to go to GCash.com</p>
                                 <p class="mb-2">2. Log in to your GCash account</p>
                                 <p class="mb-0">3. Use "Send Money" feature with the details above</p>`
                            }
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="openGCash()">
                            <i class="bi bi-phone me-2"></i>Open GCash
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('paymentDetailsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalContent);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
    modal.show();
}

// Open GCash website
function openGCashWebsite() {
    const gcashWebsite = 'https://www.gcash.com';
    window.open(gcashWebsite, '_blank');
    
    // Show instructions for website users
    setTimeout(() => {
        alert('GCash website opened in new tab!\n\nPlease:\n1. Log in to your GCash account\n2. Navigate to "Send Money" or "Pay Bills"\n3. Enter the payment details:\n   - Number: <?php echo htmlspecialchars($gcashNumber); ?>\n   - Amount: ₱<?php echo number_format($payment['amount'], 2); ?>\n   - Reference: <?php echo $payment['reference_number']; ?>');
    }, 500);
}

// Retry opening GCash app
function retryOpenGCash() {
    // Close the modal first
    const modal = bootstrap.Modal.getInstance(document.getElementById('manualInstructionsModal'));
    if (modal) {
        modal.hide();
    }
    
    // Reset button state
    const openBtn = document.getElementById('openGCashBtn');
    if (openBtn) {
        openBtn.disabled = false;
        openBtn.classList.remove('btn-success');
        openBtn.classList.add('btn-primary');
        openBtn.innerHTML = '<i class="bi bi-phone me-2"></i><span id="gcashButtonText">Open GCash</span>';
    }
    
    // Try opening GCash again
    setTimeout(() => {
        openGCash();
    }, 500);
}

// Enable verification button
function enableVerificationButton() {
    const verifyBtn = document.getElementById('verifyPaymentBtn');
    const verifyHint = document.getElementById('verifyPaymentHint');
    
    if (verifyBtn && verifyHint) {
        verifyBtn.disabled = false;
        verifyBtn.classList.remove('btn-secondary');
        verifyBtn.classList.add('btn-success');
        verifyBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>I\'ve Completed Payment';
        verifyHint.innerHTML = '<i class="bi bi-clock me-1 text-warning"></i>GCash app opened! Please wait 30 seconds before verifying payment...';
        verifyHint.className = 'form-text mt-2 text-warning';
        
        // Start countdown timer
        startVerificationCountdown();
    }
}

// Start countdown for verification button
function startVerificationCountdown() {
    const verifyHint = document.getElementById('verifyPaymentHint');
    let countdown = 30;
    
    const countdownInterval = setInterval(() => {
        countdown--;
        
        if (countdown > 0) {
            verifyHint.innerHTML = `<i class="bi bi-clock me-1 text-warning"></i>Please wait ${countdown} more seconds before verifying payment...`;
        } else {
            clearInterval(countdownInterval);
            verifyHint.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Ready to verify payment!';
            verifyHint.className = 'form-text mt-2 text-success';
        }
    }, 1000);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(() => {
        // Fallback for older browsers
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast('Copied to clipboard!', 'success');
    });
}

// Verify payment
function verifyPayment() {
    // Security check: Ensure GCash app was opened first
    if (!gcashAppOpened) {
        alert('Please click "Open GCash App" first before verifying payment.');
        return;
    }
    
    // Security check: Ensure minimum time has passed (at least 30 seconds)
    const timeSinceOpened = Date.now() - gcashOpenedTime;
    const minimumTime = 30 * 1000; // 30 seconds
    
    if (timeSinceOpened < minimumTime) {
        const remainingTime = Math.ceil((minimumTime - timeSinceOpened) / 1000);
        alert(`Please wait at least 30 seconds after opening GCash app before verifying payment. Please wait ${remainingTime} more seconds.`);
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying Payment...';
    
    // Redirect to verification endpoint
    window.location.href = '?payment_id=<?php echo $paymentId; ?>&verify=1';
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `position-fixed bottom-0 end-0 p-3`;
    toast.style.zIndex = '5';
    toast.innerHTML = `
        <div class="toast show align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Prevent back navigation
window.addEventListener('beforeunload', function(e) {
    e.preventDefault();
    e.returnValue = 'Payment in progress. Are you sure you want to leave?';
    return e.returnValue;
});

// Disable back button
history.pushState(null, null, location.href);
window.addEventListener('popstate', function() {
    history.pushState(null, null, location.href);
    alert('Please complete your payment before leaving this page.');
});

// Initialize timer when page loads
document.addEventListener('DOMContentLoaded', function() {
    initTimer();
    
    // Update button text based on device
    updateGCashButtonText();
});

// Update GCash button text based on device
function updateGCashButtonText() {
    const buttonText = document.getElementById('gcashButtonText');
    const instructions = document.getElementById('gcashInstructions');
    const deviceInfo = document.getElementById('deviceInfo');
    const isMobile = isMobileDevice();
    
    if (buttonText) {
        if (isMobile) {
            buttonText.textContent = 'Open GCash App';
        } else {
            buttonText.textContent = 'Open GCash Website';
        }
    }
    
    if (instructions) {
        if (isMobile) {
            instructions.textContent = 'Click the button below to open GCash app and complete your payment';
        } else {
            instructions.textContent = 'Click the button below to open GCash website in a new tab and complete your payment';
        }
    }
    
    if (deviceInfo) {
        if (isMobile) {
            deviceInfo.innerHTML = '<i class="bi bi-phone me-1"></i>Mobile device detected - will open GCash app';
        } else {
            deviceInfo.innerHTML = '<i class="bi bi-laptop me-1"></i>Desktop device detected - will open GCash website';
        }
    }
}

// Auto-refresh payment status every 30 seconds
setInterval(() => {
    // Check if payment was completed externally
    fetch('check-payment-status.php?payment_id=<?php echo $paymentId; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'verified') {
                window.location.href = 'payment-success.php?id=<?php echo $payment['application_id']; ?>';
            }
        })
        .catch(error => {
            console.log('Payment status check failed:', error);
        });
}, 30000);
</script>

<?php include 'scripts.php'; ?> 