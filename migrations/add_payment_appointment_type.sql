-- Add payment appointment type to appointments table
ALTER TABLE appointments 
MODIFY COLUMN appointment_type ENUM('verification', 'pickup', 'interview', 'payment') NOT NULL; 