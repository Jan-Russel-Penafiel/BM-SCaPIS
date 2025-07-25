<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$pageTitle = 'Register';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Validate form data
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $suffix = trim($_POST['suffix'] ?? '');
        $birthdate = $_POST['birthdate'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $civilStatus = $_POST['civil_status'] ?? '';
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $purokId = $_POST['purok_id'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $monthlyIncome = floatval($_POST['monthly_income'] ?? 0);
        $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
        $emergencyContactNumber = trim($_POST['emergency_contact_number'] ?? '');
        
        // Validation
        if (empty($firstName)) $errors[] = 'First name is required.';
        if (empty($lastName)) $errors[] = 'Last name is required.';
        if (empty($birthdate)) $errors[] = 'Birthdate is required.';
        if (empty($gender)) $errors[] = 'Gender is required.';
        if (empty($civilStatus)) $errors[] = 'Civil status is required.';
        if (empty($contactNumber)) $errors[] = 'Contact number is required.';
        if (empty($purokId)) $errors[] = 'Purok is required.';
        if (empty($address)) $errors[] = 'Address is required.';
        
        // Validate phone number format
        if (!empty($contactNumber) && !preg_match('/^(\+63|63|0)[0-9]{10}$/', $contactNumber)) {
            $errors[] = 'Please enter a valid Philippine phone number.';
        }
        
        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Validate age (must be 18 or older)
        $age = calculateAge($birthdate);
        if ($age < 18) {
            $errors[] = 'You must be at least 18 years old to register.';
        }
        
        // File uploads
        $uploadDir = 'uploads/profiles';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $profilePicture = '';
        $validIdFront = '';
        $validIdBack = '';
        
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
            $upload = uploadFile($_FILES['profile_picture'], $uploadDir, ['jpg', 'jpeg', 'png']);
            if ($upload['success']) {
                $profilePicture = $upload['filename'];
            } else {
                $errors[] = 'Profile picture: ' . $upload['message'];
            }
        }
        
        // Handle valid ID front upload
        if (isset($_FILES['valid_id_front']) && $_FILES['valid_id_front']['error'] === 0) {
            $upload = uploadFile($_FILES['valid_id_front'], $uploadDir, ['jpg', 'jpeg', 'png', 'pdf']);
            if ($upload['success']) {
                $validIdFront = $upload['filename'];
            } else {
                $errors[] = 'Valid ID (front): ' . $upload['message'];
            }
        } else {
            $errors[] = 'Valid ID (front) is required.';
        }
        
        // Handle valid ID back upload
        if (isset($_FILES['valid_id_back']) && $_FILES['valid_id_back']['error'] === 0) {
            $upload = uploadFile($_FILES['valid_id_back'], $uploadDir, ['jpg', 'jpeg', 'png', 'pdf']);
            if ($upload['success']) {
                $validIdBack = $upload['filename'];
            } else {
                $errors[] = 'Valid ID (back): ' . $upload['message'];
            }
        } else {
            $errors[] = 'Valid ID (back) is required.';
        }
        
        // If no errors, save to database
        if (empty($errors)) {
            try {
                // Generate username
                $username = generateUsername($firstName, $lastName, $purokId);
                
                // Check if username already exists (just in case)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->rowCount() > 0) {
                    // Add random suffix if username exists
                    $username .= rand(100, 999);
                }
                
                // Default password (will be changed on first login)
                $password = 'bmscapis' . date('Y');
                
                // Format phone number
                $contactNumber = formatPhoneNumber($contactNumber);
                $emergencyContactNumber = formatPhoneNumber($emergencyContactNumber);
                
                // Insert user
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        username, password, role, first_name, middle_name, last_name, suffix,
                        birthdate, age, gender, civil_status, contact_number, email, purok_id,
                        address, occupation, monthly_income, emergency_contact_name, 
                        emergency_contact_number, profile_picture, valid_id_front, valid_id_back
                    ) VALUES (?, ?, 'resident', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $username, password_hash($password, PASSWORD_DEFAULT), $firstName, $middleName, $lastName, $suffix,
                    $birthdate, $age, $gender, $civilStatus, $contactNumber, $email, $purokId,
                    $address, $occupation, $monthlyIncome, $emergencyContactName,
                    $emergencyContactNumber, $profilePicture, $validIdFront, $validIdBack
                ]);
                
                $userId = $pdo->lastInsertId();
                
                // Store credentials for display
                $credentials = [
                    'username' => $username,
                    'password' => $password,
                    'name' => $firstName . ' ' . $lastName
                ];
                
                // Log activity
                logActivity($userId, 'User registered', 'users', $userId);
                
                // Send SMS notification
                if (!empty($contactNumber)) {
                    $message = "Welcome to BM-SCaPIS! Your account has been created successfully.\nUsername: $username\nPassword: $password\n\nYour registration is pending approval from your Purok Leader and Admin.";
                    sendSMSNotification($contactNumber, $message, $userId);
                }
                
                $success = true;
                
            } catch (PDOException $e) {
                $errors[] = 'Registration failed. Please try again.';
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}

