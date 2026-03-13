<?php
require_once __DIR__ . '/../auth.php';
include '../db.php';

if (isset($_POST['login'])) {
    $email = filter_var((string)($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    if ($email === false) {
        header('Location: index.php?error=invalid_email');
        exit();
    }
    auth_ensure_staff_users_table($conn);
    $role = 'receptionist';
    $stmt = $conn->prepare("SELECT id, name, email, password, is_active FROM staff_users WHERE email = ? AND role = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ss', $email, $role);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && (int)$row['is_active'] === 1 && auth_verify_password((string)$row['password'], $password)) {
            auth_login_user($role, (int)$row['id'], (string)$row['email'], (string)$row['name']);
            header('Location: dashboard.php');
            exit();
        }
    }
}
header('Location: index.php?error=invalid_credentials');
exit();

