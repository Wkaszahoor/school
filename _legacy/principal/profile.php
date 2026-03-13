<?php
require_once __DIR__ . '/../auth.php';
auth_require_roles(['principal'], 'index.php');
include '../db.php';

$principalId = (int)($_SESSION['auth_user_id'] ?? 0);
$flashType = '';
$flashMessage = '';

// Ensure staff_users has avatar_url column.
$colCheck = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff_users' AND column_name = 'avatar_url'
");
if ($colCheck) {
    $colCheck->execute();
    $hasAvatarCol = (int)($colCheck->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $colCheck->close();
    if (!$hasAvatarCol) {
        $conn->query("ALTER TABLE staff_users ADD COLUMN avatar_url VARCHAR(255) NULL AFTER name");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim((string)($_POST['name'] ?? ''));
    $avatarUrl = trim((string)($_POST['avatar_url'] ?? ''));
    if ($name === '') {
        $flashType = 'danger';
        $flashMessage = 'Name is required.';
    } elseif ($principalId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid principal session.';
    } else {
        $stmt = $conn->prepare("UPDATE staff_users SET name = ?, avatar_url = ? WHERE id = ? AND role = 'principal' LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ssi", $name, $avatarUrl, $principalId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['auth_name'] = $name;
            $_SESSION['auth_avatar'] = $avatarUrl;
            $flashType = 'success';
            $flashMessage = 'Profile updated successfully.';
            auth_audit_log($conn, 'edit', 'principal_profile', (string)$principalId, null, json_encode(['name' => $name, 'avatar_url' => $avatarUrl]));
        } else {
            $flashType = 'danger';
            $flashMessage = 'Failed to update profile.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    if ($principalId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid principal session.';
    } elseif ($newPassword === '' || strlen($newPassword) < 6) {
        $flashType = 'danger';
        $flashMessage = 'New password must be at least 6 characters.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $flashType = 'danger';
        $flashMessage = 'New password and confirm password do not match.';
    } else {
        $stmt = $conn->prepare("SELECT password FROM staff_users WHERE id = ? AND role = 'principal' LIMIT 1");
        $storedPassword = '';
        if ($stmt) {
            $stmt->bind_param("i", $principalId);
            $stmt->execute();
            $storedPassword = (string)($stmt->get_result()->fetch_assoc()['password'] ?? '');
            $stmt->close();
        }

        if ($storedPassword === '' || !auth_verify_password($storedPassword, $currentPassword)) {
            $flashType = 'danger';
            $flashMessage = 'Current password is incorrect.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE staff_users SET password = ? WHERE id = ? AND role = 'principal' LIMIT 1");
            if ($upd) {
                $upd->bind_param("si", $newHash, $principalId);
                $upd->execute();
                $upd->close();
                $flashType = 'success';
                $flashMessage = 'Password changed successfully.';
                auth_audit_log($conn, 'change_password', 'principal_profile', (string)$principalId);
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to update password.';
            }
        }
    }
}

$profile = [
    'name' => (string)($_SESSION['auth_name'] ?? 'Principal'),
    'email' => (string)($_SESSION['auth_email'] ?? ''),
    'avatar_url' => (string)($_SESSION['auth_avatar'] ?? ''),
];
if ($principalId > 0) {
    $stmt = $conn->prepare("SELECT name, email, avatar_url FROM staff_users WHERE id = ? AND role = 'principal' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $principalId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $profile = [
                'name' => (string)($row['name'] ?? $profile['name']),
                'email' => (string)($row['email'] ?? $profile['email']),
                'avatar_url' => (string)($row['avatar_url'] ?? $profile['avatar_url']),
            ];
            $_SESSION['auth_name'] = $profile['name'];
            $_SESSION['auth_avatar'] = $profile['avatar_url'];
        }
    }
}

$avatarPreview = trim($profile['avatar_url']) !== '' ? $profile['avatar_url'] : 'https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg';

include './partials/topbar.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Principal Profile</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType ?: 'info', ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <img src="<?php echo htmlspecialchars($avatarPreview, ENT_QUOTES, 'UTF-8'); ?>" alt="Principal Avatar" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:2px solid #d1d3e2;">
                    <h5 class="mt-3 mb-1"><?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                    <div class="text-muted"><?php echo htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <span class="badge badge-primary mt-2">Principal</span>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update Profile</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Avatar / Image URL</label>
                            <input type="text" class="form-control" name="avatar_url" value="<?php echo htmlspecialchars($profile['avatar_url'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://example.com/image.jpg">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Profile</button>
                    </form>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Change Password</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>New Password</label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-danger">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
