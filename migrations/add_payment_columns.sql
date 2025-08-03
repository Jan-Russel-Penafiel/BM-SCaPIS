-- Add payment-related columns to applications table
ALTER TABLE applications 
ADD COLUMN payment_receipt VARCHAR(255) DEFAULT NULL AFTER payment_reference;

-- Add indexes for better performance
CREATE INDEX idx_applications_payment_status ON applications(payment_status);
CREATE INDEX idx_applications_payment_method ON applications(payment_method);
CREATE INDEX idx_applications_payment_date ON applications(payment_date); 