<?php
/**
 * CampusPark – Admin Digital Parking Register Book & Attendance Reports
 * Modern Dark Helios Layout: Replaces traditional physical college parking register notebook with a digital, searchable, and exportable ledger.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_admin();

$admin = get_logged_admin();
$db = getDB();

$pageTitle = 'Digital Parking Register & Reports – ' . APP_NAME;
$activeNav = 'admin_register';
$sidebarActive = 'register';

$today = date('Y-m-d');

// Handle Edit Record Submission (Security Gate Override)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_parking_record') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Security validation failed.');
    } else {
        $recordId  = (int)($_POST['record_id'] ?? 0);
        $newStatus = trim($_POST['arrival_status'] ?? '');
        $entryTime = trim($_POST['entry_time'] ?? '');
        $remarks   = trim($_POST['remarks'] ?? '');

        if ($db && $recordId > 0 && in_array($newStatus, ['brought', 'not_brought'])) {
            $timeVal = ($newStatus === 'brought') ? (!empty($entryTime) ? $entryTime : date('H:i:s')) : null;
            $stmt = $db->prepare("
                UPDATE parking_records 
                SET arrival_status = ?, entry_time = ?, remarks = ?, updated_at = NOW()
                WHERE record_id = ?
            ");
            if ($stmt->execute([$newStatus, $timeVal, $remarks, $recordId])) {
                set_flash('success', "Register record #{$recordId} updated successfully.");
            }
        }
    }
    header("Location: admin_parking_records.php?" . http_build_query($_GET));
    exit;
}

// Filter Parameters
$filterDateMode   = trim($_GET['date_mode'] ?? 'today');
$filterDateSingle = trim($_GET['date'] ?? $today);
$filterDateFrom   = trim($_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')));
$filterDateTo     = trim($_GET['date_to'] ?? $today);
$statusFilter     = trim($_GET['status'] ?? '');
$courseFilter     = trim($_GET['course'] ?? '');
$deptFilter       = trim($_GET['department'] ?? '');
$vehicleTypeFilter = trim($_GET['vehicle_type'] ?? '');
$searchFilter     = trim($_GET['search'] ?? '');

// Resolve Dates based on mode
if ($filterDateMode === 'today') {
    $dateCondition = "pr.parking_date = :f_date";
    $dateParams = [':f_date' => $today];
    $dateDisplayTitle = "Today (" . format_date($today) . ")";
} elseif ($filterDateMode === 'yesterday') {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $dateCondition = "pr.parking_date = :f_date";
    $dateParams = [':f_date' => $yesterday];
    $dateDisplayTitle = "Yesterday (" . format_date($yesterday) . ")";
} elseif ($filterDateMode === 'all_time') {
    $dateCondition = "1=1";
    $dateParams = [];
    $dateDisplayTitle = "All Historical Dates";
} else {
    $filterDateMode = 'range';
    $dateCondition = "pr.parking_date BETWEEN :f_from AND :f_to";
    $dateParams = [':f_from' => $filterDateFrom, ':f_to' => $filterDateTo];
    $dateDisplayTitle = format_date($filterDateFrom) . " to " . format_date($filterDateTo);
}

// Build SQL where clauses
$where = [$dateCondition];
$params = $dateParams;

if (!empty($statusFilter)) {
    $where[] = "pr.arrival_status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($courseFilter)) {
    $where[] = "s.course = :course";
    $params[':course'] = $courseFilter;
}

if (!empty($deptFilter)) {
    $where[] = "s.department = :dept";
    $params[':dept'] = $deptFilter;
}

if (!empty($vehicleTypeFilter)) {
    $where[] = "v.vehicle_type = :vtype";
    $params[':vtype'] = $vehicleTypeFilter;
}

if (!empty($searchFilter)) {
    $where[] = "(s.full_name LIKE :s1 OR s.roll_number LIKE :s2 OR v.registration_number LIKE :s3 OR s.contact_number LIKE :s4)";
    $term = '%' . $searchFilter . '%';
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
}

$whereSql = implode(' AND ', $where);

$records = [];
$statsBrought = 0;
$statsNotBrought = 0;

if ($db) {
    $sql = "
        SELECT 
            pr.record_id, pr.student_id, pr.vehicle_id, pr.parking_date, pr.arrival_status, pr.entry_time, pr.remarks, pr.updated_at,
            s.roll_number, s.full_name, s.course, s.department, s.year_of_study, s.contact_number, s.email,
            v.vehicle_type, v.registration_number, v.vehicle_model, v.colour, v.photo_url
        FROM parking_records pr
        JOIN students s ON pr.student_id = s.student_id
        JOIN vehicles v ON pr.vehicle_id = v.vehicle_id
        WHERE {$whereSql}
        ORDER BY pr.parking_date DESC, pr.entry_time DESC, pr.record_id DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    foreach ($records as $r) {
        if ($r['arrival_status'] === 'brought') {
            $statsBrought++;
        } else {
            $statsNotBrought++;
        }
    }
}

// Prepare export URL with same parameters
$exportQuery = $_GET;
$exportUrl = "admin_export.php?" . http_build_query($exportQuery);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Helios Style Rounded Dashboard Frame -->
<div class="helios-frame">
    <!-- Admin Sidebar -->
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="helios-main">
        <!-- Print-only header -->
        <div class="d-none d-print-block mb-4">
            <h3 class="fw-bold mb-1">UNIVERSITY CAMPUS SECURITY</h3>
            <h4 class="mb-1">DAILY VEHICLE PARKING REGISTER BOOK</h4>
            <p class="mb-0 text-muted">Period: <?= e($dateDisplayTitle) ?> &bull; CampusPark Digital System</p>
            <hr>
        </div>

        <!-- Top Bar: Greeting & Actions -->
        <header class="helios-topbar no-print">
            <div class="topbar-header-row">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggleBtn" type="button" aria-label="Toggle Navigation">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="topbar-greeting">
                        <h2>Digital Parking Register Book</h2>
                        <p>Official ledger & gate logs &bull; Active: <?= e($dateDisplayTitle) ?></p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <button type="button" class="btn-helios-outline" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Sheet
                    </button>
                    <a href="<?= e($exportUrl) ?>" class="btn-helios-primary">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                </div>
            </div>

            <!-- Subrow: Filter Pills & Search -->
            <div class="topbar-subrow">
                <div class="filter-pills-group">
                    <a href="admin_dashboard.php" class="filter-pill-btn">Dashboard</a>
                    <a href="admin_parking_records.php" class="filter-pill-btn active">Register Book</a>
                    <a href="admin_students_vehicles.php" class="filter-pill-btn">Students</a>
                </div>

                <div class="command-search-pill">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" placeholder="Live filter visible rows..." data-table-search="#officialRegisterTable" autocomplete="off">
                </div>
            </div>
        </header>

        <!-- Advanced Filter Box (Clean Dark Card) -->
        <div class="helios-card no-print">
            <form method="GET" action="admin_parking_records.php">
                <!-- Quick Date Pills -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-3" style="border-bottom: 1px solid var(--border-subtle);">
                    <span class="small fw-semibold text-muted text-uppercase me-2">Date Quick Filter:</span>
                    <a href="admin_parking_records.php?date_mode=today" class="btn-helios-pill-sm <?= ($filterDateMode === 'today') ? 'bg-primary text-white border-primary' : '' ?>">
                        <i class="bi bi-calendar-check me-1"></i> Today (<?= date('d M') ?>)
                    </a>
                    <a href="admin_parking_records.php?date_mode=yesterday" class="btn-helios-pill-sm <?= ($filterDateMode === 'yesterday') ? 'bg-primary text-white border-primary' : '' ?>">
                        <i class="bi bi-calendar-minus me-1"></i> Yesterday
                    </a>
                    <a href="admin_parking_records.php?date_mode=range&date_from=<?= date('Y-m-d', strtotime('-7 days')) ?>&date_to=<?= $today ?>" class="btn-helios-pill-sm <?= ($filterDateMode === 'range') ? 'bg-primary text-white border-primary' : '' ?>">
                        <i class="bi bi-calendar-range me-1"></i> Past 7 Days
                    </a>
                    <a href="admin_parking_records.php?date_mode=all_time" class="btn-helios-pill-sm <?= ($filterDateMode === 'all_time') ? 'bg-primary text-white border-primary' : '' ?>">
                        <i class="bi bi-clock-history me-1"></i> All-Time History
                    </a>
                </div>

                <div class="row g-3">
                    <input type="hidden" name="date_mode" value="<?= e($filterDateMode) ?>">

                    <?php if ($filterDateMode === 'range'): ?>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-dark-label">From Date</label>
                            <input type="date" name="date_from" class="form-dark-control" value="<?= e($filterDateFrom) ?>">
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-dark-label">To Date</label>
                            <input type="date" name="date_to" class="form-dark-control" value="<?= e($filterDateTo) ?>">
                        </div>
                    <?php endif; ?>

                    <div class="col-md-3">
                        <label class="form-dark-label">Search Keyword</label>
                        <input type="text" name="search" class="form-dark-control" placeholder="Name, Roll No, Plate..." value="<?= e($searchFilter) ?>">
                    </div>

                    <div class="col-sm-6 col-md-2">
                        <label class="form-dark-label">Arrival Status</label>
                        <select name="status" class="form-dark-control">
                            <option value="" class="bg-dark text-white">All Statuses</option>
                            <option value="brought" class="bg-dark text-white" <?= ($statusFilter === 'brought') ? 'selected' : '' ?>>Brought</option>
                            <option value="not_brought" class="bg-dark text-white" <?= ($statusFilter === 'not_brought') ? 'selected' : '' ?>>Not Brought</option>
                        </select>
                    </div>

                    <div class="col-sm-6 col-md-2">
                        <label class="form-dark-label">Course</label>
                        <select name="course" class="form-dark-control">
                            <option value="" class="bg-dark text-white">All Courses</option>
                            <?php foreach ($COURSES as $c): ?>
                                <option value="<?= e($c) ?>" class="bg-dark text-white" <?= ($courseFilter === $c) ? 'selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <label class="form-dark-label">Vehicle Type</label>
                        <select name="vehicle_type" class="form-dark-control">
                            <option value="" class="bg-dark text-white">All Types</option>
                            <?php foreach ($VEHICLE_TYPES as $vt): ?>
                                <option value="<?= e($vt) ?>" class="bg-dark text-white" <?= ($vehicleTypeFilter === $vt) ? 'selected' : '' ?>><?= e($vt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 d-flex flex-wrap align-items-center gap-2 pt-2">
                        <button type="submit" class="btn-glowing-pill">
                            <i class="bi bi-funnel me-1"></i> Apply Filters
                        </button>
                        <a href="admin_parking_records.php" class="btn-helios-outline">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>

                        <div class="ms-auto d-flex align-items-center gap-2 small">
                            <span class="badge" style="background: var(--bg-pill); color: var(--text-secondary); border: 1px solid var(--border-subtle);">
                                Total: <strong><?= count($records) ?></strong>
                            </span>
                            <span class="badge" style="background: rgba(52, 211, 153, 0.12); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3);">
                                Brought: <strong><?= $statsBrought ?></strong>
                            </span>
                            <span class="badge" style="background: rgba(248, 113, 113, 0.12); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3);">
                                Not Brought: <strong><?= $statsNotBrought ?></strong>
                            </span>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- The Official Register Book Table -->
        <section class="helios-table-wrapper">
            <div class="p-3 px-4 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid var(--border-subtle); background: #14151a;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-bookmark-fill text-warning fs-5"></i>
                    <div>
                        <h6 class="text-white fw-bold mb-0">Official Digital Parking Register</h6>
                        <span class="text-muted small">Period: <?= e($dateDisplayTitle) ?> &bull; <?= count($records) ?> entries</span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 no-print">
                    <a href="<?= e($exportUrl) ?>" class="btn-helios-pill-sm text-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-dark-custom" id="officialRegisterTable">
                    <thead>
                        <tr>
                            <th># Token</th>
                            <th>Date & Time</th>
                            <th>Student ID & Name</th>
                            <th>Course & Dept</th>
                            <th>License Plate</th>
                            <th>Vehicle Type & Model</th>
                            <th>Arrival Status</th>
                            <th>Phone</th>
                            <th>Remarks</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
                                    No parking records found matching the specified date or filter parameters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td class="font-monospace text-muted small fw-bold">
                                        #<?= str_pad((string)$counter++, 3, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white small"><?= format_date($r['parking_date']) ?></div>
                                        <span class="font-monospace text-muted small">
                                            <?= $r['entry_time'] ? format_time($r['entry_time']) : '—' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="d-block text-white"><?= e($r['full_name']) ?></strong>
                                        <span class="text-info font-monospace small"><?= e($r['roll_number']) ?></span>
                                    </td>
                                    <td class="small text-muted">
                                        <div class="text-white"><?= e($r['course']) ?> (<?= e($r['year_of_study']) ?>)</div>
                                        <div><?= e($r['department']) ?></div>
                                    </td>
                                    <td>
                                        <span class="vehicle-plate"><?= e($r['registration_number']) ?></span>
                                    </td>
                                    <td class="small text-muted">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= htmlspecialchars(get_vehicle_photo($r['photo_url'] ?? null, $r['vehicle_type'])) ?>" alt="" class="vehicle-thumb-sm">
                                            <div>
                                                <div class="text-white fw-semibold"><?= e($r['vehicle_type']) ?></div>
                                                <div><?= e($r['vehicle_model'] ?: 'Standard') ?> &bull; <?= e($r['colour'] ?: 'N/A') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= render_status_badge($r['arrival_status']) ?>
                                    </td>
                                    <td class="font-monospace small text-muted">
                                        <?= e($r['contact_number']) ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= e($r['remarks'] ?: '—') ?>
                                    </td>
                                    <td class="no-print">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editRecordModal_<?= $r['record_id'] ?>" title="Edit Entry Status or Remarks">
                                            <i class="bi bi-pencil text-light"></i>
                                        </button>

                                        <!-- Modal: Edit Parking Record -->
                                        <div class="modal fade" id="editRecordModal_<?= $r['record_id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content modal-content-dark">
                                                    <div class="modal-header modal-header-dark">
                                                        <h6 class="modal-title fw-bold text-white">
                                                            <i class="bi bi-pencil-square me-2 text-warning"></i>
                                                            Edit Gate Entry #<?= (int)$r['record_id'] ?>
                                                        </h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="POST" action="admin_parking_records.php?<?= e(http_build_query($_GET)) ?>">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="edit_parking_record">
                                                        <input type="hidden" name="record_id" value="<?= (int)$r['record_id'] ?>">

                                                        <div class="modal-body p-4">
                                                            <div class="p-3 rounded-3 mb-3" style="background: #121318; border: 1px solid var(--border-subtle);">
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span class="text-muted small">Student:</span>
                                                                    <strong class="text-white small"><?= e($r['full_name']) ?> (<?= e($r['roll_number']) ?>)</strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-muted small">Vehicle Plate:</span>
                                                                    <span class="vehicle-plate py-0 px-2 small"><?= e($r['registration_number']) ?></span>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Arrival Status</label>
                                                                <select name="arrival_status" class="form-dark-control" required>
                                                                    <option value="brought" class="bg-dark text-white" <?= ($r['arrival_status'] === 'brought') ? 'selected' : '' ?>>Brought to College</option>
                                                                    <option value="not_brought" class="bg-dark text-white" <?= ($r['arrival_status'] === 'not_brought') ? 'selected' : '' ?>>Not Brought</option>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Entry Timestamp</label>
                                                                <input type="time" name="entry_time" class="form-dark-control" value="<?= e($r['entry_time'] ?? date('H:i')) ?>">
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Security Remarks / Parking Bay</label>
                                                                <input type="text" name="remarks" class="form-dark-control" value="<?= e($r['remarks'] ?? '') ?>">
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer modal-footer-dark">
                                                            <button type="button" class="btn-helios-outline" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn-glowing-pill">Save Gate Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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
