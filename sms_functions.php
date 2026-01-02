<?php
// Include database connection
require_once "config.php";

/**
 * Convert technical class names and terms to plain text for SMS
 * @param string $message Original message that may contain class names
 * @return string Plain text message suitable for SMS
 */
function convertToPlainMessage($message) {
    // Remove HTML tags if any
    $message = strip_tags($message);
    
    // Replace common database attributes and technical terms with plain language
    $replacements = array(
        // User and resident related
        'resident_id' => 'Resident ID',
        'user_id' => 'User ID',
        'first_name' => 'First Name',
        'middle_name' => 'Middle Name',
        'last_name' => 'Last Name',
        'full_name' => 'Full Name',
        'contact_number' => 'Contact Number',
        'phone_number' => 'Phone Number',
        'email_address' => 'Email Address',
        'sms_notifications' => 'SMS Notifications',
        
        // Location related
        'purok_name' => 'Purok',
        'purok_id' => 'Purok ID',
        'barangay_name' => 'Barangay',
        'address_line' => 'Address',
        
        // Application related
        'application_id' => 'Application ID',
        'application_number' => 'Application Number',
        'document_type_id' => 'Document Type ID',
        'type_name' => 'Document Type',
        'doc_type' => 'Document Type',
        'processing_days' => 'Processing Days',
        'application_status' => 'Application Status',
        'app_status' => 'Status',
        
        // Payment related
        'payment_status' => 'Payment Status',
        'payment_type' => 'Payment Type',
        'payment_amount' => 'Payment Amount',
        'payment_date' => 'Payment Date',
        'reference_number' => 'Reference Number',
        'transaction_id' => 'Transaction ID',
        'gcash_reference' => 'GCash Reference',
        
        // Appointment related
        'appointment_id' => 'Appointment ID',
        'appointment_date' => 'Appointment Date',
        'appointment_time' => 'Appointment Time',
        'pickup_date' => 'Pickup Date',
        'scheduled_date' => 'Scheduled Date',
        
        // Program related
        'program_name' => 'Program',
        'program_id' => 'Program ID',
        'distribution_date' => 'Distribution Date',
        'distribution_type' => 'Distribution Type',
        
        // System related
        'created_at' => 'Created',
        'updated_at' => 'Updated',
        'deleted_at' => 'Deleted',
        'sent_at' => 'Sent',
        'api_response' => 'Response',
        'error_message' => 'Error',
        'status_code' => 'Status Code',
        'config_key' => 'Setting',
        'config_value' => 'Value'
    );
    
    // Apply replacements (case-insensitive)
    foreach ($replacements as $technical => $plain) {
        $message = str_ireplace($technical, $plain, $message);
    }
    
    // Convert remaining camelCase to plain text (e.g., "ResidentName" -> "Resident Name")
    $message = preg_replace('/([a-z])([A-Z])/', '$1 $2', $message);
    
    // Convert remaining snake_case to plain text
    $message = preg_replace('/(\w+)_(\w+)/', '$1 $2', $message);
    
    // Preserve application numbers (APP-XXXXXXXX-XXXX format) before converting kebab-case
    // Replace with placeholder to protect from dash removal
    $appNumberPlaceholders = [];
    $message = preg_replace_callback('/APP-\d{8}-\d{4}/', function($matches) use (&$appNumberPlaceholders) {
        $placeholder = '##APPNUM' . count($appNumberPlaceholders) . '##';
        $appNumberPlaceholders[$placeholder] = $matches[0];
        return $placeholder;
    }, $message);
    
    // Convert remaining kebab-case to plain text (but not application numbers)
    $message = str_replace('-', ' ', $message);
    
    // Restore application numbers
    foreach ($appNumberPlaceholders as $placeholder => $appNumber) {
        $message = str_replace($placeholder, $appNumber, $message);
    }
    
    // Remove excessive whitespace
    $message = preg_replace('/\s+/', ' ', $message);
    
    // Trim and clean up
    $message = trim($message);
    
    // Ensure first letter is capitalized
    $message = ucfirst($message);
    
    return $message;
}

