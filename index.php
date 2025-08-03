<?php
require_once 'config.php';

$pageTitle = 'Welcome to ' . BARANGAY_NAME;
include 'header.php';
// Sidebar not included for landing page
?>

<div class="main-content">
    <!-- Hero Section -->
    <section class="hero-section position-relative vh-100 d-flex align-items-center">
        <!-- Background with overlay -->
        <div class="position-absolute top-0 start-0 w-100 h-100" 
             style="background: linear-gradient(135deg, rgba(44, 90, 160, 0.95), rgba(61, 109, 176, 0.95)), url('assets/images/barangay-bg.jpg') center/cover no-repeat;">
        </div>
        
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content text-white py-4">
                        <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInUp">
                            Welcome to <br>
                            <span class="text-warning"><?php echo BARANGAY_NAME; ?></span>
                        </h1>
                        <h2 class="h3 mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                            Smart Clearance and Permit Issuance System
                        </h2>
                        <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-2s">
                            Get your barangay clearance and permits online! Fast, convenient, and secure document processing 
                            with real-time tracking and SMS notifications.
                        </p>
                        <div class="d-flex flex-wrap gap-3 animate__animated animate__fadeInUp animate__delay-3s">
                            <?php if (!isLoggedIn()): ?>
                                <a href="register.php" class="btn btn-warning btn-lg">
                                    <i class="bi bi-person-plus me-2"></i>Register Now
                                </a>
                                <a href="login.php" class="btn btn-outline-light btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </a>
                            <?php else: ?>
                                <a href="dashboard.php" class="btn btn-warning btn-lg">
                                    <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
                                </a>
                                <?php if ($_SESSION['role'] === 'resident'): ?>
                                    <a href="apply.php" class="btn btn-outline-light btn-lg">
                                        <i class="bi bi-file-earmark-plus me-2"></i>Apply for Document
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-image text-center animate__animated animate__fadeInRight">
                        <img src="assets/images/logo-512.png?v=1.0.4" alt="Digital Services" class="img-fluid" style="max-height: 500px;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section-narrow bg-light">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="display-5 fw-bold text-primary mb-3">Why Choose BM-SCaPIS?</h2>
                <p class="lead text-muted px-lg-5 mx-lg-5">Experience the future of barangay services with our smart, efficient, and user-friendly system.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle mb-3 mx-auto" 
                                 style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-clock fs-3"></i>
                            </div>
                            <h5 class="card-title">24/7 Online Access</h5>
                            <p class="card-text text-muted">Apply for documents anytime, anywhere. No need to queue during office hours.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-success bg-opacity-10 text-success rounded-circle mb-3 mx-auto" 
                                 style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-shield-check fs-3"></i>
                            </div>
                            <h5 class="card-title">Secure & Verified</h5>
                            <p class="card-text text-muted">Two-step verification process ensures authentic residents and secure transactions.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-info bg-opacity-10 text-info rounded-circle mb-3 mx-auto" 
                                 style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-phone fs-3"></i>
                            </div>
                            <h5 class="card-title">SMS Notifications</h5>
                            <p class="card-text text-muted">Get real-time updates on your application status via SMS alerts.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-warning bg-opacity-10 text-warning rounded-circle mb-3 mx-auto" 
                                 style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-graph-up fs-3"></i>
                            </div>
                            <h5 class="card-title">Real-time Tracking</h5>
                            <p class="card-text text-muted">Track your application progress in real-time with detailed status updates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold text-primary mb-3">Available Services</h2>
                    <p class="lead text-muted">Choose from our wide range of barangay documents and permits.</p>
                </div>
            </div>
            
            <div class="row g-4">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1 ORDER BY type_name");
                $stmt->execute();
                $documentTypes = $stmt->fetchAll();
                
                foreach ($documentTypes as $docType):
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 service-card">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start">
                                    <div class="service-icon me-3">
                                        <i class="bi bi-file-earmark-text text-primary fs-2"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title"><?php echo htmlspecialchars($docType['type_name']); ?></h5>
                                        <p class="card-text text-muted small"><?php echo htmlspecialchars($docType['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="text-primary fw-bold">₱<?php echo number_format($docType['fee'], 2); ?></span>
                                            <small class="text-muted"><?php echo $docType['processing_days']; ?> day(s)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <?php if (isLoggedIn() && $_SESSION['role'] === 'resident'): ?>
                    <a href="apply.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-file-earmark-plus"></i> Apply for Document
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-person-plus"></i> Register to Apply
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">How It Works</h2>
                    <p class="lead">Get your documents in 4 easy steps</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="text-center">
                        <div class="step-number bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                            1
                        </div>
                        <h5>Register Account</h5>
                        <p>Create your account and wait for verification from your Purok Leader and Admin.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="text-center">
                        <div class="step-number bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                            2
                        </div>
                        <h5>Submit Application</h5>
                        <p>Choose your document type, fill out the form, and submit your application online.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="text-center">
                        <div class="step-number bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                            3
                        </div>
                        <h5>Track Progress</h5>
                        <p>Monitor your application status in real-time and receive SMS notifications.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="text-center">
                        <div class="step-number bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                            4
                        </div>
                        <h5>Collect Document</h5>
                        <p>Visit the barangay office to collect your processed document and pay fees.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold text-primary">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'resident' AND status = 'approved'");
                            $stmt->execute();
                            echo number_format($stmt->fetchColumn());
                            ?>
                        </h3>
                        <p class="text-muted">Registered Residents</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold text-success">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'completed'");
                            $stmt->execute();
                            echo number_format($stmt->fetchColumn());
                            ?>
                        </h3>
                        <p class="text-muted">Documents Issued</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold text-info">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM puroks");
                            $stmt->execute();
                            echo number_format($stmt->fetchColumn());
                            ?>
                        </h3>
                        <p class="text-muted">Puroks Served</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold text-warning">98%</h3>
                        <p class="text-muted">Satisfaction Rate</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="fw-bold text-primary mb-4">Need Help?</h2>
                    <p class="lead mb-4">Our team is here to assist you with any questions about the system or document requirements.</p>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="bi bi-geo-alt text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">Visit Us</h6>
                                    <p class="text-muted mb-0"><?php echo BARANGAY_NAME; ?> Hall<br>Malangit, Philippines</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="bi bi-telephone text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">Call Us</h6>
                                    <p class="text-muted mb-0">+63 123 456 7890<br>Monday - Friday, 8:00 AM - 5:00 PM</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="bi bi-envelope text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">Email Us</h6>
                                    <p class="text-muted mb-0">info@malangit.gov.ph<br>support@bm-scapis.com</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="bi bi-clock text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">Office Hours</h6>
                                    <p class="text-muted mb-0">Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 8:00 AM - 12:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card border-0 shadow">
                        <div class="card-body p-4">
                            <h5 class="card-title text-center mb-4">Quick Links</h5>
                            <div class="d-grid gap-2">
                                <a href="about.php" class="btn btn-outline-primary">
                                    <i class="bi bi-info-circle me-2"></i> About Us
                                </a>
                                <a href="services.php" class="btn btn-outline-primary">
                                    <i class="bi bi-list-ul me-2"></i> All Services
                                </a>
                                <a href="contact.php" class="btn btn-outline-primary">
                                    <i class="bi bi-telephone me-2"></i> Contact Us
                                </a>
                                <?php if (!isLoggedIn()): ?>
                                    <a href="register.php" class="btn btn-primary">
                                        <i class="bi bi-person-plus me-2"></i> Get Started
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PWA Install Banner -->
    <div class="pwa-install-banner" id="pwaInstallBanner">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">Install BM-SCaPIS App</h6>
                <small>Add to your home screen for quick access!</small>
            </div>
            <div>
                <button class="btn btn-warning btn-sm me-2" id="pwaInstallBtn">Install</button>
                <button class="btn btn-outline-light btn-sm" id="pwaCloseBanner">&times;</button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    // PWA Installation
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        document.getElementById('pwaInstallBanner').classList.add('show');
    });
    
    document.getElementById('pwaInstallBtn')?.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            document.getElementById('pwaInstallBanner').classList.remove('show');
        }
    });
    
    document.getElementById('pwaCloseBanner')?.addEventListener('click', () => {
        document.getElementById('pwaInstallBanner').classList.remove('show');
    });
    
    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(registration => console.log('SW registered'))
                .catch(error => console.log('SW registration failed'));
        });
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.card, .stat-item').forEach(el => {
        observer.observe(el);
    });
