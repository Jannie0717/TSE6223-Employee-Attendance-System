<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
sync_daily_attendance($conn);
$pageTitle = 'Clock In / Clock Out';
$user = current_user();
$profile = get_user_profile($conn, $user['empID']);
$message = '';
$messageType = 'ok';
$today = date('Y-m-d');
$nowTime = date('H:i:s');
$lateThreshold = '09:15:00';
$endTime = '18:00:00';
$afterWorkHours = ($nowTime >= $endTime);
$isWorkingDay = is_workday();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $empID = $user['empID'];
    $record = today_record($conn, $empID);

    if (!$isWorkingDay) {
        $message = 'Clock-in and clock-out are only available from Monday to Friday.';
        $messageType = 'warn';
    } elseif ($afterWorkHours) {
        $message = 'Working hours have ended. Clock-in and clock-out are no longer available for today.';
        $messageType = 'warn';
    } elseif ($action === 'clock_in') {
        if ($record && $record['clockInTime']) {
            $message = 'You have already clocked in for today.';
            $messageType = 'warn';
        } else {
            $lateStatus = ($nowTime > $lateThreshold) ? 'Late' : 'Not Late';
            $attendanceStatus = 'Pending';
            if ($record && !$record['clockInTime']) {
                $stmt = $conn->prepare("UPDATE attendance_record SET clockInTime=?, lateStatus=?, attendanceStatus=? WHERE recordID=?");
                $stmt->bind_param('sssi', $nowTime, $lateStatus, $attendanceStatus, $record['recordID']);
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance_record (empID, attendanceDate, clockInTime, lateStatus, attendanceStatus) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssss', $empID, $today, $nowTime, $lateStatus, $attendanceStatus);
            }
            if ($stmt->execute()) {
                $message = 'Clock-in recorded successfully. Your clock-in time has been recorded at ' . date('h:i A', strtotime($nowTime)) . '.';
                $messageType = 'ok';
            } else {
                $message = 'Unable to record clock-in. Please try again.';
                $messageType = 'warn';
            }
        }
    } elseif ($action === 'clock_out') {
        if (!$record || !$record['clockInTime']) {
            if ($afterWorkHours) {
                $message = 'Working hours have ended. You are marked as absent and cannot clock out today.';
            } else {
                $message = 'Please clock in before clocking out.';
            }
            $messageType = 'warn';
        } elseif (($record['attendanceStatus'] ?? '') === 'Absent') {
            $message = 'You are marked as absent and cannot clock out today.';
            $messageType = 'warn';
        } elseif ($record['clockOutTime']) {
            $message = 'You have already clocked out for today.';
            $messageType = 'warn';
        } else {
            $finalStatus = ($record['lateStatus'] === 'Late') ? 'Late' : 'On Time';
            $stmt = $conn->prepare("UPDATE attendance_record SET clockOutTime = ?, attendanceStatus = ? WHERE recordID = ?");
            $stmt->bind_param('ssi', $nowTime, $finalStatus, $record['recordID']);
            if ($stmt->execute()) {
                $message = 'Clock-out recorded successfully. Your clock-out time has been recorded at ' . date('h:i A', strtotime($nowTime)) . '.';
                $messageType = 'ok';
            } else {
                $message = 'Unable to record clock-out. Please try again.';
                $messageType = 'warn';
            }
        }
    }
}

$todayRec = today_record($conn, $user['empID']);
$clockIn = $todayRec['clockInTime'] ?? null;
$clockOut = $todayRec['clockOutTime'] ?? null;
$status = attendance_display_status($todayRec);
$isAbsent = ($todayRec && ($todayRec['attendanceStatus'] ?? '') === 'Absent');
$clockInDisabled = (!$isWorkingDay || $clockIn || $isAbsent || $afterWorkHours);
$clockOutDisabled = (!$isWorkingDay || !$clockIn || $clockOut || $isAbsent || $afterWorkHours);
include __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Clock In / Clock Out</h1>
<p class="page-subtitle">Record your attendance by clocking in and out based on the current server time.</p>

