# SMS Notification System Implementation

## Overview
The BM-SCaPIS system now includes automatic SMS notifications for application status changes and payment processing using the PhilSMS API.

## Features Implemented

### 1. Automatic Status Changes
- **Payment Made**: When a resident pays via GCash, the application status automatically changes from "pending" to "processing"
- **Processing Started**: Admin can manually start processing applications
- **Ready for Pickup**: Admin can mark applications as ready for pickup
- **Completed**: Admin can mark applications as completed

### 2. Automatic Payment Processing
- **Payment Waived**: Admin can waive payment for applications
- **Payment Received**: Automatic status change when payment is made

### 3. SMS Notifications
All status and payment changes automatically trigger SMS notifications to residents with the following format:

#### Phone Number Support
- **09XXXXXXXXX** (Philippine mobile format)
- **+639XXXXXXXXX** (International format)
- **639XXXXXXXXX** (International format without +)

All numbers are automatically converted to international format (639XXXXXXXXX) for PhilSMS API.

## SMS Notification Messages

### 1. Payment Received
```
Payment received for application #BM20241200001. Your Barangay Clearance is now being processed. You will be notified when it's ready for pickup.
```

### 2. Processing Started
```
Your application #BM20241200001 is now being processed. Estimated completion: Dec 15, 2024
```

### 3. Ready for Pickup
```
Your Barangay Clearance (#BM20241200001) is ready for pickup. Please visit the barangay office on Dec 15, 2024. Pickup instructions: Bring valid ID
```

### 4. Payment Waived
```
Your application #BM20241200001 payment has been waived. Your Barangay Clearance application is now being processed.
```

### 5. Application Completed
```
Your Barangay Clearance (#BM20241200001) has been completed and delivered. Thank you for using our services!
```

## Configuration

### PhilSMS API Setup
1. Get API key from PhilSMS
2. Configure in system settings:
   - `philsms_api_key`: Your PhilSMS API key
   - `philsms_sender_name`: Sender name (default: BM-SCaPIS)

### User Preferences
- Users can enable/disable SMS notifications in their profile settings
- Default: SMS notifications enabled

## Files Modified

### Core Files
- `config.php`: Updated `sendSMSNotification()` function with proper phone formatting
- `applications.php`: Added auto-processing functionality and new action buttons
- `process-application.php`: Updated to use new SMS function
- `mark-ready.php`: Updated to use new SMS function
- `waive-payment.php`: Updated to use new SMS function
- `pay-application.php`: Added automatic status change and SMS notifications

### New Files
- `complete-application.php`: New file for completing applications with SMS
- `test-sms.php`: Test script for SMS functionality
- `SMS_NOTIFICATION_SYSTEM.md`: This documentation

## Database Tables Used

### sms_notifications
- `id`: Primary key
- `user_id`: User who receives the SMS
- `phone_number`: Formatted phone number
- `message`: SMS content
- `status`: pending/sent/failed
- `api_response`: API response from PhilSMS
- `sent_at`: Timestamp when sent
- `created_at`: Timestamp when created

### system_config
- `philsms_api_key`: PhilSMS API key
- `philsms_sender_name`: Sender name for SMS

## Testing

### Test SMS Functionality
1. Access `test-sms.php` in your browser
2. Verify phone number formatting
3. Test SMS sending (if API key is configured)
4. View recent SMS notifications

### Test Application Flow
1. Create a new application
2. Make payment via GCash
3. Verify automatic status change to "processing"
4. Check SMS notification sent to resident
5. Process application as admin
6. Mark as ready for pickup
7. Complete application
8. Verify all SMS notifications sent

## Error Handling

### API Key Not Configured
- SMS notifications are logged but marked as failed
- System continues to function normally
- Admin is notified to configure API key

### Invalid Phone Numbers
- Phone numbers are automatically formatted
- Invalid numbers are logged but don't break the system

### API Failures
- Failed SMS are logged with error details
- System continues to function
- Admin can retry failed SMS from admin panel

## Security Considerations

### Phone Number Validation
- All phone numbers are validated and formatted
- Only Philippine mobile numbers are supported
- International format is used for API calls

### API Key Security
- API key is stored in database, not in code
- API key is not logged or exposed in error messages
- Failed API calls don't expose sensitive information

## Future Enhancements

### Planned Features
1. SMS delivery status tracking
2. Retry mechanism for failed SMS
3. SMS templates for different document types
4. Bulk SMS for announcements
5. SMS scheduling for reminders

### Integration Possibilities
1. WhatsApp Business API
2. Email notifications
3. Push notifications
4. Telegram bot integration

## Troubleshooting

### Common Issues

1. **SMS not sending**
   - Check API key configuration
   - Verify phone number format
   - Check API endpoint accessibility

2. **Phone number formatting issues**
   - Ensure numbers start with 09 or +63
   - Check for special characters
   - Verify number length

3. **API errors**
   - Check PhilSMS API documentation
   - Verify API key permissions
   - Check network connectivity

### Debug Steps
1. Run `test-sms.php` to verify configuration
2. Check `sms_notifications` table for error details
3. Verify API key in system settings
4. Test with a known working phone number

## Support

For technical support:
1. Check the test script output
2. Review SMS notification logs
3. Verify PhilSMS API status
4. Contact system administrator 