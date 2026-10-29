<?php
/**
 * CampusPark – Student Registration Page
 * Modern Dark Helios Layout: Collects full name, student ID / roll number, department, course, year of study, email, contact number, and password.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in as student, redirect to dashboard
if (is_student_logged_in()) {
    header("Location: student_dashboard.php");
    exit;
}

$pageTitle = 'Student Registration – ' . APP_NAME;
$activeNav = 'register';

$errors = [];
$formData = [
    'full_name'      => '',
    'roll_number'    => '',
    'email'          => '',
    'contact_number' => '',
    'course'         => 'B.Tech',
    'department'     => 'Computer Science & Engineering',
    'year_of_study'  => '1st Year'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $formData['full_name']      = trim($_POST['full_name'] ?? '');
        $formData['roll_number']    = strtoupper(trim($_POST['roll_number'] ?? ''));
        $formData['email']          = strtolower(trim($_POST['email'] ?? ''));
        $formData['contact_number'] = trim($_POST['contact_number'] ?? '');
        $formData['course']         = trim($_POST['course'] ?? '');
        $formData['department']     = trim($_POST['department'] ?? '');
        $formData['year_of_study']  = trim($_POST['year_of_study'] ?? '');
        $password                   = $_POST['password'] ?? '';
        $confirm_password           = $_POST['confirm_password'] ?? '';

        if (empty($formData['full_name'])) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($formData['full_name']) < 3) {
            $errors[] = 'Full name must be at least 3 characters long.';
        }

        if (empty($formData['roll_number'])) {
            $errors[] = 'Student ID / Roll Number is required.';
        } elseif (strlen($formData['roll_number']) < 4) {
            $errors[] = 'Student ID must be at least 4 characters long.';
        }

        if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid college email address is required.';
        }

        if (empty($formData['contact_number'])) {
            $errors[] = 'Contact number is required.';
        } elseif (!preg_match('/^[0-9+\s\-]{8,15}$/', $formData['contact_number'])) {
            $errors[] = 'Please enter a valid phone/contact number (8 to 15 digits).';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Password confirmation does not match.';
        }

        if (empty($errors)) {
            $db = getDB();
            if (!$db) {
                $errors[] = 'Database is currently offline. Please run setup.php or start MySQL in XAMPP.';
            } else {
                $chkStmt = $db->prepare("SELECT student_id, roll_number, email FROM students WHERE roll_number = ? OR email = ? LIMIT 1");
                $chkStmt->execute([$formData['roll_number'], $formData['email']]);
                $existing = $chkStmt->fetch();

                if ($existing) {
                    if (strcasecmp($existing['roll_number'], $formData['roll_number']) === 0) {
                        $errors[] = "A student with ID / Roll Number '{$formData['roll_number']}' is already registered.";
                    }
                    if (strcasecmp($existing['email'], $formData['email']) === 0) {
                        $errors[] = "A student with email '{$formData['email']}' is already registered.";
                    }
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $insertStmt = $db->prepare("
                        INSERT INTO students (roll_number, full_name, email, password_hash, contact_number, course, department, year_of_study, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");

                    $success = $insertStmt->execute([
                        $formData['roll_number'],
                        $formData['full_name'],
                        $formData['email'],
                        $passwordHash,
                        $formData['contact_number'],
                        $formData['course'],
                        $formData['department'],
                        $formData['year_of_study']
                    ]);

                    if ($success) {
                        $newStudentId = (int)$db->lastInsertId();

                        $_SESSION['user_type']    = 'student';
                        $_SESSION['student_id']   = $newStudentId;
                        $_SESSION['student_name'] = $formData['full_name'];
                        $_SESSION['roll_number']  = $formData['roll_number'];

                        set_flash('success', "Welcome to CampusPark, {$formData['full_name']}! Your student profile has been created. Please register your vehicle below.");
                        header("Location: student_vehicles.php?action=new");
                        exit;
                    } else {
                        $errors[] = 'An unexpected database error occurred during registration. Please try again.';
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <!-- Modern Dark Helios Registration Card -->
            <div class="helios-card p-4 p-md-5" style="box-shadow: 0 30px 90px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.08);">
                <div class="text-center mb-4">
                    <div class="brand-logo-mark mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.5rem; border-radius: 14px;">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Student Registration</h3>
                    <p class="text-muted small mb-0">Create your CampusPark account for digital parking attendance</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm mb-4" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;" role="alert">
                        <ul class="mb-0 small ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="student_register.php" novalidate id="studentRegForm">
                    <?= csrf_input() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-dark-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-dark-control" placeholder="e.g. Johnathan Doe" value="<?= e($formData['full_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-dark-label">Student ID / Roll No. *</label>
                            <input type="text" name="roll_number" class="form-dark-control text-uppercase font-monospace" placeholder="e.g. 24BTCS102" value="<?= e($formData['roll_number']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-dark-label">College / Personal Email *</label>
                            <input type="email" name="email" class="form-dark-control" placeholder="name@campus.edu" value="<?= e($formData['email']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-dark-label">Mobile Contact Number *</label>
                            <input type="tel" name="contact_number" class="form-dark-control" placeholder="e.g. 9876543210" value="<?= e($formData['contact_number']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-dark-label">Course *</label>
                            <select name="course" class="form-dark-control" required>
                                <?php foreach ($COURSES as $c): ?>
                                    <option value="<?= e($c) ?>" class="bg-dark text-white" <?= ($formData['course'] === $c) ? 'selected' : '' ?>><?= e($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-dark-label">Department *</label>
                            <select name="department" class="form-dark-control" required>
                                <?php foreach ($DEPARTMENTS as $dept): ?>
                                    <option value="<?= e($dept) ?>" class="bg-dark text-white" <?= ($formData['department'] === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-dark-label">Year of Study *</label>
                            <select name="year_of_study" class="form-dark-control" required>
                                <?php foreach ($YEARS as $yr): ?>
                                    <option value="<?= e($yr) ?>" class="bg-dark text-white" <?= ($formData['year_of_study'] === $yr) ? 'selected' : '' ?>><?= e($yr) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-dark-label">Password *</label>
                            <input type="password" name="password" id="regPassword" class="form-dark-control" placeholder="Min. 6 characters" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-dark-label">Confirm Password *</label>
                            <input type="password" name="confirm_password" id="regConfirmPassword" class="form-dark-control" placeholder="Repeat password" required>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn-glowing-pill w-100 py-2 fs-6">
                            <i class="bi bi-person-check-fill me-1"></i> Register Student Account
                        </button>
                    </div>

                    <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(255,255,255,0.06) !important;">
                        <p class="small text-muted mb-0">
                            Already have an account? <a href="student_login.php" class="fw-bold text-white text-decoration-none">Sign In here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
