<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
sync_daily_attendance($conn);
$pageTitle = 'Dashboard';
$user = current_user();
$profile = get_user_profile($conn, $user['empID']);
$name = get_full_name($profile);
$today = date('Y-m-d');
$todayRec = today_record($conn, $user['empID']);
$isWorkingDay = is_workday();
$status = $isWorkingDay ? attendance_display_status($todayRec) : 'Non-working Day';

if (is_admin()) {
    $totalEmployees = (int)$conn->query("SELECT COUNT(*) c FROM employee_profile WHERE employmentStatus='ACTIVE'")->fetch_assoc()['c'];
    $presentToday = (int)$conn->query("SELECT COUNT(*) c FROM attendance_record WHERE attendanceDate='$today' AND clockInTime IS NOT NULL")->fetch_assoc()['c'];
    $lateToday = (int)$conn->query("SELECT COUNT(*) c FROM attendance_record WHERE attendanceDate='$today' AND lateStatus='Late' AND attendanceStatus<>'Absent'")->fetch_assoc()['c'];
    $absent = (int)$conn->query("SELECT COUNT(*) c FROM attendance_record WHERE attendanceDate='$today' AND attendanceStatus='Absent'")->fetch_assoc()['c'];
    $totalRecords = (int)$conn->query("SELECT COUNT(*) c FROM attendance_record")->fetch_assoc()['c'];
    $lateAll = (int)$conn->query("SELECT COUNT(*) c FROM attendance_record WHERE lateStatus='Late'")->fetch_assoc()['c'];
    $percentage = attendance_percentage($conn, null);
} else {
    $empID = $user['empID'];
    $stmt = $conn->prepare("SELECT COUNT(*) total, SUM(CASE WHEN lateStatus='Late' THEN 1 ELSE 0 END) lateCount FROM attendance_record WHERE empID=?");
    $stmt->bind_param('s',$empID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $totalRecords = (int)$row['total'];
    $lateAll = (int)$row['lateCount'];
    $totalEmployees = 1;
    $presentToday = ($todayRec && $todayRec['clockInTime']) ? 1 : 0;
    $lateToday = ($todayRec && $todayRec['lateStatus']==='Late' && $todayRec['attendanceStatus'] !== 'Absent') ? 1 : 0;
    $absent = ($todayRec && $todayRec['attendanceStatus'] === 'Absent') ? 1 : 0;
    $percentage = attendance_percentage($conn, $empID);
}
$statusClass = $isWorkingDay ? attendance_badge_class($todayRec) : 'purple';
include __DIR__ . '/includes/header.php';
?>
<div class="hero-card">
    <h1>Welcome, <?= e($name ?: 'Name') ?></h1>
    <p><?= is_admin() ? 'Admin ID' : 'Employee ID' ?>: <?= e(is_admin() ? ($user['adminID'] ?? '') : $user['empID']) ?></p>
    <p><?= e($profile['department']) ?></p>
</div>

<div class="grid grid-3">
    <div class="card stat-card"><div class="stat-icon <?= e($statusClass) ?>">☑</div><div class="stat-number <?= e($statusClass) ?>" style="font-size:24px"><?= e($status) ?></div><div class="stat-label">Attendance Status</div></div>
    <div class="card stat-card"><div class="stat-number green"><?= e($percentage) ?>%</div><div class="stat-label">Attendance Percentage</div></div>
    <div class="card stat-card"><div class="stat-number purple" style="font-size:24px"><?= date('h:i a') ?><br><?= date('d-m-Y') ?></div><div class="stat-label">Current Date & Time</div></div>
    <div class="card stat-card"><div class="stat-icon purple">✓</div><div class="stat-number"><?= e($totalRecords) ?></div><div class="stat-label">Total Working Days</div></div>
    <div class="card stat-card"><div class="stat-icon orange">◷</div><div class="stat-number"><?= e($lateAll) ?></div><div class="stat-label">Number of Late Arrivals</div></div>
    <div class="card stat-card"><div class="stat-icon red">⊗</div><div class="stat-number"><?= e($absent) ?></div><div class="stat-label">Absent</div></div>
    <?php if (is_admin()): ?>
    <div class="card stat-card"><div class="stat-icon purple">♙</div><div class="stat-number"><?= e($presentToday) ?></div><div class="stat-label">Employees present today</div></div>
    <div class="card stat-card"><div class="stat-icon orange">!</div><div class="stat-number"><?= e($lateToday) ?></div><div class="stat-label">Late employees today</div></div>
    <div class="card stat-card"><div class="stat-icon purple">👥</div><div class="stat-number"><?= e($totalEmployees) ?></div><div class="stat-label">Total employees</div></div>
    <?php endif; ?>
</div>

<?php if (!is_admin()): ?>
<div class="section-card">
    <h2 class="section-title">New Activity</h2>
    <div class="empty">🔔<br>No recent activity</div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
