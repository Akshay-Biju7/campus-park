<?php
/**
 * CampusPark – Student Vehicle Registration & Management
 * Modern Dark Helios Layout: Allows students to register new vehicles, update vehicle details, and prevent duplicate plate registrations.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_student();

$student = get_logged_student();
$studentId = (int)$student['student_id'];

$pageTitle = 'My Registered Vehicles – ' . APP_NAME;
$activeNav = 'student_dash';
$sidebarActive = 'vehicles';

$errors = [];
$editVehicle = null;
$showNewForm = (isset($_GET['action']) && $_GET['action'] === 'new');

$db = getDB();

// 1. Check if editing an existing vehicle
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($db) {
        $editStmt = $db->prepare("SELECT * FROM vehicles WHERE vehicle_id = ? AND student_id = ? AND is_active = 1");
        $editStmt->execute([$editId, $studentId]);
        $editVehicle = $editStmt->fetch();
        if ($editVehicle) {
            $showNewForm = true;
        }
    }
}

// 2. Handle Deactivation / Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deactivate_vehicle') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('danger', 'Security validation failed.');
    } else {
        $vId = (int)($_POST['vehicle_id'] ?? 0);
        if ($db && $vId > 0) {
            $delStmt = $db->prepare("UPDATE vehicles SET is_active = 0 WHERE vehicle_id = ? AND student_id = ?");
            if ($delStmt->execute([$vId, $studentId])) {
                set_flash('success', 'Vehicle has been successfully removed from your active registered list.');
            }
        }
    }
    header("Location: student_vehicles.php");
    exit;
}

// 3. Handle Add / Edit Vehicle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_vehicle'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        ensure_vehicle_photo_column();
        $vehicleType = trim($_POST['vehicle_type'] ?? '');
        $regNumber   = strtoupper(preg_replace('/\s+/', ' ', trim($_POST['registration_number'] ?? '')));
        $model       = trim($_POST['vehicle_model'] ?? '');
        $colour      = trim($_POST['colour'] ?? '');
        $vehicleId   = (int)($_POST['vehicle_id'] ?? 0);

        if (empty($vehicleType) || !in_array($vehicleType, $VEHICLE_TYPES)) {
            $errors[] = 'Please select a valid vehicle type.';
        }

        if (empty($regNumber)) {
            $errors[] = 'Vehicle registration number (license plate) is required.';
        } elseif (strlen($regNumber) < 4 || strlen($regNumber) > 25) {
            $errors[] = 'Vehicle registration number must be between 4 and 25 characters.';
        }

        // Handle Vehicle Photo Upload if provided
        $photoUrl = $editVehicle['photo_url'] ?? null;
        if (isset($_FILES['vehicle_photo']) && $_FILES['vehicle_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handle_vehicle_photo_upload($_FILES['vehicle_photo']);
            if (is_array($uploadResult) && !empty($uploadResult['success'])) {
                $photoUrl = $uploadResult['path'];
            } elseif (is_string($uploadResult)) {
                $photoUrl = $uploadResult;
            } elseif (is_array($uploadResult) && !empty($uploadResult['error'])) {
                $errors[] = $uploadResult['error'];
            }
        }

        if (empty($errors) && $db) {
            // Prevent duplicate vehicle registrations
            if ($vehicleId > 0) {
                $dupStmt = $db->prepare("SELECT vehicle_id, student_id FROM vehicles WHERE registration_number = ? AND vehicle_id != ? AND is_active = 1");
                $dupStmt->execute([$regNumber, $vehicleId]);
                $dup = $dupStmt->fetch();
            } else {
                $dupStmt = $db->prepare("SELECT vehicle_id, student_id FROM vehicles WHERE registration_number = ? AND is_active = 1");
                $dupStmt->execute([$regNumber]);
                $dup = $dupStmt->fetch();
            }

            if ($dup) {
                $errors[] = "A vehicle with registration number '{$regNumber}' is already registered in the college parking system.";
            } else {
                if ($vehicleId > 0) {
                    $updateStmt = $db->prepare("
                        UPDATE vehicles 
                        SET vehicle_type = ?, registration_number = ?, vehicle_model = ?, colour = ?, photo_url = ?
                        WHERE vehicle_id = ? AND student_id = ?
                    ");
                    $ok = $updateStmt->execute([$vehicleType, $regNumber, $model, $colour, $photoUrl, $vehicleId, $studentId]);
                    if ($ok) {
                        set_flash('success', "Vehicle '{$regNumber}' updated successfully with photo.");
                        header("Location: student_vehicles.php");
                        exit;
                    } else {
                        $errors[] = 'Could not update vehicle. Please try again.';
                    }
                } else {
                    $insertStmt = $db->prepare("
                        INSERT INTO vehicles (student_id, vehicle_type, registration_number, vehicle_model, colour, photo_url, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, 1)
                    ");
                    $ok = $insertStmt->execute([$studentId, $vehicleType, $regNumber, $model, $colour, $photoUrl]);
                    if ($ok) {
                        $newVehId = (int)$db->lastInsertId();
                        set_daily_parking_status($studentId, $newVehId, 'brought', date('Y-m-d'), 'Initial registration check-in');

                        set_flash('success', "Vehicle '{$regNumber}' registered successfully with photo! You can now mark its daily arrival status.");
                        header("Location: student_vehicles.php");
                        exit;
                    } else {
                        $errors[] = 'Failed to register vehicle. Please try again.';
                    }
                }
            }
        }
    }
}

// Fetch all active vehicles for this student
$myVehicles = [];
if ($db) {
    $stmt = $db->prepare("
        SELECT v.*, pr.arrival_status, pr.entry_time, pr.parking_date
        FROM vehicles v
        LEFT JOIN parking_records pr ON v.vehicle_id = pr.vehicle_id AND pr.parking_date = CURDATE()
        WHERE v.student_id = ? AND v.is_active = 1
        ORDER BY v.vehicle_id DESC
    ");
    $stmt->execute([$studentId]);
    $myVehicles = $stmt->fetchAll();
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
                        <h2>My Registered Vehicles</h2>
                        <p>Manage your campus parking permits & vehicle fleet &bull; <?= e($student['roll_number']) ?></p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <?php if ($showNewForm): ?>
                        <a href="student_vehicles.php" class="btn-helios-outline">
                            <i class="bi bi-arrow-left"></i> View Vehicles List
                        </a>
                    <?php else: ?>
                        <a href="student_vehicles.php?action=new" class="btn-glowing-pill">
                            <i class="bi bi-plus-lg"></i> Register New Vehicle
                        </a>
                    <?php endif; ?>

                    <a href="student_profile.php" class="profile-pill">
                        <div class="profile-avatar"><?= strtoupper(substr($student['full_name'] ?? 'S', 0, 1)) ?></div>
                        <div class="profile-meta">
                            <span class="profile-name"><?= e($student['full_name']) ?></span>
                            <span class="profile-sub font-monospace"><?= e($student['roll_number']) ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Subrow: Filter Pills -->
            <div class="topbar-subrow">
                <div class="filter-pills-group">
                    <a href="student_dashboard.php" class="filter-pill-btn">Overview</a>
                    <a href="student_daily_status.php" class="filter-pill-btn">Daily Status</a>
                    <a href="student_vehicles.php" class="filter-pill-btn active">Vehicles (<?= count($myVehicles) ?>)</a>
                </div>

                <div class="command-search-pill">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" placeholder="Search plates or models..." data-table-search="#myVehiclesTable" autocomplete="off">
                </div>
            </div>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm mb-3" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= implode(' ', $errors) ?>
            </div>
        <?php endif; ?>

        <?php if ($showNewForm): ?>
            <!-- Add / Edit Vehicle Form -->
            <div class="helios-card">
                <div class="card-header-sleek">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="bi <?= $editVehicle ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2 text-primary"></i>
                        <?= $editVehicle ? 'Edit Vehicle Information & Photo' : 'Register New Campus Vehicle' ?>
                    </h5>
                    <a href="student_vehicles.php" class="btn-helios-pill-sm">Cancel</a>
                </div>

                <form method="POST" action="student_vehicles.php" enctype="multipart/form-data" class="mt-3">
                    <?= csrf_input() ?>
                    <?php if ($editVehicle): ?>
                        <input type="hidden" name="vehicle_id" value="<?= (int)$editVehicle['vehicle_id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-dark-label" for="vehicle_type">Vehicle Classification *</label>
                            <select name="vehicle_type" id="vehicle_type" class="form-dark-control" required>
                                <option value="" class="bg-dark text-white">-- Select Vehicle Category --</option>
                                <?php foreach ($VEHICLE_TYPES as $vt): ?>
                                    <option value="<?= e($vt) ?>" class="bg-dark text-white" <?= (($editVehicle['vehicle_type'] ?? '') === $vt) ? 'selected' : '' ?>>
                                        <?= e($vt) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-dark-label" for="registration_number">Registration Number (Plate) *</label>
                            <input type="text" name="registration_number" id="registration_number" class="form-dark-control font-monospace" placeholder="e.g. MH 12 AB 4590 or KA 05 MC 3344" value="<?= e($editVehicle['registration_number'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-dark-label" for="vehicle_model">Vehicle Make / Model</label>
                            <input type="text" name="vehicle_model" id="vehicle_model" class="form-dark-control" placeholder="e.g. Yamaha R15 V4, Honda Activa 6G, Swift" value="<?= e($editVehicle['vehicle_model'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-dark-label" for="colour">Vehicle Color</label>
                            <input type="text" name="colour" id="colour" class="form-dark-control" placeholder="e.g. Matte Black, Pearl White, Red" value="<?= e($editVehicle['colour'] ?? '') ?>">
                        </div>

                        <!-- Vehicle Photo Upload Dropzone -->
                        <div class="col-12">
                            <label class="form-dark-label">Vehicle Photograph (Bike / Scooter / Car)</label>
                            <div class="vehicle-upload-dropzone" onclick="document.getElementById('vehicle_photo').click()">
                                <input type="file" name="vehicle_photo" id="vehicle_photo" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewVehicleUpload(this)">
                                
                                <div class="preview-container" id="photoPreviewContainer" style="<?= !empty($editVehicle['photo_url']) ? 'display: block;' : '' ?>">
                                    <img id="photoPreviewImg" src="<?= !empty($editVehicle['photo_url']) ? htmlspecialchars(get_vehicle_photo($editVehicle['photo_url'], $editVehicle['vehicle_type'] ?? 'Bike')) : '' ?>" alt="Vehicle Photo Preview">
                                </div>

                                <div id="uploadPrompt" style="<?= !empty($editVehicle['photo_url']) ? 'display: none;' : '' ?>">
                                    <div class="upload-icon-circle">
                                        <i class="bi bi-camera"></i>
                                    </div>
                                    <strong class="text-white d-block mb-1">Click to browse or upload vehicle photo</strong>
                                    <span class="text-muted small">Supports JPG, PNG, WebP (Max 5MB). Photo is verified by campus security at gate clearance.</span>
                                </div>

                                <div id="uploadChangeNotice" class="mt-2 text-primary small fw-semibold" style="<?= empty($editVehicle['photo_url']) ? 'display: none;' : '' ?>">
                                    <i class="bi bi-arrow-repeat me-1"></i> Click to change vehicle photo
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 pt-2">
                        <button type="submit" name="save_vehicle" class="btn-glowing-pill">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            <?= $editVehicle ? 'Update Vehicle Details' : 'Save & Register Vehicle' ?>
                        </button>
                        <a href="student_vehicles.php" class="btn-helios-outline">Cancel</a>
                    </div>
                </form>
            </div>

            <script>
            function previewVehicleUpload(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewImg = document.getElementById('photoPreviewImg');
                        const previewContainer = document.getElementById('photoPreviewContainer');
                        const uploadPrompt = document.getElementById('uploadPrompt');
                        const changeNotice = document.getElementById('uploadChangeNotice');
                        
                        previewImg.src = e.target.result;
                        previewContainer.style.display = 'block';
                        uploadPrompt.style.display = 'none';
                        changeNotice.style.display = 'block';
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }
            </script>
        <?php endif; ?>

        <!-- Vehicles Grid List -->
        <?php if (empty($myVehicles)): ?>
            <div class="helios-card text-center py-5">
                <div class="watchlist-icon-circle mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.6rem;">
                    <i class="bi bi-car-front text-muted"></i>
                </div>
                <h4 class="text-white fw-bold mb-2">No Registered Vehicles Found</h4>
                <p class="text-muted small mb-4" style="max-width: 480px; margin: 0 auto;">
                    You have not registered any vehicles yet. Register your bike, scooter, or car and upload its photo to generate your campus parking pass.
                </p>
                <div>
                    <a href="student_vehicles.php?action=new" class="btn-glowing-pill">
                        <i class="bi bi-plus-lg me-1"></i> Register Your Vehicle
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($myVehicles as $v): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="helios-card h-100 d-flex flex-column justify-content-between">
                            <div>
                                <!-- Vehicle Visual Card Media with Real Photo or High-Res Graphic -->
                                <div class="vehicle-card-media">
                                    <img src="<?= htmlspecialchars(get_vehicle_photo($v['photo_url'] ?? null, $v['vehicle_type'])) ?>" alt="<?= e($v['registration_number']) ?>" class="vehicle-photo-img">
                                    <div class="vehicle-card-overlay">
                                        <div class="vehicle-card-top-badges">
                                            <span class="vehicle-type-tag">
                                                <i class="bi <?= get_vehicle_icon($v['vehicle_type']) ?>"></i> <?= e($v['vehicle_type']) ?>
                                            </span>
                                            <div><?= render_status_badge($v['arrival_status']) ?></div>
                                        </div>
                                        <div class="vehicle-card-bottom-plate">
                                            <span class="vehicle-plate"><?= e($v['registration_number']) ?></span>
                                            <span class="text-white small fw-semibold" style="text-shadow: 0 1px 3px rgba(0,0,0,0.8);"><?= e($v['vehicle_model'] ?: 'Standard') ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 my-2 rounded-3" style="background: #14151a; border: 1px solid var(--border-subtle);">
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Vehicle Color:</span>
                                        <span class="text-white fw-semibold"><?= e($v['colour'] ?: 'Standard') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center small">
                                        <span class="text-muted">Today's Gate Clearance:</span>
                                        <span class="text-white fw-semibold">
                                            <?= $v['arrival_status'] === 'brought' ? '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Brought</span>' : '<span class="text-muted"><i class="bi bi-dash-circle me-1"></i>Not Brought</span>' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between pt-3 border-top" style="border-color: rgba(255,255,255,0.05) !important;">
                                <a href="student_vehicles.php?edit=<?= (int)$v['vehicle_id'] ?>" class="btn-helios-pill-sm">
                                    <i class="bi bi-pencil"></i> Edit Details & Photo
                                </a>

                                <form method="POST" action="student_vehicles.php" class="d-inline" onsubmit="return confirm('Remove this vehicle from your profile?');">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="deactivate_vehicle">
                                    <input type="hidden" name="vehicle_id" value="<?= (int)$v['vehicle_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-0" style="font-size: 0.78rem;">
                                        <i class="bi bi-trash3"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
