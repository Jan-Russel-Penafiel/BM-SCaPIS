# Real-time GCash Payment System

## Overview

The real-time GCash payment system provides a seamless payment experience for residents applying for barangay documents. The system integrates with GCash mobile app through deep linking and provides real-time payment verification.

## Features

### 🔄 Real-time Payment Flow
- **One-click payment initiation**: Users can start payment with a single click
- **GCash deep linking**: Automatically opens GCash app with pre-filled payment details
- **Real-time verification**: Automatic payment verification without manual receipt upload
- **Session management**: Secure payment sessions with 15-minute expiration
- **Back navigation prevention**: Prevents users from accidentally leaving during payment

### 🛡️ Security Features
- **CSRF protection**: All payment forms include CSRF tokens
- **Session validation**: Payment sessions are tied to user accounts
- **Payment verification**: Server-side payment verification
- **Expiration handling**: Automatic session cleanup for expired payments

### 📱 User Experience
- **Mobile-optimized**: Responsive design for mobile devices
- **Progress tracking**: Visual payment progress indicators
- **Copy-to-clipboard**: Easy copying of payment details
- **Auto-refresh**: Automatic status updates every 30 seconds
- **Success animations**: Celebratory animations on successful payment

## File Structure

```
├── pay-application.php          # Main payment initiation page
├── gcash-payment.php           # Real-time payment flow page
├── payment-success.php          # Payment success page
├── check-payment-status.php     # AJAX endpoint for status checks
├── migrations/
│   └── create_payment_verifications_table.sql
└── assets/
    └── images/
        └── gcash-logo.png      # GCash logo (placeholder)
```

## Database Schema

### payment_verifications Table
```sql
CREATE TABLE payment_verifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  application_id INT NOT NULL,
  reference_number VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','verified','failed','expired') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verified_at TIMESTAMP NULL,
  expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 15 MINUTE),
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);
```

## Payment Flow

### 1. Payment Initiation (`pay-application.php`)
- User clicks "Pay with GCash" button
- System generates unique payment reference
- Creates payment verification record
- Stores payment session in user session
- Redirects to payment flow page

### 2. Payment Flow (`gcash-payment.php`)
- Displays payment details and GCash instructions
- Provides copy-to-clipboard functionality
- Implements GCash deep linking
- Shows countdown timer (15 minutes)
- Prevents back navigation during payment
- Auto-refreshes payment status every 30 seconds

### 3. Payment Verification
- User completes payment in GCash app
- Returns to website and clicks "I've Completed Payment"
- System verifies payment with GCash API (simulated)
- Updates application status to "processing"
- Sends SMS notifications
- Redirects to success page

### 4. Success Page (`payment-success.php`)
- Displays payment receipt
- Shows next steps timeline
- Provides download receipt functionality
- Offers quick navigation options

## API Endpoints

### Check Payment Status
```
GET /check-payment-status.php?payment_id={id}
```
Returns JSON with payment status and details.

## Configuration

### GCash Integration
The system includes placeholder functions for GCash API integration:

```php
function verifyGCashPayment($referenceNumber, $expectedAmount) {
    // In production, this would call actual GCash API
    // For demo, accepts any reference starting with 'GC'
    return strpos($referenceNumber, 'GC') === 0;
}
```

### Deep Linking
GCash deep links are implemented for seamless app opening:

```javascript
const gcashDeepLink = 'gcash://send?number=09123456789&amount=100.00&reference=GC123456789';
window.location.href = gcashDeepLink;
```

## Security Considerations

1. **CSRF Protection**: All forms include CSRF tokens
2. **Session Validation**: Payment sessions are user-specific
3. **Input Validation**: All payment data is validated
4. **SQL Injection Prevention**: Prepared statements used throughout
5. **XSS Prevention**: All output is properly escaped

## Error Handling

- **Payment expiration**: Automatic cleanup of expired sessions
- **Verification failures**: Clear error messages and retry options
- **Network issues**: Graceful fallbacks for API failures
- **Session timeouts**: Automatic redirect to login

## Mobile Optimization

- **Responsive design**: Works on all screen sizes
- **Touch-friendly**: Large buttons and touch targets
- **Progressive enhancement**: Works without JavaScript
- **Offline support**: Basic functionality without internet

## Testing

### Manual Testing Checklist
- [ ] Payment initiation works
- [ ] GCash deep linking opens app
- [ ] Payment verification succeeds
- [ ] Session expiration works
- [ ] Back navigation is prevented
- [ ] Success page displays correctly
- [ ] Receipt download works
- [ ] SMS notifications are sent

### Automated Testing
```php
// Test payment verification
$result = verifyGCashPayment('GC123456789', 100.00);
assert($result === true);

// Test session creation
$sessionId = createPaymentSession(1, 100.00, 'GC123456789');
assert($sessionId > 0);
```

## Deployment Notes

1. **Database Migration**: Run the payment_verifications table creation
2. **GCash Logo**: Replace placeholder with actual GCash logo
3. **API Integration**: Replace simulated verification with actual GCash API
4. **SMS Configuration**: Ensure SMS gateway is properly configured
5. **SSL Certificate**: Required for secure payment processing

## Future Enhancements

- **Webhook support**: Real-time payment notifications from GCash
- **Multiple payment methods**: Add other e-wallets
- **Payment analytics**: Track payment success rates
- **Automated reconciliation**: Match payments with applications
- **Refund processing**: Handle payment refunds
- **Payment history**: Detailed payment logs for users

## Support

For technical support or questions about the GCash payment system, please contact:
- Email: support@barangaymalangit.gov.ph
- Phone: 0912-345-6789
- Office: Barangay Hall, Malangit 