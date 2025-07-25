<?php
require_once 'config.php';

// Require login
requireLogin();

$pageTitle = 'My Profile';
$currentUser = getCurrentUser();
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $suffix = trim($_POST['suffix'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $civilStatus = trim($_POST['civil_status'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $monthlyIncome = trim($_POST['monthly_income'] ?? '');
        $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
        $emergencyContactNumber = trim($_POST['emergency_contact_number'] ?? '');
        
        // Basic validation
        if (empty($firstName) || empty($lastName) || empty($gender) || empty($civilStatus)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                // Handle profile picture upload
                $profilePicture = $currentUser['profile_picture']; // Keep existing by default
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/profiles/';
                    $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        throw new Exception('Invalid file type. Only JPG, JPEG, and PNG files are allowed.');
                    }
                    
                    $newFileName = uniqid() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                        // Delete old profile picture if exists
                        if ($profilePicture && file_exists($uploadDir . $profilePicture)) {
                            unlink($uploadDir . $profilePicture);
                        }
                        $profilePicture = $newFileName;
                    }
                }
                
                // Calculate age from birthdate
                $age = null;
                if (!empty($birthdate)) {
                    $birthdateObj = new DateTime($birthdate);
                    $today = new DateTime();
                    $age = $today->diff($birthdateObj)->y;
                }
                
                // Update user profile
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                    first_name = ?,
                    middle_name = ?,
                    last_name = ?,
                    suffix = ?,
                    birthdate = ?,
                    age = ?,
                    gender = ?,
                    civil_status = ?,
                    contact_number = ?,
                    email = ?,
                    address = ?,
                    occupation = ?,
                    monthly_income = ?,
                    emergency_contact_name = ?,
                    emergency_contact_number = ?,
                    profile_picture = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $firstName,
                    $middleName ?: null,
                    $lastName,
                    $suffix ?: null,
                    $birthdate ?: null,
                    $age,
                    $gender,
                    $civilStatus,
                    $contactNumber ?: null,
                    $email ?: null,
                    $address ?: null,
                    $occupation ?: null,
                    $monthlyIncome ?: null,
                    $emergencyContactName ?: null,
                    $emergencyContactNumber ?: null,
                    $profilePicture,
                    $_SESSION['user_id']
                ]);
                
                // Log activity
                logActivity($_SESSION['user_id'], 'Updated profile information', 'users', $_SESSION['user_id']);
                
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $currentUser = getCurrentUser();
                
            } catch (Exception $e) {
                $error = 'Error updating profile: ' . $e->getMessage();
            }
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-circle me-2"></i>My Profile
                                </h5>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-<?php 
                                    echo $currentUser['status'] === 'approved' ? 'success' : 
                                        ($currentUser['status'] === 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($currentUser['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
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
                        
                        <form method="POST" enctype="multipart/form-data" id="profileForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Profile Picture -->
                            <div class="text-center mb-4">
                                <div class="position-relative d-inline-block">
                                    <?php if ($currentUser['profile_picture']): ?>
                                        <img src="uploads/profiles/<?php echo htmlspecialchars($currentUser['profile_picture']); ?>" 
                                             alt="Profile Picture" class="rounded-circle" width="150" height="150"
                                             style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                             style="width: 150px; height: 150px;">
                                            <i class="bi bi-person text-secondary" style="font-size: 4rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <label for="profile_picture" class="position-absolute bottom-0 end-0 mb-2 me-2">
                                        <div class="btn btn-sm btn-primary rounded-circle">
                                            <i class="bi bi-camera"></i>
                                        </div>
                                        <input type="file" id="profile_picture" name="profile_picture" class="d-none" 
                                               accept="image/jpeg,image/png">
                                    </label>
                                </div>
                                <div class="small text-muted mt-2">Click the camera icon to change profile picture</div>
                            </div>
                            
                            <!-- User Role Information -->
                            <div class="row mb-4">
                                <div class="col-md-6 offset-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="mb-2">Account Type</h6>
                                            <span class="badge bg-primary"><?php echo ucfirst($currentUser['role']); ?></span>
                                            <?php if ($currentUser['role'] === 'resident'): ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <?php
                                                        if ($currentUser['status'] === 'pending') {
                                                            if ($currentUser['purok_leader_approval'] === 'pending' && $currentUser['admin_approval'] === 'pending') {
                                                                echo 'Pending approval from Purok Leader and Admin';
                                                            } elseif ($currentUser['purok_leader_approval'] === 'pending') {
                                                                echo 'Pending approval from Purok Leader';
                                                            } elseif ($currentUser['admin_approval'] === 'pending') {
                                                                echo 'Pending approval from Admin';
                                                            }
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Personal Information -->
                            <h6 class="mb-3">Personal Information</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="first_name" 
                                           value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" name="middle_name" 
                                           value="<?php echo htmlspecialchars($currentUser['middle_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="last_name" 
                                           value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" required>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Suffix</label>
                                    <input type="text" class="form-control" name="suffix" 
                                           value="<?php echo htmlspecialchars($currentUser['suffix'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Birthdate</label>
                                    <input type="date" class="form-control" name="birthdate" 
                                           value="<?php echo $currentUser['birthdate'] ?? ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo $currentUser['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $currentUser['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo $currentUser['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="civil_status" required>
                                        <option value="">Select Civil Status</option>
                                        <option value="Single" <?php echo $currentUser['civil_status'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo $currentUser['civil_status'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                                        <option value="Divorced" <?php echo $currentUser['civil_status'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                        <option value="Widowed" <?php echo $currentUser['civil_status'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Contact Information -->
                            <h6 class="mb-3">Contact Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" name="contact_number" 
                                           value="<?php echo htmlspecialchars($currentUser['contact_number'] ?? ''); ?>"
                                           pattern="[0-9]{11}" title="Please enter a valid 11-digit phone number">
                                    <div class="form-text">Format: 09XXXXXXXXX</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Complete Address</label>
                                    <textarea class="form-control" name="address" rows="2"><?php 
                                        echo htmlspecialchars($currentUser['address'] ?? ''); 
                                    ?></textarea>
                                </div>
                            </div>
                            
                            <?php if ($currentUser['role'] === 'resident'): ?>
                            <hr class="my-4">
                            
                            <!-- Additional Information for Residents -->
                            <h6 class="mb-3">Additional Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Occupation</label>
                                    <input type="text" class="form-control" name="occupation" 
                                           value="<?php echo htmlspecialchars($currentUser['occupation'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Monthly Income</label>
                                    <input type="number" class="form-control" name="monthly_income" 
                                           value="<?php echo htmlspecialchars($currentUser['monthly_income'] ?? ''); ?>"
                                           step="0.01" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Emergency Contact Name</label>
                                    <input type="text" class="form-control" name="emergency_contact_name" 
                                           value="<?php echo htmlspecialchars($currentUser['emergency_contact_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Emergency Contact Number</label>
                                    <input type="tel" class="form-control" name="emergency_contact_number" 
                                           value="<?php echo htmlspecialchars($currentUser['emergency_contact_number'] ?? ''); ?>"
                                           pattern="[0-9]{11}" title="Please enter a valid 11-digit phone number">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <hr class="my-4">
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Changes
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
document.addEventListener('DOMContentLoaded', function() {
    // Preview profile picture before upload
    document.getElementById('profile_picture').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.querySelector('.rounded-circle');
                preview.src = e.target.result;
                preview.style.objectFit = 'cover';
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Auto-calculate age when birthdate changes
    const birthdateInput = document.querySelector('input[name="birthdate"]');
    if (birthdateInput) {
        birthdateInput.addEventListener('change', function() {
            if (this.value) {
                const birthdate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthdate.getFullYear();
                const monthDiff = today.getMonth() - birthdate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
                    age--;
                }
                
                // You could display this somewhere if needed
                console.log('Calculated age:', age);
            }
        });
    }
    
    // Form validation
    const form = document.getElementById('profileForm');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showError('Please fill in all required fields.');
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    });
});
</script>

<?php include 'scripts.php'; ?>
