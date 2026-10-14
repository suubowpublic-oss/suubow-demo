# Suubow Document Management System (PHP + MySQL)

A starter PHP/MySQL application with:
- Login/logout and password hashing
- Dashboard cards for document categories and totals
- PDF document upload and storage
- View document details and preview the stored PDF
- Edit document details and optionally replace the PDF file
- Delete documents (with confirmation and CSRF protection)
- Download the original PDF as an attachment
- Print PDF through the browser PDF viewer
- Person details: full name, mobile, address, ID number
- Reference number and document category
- Search by mobile number, REF.No., name, or ID number
- Weekly, monthly, and yearly reports
- Staff attendance check-in/check-out

## Requirements
- PHP 8.1+
- MySQL 5.7+ / MariaDB
- Apache/Nginx (XAMPP works locally)

## Installation
1. Copy the `suubow_document_system` folder into your web server directory (e.g. `htdocs`).
2. Create a MySQL database named `suubow_documents`.
3. Import `database.sql` using phpMyAdmin or the MySQL command line.
4. Edit database credentials in `config.php`.
5. Make `uploads/` writable by the web server.
6. Visit `http://localhost/suubow_document_system/`.
7. Default login: **admin** / **ChangeMe123!**
8. Change the default password immediately after first login. For a production deployment, remove the default account and create a new admin password.

## Security notes
This is a starter implementation, not a security-audited production system. Use HTTPS, strong unique passwords, backups, restricted file permissions, and server-side access controls before real-world deployment. PDF uploads are validated by extension and MIME type, but production deployments should also scan uploads and store files outside the public web root.


## Cusboonaysiintan
- Noocyada cusub: Caddeyn Damaanad iyo Caddeyn Hibo Dhul Deegaan / Beer.
- Magaca dashboard-ka: Dr. Mohamed Yusuf Ali (Caddow), salaanta kore waxay muujinaysaa magacaas.


## V3 updates
- Admin-only User Control page to create and delete staff accounts. Passwords are stored using PHP password hashing.
- Staff accounts can create and view/edit/delete/download/print only their own uploaded documents; admin can manage all documents.
- Attendance now includes employee registration (name, mobile, address, education level) and admin check-in/check-out with server timestamps.
- Navigation and greeting use the signed-in user's name.

### Existing database upgrade
If upgrading an existing installation, back up your database first. Run this SQL once in phpMyAdmin before using Attendance V3:
```sql
CREATE TABLE IF NOT EXISTS employees (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(180) NOT NULL, mobile VARCHAR(40) NOT NULL, address VARCHAR(255) NOT NULL, education ENUM('Jaamacad','Dugsi sare','Dugsi dhexe','Dugsi hoose','Waxbarasho kale') NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
ALTER TABLE attendance ADD COLUMN employee_id INT UNSIGNED NULL;
ALTER TABLE attendance ADD CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL;
```
Do not run the ALTER statements repeatedly; the `employee_id` column and foreign key should be added only once.


V7 update: Admin-only Change Password page added to the navigation. Admin must enter the current password, provide a new password (minimum 8 characters), and confirm it. The new password is saved using PHP password_hash. No database schema changes are required.

## V8 additions
- Employee roles and employee photos.
- Verification Document checks REF.NS/mobile and lists gaps in NS reference sequences.
- User profile picture upload; login uses supplied portrait and shows a loading spinner.
- New pages: verification.php and profile.php. These add the profile_picture column on first visit. Employee columns migrate automatically in Employee Control.


V9 update: Removed the user Profile Picture page and removed the portrait image from the login page. Employee Photo in Employee Control remains available.
