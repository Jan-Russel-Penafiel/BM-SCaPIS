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
            'GCash payment verified. Reference: ' . $payment['reference_number'] . '. Processing started automatically. Processing time: 3 to 5 working days (except holidays)',
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
            <div class="col-12">
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
let paymentVerified = false;
let paymentCheckInterval = null;

// Start continuous payment monitoring
function startPaymentMonitoring() {
    // Check payment status every 10 seconds
    paymentCheckInterval = setInterval(() => {
        checkPaymentStatus();
    }, 10000);
    
    // Initial check
    setTimeout(() => {
        checkPaymentStatus();
    }, 2000);
}

// Check payment status via AJAX
function checkPaymentStatus() {
    fetch('check-payment-status.php?payment_id=<?php echo $paymentId; ?>')
        .then(response => response.json())
        .then(data => {
            updatePaymentStatus(data);
        })
        .catch(error => {
            console.error('Payment status check failed:', error);
            updatePaymentStatusUI('error', 'Failed to check payment status');
        });
}

// Update payment status based on server response
function updatePaymentStatus(data) {
    if (data.error) {
        updatePaymentStatusUI('error', data.error);
        return;
    }
    
    if (data.verified) {
        paymentVerified = true;
        updatePaymentStatusUI('verified', data.message);
        enableVerificationButton(true);
        
        // Stop monitoring and redirect to success page after a delay
        if (paymentCheckInterval) {
            clearInterval(paymentCheckInterval);
            paymentCheckInterval = null;
        }
        
        setTimeout(() => {
            window.location.href = 'payment-success.php?id=<?php echo $payment['application_id']; ?>';
        }, 3000);
        
    } else if (data.status === 'expired') {
        updatePaymentStatusUI('expired', 'Payment session has expired');
        disableVerificationButton();
        
        if (paymentCheckInterval) {
            clearInterval(paymentCheckInterval);
        }
        
        setTimeout(() => {
            window.location.href = 'pay-application.php?id=<?php echo $payment['application_id']; ?>';
        }, 3000);
        
    } else {
        updatePaymentStatusUI('pending', data.message || 'Waiting for payment...');
        
        // Update button state based on whether payment can be verified manually
        if (data.can_verify && gcashAppOpened) {
            enableVerificationButton(false); // Enable manual verification
        } else {
            disableVerificationButton();
        }
    }
}

// Update payment status UI elements
function updatePaymentStatusUI(status, message) {
    const statusIndicator = document.getElementById('paymentStatusIndicator');
    const statusSpinner = document.getElementById('paymentStatusSpinner');
    const statusText = document.getElementById('paymentStatusText');
    const verifyHint = document.getElementById('verifyPaymentHint');
    
    if (!statusIndicator || !statusSpinner || !statusText || !verifyHint) return;
    
    switch (status) {
        case 'verified':
            statusSpinner.className = 'spinner-border spinner-border-sm text-success me-2 d-none';
            statusText.className = 'text-success fw-bold';
            statusText.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + message;
            verifyHint.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Payment verified! Redirecting...';
            verifyHint.className = 'form-text mt-2 text-success';
            break;
            
        case 'expired':
            statusSpinner.className = 'spinner-border spinner-border-sm text-danger me-2 d-none';
            statusText.className = 'text-danger fw-bold';
            statusText.innerHTML = '<i class="bi bi-x-circle me-1"></i>' + message;
            verifyHint.innerHTML = '<i class="bi bi-x-circle me-1 text-danger"></i>Session expired. Redirecting...';
            verifyHint.className = 'form-text mt-2 text-danger';
            break;
            
        case 'error':
            statusSpinner.className = 'spinner-border spinner-border-sm text-warning me-2 d-none';
            statusText.className = 'text-warning';
            statusText.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' + message;
            verifyHint.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Error checking payment';
            verifyHint.className = 'form-text mt-2 text-warning';
            break;
            
        default: // pending
            statusSpinner.className = 'spinner-border spinner-border-sm text-warning me-2';
            statusText.className = 'text-muted';
            statusText.innerHTML = message;
            if (gcashAppOpened) {
                verifyHint.innerHTML = '<i class="bi bi-clock me-1 text-info"></i>Payment monitoring active. Complete payment in GCash app.';
                verifyHint.className = 'form-text mt-2 text-info';
            } else {
                verifyHint.innerHTML = '<i class="bi bi-info-circle me-1"></i>Please open GCash app first to enable payment verification';
                verifyHint.className = 'form-text mt-2';
            }
            break;
    }
}

