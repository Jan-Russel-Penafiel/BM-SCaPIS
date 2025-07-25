<?php
require_once 'config.php';

// Require login and must be admin
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Create Purok Leader Account';
$currentUser = getCurrentUser();

// Initialize variables
$errors = [];
$success = false;
$generatedCredentials = [
    'username' => '',
    'password' => ''
];
$formData = [
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'suffix' => '',
    'birthdate' => '',
    'gender' => '',
    'civil_status' => '',
    'contact_number' => '',
    'email' => '',
    'purok_id' => '',
    'address' => '',
    'occupation' => '',
    'monthly_income' => '',
    'emergency_contact_name' => '',
    'emergency_contact_number' => ''
];

// Get all puroks for dropdown
$stmt = $pdo->prepare("
    SELECT p.* 
    FROM puroks p
    LEFT JOIN users u ON p.purok_leader_id = u.id
    WHERE p.purok_leader_id IS NULL
    ORDER BY p.purok_name
");
$stmt->execute();
$availablePuroks = $stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $formData = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'middle_name' => trim($_POST['middle_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'suffix' => trim($_POST['suffix'] ?? ''),
        'birthdate' => trim($_POST['birthdate'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'civil_status' => trim($_POST['civil_status'] ?? ''),
        'contact_number' => trim($_POST['contact_number'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'purok_id' => trim($_POST['purok_id'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'monthly_income' => trim($_POST['monthly_income'] ?? ''),
        'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_number' => trim($_POST['emergency_contact_number'] ?? '')
    ];

    // Validate required fields
    $requiredFields = ['first_name', 'last_name', 'gender', 'civil_status', 'purok_id'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    // Validate birthdate if provided
    if (!empty($formData['birthdate'])) {
        $birthdate = new DateTime($formData['birthdate']);
        $now = new DateTime();
        $age = $now->diff($birthdate)->y;
        
        if ($birthdate > $now) {
            $errors[] = 'Birthdate cannot be in the future.';
        } elseif ($age < 18) {
            $errors[] = 'Purok leader must be at least 18 years old.';
        }
    }

    // Validate monthly income if provided
    if (!empty($formData['monthly_income']) && !is_numeric($formData['monthly_income'])) {
        $errors[] = 'Monthly income must be a numeric value.';
    }

    // Validate purok_id
    if (!empty($formData['purok_id'])) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM puroks 
            WHERE id = ?
        ");
        $stmt->execute([$formData['purok_id']]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Selected purok does not exist.';
        }
    }

    // If no errors, create the purok leader account
    if (empty($errors)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // First, remove the existing purok leader assignment if any
            $stmt = $pdo->prepare("
                UPDATE puroks 
                SET purok_leader_id = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$formData['purok_id']]);
            
            // Calculate age from birthdate
            $age = null;
            if (!empty($formData['birthdate'])) {
                $birthdate = new DateTime($formData['birthdate']);
                $now = new DateTime();
                $age = $now->diff($birthdate)->y;
            }

            // Generate username (first letter of first name + last name + purok id)
            $username = strtolower(substr($formData['first_name'], 0, 1) . $formData['last_name'] . '_p' . $formData['purok_id']);
            $username = preg_replace('/[^a-z0-9_]/', '', $username); // Remove special characters
            
            // Check if username exists, if so, add a random number
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $username .= rand(100, 999);
            }
            
            // Generate random password (8 characters)
            $password = bin2hex(random_bytes(4)); // 8 characters
            $generatedCredentials = [
                'username' => $username,
                'password' => $password
            ];
            
            // Remove password hashing and use plain password
            // Insert user with purok_leader role and approved status
            // Note: status is set to 'approved' directly, and both purok_leader_approval and admin_approval are set to 'approved'
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    username, password, role, status,
                    first_name, middle_name, last_name, suffix,
                    birthdate, age, gender, civil_status,
                    contact_number, email, purok_id, address,
                    occupation, monthly_income,
                    emergency_contact_name, emergency_contact_number,
                    purok_leader_approval, admin_approval, approved_by_admin,
                    approved_at
                ) VALUES (
                    ?, ?, 'purok_leader', 'approved',
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?,
                    'approved', 'approved', ?,
                    CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute([
                $username, $password, // Use plain password here
                $formData['first_name'], $formData['middle_name'], $formData['last_name'], $formData['suffix'],
                $formData['birthdate'], $age, $formData['gender'], $formData['civil_status'],
                $formData['contact_number'], $formData['email'], $formData['purok_id'], $formData['address'],
                $formData['occupation'], $formData['monthly_income'],
                $formData['emergency_contact_name'], $formData['emergency_contact_number'],
                $currentUser['id']
            ]);
            
            // Get the new user ID
            $newUserId = $pdo->lastInsertId();
            
            // Update purok to assign this user as leader
            $stmt = $pdo->prepare("
                UPDATE puroks
                SET purok_leader_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$newUserId, $formData['purok_id']]);
            
            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (
                    user_id, action, table_affected, record_id,
                    new_values, ip_address, user_agent
                ) VALUES (
                    ?, 'create_purok_leader', 'users', ?,
                    ?, ?, ?
                )
            ");
            
            // Add credentials to log data but exclude the actual password
            $logData = $formData;
            $logData['username'] = $username;
            $logData['password'] = '[REDACTED]';
            
            $stmt->execute([
                $currentUser['id'],
                $newUserId,
                json_encode($logData),
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            // Create system notification
            $stmt = $pdo->prepare("
                INSERT INTO system_notifications (
                    type, title, message, target_role
                ) VALUES (
                    'new_purok_leader',
                    'New Purok Leader Created',
                    ?,
                    'admin'
                )
            ");
            $stmt->execute([
                'A new purok leader account has been created for ' . 
                $formData['first_name'] . ' ' . $formData['last_name'] . '.'
            ]);
            
            $pdo->commit();
            $success = true;
            
            // Do not reset form data after successful submission to display info
            // Instead, store the credentials for display
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'An error occurred: ' . $e->getMessage();
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <div class="row">
            <div class="col-12">
                <!-- Page Header -->
                <div class="card border-0 shadow-sm mb-4 mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2">Create Purok Leader Account</h1>
                                <p class="text-muted mb-0">Create a new purok leader account and assign to a purok</p>
                            </div>
                            <a href="purok-leaders.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Purok Leaders
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Success Message with Credentials -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <h4><i class="bi bi-check-circle me-2"></i>Purok leader account created successfully!</h4>
                        <hr>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">Account Credentials</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Username:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($generatedCredentials['username']); ?>" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($generatedCredentials['username']); ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Password:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($generatedCredentials['password']); ?>" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('<?php echo htmlspecialchars($generatedCredentials['password']); ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="alert alert-warning mt-3">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Please save or share these credentials with the purok leader. This is the only time they will be displayed.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <a href="purok-leaders.php" class="btn btn-primary btn-lg">
                                        <i class="bi bi-list me-2"></i>View All Purok Leaders
                                    </a>
                                    <a href="create_purok_leader.php" class="btn btn-outline-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Create Another Purok Leader
                                    </a>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- No Available Puroks Warning -->
                <?php if (empty($availablePuroks)): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>No available puroks!</strong> All puroks already have leaders assigned. 
                        <a href="purok-leaders.php" class="alert-link">Add a new purok</a> before creating a purok leader.
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <?php if (!$success): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="POST" action="" class="row g-3">
                            <!-- Account Information -->
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">Account Information</h5>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Username and password will be auto-generated after submission.
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <div class="col-12 mt-4">
                                <h5 class="border-bottom pb-2 mb-3">Personal Information</h5>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($formData['first_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($formData['middle_name']); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($formData['last_name']); ?>" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Suffix</label>
                                <input type="text" name="suffix" class="form-control" value="<?php echo htmlspecialchars($formData['suffix']); ?>" placeholder="Jr., Sr., III, etc.">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Birthdate</label>
                                <input type="date" name="birthdate" class="form-control" value="<?php echo htmlspecialchars($formData['birthdate']); ?>" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo $formData['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $formData['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $formData['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="civil_status" class="form-label">Civil Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="civil_status" name="civil_status" required>
                                    <option value="">Select Civil Status</option>
                                    <option value="Single" <?php echo $formData['civil_status'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo $formData['civil_status'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Divorced" <?php echo $formData['civil_status'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Widowed" <?php echo $formData['civil_status'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="purok_id" class="form-label">Purok Assignment <span class="text-danger">*</span></label>
                                <select class="form-select" id="purok_id" name="purok_id" required <?php echo empty($availablePuroks) ? 'disabled' : ''; ?>>
                                    <option value="">Select Purok</option>
                                    <?php foreach ($availablePuroks as $purok): ?>
                                        <option value="<?php echo $purok['id']; ?>" <?php echo $formData['purok_id'] == $purok['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($purok['purok_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($availablePuroks)): ?>
                                    <small class="text-danger">No available puroks</small>
                                <?php endif; ?>
                            </div>

                            <!-- Contact Information -->
                            <div class="col-12 mt-4">
                                <h5 class="border-bottom pb-2 mb-3">Contact Information</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($formData['contact_number']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email']); ?>">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                            </div>

                            <!-- Additional Information -->
                            <div class="col-12 mt-4">
                                <h5 class="border-bottom pb-2 mb-3">Additional Information</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" class="form-control" value="<?php echo htmlspecialchars($formData['occupation']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Monthly Income</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="monthly_income" class="form-control" value="<?php echo htmlspecialchars($formData['monthly_income']); ?>" step="0.01" min="0">
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="col-12 mt-4">
                                <h5 class="border-bottom pb-2 mb-3">Emergency Contact</h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name" class="form-control" value="<?php echo htmlspecialchars($formData['emergency_contact_name']); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Number</label>
                                <input type="text" name="emergency_contact_number" class="form-control" value="<?php echo htmlspecialchars($formData['emergency_contact_number']); ?>">
                            </div>

                            <!-- Approval Information -->
                            <div class="col-12 mt-4">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note:</strong> Purok leader accounts are automatically approved by both purok leader and admin.
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary" <?php echo empty($availablePuroks) ? 'disabled' : ''; ?>>
                                    <i class="bi bi-person-plus me-2"></i>Create Purok Leader Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for dropdowns
    try {
        // Initialize Select2 with setTimeout to ensure DOM is fully loaded
        setTimeout(function() {
            $('#purok_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Purok',
                width: '100%',
                dropdownParent: $('#purok_id').parent() // Attach dropdown to parent element
            });
            
            $('#gender').select2({
                theme: 'bootstrap-5',
                minimumResultsForSearch: Infinity,
                width: '100%',
                dropdownParent: $('#gender').parent() // Attach dropdown to parent element
            });
            
            $('#civil_status').select2({
                theme: 'bootstrap-5',
                minimumResultsForSearch: Infinity,
                width: '100%',
                dropdownParent: $('#civil_status').parent() // Attach dropdown to parent element
            });
        }, 100);
    } catch (e) {
        console.error('Error initializing Select2:', e);
    }
    
    // Fix z-index issues for dropdowns
    $('.select2-dropdown').css('z-index', 9999);
});

// Function to copy text to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show toast or notification
        alert('Copied to clipboard!');
    }).catch(err => {
        console.error('Could not copy text: ', err);
    });
}
</script>

<?php include 'scripts.php'; ?> 