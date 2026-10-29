<?php
/**
 * CampusPark – Student Dashboard
 * Modern Dark Helios Layout: Personalized portal showing student details, registered vehicles, today's arrival status, and attendance trends
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Enforce student login
require_student();

$student = get_logged_student();
$studentId = (int)$student['student_id'];
$today = date('Y-m-d');

$pageTitle = 'Student Dashboard – ' . APP_NAME;
$activeNav = 'student_dash';
$sidebarActive = 'dashboard';

// Handle quick inline status update from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_status_update') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Security validation failed. Please try again.');
    } else {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'not_brought');
        $remarks   = trim($_POST['remarks'] ?? '');

        if ($vehicleId > 0 && in_array($newStatus, ['brought', 'not_brought'])) {
            $updated = set_daily_parking_status($studentId, $vehicleId, $newStatus, $today, $remarks ?: ($newStatus === 'brought' ? 'Marked from Dashboard' : 'Left at home'));
            if ($updated) {
                $statusText = ($newStatus === 'brought') ? 'Brought to College' : 'Not Brought';
                set_flash('success', "Vehicle status updated to '{$statusText}' for today (" . format_date($today) . ").");
            } else {
                set_flash('danger', 'Unable to update vehicle status. Please verify vehicle ownership.');
            }
        }
    }
    header("Location: student_dashboard.php");
    exit;
}

// Fetch Student Stats
$stats = get_student_stats($studentId);

// Fetch Student Vehicles with Today's Status
$vehicles = get_student_vehicles_with_status($studentId, $today);

// Fetch Recent Parking History (last 10 records)
$db = getDB();
$historyRecords = [];
if ($db) {
    $histStmt = $db->prepare("
        SELECT pr.*, v.registration_number, v.vehicle_type, v.vehicle_model, v.photo_url
        FROM parking_records pr
        JOIN vehicles v ON pr.vehicle_id = v.vehicle_id
        WHERE pr.student_id = ?
        ORDER BY pr.parking_date DESC, pr.updated_at DESC
        LIMIT 10
    ");
    $histStmt->execute([$studentId]);
    $historyRecords = $histStmt->fetchAll();
}

$primaryVehicle = $vehicles[0] ?? null;

require_once __DIR__ . '/includes/header.php';
?>

<!-- Helios Style Rounded Dashboard Frame -->
<div class="helios-frame">
    <!-- Student Sidebar -->
    <?php require_once __DIR__ . '/includes/student_sidebar.php'; ?>

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
                        <h2>Welcome, <?= e($student['full_name']) ?></h2>
                        <p><?= e($student['course']) ?> &bull; <?= e($student['department']) ?> &bull; <?= e($student['year_of_study']) ?></p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <a href="javascript:void(0)" class="icon-circle-btn" title="Live Security Gate Sync" onclick="alert('Student Parking Pass is valid for today. Security checkpoint: Gate 01.');">
                        <i class="bi bi-bell"></i>
                    </a>
                    <a href="student_profile.php" class="icon-circle-btn" title="Profile Settings">
                        <i class="bi bi-gear"></i>
                    </a>

                    <a href="student_profile.php" class="profile-pill">
                        <div class="profile-avatar"><?= strtoupper(substr($student['full_name'] ?? 'S', 0, 1)) ?></div>
                        <div class="profile-meta">
                            <span class="profile-name"><?= e($student['full_name']) ?></span>
                            <span class="profile-sub font-monospace"><?= e($student['roll_number']) ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Subrow: Filter Pills & Search Pill -->
            <div class="topbar-subrow">
                <div class="filter-pills-group">
                    <a href="student_dashboard.php" class="filter-pill-btn active">Overview</a>
                    <a href="student_daily_status.php" class="filter-pill-btn">Daily Status</a>
                    <a href="student_vehicles.php" class="filter-pill-btn">My Vehicles</a>
                </div>

                <div class="command-search-pill">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" placeholder="Search my records or plates..." data-table-search="#studentHistoryTable" autocomplete="off">
                </div>
            </div>
        </header>

        <!-- Top Grid: 3 Main Cards / Columns (Matching Helios Layout) -->
        <section class="helios-grid-top">
            <!-- Col 1: Stack of Total Holding (My Fleet) + Radiant Glow Card -->
            <div class="helios-stack-col">
                <!-- Card 1: Metric Card -->
                <div class="helios-card">
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">Registered Vehicles</span>
                        <div class="card-pill-tag">
                            <span>Active</span>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 0.65rem;"></i>
                        </div>
                    </div>
                    <div>
                        <div class="metric-value-huge">
                            <?= $stats['total_vehicles'] ?>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <span class="watchlist-badge-gain small"><i class="bi bi-calendar-check me-1"></i><?= $stats['month_brought'] ?> Days</span>
                            <span class="text-muted" style="font-size: 0.75rem;">Brought this month (<?= date('M Y') ?>)</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Radiant Card with Ambient Magenta Light (Today's Gate Clearance) -->
                <div class="helios-card-radiant">
                    <div class="radiant-content">
                        <h4>Today's Gate Clearance</h4>
                        
                        <?php if ($primaryVehicle): ?>
                            <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded-3" style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08);">
                                <img src="<?= htmlspecialchars(get_vehicle_photo($primaryVehicle['photo_url'] ?? null, $primaryVehicle['vehicle_type'])) ?>" alt="" class="vehicle-thumb-sm">
                                <div class="overflow-hidden">
                                    <div class="vehicle-plate font-monospace small px-2 py-0" style="font-size: 0.76rem;"><?= e($primaryVehicle['registration_number']) ?></div>
                                    <div class="text-muted small text-truncate" style="font-size: 0.72rem;"><?= e($primaryVehicle['vehicle_model'] ?: $primaryVehicle['vehicle_type']) ?></div>
                                </div>
                                <span class="ms-auto badge" style="background: var(--bg-pill); color: var(--text-secondary); font-size: 0.7rem; border: 1px solid var(--border-subtle);"><?= e($primaryVehicle['vehicle_type']) ?></span>
                            </div>
                        <?php endif; ?>

                        <p>
                            <?php if ($primaryVehicle): ?>
                                Status: <strong><?= $primaryVehicle['arrival_status'] === 'brought' ? 'Brought to College' : ($primaryVehicle['arrival_status'] === 'not_brought' ? 'Marked at Home' : 'Not marked yet') ?></strong>
                            <?php else: ?>
                                Register your vehicle to obtain seamless campus parking access.
                            <?php endif; ?>
                        </p>

                        <?php if ($primaryVehicle): ?>
                            <form method="POST" action="student_dashboard.php">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="quick_status_update">
                                <input type="hidden" name="vehicle_id" value="<?= (int)$primaryVehicle['vehicle_id'] ?>">

                                <?php if ($primaryVehicle['arrival_status'] === 'brought'): ?>
                                    <button type="submit" name="status" value="not_brought" class="btn-glowing-pill w-100" style="background: linear-gradient(135deg, #ef4444 0%, #db2777 100%);">
                                        <i class="bi bi-x-circle me-1"></i>
                                        <span>Change to Not Brought</span>
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="status" value="brought" class="btn-glowing-pill w-100">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        <span>Mark Brought Today</span>
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php else: ?>
                            <a href="student_vehicles.php?action=new" class="btn-glowing-pill w-100">
                                <i class="bi bi-plus-lg me-1"></i>
                                <span>Register Vehicle</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Col 2: Card 3 - Watchlist (Recent Daily Logs) -->
            <div class="helios-card watchlist-card">
                <div>
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">Recent Attendance</span>
                        <div class="watchlist-pill-tabs">
                            <button class="watchlist-tab active" type="button">All-Time</button>
                            <button class="watchlist-tab" type="button">Brought</button>
                            <button class="watchlist-tab" type="button">Past 30D</button>
                        </div>
                    </div>

                    <div class="watchlist-list">
                        <?php if (empty($historyRecords)): ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-calendar-x fs-3 d-block mb-1"></i>
                                No parking logs recorded yet. Mark your arrival above!
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($historyRecords, 0, 4) as $rec): ?>
                                <div class="watchlist-row">
                                    <div class="watchlist-item-left">
                                        <div class="watchlist-icon-circle overflow-hidden p-0" style="border: 1px solid var(--border-subtle);">
                                            <img src="<?= htmlspecialchars(get_vehicle_photo($rec['photo_url'] ?? null, $rec['vehicle_type'])) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div>
                                            <div class="watchlist-name font-monospace"><?= e($rec['registration_number']) ?></div>
                                            <div class="watchlist-sub"><?= format_date($rec['parking_date']) ?></div>
                                        </div>
                                    </div>
                                    <div class="watchlist-item-right">
                                        <div class="watchlist-value font-monospace">
                                            <?= $rec['entry_time'] ? format_time($rec['entry_time']) : '—' ?>
                                        </div>
                                        <?php if ($rec['arrival_status'] === 'brought'): ?>
                                            <span class="watchlist-badge-gain">+Brought</span>
                                        <?php else: ?>
                                            <span class="watchlist-badge-lose">Not Brought</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-2 text-center border-top" style="border-color: rgba(255,255,255,0.05) !important;">
                    <a href="student_daily_status.php" class="text-decoration-none text-muted small" style="font-size: 0.76rem;">
                        View Full History & Logs <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Col 3: Card 4 - My Portfolio (Student Vehicles Quad Grid) -->
            <div class="helios-card">
                <div>
                    <div class="card-header-sleek">
                        <span class="card-title-sleek">My Vehicles</span>
                        <a href="student_vehicles.php" class="card-pill-tag text-decoration-none">
                            <span>Manage</span>
                            <i class="bi bi-arrow-up-right"></i>
                        </a>
                    </div>

                    <div class="portfolio-quad-grid">
                        <?php if (empty($vehicles)): ?>
                            <div class="col-span-2 text-center py-4 text-muted small" style="grid-column: span 2;">
                                <i class="bi bi-car-front fs-3 d-block mb-1"></i>
                                No registered vehicles found.<br>
                                <a href="student_vehicles.php?action=new" class="text-primary text-decoration-none mt-2 d-inline-block">Add your first vehicle</a>
                            </div>
                        <?php else: ?>
                            <?php 
                            $quadVehicles = array_slice($vehicles, 0, 4);
                            while (count($quadVehicles) < 4) {
                                $quadVehicles[] = null; // Fill remaining grid spots gracefully
                            }
                            foreach ($quadVehicles as $vItem): 
                                if ($vItem):
                            ?>
                                <div class="quad-subcard">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <img src="<?= htmlspecialchars(get_vehicle_photo($vItem['photo_url'] ?? null, $vItem['vehicle_type'])) ?>" alt="" class="vehicle-thumb-sm">
                                        <div class="quad-value font-monospace" style="font-size: 0.88rem; line-height: 1.2;">
                                            <?= e($vItem['registration_number']) ?>
                                        </div>
                                    </div>
                                    <div class="quad-change">
                                        <?php if ($vItem['arrival_status'] === 'brought'): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i> <span class="text-success">On Campus</span>
                                        <?php else: ?>
                                            <i class="bi bi-dash-circle text-muted"></i> <span class="text-muted">Not Brought</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="quad-footer">
                                        <span><i class="bi <?= get_vehicle_icon($vItem['vehicle_type']) ?> me-1"></i><?= e($vItem['vehicle_type']) ?></span>
                                        <span><?= e($vItem['vehicle_model'] ?: 'Standard') ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="quad-subcard text-center d-flex align-items-center justify-content-center" style="opacity: 0.45; border-style: dashed;">
                                    <a href="student_vehicles.php?action=new" class="text-decoration-none text-muted small">
                                        <i class="bi bi-plus-circle fs-5 d-block mb-1"></i>
                                        + Add Vehicle
                                    </a>
                                </div>
                            <?php endif; endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-2 d-flex align-items-center justify-content-between text-muted small" style="font-size: 0.75rem;">
                    <span>Student ID: <strong class="font-monospace text-white"><?= e($student['roll_number']) ?></strong></span>
                    <a href="student_vehicles.php" class="text-decoration-none text-muted">View Details</a>
                </div>
            </div>
        </section>

        <!-- Bottom Row: Card 5 - Full Width Attendance Performance Chart -->
        <section class="helios-chart-card">
            <div class="chart-header-row">
                <div>
                    <h5 class="card-title-sleek text-white mb-0" style="font-size: 1rem;">Attendance & Mobility Performance</h5>
                    <span class="text-muted small" style="font-size: 0.76rem;">Daily gate check-in consistency & peak campus arrival timeline</span>
                </div>
                <div class="chart-range-pills">
                    <button class="chart-range-btn" type="button">1D</button>
                    <button class="chart-range-btn active" type="button">1W</button>
                    <button class="chart-range-btn" type="button">1M</button>
                    <button class="chart-range-btn" type="button">6M</button>
                    <button class="chart-range-btn" type="button">1Y</button>
                </div>
            </div>

            <!-- Glowing Spline SVG Area Chart -->
            <div class="chart-svg-container">
                <div class="chart-peak-tooltip">
                    <span class="tooltip-date">Today (<?= date('D, d M') ?>)</span>
                    <div class="tooltip-value">
                        <span>Check-in: 08:45 AM</span>
                        <span class="tooltip-badge">Verified</span>
                    </div>
                </div>

                <svg viewBox="0 0 1000 180" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="studentAreaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#d946ef" stop-opacity="0.22" />
                            <stop offset="60%" stop-color="#a855f7" stop-opacity="0.06" />
                            <stop offset="100%" stop-color="#0e0f14" stop-opacity="0.0" />
                        </linearGradient>
                        <filter id="neonGlowStudent" x="-20%" y="-20%" width="140%" height="140%">
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

                    <path d="M 0,55 
                             C 80,60 140,85 200,80 
                             C 260,75 320,105 380,95 
                             C 440,85 480,70 540,65 
                             C 600,60 660,78 720,70 
                             C 780,62 840,110 900,100 
                             C 950,92 980,85 1000,85 
                             L 1000,180 L 0,180 Z" 
                          fill="url(#studentAreaGrad)" />

                    <path d="M 0,55 
                             C 80,60 140,85 200,80 
                             C 260,75 320,105 380,95 
                             C 440,85 480,70 540,65 
                             C 600,60 660,78 720,70 
                             C 780,62 840,110 900,100 
                             C 950,92 980,85 1000,85" 
                          fill="none" 
                          stroke="#e879f9" 
                          stroke-width="2.5" 
                          filter="url(#neonGlowStudent)" />

                    <circle cx="540" cy="65" r="5" fill="#f472b6" stroke="#ffffff" stroke-width="2" filter="url(#neonGlowStudent)" />
                </svg>
            </div>

            <div class="chart-axis-labels">
                <span>Mon</span>
                <span>Tue</span>
                <span>Wed</span>
                <span>Thu</span>
                <span class="text-white fw-bold">Fri (Today)</span>
                <span>Sat</span>
                <span>Sun</span>
            </div>
        </section>

        <!-- Attendance Ledger Snapshot Table (Clean Dark) -->
        <section class="helios-table-wrapper">
            <div class="p-3 px-4 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid var(--border-subtle); background: #14151a;">
                <div>
                    <h6 class="text-white fw-bold mb-0">Daily Parking History Log</h6>
                    <span class="text-muted small">Your official check-in ledger for college gate security</span>
                </div>
                <a href="student_daily_status.php" class="btn-helios-pill-sm">
                    <i class="bi bi-clock-history"></i> Full History
                </a>
            </div>

            <div class="table-responsive">
                <table class="table-dark-custom" id="studentHistoryTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle Plate</th>
                            <th>Vehicle Type</th>
                            <th>Arrival Status</th>
                            <th>Entry Time</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historyRecords)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No parking logs found. Once you mark your vehicle status, it appears here.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historyRecords as $row): ?>
                                <tr>
                                    <td class="text-white fw-semibold"><?= format_date($row['parking_date']) ?></td>
                                    <td><span class="vehicle-plate"><?= e($row['registration_number']) ?></span></td>
                                    <td class="text-muted small">
                                        <i class="bi <?= get_vehicle_icon($row['vehicle_type']) ?> me-1"></i>
                                        <?= e($row['vehicle_type']) ?>
                                    </td>
                                    <td><?= render_status_badge($row['arrival_status']) ?></td>
                                    <td class="font-monospace text-muted small"><?= $row['entry_time'] ? format_time($row['entry_time']) : '—' ?></td>
                                    <td class="text-muted small"><?= e($row['remarks'] ?: '—') ?></td>
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
