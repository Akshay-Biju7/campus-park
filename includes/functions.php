<?php
/**
 * CampusPark - Global Utility & Domain Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * XSS Safe HTML Escaper
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format Date nicely (e.g. 29 Oct 2026 or Thursday, 29 Oct 2026)
 */
function format_date(?string $dateStr, string $format = 'd M Y'): string {
    if (empty($dateStr)) return '—';
    try {
        $date = new DateTime($dateStr);
        return $date->format($format);
    } catch (Exception $e) {
        return $dateStr;
    }
}

/**
 * Format Time nicely (e.g. 08:45 AM)
 */
function format_time(?string $timeStr): string {
    if (empty($timeStr)) return '—';
    try {
        $time = new DateTime($timeStr);
        return $time->format('h:i A');
    } catch (Exception $e) {
        return $timeStr;
    }
}

/**
 * Return Bootstrap Icon class for a vehicle type
 */
function get_vehicle_icon(string $type): string {
    switch (strtolower(trim($type))) {
        case 'bike':
            return 'bi-bicycle'; // or bi-speedometer2
        case 'scooter':
            return 'bi-ev-front';
        case 'car':
            return 'bi-car-front-fill';
        case 'bicycle/ev':
        case 'bicycle':
            return 'bi-bicycle';
        default:
            return 'bi-truck-front-fill';
    }
}

/**
 * Return vehicle photo URL or categorized fallback image
 */
function get_vehicle_photo(?string $photoUrl, string $type = 'Bike'): string {
    if (!empty($photoUrl)) {
        $cleanPath = trim($photoUrl);
        $fullPath = __DIR__ . '/../' . ltrim($cleanPath, '/\\');
        if (file_exists($fullPath) || str_starts_with($cleanPath, 'http')) {
            return $cleanPath;
        }
    }

    switch (strtolower(trim($type))) {
        case 'bike':
            return 'assets/images/vehicles/bike.svg';
        case 'scooter':
            return 'assets/images/vehicles/scooter.svg';
        case 'car':
            return 'assets/images/vehicles/car.svg';
        case 'bicycle/ev':
        case 'ev':
        case 'bicycle':
            return 'assets/images/vehicles/ev.svg';
        default:
            return 'assets/images/vehicles/default.svg';
    }
}

/**
 * Handle vehicle photo upload securely
 * Returns associative array: ['success' => bool, 'path' => ?string, 'error' => ?string]
 */
function handle_vehicle_photo_upload(?array $file): array {
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'path' => null, 'error' => 'No file selected.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'error' => 'Upload error (code: ' . $file['error'] . '). Please try again.'];
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];

    $maxBytes = 5 * 1024 * 1024; // 5 MB max
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'path' => null, 'error' => 'File size exceeds 5MB limit. Please upload a smaller image.'];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $file['tmp_name']) ?: '';
            // finfo_close() is deprecated in PHP 8.5+ as finfo objects are freed automatically
            if (PHP_VERSION_ID < 80500) {
                @finfo_close($finfo);
            }
        }
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']) ?: '';
    }

    if (!$mime || !in_array($mime, $allowedMimes, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid image format. Only JPG, PNG, and WebP files are supported.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        $ext = ($mime === 'image/png') ? 'png' : (($mime === 'image/webp') ? 'webp' : 'jpg');
    }

    $uploadDir = __DIR__ . '/../assets/uploads/vehicles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = 'veh_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => 'assets/uploads/vehicles/' . $fileName, 'error' => null];
    }

    return ['success' => false, 'path' => null, 'error' => 'Failed to save vehicle photo. Check directory permissions.'];
}

/**
 * Ensure photo_url column exists in vehicles table
 */
function ensure_vehicle_photo_column(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $db = getDB();
    if (!$db) return;

    try {
        $cols = $db->query("SHOW COLUMNS FROM `vehicles` LIKE 'photo_url'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE `vehicles` ADD COLUMN `photo_url` VARCHAR(255) NULL AFTER `colour`");
        }
    } catch (Exception $e) {
        // ignore
    }
}

/**
 * Render visual badge for arrival status
 * Green for 'brought', Red/Grey for 'not_brought'
 */
function render_status_badge(?string $status): string {
    if ($status === 'brought') {
        return '<span class="badge badge-brought px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Brought to College</span>';
    } elseif ($status === 'not_brought') {
        return '<span class="badge badge-not-brought px-3 py-2 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i> Not Brought</span>';
    } else {
        return '<span class="badge badge-pending px-3 py-2 rounded-pill"><i class="bi bi-clock-history me-1"></i> Not Marked Yet</span>';
    }
}

/**
 * Render compact status badge for tables
 */
function render_status_badge_sm(?string $status): string {
    if ($status === 'brought') {
        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Brought</span>';
    } elseif ($status === 'not_brought') {
        return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Not Brought</span>';
    } else {
        return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-dash-circle me-1"></i>Pending</span>';
    }
}

/**
 * Get all vehicles for a student with their today's arrival status
 */
function get_student_vehicles_with_status(int $student_id, ?string $date = null): array {
    ensure_vehicle_photo_column();
    $db = getDB();
    if (!$db) return [];

    $date = $date ?? date('Y-m-d');

    $sql = "SELECT v.*, pr.record_id, pr.arrival_status, pr.entry_time, pr.remarks, pr.updated_at as status_updated_at
            FROM vehicles v
            LEFT JOIN parking_records pr ON v.vehicle_id = pr.vehicle_id AND pr.parking_date = :date
            WHERE v.student_id = :student_id AND v.is_active = 1
            ORDER BY v.vehicle_id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':student_id' => $student_id,
        ':date' => $date
    ]);

    return $stmt->fetchAll();
}

