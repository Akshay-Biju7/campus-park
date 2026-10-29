<?php
/**
 * CampusPark – Daily Vehicle Arrival Status Page
 * Modern Dark Helios Layout: Allows students to mark whether they brought their registered vehicle to college today
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_student();

$student = get_logged_student();
$studentId = (int)$student['student_id'];
$today = date('Y-m-d');

$pageTitle = 'Daily Parking Status – ' . APP_NAME;
$activeNav = 'daily_status';
$sidebarActive = 'daily_status';

$db = getDB();
$errors = [];

// Handle Status Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token expired or invalid. Please refresh and try again.';
    } else {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $status    = trim($_POST['arrival_status'] ?? '');
        $remarks   = trim($_POST['remarks'] ?? '');

        if ($vehicleId <= 0) {
            $errors[] = 'Please select a valid registered vehicle.';
        }

        if (!in_array($status, ['brought', 'not_brought'])) {
            $errors[] = 'Please choose either "Brought to College" or "Not Brought".';
        }

        if (empty($errors)) {
            $saved = set_daily_parking_status($studentId, $vehicleId, $status, $today, $remarks);
            if ($saved) {
                $statusLabel = ($status === 'brought') ? 'Brought to College' : 'Not Brought';
                set_flash('success', "Daily status recorded as '{$statusLabel}' for today (" . format_date($today) . ").");
                header("Location: student_daily_status.php");
                exit;
            } else {
                $errors[] = 'Failed to record daily status. Please ensure the vehicle is active on your profile.';
            }
        }
    }
}

// Fetch Student Vehicles with Today's Status
$vehicles = get_student_vehicles_with_status($studentId, $today);

// Fetch Student's Complete Parking Log History
$history = [];
if ($db) {
    $histStmt = $db->prepare("
        SELECT pr.*, v.registration_number, v.vehicle_type, v.vehicle_model, v.photo_url
        FROM parking_records pr
        JOIN vehicles v ON pr.vehicle_id = v.vehicle_id
        WHERE pr.student_id = ?
        ORDER BY pr.parking_date DESC, pr.updated_at DESC
        LIMIT 50
    ");
    $histStmt->execute([$studentId]);
    $history = $histStmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Helios Style Rounded Dashboard Frame -->
<div class="helios-frame">
    <!-- Student Sidebar -->
    <?php require_once __DIR__ . '/includes/student_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="helios-main">
        <!-- Top Bar: Greeting & Actions -->
        <header class="helios-topbar">
            <div class="topbar-header-row">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggleBtn" type="button" aria-label="Toggle Navigation">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="topbar-greeting">
                        <h2>Daily Parking Status</h2>
                        <p>Mark whether you brought your vehicle to campus today &bull; <?= date('l, d F Y') ?></p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <a href="student_vehicles.php?action=new" class="btn-helios-pill-sm">
                        <i class="bi bi-plus-lg"></i> Register Vehicle
                    </a>
                    <a href="student_dashboard.php" class="profile-pill">
                        <div class="profile-avatar"><?= strtoupper(substr($student['full_name'] ?? 'S', 0, 1)) ?></div>
                        <div class="profile-meta">
                            <span class="profile-name"><?= e($student['full_name']) ?></span>
                            <span class="profile-sub font-monospace"><?= e($student['roll_number']) ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Subrow: Quick Filter Pills -->
            <div class="topbar-subrow">
                <div class="filter-pills-group">
                    <a href="student_dashboard.php" class="filter-pill-btn">Overview</a>
                    <a href="student_daily_status.php" class="filter-pill-btn active">Mark Status</a>
                    <a href="student_vehicles.php" class="filter-pill-btn">Vehicles</a>
                </div>

                <div class="command-search-pill">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" placeholder="Filter attendance logs..." data-table-search="#statusHistoryTable" autocomplete="off">
                </div>
            </div>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm mb-3" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= implode(' ', $errors) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($vehicles)): ?>
            <div class="helios-card text-center py-5">
                <div class="watchlist-icon-circle mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.6rem; background: rgba(239, 68, 68, 0.15); color: #f87171; border-color: rgba(239, 68, 68, 0.3);">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
                <h4 class="text-white fw-bold mb-2">No Registered Vehicles Found</h4>
                <p class="text-muted small mb-4" style="max-width: 480px; margin: 0 auto;">
                    Before you can record daily attendance, you must register your bike, scooter, or car with campus security.
                </p>
                <div>
                    <a href="student_vehicles.php?action=new" class="btn-helios-primary">
                        <i class="bi bi-plus-lg me-1"></i> Register Vehicle Now
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Vehicle Status Cards Grid -->
            <div class="row g-4">
                <?php foreach ($vehicles as $veh): ?>
                    <div class="col-lg-6">
                        <div class="helios-card h-100">
                            <!-- Card Header -->
                            <div class="card-header-sleek">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="watchlist-icon-circle overflow-hidden p-0" style="border: 1px solid var(--border-subtle);">
                                        <img src="<?= htmlspecialchars(get_vehicle_photo($veh['photo_url'] ?? null, $veh['vehicle_type'])) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div>
                                        <h6 class="text-white fw-bold mb-0"><?= e($veh['vehicle_type']) ?> &bull; <?= e($veh['vehicle_model'] ?: 'Standard') ?></h6>
                                        <span class="text-muted small">Color: <?= e($veh['colour'] ?: 'Standard') ?></span>
                                    </div>
                                </div>
                                <span class="vehicle-plate"><?= e($veh['registration_number']) ?></span>
                            </div>

                            <div class="p-3 my-2 rounded-3 d-flex align-items-center justify-content-between" style="background: #14151a; border: 1px solid var(--border-subtle);">
                                <span class="text-muted small">Today's Recorded Status:</span>
                                <div><?= render_status_badge($veh['arrival_status']) ?></div>
                            </div>

                            <?php if ($veh['arrival_status'] === 'brought' && $veh['entry_time']): ?>
                                <div class="p-2 px-3 rounded-3 mb-3 small d-flex align-items-center justify-content-between" style="background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: #34d399;">
                                    <span><i class="bi bi-clock-history me-1"></i> Entry Time: <strong><?= format_time($veh['entry_time']) ?></strong></span>
                                    <span class="text-muted small"><?= e($veh['remarks'] ?: 'Campus Gate 01') ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Action Form -->
                            <form method="POST" action="student_daily_status.php" class="mt-2">
                                <?= csrf_input() ?>
                                <input type="hidden" name="vehicle_id" value="<?= (int)$veh['vehicle_id'] ?>">

                                <div class="mb-3">
                                    <label class="form-dark-label">Mark Status for Today</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="arrival_status" id="status_brought_<?= $veh['vehicle_id'] ?>" value="brought" <?= ($veh['arrival_status'] === 'brought' || empty($veh['arrival_status'])) ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-success w-100 py-2 rounded-pill small fw-semibold" for="status_brought_<?= $veh['vehicle_id'] ?>">
                                                <i class="bi bi-check-circle-fill me-1"></i> Brought
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="arrival_status" id="status_not_brought_<?= $veh['vehicle_id'] ?>" value="not_brought" <?= ($veh['arrival_status'] === 'not_brought') ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-danger w-100 py-2 rounded-pill small fw-semibold" for="status_not_brought_<?= $veh['vehicle_id'] ?>">
                                                <i class="bi bi-x-circle-fill me-1"></i> Not Brought
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-dark-label" for="remarks_<?= $veh['vehicle_id'] ?>">Optional Remarks</label>
                                    <input type="text" name="remarks" id="remarks_<?= $veh['vehicle_id'] ?>" class="form-dark-control" placeholder="e.g. Zone B Parking, Carpool, etc." value="<?= e($veh['remarks'] ?? '') ?>">
                                </div>

                                <button type="submit" name="submit_status" class="btn-glowing-pill w-100">
                                    <i class="bi bi-check2-circle me-1"></i> Save Daily Status
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Full Attendance History Ledger (Clean Dark) -->
        <section class="helios-table-wrapper mt-3">
            <div class="p-3 px-4 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid var(--border-subtle); background: #14151a;">
                <div>
                    <h6 class="text-white fw-bold mb-0">My Daily Attendance Ledger</h6>
                    <span class="text-muted small">Complete record of your daily college parking logs</span>
                </div>
                <span class="badge" style="background: var(--bg-pill); color: var(--text-secondary); border: 1px solid var(--border-subtle);">
                    <?= count($history) ?> Records Logged
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-dark-custom" id="statusHistoryTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle Type</th>
                            <th>Plate Number</th>
                            <th>Arrival Status</th>
                            <th>Entry Time</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No parking history found yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td class="text-white fw-semibold"><?= format_date($row['parking_date']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= htmlspecialchars(get_vehicle_photo($row['photo_url'] ?? null, $row['vehicle_type'])) ?>" alt="" class="vehicle-thumb-sm">
                                            <span class="text-muted small"><?= e($row['vehicle_type']) ?></span>
                                        </div>
                                    </td>
                                    <td><span class="vehicle-plate"><?= e($row['registration_number']) ?></span></td>
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
