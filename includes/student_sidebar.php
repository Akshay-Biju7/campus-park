<?php
/**
 * CampusPark - Student Modern Dark Sidebar
 */
require_once __DIR__ . '/auth.php';
$student = get_logged_student();
$sidebarActive = $sidebarActive ?? 'dashboard';
?>
<aside class="helios-sidebar no-print" id="dashboardSidebar">
    <!-- Brand Header -->
    <a href="student_dashboard.php" class="sidebar-brand">
        <span class="brand-icon-glyph"><i class="bi bi-cpu-fill"></i></span>
        <span>CampusPark</span>
    </a>

    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <li class="menu-item">
            <a class="<?= ($sidebarActive === 'dashboard') ? 'active' : '' ?>" href="student_dashboard.php">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a class="<?= ($sidebarActive === 'daily_status') ? 'active' : '' ?>" href="student_daily_status.php">
                <i class="bi bi-calendar2-check"></i>
                <span>Today's Status</span>
            </a>
        </li>
        <li class="menu-item">
            <a class="<?= ($sidebarActive === 'vehicles') ? 'active' : '' ?>" href="student_vehicles.php">
                <i class="bi bi-car-front"></i>
                <span>My Vehicles</span>
            </a>
        </li>
        <li class="menu-item">
            <a class="<?= ($sidebarActive === 'profile') ? 'active' : '' ?>" href="student_profile.php">
                <i class="bi bi-person-gear"></i>
                <span>Profile & Pass</span>
            </a>
        </li>
        <li class="menu-item mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.06);">
            <a href="index.php">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Public Overview</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="student_profile.php">
            <i class="bi bi-sliders2"></i>
            <span>Settings</span>
        </a>
        <a href="setup.php">
            <i class="bi bi-question-circle"></i>
            <span>Support</span>
        </a>
        <a href="logout.php" class="text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
