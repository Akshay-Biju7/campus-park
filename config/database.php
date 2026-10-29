<?php
/**
 * CampusPark - Database Connection Handler
 * Uses PDO with Prepared Statements for complete SQL Injection Protection
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static bool $connectionAttempted = false;
    private static ?string $lastError = null;

    /**
     * Get or create PDO Database Connection
     */
    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$connectionAttempted = true;

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            return self::$instance;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            
            // Check if database simply does not exist yet
            if (strpos(self::$lastError, 'Unknown database') !== false) {
                // If on a page other than setup.php, let user know setup is required
                $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
                if ($currentPage !== 'setup.php') {
                    // Redirect or display friendly setup banner
                    self::renderDatabaseSetupPrompt();
                    exit;
                }
            } else {
                // Other connection issues (e.g. MySQL server not running in XAMPP)
                $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
                if ($currentPage !== 'setup.php') {
                    self::renderConnectionErrorPrompt(self::$lastError);
                    exit;
                }
            }
            return null;
        }
    }

    /**
     * Get raw connection without specific db selected (useful for setup.php)
     */
    public static function getRawConnection(): ?PDO {
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            return null;
        }
    }

    public static function getLastError(): ?string {
        return self::$lastError;
    }

    private static function renderDatabaseSetupPrompt(): void {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>CampusPark - Database Setup Required</title>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
            <link rel="stylesheet" href="assets/css/style.css">
        </head>
        <body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
            <div class="container" style="max-width: 540px;">
                <div class="helios-card p-4 p-md-5 text-center" style="box-shadow: 0 30px 90px rgba(0,0,0,0.8);">
                    <div class="brand-logo-mark mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem; border-radius: 14px;">
                        <i class="bi bi-database-gear"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-2">CampusPark Setup Required</h3>
                    <p class="text-muted small mb-4">
                        The database <code class="text-white"><?= htmlspecialchars(DB_NAME) ?></code> has not been initialized yet in MySQL.
                    </p>
                    <div class="p-3 rounded-3 mb-4 text-start small" style="background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.25); color: #e9d5ff;">
                        <i class="bi bi-info-circle-fill me-1 text-primary"></i> You can initialize all database tables and seed ready-to-test sample data in 1-click using the setup wizard.
                    </div>
                    <div class="d-grid gap-2">
                        <a href="setup.php" class="btn-glowing-pill py-2">
                            <i class="bi bi-play-circle me-1"></i> Run 1-Click Database Setup Wizard
                        </a>
                        <a href="index.php" class="btn-helios-outline py-2">Refresh Page</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    private static function renderConnectionErrorPrompt(string $error): void {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>CampusPark - MySQL Connection Notice</title>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
            <link rel="stylesheet" href="assets/css/style.css">
        </head>
        <body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
            <div class="container" style="max-width: 560px;">
                <div class="helios-card p-4 p-md-5" style="box-shadow: 0 30px 90px rgba(0,0,0,0.8);">
                    <div class="text-center mb-3">
                        <div class="watchlist-icon-circle mx-auto mb-2" style="width: 52px; height: 52px; font-size: 1.6rem; background: rgba(239, 68, 68, 0.15); color: #f87171; border-color: rgba(239, 68, 68, 0.3);">
                            <i class="bi bi-hdd-network"></i>
                        </div>
                        <h4 class="fw-bold text-white mb-1">Cannot Connect to MySQL</h4>
                        <p class="text-muted small mb-3">Please ensure MySQL is started in your local XAMPP Control Panel.</p>
                    </div>
                    <div class="p-3 rounded-3 mb-4 small font-monospace" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); color: #fca5a5;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <div class="p-3 rounded-3 mb-4 small text-muted" style="background: #14151a; border: 1px solid var(--border-subtle);">
                        <div class="text-white fw-semibold mb-1">Quick Steps:</div>
                        1. Open the <strong>XAMPP Control Panel</strong>.<br>
                        2. Click <strong>Start</strong> next to <strong>Apache</strong> and <strong>MySQL</strong>.<br>
                        3. Refresh this page or run <a href="setup.php" class="text-info text-decoration-none">setup.php</a>.
                    </div>
                    <a href="javascript:location.reload();" class="btn-glowing-pill w-100 py-2">Retry Connection</a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}

/**
 * Global helper function to retrieve database PDO instance
 */
function getDB(): ?PDO {
    return Database::getConnection();
}
