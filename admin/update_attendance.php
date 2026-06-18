<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pageTitle = 'Update Attendance';
$id = (int)($_GET['id'] ?? $_POST['recordID'] ?? 0);
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clockIn = $_POST['clockInTime'] ?: null;
    $clockOut = $_POST['clockOutTime'] ?: null;
    $lateStatus = $_POST['lateStatus'] ?? 'Not Late';
    $attendanceStatus = $_POST['attendanceStatus'] ?? 'Pending';
    $adminID = current_user()['adminID'];
    $stmt = $conn->prepare('UPDATE attendance_record SET clockInTime=?, clockOutTime=?, lateStatus=?, attendanceStatus=?, updatedByAdmin=? WHERE recordID=?');
    $stmt->bind_param('sssssi', $clockIn, $clockOut, $lateStatus, $attendanceStatus, $adminID, $id);
    $message = $stmt->execute() ? 'Attendance record updated successfully.' : 'Unable to update attendance record.';
}
$stmt = $conn->prepare("SELECT ar.*, ep.firstName, ep.lastName FROM attendance_record ar JOIN employee_profile ep ON ar.empID=ep.empID WHERE ar.recordID=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Update Attendance Record</h1>
<p class="page-subtitle">Manual correction is only available for administrators.</p>
<?php if ($message): ?><div class="success-box"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?= e($error) ?></div><?php endif; ?>
<?php if (!$record): ?>
<div class="error-box">Attendance record not found.</div>
<?php else: ?>
<div class="small-form">
    <h2 class="section-title"><?= e($record['firstName'].' '.$record['lastName']) ?> - <?= e($record['attendanceDate']) ?></h2>
    <form method="POST" class="grid grid-2">
        <input type="hidden" name="recordID" value="<?= e($record['recordID']) ?>">
        <div class="form-group"><label>Clock-In Time</label><input type="time" name="clockInTime" value="<?= e($record['clockInTime']) ?>"></div>
        <div class="form-group"><label>Clock-Out Time</label><input type="time" name="clockOutTime" value="<?= e($record['clockOutTime']) ?>"></div>
        <div class="form-group"><label>Late Status</label><select name="lateStatus"><option value="Not Late" <?= $record['lateStatus']==='Not Late'?'selected':'' ?>>Not Late</option><option value="Late" <?= $record['lateStatus']==='Late'?'selected':'' ?>>Late</option></select></div>
        <div class="form-group"><label>Attendance Status</label><select name="attendanceStatus"><option value="Pending" <?= $record['attendanceStatus']==='Pending'?'selected':'' ?>>Pending</option><option value="On Time" <?= $record['attendanceStatus']==='On Time'?'selected':'' ?>>On Time</option><option value="Late" <?= $record['attendanceStatus']==='Late'?'selected':'' ?>>Late</option><option value="Absent" <?= $record['attendanceStatus']==='Absent'?'selected':'' ?>>Absent</option></select></div>
        <button class="btn btn-green" type="submit">Save Correction</button>
        <a class="btn btn-outline" href="records.php">Back to Records</a>
    </form>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
