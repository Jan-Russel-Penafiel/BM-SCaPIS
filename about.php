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
                    <h1 class="h3 mb-0 text-gray-800">About BM-SCaPIS</h1>
                    <p class="mb-0 text-muted">Barangay Malangit Smart Clearance and Permit Issuance System - Pandag, Maguindanao Del Sur</p>
                </div>
            </div>

            <!-- About System Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">About Our System</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <h5>Mission</h5>
                            <p class="text-justify">
                                To provide efficient, transparent, and accessible government services to the residents of Barangay Malangit, Pandag, Maguindanao Del Sur through digital transformation and smart technology solutions.
                            </p>

                            <h5>Vision</h5>
                            <p class="text-justify">
                                A progressive and digitally-enabled barangay that delivers excellent public services, promotes transparency, and fosters community development through technology.
                            </p>

                            <h5>System Features</h5>
                            <ul>
                                <li><strong>Digital Document Processing:</strong> Streamlined application and processing of barangay clearances, permits, and certificates</li>
                                <li><strong>SMS Notifications:</strong> Real-time updates on application status and important announcements</li>
                                <li><strong>User Management:</strong> Secure registration and approval system with multi-level verification</li>
                                <li><strong>Payment Tracking:</strong> Transparent fee structure and payment monitoring</li>
                                <li><strong>Appointment System:</strong> Convenient scheduling for document pickup and verification</li>
                                <li><strong>Purok Management:</strong> Organized community structure with designated leaders</li>
                                <li><strong>Reporting & Analytics:</strong> Comprehensive reports for better governance and decision-making</li>
                            </ul>
                        </div>
                        <div class="col-lg-4">
                            <div class="text-center">
                                <img src="assets/images/barangay-logo.png" alt="Barangay Logo" class="img-fluid mb-3" style="max-width: 200px;" onerror="this.style.display='none'">
                                <h6>Barangay Malangit</h6>
                                <p class="text-muted small">Smart Clearance and Permit Issuance System</p>
                            </div>

                            <div class="mt-4">
                                <h6>System Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Version:</strong></td>
                                        <td><?php echo SYSTEM_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Launch Date:</strong></td>
                                        <td>January 2025</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database:</strong></td>
                                        <td>MySQL</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Platform:</strong></td>
                                        <td>Web-based</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services Offered Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Services Offered</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // Get document types from database
                        $stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1 ORDER BY type_name");
                        $stmt->execute();
                        $documentTypes = $stmt->fetchAll();

                        foreach ($documentTypes as $doc) {
                            echo '<div class="col-md-6 col-lg-4 mb-3">';
                            echo '<div class="card h-100 border-left-primary">';
                            echo '<div class="card-body">';
                            echo '<h6 class="card-title text-primary">' . htmlspecialchars($doc['type_name']) . '</h6>';
                            echo '<p class="card-text small">' . htmlspecialchars($doc['description']) . '</p>';
                            echo '<div class="d-flex justify-content-between align-items-center">';
                            echo '<span class="text-success font-weight-bold">₱' . number_format($doc['fee'], 2) . '</span>';
                            echo '<span class="text-muted small">' . $doc['processing_days'] . ' days</span>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Barangay Office</h6>
                            <p>
                                <i class="bi bi-geo-alt text-primary me-2"></i>
                                Barangay Malangit Hall<br>
                                Pandag, Maguindanao Del Sur<br>
                                <span class="ms-4">Malangit, [Municipality], [Province]</span>
                            </p>
                            <p>
                                <i class="bi bi-telephone text-primary me-2"></i>
                                (xxx) xxx-xxxx
                            </p>
                            <p>
                                <i class="bi bi-envelope text-primary me-2"></i>
                                barangaymalangit@gmail.com
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Office Hours</h6>
                            <p>
                                <i class="bi bi-clock text-primary me-2"></i>
                                Monday - Friday: 8:00 AM - 5:00 PM<br>
                                <span class="ms-4">Saturday: 8:00 AM - 12:00 PM</span><br>
                                <span class="ms-4">Sunday: Closed</span>
                            </p>
                            
                            <h6 class="mt-3">Emergency Hotline</h6>
                            <p>
                                <i class="bi bi-telephone-fill text-danger me-2"></i>
                                911 / (xxx) xxx-xxxx
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
