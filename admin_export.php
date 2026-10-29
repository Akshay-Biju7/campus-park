<?php
/**
 * CampusPark – Admin CSV / Excel Data Exporter
 * Exports filtered college parking attendance and vehicle records directly to downloadable CSV
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Ensure only authorized admin can export data
require_admin();

$db = getDB();
if (!$db) {
    die("Database connection unavailable.");
}

$today = date('Y-m-d');

// Read filters from GET request
$filterDateMode   = trim($_GET['date_mode'] ?? 'today');
$filterDateSingle = trim($_GET['date'] ?? $today);
$filterDateFrom   = trim($_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')));
$filterDateTo     = trim($_GET['date_to'] ?? $today);
$statusFilter     = trim($_GET['status'] ?? '');
$courseFilter     = trim($_GET['course'] ?? '');
$deptFilter       = trim($_GET['department'] ?? '');
$vehicleTypeFilter = trim($_GET['vehicle_type'] ?? '');
$searchFilter     = trim($_GET['search'] ?? '');

// Date resolution
if ($filterDateMode === 'today') {
    $dateCondition = "pr.parking_date = :f_date";
    $dateParams = [':f_date' => $today];
    $fileDateSuffix = "Today_" . $today;
} elseif ($filterDateMode === 'yesterday') {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $dateCondition = "pr.parking_date = :f_date";
    $dateParams = [':f_date' => $yesterday];
    $fileDateSuffix = "Yesterday_" . $yesterday;
} elseif ($filterDateMode === 'all_time') {
    $dateCondition = "1=1";
    $dateParams = [];
    $fileDateSuffix = "AllTime";
} else {
    $dateCondition = "pr.parking_date BETWEEN :f_from AND :f_to";
    $dateParams = [':f_from' => $filterDateFrom, ':f_to' => $filterDateTo];
    $fileDateSuffix = "Range_" . $filterDateFrom . "_to_" . $filterDateTo;
}

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

$sql = "
    SELECT 
        pr.record_id, pr.parking_date, pr.entry_time, pr.arrival_status, pr.remarks, pr.updated_at,
        s.roll_number, s.full_name, s.course, s.department, s.year_of_study, s.contact_number, s.email,
        v.vehicle_type, v.registration_number, v.vehicle_model, v.colour
    FROM parking_records pr
    JOIN students s ON pr.student_id = s.student_id
    JOIN vehicles v ON pr.vehicle_id = v.vehicle_id
    WHERE {$whereSql}
    ORDER BY pr.parking_date DESC, pr.entry_time DESC, pr.record_id DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Set HTTP headers for file download
$filename = "CampusPark_Register_" . $fileDateSuffix . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header row
fputcsv($output, [
    'Token / ID',
    'Parking Date',
    'Entry Time',
    'Student Roll No',
    'Student Full Name',
    'Course',
    'Department',
    'Year of Study',
    'Contact Number',
    'College Email',
    'Vehicle Type',
    'Registration Number (Plate)',
    'Vehicle Model',
    'Vehicle Colour',
    'Arrival Status',
    'Remarks / Parking Zone',
    'Record Last Updated'
]);

// Write data rows
$token = 1;
foreach ($rows as $r) {
    fputcsv($output, [
        '#' . str_pad((string)$token++, 4, '0', STR_PAD_LEFT),
        $r['parking_date'],
        $r['entry_time'] ?: 'N/A',
        $r['roll_number'],
        $r['full_name'],
        $r['course'],
        $r['department'],
        $r['year_of_study'],
        $r['contact_number'],
        $r['email'],
        $r['vehicle_type'],
        $r['registration_number'],
        $r['vehicle_model'] ?: 'Standard',
        $r['colour'] ?: 'N/A',
        ($r['arrival_status'] === 'brought') ? 'Brought to College' : 'Not Brought',
        $r['remarks'] ?: 'None',
        $r['updated_at']
    ]);
}

fclose($output);
exit;
