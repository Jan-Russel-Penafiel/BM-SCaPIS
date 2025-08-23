<?php
require_once 'config.php';

// Require login and must be a resident
requireLogin();
if ($_SESSION['role'] !== 'resident') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Schedule Pickup';
$currentUser = getCurrentUser();

// Get application ID from URL
$applicationId = $_GET['id'] ?? 0;

// Validate application ID
if (empty($applicationId)) {
    $_SESSION['error'] = 'Invalid application ID.';
    header('Location: my-applications.php');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $appointmentDate = $_POST['appointment_date'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate inputs
        if (empty($appointmentDate)) {
            throw new Exception('Please select a preferred pickup date and time.');
        }
        
        // Validate appointment date (must be in the future)
        $appointmentDateTime = new DateTime($appointmentDate);
        $now = new DateTime();
        if ($appointmentDateTime <= $now) {
            throw new Exception('Pickup date must be in the future.');
        }
        
        // Get application details and verify it belongs to current user
        $stmt = $pdo->prepare("
            SELECT a.*, dt.type_name, dt.fee
            FROM applications a
            JOIN document_types dt ON a.document_type_id = dt.id
            WHERE a.id = ? AND a.user_id = ? AND a.status = 'ready_for_pickup'
        ");
        $stmt->execute([$applicationId, $_SESSION['user_id']]);
        $application = $stmt->fetch();
        
        // Check if application exists and is ready for pickup
        if (!$application) {
            throw new Exception('Application not found or not ready for pickup.');
        }
        
        // Check if there's already a pickup appointment for this application
        $stmt = $pdo->prepare("
            SELECT id FROM appointments 
            WHERE application_id = ? AND appointment_type = 'pickup' AND status IN ('scheduled', 'rescheduled')
        ");
        $stmt->execute([$applicationId]);
        if ($stmt->fetch()) {
            throw new Exception('A pickup appointment is already scheduled for this application.');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Create pickup appointment
        $stmt = $pdo->prepare("
            INSERT INTO appointments (
                application_id, user_id, appointment_type, appointment_date, notes, created_by
            ) VALUES (?, ?, 'pickup', ?, ?, ?)
        ");
        $stmt->execute([
            $applicationId,
            $_SESSION['user_id'],
            $appointmentDate,
            $notes,
            $_SESSION['user_id']
        ]);
        $appointmentId = $pdo->lastInsertId();
        
        // Update application remarks to include pickup appointment info
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET admin_remarks = CONCAT(COALESCE(admin_remarks, ''), ?),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            "\n" . date('Y-m-d H:i:s') . ' - Pickup appointment scheduled by resident for ' . date('M j, Y g:i A', strtotime($appointmentDate)),
            $applicationId
        ]);
        
        // Add to application history
        $stmt = $pdo->prepare("
            INSERT INTO application_history (
                application_id, status, remarks, changed_by
            ) VALUES (?, 'ready_for_pickup', ?, ?)
        ");
        $stmt->execute([
            $applicationId,
            'Pickup appointment scheduled for ' . date('M j, Y g:i A', strtotime($appointmentDate)),
            $_SESSION['user_id']
        ]);
        
        // Send notification to admin about the scheduled pickup
        $notificationMessage = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] . ' has scheduled a pickup appointment for application #' . $application['application_number'] . ' on ' . date('M j, Y g:i A', strtotime($appointmentDate));
        
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (
                type, title, message, target_role, metadata
            ) VALUES (?, ?, ?, 'admin', ?)
        ");
        $stmt->execute([
            'pickup_scheduled',
            'Pickup Appointment Scheduled',
            $notificationMessage,
            json_encode([
                'application_id' => $applicationId,
                'appointment_id' => $appointmentId,
                'appointment_date' => $appointmentDate,
                'resident_name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
            ])
        ]);
        
        // Send SMS notification to admin if enabled
        $stmt = $pdo->prepare("
            SELECT id, contact_number FROM users 
            WHERE role = 'admin' AND sms_notifications = 1 
            ORDER BY id ASC LIMIT 1
        ");
        $stmt->execute();
        $adminData = $stmt->fetch();
        
        if ($adminData && $adminData['contact_number']) {
            $smsMessage = "New pickup appointment scheduled:\n" .
                        "Resident: " . $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] . "\n" .
                        "Document: " . $application['type_name'] . "\n" .
                        "App #: " . $application['application_number'] . "\n" .
                        "Date: " . date('M j, Y g:i A', strtotime($appointmentDate));
            
            require_once 'sms_functions.php';
            sendSMSNotification($adminData['contact_number'], $smsMessage, $adminData['id']);
        }
        
        // Log activity
        logActivity(
            $_SESSION['user_id'],
            'Scheduled pickup appointment for application #' . $application['application_number'],
            'appointments',
            $appointmentId
        );
        
        $pdo->commit();
        
        // Set success message and redirect
        $_SESSION['success'] = 'Pickup appointment scheduled successfully for ' . date('M j, Y g:i A', strtotime($appointmentDate)) . '. You will receive a confirmation once the admin approves your schedule.';
        header('Location: my-applications.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $error = $e->getMessage();
    }
}

// Get application details for display
try {
    $stmt = $pdo->prepare("
        SELECT a.*, dt.type_name, dt.fee, dt.description,
               CONCAT(u.first_name, ' ', u.last_name) as resident_name
        FROM applications a
        JOIN document_types dt ON a.document_type_id = dt.id
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ? AND a.user_id = ? AND a.status = 'ready_for_pickup'
    ");
    $stmt->execute([$applicationId, $_SESSION['user_id']]);
    $application = $stmt->fetch();
    
    // Check if application exists and belongs to current user
    if (!$application) {
        $_SESSION['error'] = 'Application not found or not ready for pickup.';
        header('Location: my-applications.php');
        exit;
    }
    
    // Check if there's already a pickup appointment
    $stmt = $pdo->prepare("
        SELECT * FROM appointments 
        WHERE application_id = ? AND appointment_type = 'pickup' AND status IN ('scheduled', 'rescheduled')
    ");
    $stmt->execute([$applicationId]);
    $existingAppointment = $stmt->fetch();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error retrieving application details.';
    header('Location: my-applications.php');
    exit;
}

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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-2">Schedule Document Pickup</h1>
                                <p class="text-muted mb-0">Schedule a convenient time to pick up your ready document</p>
                            </div>
                            <a href="my-applications.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Applications
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Application Details -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-text me-2"></i>Application Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label text-muted small">Application Number</label>
                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($application['application_number']); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label text-muted small">Document Type</label>
                                <p class="fw-bold mb-0"><?php echo htmlspecialchars($application['type_name']); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label text-muted small">Status</label>
                                <span class="badge bg-success">Ready for Pickup</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label text-muted small">Fee</label>
                                <p class="fw-bold mb-0">₱<?php echo number_format($application['fee'], 2); ?></p>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted small">Purpose</label>
                                <p class="mb-0"><?php echo htmlspecialchars($application['purpose']); ?></p>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted small">Date Applied</label>
                                <p class="mb-0"><?php echo date('F j, Y g:i A', strtotime($application['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pickup Instructions -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>Pickup Instructions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Bring Valid ID</h6>
                                <p class="text-muted small mb-0">Bring any valid government-issued ID for verification.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Arrive on Time</h6>
                                <p class="text-muted small mb-0">Please arrive at your scheduled time to avoid delays.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Office Hours</h6>
                                <p class="text-muted small mb-0">Monday to Friday: 8:00 AM - 5:00 PM<br>Saturday: 8:00 AM - 12:00 PM</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Contact Information</h6>
                                <p class="text-muted small mb-0">For inquiries, contact the barangay office or text the official number.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Pickup Form -->
            <div class="col-lg-6">
                <?php if ($existingAppointment): ?>
                    <!-- Existing Appointment -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-check me-2"></i>Pickup Scheduled
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-date text-success" style="font-size: 3rem;"></i>
                            <h4 class="mt-3 mb-2"><?php echo date('F j, Y', strtotime($existingAppointment['appointment_date'])); ?></h4>
                            <p class="h5 text-primary mb-3"><?php echo date('g:i A', strtotime($existingAppointment['appointment_date'])); ?></p>
                            
                            <div class="badge bg-<?php 
                                echo $existingAppointment['status'] === 'completed' ? 'success' : 
                                    ($existingAppointment['status'] === 'cancelled' ? 'danger' : 
                                    ($existingAppointment['status'] === 'rescheduled' ? 'warning' : 'info'));
                            ?> mb-3">
                                <?php echo ucfirst($existingAppointment['status']); ?>
                            </div>
                            
                            <?php if ($existingAppointment['notes']): ?>
                                <div class="alert alert-light">
                                    <strong>Notes:</strong> <?php echo htmlspecialchars($existingAppointment['notes']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <p class="text-muted">
                                Your pickup appointment has been scheduled. Please arrive at the barangay office at the scheduled time.
                            </p>
                            
                            <a href="my_appointments.php" class="btn btn-primary">
                                <i class="bi bi-calendar-check me-2"></i>View All Appointments
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Schedule Pickup Form -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-plus me-2"></i>Schedule Pickup
                            </h5>
                        </div>
                        <form action="schedule-pickup.php?id=<?php echo $applicationId; ?>" method="POST">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Preferred Pickup Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="appointment_date" class="form-control" required
                                           min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>"
                                           max="<?php echo date('Y-m-d\TH:i', strtotime('+30 days')); ?>">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Select a date and time within office hours. Your schedule is subject to admin confirmation.
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Additional Notes <span class="text-muted">(Optional)</span></label>
                                    <textarea name="notes" class="form-control" rows="3" 
                                            placeholder="Any special instructions or requests for your pickup..."></textarea>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note:</strong> This is a pickup request. The admin will review and confirm your preferred schedule.
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="my-applications.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-lg me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-calendar-check me-2"></i>Schedule Pickup
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Quick Schedule Options -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bi bi-clock me-2"></i>Suggested Times
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <?php
                                // Generate quick schedule options for the next few days
                                $quickTimes = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];
                                $today = new DateTime();
                                $tomorrow = new DateTime('+1 day');
                                
                                for ($i = 1; $i <= 3; $i++) {
                                    $date = new DateTime("+{$i} day");
                                    // Skip weekends for suggestions
                                    if ($date->format('N') >= 6) continue;
                                    
                                    echo '<div class="col-12">';
                                    echo '<h6 class="text-muted small mb-2">' . $date->format('l, M j') . '</h6>';
                                    echo '<div class="d-flex flex-wrap gap-1 mb-3">';
                                    
                                    foreach ($quickTimes as $time) {
                                        $datetime = $date->format('Y-m-d') . 'T' . $time;
                                        echo '<button type="button" class="btn btn-outline-primary btn-sm quick-time" data-datetime="' . $datetime . '">';
                                        echo date('g:i A', strtotime($time));
                                        echo '</button>';
                                    }
                                    
                                    echo '</div></div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick time selection
    const quickTimeButtons = document.querySelectorAll('.quick-time');
    const datetimeInput = document.querySelector('input[name="appointment_date"]');
    
    quickTimeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const datetime = this.getAttribute('data-datetime');
            if (datetimeInput) {
                datetimeInput.value = datetime;
                
                // Highlight selected button
                quickTimeButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const datetimeInput = document.querySelector('input[name="appointment_date"]');
            if (!datetimeInput.value) {
                e.preventDefault();
                alert('Please select a pickup date and time.');
                datetimeInput.focus();
                return;
            }
            
            // Validate that the selected time is in the future
            const selectedTime = new Date(datetimeInput.value);
            const now = new Date();
            
            if (selectedTime <= now) {
                e.preventDefault();
                alert('Please select a future date and time.');
                datetimeInput.focus();
                return;
            }
            
            // Confirm submission
            if (!confirm('Schedule pickup for ' + selectedTime.toLocaleDateString() + ' at ' + selectedTime.toLocaleTimeString() + '?')) {
                e.preventDefault();
            }
        });
    }
});

// Add some CSS for the active quick time button
const style = document.createElement('style');
style.textContent = `
    .quick-time.active {
        background-color: var(--bs-primary) !important;
        color: white !important;
        border-color: var(--bs-primary) !important;
    }
    
    .quick-time:hover {
        background-color: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
    }
`;
document.head.appendChild(style);
</script>

<?php include 'scripts.php'; ?>
