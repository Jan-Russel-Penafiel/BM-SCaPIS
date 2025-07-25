<!-- Footer -->
<footer class="footer bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5 class="mb-3">
                    <i class="bi bi-building me-2"></i>
                    <?php echo SYSTEM_NAME; ?>
                </h5>
                <p class="text-light">
                    <?php echo BARANGAY_NAME; ?> Smart Clearance and Permit Issuance System - 
                    Making government services accessible, efficient, and transparent for all residents.
                </p>
                <div class="social-links">
                    <a href="#" class="text-light me-3"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="#" class="text-light me-3"><i class="bi bi-twitter fs-5"></i></a>
                    <a href="#" class="text-light me-3"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="#" class="text-light"><i class="bi bi-youtube fs-5"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <h6 class="mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="text-light text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="about.php" class="text-light text-decoration-none">About</a></li>
                    <li class="mb-2"><a href="services.php" class="text-light text-decoration-none">Services</a></li>
                    <li class="mb-2"><a href="contact.php" class="text-light text-decoration-none">Contact</a></li>
                    <?php if (!isLoggedIn()): ?>
                        <li class="mb-2"><a href="register.php" class="text-light text-decoration-none">Register</a></li>
                        <li class="mb-2"><a href="login.php" class="text-light text-decoration-none">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <h6 class="mb-3">Services</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none">Barangay Clearance</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none">Certificate of Residency</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none">Certificate of Indigency</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none">Business Permit</a></li>
                    <li class="mb-2"><a href="#" class="text-light text-decoration-none">Building Permit</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3">
                <h6 class="mb-3">Contact Info</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="bi bi-geo-alt me-2"></i>
                        <?php echo BARANGAY_NAME; ?> Hall, Malangit
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-telephone me-2"></i>
                        +63 123 456 7890
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-envelope me-2"></i>
                        info@malangit.gov.ph
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-clock me-2"></i>
                        Mon-Fri: 8:00 AM - 5:00 PM
                    </li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4">
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-light">
                    &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?>. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item">
                        <a href="privacy-policy.php" class="text-light text-decoration-none small">Privacy Policy</a>
                    </li>
                    <li class="list-inline-item">
                        <span class="text-muted">|</span>
                    </li>
                    <li class="list-inline-item">
                        <a href="terms-of-service.php" class="text-light text-decoration-none small">Terms of Service</a>
                    </li>
                    <li class="list-inline-item">
                        <span class="text-muted">|</span>
                    </li>
                    <li class="list-inline-item">
                        <a href="help.php" class="text-light text-decoration-none small">Help</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Back to top button -->
<button type="button" class="btn btn-primary btn-floating position-fixed bottom-0 end-0 m-3 d-none" id="backToTopBtn" style="z-index: 1000;">
    <i class="bi bi-arrow-up"></i>
</button>

<!-- Cookie Consent Banner -->
<div class="position-fixed bottom-0 start-0 end-0 bg-dark text-white p-3 d-none" id="cookieConsent" style="z-index: 1001;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="me-3">
                <small>
                    This website uses cookies to ensure you get the best experience. 
                    <a href="privacy-policy.php" class="text-warning">Learn more</a>
                </small>
            </div>
            <div>
                <button class="btn btn-warning btn-sm me-2" onclick="acceptCookies()">Accept</button>
                <button class="btn btn-outline-light btn-sm" onclick="declineCookies()">Decline</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Back to top button functionality
    window.addEventListener('scroll', function() {
        const backToTopBtn = document.getElementById('backToTopBtn');
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.remove('d-none');
        } else {
            backToTopBtn.classList.add('d-none');
        }
    });
    
    document.getElementById('backToTopBtn')?.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Cookie consent functionality
    function showCookieConsent() {
        if (!localStorage.getItem('cookieConsent')) {
            setTimeout(() => {
                document.getElementById('cookieConsent').classList.remove('d-none');
            }, 2000);
        }
    }
    
    function acceptCookies() {
        localStorage.setItem('cookieConsent', 'accepted');
        document.getElementById('cookieConsent').classList.add('d-none');
    }
    
    function declineCookies() {
        localStorage.setItem('cookieConsent', 'declined');
        document.getElementById('cookieConsent').classList.add('d-none');
    }
    
    // Show cookie consent on page load
    document.addEventListener('DOMContentLoaded', showCookieConsent);
</script>
</body>
</html>
