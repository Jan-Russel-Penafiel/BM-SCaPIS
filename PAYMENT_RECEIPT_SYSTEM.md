# Payment Receipt Upload System

## Changes Made

### 1. Database Changes
Run the SQL file `add_payment_receipt_column.sql` to add the required column:
```sql
ALTER TABLE applications 
ADD COLUMN payment_receipt VARCHAR(255) DEFAULT NULL AFTER payment_date;
```

Note: The system uses the existing `payment_date` column to track when the receipt was uploaded.

### 2. File Structure
Create the uploads directory:
- `uploads/payment_receipts/` - This will be created automatically when first receipt is uploaded

### 3. File Changes

#### pay-application.php
- Added file upload form for payment receipt/screenshot
- Handles file validation (JPG, PNG, GIF, PDF, max 5MB)
- Uploads receipt to `uploads/payment_receipts/` directory
- Saves receipt path to database

#### my-applications.php
- Added "Receipt" column in desktop table view
- Added "View Receipt" button for applications with uploaded receipts
- Added receipt preview modal for viewing images and PDFs
- Shows receipt status in mobile card view

#### applications.php (Admin view)
- Added "View Receipt" button in payment column
- Admin can preview uploaded receipts
- Added receipt preview modal with support for images and PDFs

### 4. Removed Support Chat Widgets
- Removed from all three files for cleaner interface

## How It Works

### For Residents:
1. Go to "My Applications"
2. Click "Pay" button for unpaid application
3. Scan QR code and pay via GCash
4. Upload payment receipt/screenshot
5. Wait for admin confirmation

### For Admin:
1. View applications in "Applications" page
2. Click "View Receipt" button to preview uploaded receipt
3. Verify payment and mark as received
4. Process the application

## File Upload Details
- **Allowed formats**: JPG, JPEG, PNG, GIF, PDF
- **Maximum size**: 5MB
- **Storage location**: `uploads/payment_receipts/`
- **Filename format**: `receipt_{application_id}_{timestamp}.{extension}`

## Security Notes
- File type validation on server side
- File size limit enforced
- Uploaded files stored outside web-accessible directories (recommended)
- Only authenticated users can upload
- Users can only upload for their own applications
