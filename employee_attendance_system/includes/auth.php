<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        $base = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : '';
        header('Location: ' . $base . 'login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['user']['role'] !== 'Admin') {
        $base = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : '';
        header('Location: ' . $base . 'dashboard.php');
        exit;
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_admin() {
    return is_logged_in() && $_SESSION['user']['role'] === 'Admin';
}

function ensure_profile_image_column($conn) {
    $result = $conn->query("SHOW COLUMNS FROM employee_profile LIKE 'profileImage'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE employee_profile ADD profileImage VARCHAR(255) NULL AFTER email");
    }
}

function get_user_profile($conn, $empID) {
    $stmt = $conn->prepare('SELECT * FROM employee_profile WHERE empID = ?');
    $stmt->bind_param('s', $empID);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function today_record($conn, $empID) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare('SELECT * FROM attendance_record WHERE empID = ? AND attendanceDate = ?');
    $stmt->bind_param('ss', $empID, $today);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function get_full_name($profile) {
    return trim(($profile['firstName'] ?? '') . ' ' . ($profile['lastName'] ?? ''));
}

function profile_photo_url($profile, $base = '') {
    $photo = trim($profile['profileImage'] ?? '');
    if ($photo === '') return '';
    return $base . $photo;
}

function sync_daily_attendance($conn) {
    // Simulate automatic attendance evaluation whenever the system is opened.
    // After 9:15 AM: active employees without clock-in are temporarily marked as Late.
    // After 6:00 PM: active employees who still have not clocked in are marked as Absent.
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $lateThreshold = '09:15:00';
    $endTime = '18:00:00';

    if ($now <= $lateThreshold) {
        return;
    }

    $targetStatus = ($now >= $endTime) ? 'Absent' : 'Late';

    $employees = $conn->query("SELECT empID FROM employee_profile WHERE employmentStatus='ACTIVE'");
    while ($emp = $employees->fetch_assoc()) {
        $empID = $emp['empID'];
        $stmt = $conn->prepare('SELECT recordID, clockInTime FROM attendance_record WHERE empID=? AND attendanceDate=?');
        $stmt->bind_param('ss', $empID, $today);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();

        if (!$record) {
            $lateStatus = ($targetStatus === 'Absent') ? 'Not Late' : 'Late';
            $stmt2 = $conn->prepare('INSERT INTO attendance_record (empID, attendanceDate, clockInTime, clockOutTime, lateStatus, attendanceStatus) VALUES (?, ?, NULL, NULL, ?, ?)');
            $stmt2->bind_param('ssss', $empID, $today, $lateStatus, $targetStatus);
            $stmt2->execute();
        } elseif (!$record['clockInTime']) {
            $lateStatus = ($targetStatus === 'Absent') ? 'Not Late' : 'Late';
            $stmt3 = $conn->prepare('UPDATE attendance_record SET lateStatus=?, attendanceStatus=? WHERE recordID=?');
            $stmt3->bind_param('ssi', $lateStatus, $targetStatus, $record['recordID']);
            $stmt3->execute();
        }
    }
}

function attendance_percentage($conn, $empID = null) {
    // Percentage is calculated based on days/records where the user was present and not late.
    if ($empID) {
        $stmt = $conn->prepare("SELECT COUNT(*) total, SUM(CASE WHEN clockInTime IS NOT NULL AND lateStatus='Not Late' THEN 1 ELSE 0 END) onTimeCount FROM attendance_record WHERE empID = ?");
        $stmt->bind_param('s', $empID);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) total, SUM(CASE WHEN clockInTime IS NOT NULL AND lateStatus='Not Late' THEN 1 ELSE 0 END) onTimeCount FROM attendance_record");
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $total = (int)($row['total'] ?? 0);
    $onTime = (int)($row['onTimeCount'] ?? 0);
    if ($total === 0) return 0;
    return round(($onTime / $total) * 100);
}

function attendance_badge_class($record) {
    if (!$record) return 'orange';
    if (($record['attendanceStatus'] ?? '') === 'Absent') return 'red';
    if (($record['lateStatus'] ?? '') === 'Late') return 'red';
    if (($record['attendanceStatus'] ?? '') === 'Pending') return 'orange';
    return 'green';
}

function attendance_display_status($record) {
    if (!$record) return 'Not Clocked In';
    if (($record['attendanceStatus'] ?? '') === 'Absent') return 'Absent';
    if (($record['lateStatus'] ?? '') === 'Late') return 'Late';
    if (!empty($record['clockInTime']) && empty($record['clockOutTime'])) return 'Clocked In';
    return $record['attendanceStatus'] ?: 'Pending';
}

function user_photo_initial($name) {
    $name = trim($name);
    return $name !== '' ? strtoupper(substr($name, 0, 1)) : 'U';
}
?>
