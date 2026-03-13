<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <meta name="description" content="KORT Admin Dashboard">
    <meta name="author" content="Your Name">

    <title>KORT - Admin Dashboard</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="css/sb-admin-2.css" rel="stylesheet">


</head>

<body id="page-top">

    <div id="wrapper">

        <ul class="navbar-nav babar sidebar sidebar-dark accordion" id="accordionSidebar">

           <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
    <div class="sidebar-brand-icon" style="transform: rotate(0deg);">
        <img src="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" alt="KORT Logo" style="width: 63px; height: auto;">
    </div>
    <div class="sidebar-brand-text mx-3">Admin <sup></sup></div>
</a>

            <hr class="sidebar-divider my-0">

        <li class="nav-item active">
    <a class="nav-link" href="index1.php">
        <i class="fas fa-fw fa-home"></i> <span>Dashboard</span>
    </a>
</li>

<li class="nav-item">
    <a href="teacher_detail.php" class="nav-link">
        <i class="nav-icon fas fa-chalkboard-teacher"></i> Teacher Details </a>
</li>

<li class="nav-item">
    <a href="student_detail.php" class="nav-link">
        <i class="nav-icon fas fa-user-graduate"></i> Student Details </a>
</li>

<li class="nav-item">
    <a href="class_year.php" class="nav-link">
        <i class="nav-icon fas fa-school"></i> Class & Year </a>
</li>

<li class="nav-item">
    <a href="class_subjects.php" class="nav-link">
        <i class="nav-icon fas fa-book"></i> Class Subjects </a>
</li>

<li class="nav-item">
    <a href="subjects.php" class="nav-link">
        <i class="nav-icon fas fa-book-open"></i> Subjects </a>
</li>

<li class="nav-item">
    <a href="subject_groups.php" class="nav-link">
        <i class="nav-icon fas fa-layer-group"></i> Subject Groups </a>
</li>

<li class="nav-item">
    <a href="view_attendance.php" class="nav-link">
        <i class="nav-icon fas fa-clipboard-check"></i> View Attendance </a>
</li>

<li class="nav-item">
    <a href="view_faculty.php" class="nav-link">
        <i class="nav-icon fas fa-users"></i> Faculty Attendance </a>
</li>

<li class="nav-item">
    <a href="post_job.php" class="nav-link" id="postJobsLink">
        <i class="nav-icon fas fa-plus-circle"></i> Post Jobs </a>
</li>
<li class="nav-item">
    <a href="view_apllicant.php" class="nav-link" id="postJobsLink">
        <i class="nav-icon fas fa-eye"></i> View Applicants
    </a>
</li>
<li class="nav-item">
    <a href="homework.php" class="nav-link" id="postJobsLink">
        <i class="nav-icon fas fa-eye"></i> Home Work
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" id="viewResultsTab" href="view.php">
        <i class="nav-icon fas fa-poll"></i> View Class Results
    </a>
</li>

<li class="nav-item">
    <a class="nav-link active" id="enterMarksTab" href="assign.php">
        <i class="nav-icon fas fa-edit"></i>Assign teacher
    </a>
</li>
<li class="nav-item">
    <a class="nav-link active" id="enterMarksTab" href="leave.php">
        <i class="nav-icon fas fa-edit"></i>Leave Record
    </a>
</li>
<li class="nav-item">
    <a href="sick.php" class="nav-link" id="postJobsLink">
        <i class="nav-icon fas fa-plus-circle"></i> student sick 
    </a>
</li>

<li class="nav-item">
    <a href="doctor.php" class="nav-link">
        <i class="nav-icon fas fa-notes-medical"></i> Doctor Profile
    </a>
</li>

<li class="nav-item">
    <a href="doctors.php" class="nav-link">
        <i class="nav-icon fas fa-user-md"></i> Doctors
    </a>
</li>
<li class="nav-item">
    <a href="staff_users.php" class="nav-link">
        <i class="nav-icon fas fa-user-shield"></i> Staff & Roles
    </a>
</li>
<li class="nav-item">
    <a href="rbac_matrix.php" class="nav-link">
        <i class="nav-icon fas fa-user-lock"></i> RBAC Matrix
    </a>
</li>
<li class="nav-item">
    <a href="audit_logs.php" class="nav-link">
        <i class="nav-icon fas fa-clipboard-list"></i> Audit Logs
    </a>
</li>
<li class="nav-item">
    <a href="change_password.php" class="nav-link">
        <i class="nav-icon fas fa-key"></i> Change Password </a>
</li>

<li class="nav-item">
    <a href="index.php?logout" class="nav-link">
        <i class="nav-icon fas fa-sign-out-alt"></i> Logout </a>
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

                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>
                    <?php 
  
include "db.php";

if (isset($_SESSION['id'])) {
    $email = $_SESSION['id'];

    $teacherQuery = "SELECT name FROM admin WHERE email = '$email'";
    $result = mysqli_query($conn, $teacherQuery);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
      
             ?>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $row['name'];?></span>
                                <img class="img-profile rounded-circle"
                                    src="img/undraw_profile.svg">
                            </a>
                           
                </nav>
              <?php   }
    }
} ?>

            <a class="scroll-to-top rounded" href="#page-top">
                <i class="fas fa-angle-up"></i>
            </a>

         
            <script src="vendor/jquery/jquery.min.js"></script>
            <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

            <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

            <script src="js/sb-admin-2.min.js"></script>

            <script src="vendor/chart.js/Chart.min.js"></script>

            <script src="js/demo/chart-area-demo.js"></script>
            <script src="js/demo/chart-pie-demo.js"></script>

          

</body>

</html>