</script>

<?php include 'scripts.php'; ?>

<style>
/* Add these styles to enhance the UI */
.hover-lift {
    transition: transform 0.2s ease-in-out;
}

.hover-lift:hover {
    transform: translateY(-5px);
}

.hero-content {
    z-index: 1;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .hero-section {
        min-height: auto !important;
        padding: 6rem 0 3rem;
        margin-top: 0 !important;
    }
    
    .hero-content {
        text-align: center;
        padding: 2rem 0;
    }
    
    .hero-content .d-flex {
        justify-content: center;
    }
    
    .display-4 {
        font-size: calc(1.525rem + 2.1vw);
    }
}

/* Ensure main content is properly centered */
.main-content {
    margin-left: 0 !important;
    margin-right: 0;
    width: 100%;
    padding: 0 !important;
}

/* Hero section improvements */
.hero-section {
    margin-top: 0;
    padding-top: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
}

/* Container improvements for better centering */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

/* Ensure full width for sections */
section {
    width: 100%;
    margin: 0;
    padding: 4rem 0;
}

/* Override sidebar margin for landing page */
.main-content {
    margin-left: 0 !important;
    padding: 0 !important;
}

/* Section spacing improvements */
section {
    padding: 4rem 0;
    overflow: hidden;
}

.section-narrow {
    padding: 3rem 0;
}

/* Card improvements */
.card {
    height: 100%;
    transition: all 0.3s ease;
}

/* Add animation classes */
.animate__animated {
    animation-duration: 1s;
    animation-fill-mode: both;
}

.animate__fadeInUp {
    animation-name: fadeInUp;
}

.animate__fadeInRight {
    animation-name: fadeInRight;
}

.animate__delay-1s {
    animation-delay: 0.2s;
}

.animate__delay-2s {
    animation-delay: 0.4s;
}

.animate__delay-3s {
    animation-delay: 0.6s;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translate3d(0, 40px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

@keyframes fadeInRight {
    from {
        opacity: 0;
        transform: translate3d(40px, 0, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}
</style>
