-- Add payment_receipt column to applications table
-- Run this SQL in your database to enable receipt upload functionality

ALTER TABLE applications 
ADD COLUMN payment_receipt VARCHAR(255) DEFAULT NULL AFTER payment_date;

-- Add index for faster queries
CREATE INDEX idx_payment_receipt ON applications(payment_receipt);
