<?php
require_once 'config.php';

// Generate CSRF token at the very beginning
if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
    (time() - $_SESSION['csrf_token_time']) > 3600) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$pageTitle = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            // Check user credentials
            $stmt = $pdo->prepare("
                SELECT u.*, p.purok_name,
                       CONCAT(pl.first_name, ' ', pl.last_name) as purok_leader_name,
                       CASE 
                           WHEN u.role = 'resident' AND (u.purok_leader_approval = 'approved' AND u.admin_approval = 'approved') THEN 'approved'
                           WHEN u.role = 'resident' AND (u.purok_leader_approval = 'disapproved' OR u.admin_approval = 'disapproved') THEN 'disapproved'
                           WHEN u.role = 'resident' THEN 'pending'
                           ELSE u.status
                       END as effective_status
                FROM users u
                LEFT JOIN puroks p ON u.purok_id = p.id
                LEFT JOIN users pl ON p.purok_leader_id = pl.id
                WHERE u.username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && $password === $user['password']) { // Simple password check (no hashing as requested)
                // Check approval status based on role
                if ($user['role'] === 'resident') {
                    if ($user['purok_leader_approval'] === 'pending' && $user['admin_approval'] === 'pending') {
                        $error = 'Your account is pending approval from both your Purok Leader (' . 
                                ($user['purok_leader_name'] ?? 'Not Assigned') . ') and Admin.';
                    }
                    elseif ($user['purok_leader_approval'] === 'pending') {
                        $error = 'Your account is pending approval from your Purok Leader (' . 
                                ($user['purok_leader_name'] ?? 'Not Assigned') . ').';
                    }
                    elseif ($user['admin_approval'] === 'pending') {
                        $error = 'Your account is pending approval from the Admin.';
                    }
                    elseif ($user['purok_leader_approval'] === 'disapproved' || $user['admin_approval'] === 'disapproved') {
                        $error = 'Your registration has been disapproved. Please contact the barangay office for more information.';
                        
                        // Show specific reason if available
                        if ($user['purok_leader_approval'] === 'disapproved' && !empty($user['purok_leader_remarks'])) {
                            $error .= '<br>Purok Leader Remarks: ' . htmlspecialchars($user['purok_leader_remarks']);
                        }
                        if ($user['admin_approval'] === 'disapproved' && !empty($user['admin_remarks'])) {
                            $error .= '<br>Admin Remarks: ' . htmlspecialchars($user['admin_remarks']);
                        }
                    }
                }
                
                // Check effective status for all roles
                if (empty($error) && $user['effective_status'] !== 'approved') {
                    switch ($user['effective_status']) {
                        case 'pending':
                            $error = 'Your account is still pending approval.';
                            break;
                        case 'disapproved':
                            $error = 'Your account has been disapproved. Please contact the administrator.';
                            break;
                        default:
                            $error = 'Your account is not active. Please contact the administrator.';
                    }
                }
                
                if (empty($error)) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    
                    // Store additional user details in session
                    $_SESSION['purok_id'] = $user['purok_id'];
                    $_SESSION['user_status'] = $user['status'];
                    
                    // Log activity
                    logActivity($user['id'], 'User logged in', 'users', $user['id']);
                    
                    // Redirect to dashboard regardless of role
                            header('Location: dashboard.php');
                    exit();
                }
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}

include 'header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Login to BM-SCaPIS
                    </h4>
                    <small>Enter your credentials to access your account</small>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                       placeholder="Enter your username" required autofocus>
                            </div>
                            <div class="form-text">
                                Your username was provided during registration approval
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Remember me on this device
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Login
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-2">Don't have an account?</p>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus me-2"></i>
                            Register Now
                        </a>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Need help? <a href="contact.php">Contact support</a>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Demo Accounts Info -->
            <div class="card border-0 shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Demo Accounts (For Testing)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-primary">Admin</h6>
                                <small class="d-block"><strong>Username:</strong> admin001</small>
                                <small class="d-block"><strong>Password:</strong> admin123</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-success">Purok Leader</h6>
                                <small class="d-block">Create via admin panel</small>
                                <small class="d-block text-muted">After admin login</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-warning">Resident</h6>
                                <small class="d-block">Register first</small>
                                <small class="d-block text-muted">Wait for approval</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Features -->
            <div class="card border-0 shadow mt-4">
                <div class="card-body">
                    <h6 class="text-primary mb-3">
                        <i class="bi bi-star me-2"></i>System Features
                    </h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <small><i class="bi bi-check-circle text-success me-1"></i> Online Applications</small>
                        </div>
                        <div class="col-6">
                            <small><i class="bi bi-check-circle text-success me-1"></i> Real-time Tracking</small>
                        </div>
                        <div class="col-6">
                            <small><i class="bi bi-check-circle text-success me-1"></i> SMS Notifications</small>
                        </div>
                        <div class="col-6">
                            <small><i class="bi bi-check-circle text-success me-1"></i> Document Management</small>
                        </div>
                        <div class="col-6">
                            <small><i class="bi bi-check-circle text-success me-1"></i> Appointment Scheduling</small>
                        </div>
                        <div class="col-6">
                            <small><i class="bi bi-check-circle text-success me-1"></i> Detailed Reports</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
    
    // Form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!username || !password) {
            e.preventDefault();
            if (typeof showError === 'function') {
                showError('Please enter both username and password.');
            } else {
                alert('Please enter both username and password.');
            }
            return false;
        }
        
        // Show loading if function exists
        if (typeof showLoadingToast === 'function') {
            showLoadingToast('Logging in...');
        }
    });
    
    // Remember username if checked
    const rememberCheckbox = document.getElementById('remember_me');
    const usernameField = document.getElementById('username');
    
    // Load remembered username
    if (localStorage.getItem('remembered_username')) {
        usernameField.value = localStorage.getItem('remembered_username');
        rememberCheckbox.checked = true;
    }
    
    // Save/remove username based on checkbox
    rememberCheckbox.addEventListener('change', function() {
        if (this.checked) {
            localStorage.setItem('remembered_username', usernameField.value);
        } else {
            localStorage.removeItem('remembered_username');
        }
    });
    
    usernameField.addEventListener('input', function() {
        if (rememberCheckbox.checked) {
            localStorage.setItem('remembered_username', this.value);
        }
    });
    
    // Auto-focus on password if username is filled
    if (usernameField.value) {
        document.getElementById('password').focus();
    }
    
    // Enter key navigation
    usernameField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('password').focus();
        }
    });
</script>

<?php include 'scripts.php'; ?>
