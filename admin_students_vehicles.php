<?php
/**
 * CampusPark – Admin Student & Vehicle Records Directory
 * Modern Dark Helios Layout: Complete searchable and filterable database of students, courses, departments, and registered vehicles
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_admin();

$admin = get_logged_admin();
$db = getDB();

$pageTitle = 'Students & Vehicles Directory – ' . APP_NAME;
$activeNav = 'admin_students';
$sidebarActive = 'students_vehicles';

// Handle Student Status Toggle (Active / Deactive)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_student_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Security validation failed.');
    } else {
        $stId = (int)($_POST['student_id'] ?? 0);
        $curr = (int)($_POST['current_status'] ?? 1);
        $newStatus = ($curr === 1) ? 0 : 1;

        if ($db && $stId > 0) {
            $stmt = $db->prepare("UPDATE students SET is_active = ? WHERE student_id = ?");
            if ($stmt->execute([$newStatus, $stId])) {
                $statusWord = ($newStatus === 1) ? 'activated' : 'deactivated';
                set_flash('success', "Student account has been {$statusWord}.");
            }
        }
    }
    header("Location: admin_students_vehicles.php");
    exit;
}

// Handle Student Edit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_student') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Security token failed.');
    } else {
        $stId     = (int)($_POST['student_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $contact  = trim($_POST['contact_number'] ?? '');
        $course   = trim($_POST['course'] ?? '');
        $dept     = trim($_POST['department'] ?? '');
        $year     = trim($_POST['year_of_study'] ?? '');

        if ($db && $stId > 0 && !empty($fullName)) {
            $stmt = $db->prepare("
                UPDATE students 
                SET full_name = ?, contact_number = ?, course = ?, department = ?, year_of_study = ?
                WHERE student_id = ?
            ");
            if ($stmt->execute([$fullName, $contact, $course, $dept, $year, $stId])) {
                set_flash('success', "Student '{$fullName}' updated successfully.");
            }
        }
    }
    header("Location: admin_students_vehicles.php");
    exit;
}

// Handle Admin Adding a Vehicle to a Student directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_add_vehicle') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Security token failed.');
    } else {
        ensure_vehicle_photo_column();
        $stId        = (int)($_POST['student_id'] ?? 0);
        $vehicleType = trim($_POST['vehicle_type'] ?? '');
        $regNumber   = strtoupper(preg_replace('/\s+/', ' ', trim($_POST['registration_number'] ?? '')));
        $model       = trim($_POST['vehicle_model'] ?? '');
        $colour      = trim($_POST['colour'] ?? '');

        // Handle Vehicle Photo Upload if provided
        $photoUrl = null;
        if (isset($_FILES['vehicle_photo']) && $_FILES['vehicle_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handle_vehicle_photo_upload($_FILES['vehicle_photo']);
            if (is_array($uploadResult) && !empty($uploadResult['success'])) {
                $photoUrl = $uploadResult['path'];
            } elseif (is_string($uploadResult)) {
                $photoUrl = $uploadResult;
            } elseif (is_array($uploadResult) && !empty($uploadResult['error'])) {
                set_flash('danger', $uploadResult['error']);
            }
        }

        if ($db && $stId > 0 && !empty($regNumber) && in_array($vehicleType, $VEHICLE_TYPES)) {
            // Check duplicate plate
            $dupStmt = $db->prepare("SELECT vehicle_id FROM vehicles WHERE registration_number = ? AND is_active = 1");
            $dupStmt->execute([$regNumber]);
            if ($dupStmt->fetch()) {
                set_flash('danger', "Vehicle registration number '{$regNumber}' is already registered in the system.");
            } else {
                $ins = $db->prepare("
                    INSERT INTO vehicles (student_id, vehicle_type, registration_number, vehicle_model, colour, photo_url, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                if ($ins->execute([$stId, $vehicleType, $regNumber, $model, $colour, $photoUrl])) {
                    set_flash('success', "Vehicle '{$regNumber}' registered successfully with photo for student #{$stId}.");
                }
            }
        }
    }
    header("Location: admin_students_vehicles.php");
    exit;
}

// Filtering Parameters
$searchFilter     = trim($_GET['search'] ?? '');
$courseFilter     = trim($_GET['course'] ?? '');
$deptFilter       = trim($_GET['department'] ?? '');
$yearFilter       = trim($_GET['year'] ?? '');
$vehicleTypeFilter = trim($_GET['vehicle_type'] ?? '');
$statusFilter     = trim($_GET['status'] ?? '');

// Build query
$whereClauses = ["1=1"];
$params = [];

if (!empty($searchFilter)) {
    $whereClauses[] = "(s.full_name LIKE :s1 OR s.roll_number LIKE :s2 OR s.email LIKE :s3 OR s.contact_number LIKE :s4 OR v.registration_number LIKE :s5)";
    $term = '%' . $searchFilter . '%';
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
    $params[':s5'] = $term;
}

if (!empty($courseFilter)) {
    $whereClauses[] = "s.course = :course";
    $params[':course'] = $courseFilter;
}

if (!empty($deptFilter)) {
    $whereClauses[] = "s.department = :dept";
    $params[':dept'] = $deptFilter;
}

if (!empty($yearFilter)) {
    $whereClauses[] = "s.year_of_study = :year";
    $params[':year'] = $yearFilter;
}

if (!empty($vehicleTypeFilter)) {
    $whereClauses[] = "v.vehicle_type = :vtype";
    $params[':vtype'] = $vehicleTypeFilter;
}

if ($statusFilter !== '') {
    $whereClauses[] = "s.is_active = :status";
    $params[':status'] = (int)$statusFilter;
}

$whereSql = implode(' AND ', $whereClauses);

$studentsList = [];
if ($db) {
    $sql = "
        SELECT 
            s.*,
            COUNT(DISTINCT v.vehicle_id) as total_vehicles
        FROM students s
        LEFT JOIN vehicles v ON s.student_id = v.student_id AND v.is_active = 1
        WHERE {$whereSql}
        GROUP BY s.student_id
        ORDER BY s.student_id DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $studentsList = $stmt->fetchAll();

    foreach ($studentsList as &$st) {
        $vStmt = $db->prepare("SELECT * FROM vehicles WHERE student_id = ? AND is_active = 1 ORDER BY vehicle_id DESC");
        $vStmt->execute([$st['student_id']]);
        $st['vehicles'] = $vStmt->fetchAll();
    }
    unset($st);
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Helios Style Rounded Dashboard Frame -->
<div class="helios-frame">
    <!-- Admin Sidebar -->
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

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
                        <h2>Students & Vehicles Directory</h2>
                        <p>Master directory of enrolled students and authorized parking permits &bull; <?= count($studentsList) ?> registered profiles</p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <a href="admin_parking_records.php" class="btn-helios-outline">
                        <i class="bi bi-journal-bookmark"></i> Register Book
                    </a>
                    <a href="admin_export.php" class="btn-helios-primary">
                        <i class="bi bi-download"></i> Export Fleet CSV
                    </a>
                </div>
            </div>

            <!-- Subrow: Filter Pills & Search Pill -->
            <div class="topbar-subrow">
                <div class="filter-pills-group">
                    <a href="admin_dashboard.php" class="filter-pill-btn">Dashboard</a>
                    <a href="admin_parking_records.php" class="filter-pill-btn">Register Book</a>
                    <a href="admin_students_vehicles.php" class="filter-pill-btn active">Directory (<?= count($studentsList) ?>)</a>
                </div>

                <div class="command-search-pill">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" placeholder="Search student, plate, phone..." data-table-search="#studentsMasterTable" autocomplete="off">
                </div>
            </div>
        </header>

        <!-- Filter Box (Clean Dark Card) -->
        <div class="helios-card">
            <form method="GET" action="admin_students_vehicles.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-dark-label">Search Query</label>
                    <input type="text" name="search" class="form-dark-control" placeholder="Name, Roll No., Plate, Phone..." value="<?= e($searchFilter) ?>">
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
                    <label class="form-dark-label">Department</label>
                    <select name="department" class="form-dark-control">
                        <option value="" class="bg-dark text-white">All Departments</option>
                        <?php foreach ($DEPARTMENTS as $dept): ?>
                            <option value="<?= e($dept) ?>" class="bg-dark text-white" <?= ($deptFilter === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-sm-6 col-md-2">
                    <label class="form-dark-label">Vehicle Type</label>
                    <select name="vehicle_type" class="form-dark-control">
                        <option value="" class="bg-dark text-white">All Vehicle Types</option>
                        <?php foreach ($VEHICLE_TYPES as $vt): ?>
                            <option value="<?= e($vt) ?>" class="bg-dark text-white" <?= ($vehicleTypeFilter === $vt) ? 'selected' : '' ?>><?= e($vt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-sm-6 col-md-2">
                    <label class="form-dark-label">Account Status</label>
                    <select name="status" class="form-dark-control">
                        <option value="" class="bg-dark text-white">All Statuses</option>
                        <option value="1" class="bg-dark text-white" <?= ($statusFilter === '1') ? 'selected' : '' ?>>Active Only</option>
                        <option value="0" class="bg-dark text-white" <?= ($statusFilter === '0') ? 'selected' : '' ?>>Deactivated Only</option>
                    </select>
                </div>

                <div class="col-12 d-flex flex-wrap align-items-center gap-2 pt-2">
                    <button type="submit" class="btn-glowing-pill">
                        <i class="bi bi-funnel me-1"></i> Filter Directory
                    </button>
                    <a href="admin_students_vehicles.php" class="btn-helios-outline">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                    <span class="ms-auto text-muted small">
                        Showing <strong><?= count($studentsList) ?></strong> student accounts
                    </span>
                </div>
            </form>
        </div>

        <!-- Master Directory Table -->
        <section class="helios-table-wrapper">
            <div class="p-3 px-4 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid var(--border-subtle); background: #14151a;">
                <h6 class="text-white fw-bold mb-0">Master Student & Permits Directory</h6>
                <span class="badge" style="background: var(--bg-pill); color: var(--text-secondary); border: 1px solid var(--border-subtle);">
                    <?= count($studentsList) ?> Students
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-dark-custom" id="studentsMasterTable">
                    <thead>
                        <tr>
                            <th>Student ID & Name</th>
                            <th>Academic Branch</th>
                            <th>Contact & Email</th>
                            <th>Registered Vehicles</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studentsList)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-search fs-3 d-block mb-2"></i>
                                    No student or vehicle records match your selected filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($studentsList as $st): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="watchlist-icon-circle">
                                                <?= strtoupper(substr($st['full_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong class="d-block text-white"><?= e($st['full_name']) ?></strong>
                                                <span class="text-info font-monospace small"><?= e($st['roll_number']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-white small"><?= e($st['course']) ?> (<?= e($st['year_of_study']) ?>)</strong>
                                        <div class="text-muted small"><?= e($st['department']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-white"><i class="bi bi-telephone me-1 text-primary"></i> <?= e($st['contact_number']) ?></div>
                                        <div class="small text-muted"><i class="bi bi-envelope me-1"></i> <?= e($st['email']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (empty($st['vehicles'])): ?>
                                            <span class="text-muted small">No vehicles registered</span>
                                        <?php else: ?>
                                            <div class="d-flex flex-column gap-2">
                                                <?php foreach ($st['vehicles'] as $v): ?>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?= htmlspecialchars(get_vehicle_photo($v['photo_url'] ?? null, $v['vehicle_type'])) ?>" alt="" class="vehicle-thumb-sm">
                                                        <div>
                                                            <span class="vehicle-plate"><?= e($v['registration_number']) ?></span>
                                                            <span class="badge" style="background: var(--bg-pill); color: var(--text-secondary); border: 1px solid var(--border-subtle); font-size: 0.7rem;"><?= e($v['vehicle_type']) ?></span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$st['is_active'] === 1): ?>
                                            <span class="badge-brought"><i class="bi bi-check2-circle"></i> Active</span>
                                        <?php else: ?>
                                            <span class="badge-not-brought"><i class="bi bi-slash-circle"></i> Deactivated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <!-- Edit Student Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" data-bs-toggle="modal" data-bs-target="#editStudentModal_<?= $st['student_id'] ?>" title="Edit Student">
                                                <i class="bi bi-pencil text-light"></i>
                                            </button>

                                            <!-- Add Vehicle Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" data-bs-toggle="modal" data-bs-target="#addVehicleModal_<?= $st['student_id'] ?>" title="Register Vehicle for Student">
                                                <i class="bi bi-plus-circle"></i>
                                            </button>

                                            <!-- Toggle Active Status Form -->
                                            <form method="POST" action="admin_students_vehicles.php" class="d-inline" onsubmit="return confirm('Toggle account active status?');">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="toggle_student_status">
                                                <input type="hidden" name="student_id" value="<?= (int)$st['student_id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= (int)$st['is_active'] ?>">
                                                <button type="submit" class="btn btn-sm <?= ((int)$st['is_active'] === 1) ? 'btn-outline-danger' : 'btn-outline-success' ?> py-1 px-2" title="<?= ((int)$st['is_active'] === 1) ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="bi <?= ((int)$st['is_active'] === 1) ? 'bi-lock' : 'bi-unlock' ?>"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Modal 1: Edit Student -->
                                        <div class="modal fade" id="editStudentModal_<?= $st['student_id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content modal-content-dark">
                                                    <form method="POST" action="admin_students_vehicles.php">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="edit_student">
                                                        <input type="hidden" name="student_id" value="<?= (int)$st['student_id'] ?>">

                                                        <div class="modal-header modal-header-dark">
                                                            <h6 class="modal-title fw-bold text-white">Edit Student: <?= e($st['full_name']) ?></h6>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body p-4 text-start">
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Full Name</label>
                                                                <input type="text" name="full_name" class="form-dark-control" value="<?= e($st['full_name']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Contact Phone</label>
                                                                <input type="tel" name="contact_number" class="form-dark-control" value="<?= e($st['contact_number']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Course</label>
                                                                <select name="course" class="form-dark-control">
                                                                    <?php foreach ($COURSES as $c): ?>
                                                                        <option value="<?= e($c) ?>" class="bg-dark text-white" <?= ($st['course'] === $c) ? 'selected' : '' ?>><?= e($c) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Department</label>
                                                                <select name="department" class="form-dark-control">
                                                                    <?php foreach ($DEPARTMENTS as $dept): ?>
                                                                        <option value="<?= e($dept) ?>" class="bg-dark text-white" <?= ($st['department'] === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Year of Study</label>
                                                                <select name="year_of_study" class="form-dark-control">
                                                                    <?php foreach ($YEARS as $yr): ?>
                                                                        <option value="<?= e($yr) ?>" class="bg-dark text-white" <?= ($st['year_of_study'] === $yr) ? 'selected' : '' ?>><?= e($yr) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer modal-footer-dark">
                                                            <button type="button" class="btn-helios-outline" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn-glowing-pill">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal 2: Add Vehicle Directly -->
                                        <div class="modal fade" id="addVehicleModal_<?= $st['student_id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content modal-content-dark">
                                                    <form method="POST" action="admin_students_vehicles.php" enctype="multipart/form-data">
                                                        <?= csrf_input() ?>
                                                        <input type="hidden" name="action" value="admin_add_vehicle">
                                                        <input type="hidden" name="student_id" value="<?= (int)$st['student_id'] ?>">

                                                        <div class="modal-header modal-header-dark">
                                                            <h6 class="modal-title fw-bold text-white">Register Vehicle for <?= e($st['full_name']) ?></h6>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body p-4 text-start">
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Vehicle Type *</label>
                                                                <select name="vehicle_type" class="form-dark-control" required>
                                                                    <?php foreach ($VEHICLE_TYPES as $vt): ?>
                                                                        <option value="<?= e($vt) ?>" class="bg-dark text-white"><?= e($vt) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Registration Number (Plate) *</label>
                                                                <input type="text" name="registration_number" class="form-dark-control text-uppercase font-monospace" placeholder="e.g. MH 12 AB 9999" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Model (Brand & Name)</label>
                                                                <input type="text" name="vehicle_model" class="form-dark-control" placeholder="e.g. Honda Activa 6G">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Colour</label>
                                                                <input type="text" name="colour" class="form-dark-control" placeholder="e.g. Black / Blue">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-dark-label">Vehicle Photograph (Bike / Car / Scooter)</label>
                                                                <input type="file" name="vehicle_photo" class="form-dark-control" accept="image/jpeg,image/png,image/webp">
                                                                <span class="text-muted small" style="font-size: 0.74rem;">JPG, PNG, WebP up to 5MB</span>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer modal-footer-dark">
                                                            <button type="button" class="btn-helios-outline" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn-glowing-pill">Register Vehicle</button>
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