// Enable verification button
function enableVerificationButton(autoVerified = false) {
    const verifyBtn = document.getElementById('verifyPaymentBtn');
    const verifyHint = document.getElementById('verifyPaymentHint');
    
    if (verifyBtn && verifyHint) {
        verifyBtn.disabled = false;
        verifyBtn.classList.remove('btn-secondary');
        verifyBtn.classList.add('btn-success');
        
        if (autoVerified) {
            verifyBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Payment Verified!';
            verifyHint.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Payment verified automatically!';
            verifyHint.className = 'form-text mt-2 text-success';
        } else {
            verifyBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirm Payment Completion';
            verifyHint.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>You can now confirm your payment!';
            verifyHint.className = 'form-text mt-2 text-success';
        }
    }
}

// Disable verification button
function disableVerificationButton() {
    const verifyBtn = document.getElementById('verifyPaymentBtn');
    const verifyHint = document.getElementById('verifyPaymentHint');
    
    if (verifyBtn && verifyHint) {
        verifyBtn.disabled = true;
        verifyBtn.classList.remove('btn-success');
        verifyBtn.classList.add('btn-secondary');
        verifyBtn.innerHTML = '<i class="bi bi-lock me-2"></i>I\'ve Completed Payment';
        
        if (!gcashAppOpened) {
            verifyHint.innerHTML = '<i class="bi bi-info-circle me-1"></i>Please open GCash app first to enable payment verification';
            verifyHint.className = 'form-text mt-2';
        }
    }
}

// Detect if user is on mobile device
function isMobileDevice() {
    // Check user agent for mobile devices (more comprehensive)
    const mobileUserAgent = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Tablet|Phone/i.test(navigator.userAgent);
    
    // Check screen size
    const smallScreen = window.innerWidth <= 768 || window.screen.width <= 768;
    
    // Check for touch capability
    const hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    
    // Check for mobile-specific features
    const hasMobileFeatures = 'orientation' in window || window.DeviceMotionEvent !== undefined;
    
    // Check platform
    const mobileplatform = /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.platform);
    
    return mobileUserAgent || (smallScreen && hasTouch) || hasMobileFeatures || mobileplatform;
}

// Detect specific mobile OS
function getMobileOS() {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
    
    if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
        return 'ios';
    }
    
    if (/android/i.test(userAgent)) {
        return 'android';
    }
    
    return 'unknown';
}

