<?php
require_once __DIR__ . '/../auth.php';
if (isset($_GET['logout'])) {
    auth_logout();
    header("location:index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Doctor Login</title>
    
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background: linear-gradient(to right, #e8f4ff, #ffffff); min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Segoe UI', sans-serif; }
        .login-box { background: #fff; padding: 40px 35px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 420px; }
        .logo { text-align: center; margin-bottom: 20px; }
        .logo img { max-width: 180px; height: auto; }
        .role-switch { display: flex; gap: 8px; margin-bottom: 16px; }
        .role-switch .btn { flex: 1; border-radius: 8px; font-weight: 600; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="logo">
        <img src="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" alt="KORT Logo">
    </div>
    <h2 class="text-center mb-3">Doctor Login</h2>
    <div class="role-switch">
        <a href="../index.php" class="btn btn-outline-primary">Teacher</a>
        <a href="../admin/index.php" class="btn btn-outline-primary">Admin</a>
        <a href="../principal/index.php" class="btn btn-outline-primary">Principal</a>
        <a href="index.php" class="btn btn-primary">Doctor</a>
    </div>
    <form method="post" action="process.php">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
    </form>
</div>
</body>
</html>