/**
 * Format SMS message with header and footer template
 * @param string $body Main message content
 * @param string $header Optional header text (default: VMC)
 * @param string $footer Optional footer text (default: Thank you. - Respective Personnel)
 * @return string Formatted SMS message
 */
function formatSMSTemplate($body, $header = '', $footer = '') {
    // IMPORTANT: IPROG SMS uses approved templates
    // The template system automatically adds header and footer
    // We only need to return the body message
    
    // Default header if not provided (used for preview only)
    if (empty($header)) {
        $header = 'VMC';
    }
    
    // Default footer if not provided (used for preview only)
    if (empty($footer)) {
        $footer = 'Thank you. - Respective Personnel';
    }
    
    // Convert body to plain text
    $body = convertToPlainMessage($body);
    
    // Format message to match IPROG template pattern
    // Template sample: "This is an important message from the Organization."
    // This ensures all messages match the approved template pattern
    $formatted_body = "This is an important message from the Organization. " . $body;
    
    // Return formatted body message
    // The IPROG template will automatically add header and footer
    return $formatted_body;
}

/**
 * Legacy function for backward compatibility
 * @param string $message Original message content
 * @return string Formatted message with prefix
 */
function formatSMSMessage($message) {
    return formatSMSTemplate($message);
}

/**
 * Get SMS configuration from database
 * @param PDO $pdo Database connection
 * @return array SMS configuration
 */
