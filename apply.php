<?php
require_once 'config.php';

// Require login and must be a resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Apply for Document';
$currentUser = getCurrentUser();

// Get available document types
$stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1");
$stmt->execute();
$documentTypes = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        if (empty($_POST['document_type']) || empty($_POST['purpose'])) {
            throw new Exception('Please fill in all required fields.');
        }

        // Generate application number
        $applicationNumber = 'APP-' . date('Ymd') . '-' . rand(1000, 9999);

        // Insert application
        $stmt = $pdo->prepare("
            INSERT INTO applications (
                application_number, user_id, document_type_id, purpose,
                urgency, status, payment_status, payment_amount
            ) VALUES (?, ?, ?, ?, ?, 'pending', 'unpaid', ?)
        ");

        // Get document fee
        $feeStmt = $pdo->prepare("SELECT fee FROM document_types WHERE id = ?");
        $feeStmt->execute([$_POST['document_type']]);
        $fee = $feeStmt->fetchColumn();

        $stmt->execute([
            $applicationNumber,
            $_SESSION['user_id'],
            $_POST['document_type'],
            $_POST['purpose'],
            $_POST['urgency'] ?? 'Regular',
            $fee
        ]);

        // Create notification for admin
        $notifStmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type, title, message, target_role, metadata
            ) VALUES (
                'application_submitted',
                'New Document Application',
                ?,
                'admin',
                ?
            )
        ");

        $notifStmt->execute([
            "New application {$applicationNumber} submitted by {$currentUser['first_name']} {$currentUser['last_name']}",
            json_encode(['application_id' => $pdo->lastInsertId()])
        ]);

        $_SESSION['success'] = 'Application submitted successfully!';
        header('Location: my-applications.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
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
                        <h1 class="h3 mb-2">Apply for Document</h1>
                        <p class="text-muted mb-0">Fill out the form below to submit your document application.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-text me-2"></i>Application Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label class="form-label">Document Type <span class="text-danger">*</span></label>
                                <select name="document_type" class="form-select" required>
                                    <option value="">Select Document Type</option>
                                    <?php foreach ($documentTypes as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" 
                                                data-fee="<?php echo $type['fee']; ?>"
                                                data-requirements="<?php echo htmlspecialchars($type['requirements']); ?>"
                                                data-processing-days="<?php echo $type['processing_days']; ?>">
                                            <?php echo htmlspecialchars($type['type_name']); ?> 
                                            (₱<?php echo number_format($type['fee'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a document type.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Purpose <span class="text-danger">*</span></label>
                                <textarea name="purpose" class="form-control" rows="3" required></textarea>
                                <div class="invalid-feedback">Please provide the purpose for this document.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Processing Type</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input type="radio" name="urgency" value="Regular" class="form-check-input" checked>
                                        <label class="form-check-label">Regular Processing</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="urgency" value="Rush" class="form-check-input">
                                        <label class="form-check-label">Rush Processing</label>
                                    </div>
                                </div>
                            </div>

                            <div id="requirementsInfo" class="alert alert-info d-none">
                                <h6 class="alert-heading mb-2">Requirements:</h6>
                                <div id="requirementsList"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="dashboard.php" class="btn btn-light">
                                    <i class="bi bi-arrow-left me-2"></i>Back
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-check me-2"></i>Submit Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Fee Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-cash me-2"></i>Fee Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>Processing Fee:</span>
                            <span class="h5 mb-0" id="processingFee">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Processing Time:</span>
                            <span id="processingDays">-</span>
                        </div>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-question-circle me-2"></i>Need Help?
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">If you need assistance with your application, you can:</p>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-telephone me-2"></i>
                                Call us at: +63 123 456 7890
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-envelope me-2"></i>
                                Email: support@malangit.gov.ph
                            </li>
                            <li>
                                <i class="bi bi-clock me-2"></i>
                                Visit us: Mon-Fri, 8:00 AM - 5:00 PM
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentSelect = document.querySelector('select[name="document_type"]');
    const requirementsInfo = document.getElementById('requirementsInfo');
    const requirementsList = document.getElementById('requirementsList');
    const processingFee = document.getElementById('processingFee');
    const processingDays = document.getElementById('processingDays');

    // Update requirements and fee when document type changes
    documentSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const requirements = selectedOption.dataset.requirements;
        const fee = selectedOption.dataset.fee;
        const days = selectedOption.dataset.processingDays;

        if (requirements) {
            requirementsList.innerHTML = requirements.split(',').map(req => 
                `<div class="mb-1"><i class="bi bi-dot me-2"></i>${req.trim()}</div>`
            ).join('');
            requirementsInfo.classList.remove('d-none');
        } else {
            requirementsInfo.classList.add('d-none');
        }

        processingFee.textContent = fee ? `₱${parseFloat(fee).toFixed(2)}` : '₱0.00';
        processingDays.textContent = days ? `${days} working days` : '-';
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>

<?php include 'scripts.php'; ?> 