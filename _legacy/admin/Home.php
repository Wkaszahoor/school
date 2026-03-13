<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('admin_dashboard', 'view', 'index.php');

include "db.php"; // Ensure db.php connects to your database

// Include your topbar/header (adjust path if necessary)
include "./partials/topbar1.php";

$loggedInTeacherEmail = $_SESSION['id'];
$assignedClassValue = 'N/A'; // Default value if no class is assigned
$currentDate = date('Y-m-d'); // Default date for attendance summary, today's date

// --- 1. Fetch the assigned class for the logged-in teacher ---
// Assuming 'class_assigned' in 'teachers' table stores the actual class name (e.g., "10th Grade A")
$teacherQuery = "SELECT class_assigned FROM teachers WHERE email = ?";
$stmt_teacher = $conn->prepare($teacherQuery);

if ($stmt_teacher === false) {
    error_log("Dashboard Page: Teacher query prepare failed: " . $conn->error);
    die("Database error: Could not prepare teacher query.");
} else {
    $stmt_teacher->bind_param("s", $loggedInTeacherEmail);
    $stmt_teacher->execute();
    $result = $stmt_teacher->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $assignedClassValue = htmlspecialchars($row["class_assigned"]);
        error_log("Dashboard Page: Teacher '" . $loggedInTeacherEmail . "' assigned class: " . $assignedClassValue);
    } else {
        error_log("Dashboard Page: No class assigned or teacher not found for email: " . $loggedInTeacherEmail);
    }
    $stmt_teacher->close();
}

// --- 2. Handle Date Selection for Attendance Summary ---
// If the user submits a date via the form in the 4th card, use that; otherwise, use the current date
if (isset($_POST['attendance_date'])) {
    $currentDate = $_POST['attendance_date'];
}

// --- 3. Initialize counts ---
$totalStudentsInAssignedClass = 0;
$totalPresentStudentsInAssignedClass = 0;
$totalAbsentStudentsInAssignedClass = 0;

// --- 4. Fetch counts only if a class is assigned ---
if ($assignedClassValue !== 'N/A' && $conn) {
    // A. Total Students in the assigned class
    $sqlTotalStudents = "SELECT COUNT(*) AS total_students FROM students WHERE class = ?";
    $stmt_total = $conn->prepare($sqlTotalStudents);
    if ($stmt_total) {
        $stmt_total->bind_param("s", $assignedClassValue);
        $stmt_total->execute();
        $res_total = $stmt_total->get_result();
        if ($res_total && $res_total->num_rows > 0) {
            $row_total = $res_total->fetch_assoc();
            $totalStudentsInAssignedClass = $row_total['total_students'];
        }
        $stmt_total->close();
    } else {
        error_log("Dashboard Page: Total students query prepare failed: " . $conn->error);
    }

    // B. Total Present Students in the assigned class for the selected date
    $sqlPresentStudents = "SELECT COUNT(DISTINCT s.id) AS total_present
                           FROM attendance a
                           JOIN students s ON a.student_id = s.id
                           WHERE s.class = ?
                           AND a.attendance_date = ?
                           AND a.status = 'P'";
    $stmt_present = $conn->prepare($sqlPresentStudents);
    if ($stmt_present) {
        $stmt_present->bind_param("ss", $assignedClassValue, $currentDate);
        $stmt_present->execute();
        $res_present = $stmt_present->get_result();
        if ($res_present && $res_present->num_rows > 0) {
            $row_present = $res_present->fetch_assoc();
            $totalPresentStudentsInAssignedClass = $row_present['total_present'];
        }
        $stmt_present->close();
    } else {
        error_log("Dashboard Page: Present students query prepare failed: " . $conn->error);
    }

    // C. Total Absent Students in the assigned class for the selected date
    $sqlAbsentStudents = "SELECT COUNT(DISTINCT s.id) AS total_absent
                          FROM attendance a
                          JOIN students s ON a.student_id = s.id
                          WHERE s.class = ?
                          AND a.attendance_date = ?
                          AND a.status = 'A'";
    $stmt_absent = $conn->prepare($sqlAbsentStudents);
    if ($stmt_absent) {
        $stmt_absent->bind_param("ss", $assignedClassValue, $currentDate);
        $stmt_absent->execute();
        $res_absent = $stmt_absent->get_result();
        if ($res_absent && $res_absent->num_rows > 0) {
            $row_absent = $res_absent->fetch_assoc();
            $totalAbsentStudentsInAssignedClass = $row_absent['total_absent'];
        }
        $stmt_absent->close();
    } else {
        error_log("Dashboard Page: Absent students query prepare failed: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <title>Teacher Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Custom styles for cards if Bootstrap is not used or for additional styling */
        body {
            background-color: #f8f9fa; /* Light gray background */
        }
        .card-custom {
            border-radius: 0.5rem;
            border-left: 0.25rem solid; /* For the color stripe */
            min-height: 120px; /* Ensure consistent card height */
        }
        .border-left-primary { border-color: #4e73df !important; }
        .border-left-success { border-color: #1cc88a !important; }
        .border-left-danger { border-color: #e74a3b !important; }
        .border-left-info { border-color: #36b9cc !important; } /* For the date picker card */

        .text-primary { color: #4e73df !important; }
        .text-success { color: #1cc88a !important; }
        .text-danger { color: #e74a3b !important; }
        .text-info { color: #36b9cc !important; } /* For the date picker card text */

        .text-xs { font-size: 0.75rem; }
        .text-uppercase { text-transform: uppercase; }
        .font-weight-bold { font-weight: 700; }
        .text-gray-800 { color: #5a5c69; }
        .text-gray-300 { color: #dddfeb; } /* For icons */
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <h1 class="h3 mb-4 text-gray-800"> <?php echo $assignedClassValue; ?></h1>

    <?php if ($assignedClassValue === 'N/A'): ?>
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">No Class Assigned!</h4>
            <p>It appears no class is assigned to your teacher account. Attendance details cannot be displayed. Please contact your administrator.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-custom border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Students in Class: <?php echo $assignedClassValue; ?></div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalStudentsInAssignedClass; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-custom border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Present on <?php echo $currentDate; ?></div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalPresentStudentsInAssignedClass; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-custom border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Absent on <?php echo $currentDate; ?></div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalAbsentStudentsInAssignedClass; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-times fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card card-custom border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Select Date
                                </div>
                                <div class="row no-gutters align-items-center">
                                    <div class="col-auto">
                                        <form method="POST" action="">
                                            <input type="date" class="form-control" name="attendance_date" value="<?php echo $currentDate; ?>" onchange="this.form.submit()">
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

