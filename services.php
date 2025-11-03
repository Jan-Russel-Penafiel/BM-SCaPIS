<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Services</h1>
                    <p class="mb-0 text-muted">Available barangay services and their requirements</p>
                </div>
            </div>

            <!-- Services Grid -->
            <div class="row">
                <?php
                // Get document types from database
                $stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1 ORDER BY type_name");
                $stmt->execute();
                $documentTypes = $stmt->fetchAll();

                foreach ($documentTypes as $doc) {
                    ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    <?php echo htmlspecialchars($doc['type_name']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo htmlspecialchars($doc['description']); ?></p>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <strong>Processing Fee:</strong><br>
                                        <span class="text-success h5">₱<?php echo number_format($doc['fee'], 2); ?></span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Processing Time:</strong><br>
                                        <span class="text-info">3 to 5 working days<br><small class="text-muted">(except holidays)</small></span>
                                    </div>
                                </div>

                                <?php if (!empty($doc['requirements'])): ?>
                                <div class="requirements">
                                    <strong>Requirements:</strong>
                                    <ul class="list-unstyled mt-2">
                                        <?php
                                        $requirements = explode(',', $doc['requirements']);
                                        foreach ($requirements as $req) {
                                            echo '<li><i class="bi bi-check-circle text-success me-2"></i>' . htmlspecialchars(trim($req)) . '</li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <?php if (isLoggedIn() && $_SESSION['role'] === 'resident'): ?>
                                    <a href="apply.php?type=<?php echo $doc['id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Apply Now
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Apply
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <!-- How to Apply Section -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">How to Apply</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-person-plus text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h6>Step 1: Register</h6>
                            <p class="small text-muted">Create your account and wait for approval</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-file-earmark-plus text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h6>Step 2: Apply</h6>
                            <p class="small text-muted">Submit your application with required documents</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-credit-card text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h6>Step 3: Pay</h6>
                            <p class="small text-muted">Pay the processing fee at the barangay office</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-download text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h6>Step 4: Pickup</h6>
                            <p class="small text-muted">Receive SMS notification and pickup your document</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Frequently Asked Questions</h6>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                    What documents do I need to bring for registration?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You need to bring a valid government-issued ID, proof of residence in Barangay Malangit, and recent passport-sized photos.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                    How long does it take to process applications?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Processing times vary by document type, ranging from 2-7 working days. You can check the specific processing time for each service above.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                    Can I track my application status?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! You can log in to your account to check your application status, and you'll also receive SMS notifications for important updates.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
                                    What payment methods are accepted?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Currently, we accept cash payments at the barangay office. Digital payment options may be available in the future.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
