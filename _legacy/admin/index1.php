<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('admin_dashboard', 'view', 'index.php');
include "./partials/topbar.php"; 
include "db.php"; 
$totalStudents = 0; 
$totalPresentStudents = 0;
$totalAbsentStudents = 0;
$selectedDate = date('Y-m-d');
if (isset($_POST['attendance_date'])) {
    $selectedDate = $_POST['attendance_date'];
}
if (isset($conn)) { 
    $sqlTotalStudents = "SELECT COUNT(*) AS total_students FROM students";
    $resultTotalStudents = $conn->query($sqlTotalStudents);
    if ($resultTotalStudents) {
        if ($resultTotalStudents->num_rows > 0) {
            $rowTotalStudents = $resultTotalStudents->fetch_assoc();
            $totalStudents = $rowTotalStudents["total_students"];
        }
    } else {
        error_log("Error getting total students: " . $conn->error);
    }
    $sqlPresent = "SELECT COUNT(*) AS total_present FROM attendance WHERE status = 'P' AND attendance_date = '" . $selectedDate . "'";
    $resultPresent = $conn->query($sqlPresent);
    if ($resultPresent) {
        if ($resultPresent->num_rows > 0) {
            $rowPresent = $resultPresent->fetch_assoc();
            $totalPresentStudents = $rowPresent["total_present"];
        }
    } else {
        error_log("Error getting total present students: " . $conn->error);
    }
    $sqlAbsent = "SELECT COUNT(*) AS total_absent FROM attendance WHERE status = 'A' AND attendance_date = '" . $selectedDate . "'";
    $resultAbsent = $conn->query($sqlAbsent);
    if ($resultAbsent) {
        if ($resultAbsent->num_rows > 0) {
            $rowAbsent = $resultAbsent->fetch_assoc();
            $totalAbsentStudents = $rowAbsent["total_absent"];
        }
    } else {
        error_log("Error getting total absent students: " . $conn->error);
    }
} else {
    error_log("Database connection not established in teacher_performance.php.");
}
$filter_teacher_id = $_POST['filter_teacher_id'] ?? '';
$filter_result_type = $_POST['filter_result_type'] ?? '';
$filter_class_id = $_POST['filter_class_id'] ?? ''; 
$result_type_map = [
    '0' => 'Weekly',
    '1' => 'Monthly',
    '2' => 'Mid Term',
    '3' => 'Annual'
];
$all_teachers = [];
$assigned_classes = [];
$performance_data = []; 
$performance_message = ""; 
if (isset($conn)) {
    $stmt_teachers = $conn->prepare("SELECT id, name FROM teachers ORDER BY name");
    if ($stmt_teachers) {
        $stmt_teachers->execute();
        $teachers_result = $stmt_teachers->get_result();
        while ($row = $teachers_result->fetch_assoc()) {
            $all_teachers[] = $row;
        }
        $stmt_teachers->close();
    } else {
        error_log("Prepare failed for fetching teachers: " . $conn->error);
    }
    if (!empty($filter_teacher_id)) {
        $stmt_classes = $conn->prepare("
            SELECT DISTINCT c.id, c.class 
            FROM assignments a
            JOIN classes c ON a.class_id = c.id
            WHERE a.teacher_id = ?
            ORDER BY c.class
        ");
        if ($stmt_classes) {
            $stmt_classes->bind_param("i", $filter_teacher_id);
            $stmt_classes->execute();
            $classes_result = $stmt_classes->get_result();
            while ($row = $classes_result->fetch_assoc()) {
                $assigned_classes[] = $row;
            }
            $stmt_classes->close();
        } else {
            error_log("Prepare failed for fetching assigned classes: " . $conn->error);
        }
    }
    if (!empty($filter_teacher_id) && !empty($filter_class_id)) {
        $sql_performance = "
            SELECT
                DATE_FORMAT(r.result_date, '%Y-%m-%d') AS result_date_formatted,
                AVG(r.percentage) AS average_percentage,
                t.name AS teacher_name
            FROM results r
            JOIN teachers t ON r.teacher_id = t.id
            WHERE r.teacher_id = ? AND r.class_id = ?
        ";
        $params = [$filter_teacher_id, $filter_class_id];
        $types = "ii";
        if ($filter_result_type !== '') {
            $sql_performance .= " AND r.result_type = ?";
            $params[] = $filter_result_type;
            $types .= "i";
        }
        $sql_performance .= "
            GROUP BY DATE_FORMAT(r.result_date, '%Y-%m-%d'), t.name
            ORDER BY r.result_date ASC
        ";
        $stmt_performance = $conn->prepare($sql_performance);
        if ($stmt_performance === false) {
            error_log("Prepare failed for performance: " . $conn->error);
            $performance_message = "An unexpected error occurred while fetching performance data.";
        } else {
            $stmt_performance->bind_param($types, ...$params);
            if (!$stmt_performance->execute()) {
                error_log("Execute failed for performance: " . $stmt_performance->error);
                $performance_message = "An unexpected error occurred during performance data retrieval.";
            } else {
                $performance_result = $stmt_performance->get_result();
                if ($performance_result->num_rows > 0) {
                    while($row = $performance_result->fetch_assoc()) {
                        $performance_data[] = $row;
                    }
                } else {
                    $performance_message = "No performance data found for the selected teacher, class, and filter criteria.";
                }
            }
            $stmt_performance->close();
        }
    } else if (!empty($filter_teacher_id) && empty($filter_class_id)) {
        $performance_message = "Please select a Class to view performance.";
    } else {
        $performance_message = "Please select a Teacher and Class to view performance.";
    }
} else {
    $performance_message = "Database connection not available for performance data.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <meta name="description" content="KORT Admin Dashboard - Teacher Performance">
    <meta name="author" content="Your Name">

    <title>KORT - Admin Dashboard</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="css/sb-admin-2.css" rel="stylesheet">
    <style>
        .filter-form {
            background-color: #f0f8ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start; 
            align-items: flex-end;
        }
        .filter-form .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }
        .filter-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 0.9em;
        }
        .filter-form select,
        .filter-form input[type="date"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.9em;
            box-sizing: border-box;
        }
        .filter-form button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            align-self: flex-end;
        }
        .filter-form button:hover {
            background-color: #0056b3;
        }
        .chart-container {
            position: relative;
            height: 40vh; 
            width: 80vw; 
            margin: auto; 
        }
        .no-data-message {
            text-align: center;
            padding: 30px;
            background-color: #f0f0f0;
            border-radius: 8px;
            color: #555;
            font-style: italic;
        }
    </style>