function getSMSConfig($pdo) {
    $stmt = $pdo->prepare("
        SELECT config_key, config_value 
        FROM system_config 
        WHERE config_key IN ('iprog_api_key', 'iprog_sender_name', 'philsms_api_key', 'philsms_sender_name')
    ");
    $stmt->execute();
    $config = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['config_key']] = $row['config_value'];
    }
    
    // For backwards compatibility, map old keys to new ones
    if (isset($config['philsms_api_key']) && !isset($config['iprog_api_key'])) {
        $config['iprog_api_key'] = $config['philsms_api_key'];
    }
    if (isset($config['philsms_sender_name']) && !isset($config['iprog_sender_name'])) {
        $config['iprog_sender_name'] = $config['philsms_sender_name'];
    }
    
    // Set default IPROG API key if not configured
    if (!isset($config['iprog_api_key']) || empty($config['iprog_api_key'])) {
        $config['iprog_api_key'] = '1ef3b27ea753780a90cbdf07d027fb7b52791004';
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
 * Send SMS using IPROG SMS API
 * @param string $phone_number Recipient phone number
 * @param string $message SMS message content
 * @param string $api_key IPROG SMS API token
 * @param string $sender_name Sender name (for backwards compatibility, not used in IPROG)
 * @return array Response with status and message
 */
function sendSMSUsingIPROG($phone_number, $message, $api_key, $sender_name = 'BM-SCaPIS') {
    // Check if message has the template format (Header\nBody\nFooter)
    $lines = explode("\n", $message);
    if (count($lines) >= 3) {
        // Extract only the body (middle part)
        // First line is header, last line is footer
        $body_lines = array_slice($lines, 1, -1);
        $message = implode(" ", $body_lines);
    }
    
    // Clean up the message (remove extra whitespace)
    $message = trim($message);
    
    // Prepare the phone number (remove any spaces and ensure 63 format for IPROG)
    $phone_number = str_replace([' ', '-', '(', ')', '.'], '', $phone_number);
    
    // Convert to 63 format (Philippine format required by IPROG)
    if (substr($phone_number, 0, 2) === '09') {
        $phone_number = '63' . substr($phone_number, 1);
    } elseif (substr($phone_number, 0, 1) === '0') {
        $phone_number = '63' . substr($phone_number, 1);
    } elseif (substr($phone_number, 0, 3) === '+63') {
        $phone_number = substr($phone_number, 1);
    } elseif (substr($phone_number, 0, 1) === '+') {
        $phone_number = substr($phone_number, 1);
    }

    // Validate phone number format
    if (!preg_match('/^63[0-9]{10}$/', $phone_number)) {
        return array(
            'success' => false,
            'message' => 'Invalid phone number format. Must be a valid Philippine mobile number.'
        );
    }

    // Format message with universal prefix for IPROG template compatibility
    // Check if message already has the prefix to avoid double-formatting
    $prefix = 'This is an important message from the Organization. ';
    if (strpos($message, $prefix) === 0) {
        $formatted_message = $message; // Already formatted
    } else {
        $formatted_message = formatSMSTemplate($message); // Format it
    }
    
    // Prepare the request data for IPROG SMS API
    $data = array(
        'api_token' => $api_key,
        'message' => $formatted_message,
        'phone_number' => $phone_number
    );

    // Initialize cURL session
    $ch = curl_init("https://sms.iprogtech.com/api/v1/sms_messages");

    // Set cURL options for IPROG SMS
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
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
        "IPROG SMS API Request - Number: %s, Status: %d, Response: %s, Error: %s",
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

    // Handle API response for IPROG SMS
    // IPROG returns: {"status":200,"message":"SMS successfully queued for delivery.","message_id":"xxx"}
    if ($http_code === 200 || $http_code === 201) {
        // Check for IPROG specific success indicators
        $isStatusSuccess = isset($result['status']) && ($result['status'] === 200 || $result['status'] === 'success' || $result['status'] === 201);
        $hasMessageId = isset($result['message_id']) && !empty($result['message_id']);
        $messageContainsSuccess = isset($result['message']) && is_string($result['message']) && 
                                  (stripos($result['message'], 'queued') !== false || 
                                   stripos($result['message'], 'sent') !== false ||
                                   stripos($result['message'], 'success') !== false);
        
        if ($isStatusSuccess || $hasMessageId || $messageContainsSuccess ||
            (isset($result['success']) && $result['success'] === true) ||
            (!isset($result['error']) && !isset($result['errors']) && $http_code === 200)) {
            return array(
                'success' => true,
                'message' => $result['message'] ?? 'SMS sent successfully',
                'reference_id' => $result['message_id'] ?? $result['id'] ?? $result['reference'] ?? null,
                'delivery_status' => $result['message'] ?? 'Queued',
                'timestamp' => $result['timestamp'] ?? date('Y-m-d g:i A')
            );
        }
    }

    // Handle error responses
    $error_message = isset($result['message']) ? $result['message'] : 
                    (isset($result['error']) ? $result['error'] : 
                    (isset($result['errors']) ? (is_array($result['errors']) ? implode(', ', $result['errors']) : $result['errors']) : 'Unknown error occurred'));
    
    return array(
        'success' => false,
        'message' => 'API Error: ' . $error_message,
        'error_code' => $http_code,
        'error_details' => $result
    );
}

/**
 * Legacy function name for backwards compatibility
 * @deprecated Use sendSMSUsingIPROG() instead
 */
function sendSMSUsingPhilSMS($phone_number, $message, $api_key, $sender_name = 'BM-SCaPIS') {
    return sendSMSUsingIPROG($phone_number, $message, $api_key, $sender_name);
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
        $api_key = $sms_config['iprog_api_key'] ?? $sms_config['philsms_api_key'] ?? '1ef3b27ea753780a90cbdf07d027fb7b52791004';
        $sender_name = $sms_config['iprog_sender_name'] ?? $sms_config['philsms_sender_name'] ?? 'BM-SCaPIS';

        // Handle null user_id by using a default system user
        if ($user_id === null) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $fallbackUserId = $stmt->fetchColumn();
            $user_id = $fallbackUserId;
        }

        // Format phone number
        $formattedPhone = formatPhoneNumber($phone_number);
        
        // Format message with universal prefix (this is what will be sent)
        $formattedMessage = formatSMSTemplate($message);
        
        // DEDUPLICATION: Check if same SMS was sent to this number in last 5 minutes
        // This prevents double-sending from accidental double-clicks or page reloads
        $stmt = $pdo->prepare("
            SELECT id FROM sms_notifications 
            WHERE phone_number = ? 
            AND SUBSTRING(message, 1, 100) = SUBSTRING(?, 1, 100)
            AND status = 'sent'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            LIMIT 1
        ");
        $stmt->execute([$formattedPhone, $formattedMessage]);
        $existingSms = $stmt->fetch();
        
        if ($existingSms) {
            // SMS already sent recently with same content - skip to save credits
            return array(
                'success' => true,
                'message' => 'SMS already sent recently (duplicate prevention)',
                'duplicate' => true,
                'existing_id' => $existingSms['id']
            );
        }

        // Insert into SMS notifications table with formatted message
        $smsId = null;
        if ($user_id !== null) {
            $stmt = $pdo->prepare("INSERT INTO sms_notifications (user_id, phone_number, message, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $formattedPhone, $formattedMessage]);
            $smsId = $pdo->lastInsertId();
        }

        // Check if API key is configured
        if (empty($api_key) || $api_key === 'your_philsms_api_key_here' || $api_key === 'your_iprog_api_key_here') {
            if ($smsId !== null) {
                $stmt = $pdo->prepare("UPDATE sms_notifications SET status = 'failed', api_response = 'No API key configured' WHERE id = ?");
                $stmt->execute([$smsId]);
            }
            return array(
                'success' => false,
                'message' => 'SMS API key not configured'
            );
        }

        // Send SMS using IPROG (backwards compatible with old function calls)
        $sms_result = sendSMSUsingIPROG($formattedPhone, $message, $api_key, $sender_name);

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

    // All messages must follow the approved IPROG template pattern
    // The template prefix "This is an important message from the Organization." is added by formatSMSTemplate()
    // Keep messages simple and consistent to match approved template
    // IMPORTANT: Avoid phrases like "barangay office on [date]" - use simpler wording
    switch ($status) {
        case 'processing':
            $estimatedDate = date('M j, Y', strtotime('+' . $application['processing_days'] . ' weekdays'));
            return "Your application {$appNumber} is now being processed. Estimated completion date is {$estimatedDate}.";

        case 'ready_for_pickup':
            if ($appointmentDate) {
                $pickupDate = date('M j, Y', strtotime($appointmentDate));
            } else {
                $pickupDate = date('M j, Y', strtotime('next weekday'));
            }
            return "Your application {$appNumber} is now ready for pickup on {$pickupDate}.";

        case 'completed':
            return "Your application {$appNumber} is now completed. Thank you for using our services.";

        case 'payment_waived':
            return "Your application {$appNumber} payment is now waived. Processing will begin shortly.";

        case 'payment_received':
            return "Your application {$appNumber} payment is now confirmed. Processing will begin shortly.";

        case 'appointment_scheduled':
            if ($appointmentDate) {
                $apptDate = date('M j, Y', strtotime($appointmentDate));
                return "Your application {$appNumber} appointment is now scheduled on {$apptDate}.";
            }
            return "Your application {$appNumber} appointment is now scheduled. Check your account for details.";

        case 'appointment_rescheduled':
            return "Your application {$appNumber} appointment is now rescheduled. Check your account for details.";

        case 'appointment_cancelled':
            return "Your application {$appNumber} appointment is now cancelled. Please contact us for assistance.";

        case 'appointment_completed':
            return "Your application {$appNumber} appointment is now completed. Thank you for visiting us.";

        case 'document_ready':
            return "Your application {$appNumber} document is now ready for pickup.";

        default:
            return "Your application {$appNumber} is now updated to {$status}.";
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

    // All messages must follow the approved IPROG template pattern
    // Use consistent "Your application {number} is now..." pattern
    switch ($payment_type) {
        case 'gcash':
            return "Your application {$appNumber} payment is now confirmed via GCash. Processing will begin shortly.";

        case 'waived':
            return "Your application {$appNumber} payment is now waived. Processing will begin shortly.";

        case 'cash':
            return "Your application {$appNumber} payment is now confirmed. Processing will begin shortly.";

        default:
            return "Your application {$appNumber} payment is now received. Processing will begin shortly.";
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