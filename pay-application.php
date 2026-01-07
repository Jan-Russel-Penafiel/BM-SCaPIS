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

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_receipt'])) {
    $applicationId = $_POST['application_id'] ?? 0;
    
    // Verify application belongs to user
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$applicationId, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        $file = $_FILES['payment_receipt'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if ($file['error'] === 0 && in_array($ext, $allowed) && $file['size'] <= 5000000) {
            // Create upload directory if it doesn't exist
            $uploadDir = 'uploads/payment_receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $newFilename = 'receipt_' . $applicationId . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update application with receipt path and payment date
                $stmt = $pdo->prepare("UPDATE applications SET payment_receipt = ?, payment_date = NOW() WHERE id = ?");
                $stmt->execute([$uploadPath, $applicationId]);
                
                $_SESSION['success'] = 'Payment receipt uploaded successfully! Please wait for admin confirmation.';
                header('Location: my-applications.php');
                exit;
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        } else {
            $error = 'Invalid file. Please upload JPG, PNG, GIF, or PDF (max 5MB).';
        }
    } else {
        $error = 'Invalid application.';
    }
}

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

// Get GCash settings - Use hardcoded values
$gcashNumber = '09753451835';
$gcashAccountName = 'Muhaimin Gani';
$gcashEnabled = true;

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
            <div class="col-lg-8 mx-auto">
                <!-- Payment Information Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>Payment Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">GCash Account Name</label>
                                <p class="fw-bold mb-0 fs-5"><?php echo htmlspecialchars($gcashAccountName); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">GCash Number</label>
                                <p class="fw-bold mb-0 fs-5 text-primary"><?php echo htmlspecialchars($gcashNumber); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment QR Code -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-qr-code me-2"></i>GCash Payment via QR Code
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
                                    <input type="text" class="form-control fw-bold text-primary" 
                                           value="<?php echo number_format($application['fee'], 2); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <hr class="my-3">
                                
                                <div class="text-center mb-4">
                                    <div class="d-flex align-items-center justify-content-center mb-3">
                                        <i class="bi bi-wallet2 text-primary fs-3 me-2"></i>
                                        <h5 class="mb-0">Scan QR Code to Pay</h5>
                                    </div>
                                    <p class="text-muted">Open your GCash app and scan the QR code below</p>
                                </div>
                                
                                <!-- QR Code Display -->
                                <div class="qr-code-container">
                                    <?php if (file_exists('assets/images/gcash.jpg')): ?>
                                        <img src="assets/images/gcash.jpg" alt="GCash QR Code" class="img-fluid qr-code-image">
                                    <?php elseif (file_exists('gcash.jpg')): ?>
                                        <img src="gcash.jpg" alt="GCash QR Code" class="img-fluid qr-code-image">
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            QR Code image not found. Please contact the administrator.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="alert alert-info mt-4">
                                    <h6 class="mb-2"><i class="bi bi-info-circle me-2"></i>How to Pay:</h6>
                                    <ol class="mb-0 ps-3">
                                        <li>Open your <strong>GCash app</strong></li>
                                        <li>Tap <strong>"Scan QR"</strong> on the home screen</li>
                                        <li>Scan the QR code above</li>
                                        <li>Enter the amount: <strong>₱<?php echo number_format($application['fee'], 2); ?></strong></li>
                                        <li>Complete the payment</li>
                                        <li>Take a screenshot of the confirmation</li>
                                        <li><strong>Upload your receipt/screenshot below</strong></li>
                                        <li>Wait for admin to confirm your payment</li>
                                    </ol>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <h6 class="mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Important Reminders:</h6>
                                    <ul class="mb-0 ps-3">
                                        <li>Make sure to pay the <strong>exact amount</strong></li>
                                        <li>Take a clear screenshot/photo of your GCash receipt</li>
                                        <li>Upload your payment proof below for faster confirmation</li>
                                        <li>Contact the barangay office if payment is not confirmed within 24 hours</li>
                                    </ul>
                                </div>
                                
                                <!-- Upload Receipt Form -->
                                <form method="POST" enctype="multipart/form-data" class="mt-4">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Upload Payment Receipt/Screenshot</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($error)): ?>
                                                <div class="alert alert-danger">
                                                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-3">
                                                <label for="payment_receipt" class="form-label fw-bold">
                                                    Select Receipt/Screenshot <span class="text-danger">*</span>
                                                </label>
                                                <input type="file" class="form-control" id="payment_receipt" name="payment_receipt" 
                                                       accept="image/*,.pdf" required>
                                                <div class="form-text">
                                                    Accepted formats: JPG, PNG, GIF, PDF (Max 5MB)
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="my-applications.php" class="btn btn-outline-secondary">
                                                    <i class="bi bi-arrow-left me-2"></i>Cancel
                                                </a>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-upload me-2"></i>Upload Receipt
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.qr-code-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.qr-code-image {
    max-width: 400px;
    width: 100%;
    height: auto;
    border-radius: 0.5rem;
    background: white;
    padding: 1rem;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

@media (max-width: 768px) {
    .qr-code-image {
        max-width: 300px;
    }
    
    .qr-code-container {
        padding: 1rem;
    }
}
</style>

<?php include 'scripts.php'; ?> 