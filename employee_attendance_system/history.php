<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
sync_daily_attendance($conn);
$pageTitle = 'My Attendance History';
$user = current_user();
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM attendance_record WHERE empID = ?";
$params = [$user['empID']];
$types = 's';
if ($search !== '') { $sql .= " AND (attendanceDate LIKE ? OR lateStatus LIKE ? OR attendanceStatus LIKE ?)"; $like="%$search%"; array_push($params,$like,$like,$like); $types.='sss'; }
if ($dateFrom !== '') { $sql .= " AND attendanceDate >= ?"; $params[]=$dateFrom; $types.='s'; }
if ($dateTo !== '') { $sql .= " AND attendanceDate <= ?"; $params[]=$dateTo; $types.='s'; }
if ($status === 'Not Late') { $sql .= " AND lateStatus = 'Not Late' AND attendanceStatus <> 'Absent'"; }
if ($status === 'Late') { $sql .= " AND lateStatus = 'Late' AND attendanceStatus <> 'Absent'"; }
if ($status === 'Absent') { $sql .= " AND attendanceStatus = 'Absent'"; }
$sql .= " ORDER BY attendanceDate DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$records = $stmt->get_result();

$summaryStmt = $conn->prepare("SELECT COUNT(*) total, SUM(CASE WHEN lateStatus='Late' THEN 1 ELSE 0 END) lateCount FROM attendance_record WHERE empID=?");
$summaryStmt->bind_param('s',$user['empID']);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();
$total = (int)$summary['total'];
$late = (int)$summary['lateCount'];
$percentage = attendance_percentage($conn, $user['empID']);
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">My Attendance History</h1>
<p class="page-subtitle">Your attendance history by recording the date, day, clock-in time, clock-out time and status.</p>

<form class="toolbar" method="GET">
    <input class="search-input" name="search" value="<?= e($search) ?>" placeholder="🔍 Search">
    <input class="filter-input" type="date" name="from" value="<?= e($dateFrom) ?>">
    <input class="filter-input" type="date" name="to" value="<?= e($dateTo) ?>">
    <select class="filter-input" name="status">
        <option value="">All Status</option>
        <option value="Not Late" <?= $status==='Not Late'?'selected':'' ?>>On Time</option>
        <option value="Late" <?= $status==='Late'?'selected':'' ?>>Late</option>
        <option value="Absent" <?= $status==='Absent'?'selected':'' ?>>Absent</option>
    </select>
    <button class="btn btn-dark">Filter</button>
</form>

<div class="grid grid-3">
    <div class="card stat-card"><div class="stat-icon purple">✓</div><div class="stat-number"><?= e($total) ?></div><div class="stat-label">Total attendance Records</div></div>
    <div class="card stat-card"><div class="stat-icon orange">◷</div><div class="stat-number"><?= e($late) ?></div><div class="stat-label">Total Late Days</div></div>
    <div class="card stat-card"><div class="stat-number green"><?= e($percentage) ?>%</div><div class="stat-label">Attendance Percentage</div></div>
</div>

<div class="table-wrap" style="margin-top:16px">
    <h2 class="section-title">Attendance Table</h2>
    <table>
        <thead><tr><th>Date</th><th>Day</th><th>Clock-In Time</th><th>Clock-Out Time</th><th>Status</th></tr></thead>
        <tbody>
        <?php if ($records->num_rows === 0): ?>
            <tr><td colspan="5" class="empty">No attendance records found</td></tr>
        <?php else: while($r=$records->fetch_assoc()): ?>
            <tr>
                <td><?= e($r['attendanceDate']) ?></td>
                <td><?= e(date('l', strtotime($r['attendanceDate']))) ?></td>
                <td><?= $r['clockInTime'] ? e(date('h:i A', strtotime($r['clockInTime']))) : '-' ?></td>
                <td><?= $r['clockOutTime'] ? e(date('h:i A', strtotime($r['clockOutTime']))) : '-' ?></td>
                <?php $displayStatus = attendance_display_status($r); $badgeClass = attendance_badge_class($r); ?>
                <td><span class="badge <?= e($badgeClass) ?>"><?= e($displayStatus === 'Clocked In' ? 'On Time' : $displayStatus) ?></span></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
