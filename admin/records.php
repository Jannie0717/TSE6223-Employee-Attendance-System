<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
sync_daily_attendance($conn);
$pageTitle = 'Admin Attendance Records';
$name = trim($_GET['name'] ?? '');
$empID = trim($_GET['empID'] ?? '');
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$status = $_GET['status'] ?? '';
$export = $_GET['export'] ?? '';

$dateError = '';

function is_valid_ymd_date($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if ($from !== '' && !is_valid_ymd_date($from)) {
    $dateError = 'Start Date format is invalid.';
} elseif ($to !== '' && !is_valid_ymd_date($to)) {
    $dateError = 'End Date format is invalid.';
} elseif ($from !== '' && $to !== '' && strtotime($from) > strtotime($to)) {
    $dateError = 'End Date cannot be earlier than Start Date.';
}

if ($dateError !== '' && $export === 'csv') {
    $export = '';
}

$sql = "SELECT ar.*, ep.firstName, ep.lastName, ep.department 
        FROM attendance_record ar 
        JOIN employee_profile ep ON ar.empID = ep.empID 
        WHERE 1=1";

$params = [];
$types = '';

if ($dateError !== '') {
    // Stop invalid date range from returning misleading result
    $sql .= " AND 1=0";
} else {
    if ($name !== '') {
        $sql .= " AND CONCAT(ep.firstName,' ',ep.lastName) LIKE ?";
        $params[] = "%$name%";
        $types .= 's';
    }

    if ($empID !== '') {
        $sql .= " AND ar.empID LIKE ?";
        $params[] = "%$empID%";
        $types .= 's';
    }

    if ($from !== '') {
        $sql .= " AND ar.attendanceDate >= ?";
        $params[] = $from;
        $types .= 's';
    }

    if ($to !== '') {
        $sql .= " AND ar.attendanceDate <= ?";
        $params[] = $to;
        $types .= 's';
    }

    if ($status === 'Not Late') {
        $sql .= " AND ar.lateStatus = 'Not Late' AND ar.attendanceStatus <> 'Absent'";
    }

    if ($status === 'Late') {
        $sql .= " AND ar.lateStatus = 'Late' AND ar.attendanceStatus <> 'Absent'";
    }

    if ($status === 'Absent') {
        $sql .= " AND ar.attendanceStatus = 'Absent'";
    }
}

$sql .= " ORDER BY ar.attendanceDate DESC, ep.firstName ASC";
$stmt = $conn->prepare($sql);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$records = $stmt->get_result();

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendance_records_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee ID', 'Full Name', 'Department', 'Date', 'Clock-In Time', 'Clock-Out Time', 'Status', 'Attendance Status']);
    while ($r = $records->fetch_assoc()) {
        $displayStatus = attendance_display_status($r);
        fputcsv($out, [$r['empID'], $r['firstName'] . ' ' . $r['lastName'], $r['department'], $r['attendanceDate'], $r['clockInTime'], $r['clockOutTime'], $displayStatus, $r['attendanceStatus']]);
    }
    fclose($out);
    exit;
}

$totalFound = $records->num_rows;
$today = date('Y-m-d');
$lateToday = (int) $conn->query("SELECT COUNT(*) c FROM attendance_record WHERE attendanceDate='$today' AND lateStatus='Late' AND attendanceStatus<>'Absent'")->fetch_assoc()['c'];
$presentToday = (int) $conn->query("SELECT COUNT(*) c FROM attendance_record WHERE attendanceDate='$today' AND clockInTime IS NOT NULL")->fetch_assoc()['c'];
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Admin Attendance Records</h1>
<p class="page-subtitle">Employee attendance history by recording the date, day, clock-in time, clock-out time and
    status.</p>

<?php if ($dateError !== ''): ?>
    <div class="error-box"><?= e($dateError) ?></div>
<?php endif; ?>

<form class="toolbar" method="GET">
    <input class="search-input" name="name" placeholder="🔍 Employee Name" value="<?= e($name) ?>">
    <input class="search-input" name="empID" placeholder="🔍 Employee ID" value="<?= e($empID) ?>">
    <input class="filter-input" type="date" name="from" value="<?= e($from) ?>">
    <input class="filter-input" type="date" name="to" value="<?= e($to) ?>">
    <div class="segmented">
        <button name="status" value="" class="<?= $status === '' ? 'active' : '' ?>">All</button>
        <button name="status" value="Not Late" class="<?= $status === 'Not Late' ? 'active' : '' ?>">On Time</button>
        <button name="status" value="Late" class="<?= $status === 'Late' ? 'active' : '' ?>">Late</button>
        <button name="status" value="Absent" class="<?= $status === 'Absent' ? 'active' : '' ?>">Absent</button>
    </div>
    <button class="btn btn-dark">Filter</button>
</form>

<div class="grid grid-3">
    <div class="card stat-card">
        <div class="stat-number"><?= e($totalFound) ?></div>
        <div class="stat-label">Total Records Found</div>
    </div>
    <div class="card stat-card">
        <div class="stat-number"><?= e($lateToday) ?></div>
        <div class="stat-label">Number of Late Employees</div><a class="btn btn-blue" href="late.php">▶ View</a>
    </div>
    <div class="card stat-card">
        <div class="stat-number"><?= e($presentToday) ?></div>
        <div class="stat-label">Number of Employees Present Today</div>
    </div>
</div>

<div class="top-actions" style="margin-top:16px">
    <a class="btn btn-outline"
        href="records.php?<?= e(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>">Export Excel</a>
    <button type="button" class="btn btn-outline" onclick="printReport()">Export PDF</button>
</div>

<div class="table-wrap print-area">
    <h2 class="section-title print-title">Attendance Table</h2>
    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Date</th>
                <th>Clock-In Time</th>
                <th>Clock-Out Time</th>
                <th>Status</th>
                <th class="no-print">Action (Edit)</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($dateError !== ''): ?>

                <tr>
                    <td colspan="8" class="empty" style="color:#b00020;">
                        <?= e($dateError) ?>
                    </td>
                </tr>

            <?php elseif ($records->num_rows === 0): ?>

                <tr>
                    <td colspan="8" class="empty">No records found</td>
                </tr>

            <?php else:
                while ($r = $records->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($r['empID']) ?></td>
                        <td><?= e($r['firstName'] . ' ' . $r['lastName']) ?></td>
                        <td><?= e($r['department']) ?></td>
                        <td><?= e($r['attendanceDate']) ?></td>
                        <td><?= $r['clockInTime'] ? e(date('h:i A', strtotime($r['clockInTime']))) : '-' ?></td>
                        <td><?= $r['clockOutTime'] ? e(date('h:i A', strtotime($r['clockOutTime']))) : '-' ?></td>
                        <?php $displayStatus = attendance_display_status($r);
                        $badgeClass = attendance_badge_class($r); ?>
                        <td><span
                                class="badge <?= e($badgeClass) ?>"><?= e($displayStatus === 'Clocked In' ? 'On Time' : $displayStatus) ?></span>
                        </td>
                        <td class="table-actions no-print"><a href="update_attendance.php?id=<?= e($r['recordID']) ?>">Edit</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>