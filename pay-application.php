<?php
require_once 'config.php';

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

// If application not found, already paid, or doesn't belong to user
if (!$application) {
    header('Location: my-applications.php');
    exit;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            $referenceNumber = trim($_POST['reference_number'] ?? '');
            
            // Validate GCash reference number
            if (!preg_match('/^[0-9]{13}$/', $referenceNumber)) {
                throw new Exception('Please enter a valid 13-digit GCash reference number.');
            }
            
            // Handle receipt upload
            if (!isset($_FILES['receipt_image']) || $_FILES['receipt_image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Please upload your GCash payment receipt.');
            }
            
            // Validate file type
            $fileInfo = getimagesize($_FILES['receipt_image']['tmp_name']);
            if (!$fileInfo || !in_array($fileInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                throw new Exception('Please upload a valid image file (JPG or PNG).');
            }
            
            // Generate unique filename
            $uploadDir = 'uploads/receipts/';
            $extension = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
            $receiptFilename = uniqid('receipt_') . '.' . $extension;
            
            // Ensure upload directory exists
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['receipt_image']['tmp_name'], $uploadDir . $receiptFilename)) {
                throw new Exception('Failed to upload receipt. Please try again.');
            }
            
            // Update application payment status and automatically start processing
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET payment_status = 'paid',
                    payment_date = CURRENT_TIMESTAMP,
                    payment_method = 'gcash',
                    payment_reference = ?,
                    payment_receipt = ?,
                    status = 'processing',
                    processed_by = NULL
                WHERE id = ?
            ");
            $stmt->execute([$referenceNumber, $receiptFilename, $applicationId]);
            
            // Add to application history
            $stmt = $pdo->prepare("
                INSERT INTO application_history (
                    application_id, status, remarks, changed_by
                ) VALUES (?, 'processing', ?, ?)
            ");
            $stmt->execute([
                $applicationId,
                'GCash payment received. Reference: ' . $referenceNumber,
                $_SESSION['user_id']
            ]);
            
            // Send SMS notification to resident about payment and processing start
            $result = sendPaymentNotificationSMS($applicationId, 'gcash', $application['fee'], $referenceNumber);
            if (!$result['success']) {
                error_log('Resident SMS notification failed: ' . $result['message']);
            }
            
            // Log activity
            logActivity(
                $_SESSION['user_id'],
                'GCash payment submitted for application #' . $application['application_number'],
                'applications',
                $applicationId
            );
            
            // Send SMS notification to admin about payment received
            $adminMessage = "GCash payment received for application #{$application['application_number']}. ";
            $adminMessage .= "Amount: ₱" . number_format($application['fee'], 2) . ". ";
            $adminMessage .= "Reference: {$referenceNumber}";
            
            $result = sendAdminNotificationSMS($adminMessage, 'payment_received');
            if (!$result['success']) {
                error_log('Admin SMS notification failed: ' . $result['message']);
            }
            
            // Send notification to admin
            $stmt = $pdo->prepare("
                INSERT INTO system_notifications (
                    type, title, message, target_role, metadata
                ) VALUES (
                    'payment_received',
                    'GCash Payment Received',
                    ?,
                    'admin',
                    ?
                )
            ");
            $stmt->execute([
                'GCash payment received for application #' . $application['application_number'],
                json_encode([
                    'application_id' => $applicationId,
                    'payment_method' => 'gcash',
                    'reference_number' => $referenceNumber,
                    'receipt_file' => $receiptFilename,
                    'amount' => $application['fee']
                ])
            ]);
            
            $pdo->commit();
            
            // Set success message and redirect
            $_SESSION['success'] = 'Payment submitted successfully! Your application is now being processed.';
            header('Location: view-application.php?id=' . $applicationId);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
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
                            <i class="bi bi-credit-card me-2"></i>Payment Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="paymentForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
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
                                            <h6 class="mb-0">GCash Payment Details</h6>
                                        </div>
                                        <p class="mb-0">Send your payment to:</p>
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white">
                                                        <i class="bi bi-phone"></i>
                                                    </span>
                                                    <input type="text" class="form-control" value="0912-345-6789" readonly>
                                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('0912-345-6789')">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">GCash Number</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white">
                                                        <i class="bi bi-person"></i>
                                                    </span>
                                                    <input type="text" class="form-control" value="BRGY MALANGIT" readonly>
                                                    <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('BRGY MALANGIT')">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">Account Name</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white">₱</span>
                                                    <input type="text" class="form-control" 
                                                           value="<?php echo number_format($application['fee'], 2); ?>" readonly>
                                                    <button class="btn btn-outline-primary" type="button" 
                                                            onclick="copyToClipboard('<?php echo number_format($application['fee'], 2); ?>')">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">Amount to Send</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white">
                                                        <i class="bi bi-hash"></i>
                                                    </span>
                                                    <input type="text" class="form-control" 
                                                           value="<?php echo $application['application_number']; ?>" readonly>
                                                    <button class="btn btn-outline-primary" type="button" 
                                                            onclick="copyToClipboard('<?php echo $application['application_number']; ?>')">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text">Reference/Application Number</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">GCash Reference Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="bi bi-upc"></i>
                                        </span>
                                        <input type="text" class="form-control" name="reference_number" 
                                               placeholder="Enter GCash reference number" required
                                               pattern="[0-9]{13}" title="Please enter a valid 13-digit GCash reference number">
                                    </div>
                                    <div class="form-text">
                                        Enter the 13-digit reference number from your GCash transaction
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Upload GCash Receipt <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="receipt_image" 
                                           accept="image/jpeg,image/png" required>
                                    <div class="form-text">
                                        Upload a screenshot of your GCash payment confirmation
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Your application will be processed once payment is verified
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Confirm Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Payment Instructions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>How to Pay with GCash
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h6>Open GCash App</h6>
                                    <p class="text-muted mb-0">Launch your GCash mobile application</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h6>Send Money</h6>
                                    <p class="text-muted mb-0">Click "Send" and choose "Send Money to GCash"</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h6>Enter Details</h6>
                                    <p class="text-muted mb-0">Enter the GCash number and amount shown on the left</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h6>Add Reference</h6>
                                    <p class="text-muted mb-0">In the notes, paste your Application Number</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h6>Review & Send</h6>
                                    <p class="text-muted mb-0">Double-check all details before sending</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-number">6</div>
                                <div class="step-content">
                                    <h6>Save Receipt</h6>
                                    <p class="text-muted mb-0">Take a screenshot of the confirmation page</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-4 mb-0">
                            <div class="d-flex">
                                <i class="bi bi-exclamation-triangle fs-4 me-2"></i>
                                <div>
                                    <h6 class="mb-1">Important Notes:</h6>
                                    <ul class="mb-0 ps-3">
                                        <li>Make sure to copy the exact amount</li>
                                        <li>Include your Application Number in notes</li>
                                        <li>Keep your payment screenshot</li>
                                        <li>Save the 13-digit reference number</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .steps {
                position: relative;
                padding-left: 50px;
            }
            
            .step {
                position: relative;
                padding-bottom: 1.5rem;
            }
            
            .step:last-child {
                padding-bottom: 0;
            }
            
            .step-number {
                position: absolute;
                left: -50px;
                width: 32px;
                height: 32px;
                background-color: var(--primary-color);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            }
            
            .step:not(:last-child)::after {
                content: '';
                position: absolute;
                left: -34px;
                top: 32px;
                bottom: 0;
                width: 2px;
                background-color: #e9ecef;
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
// Copy to clipboard function
function copyToClipboard(text) {
    // Create temporary input
    const input = document.createElement('input');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    
    try {
        // Execute copy command
        document.execCommand('copy');
        
        // Show success toast
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '5';
        toast.innerHTML = `
            <div class="toast show align-items-center text-white bg-success border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check-circle me-2"></i>Copied to clipboard!
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.remove();
        }, 3000);
    } catch (err) {
        alert('Failed to copy text. Please try manually selecting and copying.');
    }
    
    // Cleanup
    document.body.removeChild(input);
}

// Form submission
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    // Validate file input
    const fileInput = document.querySelector('input[name="receipt_image"]');
    if (fileInput.files.length === 0) {
        e.preventDefault();
        alert('Please upload your GCash payment receipt.');
        return;
    }
    
    // Validate reference number
    const refInput = document.querySelector('input[name="reference_number"]');
    if (!/^[0-9]{13}$/.test(refInput.value)) {
        e.preventDefault();
        alert('Please enter a valid 13-digit GCash reference number.');
        return;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing Payment...';
});
</script>

<?php include 'scripts.php'; ?> 