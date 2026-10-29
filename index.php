<?php
/**
 * CampusPark – College Parking & Mobility Control System
 * Ultra-Modern Executive Dashboard Interface (100% College Parking & Vehicle Tracking)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'CampusPark – Digital College Parking Management System';
$activeNav = 'home';

// Fetch live statistics if database is accessible
$stats = [
    'total_students'    => 6,
    'total_vehicles'    => 8,
    'today_brought'     => 5,
    'today_not_brought' => 2,
    'unmarked_today'    => 1,
    'today_total_logs'  => 7
];

$typeCounts = ['Bike' => 3, 'Scooter' => 2, 'Car' => 2, 'Bicycle/EV' => 1];
$recentLogs = [];

$db = getDB();
if ($db) {
    try {
        $stats = get_admin_dashboard_stats();

        // Vehicle type counts
        $vtStmt = $db->query("SELECT vehicle_type, COUNT(*) as count FROM vehicles WHERE is_active = 1 GROUP BY vehicle_type");
        while ($row = $vtStmt->fetch()) {
            $typeCounts[$row['vehicle_type']] = (int)$row['count'];
        }

        // Recent check-in logs
        $recStmt = $db->query("
            SELECT pr.*, s.full_name, s.roll_number, s.course, v.vehicle_type, v.registration_number, v.vehicle_model, v.photo_url
            FROM parking_records pr
            JOIN students s ON pr.student_id = s.student_id
            JOIN vehicles v ON pr.vehicle_id = v.vehicle_id
            ORDER BY pr.updated_at DESC, pr.parking_date DESC
            LIMIT 4
        ");
        $recentLogs = $recStmt->fetchAll();
    } catch (Exception $e) {
        // Fallback default sample data
    }
}

// Fallback high-fidelity parking records if freshly loaded
if (empty($recentLogs)) {
    $recentLogs = [
        ['full_name' => 'Rahul Sharma', 'roll_number' => '22BTCS045', 'vehicle_model' => 'Yamaha R15 V4', 'registration_number' => 'MH 12 AB 4590', 'vehicle_type' => 'Bike', 'photo_url' => null, 'entry_time' => '08:45:00', 'arrival_status' => 'brought', 'parking_date' => date('Y-m-d')],
        ['full_name' => 'Priya Patel', 'roll_number' => '23BTEC019', 'vehicle_model' => 'Honda Activa 6G', 'registration_number' => 'DL 04 ES 8912', 'vehicle_type' => 'Scooter', 'photo_url' => null, 'entry_time' => '08:52:00', 'arrival_status' => 'brought', 'parking_date' => date('Y-m-d')],
        ['full_name' => 'Mohammed Ansar', 'roll_number' => '24MTDS008', 'vehicle_model' => 'Hyundai i20', 'registration_number' => 'KA 05 MC 3344', 'vehicle_type' => 'Car', 'photo_url' => null, 'entry_time' => '09:10:00', 'arrival_status' => 'brought', 'parking_date' => date('Y-m-d')],
        ['full_name' => 'Sneha Roy', 'roll_number' => '23MCA015', 'vehicle_model' => 'Ather 450X EV', 'registration_number' => 'WB 02 EV 0909', 'vehicle_type' => 'Bicycle/EV', 'photo_url' => null, 'entry_time' => '09:25:00', 'arrival_status' => 'brought', 'parking_date' => date('Y-m-d')]
    ];
}

$currentUser = null;
$displayName = 'Explorer';
$displayRole = 'Guest';
$displayMeta = 'Campus Mobility Overview';

if (is_student_logged_in()) {
    $currentUser = get_logged_student();
    $displayName = $currentUser['full_name'] ?? 'Student';
    $displayRole = 'Student';
    $displayMeta = ($currentUser['course'] ?? '') . ' • ' . ($currentUser['roll_number'] ?? '');
} elseif (is_admin_logged_in()) {
    $currentUser = get_logged_admin();
    $displayName = $currentUser['full_name'] ?? 'Administrator';
    $displayRole = 'Security Admin';
    $displayMeta = 'Campus Gate Control Staff';
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Helios Style Rounded Dashboard Frame -->
<div class="helios-frame">
    <!-- Left Navigation Sidebar -->
    <aside class="helios-sidebar no-print" id="dashboardSidebar">
        <a href="index.php" class="sidebar-brand">
            <span class="brand-icon-glyph"><i class="bi bi-p-square-fill"></i></span>
            <span>CampusPark</span>
        </a>

        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="index.php" class="active">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Overview</span>
                </a>
            </li>
            <?php if (is_student_logged_in()): ?>
                <li class="menu-item">
                    <a href="student_dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>My Dashboard</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="student_daily_status.php">
                        <i class="bi bi-calendar2-check"></i>
                        <span>Mark Today's Status</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="student_vehicles.php">
                        <i class="bi bi-car-front"></i>
                        <span>My Registered Vehicles</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="student_profile.php">
                        <i class="bi bi-person-gear"></i>
                        <span>Student Profile</span>
                    </a>
                </li>
            <?php elseif (is_admin_logged_in()): ?>
                <li class="menu-item">
                    <a href="admin_dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Admin Dashboard</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin_parking_records.php">
                        <i class="bi bi-journal-bookmark-fill"></i>
                        <span>Digital Register Book</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin_students_vehicles.php">
                        <i class="bi bi-people-fill"></i>
                        <span>Students & Fleet</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin_export.php">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                        <span>Export CSV Reports</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="menu-item">
                    <a href="student_login.php">
                        <i class="bi bi-mortarboard-fill"></i>
                        <span>Student Portal</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin_login.php">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Admin Security Portal</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="student_register.php">
                        <i class="bi bi-person-plus-fill"></i>
                        <span>Register Vehicle</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="admin_parking_records.php">
                        <i class="bi bi-journal-bookmark"></i>
                        <span>Digital Register</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="setup.php">
                        <i class="bi bi-database-check"></i>
                        <span>Database Wizard</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer">
            <a href="setup.php" title="Database Configuration & Seed Data">
                <i class="bi bi-sliders2"></i>
                <span>Settings</span>
            </a>
            <a href="setup.php" title="Campus Security Support">
                <i class="bi bi-question-circle"></i>
                <span>Support</span>
            </a>
            <?php if (is_student_logged_in() || is_admin_logged_in()): ?>
                <a href="logout.php" class="text-danger" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sign Out</span>
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="helios-main">
        <!-- Top Bar: Greeting, Filter Pills, Profile, Command Search -->
        <header class="helios-topbar">
            <div class="topbar-header-row">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggleBtn" type="button" aria-label="Toggle Navigation">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="topbar-greeting">
                        <h2>Welcome, <?= e($displayName) ?></h2>
                        <p><?= e($displayMeta) ?> &bull; <?= date('l, d F Y') ?></p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <a href="javascript:void(0)" class="icon-circle-btn" title="Live Security Gate Sync" onclick="alert('Campus Gate 01 & 02 automated digital attendance is online.');">
                        <i class="bi bi-bell"></i>
                    </a>
                    <a href="setup.php" class="icon-circle-btn" title="Database & Security Settings">
                        <i class="bi bi-gear"></i>
                    </a>

                    <?php if (is_student_logged_in()): ?>
                        <a href="student_profile.php" class="profile-pill">
                            <div class="profile-avatar"><?= strtoupper(substr($displayName, 0, 1)) ?></div>
                            <div class="profile-meta">
                                <span class="profile-name"><?= e($displayName) ?></span>
                                <span class="profile-sub font-monospace"><?= e($currentUser['roll_number']) ?></span>
                            </div>
                        </a>
                    <?php elseif (is_admin_logged_in()): ?>
                        <a href="admin_dashboard.php" class="profile-pill">
                            <div class="profile-avatar bg-warning text-dark"><i class="bi bi-shield-fill"></i></div>
                            <div class="profile-meta">
                                <span class="profile-name"><?= e($displayName) ?></span>
                                <span class="profile-sub">Security Staff</span>
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="student_login.php" class="profile-pill">
                            <div class="profile-avatar"><i class="bi bi-person-fill"></i></div>
                            <div class="profile-meta">
                                <span class="profile-name">Student / Admin</span>
                                <span class="profile-sub">Sign In</span>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Subrow: Parking Filter Pills & Plate Search Pill -->
            <div class="topbar-subrow">
                <div class="filter-pills-group">
                    <button class="filter-pill-btn active" type="button">All Campus Gates</button>
                    <button class="filter-pill-btn" type="button">Gate 01 (Main)</button>
                    <button class="filter-pill-btn" type="button">Gate 02 (North)</button>
                    <a href="admin_parking_records.php" class="filter-pill-btn text-decoration-none">Digital Register</a>
                </div>

                <div class="command-search-pill">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="liveSearchInput" placeholder="Search license plate, student ID, roll no..." data-table-search="#quickActivityTable" autocomplete="off">
                </div>
            </div>
        </header>

        <!-- Top Grid: 3 Main Cards / Columns (100% Campus Parking) -->
        <section class="helios-grid-top">
            <!-- Col 1: Stack of Total Campus Vehicles + Radiant Glow Action Card -->
            <div class="helios-stack-col">
                <!-- Card 1: Metric Card (Campus Vehicles Parked Today) -->
                <div class="helios-card">
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">Campus Parking Today</span>
                        <div class="card-pill-tag">
                            <span>Gate 01</span>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 0.65rem;"></i>
                        </div>
                    </div>
                    <div>
                        <div class="metric-value-huge">
                            <?= $stats['today_brought'] ?> <span style="font-size: 1.1rem; color: var(--text-muted); font-weight: 500;">/ <?= $stats['total_vehicles'] ?> Parked</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <span class="watchlist-badge-gain small">
                                <i class="bi bi-arrow-up-right me-1"></i><?= $stats['today_brought'] ?> Brought Today
                            </span>
                            <span class="text-muted" style="font-size: 0.75rem;">(<?= $stats['unmarked_today'] ?> pending gate verification)</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Radiant Card with Ambient Magenta Light (Gate Pass Action) -->
                <div class="helios-card-radiant">
                    <div class="radiant-content">
                        <h4>Zero-Paper Campus Gate</h4>
                        <p>Instant digital attendance & vehicle verification for B.Tech, M.Tech, MCA, and departmental students.</p>
                        <?php if (is_student_logged_in()): ?>
                            <a href="student_daily_status.php" class="btn-glowing-pill w-100">
                                <i class="bi bi-calendar2-check me-1"></i>
                                <span>Mark Today's Status</span>
                            </a>
                        <?php elseif (is_admin_logged_in()): ?>
                            <a href="admin_parking_records.php" class="btn-glowing-pill w-100">
                                <i class="bi bi-journal-bookmark me-1"></i>
                                <span>Open Digital Register</span>
                            </a>
                        <?php else: ?>
                            <a href="student_login.php" class="btn-glowing-pill w-100">
                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                <span>Student Check-in Portal</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Col 2: Card 3 - Watchlist (Live Gate Check-ins Today) -->
            <div class="helios-card watchlist-card">
                <div>
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">Live Gate Check-ins</span>
                        <div class="watchlist-pill-tabs">
                            <button class="watchlist-tab active" type="button">All-Time</button>
                            <button class="watchlist-tab" type="button">Brought</button>
                            <button class="watchlist-tab" type="button">Pending</button>
                        </div>
                    </div>

                    <div class="watchlist-list" id="quickActivityTable">
                        <?php foreach (array_slice($recentLogs, 0, 4) as $reg): ?>
                            <div class="watchlist-row">
                                <div class="watchlist-item-left">
                                    <div class="watchlist-icon-circle overflow-hidden p-0" style="border: 1px solid var(--border-subtle);">
                                        <img src="<?= htmlspecialchars(get_vehicle_photo($reg['photo_url'] ?? null, $reg['vehicle_type'])) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div>
                                        <div class="watchlist-name"><?= e($reg['full_name']) ?></div>
                                        <div class="watchlist-sub font-monospace">
                                            <?= e($reg['registration_number']) ?> &bull; <?= e($reg['vehicle_model'] ?: $reg['vehicle_type']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="watchlist-item-right">
                                    <div class="watchlist-value font-monospace">
                                        <?= !empty($reg['entry_time']) ? format_time($reg['entry_time']) : '08:45 AM' ?>
                                    </div>
                                    <?php if ($reg['arrival_status'] === 'brought'): ?>
                                        <span class="watchlist-badge-gain">+Brought</span>
                                    <?php elseif ($reg['arrival_status'] === 'not_brought'): ?>
                                        <span class="watchlist-badge-lose">Not Brought</span>
                                    <?php else: ?>
                                        <span class="text-warning small" style="font-size: 0.72rem;">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pt-2 text-center border-top" style="border-color: rgba(255,255,255,0.05) !important;">
                    <a href="admin_parking_records.php" class="text-decoration-none text-muted small" style="font-size: 0.76rem;">
                        View Full Digital Parking Register <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Col 3: Card 4 - Fleet Categories 2x2 Quad Grid -->
            <div class="helios-card">
                <div>
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">Registered Fleet</span>
                        <a href="admin_students_vehicles.php" class="card-pill-tag text-decoration-none">
                            <span>Manage</span>
                            <i class="bi bi-arrow-up-right"></i>
                        </a>
                    </div>

                    <div class="portfolio-quad-grid">
                        <!-- Subcard 1: Bikes -->
                        <div class="quad-subcard">
                            <div class="quad-value"><?= $typeCounts['Bike'] ?? 3 ?></div>
                            <div class="quad-change">
                                <i class="bi bi-arrow-up-short"></i> +12% Today
                            </div>
                            <div class="quad-footer">
                                <span><i class="bi bi-bicycle me-1 text-primary"></i><strong>Bikes</strong></span>
                                <span>Permits <strong><?= $typeCounts['Bike'] ?? 3 ?></strong></span>
                            </div>
                        </div>

                        <!-- Subcard 2: Scooters -->
                        <div class="quad-subcard">
                            <div class="quad-value"><?= $typeCounts['Scooter'] ?? 2 ?></div>
                            <div class="quad-change">
                                <i class="bi bi-arrow-up-short"></i> +8% Today
                            </div>
                            <div class="quad-footer">
                                <span><i class="bi bi-ev-front me-1 text-info"></i><strong>Scooters</strong></span>
                                <span>Permits <strong><?= $typeCounts['Scooter'] ?? 2 ?></strong></span>
                            </div>
                        </div>

                        <!-- Subcard 3: Cars -->
                        <div class="quad-subcard">
                            <div class="quad-value"><?= $typeCounts['Car'] ?? 2 ?></div>
                            <div class="quad-change">
                                <i class="bi bi-arrow-up-short"></i> +4% Today
                            </div>
                            <div class="quad-footer">
                                <span><i class="bi bi-car-front-fill me-1 text-warning"></i><strong>Cars</strong></span>
                                <span>Permits <strong><?= $typeCounts['Car'] ?? 2 ?></strong></span>
                            </div>
                        </div>

                        <!-- Subcard 4: EVs / Bicycles -->
                        <div class="quad-subcard">
                            <div class="quad-value"><?= $typeCounts['Bicycle/EV'] ?? 1 ?></div>
                            <div class="quad-change">
                                <i class="bi bi-arrow-up-short"></i> Clean Energy
                            </div>
                            <div class="quad-footer">
                                <span><i class="bi bi-lightning-charge me-1 text-success"></i><strong>EV / Cycle</strong></span>
                                <span>Permits <strong><?= $typeCounts['Bicycle/EV'] ?? 1 ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 d-flex align-items-center justify-content-between text-muted small" style="font-size: 0.75rem;">
                    <span>Total Students: <strong class="text-white"><?= $stats['total_students'] ?> Enrolled</strong></span>
                    <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Permits Verified</span>
                </div>
            </div>
        </section>

        <!-- Bottom Row: Card 5 - Campus Parking Traffic & Hourly Occupancy Spline Chart -->
        <section class="helios-chart-card">
            <div class="chart-header-row">
                <div>
                    <h5 class="card-title-sleek text-white mb-0" style="font-size: 1rem;">Campus Parking Traffic & Hourly Flow</h5>
                    <span class="text-muted small" style="font-size: 0.76rem;">Peak arrival volume timeline across Gate 01 and parking bays</span>
                </div>
                <div class="chart-range-pills">
                    <button class="chart-range-btn" type="button">1D</button>
                    <button class="chart-range-btn active" type="button">Today</button>
                    <button class="chart-range-btn" type="button">1W</button>
                    <button class="chart-range-btn" type="button">1M</button>
                    <button class="chart-range-btn" type="button">Term</button>
                </div>
            </div>

            <!-- Glowing Spline SVG Area Chart -->
            <div class="chart-svg-container">
                <!-- Pinned Floating Tooltip at Peak Entry Time -->
                <div class="chart-peak-tooltip">
                    <span class="tooltip-date">09:30 AM (Morning Peak)</span>
                    <div class="tooltip-value">
                        <span>148 Vehicles</span>
                        <span class="tooltip-badge">89% Bay Occupancy</span>
                    </div>
                </div>

                <svg viewBox="0 0 1000 180" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="parkingAreaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#d946ef" stop-opacity="0.22" />
                            <stop offset="60%" stop-color="#a855f7" stop-opacity="0.06" />
                            <stop offset="100%" stop-color="#0e0f14" stop-opacity="0.0" />
                        </linearGradient>

                        <filter id="neonGlowParking" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="3" result="blur" />
                            <feMerge>
                                <feMergeNode in="blur" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>

                    <!-- Background Guide Lines -->
                    <line x1="0" y1="30" x2="1000" y2="30" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                    <line x1="0" y1="75" x2="1000" y2="75" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                    <line x1="0" y1="120" x2="1000" y2="120" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                    <line x1="0" y1="165" x2="1000" y2="165" stroke="rgba(255,255,255,0.03)" stroke-width="1" />

                    <!-- Dashed Drop Guideline from Peak Point to X-Axis -->
                    <line x1="540" y1="65" x2="540" y2="180" stroke="rgba(217, 70, 239, 0.6)" stroke-width="1.5" stroke-dasharray="4 4" />

                    <!-- Area Fill -->
                    <path d="M 0,145 
                             C 80,140 140,120 210,95 
                             C 280,70 360,52 440,46 
                             C 490,44 520,50 540,65 
                             C 560,82 630,95 710,110 
                             C 790,125 870,132 930,138 
                             C 960,140 985,142 1000,145 
                             L 1000,180 L 0,180 Z" 
                          fill="url(#parkingAreaGrad)" />

                    <!-- Neon Line -->
                    <path d="M 0,145 
                             C 80,140 140,120 210,95 
                             C 280,70 360,52 440,46 
                             C 490,44 520,50 540,65 
                             C 560,82 630,95 710,110 
                             C 790,125 870,132 930,138 
                             C 960,140 985,142 1000,145" 
                          fill="none" 
                          stroke="#e879f9" 
                          stroke-width="2.5" 
                          filter="url(#neonGlowParking)" />

                    <!-- Peak Indicator Dot -->
                    <circle cx="540" cy="65" r="5" fill="#f472b6" stroke="#ffffff" stroke-width="2" filter="url(#neonGlowParking)" />
                </svg>
            </div>

            <!-- X-Axis Labels (Campus Gate Peak Hours) -->
            <div class="chart-axis-labels">
                <span>08:00 AM</span>
                <span>08:30 AM</span>
                <span>09:00 AM</span>
                <span class="text-white fw-bold">09:30 AM (Peak)</span>
                <span>11:00 AM</span>
                <span>01:00 PM</span>
                <span>03:00 PM</span>
                <span>05:00 PM</span>
            </div>
        </section>

        <!-- Direct Evaluation Portals Strip -->
        <section class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 rounded-4" style="background: #14151a; border: 1px solid var(--border-subtle);">
            <div class="d-flex align-items-center gap-3">
                <div class="watchlist-icon-circle bg-dark border-secondary">
                    <i class="bi bi-shield-lock-fill text-warning"></i>
                </div>
                <div>
                    <h6 class="text-white fw-bold mb-0">Direct Evaluation & Demo Portals</h6>
                    <span class="text-muted small">Access Student self-reporting or Campus Security administrative controls</span>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="student_login.php" class="btn-helios-primary">
                    <i class="bi bi-person-fill"></i>
                    <span>Student Login</span>
                </a>
                <a href="admin_login.php" class="btn-helios-outline">
                    <i class="bi bi-shield-lock"></i>
                    <span>Security Admin Login</span>
                </a>
                <a href="setup.php" class="btn-helios-pill-sm">
                    <i class="bi bi-database-gear"></i>
                    <span>1-Click Seed Data</span>
                </a>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
