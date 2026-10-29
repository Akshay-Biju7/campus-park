<?php
/**
 * CampusPark - Database Setup & Auto-Installer Wizard
 * Modern Dark Helios Layout: Initializes tables, constraints, and seeds rich demo data with valid password hashes.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$message = null;
$error = null;
$isInstalled = false;

// Check existing status
$rawPdo = Database::getRawConnection();
if ($rawPdo) {
    try {
        $stmt = $rawPdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
        if ($stmt->fetch()) {
            $dbPdo = Database::getConnection();
            if ($dbPdo) {
                $chk = $dbPdo->query("SHOW TABLES LIKE 'students'");
                if ($chk->fetch()) {
                    $isInstalled = true;
                }
            }
        }
    } catch (Exception $e) {
        // ignore
    }
}

// Handle Form Submission: Run Installation / Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    if (!$rawPdo) {
        $error = "Cannot connect to MySQL server. Ensure Apache & MySQL are running in XAMPP.";
    } else {
        try {
            // 1. Create database
            $rawPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $rawPdo->exec("USE `" . DB_NAME . "`");

            // 2. Drop existing tables safely
            $rawPdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $rawPdo->exec("DROP TABLE IF EXISTS `parking_records`");
            $rawPdo->exec("DROP TABLE IF EXISTS `vehicles`");
            $rawPdo->exec("DROP TABLE IF EXISTS `students`");
            $rawPdo->exec("DROP TABLE IF EXISTS `admins`");
            $rawPdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // 3. Create tables
            $rawPdo->exec("
                CREATE TABLE `admins` (
                    `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(50) NOT NULL UNIQUE,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `full_name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(100) NOT NULL UNIQUE,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $rawPdo->exec("
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
            ");

            $rawPdo->exec("
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
            ");

            $rawPdo->exec("
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
            ");

            // 4. Seed Admin
            $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
            $stmtAdmin = $rawPdo->prepare("INSERT INTO `admins` (`username`, `password_hash`, `full_name`, `email`) VALUES (?, ?, ?, ?)");
            $stmtAdmin->execute(['admin', $adminPass, 'Chief Campus Security Officer', 'admin@campuspark.edu']);

            // 5. Seed Students
            $studentPass = password_hash('student123', PASSWORD_DEFAULT);
            $studentsData = [
                ['22BTCS045', 'Rahul Sharma', 'rahul.sharma@campus.edu', $studentPass, '9876543210', 'B.Tech', 'Computer Science & Engineering', '3rd Year'],
                ['23BTEC019', 'Priya Patel', 'priya.patel@campus.edu', $studentPass, '9811223344', 'B.Tech', 'Electronics & Comm. Engg', '2nd Year'],
                ['24MTDS008', 'Mohammed Ansar', 'ansar.m@campus.edu', $studentPass, '9822334455', 'M.Tech', 'AI & Data Science', '1st Year'],
                ['21BTCE031', 'Ananya Nair', 'ananya.nair@campus.edu', $studentPass, '9833445566', 'B.Tech', 'Civil Engineering', '4th Year'],
                ['22BTME072', 'Siddharth Verma', 'siddharth.v@campus.edu', $studentPass, '9844556677', 'B.Tech', 'Mechanical Engineering', '3rd Year'],
                ['23MCA015', 'Sneha Roy', 'sneha.roy@campus.edu', $studentPass, '9855667788', 'MCA', 'Master of Computer Applications', '2nd Year'],
            ];

            $stmtStd = $rawPdo->prepare("INSERT INTO `students` (`roll_number`, `full_name`, `email`, `password_hash`, `contact_number`, `course`, `department`, `year_of_study`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($studentsData as $st) {
                $stmtStd->execute($st);
            }

            // 6. Seed Vehicles
            $vehiclesData = [
                [1, 'Bike', 'MH 12 AB 4590', 'Yamaha R15 V4', 'Metallic Blue', 'assets/images/vehicles/bike.svg'],
                [1, 'Scooter', 'MH 12 CD 1245', 'TVS Jupiter 125', 'Titanium Grey', 'assets/images/vehicles/scooter.svg'],
                [2, 'Scooter', 'DL 04 ES 8912', 'Honda Activa 6G', 'Pearl White', 'assets/images/vehicles/scooter.svg'],
                [3, 'Car', 'KA 05 MC 3344', 'Hyundai i20', 'Fiery Red', 'assets/images/vehicles/car.svg'],
                [4, 'Scooter', 'TN 09 BK 7765', 'Suzuki Access 125', 'Matte Black', 'assets/images/vehicles/scooter.svg'],
                [5, 'Bike', 'MH 14 GH 9988', 'Royal Enfield Classic 350', 'Halcyon Green', 'assets/images/vehicles/bike.svg'],
                [6, 'Car', 'WB 02 AX 5511', 'Maruti Suzuki Swift', 'Silver Metallic', 'assets/images/vehicles/car.svg'],
                [6, 'Bicycle/EV', 'WB 02 EV 0909', 'Ather 450X Gen 3', 'Space Grey', 'assets/images/vehicles/ev.svg'],
            ];

            $stmtVeh = $rawPdo->prepare("INSERT INTO `vehicles` (`student_id`, `vehicle_type`, `registration_number`, `vehicle_model`, `colour`, `photo_url`) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($vehiclesData as $vh) {
                $stmtVeh->execute($vh);
            }

            // 7. Seed Parking Records
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
            $threeDaysAgo = date('Y-m-d', strtotime('-3 days'));

            $recordsData = [
                // Today
                [1, 1, $today, 'brought', '08:45:00', 'Parked at Bay A-14'],
                [2, 3, $today, 'brought', '08:52:00', 'Regular check-in'],
                [3, 4, $today, 'brought', '09:10:00', 'Car parking Level 1'],
                [4, 5, $today, 'not_brought', null, 'Using Metro today'],
                [5, 6, $today, 'brought', '08:35:00', 'Bike stand zone 2'],
                [6, 7, $today, 'not_brought', null, 'Public transit'],
                [6, 8, $today, 'brought', '09:25:00', 'EV charging bay 03'],

                // Yesterday
                [1, 1, $yesterday, 'brought', '08:40:00', 'Regular entry'],
                [2, 3, $yesterday, 'not_brought', null, 'Left at residence'],
                [3, 4, $yesterday, 'brought', '09:15:00', 'Car zone'],
                [4, 5, $yesterday, 'brought', '08:50:00', 'Regular entry'],
                [5, 6, $yesterday, 'brought', '08:45:00', 'Regular entry'],
                [6, 7, $yesterday, 'brought', '09:00:00', 'Car pass'],

                // Past dates
                [1, 1, $twoDaysAgo, 'brought', '08:30:00', 'Morning lab entry'],
                [2, 3, $twoDaysAgo, 'brought', '08:45:00', 'Regular entry'],
                [3, 4, $twoDaysAgo, 'not_brought', null, 'Online project work'],
                [4, 5, $twoDaysAgo, 'brought', '08:55:00', 'Regular entry'],
                [1, 1, $threeDaysAgo, 'brought', '08:40:00', 'Regular entry'],
                [2, 3, $threeDaysAgo, 'brought', '08:55:00', 'Regular entry'],
                [3, 4, $threeDaysAgo, 'brought', '09:05:00', 'Regular entry'],
                [5, 6, $threeDaysAgo, 'not_brought', null, 'Rain / Bus transit'],
                [6, 8, $threeDaysAgo, 'brought', '08:40:00', 'E-bike campus ride']
            ];

            $stmtRec = $rawPdo->prepare("INSERT INTO `parking_records` (`student_id`, `vehicle_id`, `parking_date`, `arrival_status`, `entry_time`, `remarks`) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($recordsData as $rc) {
                $stmtRec->execute($rc);
            }

            $message = "Database `campuspark_db` initialized successfully! All tables created and demo data seeded.";
            $isInstalled = true;
        } catch (Exception $e) {
            $error = "Setup failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusPark – Database Setup Wizard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container py-5" style="max-width: 820px;">
    <div class="text-center mb-4">
        <div class="brand-logo-mark mx-auto mb-3" style="width: 54px; height: 54px; font-size: 1.7rem; border-radius: 16px;">
            <i class="bi bi-database-gear"></i>
        </div>
        <h2 class="fw-bold text-white mb-1">CampusPark Setup Wizard</h2>
        <p class="text-muted small">College Parking Management System &bull; Database Initializer & Demo Seeder</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success d-flex align-items-center shadow-sm mb-4" style="background: rgba(52, 211, 153, 0.15); border: 1px solid rgba(52, 211, 153, 0.3); color: #34d399;" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3 flex-shrink-0"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Installation Complete!</h6>
                <p class="mb-0 small"><?= htmlspecialchars($message) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center shadow-sm mb-4" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 flex-shrink-0"></i>
            <div>
                <h6 class="alert-heading fw-bold mb-1">Configuration Notice</h6>
                <p class="mb-0 small"><?= htmlspecialchars($error) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Database Info Card -->
    <div class="helios-card mb-4">
        <h5 class="card-title-sleek text-white mb-3"><i class="bi bi-sliders me-2 text-primary"></i> Environment Status</h5>
        <div class="row g-3 small">
            <div class="col-sm-6">
                <div class="p-3 rounded-3" style="background: #14151a; border: 1px solid var(--border-subtle);">
                    <span class="text-muted d-block" style="font-size: 0.72rem;">MySQL Host & Port</span>
                    <strong class="text-white"><?= DB_HOST ?> (Port 3306)</strong>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="p-3 rounded-3" style="background: #14151a; border: 1px solid var(--border-subtle);">
                    <span class="text-muted d-block" style="font-size: 0.72rem;">Database Name</span>
                    <strong class="text-white"><?= DB_NAME ?></strong>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="p-3 rounded-3" style="background: #14151a; border: 1px solid var(--border-subtle);">
                    <span class="text-muted d-block" style="font-size: 0.72rem;">MySQL User</span>
                    <strong class="text-white"><?= DB_USER ?></strong>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="p-3 rounded-3" style="background: #14151a; border: 1px solid var(--border-subtle);">
                    <span class="text-muted d-block" style="font-size: 0.72rem;">Current Status</span>
                    <?php if ($isInstalled): ?>
                        <span class="badge-brought"><i class="bi bi-check2-circle"></i> Database Installed & Active</span>
                    <?php else: ?>
                        <span class="badge-pending"><i class="bi bi-clock"></i> Not Initialized Yet</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form method="POST" class="mt-4 text-center">
            <button type="submit" name="run_setup" class="btn-glowing-pill" onclick="return confirm('This will initialize tables and seed demo student, vehicle, and arrival records. Proceed?');">
                <i class="bi bi-arrow-repeat me-1"></i> <?= $isInstalled ? 'Re-install / Reset Demo Data' : 'Initialize Database & Seed Sample Records' ?>
            </button>
        </form>
    </div>

    <!-- Demo Credentials & Quick Links -->
    <div class="helios-card mb-4">
        <h5 class="card-title-sleek text-white mb-3"><i class="bi bi-key-fill me-2 text-warning"></i> Ready-to-Test Demo Accounts</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 rounded-3 h-100" style="background: #14151a; border: 1px solid var(--border-subtle);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3);">Security Admin</span>
                    </div>
                    <ul class="list-unstyled small text-muted mb-3">
                        <li><strong>Username:</strong> <code class="text-white">admin</code></li>
                        <li><strong>Password:</strong> <code class="text-white">admin123</code></li>
                        <li><strong>Access:</strong> Digital register, gate control, exports</li>
                    </ul>
                    <a href="admin_login.php" class="btn-helios-outline w-100 py-1" style="font-size: 0.8rem;">
                        <i class="bi bi-shield-lock me-1"></i> Go to Admin Login
                    </a>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded-3 h-100" style="background: #14151a; border: 1px solid var(--border-subtle);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge" style="background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3);">Student Accounts</span>
                    </div>
                    <ul class="list-unstyled small text-muted mb-3">
                        <li><strong>ID 1:</strong> <code class="text-white">22BTCS045</code> (Rahul Sharma - B.Tech CSE)</li>
                        <li><strong>ID 2:</strong> <code class="text-white">23BTEC019</code> (Priya Patel - B.Tech ECE)</li>
                        <li><strong>Password:</strong> <code class="text-white">student123</code></li>
                    </ul>
                    <a href="student_login.php" class="btn-helios-primary w-100 py-1" style="font-size: 0.8rem;">
                        <i class="bi bi-person-check me-1"></i> Go to Student Login
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 pt-2">
            <a href="index.php" class="btn-helios-pill-sm">
                <i class="bi bi-house-door me-1"></i> Back to CampusPark Home
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
