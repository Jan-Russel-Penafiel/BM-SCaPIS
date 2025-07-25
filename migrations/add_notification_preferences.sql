-- Add notification preferences columns to users table
ALTER TABLE users
ADD COLUMN sms_notifications TINYINT(1) DEFAULT 1 AFTER approved_at,
ADD COLUMN email_notifications TINYINT(1) DEFAULT 1 AFTER sms_notifications;
 
-- Update existing users to have notifications enabled by default
UPDATE users SET sms_notifications = 1, email_notifications = 1 WHERE sms_notifications IS NULL; 