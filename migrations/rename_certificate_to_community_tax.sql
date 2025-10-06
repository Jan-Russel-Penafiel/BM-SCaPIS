-- Migration: Rename "Certificate of Good Moral" to "Community Tax"
-- Created: October 6, 2025
-- Description: Updates the document type name from "Certificate of Good Moral" to "Community Tax (Cedula)"

-- Update the document type name and description
UPDATE document_types 
SET type_name = 'Community Tax (Cedula)',
    description = 'Community Tax Certificate (also known as Cedula)'
WHERE type_name = 'Certificate of Good Moral';

-- Note: This will automatically update all applications, appointments, and other records
-- that reference this document type through the document_type_id foreign key
