-- Add payment_allowed status to appointments table
ALTER TABLE appointments 
MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled', 'payment_allowed') DEFAULT 'scheduled'; 