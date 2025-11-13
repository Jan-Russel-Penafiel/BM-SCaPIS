<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'System Settings';
$currentUser = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Update each setting
        $stmt = $pdo->prepare("UPDATE system_config SET config_value = ? WHERE config_key = ?");
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $configKey = substr($key, 8); // Remove 'setting_' prefix
                $stmt->execute([$value, $configKey]);
            }
        }

        // Log the changes
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, table_affected, new_values, ip_address, user_agent)
            VALUES (?, 'update_settings', 'system_config', ?, ?, ?)
        ");
        $stmt->execute([
            $currentUser['id'],
            json_encode($_POST),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);

        $pdo->commit();
        $_SESSION['success'] = 'System settings updated successfully.';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Failed to update system settings: ' . $e->getMessage();
    }

    // Redirect to prevent form resubmission
    header('Location: system-settings.php');
    exit;
}

// Get current settings
$stmt = $pdo->prepare("SELECT config_key, config_value FROM system_config ORDER BY config_key");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

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
                        <h1 class="h3 mb-2">System Settings</h1>
                        <p class="text-muted mb-0">Configure system-wide settings and preferences</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Settings Form -->
        <form method="POST" class="row">
            <!-- General Settings -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-gear me-2"></i>General Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">System Name</label>
                            <input type="text" name="setting_system_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['system_name']); ?>" required>
                            <small class="text-muted">The name of the system as it appears throughout the application</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barangay Name</label>
                            <input type="text" name="setting_barangay_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['barangay_name']); ?>" required>
                            <small class="text-muted">The name of your barangay</small>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="setting_ringtone_enabled" class="form-check-input" 
                                   value="1" <?php echo $settings['ringtone_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Enable Notification Sound</label>
                            <small class="text-muted d-block">Play sound when new notifications arrive</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Maintenance -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="bi bi-tools me-2"></i>System Maintenance
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-warning" onclick="clearCache()">
                                        <i class="bi bi-trash me-2"></i>Clear System Cache
                                    </button>
                                    <small class="text-muted">Clear cached reports and temporary files</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-danger" onclick="clearLogs()">
                                        <i class="bi bi-journal-x me-2"></i>Clear Activity Logs
                                    </button>
                                    <small class="text-muted">Delete old activity logs (older than 30 days)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(button) {
    const input = button.previousElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function clearCache() {
    if (confirm('Are you sure you want to clear the system cache?')) {
        fetch('clear-cache.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache cleared successfully!');
            } else {
                alert('Failed to clear cache: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}

function clearLogs() {
    if (confirm('Are you sure you want to clear old activity logs? This cannot be undone.')) {
        fetch('clear-logs.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Activity logs cleared successfully!');
            } else {
                alert('Failed to clear logs: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
}
</script>

<?php include 'scripts.php'; ?> 