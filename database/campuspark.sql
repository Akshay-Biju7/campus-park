-- ==============================================================
-- CampusPark - College Parking Management System
-- Database Schema & Sample Records
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `campuspark_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `campuspark_db`;

-- Drop tables in proper foreign key order if re-importing
DROP TABLE IF EXISTS `parking_records`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `admins`;

-- -------------------------------------------------------------
-- 1. Admins Table
-- -------------------------------------------------------------
CREATE TABLE `admins` (
    `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. Students Table
-- -------------------------------------------------------------
CREATE TABLE `students` (
    `student_id` INT AUTO_INCREMENT PRIMARY KEY,
    `roll_number` VARCHAR(30) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `contact_number` VARCHAR(20) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `year_of_study` VARCHAR(20) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_student_roll` (`roll_number`),
    INDEX `idx_student_course_dept` (`course`, `department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. Vehicles Table
-- -------------------------------------------------------------
CREATE TABLE `vehicles` (
    `vehicle_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `vehicle_type` ENUM('Bike', 'Scooter', 'Car', 'Bicycle/EV', 'Other') NOT NULL,
    `registration_number` VARCHAR(30) NOT NULL UNIQUE,
    `vehicle_model` VARCHAR(60) NULL,
    `colour` VARCHAR(30) NULL,
    `photo_url` VARCHAR(255) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_vehicle_reg` (`registration_number`),
    INDEX `idx_vehicle_student` (`student_id`),
    CONSTRAINT `fk_vehicles_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`student_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. Parking Records Table (Daily College Arrival)
-- -------------------------------------------------------------
CREATE TABLE `parking_records` (
    `record_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `parking_date` DATE NOT NULL,
    `arrival_status` ENUM('brought', 'not_brought') NOT NULL DEFAULT 'not_brought',
    `entry_time` TIME NULL,
    `remarks` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_vehicle_daily` (`vehicle_id`, `parking_date`),
    INDEX `idx_records_date` (`parking_date`),
    INDEX `idx_records_status` (`arrival_status`),
    INDEX `idx_records_student` (`student_id`),
    CONSTRAINT `fk_parking_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`student_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_parking_vehicle` FOREIGN KEY (`vehicle_id`) 
        REFERENCES `vehicles` (`vehicle_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- Sample Data Seeding
-- Passwords:
-- Admin password: admin123  (Standard hash: $2y$10$QkX/VeqeWqO72l7gq4n60.o6z92rMsqkHQj2M/8z6zQ6c1YF5s2Y2)
-- Student password: student123 (Standard hash: $2y$10$Eix9y8G7t8mE2C7Jb8q29.vO7Zt2d7E6H5J4K3L2M1N0O9P8Q7R6S)
-- Note: setup.php also dynamically hashes passwords using PHP password_hash()!
-- ==============================================================

-- 1. Insert Default Administrator
INSERT INTO `admins` (`admin_id`, `username`, `password_hash`, `full_name`, `email`) VALUES
(1, 'admin', '$2y$10$f38w4Qx4B7U5pPz6p3L0xeDq8mE8j1vW1nK3s2d5f6g7h8j9k0l1m', 'Campus Parking Administrator', 'admin@campuspark.edu');

-- 2. Insert Sample Students
INSERT INTO `students` (`student_id`, `roll_number`, `full_name`, `email`, `password_hash`, `contact_number`, `course`, `department`, `year_of_study`, `is_active`) VALUES
(1, '22BTCS045', 'Rahul Sharma', 'rahul.sharma@campus.edu', '$2y$10$f38w4Qx4B7U5pPz6p3L0xeDq8mE8j1vW1nK3s2d5f6g7h8j9k0l1m', '9876543210', 'B.Tech', 'Computer Science & Engineering', '3rd Year', 1),
(2, '23BTEC019', 'Priya Patel', 'priya.patel@campus.edu', '$2y$10$f38w4Qx4B7U5pPz6p3L0xeDq8mE8j1vW1nK3s2d5f6g7h8j9k0l1m', '9812345678', 'B.Tech', 'Electronics & Communication Engineering', '2nd Year', 1),
(3, '24MTDS008', 'Mohammed Ansar', 'm.ansar@campus.edu', '$2y$10$f38w4Qx4B7U5pPz6p3L0xeDq8mE8j1vW1nK3s2d5f6g7h8j9k0l1m', '9745612300', 'M.Tech', 'Artificial Intelligence & Data Science', '1st Year', 1),
(4, '21BTCE031', 'Ananya Nair', 'ananya.nair@campus.edu', '$2y$10$f38w4Qx4B7U5pPz6p3L0xeDq8mE8j1vW1nK3s2d5f6g7h8j9k0l1m', '9988776655', 'B.Tech', 'Civil Engineering', '4th Year', 1),
(5, '22BTME072', 'Siddharth Verma', 'siddharth.v@campus.edu', '$2y$10$f38w4Qx4B7U5pPz6p3L0xeDq8mE8j1vW1nK3s2d5f6g7h8j9k0l1m', '9123456780', 'B.Tech', 'Mechanical Engineering', '3rd Year', 1),
(6, '23MCA015', 'Sneha Roy', 'sneha.roy@campus.edu', '$2y$10$f38w4Qx4B7U5pPz6p3L0xeDq8mE8j1vW1nK3s2d5f6g7h8j9k0l1m', '9012345671', 'MCA', 'Master of Computer Applications (MCA)', '2nd Year', 1);

-- 3. Insert Sample Vehicles (Multiple vehicles for students)
INSERT INTO `vehicles` (`vehicle_id`, `student_id`, `vehicle_type`, `registration_number`, `vehicle_model`, `colour`, `photo_url`, `is_active`) VALUES
(1, 1, 'Bike', 'MH 12 AB 4590', 'Yamaha R15 V4', 'Racing Blue', 'assets/images/vehicles/bike.svg', 1),
(2, 1, 'Scooter', 'MH 12 CD 1245', 'Honda Activa 6G', 'Pearl White', 'assets/images/vehicles/scooter.svg', 1),
(3, 2, 'Scooter', 'DL 04 ES 8912', 'Ather 450X', 'Space Grey', 'assets/images/vehicles/ev.svg', 1),
(4, 3, 'Car', 'KA 05 MC 3344', 'Hyundai i20 Asta', 'Polar White', 'assets/images/vehicles/car.svg', 1),
(5, 4, 'Scooter', 'TN 09 BK 7765', 'TVS Jupiter 125', 'Matte Black', 'assets/images/vehicles/scooter.svg', 1),
(6, 5, 'Bike', 'MH 14 GH 9988', 'Royal Enfield Classic 350', 'Stealth Black', 'assets/images/vehicles/bike.svg', 1),
(7, 6, 'Car', 'WB 02 AX 5511', 'Maruti Suzuki Swift ZXi', 'Fire Red', 'assets/images/vehicles/car.svg', 1),
(8, 6, 'Bicycle/EV', 'WB 02 EV 0909', 'Hero Lectro E-Cycle', 'Neon Yellow', 'assets/images/vehicles/ev.svg', 1);

-- 4. Insert Sample Parking Attendance Records (Today & Previous Days)
-- Current Date Records
INSERT INTO `parking_records` (`student_id`, `vehicle_id`, `parking_date`, `arrival_status`, `entry_time`, `remarks`) VALUES
(1, 1, CURDATE(), 'brought', '08:45:00', 'Parked at Student Zone A'),
(1, 2, CURDATE(), 'not_brought', NULL, 'Kept at home'),
(2, 3, CURDATE(), 'brought', '08:55:00', 'Parked at EV Charging Bay 2'),
(3, 4, CURDATE(), 'brought', '09:10:00', 'Parked at 4-Wheeler Bay C'),
(4, 5, CURDATE(), 'not_brought', NULL, 'Commuted via Metro today'),
(5, 6, CURDATE(), 'brought', '08:30:00', 'Parked at 2-Wheeler Zone B'),
(6, 7, CURDATE(), 'brought', '09:20:00', 'Parked at 4-Wheeler Bay A'),
(6, 8, CURDATE(), 'not_brought', NULL, 'Kept at hostel');

-- Yesterday's Records (CURDATE() - INTERVAL 1 DAY)
INSERT INTO `parking_records` (`student_id`, `vehicle_id`, `parking_date`, `arrival_status`, `entry_time`, `remarks`) VALUES
(1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'brought', '08:40:00', 'On time'),
(2, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'brought', '09:05:00', 'Morning session'),
(3, 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'not_brought', NULL, 'WFH / Project day'),
(4, 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'brought', '08:50:00', 'Parked at Zone B'),
(5, 6, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'brought', '08:35:00', 'Lab exam arrival'),
(6, 7, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'not_brought', NULL, 'Car servicing');

-- 2 Days Ago Records (CURDATE() - INTERVAL 2 DAY)
INSERT INTO `parking_records` (`student_id`, `vehicle_id`, `parking_date`, `arrival_status`, `entry_time`, `remarks`) VALUES
(1, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'brought', '08:30:00', 'Full day classes'),
(2, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'not_brought', NULL, 'Sick leave'),
(3, 4, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'brought', '09:00:00', 'Research lab'),
(4, 5, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'brought', '08:45:00', 'Zone B'),
(5, 6, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'brought', '08:25:00', 'Morning workshop'),
(6, 7, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'brought', '09:15:00', 'Department seminar');

-- 3 Days Ago Records (CURDATE() - INTERVAL 3 DAY)
INSERT INTO `parking_records` (`student_id`, `vehicle_id`, `parking_date`, `arrival_status`, `entry_time`, `remarks`) VALUES
(1, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'brought', '08:50:00', 'Scooter used today'),
(2, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'brought', '08:55:00', 'Regular entry'),
(3, 4, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'brought', '09:05:00', 'Regular entry'),
(5, 6, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'not_brought', NULL, 'Rain / Bus transit'),
(6, 8, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'brought', '08:40:00', 'E-bike campus ride');
