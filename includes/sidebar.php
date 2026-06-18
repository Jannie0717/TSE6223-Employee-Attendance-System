<?php
$user = current_user();
$profile = get_user_profile($conn, $user['empID']);
$name = get_full_name($profile);
$current = basename($_SERVER['SCRIPT_NAME']);
$isAdminPage = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;
$base = $isAdminPage ? '../' : '';
$photoUrl = profile_photo_url($profile, $base);

function active_link($file) {
    return basename($_SERVER['SCRIPT_NAME']) === $file ? 'active' : '';
}
?>

<aside class="sidebar">
    <div class="brand">
        <span class="brand-icon">
            <img src="<?= $base ?>icon/icon_Employee_Attendance_System.png"
                 class="brand-img black-filter"
                 width="25"
                 height="25"
                 alt="Employee Attendance System Icon">
        </span>
        Employee Attendance System
    </div>

    <div class="user-pill">
        <div class="avatar">
            <?php if ($photoUrl): ?>
                <img src="<?= e($photoUrl) ?>" alt="Profile picture">
            <?php else: ?>
                <?= e(user_photo_initial($name)) ?>
            <?php endif; ?>
        </div>
        <div>
            <strong><?= e($name ?: 'Name') ?></strong>
            <small><?= e($user['role']) ?></small>
        </div>
    </div>

    <nav class="nav-menu">
        <a class="<?= active_link('dashboard.php') ?>" href="<?= $base ?>dashboard.php">
            <span>
                <img src="<?= $base ?>icon/icon_Dashboard.png" class="nav-icon" width="20" height="20" alt="Dashboard Icon">
            </span>
            Dashboard <b>›</b>
        </a>

        <a class="<?= active_link('clock.php') ?>" href="<?= $base ?>clock.php">
            <span>
                <img src="<?= $base ?>icon/icon_Clock.png" class="nav-icon" width="20" height="20" alt="Clock Icon">
            </span>
            Clock In / Clock Out <b>›</b>
        </a>

        <?php if (is_admin()): ?>
            <a class="<?= active_link('records.php') ?>" href="<?= $base ?>admin/records.php">
                <span>
                    <img src="<?= $base ?>icon/icon_Attendance.png" class="nav-icon" width="20" height="20" alt="Attendance Records Icon">
                </span>
                Attendance Records <b>›</b>
            </a>

            <a class="<?= active_link('late.php') ?>" href="<?= $base ?>admin/late.php">
                <span>
                    <img src="<?= $base ?>icon/icon_Late.png" class="nav-icon" width="20" height="20" alt="Late Employees Icon">
                </span>
                Late Employees <b>›</b>
            </a>

            <a class="<?= active_link('employees.php') ?>" href="<?= $base ?>admin/employees.php">
                <span>
                    <img src="<?= $base ?>icon/icon_Employee.png" class="nav-icon" width="20" height="20" alt="Employee Management Icon">
                </span>
                Employee Management <b>›</b>
            </a>
        <?php else: ?>
            <a class="<?= active_link('history.php') ?>" href="<?= $base ?>history.php">
                <span>
                    <img src="<?= $base ?>icon/icon_Attendance.png" class="nav-icon" width="20" height="20" alt="Attendance History Icon">
                </span>
                My Attendance History <b>›</b>
            </a>
        <?php endif; ?>

        <a class="<?= active_link('profile.php') ?>" href="<?= $base ?>profile.php">
            <span>
                <img src="<?= $base ?>icon/icon_Profile.png" class="nav-icon" width="20" height="20" alt="Profile Icon">
            </span>
            My Profile <b>›</b>
        </a>
    </nav>

    <div class="logout-wrap">
        <a class="logout" href="<?= $base ?>logout.php">↪ Logout</a>
    </div>
</aside>