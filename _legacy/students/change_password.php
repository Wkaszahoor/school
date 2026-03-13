<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teacher_profile', 'change_password', '../index.php');

include "../db.php";

$flashType = '';
$flashMessage = '';

$teacherId = (int)($_SESSION['teacher_id'] ?? 0);
$teacherEmail = (string)($_SESSION['id'] ?? '');

if ($teacherId <= 0 && $teacherEmail !== '') {
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $teacherEmail);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $teacherId = (int)($row['id'] ?? 0);
        $stmt->close();
        if ($teacherId > 0) {
            $_SESSION['teacher_id'] = $teacherId;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($teacherId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Teacher account not found.';
    } elseif ($newPassword === '' || strlen($newPassword) < 6) {
        $flashType = 'danger';
        $flashMessage = 'New password must be at least 6 characters.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $flashType = 'danger';
        $flashMessage = 'New password and confirm password do not match.';
    } else {
        $stmt = $conn->prepare("SELECT password FROM teachers WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $storedPassword = (string)($row['password'] ?? '');
            $isValidCurrent = hash_equals($storedPassword, $currentPassword) || password_verify($currentPassword, $storedPassword);

            if (!$isValidCurrent) {
                $flashType = 'danger';
                $flashMessage = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
                if ($update) {
                    $update->bind_param("si", $newHash, $teacherId);
                    $update->execute();
                    $update->close();
                    $flashType = 'success';
                    $flashMessage = 'Password changed successfully.';
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Failed to update password.';
                }
            }
        } else {
            $flashType = 'danger';
            $flashMessage = 'Failed to load current password.';
        }
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Change Password</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Update Login Password</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="6" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php
include "./partials/footer.php";
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
