<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pageTitle = 'Generate Report';
$name = trim($_GET['name'] ?? '');
$empID = trim($_GET['empID'] ?? '');
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$status = $_GET['status'] ?? '';
$export = $_GET['export'] ?? '';

$sql = "SELECT ar.*, ep.firstName, ep.lastName, ep.department FROM attendance_record ar JOIN employee_profile ep ON ar.empID = ep.empID WHERE 1=1";
$params = [];
$types = '';
if ($name !== '') { $sql .= " AND CONCAT(ep.firstName,' ',ep.lastName) LIKE ?"; $params[]="%$name%"; $types.='s'; }
if ($empID !== '') { $sql .= " AND ar.empID LIKE ?"; $params[]="%$empID%"; $types.='s'; }
if ($from !== '') { $sql .= " AND ar.attendanceDate >= ?"; $params[]=$from; $types.='s'; }
if ($to !== '') { $sql .= " AND ar.attendanceDate <= ?"; $params[]=$to; $types.='s'; }
if ($status !== '') { $sql .= " AND ar.lateStatus = ?"; $params[]=$status; $types.='s'; }
$sql .= " ORDER BY ar.attendanceDate DESC, ep.firstName ASC";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$records = $stmt->get_result();

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attendance_report_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee ID','Full Name','Department','Date','Clock-In Time','Clock-Out Time','Late Status','Attendance Status']);
    while ($r = $records->fetch_assoc()) {
        fputcsv($out, [$r['empID'], $r['firstName'].' '.$r['lastName'], $r['department'], $r['attendanceDate'], $r['clockInTime'], $r['clockOutTime'], $r['lateStatus'], $r['attendanceStatus']]);
    }
    fclose($out);
    exit;
}
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Generate Report</h1>
<p class="page-subtitle">Generate a simple attendance report with employee ID, full name, date, clock-in, clock-out, and late status.</p>
<form class="toolbar" method="GET">
    <input class="search-input" name="name" placeholder="🔍 Employee Name" value="<?= e($name) ?>">
    <input class="search-input" name="empID" placeholder="🔍 Employee ID" value="<?= e($empID) ?>">
    <input class="filter-input" type="date" name="from" value="<?= e($from) ?>">
    <input class="filter-input" type="date" name="to" value="<?= e($to) ?>">
    <select class="filter-input" name="status">
        <option value="">All Status</option>
        <option value="Not Late" <?= $status==='Not Late'?'selected':'' ?>>On Time</option>
        <option value="Late" <?= $status==='Late'?'selected':'' ?>>Late</option>
    </select>
    <button class="btn btn-dark">Generate Report</button>
</form>
<div class="top-actions">
    <a class="btn btn-outline" href="report.php?<?= e(http_build_query(array_merge($_GET,['export'=>'csv']))) ?>">Export Excel/CSV</a>
    <button class="btn btn-outline" onclick="printReport()">Print / Save as PDF</button>
</div>
<div class="table-wrap">
    <h2 class="section-title">Attendance Report</h2>
    <table>
        <thead><tr><th>Employee ID</th><th>Full Name</th><th>Department</th><th>Date</th><th>Clock-In Time</th><th>Clock-Out Time</th><th>Late Status</th><th>Attendance Status</th></tr></thead>
        <tbody>
        <?php if ($records->num_rows === 0): ?>
            <tr><td colspan="8" class="empty">No records found</td></tr>
        <?php else: while($r=$records->fetch_assoc()): ?>
            <tr>
                <td><?= e($r['empID']) ?></td>
                <td><?= e($r['firstName'].' '.$r['lastName']) ?></td>
                <td><?= e($r['department']) ?></td>
                <td><?= e($r['attendanceDate']) ?></td>
                <td><?= $r['clockInTime'] ? e(date('h:i A', strtotime($r['clockInTime']))) : '-' ?></td>
                <td><?= $r['clockOutTime'] ? e(date('h:i A', strtotime($r['clockOutTime']))) : '-' ?></td>
                <td><span class="badge <?= $r['lateStatus']==='Late'?'red':'green' ?>"><?= e($r['lateStatus']) ?></span></td>
                <td><?= e($r['attendanceStatus']) ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
