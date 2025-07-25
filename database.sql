-- BM-SCaPIS Database Schema
-- Barangay Malangit Smart Clearance and Permit Issuance System

CREATE DATABASE IF NOT EXISTS bm_scapis;
USE bm_scapis;

-- System Configuration Table
CREATE TABLE system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default API keys and system configurations
INSERT INTO system_config (config_key, config_value) VALUES
('philsms_api_key', 'your_philsms_api_key_here'),
('philsms_sender_name', 'BM-SCaPIS'),
('system_name', 'BM-SCaPIS'),
('barangay_name', 'Barangay Malangit'),
('ringtone_enabled', '1');

-- Purok/Zones Table
CREATE TABLE puroks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purok_name VARCHAR(100) NOT NULL,
    purok_leader_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample puroks
INSERT INTO puroks (purok_name) VALUES
('Purok 1'), ('Purok 2'), ('Purok 3'), ('Purok 4'), ('Purok 5'),
('Purok 6'), ('Purok 7'), ('Purok 8'), ('Purok 9'), ('Purok 10');

-- Users Table (Residents, Purok Leaders, Admin)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('resident', 'purok_leader', 'admin') NOT NULL,
    status ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending',
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(20),
    birthdate DATE,
    age INT,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed') NOT NULL,
    contact_number VARCHAR(15),
    email VARCHAR(100),
    purok_id INT,
    address TEXT,
    occupation VARCHAR(100),
    monthly_income DECIMAL(10,2),
    emergency_contact_name VARCHAR(100),
    emergency_contact_number VARCHAR(15),
    profile_picture VARCHAR(255),
    valid_id_front VARCHAR(255),
    valid_id_back VARCHAR(255),
    purok_leader_approval ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending',
    admin_approval ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending',
    purok_leader_remarks TEXT,
    admin_remarks TEXT,
    approved_by_purok_leader INT,
    approved_by_admin INT,
    approved_at TIMESTAMP NULL,
    sms_notifications TINYINT(1) DEFAULT 1,
    email_notifications TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (purok_id) REFERENCES puroks(id),
    FOREIGN KEY (approved_by_purok_leader) REFERENCES users(id),
    FOREIGN KEY (approved_by_admin) REFERENCES users(id)
);

-- Create default admin user
INSERT INTO users (username, password, role, status, first_name, last_name, gender, civil_status, purok_leader_approval, admin_approval) 
VALUES ('admin001', 'admin123', 'admin', 'approved', 'System', 'Administrator', 'Male', 'Single', 'approved', 'approved');

-- Document Types Table
CREATE TABLE document_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    fee DECIMAL(8,2) DEFAULT 0.00,
    requirements TEXT,
    processing_days INT DEFAULT 3,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default document types
INSERT INTO document_types (type_name, description, fee, requirements, processing_days) VALUES
('Barangay Clearance', 'Certificate of Barangay Clearance for various purposes', 50.00, 'Valid ID, Cedula, Residence Certificate', 3),
('Certificate of Residency', 'Proof of residency in Barangay Malangit', 30.00, 'Valid ID, Proof of Address', 2),
('Certificate of Indigency', 'Certificate for low-income residents', 25.00, 'Valid ID, Income Statement, Barangay ID', 3),
('Business Permit', 'Permit for small business operations', 200.00, 'Valid ID, Business Registration, Location Map', 5),
('Building Permit', 'Permit for construction/renovation', 500.00, 'Valid ID, Construction Plans, Lot Title', 7),
('Certificate of Good Moral', 'Character certificate', 40.00, 'Valid ID, Character References', 3);

-- Applications Table
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_number VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    document_type_id INT NOT NULL,
    purpose TEXT NOT NULL,
    urgency ENUM('Regular', 'Rush') DEFAULT 'Regular',
    status ENUM('pending', 'processing', 'ready_for_pickup', 'completed', 'rejected') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'waived') DEFAULT 'unpaid',
    payment_amount DECIMAL(8,2),
    payment_date TIMESTAMP NULL,
    payment_reference VARCHAR(100),
    admin_remarks TEXT,
    pickup_date TIMESTAMP NULL,
    appointment_date TIMESTAMP NULL,
    processed_by INT,
    priority_level INT DEFAULT 1,
    supporting_documents TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Application Status History Table
