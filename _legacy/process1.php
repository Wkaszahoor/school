<?php
require_once __DIR__ . '/auth.php';
include 'db.php';

if (isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if ($email === false) {
        header("Location: index.php?error=invalid_email");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM `teachers` WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        $storedPassword = (string)($teacher['password'] ?? '');

        // Support both legacy plain-text passwords and bcrypt hashes.
        $isValidPassword = hash_equals($storedPassword, (string)$password) || password_verify((string)$password, $storedPassword);

        if ($isValidPassword) {
            auth_login_user(
                'teacher',
                (int)$teacher['id'],
                (string)$teacher['email'],
                (string)($teacher['name'] ?? 'Teacher')
            );
            header("Location: students/index.php");
            exit();
        }
    }

    header("Location: index.php?error=invalid_credentials");
    exit();
}
?>
