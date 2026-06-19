<?php
session_start();
require_once __DIR__ . '/config/db.php';
$error = '';
$base = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Employee';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT ua.*, ep.firstName, ep.lastName, ep.department FROM user_account ua JOIN employee_profile ep ON ua.empID = ep.empID WHERE ua.email = ? AND ua.role = ?");
        $stmt->bind_param('ss', $email, $role);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if ($account && password_verify($password, $account['passwordHash'])) {
            $_SESSION['user'] = [
                'loginID' => $account['loginID'],
                'empID' => $account['empID'],
                'adminID' => $account['adminID'],
                'email' => $account['email'],
                'role' => $account['role'],
                'name' => trim($account['firstName'] . ' ' . $account['lastName']),
                'department' => $account['department']
            ];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email, password, or selected role.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employee Attendance System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-body">
    <form class="login-card" method="POST">
        <div class="login-logo"><img src="icon/icon_Employee_Attendance_System.png" class="login-system-icon"
                alt="Employee Attendance System Icon"></div>
        <div class="company-title">JolSpinTech Solution Sdn. Bhd.</div>
        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <input type="hidden" name="role" id="roleInput" value="Employee">
        <div class="role-tabs">
            <button type="button" class="role-btn active" data-role="Employee">
                <img src="icon/icon_Login.png" class="role-icon" width="20" height="20" alt="Employee Icon">
                Employee
            </button>

            <button type="button" class="role-btn" data-role="Admin">
                <img src="icon/icon_Admin.png" class="role-icon" width="20" height="20" alt="Admin Icon">
                Admin
            </button>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="employee@jolspintech.com" autocomplete="username"
                inputmode="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="password123" autocomplete="current-password" required>
        </div>
        <button class="btn btn-dark full" type="submit">✓ Login</button>
        <div class="login-footer">
            Employee Attendance System v1.0<br>
            @Copyrighted<br><br>
            Demo Employee: employee@jolspintech.com<br>
            Demo Admin: admin@jolspintech.com<br>
            Password: password123
        </div>
    </form>
    <script src="assets/js/app.js"></script>
</body>

</html>