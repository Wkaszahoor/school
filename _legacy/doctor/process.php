<?php
require_once __DIR__ . '/../auth.php';
include '../db.php';

if (isset($_POST['login'])) {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    if ($email === false) {
        header("location:index.php?error=invalid_email");
        exit();
    }

    $conn->query("\n        CREATE TABLE IF NOT EXISTS doctors (\n            id INT AUTO_INCREMENT PRIMARY KEY,\n            name VARCHAR(120) NOT NULL,\n            email VARCHAR(190) NOT NULL UNIQUE,\n            password VARCHAR(255) NOT NULL,\n            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n        )\n    ");

    $stmt = $conn->prepare("SELECT id, name, email, password FROM doctors WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $doc = $res->fetch_assoc();
            $stored = (string)($doc['password'] ?? '');
            $isValid = hash_equals($stored, $password) || password_verify($password, $stored);
            if ($isValid) {
                auth_login_user('doctor', (int)$doc['id'], (string)$doc['email'], (string)$doc['name']);
                header("location:records.php");
                exit();
            }
        }
        $stmt->close();
    }

    header("location:index.php?error=invalid_credentials");
    exit();
}
?>
