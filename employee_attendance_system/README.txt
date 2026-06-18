Employee Attendance System - XAMPP Setup Guide

Software Type: Web Application
Company: JolSpinTech Solutions Sdn. Bhd.
Technology: PHP, MySQL, HTML, CSS, JavaScript
Database Tool: phpMyAdmin through XAMPP

========================================
1. INSTALLATION STEPS
========================================

1. Install and open XAMPP.
2. Start Apache and MySQL.
3. Copy the folder named employee_attendance_system into:
   C:\xampp\htdocs\

4. Open phpMyAdmin in your browser:
   http://localhost/phpmyadmin

5. Import the database:
   - Click Import
   - Choose database.sql from this project folder
   - Click Go

6. Open the system in browser:
   http://localhost/employee_attendance_system/

========================================
2. DEMO LOGIN ACCOUNTS
========================================

Employee account:
Email: employee@jolspintech.com
Password: password123
Role: Employee

Admin account:
Email: admin@jolspintech.com
Password: password123
Role: Admin

Important: On the login page, select the correct role before logging in.

========================================
3. MAIN FEATURES
========================================

Employee:
- Secure login and logout
- View dashboard
- Clock in attendance
- Clock out attendance
- View own attendance history
- View and edit profile
- Change password

Admin:
- Secure login and logout
- View admin dashboard
- Clock in and clock out attendance
- View profile
- Manage employee records
- View all attendance records
- Search and filter attendance records
- View late employees
- Generate simple attendance reports from Attendance Records
- Export attendance records as CSV/Excel
- Print attendance records or save as PDF through browser print
- Manually correct attendance records

========================================
4. DATABASE CONFIGURATION
========================================

Database connection file:
config/db.php

Default XAMPP setting:
Host: localhost
User: root
Password: empty
Database: employee_attendance_system

If your MySQL password is different, update config/db.php.

========================================
5. ATTENDANCE RULE USED
========================================

Official working hours: 9:00 AM - 6:00 PM
Late threshold: after 9:15 AM

Clock-in and clock-out time are recorded using server-side time.


Automatic status rule:
- After 9:15 AM, active employees who have not clocked in are temporarily marked as Late.
- After 6:00 PM, active employees who still have not clocked in are marked as Absent.


Latest changes included:
- Removed the Edit Profile option from My Profile.
- Added profile picture upload in My Profile. Uploaded images are stored in uploads/profiles/.
- Export PDF in Attendance Records now prints only the attendance table, not the whole web page.
- After 6:00 PM, employees who did not clock in are automatically marked as Absent and cannot clock in or clock out for that day.
- Profile image field added to database.sql as employee_profile.profileImage.
