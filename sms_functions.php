<?php
// Include database connection
require_once "config.php";

/**
 * Get SMS configuration from database
 * @param PDO $pdo Database connection
 * @return array SMS configuration
 */
function getSMSConfig($pdo) {
    $stmt = $pdo->prepare("
        SELECT config_key, config_value 
        FROM system_config 
        WHERE config_key IN ('philsms_api_key', 'philsms_sender_name')
    ");
    $stmt->execute();
    $config = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['config_key']] = $row['config_value'];
    }
    
    return $config;
}

/**
 * Format phone number to international format
 * @param string $phone_number Phone number to format
 * @return string Formatted phone number
 */
function formatPhoneNumber($phone_number) {
    // Remove all non-numeric characters
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    
    // Convert to 09XXXXXXXXX format for Philippines
    if (strlen($phone_number) === 11 && substr($phone_number, 0, 1) === '0') {
        // Already in correct format
        return $phone_number;
    } elseif (strlen($phone_number) === 10) {
        // Add leading 0
        $phone_number = '0' . $phone_number;
    } elseif (strlen($phone_number) === 12 && substr($phone_number, 0, 2) === '63') {
        // Convert from +63 to 09 format
        $phone_number = '0' . substr($phone_number, 2);
    }
    
    return $phone_number;
}

/**
 * Send SMS using PhilSMS API
 * @param string $phone_number Recipient phone number
 * @param string $message SMS message content
 * @param string $api_key PhilSMS API key
 * @param string $sender_name Sender name
 * @return array Response with status and message
 */
function sendSMSUsingPhilSMS($phone_number, $message, $api_key, $sender_name = 'BM-SCaPIS') {
    // Format the phone number
    $phone_number = formatPhoneNumber($phone_number);

    // Validate phone number format
    if (!preg_match('/^09[0-9]{9}$/', $phone_number)) {
        return array(
            'success' => false,
            'message' => 'Invalid phone number format. Must be a valid Philippine mobile number (09XXXXXXXXX).'
        );
    }

    // Convert 09 format to +63 for API
    $api_phone_number = '+63' . substr($phone_number, 1);
    
    // Prepare the request data
    $data = array(
        'sender_id' => $sender_name,
        'recipient' => $api_phone_number,
        'message' => $message
    );

    // Initialize cURL session
    $ch = curl_init("https://app.philsms.com/api/v3/sms/send");

    // Set cURL options
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $api_key
        ),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ));

    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);

    // Close cURL session
    curl_close($ch);

    // Log the API request for debugging
    error_log(sprintf(
        "PhilSMS API Request - Number: %s, Status: %d, Response: %s, Error: %s",
        $phone_number,
        $http_code,
        $response,
        $curl_error
    ));

    // Handle cURL errors
    if ($curl_errno) {
        return array(
            'success' => false,
            'message' => 'Connection error: ' . $curl_error,
            'error_code' => $curl_errno
        );
    }

    // Parse response
    $result = json_decode($response, true);

    // Handle API response
    if ($http_code === 200 || $http_code === 201) {
        if (isset($result['status']) && $result['status'] === 'success') {
            return array(
                'success' => true,
                'message' => 'SMS sent successfully',
                'reference_id' => $result['message_id'] ?? $result['id'] ?? null,
                'delivery_status' => $result['status'] ?? 'Sent',
                'timestamp' => $result['timestamp'] ?? date('Y-m-d g:i A')
            );
        }
    }

    // Handle error responses
    $error_message = isset($result['message']) ? $result['message'] : 
                    (isset($result['error']) ? $result['error'] : 'Unknown error occurred');
    
    return array(
        'success' => false,
        'message' => 'API Error: ' . $error_message,
        'error_code' => $http_code,
        'error_details' => $result
    );
}

/**
 * Send SMS notification and store in database
 * @param string $phone_number Recipient phone number
 * @param string $message SMS message content
 * @param int $user_id User ID (optional, for logging)
 * @param string $notification_type Type of notification (optional)
 * @return array Response with status and message
 */
