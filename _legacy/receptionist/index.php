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
    
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <title>Receptionist Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5"><div class="row justify-content-center"><div class="col-md-5">
    <div class="card shadow-sm"><div class="card-body p-4">
        <h4 class="mb-3">Receptionist Login</h4>
        <form method="post" action="process.php">
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
            <button class="btn btn-primary w-100" type="submit" name="login">Login</button>
        </form>
    </div></div>
</div></div></div>
</body>
</html>


