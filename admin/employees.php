<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$pageTitle = 'Employee Management';
$message = '';
$error = '';

function next_emp_id($conn) {
    $row = $conn->query("SELECT empID FROM employee_profile ORDER BY empID DESC LIMIT 1")->fetch_assoc();
    $num = $row ? ((int)substr($row['empID'], 3) + 1) : 123;
    return 'EMP' . str_pad($num, 5, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empID = $_POST['empID'] ?? '';
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $jobPosition = trim($_POST['jobPosition'] ?? '');
    $contactNum = trim($_POST['contactNum'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['employmentStatus'] ?? 'ACTIVE';
    $dateJoined = $_POST['dateJoined'] ?? date('Y-m-d');

    if (isset($_POST['add_employee'])) {
        $newID = next_emp_id($conn);
        $stmt = $conn->prepare('INSERT INTO employee_profile (empID, firstName, lastName, department, jobPosition, contactNum, email, employmentStatus, dateJoined) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('sssssssss', $newID, $firstName, $lastName, $department, $jobPosition, $contactNum, $email, $status, $dateJoined);
        if ($stmt->execute()) {
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $role = 'Employee';
            $stmt2 = $conn->prepare('INSERT INTO user_account (loginID, empID, email, passwordHash, role) VALUES (?,?,?,?,?)');
            $stmt2->bind_param('sssss', $newID, $newID, $email, $hash, $role);
            $stmt2->execute();
            $message = 'Employee added successfully. Default password is password123.';
        } else {
            $error = 'Unable to add employee. Email may already exist.';
        }
    }

    if (isset($_POST['update_employee'])) {
        $stmt = $conn->prepare('UPDATE employee_profile SET firstName=?, lastName=?, department=?, jobPosition=?, contactNum=?, email=?, employmentStatus=?, dateJoined=? WHERE empID=?');
        $stmt->bind_param('sssssssss', $firstName, $lastName, $department, $jobPosition, $contactNum, $email, $status, $dateJoined, $empID);
        $message = $stmt->execute() ? 'Employee updated successfully.' : 'Unable to update employee.';
        $stmt2 = $conn->prepare('UPDATE user_account SET email=? WHERE empID=?');
        $stmt2->bind_param('ss', $email, $empID);
        $stmt2->execute();
    }
}

if (isset($_GET['delete'])) {
    $deleteID = $_GET['delete'];
    if ($deleteID === current_user()['empID']) {
        $error = 'You cannot delete your own employee record.';
    } else {
        $stmt = $conn->prepare('DELETE FROM employee_profile WHERE empID=?');
        $stmt->bind_param('s', $deleteID);
        $message = $stmt->execute() ? 'Employee deleted successfully.' : 'Unable to delete employee.';
    }
}

$editEmployee = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare('SELECT * FROM employee_profile WHERE empID=?');
    $stmt->bind_param('s', $_GET['edit']);
    $stmt->execute();
    $editEmployee = $stmt->get_result()->fetch_assoc();
}
$employees = $conn->query("SELECT ep.*, ua.role FROM employee_profile ep LEFT JOIN user_account ua ON ep.empID = ua.empID ORDER BY ep.empID");
include __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">Employee Management</h1>
<p class="page-subtitle">Add, edit, and delete employee records.</p>
<?php if ($message): ?><div class="success-box"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?= e($error) ?></div><?php endif; ?>

<?php if (!$editEmployee): ?>
<div class="top-actions" style="justify-content:flex-start;margin-bottom:16px">
    <button type="button" class="btn btn-green" data-toggle-target="addEmployeeForm">＋ Add Employee</button>
</div>
<?php endif; ?>

<div class="small-form toggle-panel <?= $editEmployee ? '' : 'hidden' ?>" id="addEmployeeForm">
    <h2 class="section-title"><?= $editEmployee ? 'Edit Employee' : 'Add New Employee' ?></h2>
    <form method="POST" class="grid grid-2">
        <input type="hidden" name="empID" value="<?= e($editEmployee['empID'] ?? '') ?>">
        <div class="form-group"><label>First Name</label><input name="firstName" value="<?= e($editEmployee['firstName'] ?? '') ?>" required></div>
        <div class="form-group"><label>Last Name</label><input name="lastName" value="<?= e($editEmployee['lastName'] ?? '') ?>" required></div>
        <div class="form-group"><label>Department</label><input name="department" value="<?= e($editEmployee['department'] ?? '') ?>" required></div>
        <div class="form-group"><label>Job Position</label><input name="jobPosition" value="<?= e($editEmployee['jobPosition'] ?? '') ?>" required></div>
        <div class="form-group"><label>Contact Number</label><input name="contactNum" value="<?= e($editEmployee['contactNum'] ?? '') ?>" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($editEmployee['email'] ?? '') ?>" required></div>
        <div class="form-group"><label>Employment Status</label><select name="employmentStatus"><option value="ACTIVE" <?= ($editEmployee['employmentStatus'] ?? '')==='ACTIVE'?'selected':'' ?>>ACTIVE</option><option value="INACTIVE" <?= ($editEmployee['employmentStatus'] ?? '')==='INACTIVE'?'selected':'' ?>>INACTIVE</option></select></div>
        <div class="form-group"><label>Date Joined</label><input type="date" name="dateJoined" value="<?= e($editEmployee['dateJoined'] ?? date('Y-m-d')) ?>" required></div>
        <div class="form-group"><button class="btn btn-green full" name="<?= $editEmployee ? 'update_employee' : 'add_employee' ?>"><?= $editEmployee ? 'Update Employee' : 'Add Employee' ?></button></div>
        <?php if ($editEmployee): ?><div class="form-group"><a class="btn btn-outline full" href="employees.php">Cancel Edit</a></div><?php endif; ?>
    </form>
</div>

<div class="table-wrap">
    <h2 class="section-title">Employee List</h2>
    <table>
        <thead><tr><th>Employee ID</th><th>Full Name</th><th>Department</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php while($eRow=$employees->fetch_assoc()): ?>
            <tr>
                <td><?= e($eRow['empID']) ?></td>
                <td><?= e($eRow['firstName'].' '.$eRow['lastName']) ?></td>
                <td><?= e($eRow['department']) ?></td>
                <td><?= e($eRow['email']) ?></td>
                <td><span class="badge green"><?= e($eRow['employmentStatus']) ?></span></td>
                <td class="table-actions"><a href="employees.php?edit=<?= e($eRow['empID']) ?>">Edit</a><a onclick="return confirm('Delete this employee?')" href="employees.php?delete=<?= e($eRow['empID']) ?>">Delete</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
