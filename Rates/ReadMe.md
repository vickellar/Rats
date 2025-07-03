# Rates Clearance System

A PHP-based web application for managing municipal rates clearance applications and processing.

## Features

- User Authentication System (Admin, Staff, Applicants)
- Property Management
- Rates Clearance Application Submission
- Application Processing Workflow
- Payment Tracking
- Document Management
- Reports Generation
- Dashboard with Analytics

## Installation

1. Clone the repository to your web server directory.
2. Create a MySQL database.
3. Configure database connection in `rates/Database/db.php`.
4. Run the setup script by visiting `setup.php` in your browser.
5. Log in with default admin credentials:
   - Username: admin
   - Password: password123

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Usage

### For Applicants
- Register an account.
- Add properties.
- Submit rates clearance applications.
- Track application status.
- Make payments.
- Download clearance certificates.

### For Staff/Admin
- Process applications.
- Update property information.
- Manage users.
- Generate reports.
- Configure system settings.

## Logging and Error Handling

- Database errors are logged to `rates/logfile/database_errors.log`.
- PHP errors are logged to `rates/logfile/php_error.log`.
- Application errors are logged to `rates/logfile/application_errors.log`.

## Notes

- The system uses prepared statements for database interactions to prevent SQL injection.
- Monthly fees and calculated bills insertion logic is handled in `rates/admin/fixed-insert-monthly-fees.php`.
- Ensure the database schema is up to date with the latest migration scripts in `rates/Database/`.

## License
