<?php
// Database connection for XAMPP/phpMyAdmin
// Default XAMPP MySQL user is usually root with empty password.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'employee_attendance_system';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Kuala_Lumpur');
?>
