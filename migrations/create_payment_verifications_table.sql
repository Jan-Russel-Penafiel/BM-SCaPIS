-- Create payment_verifications table
CREATE TABLE IF NOT EXISTS `payment_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','verified','failed','expired') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL 15 MINUTE),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `reference_number` (`reference_number`),
  KEY `status` (`status`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `fk_payment_verifications_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX idx_payment_verifications_status_created ON payment_verifications(status, created_at);
CREATE INDEX idx_payment_verifications_expires_at ON payment_verifications(expires_at); 