<?php
/**
 * CampusPark – Student Profile & Account Settings
 * Modern Dark Helios Layout: Review and update personal information and password
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_student();

$student = get_logged_student();
$studentId = (int)$student['student_id'];

$pageTitle = 'Profile & Settings – ' . APP_NAME;
$activeNav = 'student_dash';
$sidebarActive = 'profile';

$errors = [];
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $fullName    = trim($_POST['full_name'] ?? '');
            $contact     = trim($_POST['contact_number'] ?? '');
            $email       = strtolower(trim($_POST['email'] ?? ''));
            $yearOfStudy = trim($_POST['year_of_study'] ?? '');

            if (empty($fullName)) {
                $errors[] = 'Full name cannot be empty.';
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            }

            if (empty($contact) || !preg_match('/^[0-9+\s\-]{8,15}$/', $contact)) {
                $errors[] = 'Valid contact number is required.';
            }

            if (empty($errors) && $db) {
                $chk = $db->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
                $chk->execute([$email, $studentId]);
                if ($chk->fetch()) {
                    $errors[] = 'This email address is already in use by another student.';
                } else {
                    $stmt = $db->prepare("
                        UPDATE students 
                        SET full_name = ?, contact_number = ?, email = ?, year_of_study = ?
                        WHERE student_id = ?
                    ");
                    if ($stmt->execute([$fullName, $contact, $email, $yearOfStudy, $studentId])) {
                        $_SESSION['student_name'] = $fullName;
                        set_flash('success', 'Profile information updated successfully.');
                        header("Location: student_profile.php");
                        exit;
                    } else {
                        $errors[] = 'Database error while updating profile.';
                    }
                }
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_new_password'] ?? '';

            if (empty($currentPass) || empty($newPass)) {
                $errors[] = 'Please fill all password fields.';
            } elseif (!verify_user_password($currentPass, $student['password_hash'])) {
                $errors[] = 'Your current password is incorrect.';
            } elseif (strlen($newPass) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPass !== $confirmPass) {
                $errors[] = 'New password confirmation does not match.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE students SET password_hash = ? WHERE student_id = ?");
                if ($stmt->execute([$newHash, $studentId])) {
                    set_flash('success', 'Your password has been changed successfully.');
                    header("Location: student_profile.php");
                    exit;
                } else {
                    $errors[] = 'Database error while changing password.';
                }
            }
        }
    }
}

// Refresh student data
$student = get_logged_student();

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
                        <h2>Profile & Security Settings</h2>
                        <p>Manage your student credentials & contact details &bull; <?= e($student['roll_number']) ?></p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <a href="student_dashboard.php" class="btn-helios-pill-sm">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <div class="profile-pill">
                        <div class="profile-avatar"><?= strtoupper(substr($student['full_name'] ?? 'S', 0, 1)) ?></div>
                        <div class="profile-meta">
                            <span class="profile-name"><?= e($student['full_name']) ?></span>
                            <span class="profile-sub font-monospace"><?= e($student['roll_number']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subrow: Filter Pills -->
            <div class="topbar-subrow">
                <div class="filter-pills-group">
                    <a href="student_dashboard.php" class="filter-pill-btn">Overview</a>
                    <a href="student_daily_status.php" class="filter-pill-btn">Daily Status</a>
                    <a href="student_vehicles.php" class="filter-pill-btn">Vehicles</a>
                    <a href="student_profile.php" class="filter-pill-btn active">Settings</a>
                </div>
            </div>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm mb-3" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= implode(' ', $errors) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left: Personal Information Form -->
            <div class="col-lg-7">
                <div class="helios-card">
                    <div class="card-header-sleek">
                        <h5 class="text-white fw-bold mb-0">
                            <i class="bi bi-person-badge me-2 text-primary"></i> Personal Academic Profile
                        </h5>
                        <span class="card-pill-tag">Verified Student</span>
                    </div>

                    <form method="POST" action="student_profile.php" class="mt-3">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-dark-label">Full Name</label>
                                <input type="text" name="full_name" class="form-dark-control" value="<?= e($student['full_name']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-dark-label">Student ID / Roll No. (Official)</label>
                                <input type="text" class="form-dark-control font-monospace" style="opacity: 0.7; background: #121318;" value="<?= e($student['roll_number']) ?>" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-dark-label">Course</label>
                                <input type="text" class="form-dark-control" style="opacity: 0.7; background: #121318;" value="<?= e($student['course']) ?>" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-dark-label">Department</label>
                                <input type="text" class="form-dark-control" style="opacity: 0.7; background: #121318;" value="<?= e($student['department']) ?>" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-dark-label">Email Address</label>
                                <input type="email" name="email" class="form-dark-control" value="<?= e($student['email']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-dark-label">Mobile Contact</label>
                                <input type="tel" name="contact_number" class="form-dark-control" value="<?= e($student['contact_number']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-dark-label">Year of Study</label>
                                <select name="year_of_study" class="form-dark-control">
                                    <?php foreach ($YEARS as $yr): ?>
                                        <option value="<?= e($yr) ?>" class="bg-dark text-white" <?= ($student['year_of_study'] === $yr) ? 'selected' : '' ?>><?= e($yr) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="btn-glowing-pill">
                                <i class="bi bi-check-circle-fill me-1"></i> Save Profile Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Change Password Card -->
            <div class="col-lg-5">
                <div class="helios-card">
                    <div class="card-header-sleek">
                        <h5 class="text-white fw-bold mb-0">
                            <i class="bi bi-shield-lock me-2 text-warning"></i> Change Password
                        </h5>
                    </div>

                    <form method="POST" action="student_profile.php" class="mt-3">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-dark-label">Current Password</label>
                            <input type="password" name="current_password" class="form-dark-control" placeholder="Enter current password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-dark-label">New Password</label>
                            <input type="password" name="new_password" class="form-dark-control" placeholder="At least 6 characters" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-dark-label">Confirm New Password</label>
                            <input type="password" name="confirm_new_password" class="form-dark-control" placeholder="Re-enter new password" required>
                        </div>

                        <button type="submit" class="btn-helios-outline w-100">
                            <i class="bi bi-key-fill me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