</head>

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <div class="container-fluid">

                    <h1 class="h3 mb-4 text-gray-800">Dashboard Overview</h1>

                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Students</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $totalStudents; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i> </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Present Students (<?php echo $selectedDate; ?>)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $totalPresentStudents; ?>
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
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Total Absent Students (<?php echo $selectedDate; ?>)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $totalAbsentStudents; ?>
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
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Select Date for Attendance
                                            </div>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <form method="POST" action="">
                                                        <input type="date" class="form-control" name="attendance_date" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="sidebar-divider">

                    <h2 class="h3 mb-4 text-gray-800">Teacher Performance </h2>

                    <form method="post" class="filter-form">
                        <div class="form-group">
                            <label for="filter_teacher">Teacher:</label>
                            <select name="filter_teacher_id" id="filter_teacher" onchange="this.form.submit()">
                                <option value="">Select Teacher</option>
                                <?php
                                foreach ($all_teachers as $teacher) {
                                    $selected = ($filter_teacher_id == $teacher['id']) ? 'selected' : '';
                                    echo "<option value='{$teacher['id']}' {$selected}>" . htmlspecialchars($teacher['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_class">Class:</label>
                            <select name="filter_class_id" id="filter_class">
                                <option value="">Select Class</option>
                                <?php
                                if (!empty($assigned_classes)) {
                                    foreach ($assigned_classes as $class) {
                                        $selected = ($filter_class_id == $class['id']) ? 'selected' : '';
                                        echo "<option value='{$class['id']}' {$selected}>" . htmlspecialchars($class['class']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_result_type">Result Type:</label>
                            <select name="filter_result_type" id="filter_result_type">
                                <option value="">All Result Types</option>
                                <?php
                                foreach ($result_type_map as $numeric_value => $display_name) {
                                    $selected = ($filter_result_type !== '' && (int)$filter_result_type === (int)$numeric_value) ? 'selected' : '';
                                    echo "<option value=\"".htmlspecialchars($numeric_value)."\" {$selected}>".htmlspecialchars($display_name)."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Show Performance</button>
                    </form>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Teacher Performance </h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($performance_data)):
                                $labels = [];
                                $data_values = [];
                                $teacher_name = '';
                                foreach ($performance_data as $entry) {
                                    $labels[] = $entry['result_date_formatted']; 
                                    $data_values[] = (float)$entry['average_percentage'];
                                    if (empty($teacher_name)) {
                                        $teacher_name = $entry['teacher_name'];
                                    }
                                }
                            ?>
                                <div class="chart-container">
                                    <canvas id="teacherPerformanceChart"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="no-data-message alert alert-info">
                                    <p><?php echo htmlspecialchars($performance_message); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                </div>
         <?php
include "./partials/footer.php";
?>
            </div>
        </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">Ã—</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="index.php?logout">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <script src="js/sb-admin-2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($performance_data)): ?>
                const ctx = document.getElementById('teacherPerformanceChart').getContext('2d');
                const chartLabels = <?php echo json_encode($labels); ?>;
                const chartDataValues = <?php echo json_encode($data_values); ?>;
                const teacherName = <?php echo json_encode($teacher_name); ?>;
                const resultTypeFilterName = "<?php echo htmlspecialchars($filter_result_type !== '' ? $result_type_map[$filter_result_type] : 'All Result Types'); ?>";

                new Chart(ctx, {
                    type: 'line', 
                    data: {
                        labels: chartLabels, 
                        datasets: [{
                            label: teacherName + ' - Average Percentage (' + resultTypeFilterName + ')',
                            data: chartDataValues,
                            backgroundColor: 'rgba(78, 115, 223, 0.7)', 
                            borderColor: 'rgba(78, 115, 223, 1)',
                            borderWidth: 2,
                            fill: false, 
                            tension: 0.2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, 
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100, 
                                title: {
                                    display: true,
                                    text: 'Average Percentage (%)'
                                }
                            },
                            x: {
                                type: 'category',
                                labels: chartLabels, 
                                title: {
                                    display: true,
                                    text: 'Result Date'
                                },
                                ticks: {
                                    autoSkip: false,
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.raw.toFixed(2) + '%';
                                    },
                                    title: function(context) {
                                        return 'Date: ' + context[0].label;
                                    }
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>
<?php
if ($conn) {
    $conn->close();
}
ob_end_flush();
?>

