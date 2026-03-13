<?php
require_once __DIR__ . '/../auth.php';
include 'db.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    // Validate email format
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if ($email === false) {
        header("location:index.php?error=invalid_email");
        exit();
    }

    // Fetch user by email, then verify password (plain/bcrypt/md5 legacy support).
    $stmt = $conn->prepare("SELECT * FROM `admin` WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $storedPassword = (string)($admin['password'] ?? '');

        $isBcrypt = password_get_info($storedPassword)['algo'] !== null;
        $isMd5Hex = preg_match('/^[a-f0-9]{32}$/i', $storedPassword) === 1;

        $isValidPassword = hash_equals($storedPassword, (string)$password)
            || ($isBcrypt && password_verify((string)$password, $storedPassword))
            || ($isMd5Hex && hash_equals(strtolower($storedPassword), md5((string)$password)));

        if ($isValidPassword) {
            auth_login_user('admin', (int)($admin['id'] ?? 0), (string)$email, (string)($admin['name'] ?? 'Admin'));
            header("location:index1.php");
            exit();
        }
    }

    header("location:index.php?error=invalid_credentials");
    exit();
}
?>