// Get puroks for dropdown
$stmt = $pdo->prepare("SELECT * FROM puroks ORDER BY purok_name");
$stmt->execute();
$puroks = $stmt->fetchAll();

include 'header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($success): ?>
                <div class="card border-0 shadow">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="text-success mb-3">Registration Successful!</h2>
                        <p class="lead mb-4">
                            Thank you for registering with BM-SCaPIS, <?php echo htmlspecialchars($credentials['name']); ?>. 
                            Your account has been created and is pending approval.
                        </p>
                        
                        <!-- Display Credentials -->
                        <div class="alert alert-success mb-4">
                            <h5 class="mb-3"><i class="bi bi-key me-2"></i>Your Login Credentials</h5>
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="bg-white rounded p-3 border">
                                        <div class="mb-3">
                                            <label class="form-label mb-1"><strong>Username:</strong></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($credentials['username']); ?>" readonly>
                                                <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard(this.previousElementSibling)">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label mb-1"><strong>Password:</strong></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($credentials['password']); ?>" readonly>
                                                <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard(this.previousElementSibling)">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-warning mt-3 mb-0 text-start">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Important:</strong> Please save or write down these credentials now. 
                                        You will need them to log in once your registration is approved. 
                                        For security reasons, they will not be shown again.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>What happens next?</h6>
                            <ol class="mb-0 text-start">
                                <li>Your Purok Leader will verify if you are a resident of <?php echo BARANGAY_NAME; ?></li>
                                <li>The Admin will review and give final approval</li>
                                <li>You will receive an SMS notification once approved</li>
                                <li>You can then log in using the credentials shown above</li>
                            </ol>
                        </div>

                        <!-- Print Button -->
                        <div class="mb-4">
                            <button type="button" class="btn btn-primary btn-lg" onclick="handlePrintCredentials()">
                                <i class="bi bi-printer me-2"></i>Print Credentials
                            </button>
                        </div>

                        <div class="mt-4" id="navigationButtons" style="display: none;">
                            <a href="login.php" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary ms-2">
                                <i class="bi bi-house me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-person-plus me-2"></i>
                            Register for BM-SCaPIS
                        </h4>
                        <small>Create your account to apply for barangay documents online</small>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" id="registrationForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Personal Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary border-bottom pb-2">
                                        <i class="bi bi-person me-2"></i>Personal Information
                                    </h5>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                           value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-1">
                                    <label for="suffix" class="form-label">Suffix</label>
                                    <input type="text" class="form-control" id="suffix" name="suffix" 
                                           value="<?php echo htmlspecialchars($_POST['suffix'] ?? ''); ?>" placeholder="Jr.">
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="birthdate" class="form-label">Birthdate <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="birthdate" name="birthdate" 
                                           value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>" 
                                           max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($_POST['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($_POST['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($_POST['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="civil_status" class="form-label">Civil Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="civil_status" name="civil_status" required>
                                        <option value="">Select Civil Status</option>
                                        <option value="Single" <?php echo ($_POST['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo ($_POST['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                        <option value="Divorced" <?php echo ($_POST['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                        <option value="Widowed" <?php echo ($_POST['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary border-bottom pb-2">
                                        <i class="bi bi-telephone me-2"></i>Contact Information
                                    </h5>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                           value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>" 
                                           placeholder="+63 or 09xxxxxxxxx" required>
                                    <div class="form-text">Enter your mobile number for SMS notifications</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="your.email@example.com">
                                </div>
                            </div>
                            
                            <!-- Address Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary border-bottom pb-2">
                                        <i class="bi bi-geo-alt me-2"></i>Address Information
                                    </h5>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="purok_id" class="form-label">Purok <span class="text-danger">*</span></label>
                                    <select class="form-select select2" id="purok_id" name="purok_id" required>
                                        <option value="">Select Purok</option>
                                        <?php foreach ($puroks as $purok): ?>
                                            <option value="<?php echo $purok['id']; ?>" 
                                                    <?php echo ($_POST['purok_id'] ?? '') == $purok['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($purok['purok_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="address" class="form-label">Complete Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required 
                                              placeholder="House No., Street, <?php echo BARANGAY_NAME; ?>"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Employment Information -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary border-bottom pb-2">
                                        <i class="bi bi-briefcase me-2"></i>Employment Information
                                    </h5>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="occupation" class="form-label">Occupation</label>
                                    <input type="text" class="form-control" id="occupation" name="occupation" 
                                           value="<?php echo htmlspecialchars($_POST['occupation'] ?? ''); ?>" 
                                           placeholder="e.g., Teacher, Driver, Self-employed">
                                </div>
                                <div class="col-md-6">
                                    <label for="monthly_income" class="form-label">Monthly Income (₱)</label>
                                    <input type="number" class="form-control" id="monthly_income" name="monthly_income" 
                                           value="<?php echo htmlspecialchars($_POST['monthly_income'] ?? ''); ?>" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            
                            <!-- Emergency Contact -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary border-bottom pb-2">
                                        <i class="bi bi-person-lines-fill me-2"></i>Emergency Contact
                                    </h5>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                    <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>" 
                                           placeholder="Full name of emergency contact">
                                </div>
                                <div class="col-md-6">
                                    <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
                                    <input type="tel" class="form-control" id="emergency_contact_number" name="emergency_contact_number" 
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact_number'] ?? ''); ?>" 
                                           placeholder="+63 or 09xxxxxxxxx">
                                </div>
                            </div>
                            
                            <!-- File Uploads -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary border-bottom pb-2">
                                        <i class="bi bi-cloud-upload me-2"></i>Document Uploads
                                    </h5>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="profile_picture" class="form-label">Profile Picture</label>
                                    <div class="file-upload-area" onclick="document.getElementById('profile_picture').click()">
                                        <i class="bi bi-camera fs-1 text-muted"></i>
                                        <p class="mb-0">Click to upload photo</p>
                                        <small class="text-muted">JPG, PNG (Max 5MB)</small>
                                    </div>
                                    <input type="file" class="form-control d-none" id="profile_picture" name="profile_picture" accept="image/*">
                                    <img id="profile_preview" class="img-thumbnail mt-2 d-none" style="max-width: 150px;">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="valid_id_front" class="form-label">Valid ID (Front) <span class="text-danger">*</span></label>
                                    <div class="file-upload-area" onclick="document.getElementById('valid_id_front').click()">
                                        <i class="bi bi-card-image fs-1 text-muted"></i>
                                        <p class="mb-0">Click to upload ID front</p>
                                        <small class="text-muted">JPG, PNG, PDF (Max 5MB)</small>
                                    </div>
                                    <input type="file" class="form-control d-none" id="valid_id_front" name="valid_id_front" accept="image/*,.pdf" required>
                                    <img id="id_front_preview" class="img-thumbnail mt-2 d-none" style="max-width: 150px;">
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="valid_id_back" class="form-label">Valid ID (Back) <span class="text-danger">*</span></label>
                                    <div class="file-upload-area" onclick="document.getElementById('valid_id_back').click()">
                                        <i class="bi bi-card-image fs-1 text-muted"></i>
                                        <p class="mb-0">Click to upload ID back</p>
                                        <small class="text-muted">JPG, PNG, PDF (Max 5MB)</small>
                                    </div>
                                    <input type="file" class="form-control d-none" id="valid_id_back" name="valid_id_back" accept="image/*,.pdf" required>
                                    <img id="id_back_preview" class="img-thumbnail mt-2 d-none" style="max-width: 150px;">
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms_agree" required>
                                    <label class="form-check-label" for="terms_agree">
                                        I agree to the <a href="terms-of-service.php" target="_blank">Terms of Service</a> 
                                        and <a href="privacy-policy.php" target="_blank">Privacy Policy</a>
                                        <span class="text-danger">*</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="data_accuracy" required>
                                    <label class="form-check-label" for="data_accuracy">
                                        I certify that all information provided is true and accurate to the best of my knowledge
                                        <span class="text-danger">*</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Login
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus me-2"></i>Register Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Print Confirmation Modal -->
<div class="modal fade" id="printConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Credentials Saved</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Have you successfully printed or saved your credentials?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> These credentials will not be shown again after confirmation.
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmCredentialsSaved" required>
                    <label class="form-check-label" for="confirmCredentialsSaved">
                        Yes, I have printed/saved my credentials and understand that I won't be able to see them again
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Go Back</button>
                <button type="button" class="btn btn-primary" onclick="confirmAndProceed()" id="confirmButton" disabled>
                    Proceed to Login
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Print styles */
@media print {
    body * {
        visibility: hidden;
    }
    .card, .card * {
        visibility: visible;
    }
    .card {
        position: absolute;
        left: 0;
        top: 0;
    }
    .btn, .modal, #navigationButtons {
        display: none !important;
    }
}
</style>

<script>
    // File upload previews
    document.getElementById('profile_picture').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('profile_preview');
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
    
    document.getElementById('valid_id_front').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('id_front_preview');
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
    
    document.getElementById('valid_id_back').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('id_back_preview');
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Phone number formatting
    document.getElementById('contact_number').addEventListener('input', function(e) {
        formatPhoneNumber(e.target);
    });
    
    document.getElementById('emergency_contact_number').addEventListener('input', function(e) {
        formatPhoneNumber(e.target);
    });
    
    // Auto-save form data
    autoSaveForm('registrationForm', 'registration_draft');
    
    // Form validation
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        if (!validateForm('registrationForm')) {
            e.preventDefault();
            showError('Please fill in all required fields.');
            return false;
        }
        
        showLoadingToast('Creating your account...');
    });

    // Function to copy text to clipboard with better feedback
    function copyToClipboard(element) {
        element.select();
        document.execCommand('copy');
        
        // Show tooltip feedback
        const button = element.nextElementSibling;
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check2"></i> Copied!';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-primary');
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');
        }, 1500);

        // Show toast notification
        showToast('success', 'Copied!', 'Credentials copied to clipboard');
    }

    function handlePrintCredentials() {
        window.print();
        
        // Show confirmation modal after print dialog closes
        setTimeout(() => {
            const modal = new bootstrap.Modal(document.getElementById('printConfirmationModal'));
            modal.show();
        }, 1000);
    }

    // Enable/disable confirm button based on checkbox
    document.getElementById('confirmCredentialsSaved').addEventListener('change', function() {
        document.getElementById('confirmButton').disabled = !this.checked;
    });

    function confirmAndProceed() {
        // Show success message
        showToast('success', 'Success', 'Credentials have been saved. Redirecting to login...');
        
        // Show navigation buttons
        document.getElementById('navigationButtons').style.display = 'block';
        
        // Hide the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('printConfirmationModal'));
        modal.hide();
        
        // Redirect to login page after a short delay
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3000);
    }

    // Function to show toast notifications
    function showToast(type, title, message) {
        const toast = document.createElement('div');
        toast.className = `toast position-fixed bottom-0 end-0 m-3 bg-${type === 'success' ? 'success' : 'danger'} text-white`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="toast-header bg-${type === 'success' ? 'success' : 'danger'} text-white">
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    // Prevent accidental navigation
    window.onbeforeunload = function() {
        if (!document.getElementById('navigationButtons').style.display === 'block') {
            return "Are you sure you want to leave? Make sure you've saved your credentials first!";
        }
    };

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>

<?php include 'scripts.php'; ?>