function sendSMSNotification($phone_number, $message, $user_id = null, $notification_type = 'application') {
    global $pdo;

    try {
        // Get SMS configuration
        $sms_config = getSMSConfig($pdo);
        $api_key = $sms_config['philsms_api_key'] ?? null;
        $sender_name = $sms_config['philsms_sender_name'] ?? 'BM-SCaPIS';

        // Handle null user_id by using a default system user
        if ($user_id === null) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $fallbackUserId = $stmt->fetchColumn();
            $user_id = $fallbackUserId;
        }

        // Format phone number
        $formattedPhone = formatPhoneNumber($phone_number);

        // Insert into SMS notifications table
        $smsId = null;
        if ($user_id !== null) {
            $stmt = $pdo->prepare("INSERT INTO sms_notifications (user_id, phone_number, message, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $formattedPhone, $message]);
            $smsId = $pdo->lastInsertId();
        }

        // Check if API key is configured
        if (empty($api_key) || $api_key === 'your_philsms_api_key_here') {
            if ($smsId !== null) {
                $stmt = $pdo->prepare("UPDATE sms_notifications SET status = 'failed', api_response = 'No API key configured' WHERE id = ?");
                $stmt->execute([$smsId]);
            }
            return array(
                'success' => false,
                'message' => 'SMS API key not configured'
            );
        }

        // Send SMS using PhilSMS
        $sms_result = sendSMSUsingPhilSMS($formattedPhone, $message, $api_key, $sender_name);

        // Update SMS status in database
        if ($smsId !== null) {
            if ($sms_result['success']) {
                $stmt = $pdo->prepare("UPDATE sms_notifications SET status = 'sent', api_response = ?, sent_at = NOW() WHERE id = ?");
                $stmt->execute([json_encode($sms_result), $smsId]);
            } else {
                $stmt = $pdo->prepare("UPDATE sms_notifications SET status = 'failed', api_response = ? WHERE id = ?");
                $stmt->execute([json_encode($sms_result), $smsId]);
            }
        }

        return $sms_result;

    } catch (Exception $e) {
        error_log('SMS Notification Error: ' . $e->getMessage());
        return array(
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        );
    }
}

/**
 * Send application status SMS notification
 * @param int $application_id Application ID
 * @param string $status New status
 * @param string $message Custom message (optional)
 * @param string $appointmentDate Optional appointment date for pickup
 * @return array Response with status and message
 */
function sendApplicationStatusSMS($application_id, $status, $message = null, $appointmentDate = null) {
    global $pdo;

    try {
        // Get application details with user information
        $stmt = $pdo->prepare("
            SELECT a.*, u.contact_number, u.sms_notifications, u.first_name, u.last_name,
                   dt.type_name, dt.processing_days
            FROM applications a
            JOIN users u ON a.user_id = u.id
            JOIN document_types dt ON a.document_type_id = dt.id
            WHERE a.id = ?
        ");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return array(
                'success' => false,
                'message' => 'Application not found'
            );
        }

        // Check if user has SMS notifications enabled
        if (!$application['sms_notifications'] || empty($application['contact_number'])) {
            return array(
                'success' => false,
                'message' => 'SMS notifications disabled or no contact number'
            );
        }

        // Generate message based on status if not provided
        if ($message === null) {
            $message = generateApplicationStatusMessage($application, $status, $appointmentDate);
        }

        // Send SMS notification
        return sendSMSNotification(
            $application['contact_number'],
            $message,
            $application['user_id'],
            'application_status'
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        );
    }
}

/**
 * Generate application status message
 * @param array $application Application data
 * @param string $status New status
 * @param string $appointmentDate Optional appointment date for pickup
 * @return string Generated message
 */
function generateApplicationStatusMessage($application, $status, $appointmentDate = null) {
    $appNumber = $application['application_number'];
    $docType = $application['type_name'];
    $residentName = $application['first_name'] . ' ' . $application['last_name'];

    switch ($status) {
        case 'processing':
            $estimatedDate = date('M j, Y', strtotime('+' . $application['processing_days'] . ' weekdays'));
            return "Your application #{$appNumber} is now being processed. Estimated completion: {$estimatedDate}";

        case 'ready_for_pickup':
            if ($appointmentDate) {
                $pickupDate = date('M j, Y', strtotime($appointmentDate));
            } else {
                $pickupDate = date('M j, Y', strtotime('next weekday'));
            }
            return "Your {$docType} (#{$appNumber}) is ready for pickup. Please visit the barangay office on {$pickupDate}.";

        case 'completed':
            return "Your {$docType} (#{$appNumber}) has been completed and delivered. Thank you for using our services!";

        case 'payment_waived':
            return "Your application #{$appNumber} payment has been waived. Your {$docType} application is now being processed.";

        case 'payment_received':
            return "Payment received for application #{$appNumber}. Your {$docType} is now being processed. You will be notified when it's ready for pickup.";

        case 'appointment_scheduled':
            return "Your appointment for application #{$appNumber} has been scheduled. Please check your email for details.";

        case 'appointment_rescheduled':
            return "Your appointment for application #{$appNumber} has been rescheduled. Please check your email for new details.";

        case 'appointment_cancelled':
            return "Your appointment for application #{$appNumber} has been cancelled. Please contact the barangay office for rescheduling.";

        case 'appointment_completed':
            return "Your appointment for application #{$appNumber} has been completed. Thank you for visiting the barangay office.";

        case 'document_ready':
            return "Your {$docType} (#{$appNumber}) is ready for pickup. Please visit the barangay office on the scheduled date.";

        default:
            return "Your application #{$appNumber} status has been updated to {$status}.";
    }
}