/**
 * Record or Update Daily Parking Status for a vehicle
 */
function set_daily_parking_status(int $student_id, int $vehicle_id, string $status, ?string $date = null, ?string $remarks = null): bool {
    $db = getDB();
    if (!$db) return false;

    $date = $date ?? date('Y-m-d');
    $status = in_array($status, ['brought', 'not_brought']) ? $status : 'not_brought';
    $entry_time = ($status === 'brought') ? date('H:i:s') : null;

    // Verify vehicle belongs to student (or student exists)
    $chkStmt = $db->prepare("SELECT student_id FROM vehicles WHERE vehicle_id = ? AND student_id = ?");
    $chkStmt->execute([$vehicle_id, $student_id]);
    if (!$chkStmt->fetch()) {
        return false; // Unauthorized or invalid vehicle
    }

    $sql = "INSERT INTO parking_records (student_id, vehicle_id, parking_date, arrival_status, entry_time, remarks, updated_at)
            VALUES (:student_id, :vehicle_id, :parking_date, :status, :entry_time, :remarks, NOW())
            ON DUPLICATE KEY UPDATE 
                arrival_status = VALUES(arrival_status),
                entry_time = VALUES(entry_time),
                remarks = VALUES(remarks),
                updated_at = NOW()";

    $stmt = $db->prepare($sql);
    return $stmt->execute([
        ':student_id'   => $student_id,
        ':vehicle_id'   => $vehicle_id,
        ':parking_date' => $date,
        ':status'       => $status,
        ':entry_time'   => $entry_time,
        ':remarks'      => $remarks
    ]);
}

/**
 * Get Student Parking Statistics
 */
function get_student_stats(int $student_id): array {
    $db = getDB();
    if (!$db) {
        return ['total_vehicles' => 0, 'today_brought' => 0, 'month_brought' => 0, 'total_days_logged' => 0];
    }

    $today = date('Y-m-d');
    $firstDayMonth = date('Y-m-01');

    // Total vehicles
    $vStmt = $db->prepare("SELECT COUNT(*) FROM vehicles WHERE student_id = ? AND is_active = 1");
    $vStmt->execute([$student_id]);
    $totalVehicles = (int)$vStmt->fetchColumn();

    // Today brought
    $tStmt = $db->prepare("SELECT COUNT(*) FROM parking_records WHERE student_id = ? AND parking_date = ? AND arrival_status = 'brought'");
    $tStmt->execute([$student_id, $today]);
    $todayBrought = (int)$tStmt->fetchColumn();

    // Month brought
    $mStmt = $db->prepare("SELECT COUNT(*) FROM parking_records WHERE student_id = ? AND parking_date >= ? AND arrival_status = 'brought'");
    $mStmt->execute([$student_id, $firstDayMonth]);
    $monthBrought = (int)$mStmt->fetchColumn();

    // Total logs
    $lStmt = $db->prepare("SELECT COUNT(DISTINCT parking_date) FROM parking_records WHERE student_id = ?");
    $lStmt->execute([$student_id]);
    $totalDays = (int)$lStmt->fetchColumn();

    return [
        'total_vehicles' => $totalVehicles,
        'today_brought'  => $todayBrought,
        'month_brought'  => $monthBrought,
        'total_days_logged' => $totalDays
    ];
}

/**
 * Get Overall Admin System Statistics
 */
function get_admin_dashboard_stats(): array {
    $db = getDB();
    if (!$db) {
        return [
            'total_students' => 0,
            'total_vehicles' => 0,
            'today_brought' => 0,
            'today_not_brought' => 0,
            'today_total_logs' => 0
        ];
    }

    $today = date('Y-m-d');

    // Total active students
    $sStmt = $db->query("SELECT COUNT(*) FROM students WHERE is_active = 1");
    $totalStudents = (int)$sStmt->fetchColumn();

    // Total active vehicles
    $vStmt = $db->query("SELECT COUNT(*) FROM vehicles WHERE is_active = 1");
    $totalVehicles = (int)$vStmt->fetchColumn();

    // Today brought
    $bStmt = $db->prepare("SELECT COUNT(*) FROM parking_records WHERE parking_date = ? AND arrival_status = 'brought'");
    $bStmt->execute([$today]);
    $todayBrought = (int)$bStmt->fetchColumn();

    // Today not brought
    $nbStmt = $db->prepare("SELECT COUNT(*) FROM parking_records WHERE parking_date = ? AND arrival_status = 'not_brought'");
    $nbStmt->execute([$today]);
    $todayNotBrought = (int)$nbStmt->fetchColumn();

    $todayTotalLogs = $todayBrought + $todayNotBrought;
    $unmarkedToday = max(0, $totalVehicles - $todayTotalLogs);

    return [
        'total_students'    => $totalStudents,
        'total_vehicles'    => $totalVehicles,
        'today_brought'     => $todayBrought,
        'today_not_brought' => $todayNotBrought,
        'unmarked_today'    => $unmarkedToday,
        'today_total_logs'  => $todayTotalLogs
    ];
}
