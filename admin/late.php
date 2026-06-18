<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
sync_daily_attendance($conn);
$pageTitle = 'Late Employees';
$today = date('Y-m-d');
$date = $_GET['date'] ?? $today;
$stmt = $conn->prepare("SELECT ar.*, ep.firstName, ep.lastName, ep.department FROM attendance_record ar JOIN employee_profile ep ON ar.empID=ep.empID WHERE ar.attendanceDate=? AND ar.lateStatus='Late' AND ar.attendanceStatus<>'Absent' ORDER BY ar.clockInTime ASC");
$stmt->bind_param('s', $date);
$stmt->execute();
$lateRecords = $stmt->get_result();
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Late Employees</h1>
<p class="page-subtitle">View employees who clocked in after the 9:15 AM late threshold.</p>
<form class="toolbar" method="GET">
    <input class="filter-input" type="date" name="date" value="<?= e($date) ?>">
    <button class="btn btn-dark">Filter</button>
</form>
<div class="table-wrap">
    <h2 class="section-title">Late Employees Table</h2>
    <table>
        <thead><tr><th>Employee ID</th><th>Full Name</th><th>Department</th><th>Date</th><th>Clock-In Time</th><th>Status</th></tr></thead>
        <tbody>
        <?php if ($lateRecords->num_rows === 0): ?>
            <tr><td colspan="6" class="empty">No late employee records found</td></tr>
        <?php else: while($r=$lateRecords->fetch_assoc()): ?>
            <tr>
                <td><?= e($r['empID']) ?></td>
                <td><?= e($r['firstName'].' '.$r['lastName']) ?></td>
                <td><?= e($r['department']) ?></td>
                <td><?= e($r['attendanceDate']) ?></td>
                <td><?= $r['clockInTime'] ? e(date('h:i A', strtotime($r['clockInTime']))) : '-' ?></td>
                <td><span class="badge red">Late</span></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