/**
 * Send payment notification SMS
 * @param int $application_id Application ID
 * @param string $payment_type Payment type (gcash, cash, waived)
 * @param float $amount Payment amount
 * @param string $reference Payment reference
 * @return array Response with status and message
 */
function sendPaymentNotificationSMS($application_id, $payment_type, $amount = null, $reference = null) {
    global $pdo;

    try {
        // Get application details
        $stmt = $pdo->prepare("
            SELECT a.*, u.contact_number, u.sms_notifications, u.first_name, u.last_name,
                   dt.type_name
            FROM applications a
            JOIN users u ON a.user_id = u.id
            JOIN document_types dt ON a.document_type_id = dt.id
            WHERE a.id = ?
        ");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return array(
                'success' => false,
                'message' => 'Application not found'
            );
        }

        // Check if user has SMS notifications enabled
        if (!$application['sms_notifications'] || empty($application['contact_number'])) {
            return array(
                'success' => false,
                'message' => 'SMS notifications disabled or no contact number'
            );
        }

        // Generate payment message
        $message = generatePaymentMessage($application, $payment_type, $amount, $reference);

        // Send SMS notification
        return sendSMSNotification(
            $application['contact_number'],
            $message,
            $application['user_id'],
            'payment_notification'
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        );
    }
}

/**
 * Generate payment notification message
 * @param array $application Application data
 * @param string $payment_type Payment type
 * @param float $amount Payment amount
 * @param string $reference Payment reference
 * @return string Generated message
 */
function generatePaymentMessage($application, $payment_type, $amount = null, $reference = null) {
    $appNumber = $application['application_number'];
    $docType = $application['type_name'];

    switch ($payment_type) {
        case 'gcash':
            $amountText = $amount ? '₱' . number_format($amount, 2) : '';
            $refText = $reference ? " Reference: {$reference}" : '';
            return "GCash payment received for application #{$appNumber}. Amount: {$amountText}.{$refText} Your {$docType} is now being processed.";

        case 'waived':
            return "Your application #{$appNumber} payment has been waived. Your {$docType} application is now being processed.";

        case 'cash':
            $amountText = $amount ? '₱' . number_format($amount, 2) : '';
            return "Cash payment received for application #{$appNumber}. Amount: {$amountText}. Your {$docType} is now being processed.";

        default:
            return "Payment received for application #{$appNumber}. Your {$docType} is now being processed.";
    }
}

/**
 * Send admin notification SMS
 * @param string $message Message content
 * @param string $notification_type Type of notification
 * @return array Response with status and message
 */
function sendAdminNotificationSMS($message, $notification_type = 'admin_alert') {
    global $pdo;

    try {
        // Get admin contact number
        $stmt = $pdo->prepare("SELECT id, contact_number FROM users WHERE role = 'admin' AND contact_number IS NOT NULL LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || empty($admin['contact_number'])) {
            return array(
                'success' => false,
                'message' => 'No admin contact number found'
            );
        }

        // Send SMS notification
        return sendSMSNotification(
            $admin['contact_number'],
            $message,
            $admin['id'],
            $notification_type
        );

    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        );
    }
}

/**
 * Get SMS notification statistics
 * @return array Statistics
 */
function getSMSStatistics() {
    global $pdo;

    try {
        // Total SMS sent
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_notifications WHERE status = 'sent'");
        $stmt->execute();
        $totalSent = $stmt->fetchColumn();

        // Total SMS failed
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_notifications WHERE status = 'failed'");
        $stmt->execute();
        $totalFailed = $stmt->fetchColumn();

        // Total SMS pending
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_notifications WHERE status = 'pending'");
        $stmt->execute();
        $totalPending = $stmt->fetchColumn();

        // Recent SMS (last 7 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM sms_notifications 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $recentSMS = $stmt->fetchColumn();

        return array(
            'total_sent' => $totalSent,
            'total_failed' => $totalFailed,
            'total_pending' => $totalPending,
            'recent_sms' => $recentSMS,
            'success_rate' => $totalSent > 0 ? round(($totalSent / ($totalSent + $totalFailed)) * 100, 2) : 0
        );

    } catch (Exception $e) {
        return array(
            'error' => $e->getMessage()
        );
    }
}
?> 