-- Support Chat System Database Schema
-- Creates tables for real-time chat between residents and admin

-- Chat Conversations Table
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    admin_id INT DEFAULT NULL,
    subject VARCHAR(255) DEFAULT 'Payment Support',
    status ENUM('active', 'closed', 'waiting') DEFAULT 'active',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    application_id INT DEFAULT NULL,
    payment_id INT DEFAULT NULL,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resident_last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    admin_last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
    INDEX idx_resident_id (resident_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_status (status),
    INDEX idx_last_message (last_message_at)
);

-- Chat Messages Table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('resident', 'admin') NOT NULL,
    message_type ENUM('text', 'file', 'system') DEFAULT 'text',
    message_content TEXT NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    file_size INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    deleted_at TIMESTAMP NULL,
    reply_to_message_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_message_id) REFERENCES chat_messages(id) ON DELETE SET NULL,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read)
);

-- Chat Settings Table
CREATE TABLE IF NOT EXISTS chat_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default chat settings
INSERT INTO chat_settings (setting_key, setting_value, description) VALUES
('chat_enabled', '1', 'Enable/disable chat system'),
('auto_assign_admin', '1', 'Automatically assign available admin to new conversations'),
('max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,txt', 'Allowed file extensions for uploads'),
('chat_widget_position', 'bottom-right', 'Position of chat widget (bottom-right, bottom-left)'),
('chat_widget_color', '#007bff', 'Primary color of chat widget'),
('offline_message', 'Admin is currently offline. Your message will be answered as soon as possible.', 'Message shown when admin is offline'),
('welcome_message', 'Hello! How can we help you with your payment or application?', 'Welcome message for new conversations'),
('typing_timeout', '3000', 'Typing indicator timeout in milliseconds'),
('message_limit_per_minute', '10', 'Maximum messages per minute per user'),
('admin_notification_sound', '1', 'Play sound for admin when receiving new messages'),
('resident_can_upload_files', '1', 'Allow residents to upload files in chat'),
('chat_history_retention_days', '365', 'Number of days to keep chat history');

-- Chat Online Status Table
CREATE TABLE IF NOT EXISTS chat_online_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    is_online TINYINT(1) DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status_message VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_online (is_online),
    INDEX idx_last_activity (last_activity)
);

-- Chat Message Rate Limiting Table
CREATE TABLE IF NOT EXISTS chat_rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_window_start (window_start)
);

-- Chat Typing Indicators Table
CREATE TABLE IF NOT EXISTS chat_typing_indicators (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    is_typing TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_conversation (conversation_id, user_id),
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_updated_at (updated_at)
);

-- Add indexes for better performance
ALTER TABLE chat_conversations ADD INDEX idx_status_updated (status, updated_at);
ALTER TABLE chat_messages ADD INDEX idx_conversation_read (conversation_id, is_read);
ALTER TABLE chat_messages ADD INDEX idx_sender_type (sender_type, created_at);