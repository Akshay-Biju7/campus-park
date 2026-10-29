<?php
/**
 * CampusPark – Student Login Page
 * Modern Dark Helios Layout: Supports login via Student ID / Roll Number or College Email + Password
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in as student
if (is_student_logged_in()) {
    header("Location: student_dashboard.php");
    exit;
}

$pageTitle = 'Student Login – ' . APP_NAME;
$activeNav = 'student_login';

$error = null;
$loginId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please refresh the page and submit again.';
    } else {
        $loginId = trim($_POST['login_id'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($loginId) || empty($password)) {
            $error = 'Please enter both your Student ID / Email and password.';
        } else {
            $db = getDB();
            if (!$db) {
                $error = 'Database connection failed. Please ensure MySQL is running in XAMPP or run setup.php.';
            } else {
                $stmt = $db->prepare("
                    SELECT * FROM students 
                    WHERE (email = :email OR roll_number = :roll_number) 
                    LIMIT 1
                ");
                $stmt->execute([
                    ':email' => $loginId,
                    ':roll_number' => $loginId
                ]);
                $student = $stmt->fetch();

                if ($student) {
                    if ((int)$student['is_active'] !== 1) {
                        $error = 'Your student account is currently deactivated. Please contact campus security.';
                    } elseif (verify_user_password($password, $student['password_hash'])) {
                        $_SESSION['user_type']    = 'student';
                        $_SESSION['student_id']   = (int)$student['student_id'];
                        $_SESSION['student_name'] = $student['full_name'];
                        $_SESSION['roll_number']  = $student['roll_number'];

                        set_flash('success', "Welcome back, {$student['full_name']}! You are logged in.");
                        header("Location: student_dashboard.php");
                        exit;
                    } else {
                        $error = 'Incorrect password. Please verify and try again.';
                    }
                } else {
                    $error = "No student account found with ID or Email '{$loginId}'.";
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <!-- Modern Dark Helios Auth Card -->
            <div class="helios-card p-4 p-md-5" style="box-shadow: 0 30px 90px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.08);">
                <!-- Header Branding -->
                <div class="text-center mb-4">
                    <div class="brand-logo-mark mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.5rem; border-radius: 14px;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Student Portal</h3>
                    <p class="text-muted small mb-0">Sign in to manage parking passes & daily gate check-in</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center shadow-sm mb-4" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2 fs-5 flex-shrink-0"></i>
                        <div class="small"><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="student_login.php" novalidate id="studentLoginForm">
                    <?= csrf_input() ?>

                    <div class="mb-3">
                        <label class="form-dark-label" for="login_id">Student ID or College Email</label>
                        <div class="position-relative">
                            <input type="text" name="login_id" id="login_id" class="form-dark-control" placeholder="e.g. 22BTCS045 or rahul@campus.edu" value="<?= e($loginId) ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-dark-label mb-0" for="password">Password</label>
                            <span class="small text-muted" style="font-size: 0.72rem;">Default: <code>student123</code></span>
                        </div>
                        <div class="position-relative">
                            <input type="password" name="password" id="password" class="form-dark-control" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input bg-dark border-secondary" type="checkbox" id="rememberMe" checked>
                            <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
                        </div>
                        <a href="setup.php" class="small text-decoration-none text-muted" style="font-size: 0.75rem;">Reset DB?</a>
                    </div>

                    <button type="submit" class="btn-glowing-pill w-100 py-2 fs-6">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Student Portal
                    </button>
                </form>

                <!-- 1-Click Demo Evaluator Quick Fill -->
                <div class="mt-4 p-3 rounded-3 text-center" style="background: #14151a; border: 1px solid var(--border-subtle);">
                    <span class="d-block small text-muted fw-semibold mb-2" style="font-size: 0.75rem;">⚡ 1-Click Demo Evaluator Quick Fill:</span>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <button type="button" class="btn-helios-pill-sm" onclick="quickFill('22BTCS045', 'student123')">
                            <i class="bi bi-person me-1"></i> Rahul (B.Tech CSE)
                        </button>
                        <button type="button" class="btn-helios-pill-sm" onclick="quickFill('23BTEC019', 'student123')">
                            <i class="bi bi-person me-1"></i> Priya (B.Tech ECE)
                        </button>
                    </div>
                </div>

                <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(255,255,255,0.06) !important;">
                    <p class="small text-muted mb-2">
                        New student? <a href="student_register.php" class="fw-bold text-white text-decoration-none">Register here</a>
                    </p>
                    <p class="small text-muted mb-0">
                        Campus Security Staff? <a href="admin_login.php" class="text-decoration-none text-warning"><i class="bi bi-shield-lock me-1"></i>Admin Portal</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickFill(id, pass) {
    document.getElementById('login_id').value = id;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
