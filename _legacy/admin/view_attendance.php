<?php
// Start output buffering ONLY ONCE
ob_start();

require_once __DIR__ . '/../auth.php';
auth_require_permission('attendance_reports', 'view', 'index.php');

include "db.php"; // Ensure db.php connects to your database

// Include topbar AFTER session check and BEFORE any HTML output, but only once.
include "./partials/topbar.php"; // Adjust path if necessary


$selectedClass = $_GET['class'] ?? '';
$selectedDate = $_GET['date'] ?? date('Y-m-d'); // Default to today's date
$lowAttendanceThreshold = 75.0;
$lowAttendanceAlerts = [];

$conn->query("
    CREATE TABLE IF NOT EXISTS attendance_alerts (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id VARCHAR(50) NOT NULL,
        alert_date DATE NOT NULL,
        attendance_percent DECIMAL(5,2) NOT NULL,
        present_count INT NOT NULL DEFAULT 0,
        total_count INT NOT NULL DEFAULT 0,
        message VARCHAR(255) NOT NULL,
        notified_to_principal TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_low_attendance_day (student_id, class_id, alert_date)
    )
");

$conn->query("CREATE TABLE IF NOT EXISTS inbox_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sender_role VARCHAR(20) NOT NULL,
    sender_id INT NOT NULL,
    recipient_role VARCHAR(20) NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(190) NOT NULL,
    message_body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inbox_recipient (recipient_role, recipient_id, is_read, id),
    INDEX idx_inbox_sender (sender_role, sender_id, id)
)");

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// --- New: Fetch classes with attendance for the selected date ---
$classesWithAttendance = [];
if (!empty($selectedDate)) {
    $attendanceCheckQuery = "SELECT DISTINCT class_id FROM attendance WHERE attendance_date = ?";
    $stmtAttendanceCheck = $conn->prepare($attendanceCheckQuery);
    if ($stmtAttendanceCheck) {
        $stmtAttendanceCheck->bind_param("s", $selectedDate);
        $stmtAttendanceCheck->execute();
        $resultAttendanceCheck = $stmtAttendanceCheck->get_result();
        while ($row = $resultAttendanceCheck->fetch_assoc()) {
            $classesWithAttendance[] = $row['class_id'];
        }
        $stmtAttendanceCheck->close();
    }
}
// -----------------------------------------------------------------

// Fetch all available classes for the dropdown
$classesQuery = "
    SELECT class_assigned AS class_name FROM teachers
    WHERE class_assigned IS NOT NULL AND class_assigned <> ''
    UNION
    SELECT CAST(class_id AS CHAR) AS class_name FROM attendance
    WHERE class_id IS NOT NULL AND class_id <> ''
    ORDER BY class_name ASC
";
$stmtClasses = $conn->prepare($classesQuery);
$classes = [];
if ($stmtClasses) {
    $stmtClasses->execute();
    $resultClasses = $stmtClasses->get_result();
    while ($row = $resultClasses->fetch_assoc()) {
        $className = trim((string)($row['class_name'] ?? ''));
        if ($className !== '') {
            $classes[] = $className;
        }
    }
    $stmtClasses->close();
}

$totalPresent = 0;
$totalAbsent = 0;
$absentStudents = []; // Initialize array to store absent students

// Initialize $resultAttendance to prevent errors if no query is run yet
$resultAttendance = null;

if ($selectedClass && $selectedDate) {
    $alertQuery = "
        SELECT
            s.id AS student_id,
            s.student_name,
            a.class_id,
            SUM(CASE WHEN a.status = 'P' THEN 1 ELSE 0 END) AS present_count,
            SUM(CASE WHEN a.status IN ('P','A','L') THEN 1 ELSE 0 END) AS total_count
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        WHERE a.attendance_date >= DATE_SUB(?, INTERVAL 30 DAY)
          AND a.attendance_date <= ?
    ";
    $params = [$selectedDate, $selectedDate];
    $types = "ss";
    if ($selectedClass !== 'all_classes') {
        $alertQuery .= " AND a.class_id = ?";
        $params[] = $selectedClass;
        $types .= "s";
    }
    $alertQuery .= "
        GROUP BY s.id, s.student_name, a.class_id
        HAVING total_count >= 5 AND ((present_count / total_count) * 100) < ?
        ORDER BY ((present_count / total_count) * 100) ASC, s.student_name ASC
    ";
    $threshold = $lowAttendanceThreshold;
    $params[] = $threshold;
    $types .= "d";

    $stmtAlert = $conn->prepare($alertQuery);
    if ($stmtAlert) {
        $stmtAlert->bind_param($types, ...$params);
        $stmtAlert->execute();
        $resAlert = $stmtAlert->get_result();
        while ($row = $resAlert->fetch_assoc()) {
            $presentCount = (int)($row['present_count'] ?? 0);
            $totalCount = (int)($row['total_count'] ?? 0);
            $percent = $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 2) : 0.0;
            $message = "Low attendance: " . (string)($row['student_name'] ?? 'Student') . " is at " . $percent . "% in class " . (string)($row['class_id'] ?? '');

            $lowAttendanceAlerts[] = [
                'student_id' => (int)($row['student_id'] ?? 0),
                'student_name' => (string)($row['student_name'] ?? ''),
                'class_id' => (string)($row['class_id'] ?? ''),
                'present_count' => $presentCount,
                'total_count' => $totalCount,
                'attendance_percent' => $percent,
                'message' => $message,
            ];

            $studentId = (int)($row['student_id'] ?? 0);
            $classId = (string)($row['class_id'] ?? '');
            $upsert = $conn->prepare("
                INSERT INTO attendance_alerts
                (student_id, class_id, alert_date, attendance_percent, present_count, total_count, message, notified_to_principal)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE
                    attendance_percent = VALUES(attendance_percent),
                    present_count = VALUES(present_count),
                    total_count = VALUES(total_count),
                    message = VALUES(message),
                    updated_at = CURRENT_TIMESTAMP
            ");
            if ($upsert) {
                $upsert->bind_param('issdiis', $studentId, $classId, $selectedDate, $percent, $presentCount, $totalCount, $message);
                $upsert->execute();
                $upsert->close();
            }

            // Automated notifications to principals via inbox_messages (once per day/student/class).
            $principalUsers = $conn->query("SELECT id FROM staff_users WHERE role = 'principal' AND is_active = 1");
            if ($principalUsers) {
                while ($p = $principalUsers->fetch_assoc()) {
                    $principalId = (int)($p['id'] ?? 0);
                    if ($principalId <= 0) {
                        continue;
                    }
                    $subject = 'Low Attendance Alert';
                    $body = $message . " (rolling 30 days up to " . $selectedDate . ").";
                    $exists = $conn->prepare("
                        SELECT id
                        FROM inbox_messages
                        WHERE sender_role = 'system'
                          AND sender_id = 0
                          AND recipient_role = 'principal'
                          AND recipient_id = ?
                          AND subject = ?
                          AND DATE(created_at) = ?
                          AND message_body = ?
                        LIMIT 1
                    ");
                    $already = false;
                    if ($exists) {
                        $exists->bind_param('isss', $principalId, $subject, $selectedDate, $body);
                        $exists->execute();
                        $already = (bool)$exists->get_result()->fetch_assoc();
                        $exists->close();
                    }
                    if (!$already) {
                        $insMsg = $conn->prepare("
                            INSERT INTO inbox_messages (sender_role, sender_id, recipient_role, recipient_id, subject, message_body, is_read)
                            VALUES ('system', 0, 'principal', ?, ?, ?, 0)
                        ");
                        if ($insMsg) {
                            $insMsg->bind_param('iss', $principalId, $subject, $body);
                            $insMsg->execute();
                            $insMsg->close();
                        }
                    }
                }
            }
        }
        $stmtAlert->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <title>View Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .status-P { color: green; font-weight: bold; }
        .status-A { color: red; font-weight: bold; }
        /* Style for classes with attendance */
        .attendance-taken {
            background-color: #d1fae5; /* Light green background */
            color: #065f46; /* Darker green text */
            font-weight: 600;
        }
        .attendance-icon {
            margin-left: 8px;
            font-size: 0.9em;
            color: #065f46; /* Green checkmark */
        }
    </style>
</head>
<body>

    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-xl mt-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">View Attendance Records</h1>

        <form method="GET" action="view_attendance.php" class="mb-8 grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label for="classSelect" class="block text-sm font-medium text-gray-700">Select Class:</label>
                <select id="classSelect" name="class" class="w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-gray-800">
                    <option value="">-- Select Class --</option>
                    <option value="all_classes" <?php echo ($selectedClass == 'all_classes') ? 'selected' : ''; ?>>View All Classes</option>
                    <?php foreach ($classes as $classOption):
                        $isAttendanceTaken = in_array($classOption, $classesWithAttendance);
                        $optionClass = $isAttendanceTaken ? 'attendance-taken' : '';
                        ?>
                        <option value="<?php echo h($classOption); ?>" class="<?php echo $optionClass; ?>" <?php echo ($selectedClass == $classOption) ? 'selected' : ''; ?>>
                            <?php echo h($classOption); ?>
                            <?php if ($isAttendanceTaken): ?>
                                <span class="attendance-icon">&#10003;</span> <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="attendanceDate" class="block text-sm font-medium text-gray-700">Select Date:</label>
                <input type="date" id="attendanceDate" name="date" class="w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-gray-800" value="<?php echo $selectedDate; ?>">
            </div>
            <div>
                <button type="submit" class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    View Attendance
                </button>
            </div>
            <?php
            // Only show export button if a class and date are selected and there are potential results
            if (!empty($selectedClass) && !empty($selectedDate)):
                // Re-run a simplified query to check for rows if needed, or rely on $resultAttendance from below
                // For simplicity here, we assume if selectedClass and selectedDate are present,
                // the user intends to view/export, and the table will indicate if no data.
                // A more robust check might involve fetching num_rows *before* this point.
            ?>
            <div>
                <a href="export_attendance.php?class=<?php echo urlencode($selectedClass); ?>&date=<?php echo urlencode($selectedDate); ?>"
                   class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-blue-800 bg-blue-200 hover:bg-blue-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-center">
                    Export to CSV
                </a>
            </div>
            <div>
                <a href="export_attendance_pdf.php?class=<?php echo urlencode($selectedClass); ?>&date=<?php echo urlencode($selectedDate); ?>"
                   target="_blank"
                   class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center">
                    Export PDF (Template)
                </a>
            </div>
            <?php endif; ?>
        </form>

        <?php if (!empty($lowAttendanceAlerts)): ?>
            <div class="mb-6 p-4 bg-red-50 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold text-red-700 mb-3">Low Attendance Alerts (Auto)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-red-100">
                        <thead class="bg-red-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700 uppercase">Student</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700 uppercase">Class</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700 uppercase">Present</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700 uppercase">Total</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-red-700 uppercase">Attendance %</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-red-100">
                            <?php foreach ($lowAttendanceAlerts as $alert): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-800"><?php echo h($alert['student_name']); ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-800"><?php echo h($alert['class_id']); ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-800"><?php echo (int)$alert['present_count']; ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-800"><?php echo (int)$alert['total_count']; ?></td>
                                    <td class="px-4 py-2 text-sm font-semibold text-red-700"><?php echo h(number_format((float)$alert['attendance_percent'], 2)); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-red-700 mt-2">Automated notifications were sent to principal inbox for new alerts.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($selectedDate)): // Show status and table only if a date is selected ?>
            <div class="mt-8 mb-6 p-4 bg-gray-100 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold text-gray-700 mb-3">Attendance Status for <?php echo $selectedDate; ?></h3>
                <?php if (!empty($classesWithAttendance)): ?>
                    <p class="text-gray-800">Attendance has been recorded for the following classes on this date:</p>
                    <ul class="list-disc list-inside text-green-700 font-medium mt-2">
                        <?php foreach ($classes as $classOption): ?>
                            <?php if (in_array($classOption, $classesWithAttendance)): ?>
                                <li><?php echo $classOption; ?> <span class="attendance-icon">&#10003;</span></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-600">No attendance has been recorded for any classes on this date.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php if ($selectedClass && $selectedDate): ?>
            <div id="attendance-results-section">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">
                    Attendance for
                    <?php echo ($selectedClass == 'all_classes') ? 'All Classes' : 'Class: ' . $selectedClass; ?>
                    on Date: <?php echo $selectedDate; ?>
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 shadow-sm rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    S.No.
                                </th>
                                <?php if ($selectedClass == 'all_classes'): ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Class
                                </th>
                                <?php endif; ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Recorded By
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $sno = 1; // Initialize sequence number for main table
                            // Base query
                            $attendanceQuery = "
                                SELECT
                                    a.id,
                                    a.class_id,
                                    a.id AS attendance_record_id, /* Use a unique ID for attendance record itself if needed, student_id is from students table */
                                    s.id AS student_id, /* Actual student ID from students table */
                                    s.student_name,
                                    a.status,
                                    a.teacher_email
                                FROM
                                    attendance a
                                JOIN
                                    students s ON a.student_id = s.id
                                WHERE
                                    a.attendance_date = ?
                            ";

                            $queryParams = [$selectedDate];
                            $paramTypes = "s"; // 's' for string (date)

                            // Add class filter if a specific class is selected
                            if ($selectedClass != 'all_classes' && !empty($selectedClass)) {
                                $attendanceQuery .= " AND a.class_id = ?";
                                $queryParams[] = $selectedClass;
                                $paramTypes .= "s"; // 's' for string (class_id)
                            }

                            // Add ordering
                            $attendanceQuery .= " ORDER BY a.class_id ASC, s.student_name ASC ,a.id ASC";

                            if ($stmtAttendance = $conn->prepare($attendanceQuery)) {
                                // Dynamically bind parameters
                                $stmtAttendance->bind_param($paramTypes, ...$queryParams);
                                $stmtAttendance->execute();
                                $resultAttendance = $stmtAttendance->get_result(); // Assign to $resultAttendance here

                                if ($resultAttendance && $resultAttendance->num_rows > 0) {
                                    while ($record = $resultAttendance->fetch_assoc()) {
                            $classId = htmlspecialchars((string)($record['class_id'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $studentId = htmlspecialchars((string)($record['student_id'] ?? ''), ENT_QUOTES, 'UTF-8'); // This is still fetched but not displayed
                            $studentName = htmlspecialchars((string)($record['student_name'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $status = htmlspecialchars((string)($record['status'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $teacherEmail = htmlspecialchars((string)($record['teacher_email'] ?? ''), ENT_QUOTES, 'UTF-8');

                                        // Increment counts for summary card
                                        if ($status == 'P') {
                                            $totalPresent++;
                                        } else {
                                            $totalAbsent++;
                                            // Add absent student to the new array
                                            $absentStudents[] = [
                                                'class_id' => $classId,
                                                'student_id' => $studentId, // Keep for internal data if needed elsewhere, but not displayed
                                                'student_name' => $studentName
                                            ];
                                        }
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $sno++; ?>
                                            </td>
                                            <?php if ($selectedClass == 'all_classes'): ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $classId; ?>
                                            </td>
                                            <?php endif; ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                                <?php echo $studentName; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm <?php echo ($status == 'P') ? 'status-P' : 'status-A'; ?>">
                                                <?php echo $status; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                                <?php echo $teacherEmail; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    // Adjusted colspan values for main table
                                    $colspan = ($selectedClass == 'all_classes') ? '5' : '4'; // S.No. + Class (optional) + Name + Status + Recorded By
                                    echo '<tr><td colspan="' . $colspan . '" class="px-6 py-4 text-center text-sm text-gray-500">No attendance records found for this ' . (($selectedClass == 'all_classes') ? 'date across all classes' : 'class and date') . '.</td></tr>';
                                }
                                $stmtAttendance->close();
                            } else {
                                // Adjusted colspan values for main table
                                $colspan = ($selectedClass == 'all_classes') ? '5' : '4'; // S.No. + Class (optional) + Name + Status + Recorded By
                                echo '<tr><td colspan="' . $colspan . '" class="px-6 py-4 text-center text-sm text-red-500">Error preparing attendance query: ' . $conn->error . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8 p-6 bg-blue-50 rounded-lg shadow-md flex justify-around items-center text-center">
                <div class="flex flex-col items-center">
                    <p class="text-sm font-medium text-gray-600">Total Present Students</p>
                    <p class="text-4xl font-bold text-green-600"><?php echo $totalPresent; ?></p>
                </div>
                <div class="flex flex-col items-center">
                    <p class="text-sm font-medium text-gray-600">Total Absent Students</p>
                    <p class="text-4xl font-bold text-red-600"><?php echo $totalAbsent; ?></p>
                </div>
            </div>

            <?php if (!empty($absentStudents)): ?>
                <div class="mt-10" id="absent-students-section">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-4">
                        Absent Students for
                        <?php echo ($selectedClass == 'all_classes') ? 'All Classes' : 'Class: ' . $selectedClass; ?>
                        on Date: <?php echo $selectedDate; ?>
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 shadow-sm rounded-lg">
                            <thead class="bg-red-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        S.No.
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student Name
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $absentSno = 1; // Initialize sequence number for absent students table
                                foreach ($absentStudents as $absentStudent): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $absentSno++; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $absentStudent['class_id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                            <?php echo $absentStudent['student_name']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-600 text-lg mt-8">No absent students found for this <?php echo ($selectedClass == 'all_classes') ? 'date across all classes' : 'class and date'; ?>.</p>
            <?php endif; ?>

        <?php else: ?>
            <p class="text-center text-gray-600 text-lg mt-8">Please select a class (or 'View All Classes') and a date to view attendance.</p>
        <?php endif; ?>

    </div>
<?php
include "./partials/footer.php";
?>
</body>
</html>
<?php
ob_end_flush(); // End output buffering
?>