// Open GCash app (direct app opening or app store redirect)
function openGCash() {
    const isMobile = isMobileDevice();
    const mobileOS = getMobileOS();
    
    // Update button appearance to show it's been clicked
    const openBtn = document.getElementById('openGCashBtn');
    if (openBtn) {
        openBtn.disabled = true;
        openBtn.classList.remove('btn-primary');
        openBtn.classList.add('btn-info');
        openBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Opening GCash...';
    }
    
    // Mark that GCash was opened
    gcashAppOpened = true;
    gcashOpenedTime = Date.now();
    
    if (isMobile) {
        // Mobile: Try to open GCash app directly, fallback to store
        const gcashNumber = '<?php echo htmlspecialchars($gcashNumber); ?>';
        const amount = '<?php echo $payment['amount']; ?>';
        const reference = '<?php echo $payment['reference_number']; ?>';
        
        // Simplified and more effective deep link formats
        const deepLinks = [];
        
        if (mobileOS === 'android') {
            // Android: Direct app links with immediate store fallback
            deepLinks.push(
                `intent://send?number=${gcashNumber}&amount=${amount}&message=${reference}#Intent;scheme=gcash;package=com.globe.gcash.android;S.browser_fallback_url=https://play.google.com/store/apps/details?id=com.globe.gcash.android;end`,
                `gcash://send?number=${gcashNumber}&amount=${amount}&message=${reference}`
            );
        } else if (mobileOS === 'ios') {
            // iOS: App scheme with App Store fallback
            deepLinks.push(
                `gcash://send?number=${gcashNumber}&amount=${amount}&message=${reference}`,
                `gcash://pay?number=${gcashNumber}&amount=${amount}&reference=${reference}`
            );
        } else {
            // Generic mobile: Try common schemes
            deepLinks.push(
                `gcash://send?number=${gcashNumber}&amount=${amount}&message=${reference}`
            );
        }
        
        // Try to open GCash app directly
        tryOpenGCashApp(deepLinks, 0, mobileOS);
        
    } else {
        // Desktop: Show message to use mobile device
        setTimeout(() => {
            alert('GCash Payment requires a mobile device!\n\nPlease:\n1. Open this page on your smartphone\n2. The GCash app will open automatically\n3. If not installed, you\'ll be redirected to install it\n\nPayment Details:\n- Number: <?php echo htmlspecialchars($gcashNumber); ?>\n- Amount: ₱<?php echo number_format($payment['amount'], 2); ?>\n- Reference: <?php echo $payment['reference_number']; ?>');
            
            // Reset button for desktop users
            if (openBtn) {
                openBtn.disabled = false;
                openBtn.classList.remove('btn-info');
                openBtn.classList.add('btn-warning');
                openBtn.innerHTML = '<i class="bi bi-phone me-2"></i>Use Mobile Device';
            }
        }, 500);
    }
    
    // Enable verification button after a short delay
    setTimeout(() => {
        enableVerificationButtonAfterAppOpen();
    }, 3000);
}

// Try to open GCash app with enhanced deep link detection
function tryOpenGCashApp(deepLinks, index, mobileOS) {
    if (index >= deepLinks.length) {
        // All deep links failed, redirect directly to app store
        redirectToAppStore(mobileOS);
        return;
    }
    
    const deepLink = deepLinks[index];
    console.log(`Trying deep link ${index + 1}/${deepLinks.length}: ${deepLink}`);
    
    // Track time when attempting to open app
    const attemptTime = Date.now();
    
    // Create a hidden iframe to test app opening
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = deepLink;
    document.body.appendChild(iframe);
    
    // Also try direct location change for better compatibility
    setTimeout(() => {
        window.location.href = deepLink;
    }, 100);
    
    // Check if app opened after 2.5 seconds
    setTimeout(() => {
        // Remove the iframe
        if (document.body.contains(iframe)) {
            document.body.removeChild(iframe);
        }
        
        // Check if page is hidden (indicating app opened)
        const pageHidden = document.hidden || document.visibilityState === 'hidden';
        const timeElapsed = Date.now() - attemptTime;
        
        if (pageHidden || timeElapsed > 3000) {
            // App opened successfully or user switched away
            console.log('GCash app opened successfully');
            updateButtonSuccess();
        } else {
            // App didn't open, try next deep link quickly
            console.log(`Deep link ${index + 1} failed, trying next...`);
            if (index < deepLinks.length - 1) {
                tryOpenGCashApp(deepLinks, index + 1, mobileOS);
            } else {
                // All attempts failed, redirect to app store
                console.log('All deep links failed, redirecting to app store...');
                redirectToAppStore(mobileOS);
            }
        }
    }, 2500);
}

// Redirect directly to app store when GCash app is not installed
function redirectToAppStore(mobileOS) {
    const isIOS = mobileOS === 'ios';
    const appStoreUrl = isIOS 
        ? 'https://apps.apple.com/ph/app/gcash/id519926866'
        : 'https://play.google.com/store/apps/details?id=com.globe.gcash.android';
    
    // Update button to show redirecting
    const openBtn = document.getElementById('openGCashBtn');
    if (openBtn) {
        openBtn.innerHTML = '<i class="bi bi-download me-2"></i>Redirecting to App Store...';
    }
    
    // Show quick notification
    showToast(`GCash app not found. Redirecting to ${isIOS ? 'App Store' : 'Google Play Store'}...`, 'info');
    
    // Redirect after a brief moment
    setTimeout(() => {
        window.location.href = appStoreUrl;
    }, 1500);
}

