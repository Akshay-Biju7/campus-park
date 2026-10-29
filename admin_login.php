<?php
/**
 * CampusPark – Admin Login Page
 * Modern Dark Helios Layout: Dedicated portal for authorized college security and parking administrators
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in as administrator
if (is_admin_logged_in()) {
    header("Location: admin_dashboard.php");
    exit;
}

$pageTitle = 'Administrator Login – ' . APP_NAME;
$activeNav = 'admin_login';

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please refresh the page and submit again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please provide both your administrator username and password.';
        } else {
            $db = getDB();
            if (!$db) {
                $error = 'Database connection failed. Please ensure MySQL is running in XAMPP or run setup.php.';
            } else {
                $stmt = $db->prepare("
                    SELECT * FROM admins 
                    WHERE (username = :username OR email = :email) 
                    LIMIT 1
                ");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $username
                ]);
                $admin = $stmt->fetch();

                if ($admin && verify_user_password($password, $admin['password_hash'])) {
                    $_SESSION['user_type']      = 'admin';
                    $_SESSION['admin_id']       = (int)$admin['admin_id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name']     = $admin['full_name'];

                    set_flash('success', "Welcome, Administrator {$admin['full_name']}! Authorized access granted.");
                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    $error = 'Invalid administrative credentials. Access denied.';
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
            <!-- Admin Login Helios Card -->
            <div class="helios-card p-4 p-md-5" style="box-shadow: 0 30px 90px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.08);">
                <div class="text-center mb-4">
                    <div class="brand-logo-mark mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.5rem; border-radius: 14px; background: linear-gradient(135deg, #f59e0b 0%, #ec4899 100%);">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Campus Security Staff</h3>
                    <p class="text-muted small mb-0">Authorized Administrator & Gate Control Access</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center shadow-sm mb-4" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;" role="alert">
                        <i class="bi bi-shield-x me-2 fs-5 flex-shrink-0 text-danger"></i>
                        <div class="small"><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin_login.php" novalidate id="adminLoginForm">
                    <?= csrf_input() ?>

                    <div class="mb-3">
                        <label class="form-dark-label" for="username">Admin Username or Email</label>
                        <input type="text" name="username" id="username" class="form-dark-control" placeholder="e.g. admin or admin@campuspark.edu" value="<?= e($username) ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-dark-label mb-0" for="password">Password</label>
                            <span class="small text-muted" style="font-size: 0.72rem;">Default: <code>admin123</code></span>
                        </div>
                        <input type="password" name="password" id="password" class="form-dark-control" placeholder="Enter administrator password" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input bg-dark border-secondary" type="checkbox" id="adminRemember" checked>
                            <label class="form-check-label small text-muted" for="adminRemember">Secure Session</label>
                        </div>
                        <a href="setup.php" class="small text-decoration-none text-muted" style="font-size: 0.75rem;">Reset via Setup</a>
                    </div>

                    <button type="submit" class="btn-glowing-pill w-100 py-2 fs-6">
                        <i class="bi bi-shield-check me-1"></i> Authorize & Enter Control Panel
                    </button>
                </form>

                <!-- 1-Click Demo Fill -->
                <div class="mt-4 p-3 rounded-3 text-center" style="background: #14151a; border: 1px solid var(--border-subtle);">
                    <span class="d-block small text-muted fw-semibold mb-2" style="font-size: 0.75rem;">⚡ 1-Click Demo Quick Fill:</span>
                    <button type="button" class="btn-helios-pill-sm text-warning border-warning border-opacity-50" onclick="quickFillAdmin('admin', 'admin123')">
                        <i class="bi bi-lightning-fill me-1"></i> Auto-fill Demo Admin (admin / admin123)
                    </button>
                </div>

                <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(255,255,255,0.06) !important;">
                    <p class="small text-muted mb-0">
                        Are you a student? <a href="student_login.php" class="fw-bold text-white text-decoration-none">Student Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickFillAdmin(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
