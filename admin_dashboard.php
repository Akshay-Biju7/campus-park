<?php
/**
 * CampusPark – Administrator Dashboard
 * Modern Dark Helios Layout: Real-time monitoring of campus vehicle attendance, KPIs, vehicle breakdowns, and today's digital register
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_admin();

$admin = get_logged_admin();
$today = date('Y-m-d');

$pageTitle = 'Admin Dashboard – ' . APP_NAME;
$activeNav = 'admin_dash';
$sidebarActive = 'dashboard';

$db = getDB();

// Handle quick gate arrival status update from admin dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_quick_update') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Security validation failed.');
    } else {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'brought');
        $remarks   = trim($_POST['remarks'] ?? 'Updated by Gate Security');

        if ($studentId > 0 && $vehicleId > 0 && in_array($newStatus, ['brought', 'not_brought'])) {
            $updated = set_daily_parking_status($studentId, $vehicleId, $newStatus, $today, $remarks);
            if ($updated) {
                set_flash('success', "Parking record for vehicle #{$vehicleId} updated to '" . ($newStatus === 'brought' ? 'Brought' : 'Not Brought') . "' for today.");
            }
        }
    }
    header("Location: admin_dashboard.php");
    exit;
}

// Fetch dashboard KPIs
$stats = get_admin_dashboard_stats();

// Fetch Vehicle Types Breakdown
$vehicleTypeStats = [];
$courseStats = [];
$todayRegister = [];

if ($db) {
    // 1. Vehicle Types Count
    $vtStmt = $db->query("
        SELECT vehicle_type, COUNT(*) as count 
        FROM vehicles 
        WHERE is_active = 1 
        GROUP BY vehicle_type
    ");
    $vehicleTypeStats = $vtStmt->fetchAll();

    // 2. Course Distribution
    $cStmt = $db->query("
        SELECT course, COUNT(*) as count 
        FROM students 
        WHERE is_active = 1 
        GROUP BY course
    ");
    $courseStats = $cStmt->fetchAll();

    // 3. Today's Digital Register snapshot
    $regStmt = $db->prepare("
        SELECT 
            s.student_id, s.roll_number, s.full_name, s.course, s.department, s.year_of_study, s.contact_number,
            v.vehicle_id, v.vehicle_type, v.registration_number, v.vehicle_model, v.colour, v.photo_url,
            pr.record_id, pr.arrival_status, pr.entry_time, pr.remarks, pr.updated_at
        FROM vehicles v
        JOIN students s ON v.student_id = s.student_id
        LEFT JOIN parking_records pr ON v.vehicle_id = pr.vehicle_id AND pr.parking_date = :today
        WHERE v.is_active = 1 AND s.is_active = 1
        ORDER BY 
            CASE WHEN pr.arrival_status = 'brought' THEN 1 
                 WHEN pr.arrival_status = 'not_brought' THEN 2 
                 ELSE 3 END,
            pr.entry_time DESC,
            s.full_name ASC
    ");
    $regStmt->execute([':today' => $today]);
    $todayRegister = $regStmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Helios Style Rounded Dashboard Frame -->
<div class="helios-frame">
    <!-- Admin Sidebar -->
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="helios-main">
        <!-- Top Bar: Greeting, Filter Pills, Profile, AI Command Search -->
        <header class="helios-topbar">
            <div class="topbar-header-row">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggleBtn" type="button" aria-label="Toggle Navigation">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="topbar-greeting">
                        <h2>Welcome, <?= e($admin['full_name']) ?></h2>
                        <p>Campus Security & Parking Authority Control Center &bull; <?= date('l, d F Y') ?></p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <a href="admin_export.php?date=<?= $today ?>" class="icon-circle-btn" title="Download Excel/CSV Report">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </a>
                    <a href="setup.php" class="icon-circle-btn" title="Database Configuration">
                        <i class="bi bi-gear"></i>
                    </a>

                    <div class="profile-pill">
                        <div class="profile-avatar bg-warning text-dark"><i class="bi bi-shield-fill"></i></div>
                        <div class="profile-meta">
                            <span class="profile-name"><?= e($admin['full_name']) ?></span>
                            <span class="profile-sub">Campus Security Staff</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subrow: Filter Pills & Search Pill -->
            <div class="topbar-subrow">
                <div class="filter-pills-group">
                    <a href="admin_dashboard.php" class="filter-pill-btn active">Live Overview</a>
                    <a href="admin_parking_records.php" class="filter-pill-btn">Digital Register</a>
                    <a href="admin_students_vehicles.php" class="filter-pill-btn">Students Directory</a>
                </div>

                <div class="command-search-pill">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" placeholder="Search plates, roll numbers, students..." data-table-search="#adminRegisterTable" autocomplete="off">
                </div>
            </div>
        </header>

        <!-- Top Grid: 3 Main Columns / Cards (Helios Aesthetic) -->
        <section class="helios-grid-top">
            <!-- Col 1: Stack of Total Campus Fleet + Radiant Action Card -->
            <div class="helios-stack-col">
                <!-- Card 1: Metric Card -->
                <div class="helios-card">
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">Registered Fleet</span>
                        <div class="card-pill-tag">
                            <span>Today</span>
                            <i class="bi bi-chevron-down" style="font-size: 0.65rem;"></i>
                        </div>
                    </div>
                    <div>
                        <div class="metric-value-huge">
                            <?= $stats['total_vehicles'] ?>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <span class="watchlist-badge-gain small"><i class="bi bi-check-circle-fill me-1"></i><?= $stats['today_brought'] ?> Parked Today</span>
                            <span class="text-muted" style="font-size: 0.75rem;">(<?= $stats['unmarked_today'] ?> pending)</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Radiant Magenta Light Card -->
                <div class="helios-card-radiant">
                    <div class="radiant-content">
                        <h4>Gate Security Active</h4>
                        <p>Real-time automated vehicle verification and instant paperless register sync across all university departments.</p>
                        <a href="admin_parking_records.php" class="btn-glowing-pill w-100">
                            <i class="bi bi-journal-bookmark me-1"></i>
                            <span>Open Digital Register</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Col 2: Card 3 - Watchlist (Live Gate Check-ins Today) -->
            <div class="helios-card watchlist-card">
                <div>
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">Live Gate Activity</span>
                        <div class="watchlist-pill-tabs">
                            <button class="watchlist-tab active" type="button">Today</button>
                            <button class="watchlist-tab" type="button">Brought</button>
                            <button class="watchlist-tab" type="button">Unmarked</button>
                        </div>
                    </div>

                    <div class="watchlist-list">
                        <?php if (empty($todayRegister)): ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-clock-history fs-3 d-block mb-1"></i>
                                No vehicle records found for today.
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($todayRegister, 0, 4) as $reg): ?>
                                <div class="watchlist-row">
                                    <div class="watchlist-item-left">
                                        <div class="watchlist-icon-circle overflow-hidden p-0" style="border: 1px solid var(--border-subtle);">
                                            <img src="<?= htmlspecialchars(get_vehicle_photo($reg['photo_url'] ?? null, $reg['vehicle_type'])) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div>
                                            <div class="watchlist-name"><?= e($reg['full_name']) ?></div>
                                            <div class="watchlist-sub font-monospace"><?= e($reg['registration_number']) ?> &bull; <?= e($reg['course']) ?></div>
                                        </div>
                                    </div>
                                    <div class="watchlist-item-right">
                                        <div class="watchlist-value font-monospace">
                                            <?= $reg['entry_time'] ? format_time($reg['entry_time']) : '—' ?>
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
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-2 text-center border-top" style="border-color: rgba(255,255,255,0.05) !important;">
                    <a href="admin_parking_records.php" class="text-decoration-none text-muted small" style="font-size: 0.76rem;">
                        View All Gate Records <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Col 3: Card 4 - Fleet Breakdown 2x2 Quad Grid -->
            <div class="helios-card">
                <div>
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">Fleet Categories</span>
                        <a href="admin_students_vehicles.php" class="card-pill-tag text-decoration-none">
                            <span>See all</span>
                            <i class="bi bi-arrow-up-right"></i>
                        </a>
                    </div>

                    <div class="portfolio-quad-grid">
                        <?php 
                        $typeMap = ['Bike' => 0, 'Scooter' => 0, 'Car' => 0, 'Bicycle/EV' => 0];
                        foreach ($vehicleTypeStats as $vt) {
                            $typeMap[$vt['vehicle_type']] = (int)$vt['count'];
                        }
                        ?>
                        <!-- Bikes -->
                        <div class="quad-subcard">
                            <div class="quad-value"><?= $typeMap['Bike'] ?></div>
                            <div class="quad-change">
                                <i class="bi bi-arrow-up-short"></i> Motorbikes
                            </div>
                            <div class="quad-footer">
                                <span><i class="bi bi-bicycle me-1"></i>Bikes</span>
                                <span>Units <strong><?= $typeMap['Bike'] ?></strong></span>
                            </div>
                        </div>

                        <!-- Scooters -->
                        <div class="quad-subcard">
                            <div class="quad-value"><?= $typeMap['Scooter'] ?></div>
                            <div class="quad-change">
                                <i class="bi bi-arrow-up-short"></i> Scooters
                            </div>
                            <div class="quad-footer">
                                <span><i class="bi bi-ev-front me-1"></i>Scooters</span>
                                <span>Units <strong><?= $typeMap['Scooter'] ?></strong></span>
                            </div>
                        </div>

                        <!-- Cars -->
                        <div class="quad-subcard">
                            <div class="quad-value"><?= $typeMap['Car'] ?></div>
                            <div class="quad-change">
                                <i class="bi bi-arrow-up-short"></i> 4-Wheelers
                            </div>
                            <div class="quad-footer">
                                <span><i class="bi bi-car-front-fill me-1"></i>Cars</span>
                                <span>Units <strong><?= $typeMap['Car'] ?></strong></span>
                            </div>
                        </div>

                        <!-- EVs / Bicycles -->
                        <div class="quad-subcard">
                            <div class="quad-value"><?= $typeMap['Bicycle/EV'] ?></div>
                            <div class="quad-change">
                                <i class="bi bi-arrow-up-short"></i> Clean Energy
                            </div>
                            <div class="quad-footer">
                                <span><i class="bi bi-lightning-charge me-1"></i>EV / Cycles</span>
                                <span>Units <strong><?= $typeMap['Bicycle/EV'] ?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 d-flex align-items-center justify-content-between text-muted small" style="font-size: 0.75rem;">
                    <span>Total Students: <strong class="text-white"><?= $stats['total_students'] ?></strong></span>
                    <a href="admin_export.php?date=<?= $today ?>" class="text-success text-decoration-none">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </a>
                </div>
            </div>
        </section>

        <!-- Bottom Row: Card 5 - Full Width Campus Traffic Spline Chart -->
        <section class="helios-chart-card">
            <div class="chart-header-row">
                <div>
                    <h5 class="card-title-sleek text-white mb-0" style="font-size: 1rem;">Campus Parking Occupancy & Peak Flow</h5>
                    <span class="text-muted small" style="font-size: 0.76rem;">Arrival volumes across Gate 01 and Campus Parking Zones</span>
                </div>
                <div class="chart-range-pills">
                    <button class="chart-range-btn" type="button">1D</button>
                    <button class="chart-range-btn active" type="button">Today</button>
                    <button class="chart-range-btn" type="button">1W</button>
                    <button class="chart-range-btn" type="button">1M</button>
                    <button class="chart-range-btn" type="button">1Y</button>
                </div>
            </div>

            <!-- Glowing Spline SVG Area Chart -->
            <div class="chart-svg-container">
                <div class="chart-peak-tooltip">
                    <span class="tooltip-date">Peak Gate Flow: 09:30 AM</span>
                    <div class="tooltip-value">
                        <span>89% Capacity</span>
                        <span class="tooltip-badge">+25%</span>
                    </div>
                </div>

                <svg viewBox="0 0 1000 180" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="adminAreaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#d946ef" stop-opacity="0.22" />
                            <stop offset="60%" stop-color="#a855f7" stop-opacity="0.06" />
                            <stop offset="100%" stop-color="#0e0f14" stop-opacity="0.0" />
                        </linearGradient>
                        <filter id="neonGlowAdmin" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="3" result="blur" />
                            <feMerge>
                                <feMergeNode in="blur" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>

                    <line x1="0" y1="30" x2="1000" y2="30" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                    <line x1="0" y1="75" x2="1000" y2="75" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                    <line x1="0" y1="120" x2="1000" y2="120" stroke="rgba(255,255,255,0.03)" stroke-width="1" />
                    <line x1="0" y1="165" x2="1000" y2="165" stroke="rgba(255,255,255,0.03)" stroke-width="1" />

                    <line x1="540" y1="65" x2="540" y2="180" stroke="rgba(217, 70, 239, 0.6)" stroke-width="1.5" stroke-dasharray="4 4" />

                    <path d="M 0,140 
                             C 80,135 150,110 230,90 
                             C 310,70 380,50 460,45 
                             C 510,42 530,48 540,65 
                             C 560,85 640,95 720,110 
                             C 800,125 880,130 940,135 
                             C 970,138 990,140 1000,140 
                             L 1000,180 L 0,180 Z" 
                          fill="url(#adminAreaGrad)" />

                    <path d="M 0,140 
                             C 80,135 150,110 230,90 
                             C 310,70 380,50 460,45 
                             C 510,42 530,48 540,65 
                             C 560,85 640,95 720,110 
                             C 800,125 880,130 940,135 
                             C 970,138 990,140 1000,140" 
                          fill="none" 
                          stroke="#e879f9" 
                          stroke-width="2.5" 
                          filter="url(#neonGlowAdmin)" />

                    <circle cx="540" cy="65" r="5" fill="#f472b6" stroke="#ffffff" stroke-width="2" filter="url(#neonGlowAdmin)" />
                </svg>
            </div>

            <div class="chart-axis-labels">
                <span>08:00 AM</span>
                <span>09:00 AM</span>
                <span class="text-white fw-bold">09:30 AM (Peak)</span>
                <span>11:00 AM</span>
                <span>01:00 PM</span>
                <span>03:00 PM</span>
                <span>05:00 PM</span>
            </div>
        </section>

        <!-- Today's Live Digital Register Table (Clean Dark) -->
        <section class="helios-table-wrapper">
            <div class="p-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3" style="border-bottom: 1px solid var(--border-subtle); background: #14151a;">
                <div>
                    <h6 class="text-white fw-bold mb-0">Today's Live Digital Register (Gate 01)</h6>
                    <span class="text-muted small"><?= date('l, d F Y') ?> &bull; <?= count($todayRegister) ?> registered vehicles in catalog</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="admin_export.php?date=<?= $today ?>" class="btn-helios-primary py-1 px-3" style="font-size: 0.8rem;">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                    </a>
                    <a href="admin_parking_records.php" class="btn-helios-pill-sm">
                        <i class="bi bi-journal-bookmark"></i> Full Register Book
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-dark-custom" id="adminRegisterTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Roll Number</th>
                            <th>Course & Dept</th>
                            <th>Vehicle Type</th>
                            <th>License Plate</th>
                            <th>Arrival Status</th>
                            <th>Entry Time</th>
                            <th>Gate Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todayRegister)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No student vehicle records found in database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($todayRegister as $r): ?>
                                <tr>
                                    <td class="text-white fw-semibold"><?= e($r['full_name']) ?></td>
                                    <td><span class="text-info font-monospace small"><?= e($r['roll_number']) ?></span></td>
                                    <td class="text-muted small"><?= e($r['course']) ?> (<?= e($r['department']) ?>)</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= htmlspecialchars(get_vehicle_photo($r['photo_url'] ?? null, $r['vehicle_type'])) ?>" alt="" class="vehicle-thumb-sm">
                                            <span class="text-muted small"><?= e($r['vehicle_type']) ?></span>
                                        </div>
                                    </td>
                                    <td><span class="vehicle-plate"><?= e($r['registration_number']) ?></span></td>
                                    <td><?= render_status_badge($r['arrival_status']) ?></td>
                                    <td class="font-monospace text-muted small"><?= $r['entry_time'] ? format_time($r['entry_time']) : '—' ?></td>
                                    <td>
                                        <form method="POST" action="admin_dashboard.php" class="d-inline-flex gap-1">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="admin_quick_update">
                                            <input type="hidden" name="student_id" value="<?= (int)$r['student_id'] ?>">
                                            <input type="hidden" name="vehicle_id" value="<?= (int)$r['vehicle_id'] ?>">
                                            
                                            <?php if ($r['arrival_status'] === 'brought'): ?>
                                                <button type="submit" name="status" value="not_brought" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" title="Mark Not Brought">
                                                    Reset
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="status" value="brought" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.72rem;" title="Mark Brought Today">
                                                    Check-In
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
