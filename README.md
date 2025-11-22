# BM-SCaPIS (Barangay Malangit Smart Clearance and Permit Issuance System)

## Overview

BM-SCaPIS is a modern, mobile-friendly Progressive Web Application (PWA) designed to streamline the process of applying for and managing barangay clearances and permits in Barangay Malangit. The system provides an efficient, secure, and user-friendly platform for residents to access government services online.

## Features

### Core Functionality
- **Online Registration & Approval System**: Two-tier approval process (Purok Leader + Admin)
- **Document Application Management**: Apply for various barangay documents online
- **Real-time Application Tracking**: Monitor application progress with live updates
- **SMS Notifications**: Automated SMS alerts using PhilSMS API integration
- **Ringtone Notifications**: Audio alerts for system administrators
- **Appointment Scheduling**: Book verification and pickup appointments
- **Comprehensive Reporting**: Generate detailed reports and statistics

### User Roles
1. **Residents**: Apply for documents, track applications, manage profile
2. **Purok Leaders**: Verify resident eligibility, review applications in their purok
3. **Admin**: System-wide management, final approval authority, generate reports

### Document Types Supported
- Barangay Clearance
- Certificate of Residency
- Certificate of Indigency
- Business Permit
- Building Permit
- Community Tax (Cedula)

## Technical Specifications

### Technology Stack
- **Backend**: PHP (non-OOP approach as requested)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5.3.2
- **Icons**: Bootstrap Icons
- **Additional Libraries**:

  - DataTables for data management
  - SweetAlert2 for user interactions
  - Chart.js for statistics visualization

### PWA Features
- Offline capability with service worker
- App-like experience on mobile devices
- Push notifications support
- Add to home screen functionality

### Security Features
- CSRF protection on all forms
- File upload validation and sanitization
- Session management
- SQL injection prevention using prepared statements
- XSS protection with proper data sanitization

## Installation Instructions

### Prerequisites
- XAMPP or similar web server with PHP 7.4+ and MySQL
- Web browser with modern JavaScript support

### Setup Steps

1. **Extract Files**
   ```
   Extract all files to: C:\xampp\htdocs\muhai_malangit\
   ```

2. **Database Setup**
   - Start XAMPP (Apache and MySQL)
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create new database named `bm_scapis`
   - Import the database.sql file

3. **Configuration**
   - Open `config.php`
   - Update database credentials if needed (default: root/no password)
   - Configure PhilSMS API key in the database or via admin panel

4. **File Permissions**
   - Ensure `uploads/` directory is writable
   - Set appropriate permissions for file uploads

5. **Access System**
   - Navigate to: http://localhost/muhai_malangit/
   - Default admin login: username: `admin001`, password: `admin123`

## System Workflow

### Registration Process
1. Resident fills out registration form with required documents
2. System generates unique username automatically
3. Purok Leader receives notification to verify residency
4. Admin receives notification for final approval
5. Resident receives SMS notification when approved
6. Resident can now log in and apply for documents

### Application Process
1. Approved resident logs in and selects document type
2. Fills out application form with purpose and supporting documents
3. System generates unique application number
4. Admin processes application and updates status
5. Resident receives SMS notifications at each status change
6. Appointment can be scheduled for verification/pickup
7. Document is marked as completed upon pickup

## Default User Accounts

### Admin Account
- **Username**: admin001
- **Password**: admin123
- **Role**: Administrator

### Creating Additional Users
- Purok Leaders: Created by admin through the admin panel
- Residents: Self-registration with approval process

## File Structure

```
muhai_malangit/
├── config.php              # Database and system configuration
├── index.php               # Homepage
├── login.php               # User login
├── register.php            # User registration
├── logout.php              # Logout handler
├── dashboard.php           # Main dashboard
├── header.php              # HTML head section
├── navbar.php              # Navigation bar
├── footer.php              # Footer section
├── scripts.php             # JavaScript libraries
├── database.sql            # Database schema
├── manifest.json           # PWA manifest
├── sw.js                   # Service worker
├── ajax/                   # AJAX endpoints
│   ├── mark-notification-read.php
│   ├── mark-all-notifications-read.php
│   └── get-notification-count.php
├── assets/                 # Static assets
│   ├── icons/              # PWA icons
│   ├── images/             # System images
│   └── sounds/             # Notification sounds
└── uploads/                # User uploads
    └── profiles/           # Profile pictures and IDs
```

## API Integration

### PhilSMS Configuration
1. Obtain API key from PhilSMS provider
2. Update system configuration:
   ```sql
   UPDATE system_config SET config_value = 'your_api_key' WHERE config_key = 'philsms_api_key';
   ```

## Reports Available

### Application Reports
- List of approved and pending applications by date, purpose, and applicant type
- Summary of issued clearances and permits
- Daily, weekly, and monthly transaction logs
- Number of applications per purok/sector

### Resident Reports
- List of residents per purok
- Demographic breakdown by gender
- Age distribution statistics

### Statistical Reports
- Application trends per purok
- Gender-based statistics
- Age group analysis
- System usage metrics

## System Evaluation Criteria

The system addresses the following evaluation aspects:

1. **Functionality**: Complete feature set as per requirements
2. **Usability**: Intuitive interface with responsive design
3. **Accuracy**: Reliable data processing and storage
4. **Acceptability**: User-friendly for all stakeholder groups
5. **Security**: Robust security measures and data protection

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check XAMPP MySQL service is running
   - Verify database credentials in config.php
   - Ensure database exists and is properly imported

2. **File Upload Issues**
   - Check uploads directory permissions
   - Verify PHP upload limits in php.ini
   - Ensure file types are allowed

3. **SMS Not Working**
   - Verify PhilSMS API key configuration
   - Check internet connection
   - Review SMS logs in database

4. **PWA Not Installing**
   - Ensure HTTPS or localhost
   - Check manifest.json validity
   - Verify service worker registration

## Support and Maintenance

### Regular Maintenance Tasks
- Database backup and optimization
- Log file cleanup
- Security updates
- Performance monitoring

### Backup Recommendations
- Daily database backups
- Regular file system backups
- Configuration file versioning

## Development Notes

### Code Standards
- Non-OOP PHP approach as requested
- Prepared statements for database queries
- Consistent naming conventions
- Comprehensive commenting

### Future Enhancements
- Mobile app development
- Advanced reporting features
- Integration with other government systems
- Enhanced notification systems

## Contact Information

For technical support or system inquiries:
- Email: support@bm-scapis.com
- Phone: +63 123 456 7890
- Office: Barangay Malangit Hall

---

**Version**: 1.0.0  
**Last Updated**: July 2025  
**Developed by**: BM-SCaPIS Development Team
