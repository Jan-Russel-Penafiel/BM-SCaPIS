<?php

class SMSGateway {
    private $apiKey;
    private $apiEndpoint;
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        
        // Get PhilSMS API key from database
        $stmt = $this->pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'philsms_api_key'");
        $stmt->execute();
        $this->apiKey = $stmt->fetchColumn();

        // Set PhilSMS API endpoint
        $this->apiEndpoint = 'https://api.philsms.com/v3/sms/send';
    }

    /**
     * Format phone number to international format
     * 
     * @param string $phone Phone number to format
     * @return string Formatted phone number
     */
    private function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to international format for Philippines
        if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
            $phone = '63' . substr($phone, 1);
        } elseif (strlen($phone) === 10) {
            $phone = '63' . $phone;
        }
        
        return $phone;
    }

    /**
     * Send SMS message
     * 
     * @param string $phoneNumber The recipient's phone number
     * @param string $message The message content
     * @return bool True if successful, throws exception otherwise
     * @throws Exception If SMS sending fails
     */
    public function sendSMS($phoneNumber, $message, $userId = null) {
        if (empty($this->apiKey) || $this->apiKey === '2100|J9BVGEx9FFOJAbHV0xfn6SMOkKBt80HTLjHb6zZX') {
            throw new Exception('SMS gateway not properly configured: No API key');
        }

        // Get sender name from config
        $stmt = $this->pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'philsms_sender_name'");
        $stmt->execute();
        $senderName = $stmt->fetchColumn() ?: 'PhilSMS';

        // Format phone number
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        // Insert into SMS notifications table
        $stmt = $this->pdo->prepare("INSERT INTO sms_notifications (user_id, phone_number, message, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$userId, $phoneNumber, $message]);
        $smsId = $this->pdo->lastInsertId();

        // PhilSMS API call
        $data = [
            'recipient' => $phoneNumber,
            'message' => $message,
            'sender_name' => $senderName
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Update SMS status
        if ($httpCode === 200) {
            $stmt = $this->pdo->prepare("UPDATE sms_notifications SET status = 'sent', api_response = ?, sent_at = NOW() WHERE id = ?");
            $stmt->execute([$response, $smsId]);
            return true;
        } else {
            $stmt = $this->pdo->prepare("UPDATE sms_notifications SET status = 'failed', api_response = ? WHERE id = ?");
            $stmt->execute([$response, $smsId]);
            throw new Exception('SMS gateway returned error: ' . $response);
        }
    }
}
