<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
ensure_profile_image_column($conn);
$pageTitle = 'My Profile';
$user = current_user();
$message = '';
$error = '';
$profile = get_user_profile($conn, $user['empID']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $newPass = $_POST['newPassword'] ?? '';
        if (strlen($newPass) < 6) {
            $error = 'Password must contain at least 6 characters.';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE user_account SET passwordHash=? WHERE loginID=?');
            $stmt->bind_param('ss', $hash, $user['loginID']);
            $message = $stmt->execute() ? 'Password changed successfully.' : 'Unable to change password.';
        }
    }

    if (isset($_POST['upload_photo'])) {
        if (!isset($_FILES['profilePhoto']) || $_FILES['profilePhoto']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please select a valid image to upload.';
        } else {
            $file = $_FILES['profilePhoto'];
            $maxSize = 2 * 1024 * 1024;
            $allowedMime = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            $imageInfo = @getimagesize($file['tmp_name']);
            $mime = $imageInfo['mime'] ?? '';

            if ($file['size'] > $maxSize) {
                $error = 'Profile picture must be smaller than 2MB.';
            } elseif (!isset($allowedMime[$mime])) {
                $error = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
            } else {
                $uploadDir = __DIR__ . '/uploads/profiles';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $ext = $allowedMime[$mime];
                $fileName = $user['empID'] . '_' . time() . '.' . $ext;
                $relativePath = 'uploads/profiles/' . $fileName;
                $targetPath = $uploadDir . '/' . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $oldPhoto = $profile['profileImage'] ?? '';
                    if ($oldPhoto && file_exists(__DIR__ . '/' . $oldPhoto)) {
                        @unlink(__DIR__ . '/' . $oldPhoto);
                    }
                    $stmt = $conn->prepare('UPDATE employee_profile SET profileImage=? WHERE empID=?');
                    $stmt->bind_param('ss', $relativePath, $user['empID']);
                    if ($stmt->execute()) {
                        $message = 'Profile picture updated successfully.';
                        $profile = get_user_profile($conn, $user['empID']);
                    } else {
                        $error = 'Unable to save profile picture.';
                    }
                } else {
                    $error = 'Unable to upload profile picture.';
                }
            }
        }
    }
}

$profile = get_user_profile($conn, $user['empID']);
$name = get_full_name($profile);
$photoUrl = profile_photo_url($profile, '');
include __DIR__ . '/includes/header.php';
?>
<?php if ($message): ?><div class="success-box"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="error-box"><?= e($error) ?></div><?php endif; ?>

<div class="profile-card">
    <div class="profile-top">
        <div class="profile-big-avatar">
            <?php if ($photoUrl): ?>
                <img src="<?= e($photoUrl) ?>" alt="Profile picture">
            <?php else: ?>
                ♙
            <?php endif; ?>
        </div>
        <div class="profile-name"><?= e($name ?: 'Name') ?></div>
        <div class="profile-role">Role : <?= e(strtoupper($user['role'])) ?></div>
    </div>
    <div class="profile-grid">
        <div class="profile-field"><strong>▣ Employee ID</strong><span><?= e(is_admin() ? $user['adminID'] : $profile['empID']) ?></span></div>
        <div class="profile-field"><strong>▥ Department</strong><span><?= e($profile['department']) ?></span></div>
        <div class="profile-field"><strong>✉ Email Address</strong><span><?= e($profile['email']) ?></span></div>
        <div class="profile-field"><strong>☎ Contact Number</strong><span><?= e($profile['contactNum']) ?></span></div>
        <div class="profile-field"><strong>✓ Employment Status</strong><span class="badge green"><?= e($profile['employmentStatus']) ?></span></div>
        <div class="profile-field"><strong>▣ Date Joined</strong><span><?= e($profile['dateJoined']) ?></span></div>
    </div>
    <div class="profile-buttons two-buttons">
        <button type="button" class="btn btn-green" data-toggle-target="changePhoto">🖼 Change Profile Picture</button>
        <button type="button" class="btn btn-blue" data-toggle-target="changePassword">⚿ Change Password</button>
    </div>
</div>

<div class="small-form toggle-panel hidden" id="changePhoto">
    <h2 class="section-title">Change Profile Picture</h2>
    <form method="POST" enctype="multipart/form-data" class="grid grid-2">
        <div class="form-group"><label>Upload Image</label><input type="file" name="profilePhoto" accept="image/*" required></div>
        <div class="form-group"><label>&nbsp;</label><button class="btn btn-green full" name="upload_photo">Upload Picture</button></div>
    </form>
</div>

<div class="small-form toggle-panel hidden" id="changePassword">
    <h2 class="section-title">Change Password</h2>
    <form method="POST" class="grid grid-2">
        <div class="form-group"><label>New Password</label><input type="password" name="newPassword" required></div>
        <div class="form-group"><label>&nbsp;</label><button class="btn btn-blue full" name="change_password">Change Password</button></div>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
