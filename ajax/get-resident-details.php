<?php
// Prevent any output before headers
ob_start();

require_once '../config.php';

// Set JSON content type header first
header('Content-Type: application/json');

try {
    // Require login and must be admin or purok leader
    requireLogin();
    if (!in_array($_SESSION['role'], ['admin', 'purok_leader'])) {
        throw new Exception('Unauthorized access');
    }

    if (!isset($_GET['id'])) {
        throw new Exception('Resident ID is required');
    }

    $residentId = $_GET['id'];

    // Get resident details with purok information based on database schema
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.role,
            u.status,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.suffix,
            u.birthdate,
            u.age,
            u.gender,
            u.civil_status,
            u.contact_number,
            u.email,
            u.purok_id,
            u.address,
            u.occupation,
            u.monthly_income,
            u.emergency_contact_name,
            u.emergency_contact_number,
            u.profile_picture,
            u.valid_id_front,
            u.valid_id_back,
            u.purok_leader_approval,
            u.admin_approval,
            u.purok_leader_remarks,
            u.admin_remarks,
            u.approved_by_purok_leader,
            u.approved_by_admin,
            u.approved_at,
            u.sms_notifications,
            u.email_notifications,
            u.created_at,
            u.updated_at,
            p.purok_name,
            CONCAT(pl.first_name, ' ', pl.last_name) as purok_leader_name,
            pl.contact_number as purok_leader_contact,
            pl.email as purok_leader_email,
            CONCAT(apl.first_name, ' ', apl.last_name) as approved_by_purok_leader_name,
            CONCAT(aa.first_name, ' ', aa.last_name) as approved_by_admin_name
        FROM users u
        LEFT JOIN puroks p ON u.purok_id = p.id
        LEFT JOIN users pl ON p.purok_leader_id = pl.id
        LEFT JOIN users apl ON u.approved_by_purok_leader = apl.id
        LEFT JOIN users aa ON u.approved_by_admin = aa.id
        WHERE u.id = ? AND u.role = 'resident'
    ");

    $stmt->execute([$residentId]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resident) {
        throw new Exception('Resident not found');
    }

    // If purok leader, check if resident belongs to their purok
    if ($_SESSION['role'] === 'purok_leader' && $resident['purok_id'] !== $_SESSION['purok_id']) {
        throw new Exception('Unauthorized access');
    }

    // Format dates
    $resident['birthdate'] = date('Y-m-d', strtotime($resident['birthdate']));
    $resident['created_at'] = date('M j, Y g:i A', strtotime($resident['created_at']));
    $resident['updated_at'] = date('M j, Y g:i A', strtotime($resident['updated_at']));
    $resident['approved_at'] = $resident['approved_at'] ? date('M j, Y g:i A', strtotime($resident['approved_at'])) : null;

    // Format monetary values
    $resident['monthly_income'] = $resident['monthly_income'] ? number_format($resident['monthly_income'], 2) : null;

    // Clear any output buffers
    ob_clean();

    // Send success response
    echo json_encode([
        'success' => true,
        'data' => $resident
    ]);

} catch (Exception $e) {
    // Clear any output buffers
    ob_clean();

    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 