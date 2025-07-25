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
                    <h1 class="h3 mb-0 text-gray-800">Contact Us</h1>
                    <p class="mb-0 text-muted">Get in touch with Barangay Malangit</p>
                </div>
            </div>

            <div class="row">
                <!-- Contact Information -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi bi-geo-alt text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Office Address</h6>
                                            <p class="text-muted mb-0">
                                                Barangay Malangit Hall<br>
                                                Malangit, [Municipality]<br>
                                                [Province], Philippines
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi bi-telephone text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Phone Numbers</h6>
                                            <p class="text-muted mb-0">
                                                Office: (xxx) xxx-xxxx<br>
                                                Mobile: +63 9xx xxx xxxx<br>
                                                Emergency: 911
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi bi-envelope text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Email Address</h6>
                                            <p class="text-muted mb-0">
                                                barangaymalangit@gmail.com<br>
                                                malangit.barangay@gov.ph
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi bi-clock text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Office Hours</h6>
                                            <p class="text-muted mb-0">
                                                Mon-Fri: 8:00 AM - 5:00 PM<br>
                                                Saturday: 8:00 AM - 12:00 PM<br>
                                                Sunday: Closed
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Send us a Message</h6>
                        </div>
                        <div class="card-body">
                            <form id="contactForm" method="POST" action="contact.php">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="subject" class="form-label">Subject *</label>
                                        <select class="form-select" id="subject" name="subject" required>
                                            <option value="">Select a subject</option>
                                            <option value="general_inquiry">General Inquiry</option>
                                            <option value="document_request">Document Request</option>
                                            <option value="system_issue">System Issue</option>
                                            <option value="complaint">Complaint</option>
                                            <option value="suggestion">Suggestion</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Please provide details about your inquiry or concern..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="agree" name="agree" required>
                                        <label class="form-check-label" for="agree">
                                            I agree to the collection and processing of my personal data for the purpose of responding to this inquiry.
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>Send Message
                                </button>
                            </form>

                            <?php
                            // Handle form submission
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
                                $name = htmlspecialchars($_POST['name']);
                                $email = htmlspecialchars($_POST['email']);
                                $phone = htmlspecialchars($_POST['phone'] ?? '');
                                $subject = htmlspecialchars($_POST['subject']);
                                $message = htmlspecialchars($_POST['message']);
                                
                                // Here you would typically send an email or save to database
                                // For now, we'll just show a success message
                                echo '<div class="alert alert-success mt-3">';
                                echo '<i class="bi bi-check-circle me-2"></i>';
                                echo 'Thank you for your message! We will get back to you within 24-48 hours.';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Links & Officials -->
                <div class="col-lg-4">
                    <!-- Quick Links -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Quick Links</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <a href="services.php" class="text-decoration-none">
                                        <i class="bi bi-arrow-right-circle text-primary me-2"></i>
                                        Available Services
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="register.php" class="text-decoration-none">
                                        <i class="bi bi-arrow-right-circle text-primary me-2"></i>
                                        Resident Registration
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="login.php" class="text-decoration-none">
                                        <i class="bi bi-arrow-right-circle text-primary me-2"></i>
                                        Login Portal
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="about.php" class="text-decoration-none">
                                        <i class="bi bi-arrow-right-circle text-primary me-2"></i>
                                        About BM-SCaPIS
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Barangay Officials -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Barangay Officials</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                    <i class="bi bi-person text-white" style="font-size: 1.5rem;"></i>
                                </div>
                                <h6 class="mb-1">Barangay Captain</h6>
                                <p class="text-muted small mb-0">[Captain Name]</p>
                            </div>
                            
                            <hr>
                            
                            <h6 class="mb-2">Barangay Kagawads</h6>
                            <ul class="list-unstyled small">
                                <li>• [Kagawad 1 Name]</li>
                                <li>• [Kagawad 2 Name]</li>
                                <li>• [Kagawad 3 Name]</li>
                                <li>• [Kagawad 4 Name]</li>
                                <li>• [Kagawad 5 Name]</li>
                                <li>• [Kagawad 6 Name]</li>
                                <li>• [Kagawad 7 Name]</li>
                            </ul>
                            
                            <hr>
                            
                            <div class="small">
                                <strong>SK Chairperson:</strong> [SK Chair Name]<br>
                                <strong>Barangay Secretary:</strong> [Secretary Name]<br>
                                <strong>Barangay Treasurer:</strong> [Treasurer Name]
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contacts -->
                    <div class="card shadow border-left-danger">
                        <div class="card-header py-3 bg-danger text-white">
                            <h6 class="m-0 font-weight-bold">Emergency Contacts</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-telephone-fill text-danger me-2"></i>
                                <strong>Emergency Hotline: 911</strong>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-shield-fill-check text-primary me-2"></i>
                                <span>Police: 117</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-fire text-danger me-2"></i>
                                <span>Fire Department: 116</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-hospital text-success me-2"></i>
                                <span>Medical Emergency: 911</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const subject = document.getElementById('subject').value;
    const message = document.getElementById('message').value.trim();
    const agree = document.getElementById('agree').checked;
    
    if (!name || !email || !subject || !message || !agree) {
        e.preventDefault();
        alert('Please fill in all required fields and agree to the terms.');
        return false;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address.');
        return false;
    }
});
</script>

<?php require_once 'footer.php'; ?>
