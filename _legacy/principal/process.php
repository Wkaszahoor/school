<?php
require_once __DIR__ . '/../auth.php';
include '../db.php';

// Keep staff_users compatible with profile avatar support.
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

if (isset($_POST['login'])) {
    $email = filter_var((string)($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    if ($email === false) {
        header('Location: index.php?error=invalid_email');
        exit();
    }

    auth_ensure_staff_users_table($conn);
    $role = 'principal';
    $stmt = $conn->prepare("SELECT id, name, email, password, is_active, avatar_url FROM staff_users WHERE email = ? AND role = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ss', $email, $role);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && (int)$row['is_active'] === 1 && auth_verify_password((string)$row['password'], $password)) {
            auth_login_user($role, (int)$row['id'], (string)$row['email'], (string)$row['name']);
            $_SESSION['auth_avatar'] = (string)($row['avatar_url'] ?? '');
            header('Location: dashboard.php');
            exit();
        }
    }
}
header('Location: index.php?error=invalid_credentials');
exit();
