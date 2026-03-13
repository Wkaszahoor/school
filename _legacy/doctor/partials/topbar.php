<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <meta name="description" content="KORT Doctor Dashboard">
    <meta name="author" content="KORT">
    <title>KORT - Doctor Panel</title>

    <link href="../admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="../admin/css/sb-admin-2.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav babar sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
                <div class="sidebar-brand-icon" style="transform: rotate(0deg);">
                    <img src="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" alt="KORT Logo" style="width: 63px; height: auto;">
                </div>
                <div class="sidebar-brand-text mx-3">Doctor</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item active">
                <a class="nav-link" href="records.php">
                    <i class="fas fa-fw fa-notes-medical"></i> <span>Approved Records</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php?logout=1" class="nav-link">
                    <i class="nav-icon fas fa-sign-out-alt"></i> Logout
                </a>
            </li>

            <hr class="sidebar-divider">
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars((string)($_SESSION['doctor_name'] ?? 'Doctor'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../admin/img/undraw_profile.svg">
                            </a>
                        </li>
                    </ul>
                </nav>