<?php if (!$isWorkingDay): ?>
<div class="error-box">Today is a non-working day. Clock-in and clock-out are only available from Monday to Friday.</div>
<?php elseif ($isAbsent): ?>
<div class="error-box">Working hours have ended. Employees without clock-in are automatically marked as Absent, and clock-in/clock-out are no longer available for today.</div>
<?php endif; ?>

<div class="grid grid-3">
    <div class="card info-card">
        <div class="info-head head-blue">ⓘ <?= is_admin() ? 'Administrator Information' : 'Employee Information' ?></div>
        <div class="info-body">
            <div class="row"><span class="row-label"><?= is_admin() ? 'Admin Name' : 'Employee Name' ?> :</span><span class="row-value"><?= e(get_full_name($profile)) ?></span></div>
            <div class="row"><span class="row-label"><?= is_admin() ? 'Admin ID' : 'Employee ID' ?> :</span><span class="row-value"><?= e(is_admin() ? $user['adminID'] : $user['empID']) ?></span></div>
            <div class="row"><span class="row-label">Department :</span><span class="row-value"><?= e($profile['department']) ?></span></div>
        </div>
    </div>
    <div class="card info-card">
        <div class="info-head head-green">◷ Current Server Date & Time</div>
        <div class="info-body">
            <div class="server-date"><?= date('l, F d, Y') ?></div>
            <div class="server-time" data-server-clock><?= date('h:i:s A') ?></div>
        </div>
    </div>
    <div class="card info-card">
        <div class="info-head head-orange">▣ Attendance Rules</div>
        <div class="info-body">
            <div class="row"><span class="row-label">Official Working Hours:</span><span class="row-value">9:00 AM - 6:00 PM</span></div>
            <hr>
            <div class="row"><span class="row-label">Late Threshold:</span><span class="row-value">After 9:15 AM</span></div>
            <hr>
            <div class="row"><span class="row-label">Working Days:</span><span class="row-value">Monday - Friday</span></div>
            <hr>
            <div class="row"><span class="row-label">Absent Rule:</span><span class="row-value">No clock-in after 6:00 PM</span></div>
        </div>
    </div>
</div>

<div class="section-card">
    <h2 class="section-title">Today’s Attendance Status:</h2>
    <div class="status-line">
        <div class="status-box"><div class="stat-icon green">☑</div><div><strong>Clock-In Time</strong><div class="status-big green"><?= $clockIn ? e(date('h:i A', strtotime($clockIn))) : '-' ?></div></div></div>
        <div class="status-box"><div class="stat-icon orange">↪</div><div><strong>Clock-Out Time</strong><div class="status-big orange"><?= $clockOut ? e(date('h:i A', strtotime($clockOut))) : 'Pending' ?></div></div></div>
        <div class="status-box"><div class="stat-icon blue">✓</div><div><strong>Attendance Status</strong><div class="status-big blue"><?= e($status) ?></div></div></div>
    </div>
</div>

<div class="section-card">
    <h2 class="section-title">Action:</h2>
    <form method="POST" class="actions">
        <button class="btn btn-green" type="submit" name="action" value="clock_in" <?= $clockInDisabled ? 'disabled' : '' ?>>☑ Clock In</button>
        <button class="btn btn-soft" type="submit" name="action" value="clock_out" <?= $clockOutDisabled ? 'disabled' : '' ?>>↪ Clock Out</button>
        <a class="btn btn-outline" href="dashboard.php">← Back to Dashboard</a>
    </form>
</div>

<?php if ($message): ?>
<div class="alert <?= $messageType === 'ok' ? 'ok' : 'warn' ?>">
    <div class="mark"><?= $messageType === 'ok' ? '✓' : '!' ?></div>
    <div><strong><?= $messageType === 'ok' ? 'Success' : 'Warning' ?></strong><br><?= e($message) ?></div>
    <span class="close-x">×</span>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
