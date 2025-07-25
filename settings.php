<?php
require_once 'config.php';
require_once 'classes/Settings.php';

// Require login
requireLogin();

$pageTitle = 'Settings';
$currentUser = getCurrentUser();
$error = '';
$success = '';

// Initialize Settings class for system settings
$settings = Settings::getInstance($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            if (isset($_POST['update_system_settings']) && $_SESSION['role'] === 'admin') {
                // Handle system settings update (admin only)
                $settings->set('system_name', trim($_POST['system_name']));
                $settings->set('barangay_name', trim($_POST['barangay_name']));
                $settings->set('philsms_api_key', trim($_POST['philsms_api_key']));
                $settings->set('philsms_sender_name', trim($_POST['philsms_sender_name']));
                $settings->set('ringtone_enabled', isset($_POST['ringtone_enabled']) ? '1' : '0');
                
                $success = 'System settings updated successfully!';
                
            } elseif (isset($_POST['update_account_settings'])) {
                // Handle user account settings update
                $currentPassword = trim($_POST['current_password'] ?? '');
                $newPassword = trim($_POST['new_password'] ?? '');
                $confirmPassword = trim($_POST['confirm_password'] ?? '');
                
                // Validate current password
                if (empty($currentPassword) || $currentPassword !== $currentUser['password']) {
                    throw new Exception('Current password is incorrect.');
                }
                
                // Validate new password
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8) {
                        throw new Exception('New password must be at least 8 characters long.');
                    }
                    
                    if ($newPassword !== $confirmPassword) {
                        throw new Exception('New passwords do not match.');
                    }
                    
                    // Update password
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$newPassword, $_SESSION['user_id']]);
                    
                    // Log the password change
                    logActivity($_SESSION['user_id'], 'Changed account password', 'users', $_SESSION['user_id']);
                }
                
                // Update notification preferences
                $enableSMS = isset($_POST['enable_sms']) ? 1 : 0;
                $enableEmail = isset($_POST['enable_email']) ? 1 : 0;
                
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                    sms_notifications = ?,
                    email_notifications = ?
                    WHERE id = ?
                ");
                $stmt->execute([$enableSMS, $enableEmail, $_SESSION['user_id']]);
                
                $success = 'Account settings updated successfully!';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="row justify-content-center">
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <!-- System Settings (Admin Only) -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-gear me-2"></i>System Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="update_system_settings" value="1">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">System Name</label>
                                    <input type="text" class="form-control" name="system_name" 
                                           value="<?php echo htmlspecialchars($settings->get('system_name')); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Barangay Name</label>
                                    <input type="text" class="form-control" name="barangay_name" 
                                           value="<?php echo htmlspecialchars($settings->get('barangay_name')); ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">PhilSMS API Key</label>
                                    <input type="text" class="form-control" name="philsms_api_key" 
                                           value="<?php echo htmlspecialchars($settings->get('philsms_api_key')); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">SMS Sender Name</label>
                                    <input type="text" class="form-control" name="philsms_sender_name" 
                                           value="<?php echo htmlspecialchars($settings->get('philsms_sender_name')); ?>">
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="ringtone_enabled" 
                                               id="ringtone_enabled" <?php echo $settings->getBool('ringtone_enabled') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="ringtone_enabled">
                                            Enable Notification Sound
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save System Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Account Settings (All Users) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-person-gear me-2"></i>Account Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="accountSettingsForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="update_account_settings" value="1">
                            
                            <!-- Change Password Section -->
                            <h6 class="mb-4">Change Password</h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="current_password" id="current_password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="new_password" id="new_password"
                                               pattern=".{8,}" title="Password must be at least 8 characters long">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Must be at least 8 characters long</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="confirm_password" id="confirm_password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Notification Preferences -->
                            <h6 class="mb-4">Notification Preferences</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_sms" id="enable_sms"
                                                       <?php echo $currentUser['sms_notifications'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_sms">
                                                    <strong>Receive SMS Notifications</strong>
                                                </label>
                                            </div>
                                            <div class="form-text mt-2">
                                                You will receive SMS updates about your applications and important announcements
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_email" id="enable_email"
                                                       <?php echo $currentUser['email_notifications'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_email">
                                                    <strong>Receive Email Notifications</strong>
                                                </label>
                                            </div>
                                            <div class="form-text mt-2">
                                                You will receive email updates about your account and application status
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Account Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// Form validation
document.getElementById('accountSettingsForm').addEventListener('submit', function(e) {
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // If changing password, validate all password fields
    if (newPassword || confirmPassword) {
        if (!currentPassword) {
            e.preventDefault();
            showError('Please enter your current password.');
            return false;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            showError('New password must be at least 8 characters long.');
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            showError('New passwords do not match.');
            return false;
        }
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
});
</script>

<?php include 'scripts.php'; ?> 