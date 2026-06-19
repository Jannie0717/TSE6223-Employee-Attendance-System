-- Employee Attendance System Database
-- Import this file in phpMyAdmin using XAMPP.

CREATE DATABASE IF NOT EXISTS employee_attendance_system;
USE employee_attendance_system;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS attendance_record;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS user_account;
DROP TABLE IF EXISTS employee_profile;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE employee_profile (
    empID VARCHAR(10) PRIMARY KEY,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    department VARCHAR(50) NOT NULL,
    jobPosition VARCHAR(30) NOT NULL,
    contactNum VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    profileImage VARCHAR(255) NULL,
    employmentStatus ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    dateJoined DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admin (
    adminID VARCHAR(10) PRIMARY KEY,
    empID VARCHAR(10) NOT NULL UNIQUE,
    accessLevel VARCHAR(20) NOT NULL DEFAULT 'HR Admin',
    overridePermission BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_admin_employee FOREIGN KEY (empID) REFERENCES employee_profile(empID)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_account (
    loginID VARCHAR(10) PRIMARY KEY,
    empID VARCHAR(10) NOT NULL,
    adminID VARCHAR(10) NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    role ENUM('Employee','Admin') NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_employee FOREIGN KEY (empID) REFERENCES employee_profile(empID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_admin FOREIGN KEY (adminID) REFERENCES admin(adminID)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attendance_record (
    recordID INT AUTO_INCREMENT PRIMARY KEY,
    empID VARCHAR(10) NOT NULL,
    attendanceDate DATE NOT NULL,
    clockInTime TIME NULL,
    clockOutTime TIME NULL,
    lateStatus ENUM('Late','Not Late') NOT NULL DEFAULT 'Not Late',
    attendanceStatus ENUM('Pending','On Time','Late','Absent') NOT NULL DEFAULT 'Pending',
    updatedByAdmin VARCHAR(10) NULL,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_employee_date (empID, attendanceDate),
    CONSTRAINT fk_attendance_employee FOREIGN KEY (empID) REFERENCES employee_profile(empID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_attendance_admin FOREIGN KEY (updatedByAdmin) REFERENCES admin(adminID)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo employees
INSERT INTO employee_profile (empID, firstName, lastName, department, jobPosition, contactNum, email, employmentStatus, dateJoined) VALUES
('EMP00123','Jamie','Ng','Software Development','Software Developer','+6012 345 6789','employee@jolspintech.com','ACTIVE','2024-03-15'),
('EMP00124','Delvin','Ting','UI/UX Design','UI/UX Designer','+6012 333 4444','delvin@jolspintech.com','ACTIVE','2024-04-20'),
('EMP00125','Zhi Xuan','Lau','Project Management','Project Coordinator','+6012 555 6666','zhixuan@jolspintech.com','ACTIVE','2023-11-02'),
('EMP00126','Shi Hong','Lim','Software Development','Backend Developer','+6012 777 8888','shihong@jolspintech.com','ACTIVE','2024-06-01'),
('EMP00127','Maya','Tan','Human Resource','HR Secretary','+6011 234 5678','admin@jolspintech.com','ACTIVE','2020-03-15');

INSERT INTO admin (adminID, empID, accessLevel, overridePermission) VALUES
('ADM00123','EMP00127','HR Admin',TRUE);

-- All demo accounts use password: password123
INSERT INTO user_account (loginID, empID, adminID, email, passwordHash, role) VALUES
('EMP00123','EMP00123',NULL,'employee@jolspintech.com','$2y$12$0.fizfzsL9MIiNDGimDinOkB1iQ3cv75MfngAmg3hZflD0/q1HIwe','Employee'),
('EMP00124','EMP00124',NULL,'delvin@jolspintech.com','$2y$12$0.fizfzsL9MIiNDGimDinOkB1iQ3cv75MfngAmg3hZflD0/q1HIwe','Employee'),
('EMP00125','EMP00125',NULL,'zhixuan@jolspintech.com','$2y$12$0.fizfzsL9MIiNDGimDinOkB1iQ3cv75MfngAmg3hZflD0/q1HIwe','Employee'),
('EMP00126','EMP00126',NULL,'shihong@jolspintech.com','$2y$12$0.fizfzsL9MIiNDGimDinOkB1iQ3cv75MfngAmg3hZflD0/q1HIwe','Employee'),
('ADM00123','EMP00127','ADM00123','admin@jolspintech.com','$2y$12$0.fizfzsL9MIiNDGimDinOkB1iQ3cv75MfngAmg3hZflD0/q1HIwe','Admin');

-- Demo attendance records
INSERT INTO attendance_record (empID, attendanceDate, clockInTime, clockOutTime, lateStatus, attendanceStatus) VALUES
('EMP00123','2026-05-18','08:57:00','18:04:00','Not Late','On Time'),
('EMP00123','2026-05-19','09:20:00','18:10:00','Late','Late'),
('EMP00123','2026-05-20','08:57:00',NULL,'Not Late','Pending'),
('EMP00124','2026-05-20','09:08:00',NULL,'Not Late','Pending'),
('EMP00125','2026-05-20','09:30:00',NULL,'Late','Pending'),
('EMP00126','2026-05-20','08:50:00',NULL,'Not Late','Pending'),
('EMP00127','2026-05-20','09:08:00',NULL,'Not Late','Pending');
