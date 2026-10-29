<?php
/**
 * CampusPark - Modern Dark Header Template
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? APP_NAME . ' – ' . APP_TAGLINE;
$activeNav = $activeNav ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CampusPark is an intelligent digital college parking management system with modern real-time gate attendance, vehicle security, and paperless registers.">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- CampusPark Modern Helios Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Sleek Public Top Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-cp sticky-top no-print">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand" href="index.php">
            <span class="brand-logo-mark"><i class="bi bi-cpu-fill"></i></span>
            <span>CampusPark</span>
        </a>
        
        <button class="navbar-toggler border-0 shadow-none text-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($activeNav === 'home') ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard Overview
                    </a>
                </li>
                <?php if (is_student_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav === 'student_dash') ? 'active' : '' ?>" href="student_dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Student Portal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav === 'daily_status') ? 'active' : '' ?>" href="student_daily_status.php">
                            <i class="bi bi-calendar-check me-1"></i> Today's Status
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav === 'vehicles') ? 'active' : '' ?>" href="student_vehicles.php">
                            <i class="bi bi-car-front me-1"></i> My Vehicles
                        </a>
                    </li>
                <?php elseif (is_admin_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav === 'admin_dash') ? 'active' : '' ?>" href="admin_dashboard.php">
                            <i class="bi bi-shield-check me-1"></i> Security Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav === 'admin_register') ? 'active' : '' ?>" href="admin_parking_records.php">
                            <i class="bi bi-journal-bookmark me-1"></i> Digital Register Book
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav === 'admin_students') ? 'active' : '' ?>" href="admin_students_vehicles.php">
                            <i class="bi bi-people me-1"></i> Students Directory
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center gap-2">
                <?php if (is_student_logged_in()): ?>
                    <a href="student_dashboard.php" class="btn-helios-pill-sm">
                        <i class="bi bi-person-circle"></i> Student Portal
                    </a>
                    <a href="logout.php" class="btn-helios-pill-sm text-danger border-danger border-opacity-25" title="Sign Out">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                <?php elseif (is_admin_logged_in()): ?>
                    <a href="admin_dashboard.php" class="btn-helios-pill-sm border-warning border-opacity-50 text-warning">
                        <i class="bi bi-shield-lock-fill"></i> Security Staff
                    </a>
                    <a href="logout.php" class="btn-helios-pill-sm text-danger border-danger border-opacity-25" title="Sign Out">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <a href="student_login.php" class="btn-helios-pill-sm">
                        <i class="bi bi-person-fill"></i> Student Sign In
                    </a>
                    <a href="admin_login.php" class="btn-helios-pill-sm">
                        <i class="bi bi-shield-lock"></i> Admin Portal
                    </a>
                    <a href="student_register.php" class="btn-helios-primary py-1 px-3" style="font-size: 0.8rem;">
                        <i class="bi bi-person-plus-fill"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Global Flash Messages Notification -->
<div class="container-fluid px-lg-4 pt-2 no-print">
    <?php display_flash(); ?>
</div>
