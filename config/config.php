<?php
/**
 * CampusPark - College Parking Management System
 * Global Application Configuration
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting (display errors during development, log in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Application Information
define('APP_NAME', 'CampusPark');
define('APP_TAGLINE', 'College Parking Management System');
define('APP_VERSION', '1.0.0');

// Database Configuration (Default XAMPP credentials)
define('DB_HOST', 'localhost');
define('DB_NAME', 'campuspark_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Auto-detect Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = rtrim($scriptDir, '/');

// If in subdirectories (like includes or api), normalize to project root
if (basename($baseDir) === 'includes' || basename($baseDir) === 'config') {
    $baseDir = dirname($baseDir);
}
define('BASE_URL', $protocol . $host . ($baseDir === '/' ? '' : $baseDir));

// Departments list supported
$DEPARTMENTS = [
    'Computer Science & Engineering',
    'Electronics & Communication Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
    'Electrical & Electronics Engineering',
    'Artificial Intelligence & Data Science',
    'Information Technology',
    'Master of Computer Applications (MCA)',
    'Master of Business Administration (MBA)'
];

// Courses list supported
$COURSES = [
    'B.Tech',
    'M.Tech',
    'MCA',
    'BCA',
    'MBA',
    'B.Sc',
    'Diploma'
];

// Years of study
$YEARS = [
    '1st Year',
    '2nd Year',
    '3rd Year',
    '4th Year'
];

// Vehicle Types
$VEHICLE_TYPES = [
    'Bike',
    'Scooter',
    'Car',
    'Bicycle/EV',
    'Other'
];