// Update button to show success
function updateButtonSuccess() {
    const openBtn = document.getElementById('openGCashBtn');
    if (openBtn) {
        openBtn.classList.remove('btn-info');
        openBtn.classList.add('btn-success');
        openBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>GCash App Opened!';
    }
}

// Show simplified manual instructions (fallback only)
function showEnhancedManualInstructions(mobileOS) {
    // This function is now mainly for edge cases since we redirect to app store directly
    const isIOS = mobileOS === 'ios';
    const appStoreLink = isIOS ? 'https://apps.apple.com/ph/app/gcash/id519926866' : 'https://play.google.com/store/apps/details?id=com.globe.gcash.android';
    
    // Show toast and redirect instead of modal
    showToast(`Redirecting to ${isIOS ? 'App Store' : 'Google Play Store'} to install GCash...`, 'info');
    
    setTimeout(() => {
        window.location.href = appStoreLink;
    }, 2000);
}

// Show payment details modal (mobile-focused)
function showPaymentDetails() {
    const isMobile = isMobileDevice();
    const mobileOS = getMobileOS();
    const isIOS = mobileOS === 'ios';
    const appStoreLink = isIOS ? 'https://apps.apple.com/ph/app/gcash/id519926866' : 'https://play.google.com/store/apps/details?id=com.globe.gcash.android';
    
    const modalContent = `
        <div class="modal fade" id="paymentDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-credit-card me-2"></i>GCash Payment Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${!isMobile ? `
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Mobile Device Required</h6>
                                <p class="mb-0">GCash payments require the mobile app. Please open this page on your smartphone.</p>
                            </div>
                        ` : ''}
                        
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
                            <h6><i class="bi bi-info-circle me-2"></i>How to Pay using GCash Mobile App:</h6>
                            ${isMobile ? 
                                `<ol class="mb-2">
                                    <li>Click "Open GCash App" to launch the mobile app</li>
                                    <li>If app doesn't open, <a href="${appStoreLink}" target="_blank">install GCash app first</a></li>
                                    <li>In GCash app, tap "Send Money"</li>
                                    <li>Enter the number and amount above</li>
                                    <li>Add the reference number in the message</li>
                                    <li>Confirm and send the payment</li>
                                </ol>` :
                                `<p class="mb-2"><strong>You need to use a mobile device:</strong></p>
                                 <ol class="mb-0">
                                    <li>Open this page on your smartphone</li>
                                    <li>Install the <a href="${appStoreLink}" target="_blank">GCash mobile app</a></li>
                                    <li>Use the app to send money with the details above</li>
                                 </ol>`
                            }
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        ${isMobile ? 
                            `<button type="button" class="btn btn-primary" onclick="openGCash(); setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('paymentDetailsModal')).hide(); }, 500);">
                                <i class="bi bi-phone me-2"></i>Open GCash App
                            </button>` :
                            `<a href="${appStoreLink}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-download me-2"></i>Download GCash App
                            </a>`
                        }
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

// Retry opening GCash app (simplified)
function retryOpenGCash() {
    // Reset button state
    const openBtn = document.getElementById('openGCashBtn');
    if (openBtn) {
        openBtn.disabled = false;
        openBtn.classList.remove('btn-success', 'btn-info', 'btn-warning');
        openBtn.classList.add('btn-primary');
        openBtn.innerHTML = '<i class="bi bi-phone me-2"></i><span id="gcashButtonText">Open GCash</span>';
    }
    
    // Try opening GCash again
    setTimeout(() => {
        openGCash();
    }, 500);
}

// Legacy function - redirected to new payment monitoring system
function enableVerificationButton() {
    enableVerificationButtonAfterAppOpen();
}

// Enable verification button after GCash app is opened
function enableVerificationButtonAfterAppOpen() {
    // Mark that app was opened
    gcashAppOpened = true;
    gcashOpenedTime = Date.now();
    
    // Update UI to show app was opened
    const verifyHint = document.getElementById('verifyPaymentHint');
    if (verifyHint) {
        verifyHint.innerHTML = '<i class="bi bi-clock me-1 text-info"></i>GCash app opened! Monitoring for payment completion...';
        verifyHint.className = 'form-text mt-2 text-info';
    }
    
    // Start more frequent payment checking after app is opened
    if (paymentCheckInterval) {
        clearInterval(paymentCheckInterval);
    }
    
    // Check every 5 seconds after app is opened
    paymentCheckInterval = setInterval(() => {
        checkPaymentStatus();
    }, 5000);
    
    // Immediate check
    setTimeout(() => {
        checkPaymentStatus();
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

// Verify payment (enhanced with real-time checking)
function verifyPayment() {
    // Check if payment has already been verified automatically
    if (paymentVerified) {
        window.location.href = 'payment-success.php?id=<?php echo $payment['application_id']; ?>';
        return;
    }
    
    // Security check: Ensure GCash app was opened first
    if (!gcashAppOpened) {
        alert('Please click "Open GCash App" first before verifying payment.');
        return;
    }
    
    // Security check: Ensure minimum time has passed (at least 15 seconds)
    const timeSinceOpened = Date.now() - gcashOpenedTime;
    const minimumTime = 15 * 1000; // 15 seconds
    
    if (timeSinceOpened < minimumTime) {
        const remainingTime = Math.ceil((minimumTime - timeSinceOpened) / 1000);
        alert(`Please wait at least 15 seconds after opening GCash app before verifying payment. Please wait ${remainingTime} more seconds.`);
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying Payment...';
    
    // Check payment status one more time before manual verification
    fetch('check-payment-status.php?payment_id=<?php echo $paymentId; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.verified) {
                // Payment was verified automatically
                window.location.href = 'payment-success.php?id=<?php echo $payment['application_id']; ?>';
            } else if (data.can_verify) {
                // Proceed with manual verification
                window.location.href = '?payment_id=<?php echo $paymentId; ?>&verify=1';
            } else {
                // Cannot verify yet
                btn.disabled = false;
                btn.innerHTML = originalText;
                alert('Payment not yet detected. Please ensure you have completed the payment in GCash and try again in a few moments.');
            }
        })
        .catch(error => {
            console.error('Payment verification failed:', error);
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('Verification failed. Please try again.');
        });
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
    
    // Start payment monitoring
    startPaymentMonitoring();
    
    // Update button text based on device
    updateGCashButtonText();
});

// Update GCash button text based on device (mobile-first approach)
function updateGCashButtonText() {
    const buttonText = document.getElementById('gcashButtonText');
    const instructions = document.getElementById('gcashInstructions');
    const deviceInfo = document.getElementById('deviceInfo');
    const isMobile = isMobileDevice();
    
    if (buttonText) {
        if (isMobile) {
            buttonText.textContent = 'Open GCash App';
        } else {
            buttonText.textContent = 'Requires Mobile Device';
        }
    }
    
    if (instructions) {
        if (isMobile) {
            instructions.textContent = 'Click the button below to open GCash mobile app and complete your payment';
        } else {
            instructions.innerHTML = 'Please open this page on your <strong>smartphone</strong> to use the GCash mobile app';
        }
    }
    
    if (deviceInfo) {
        if (isMobile) {
            const mobileOS = getMobileOS();
            const osText = mobileOS === 'ios' ? 'iOS' : mobileOS === 'android' ? 'Android' : 'Mobile';
            deviceInfo.innerHTML = `<i class="bi bi-phone me-1 text-success"></i>${osText} device detected - will open GCash app`;
        } else {
            deviceInfo.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Desktop device detected - please use mobile device for GCash payments';
        }
    }
}

// Cleanup intervals when page is unloaded
window.addEventListener('beforeunload', function() {
    if (paymentCheckInterval) {
        clearInterval(paymentCheckInterval);
    }
});

// Page visibility change handling (for mobile apps switching)
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible' && gcashAppOpened) {
        // User returned to the page, check payment status immediately
        setTimeout(() => {
            checkPaymentStatus();
        }, 1000);
    }
});
</script>

<?php include 'scripts.php'; ?>

<!-- Support Chat Widget -->
<?php include 'includes/support-widget.php'; ?>
<script src="includes/support-chat-functions.js"></script> 