CREATE TABLE application_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    remarks TEXT,
    changed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Appointments Table
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    appointment_type ENUM('verification', 'pickup', 'interview') NOT NULL,
    appointment_date DATETIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- SMS Notifications Table
CREATE TABLE sms_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    api_response TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- System Notifications Table (For ringtone alerts)
CREATE TABLE system_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    target_role ENUM('admin', 'purok_leader', 'all') NOT NULL,
    target_user_id INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (target_user_id) REFERENCES users(id)
);

-- Activity Logs Table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_affected VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Reports Cache Table
CREATE TABLE reports_cache (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_type VARCHAR(100) NOT NULL,
    parameters JSON,
    data JSON,
    generated_by INT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Update purok leaders foreign key
ALTER TABLE puroks ADD FOREIGN KEY (purok_leader_id) REFERENCES users(id);

-- Indexes for better performance
CREATE INDEX idx_users_role_status ON users(role, status);
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_applications_user_status ON applications(user_id, status);
CREATE INDEX idx_activity_logs_user_date ON activity_logs(user_id, created_at);
CREATE INDEX idx_notifications_user_read ON system_notifications(target_user_id, is_read);
CREATE INDEX idx_sms_status ON sms_notifications(status);

-- Views for reporting
CREATE VIEW vw_pending_registrations AS
SELECT 
    u.id,
    u.username,
    CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) as full_name,
    u.gender,
    u.age,
    p.purok_name,
    u.contact_number,
    u.purok_leader_approval,
    u.admin_approval,
    u.created_at
FROM users u
LEFT JOIN puroks p ON u.purok_id = p.id
WHERE u.role = 'resident' AND u.status = 'pending';

CREATE VIEW vw_application_summary AS
SELECT 
    a.id,
    a.application_number,
    CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
    dt.type_name as document_type,
    a.purpose,
    a.status,
    a.payment_status,
    a.payment_amount,
    p.purok_name,
    a.created_at,
    a.updated_at
FROM applications a
JOIN users u ON a.user_id = u.id
JOIN document_types dt ON a.document_type_id = dt.id
LEFT JOIN puroks p ON u.purok_id = p.id;

-- Trigger to update user status when both approvals are given
DELIMITER //
CREATE TRIGGER tr_user_approval_status 
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.purok_leader_approval = 'approved' AND NEW.admin_approval = 'approved' THEN
        UPDATE users SET status = 'approved', approved_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    ELSEIF NEW.purok_leader_approval = 'disapproved' OR NEW.admin_approval = 'disapproved' THEN
        UPDATE users SET status = 'disapproved' WHERE id = NEW.id;
    END IF;
END//
DELIMITER ;

-- Trigger to create notification on new registration
DELIMITER //
CREATE TRIGGER tr_new_registration_notification 
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'resident' THEN
        -- Notify admin
        INSERT INTO system_notifications (type, title, message, target_role, metadata)
        VALUES (
            'new_registration',
            'New Resident Registration',
            CONCAT('New registration from ', NEW.first_name, ' ', NEW.last_name),
            'admin',
            JSON_OBJECT('user_id', NEW.id, 'purok_id', NEW.purok_id)
        );
        
        -- Notify purok leader if purok is assigned
        IF NEW.purok_id IS NOT NULL THEN
            INSERT INTO system_notifications (type, title, message, target_role, metadata)
            VALUES (
                'new_registration',
                'New Resident Registration in Your Purok',
                CONCAT('New registration from ', NEW.first_name, ' ', NEW.last_name, ' in your purok'),
                'purok_leader',
                JSON_OBJECT('user_id', NEW.id, 'purok_id', NEW.purok_id)
            );
        END IF;
    END IF;
END//
DELIMITER ;
