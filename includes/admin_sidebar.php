<?php
/**
 * CampusPark - Admin Modern Dark Sidebar
 */
require_once __DIR__ . '/auth.php';
$admin = get_logged_admin();
$sidebarActive = $sidebarActive ?? 'dashboard';
?>
<aside class="helios-sidebar no-print" id="dashboardSidebar">
    <!-- Brand Header -->
    <a href="admin_dashboard.php" class="sidebar-brand">
        <span class="brand-icon-glyph"><i class="bi bi-shield-lock-fill"></i></span>
        <span>CampusPark</span>
    </a>

    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <li class="menu-item">
            <a class="<?= ($sidebarActive === 'dashboard') ? 'active' : '' ?>" href="admin_dashboard.php">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Admin Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a class="<?= ($sidebarActive === 'register') ? 'active' : '' ?>" href="admin_parking_records.php">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Digital Register</span>
            </a>
        </li>
        <li class="menu-item">
            <a class="<?= ($sidebarActive === 'students_vehicles') ? 'active' : '' ?>" href="admin_students_vehicles.php">
                <i class="bi bi-people-fill"></i>
                <span>Students & Fleet</span>
            </a>
        </li>
        <li class="menu-item">
            <a class="<?= ($sidebarActive === 'reports') ? 'active' : '' ?>" href="admin_export.php">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                <span>Export Reports</span>
            </a>
        </li>
        <li class="menu-item mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.06);">
            <a href="setup.php">
                <i class="bi bi-database-gear"></i>
                <span>Database Setup</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="index.php">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Public Portal</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="setup.php">
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
