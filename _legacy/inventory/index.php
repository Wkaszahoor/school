<?php
require_once __DIR__ . '/../auth.php';
if (isset($_GET['logout'])) {
    auth_logout();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <title>Inventory Manager Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background:
                linear-gradient(rgba(255,255,255,0.90), rgba(255,255,255,0.90)),
                url('https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg') center 18% / 360px auto no-repeat,
                linear-gradient(to right, #dfe9f3, #ffffff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            overflow: hidden;
        }

        .login-box {
            background: #fff;
            padding: 40px 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-box .logo {
            margin-bottom: 25px;
        }

        .login-box .logo img {
            max-width: 180px;
            height: auto;
        }

        .login-box h2 {
            font-weight: 600;
            margin-bottom: 25px;
            color: #333;
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
            background-color: #f8faff;
        }

        .btn-primary {
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            background-color: #007bff;
            border-color: #007bff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,123,255,0.2);
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,123,255,0.3);
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
<div class="login-box">
    <div class="logo">
        <img src="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" alt="KORT Logo">
    </div>
    <h2>Inventory Manager Login</h2>
    <form method="post" action="process.php">
        <div class="mb-3 text-start">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter your email" required autofocus>
        </div>
        <div class="mb-4 text-start">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <div class="d-flex gap-2 mb-3">
            <a href="../index.php" class="btn btn-outline-primary flex-fill">Teacher</a>
            <a href="../admin/index.php" class="btn btn-outline-primary flex-fill">Admin</a>
            <a href="../principal/index.php" class="btn btn-outline-primary flex-fill">Principal</a>
            <a href="index.php" class="btn btn-primary flex-fill">Inventory</a>
        </div>
        <button class="btn btn-primary w-100" type="submit" name="login">Login</button>
    </form>
</div>
</body>
</html>


