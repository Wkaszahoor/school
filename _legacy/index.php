<?php
require_once __DIR__ . '/auth.php';

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
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
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
            max-width: 400px;
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

        .role-switch {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .role-switch .btn {
            flex: 1 1 calc(50% - 8px);
            border-radius: 8px;
            font-weight: 600;
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
    <h2 id="loginTitle">Teacher Login</h2>
    <form method="post" action="process1.php" id="loginForm">
        <div class="mb-3 text-start">
            <label for="email" class="form-label">Email</label>
            <input
                type="email"
                name="email"
                id="email"
                class="form-control"
                placeholder="Enter your email"
                required
                autofocus
            />
        </div>

        <div class="mb-4 text-start">
            <label for="password" class="form-label">Password</label>
            <input
                type="password"
                name="password"
                id="password"
                class="form-control"
                placeholder="Enter your password"
                required
            />
        </div>

        <div class="role-switch">
            <button type="button" class="btn btn-primary role-btn active" data-action="process1.php" data-title="Teacher Login">Teacher</button>
            <button type="button" class="btn btn-outline-primary role-btn" data-action="admin/process.php" data-title="Admin Login" data-redirect="admin/index.php">Admin</button>
            <button type="button" class="btn btn-outline-primary role-btn" data-action="principal/process.php" data-title="Principal Login" data-redirect="principal/index.php">Principal</button>
            <button type="button" class="btn btn-outline-primary role-btn" data-action="doctor/process.php" data-title="Doctor Login" data-redirect="doctor/index.php">Doctor</button>
            <button type="button" class="btn btn-outline-primary role-btn" data-action="inventory/process.php" data-title="Inventory Login" data-redirect="inventory/index.php">Inventory</button>
        </div>

        <input type="hidden" id="selectedRole" value="teacher" />
        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
    </form>
</div>

<script>
    (function () {
        const form = document.getElementById('loginForm');
        const title = document.getElementById('loginTitle');
        const buttons = document.querySelectorAll('.role-btn');
        if (!form || !title || !buttons.length) {
            return;
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const redirect = btn.getAttribute('data-redirect') || '';
                if (redirect !== '') {
                    window.location.href = redirect;
                    return;
                }
                const action = btn.getAttribute('data-action') || 'process1.php';
                const heading = btn.getAttribute('data-title') || 'Login';
                form.setAttribute('action', action);
                title.textContent = heading;
                buttons.forEach(function (b) {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-outline-primary');
                });
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary', 'active');
            });
        });
    })();
</script>

</body>
</html>
