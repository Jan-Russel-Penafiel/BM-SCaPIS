# Payment Appointment Flow

## Overview
This document describes the enhanced payment flow that includes payment appointments and admin approval for payments.

## Flow Steps

### 1. Application Submission
- Resident submits application
- Application status: `pending`
- Payment status: `unpaid`

### 2. Admin Schedules Payment Appointment
- Admin clicks "Schedule Payment Appointment" button
- Admin sets appointment date/time and notes
- System creates appointment with:
  - `appointment_type`: `payment`
  - `status`: `scheduled`
- **Resident receives SMS notification** about scheduled appointment
- **Resident receives email notification** with detailed appointment information

### 3. Admin Allows Payment
- Admin clicks "Allow Payment" button (replaces "Schedule Payment Appointment" button)
- **Button is only enabled on the scheduled appointment date**
- System updates appointment status to `payment_allowed`
- **Resident receives SMS notification** that payment is now allowed
- **Resident receives email notification** with payment instructions
- Pay button in resident's view becomes enabled

### 4. Resident Makes Payment
- Resident can now click "Pay" button in their applications list
- Resident proceeds with GCash payment
- Payment verification occurs
- Application status changes to `processing`
- Payment status changes to `paid`

## Database Changes

### Appointments Table
- Added `payment_allowed` to status enum
- Added `payment` to appointment_type enum

### New Files
- `allow-payment.php` - Handles allowing payment for applications
- `schedule-appointment.php` - Handles general appointment scheduling with SMS notifications
- `reschedule-appointment.php` - Handles appointment rescheduling with SMS notifications
- `cancel-appointment.php` - Handles appointment cancellation with SMS notifications
- `complete-appointment.php` - Handles appointment completion with SMS notifications
- `complete-payment-appointment.php` - Handles payment appointment completion with SMS notifications
- `migrations/add_payment_allowed_status.sql` - Database migration

## UI Changes

### Admin View (applications.php)
- "Schedule Payment Appointment" button disappears after appointment is scheduled
- "Allow Payment" button appears for applications with scheduled payment appointments
- **Button is only enabled on the scheduled appointment date**
- Button logic:
  ```php
  if (!$app['payment_appointment_id'] || $app['payment_appointment_status'] !== 'scheduled') {
      // Show "Schedule Payment Appointment" button
  } else {
      if (appointment_date == today) {
          // Show enabled "Allow Payment" button
      } else {
          // Show disabled button with appointment date info
      }
  }
  ```

### Resident View (my-applications.php)
- Pay button is disabled when payment appointment is scheduled but not yet allowed
- Pay button is enabled when payment appointment status is `payment_allowed`
- Button logic:
  ```php
  if ($app['payment_appointment_status'] === 'payment_allowed') {
      // Show enabled Pay button
  } elseif ($app['payment_appointment_status'] === 'scheduled') {
      // Show disabled Pay button with waiting message
  } else {
      // Show disabled Pay button with appointment required message
  }
  ```

## Benefits
1. **Controlled Payment Flow**: Admins have control over when residents can make payments
2. **Date-Based Security**: Payment can only be allowed on the scheduled appointment date
3. **Better Communication**: Clear status indicators for both admin and resident
4. **SMS Notifications**: Automatic SMS notifications for all appointment activities
5. **Audit Trail**: Complete history of payment scheduling and approval
6. **Flexibility**: Admins can schedule appointments but allow payment at their convenience

## SMS Notifications

The system now sends automatic SMS notifications for all appointment-related activities:

### Payment Appointments
- **Scheduled**: Resident notified of payment appointment with amount due
- **Allowed**: Resident notified that payment can now be made online
- **Completed**: Resident notified of payment receipt and processing start

### General Appointments
- **Scheduled**: Resident notified of appointment details
- **Rescheduled**: Resident notified of new appointment time
- **Cancelled**: Resident notified of cancellation with rescheduling instructions
- **Completed**: Resident notified of successful completion

### Notification Features
- Uses proper SMS API integration via `sms_functions.php`
- Respects user SMS notification preferences
- Includes detailed information in messages
- **Uses actual appointment dates** instead of hardcoded dates
- Logs all SMS activities for monitoring
- Fallback to email notifications if SMS fails

## Migration Instructions
Run the following SQL to update existing databases:
```sql
ALTER TABLE appointments 
MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled', 'payment_allowed') DEFAULT 'scheduled';

ALTER TABLE appointments 
MODIFY COLUMN appointment_type ENUM('verification', 'pickup', 'interview', 'payment') NOT NULL;
``` 