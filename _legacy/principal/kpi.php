<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('admin_dashboard', 'view', 'index.php');
include '../db.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function kpiTableExists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row['c'] ?? 0) > 0);
}

function kpiColumnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row['c'] ?? 0) > 0);
}

$flashType = '';
$flashMessage = '';

$conn->query("
    CREATE TABLE IF NOT EXISTS principal_kpi_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(80) NOT NULL,
        indicator VARCHAR(180) NOT NULL,
        target_value DECIMAL(10,2) NULL,
        actual_value DECIMAL(10,2) NULL,
        unit VARCHAR(30) NULL,
        notes VARCHAR(255) NULL,
        recorded_on DATE NOT NULL,
        created_by_user_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kpi_entry'])) {
    $category = trim((string)($_POST['category'] ?? ''));
    $indicator = trim((string)($_POST['indicator'] ?? ''));
    $targetValueRaw = trim((string)($_POST['target_value'] ?? ''));
    $actualValueRaw = trim((string)($_POST['actual_value'] ?? ''));
    $unit = trim((string)($_POST['unit'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));
    $recordedOn = trim((string)($_POST['recorded_on'] ?? date('Y-m-d')));
    $createdBy = (int)($_SESSION['auth_user_id'] ?? 0);

    $validCategories = ['Operations', 'Fee Recovery', 'Cultural', 'Growth', 'Maintenance', 'Partnerships'];
    if (!in_array($category, $validCategories, true) || $indicator === '') {
        $flashType = 'danger';
        $flashMessage = 'Category and indicator are required.';
    } else {
        $targetValue = ($targetValueRaw === '') ? null : (float)$targetValueRaw;
        $actualValue = ($actualValueRaw === '') ? null : (float)$actualValueRaw;
        $stmt = $conn->prepare("
            INSERT INTO principal_kpi_entries
            (category, indicator, target_value, actual_value, unit, notes, recorded_on, created_by_user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param(
                'ssddsssi',
                $category,
                $indicator,
                $targetValue,
                $actualValue,
                $unit,
                $notes,
                $recordedOn,
                $createdBy
            );
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'KPI entry added successfully.';
        } else {
            $flashType = 'danger';
            $flashMessage = 'Unable to add KPI entry.';
        }
    }
}

$teachers = [];
$qTeachers = $conn->query("SELECT id, name, email FROM teachers ORDER BY name ASC");
while ($qTeachers && $row = $qTeachers->fetch_assoc()) {
    $teachers[(int)$row['id']] = $row;
}

$assignmentCounts = [];
$qAssignments = kpiTableExists($conn, 'teacher_assignments')
    ? $conn->query("SELECT teacher_id, COUNT(*) AS c FROM teacher_assignments GROUP BY teacher_id")
    : false;
while ($qAssignments && $row = $qAssignments->fetch_assoc()) {
    $assignmentCounts[(int)$row['teacher_id']] = (int)$row['c'];
}

$teacherAssignmentDetails = [];
if (kpiTableExists($conn, 'teacher_assignments')) {
    $qAssignmentDetails = $conn->query("
        SELECT
            ta.teacher_id,
            COALESCE(c.class, CONCAT('Class #', ta.class_id)) AS class_name,
            COALESCE(s.subject_name, CONCAT('Subject #', ta.subject_id)) AS subject_name
        FROM teacher_assignments ta
        LEFT JOIN classes c ON c.id = ta.class_id
        LEFT JOIN subjects s ON s.id = ta.subject_id
        ORDER BY ta.teacher_id ASC, class_name ASC, subject_name ASC
    ");
    while ($qAssignmentDetails && $row = $qAssignmentDetails->fetch_assoc()) {
        $teacherId = (int)($row['teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            continue;
        }
        $label = trim((string)($row['class_name'] ?? '')) . ' - ' . trim((string)($row['subject_name'] ?? ''));
        if (!isset($teacherAssignmentDetails[$teacherId])) {
            $teacherAssignmentDetails[$teacherId] = [];
        }
        $teacherAssignmentDetails[$teacherId][$label] = true;
    }
}

$classTeacherByTeacher = [];
if (kpiTableExists($conn, 'classes') && kpiColumnExists($conn, 'classes', 'class_teacher_id')) {
    $qClassTeachers = $conn->query("
        SELECT class_teacher_id, class
        FROM classes
        WHERE class_teacher_id IS NOT NULL AND class_teacher_id > 0
        ORDER BY class ASC
    ");
    while ($qClassTeachers && $row = $qClassTeachers->fetch_assoc()) {
        $teacherId = (int)($row['class_teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            continue;
        }
        if (!isset($classTeacherByTeacher[$teacherId])) {
            $classTeacherByTeacher[$teacherId] = [];
        }
        $classTeacherByTeacher[$teacherId][] = (string)($row['class'] ?? '');
    }
}

$lessonCounts = [];
$qLesson = kpiTableExists($conn, 'lesson_plans') ? $conn->query("
    SELECT teacher_id, COUNT(DISTINCT CONCAT(class_id, ':', subject_id, ':', week_start)) AS c
    FROM lesson_plans
    WHERE week_start >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY teacher_id
") : false;
while ($qLesson && $row = $qLesson->fetch_assoc()) {
    $lessonCounts[(int)$row['teacher_id']] = (int)$row['c'];
}

$weeklyTestCounts = [];
$qWeekly = kpiTableExists($conn, 'weekly_test_results') ? $conn->query("
    SELECT teacher_id, COUNT(DISTINCT CONCAT(class_id, ':', subject_id, ':', test_date)) AS c
    FROM weekly_test_results
    WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY teacher_id
") : false;
while ($qWeekly && $row = $qWeekly->fetch_assoc()) {
    $weeklyTestCounts[(int)$row['teacher_id']] = (int)$row['c'];
}

$avgResultByTeacher = [];
$qAvgResult = kpiTableExists($conn, 'results') ? $conn->query("
    SELECT teacher_id, AVG(percentage) AS avg_pct
    FROM results
    WHERE approval_status = 'Approved'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
      AND is_absent = 0
    GROUP BY teacher_id
") : false;
while ($qAvgResult && $row = $qAvgResult->fetch_assoc()) {
    $avgResultByTeacher[(int)$row['teacher_id']] = (float)($row['avg_pct'] ?? 0);
}

$teacherMarkedDays = [];
$hasAttendanceMarkedBy = kpiTableExists($conn, 'attendance') && kpiColumnExists($conn, 'attendance', 'marked_by');
$qMarkedDays = $hasAttendanceMarkedBy ? $conn->query("
    SELECT marked_by AS teacher_id, COUNT(DISTINCT attendance_date) AS marked_days
    FROM attendance
    WHERE marked_by IS NOT NULL
      AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY marked_by
") : false;
while ($qMarkedDays && $row = $qMarkedDays->fetch_assoc()) {
    $teacherMarkedDays[(int)$row['teacher_id']] = (int)($row['marked_days'] ?? 0);
}

$leaveDaysByTeacher = [];
$qLeaveDays = kpiTableExists($conn, 'leave_requests') ? $conn->query("
    SELECT
        teacher_id,
        SUM(
            DATEDIFF(
                LEAST(to_date, CURDATE()),
                GREATEST(from_date, DATE_SUB(CURDATE(), INTERVAL 29 DAY))
            ) + 1
        ) AS leave_days
    FROM leave_requests
    WHERE request_type = 'teacher'
      AND status = 'Approved'
      AND teacher_id IS NOT NULL
      AND to_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
      AND from_date <= CURDATE()
    GROUP BY teacher_id
") : false;
while ($qLeaveDays && $row = $qLeaveDays->fetch_assoc()) {
    $leaveDaysByTeacher[(int)$row['teacher_id']] = max(0, (int)($row['leave_days'] ?? 0));
}

$teacherScoreRows = [];
$workingDays = 26.0;
foreach ($teachers as $teacherId => $teacher) {
    $assignmentCount = max(1, (int)($assignmentCounts[$teacherId] ?? 0));
    $expectedMonthlySubmissions = max(1.0, $assignmentCount * 4.0);
    $lessonCount = (int)($lessonCounts[$teacherId] ?? 0);
    $weeklyCount = (int)($weeklyTestCounts[$teacherId] ?? 0);
    $avgResult = (float)($avgResultByTeacher[$teacherId] ?? 0.0);
    $markedDays = (int)($teacherMarkedDays[$teacherId] ?? 0);
    $leaveDays = (int)($leaveDaysByTeacher[$teacherId] ?? 0);

    if ($markedDays > 0) {
        $attendancePct = max(0.0, min(100.0, ($markedDays / $workingDays) * 100.0));
    } else {
        $attendancePct = max(0.0, min(100.0, (($workingDays - $leaveDays) / $workingDays) * 100.0));
    }
    $lessonCompliance = max(0.0, min(100.0, ($lessonCount / $expectedMonthlySubmissions) * 100.0));
    $weeklyCompliance = max(0.0, min(100.0, ($weeklyCount / $expectedMonthlySubmissions) * 100.0));
    $score = ($attendancePct * 0.35) + ($lessonCompliance * 0.25) + ($weeklyCompliance * 0.25) + ($avgResult * 0.15);

    $teacherScoreRows[] = [
        'name' => (string)($teacher['name'] ?? 'Teacher'),
        'email' => (string)($teacher['email'] ?? ''),
        'assigned' => array_keys($teacherAssignmentDetails[$teacherId] ?? []),
        'class_teacher_of' => $classTeacherByTeacher[$teacherId] ?? [],
        'assignments' => $assignmentCount,
        'marked_days' => $markedDays,
        'attendance_pct' => round($attendancePct, 1),
        'lesson_count' => $lessonCount,
        'weekly_count' => $weeklyCount,
        'avg_result' => round($avgResult, 1),
        'score' => round($score, 1),
    ];
}
usort($teacherScoreRows, static function (array $a, array $b): int {
    return $b['score'] <=> $a['score'];
});

$teacherPerPageOptions = [10, 25, 50, 100];
$teacherPerPage = (int)($_GET['teacher_per_page'] ?? 25);
if (!in_array($teacherPerPage, $teacherPerPageOptions, true)) {
    $teacherPerPage = 25;
}
$teacherPage = max(1, (int)($_GET['teacher_page'] ?? 1));
$teacherTotalRows = count($teacherScoreRows);
$teacherTotalPages = max(1, (int)ceil($teacherTotalRows / $teacherPerPage));
if ($teacherPage > $teacherTotalPages) {
    $teacherPage = $teacherTotalPages;
}
$teacherOffset = ($teacherPage - 1) * $teacherPerPage;
$teacherScoreRowsPage = array_slice($teacherScoreRows, $teacherOffset, $teacherPerPage);

if (isset($_GET['export']) && $_GET['export'] === 'teacher_kpi_excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="teacher_kpi_' . date('Ymd_His') . '.xls"');
    echo "<table border='1'>";
    echo "<tr><th>Teacher</th><th>Email</th><th>Assigned Class/Subject</th><th>Class Teacher Status</th><th>Assignments</th><th>Marked Days</th><th>Attendance %</th><th>Lesson Plans</th><th>Weekly Tests</th><th>Avg Result %</th><th>KPI Score</th></tr>";
    foreach ($teacherScoreRows as $row) {
        $assignedText = !empty($row['assigned']) ? implode('; ', $row['assigned']) : 'Not Assigned';
        $classTeacherText = !empty($row['class_teacher_of']) ? ('Yes - ' . implode(', ', $row['class_teacher_of'])) : 'No';
        echo "<tr>";
        echo "<td>" . htmlspecialchars((string)$row['name']) . "</td>";
        echo "<td>" . htmlspecialchars((string)$row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($assignedText) . "</td>";
        echo "<td>" . htmlspecialchars($classTeacherText) . "</td>";
        echo "<td>" . (int)$row['assignments'] . "</td>";
        echo "<td>" . (int)$row['marked_days'] . "</td>";
        echo "<td>" . htmlspecialchars(number_format((float)$row['attendance_pct'], 1)) . "%</td>";
        echo "<td>" . (int)$row['lesson_count'] . "</td>";
        echo "<td>" . (int)$row['weekly_count'] . "</td>";
        echo "<td>" . htmlspecialchars(number_format((float)$row['avg_result'], 1)) . "%</td>";
        echo "<td>" . htmlspecialchars(number_format((float)$row['score'], 1)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

$classesById = [];
$qClasses = $conn->query("SELECT id, class FROM classes");
while ($qClasses && $row = $qClasses->fetch_assoc()) {
    $classesById[(int)$row['id']] = (string)($row['class'] ?? '');
}

$qLearning = kpiTableExists($conn, 'results') ? $conn->query("
    SELECT
        r.id, r.class_id, r.subject_id, r.student_id, r.result_date, r.percentage, r.is_absent,
        COALESCE(NULLIF(r.student_name, ''), CONCAT('Student #', r.student_id)) AS student_name,
        COALESCE(NULLIF(s.subject_name, ''), CONCAT('Subject #', r.subject_id)) AS subject_name
    FROM results r
    LEFT JOIN subjects s ON s.id = r.subject_id
    WHERE approval_status = 'Approved'
    ORDER BY student_id ASC, class_id ASC, subject_id ASC, result_date ASC, id ASC
") : false;
$groups = [];
while ($qLearning && $row = $qLearning->fetch_assoc()) {
    if ((int)($row['is_absent'] ?? 0) === 1) {
        continue;
    }
    $key = (int)$row['student_id'] . ':' . (int)$row['class_id'] . ':' . (int)$row['subject_id'];
    if (!isset($groups[$key])) {
        $groups[$key] = [];
    }
    $groups[$key][] = $row;
}

$valueAddTotal = 0.0;
$valueAddCount = 0;
$highRiskCount = 0;
$finalCount = 0;
$classRisk = [];
$highRiskStudentsByClass = [];
foreach ($groups as $rows) {
    if (count($rows) === 0) {
        continue;
    }
    $first = (float)($rows[0]['percentage'] ?? 0.0);
    $lastRow = $rows[count($rows) - 1];
    $last = (float)($lastRow['percentage'] ?? 0.0);
    $classId = (int)($lastRow['class_id'] ?? 0);

    if ($first > 0) {
        $valueAddTotal += (($last - $first) / $first) * 100.0;
        $valueAddCount++;
    }
    $finalCount++;
    if ($last < 40.0) {
        $highRiskCount++;
        if (!isset($classRisk[$classId])) {
            $classRisk[$classId] = 0;
        }
        $classRisk[$classId]++;

        $studentId = (int)($lastRow['student_id'] ?? 0);
        $studentName = (string)($lastRow['student_name'] ?? ('Student #' . $studentId));
        $subjectName = (string)($lastRow['subject_name'] ?? ('Subject #' . (int)($lastRow['subject_id'] ?? 0)));
        if (!isset($highRiskStudentsByClass[$classId])) {
            $highRiskStudentsByClass[$classId] = [];
        }
        if (!isset($highRiskStudentsByClass[$classId][$studentId])) {
            $highRiskStudentsByClass[$classId][$studentId] = [
                'student_name' => $studentName,
                'subjects' => [],
                'scores' => [],
            ];
        }
        $highRiskStudentsByClass[$classId][$studentId]['subjects'][$subjectName] = true;
        $highRiskStudentsByClass[$classId][$studentId]['scores'][] = $last;
    }
}
$avgValueAdded = $valueAddCount > 0 ? round($valueAddTotal / $valueAddCount, 2) : 0.0;
$highRiskPct = $finalCount > 0 ? round(($highRiskCount / $finalCount) * 100.0, 2) : 0.0;

$customEntries = [];
$qCustom = $conn->query("
    SELECT id, category, indicator, target_value, actual_value, unit, notes, recorded_on
    FROM principal_kpi_entries
    ORDER BY recorded_on DESC, id DESC
    LIMIT 200
");
while ($qCustom && $row = $qCustom->fetch_assoc()) {
    $customEntries[] = $row;
}

include './partials/topbar.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Principal KPI Dashboard</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo h($flashType !== '' ? $flashType : 'info'); ?>"><?php echo h($flashMessage); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Learning KPI</div>
                    <div class="h5 mb-2"><?php echo h(number_format($avgValueAdded, 2)); ?>%</div>
                    <div class="text-muted small">Average Value-Added %</div>
                    <hr>
                    <div class="h6 mb-1"><?php echo h((string)$highRiskCount); ?> / <?php echo h((string)$finalCount); ?></div>
                    <div class="text-muted small">High-Risk Students (<?php echo h(number_format($highRiskPct, 2)); ?>%)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Teaching KPI</div>
                    <div class="h5 mb-2"><?php echo h((string)count($teacherScoreRows)); ?> Teachers</div>
                    <div class="text-muted small">Evaluated using attendance marking, weekly tests, lesson plans and results</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Other KPIs</div>
                    <div class="h5 mb-2"><?php echo h((string)count($customEntries)); ?> Entries</div>
                    <div class="text-muted small">Operations, Fee Recovery, Cultural, Growth, Maintenance, Partnerships</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h6 class="m-0 font-weight-bold text-primary">Teacher Evaluation KPI (Last 30-60 Days)</h6>
                <div class="mt-2 mt-md-0">
                    <a class="btn btn-success btn-sm mr-2" href="kpi.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'teacher_kpi_excel'])); ?>">Export Excel</a>
                    <button class="btn btn-danger btn-sm" type="button" id="exportTeacherKpiPdf">Export PDF</button>
                </div>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                <tr>
                    <th>Teacher</th>
                    <th>Assigned Class/Subject</th>
                    <th>Class Teacher Status</th>
                    <th>Assignments</th>
                    <th>Marked Days</th>
                    <th>Attendance %</th>
                    <th>Lesson Plans</th>
                    <th>Weekly Tests</th>
                    <th>Avg Result %</th>
                    <th>KPI Score</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($teacherScoreRowsPage as $row): ?>
                    <tr>
                        <td><?php echo h($row['name']); ?><br><small class="text-muted"><?php echo h($row['email']); ?></small></td>
                        <td>
                            <?php if (!empty($row['assigned'])): ?>
                                <div class="small">
                                    <?php foreach ($row['assigned'] as $assignmentLabel): ?>
                                        <div><?php echo h($assignmentLabel); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['class_teacher_of'])): ?>
                                <span class="badge badge-success">Yes</span>
                                <div class="small mt-1"><?php echo h(implode(', ', $row['class_teacher_of'])); ?></div>
                            <?php else: ?>
                                <span class="badge badge-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo h((string)$row['assignments']); ?></td>
                        <td><?php echo h((string)$row['marked_days']); ?></td>
                        <td><?php echo h(number_format((float)$row['attendance_pct'], 1)); ?>%</td>
                        <td><?php echo h((string)$row['lesson_count']); ?></td>
                        <td><?php echo h((string)$row['weekly_count']); ?></td>
                        <td><?php echo h(number_format((float)$row['avg_result'], 1)); ?>%</td>
                        <td><strong><?php echo h(number_format((float)$row['score'], 1)); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($teacherScoreRowsPage) === 0): ?>
                    <tr><td colspan="10" class="text-center text-muted">No teacher data found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
                <form method="get" class="form-inline">
                    <label class="mr-2 mb-0">Rows</label>
                    <select name="teacher_per_page" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <?php foreach ($teacherPerPageOptions as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $teacherPerPage === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="teacher_page" value="1">
                </form>
                <div class="small text-muted">
                    Showing <?php echo $teacherTotalRows > 0 ? ($teacherOffset + 1) : 0; ?> to <?php echo min($teacherOffset + $teacherPerPage, $teacherTotalRows); ?> of <?php echo $teacherTotalRows; ?>
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $prevPage = max(1, $teacherPage - 1);
                        $nextPage = min($teacherTotalPages, $teacherPage + 1);
                        ?>
                        <li class="page-item <?php echo $teacherPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="kpi.php?<?php echo http_build_query(array_merge($_GET, ['teacher_page' => $prevPage, 'teacher_per_page' => $teacherPerPage])); ?>">Prev</a>
                        </li>
                        <?php for ($p = 1; $p <= $teacherTotalPages; $p++): ?>
                            <li class="page-item <?php echo $p === $teacherPage ? 'active' : ''; ?>">
                                <a class="page-link" href="kpi.php?<?php echo http_build_query(array_merge($_GET, ['teacher_page' => $p, 'teacher_per_page' => $teacherPerPage])); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $teacherPage >= $teacherTotalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="kpi.php?<?php echo http_build_query(array_merge($_GET, ['teacher_page' => $nextPage, 'teacher_per_page' => $teacherPerPage])); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">High-Risk Distribution by Class</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                <tr>
                    <th>Class</th>
                    <th>High-Risk Students</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($classRisk as $classId => $count): ?>
                    <?php $rowId = 'risk-row-' . (int)$classId; ?>
                    <tr>
                        <td><?php echo h($classesById[(int)$classId] ?? ('Class ID ' . (int)$classId)); ?></td>
                        <td><?php echo h((string)$count); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleRiskRow('<?php echo h($rowId); ?>')">Show List</button>
                        </td>
                    </tr>
                    <tr id="<?php echo h($rowId); ?>" style="display:none;">
                        <td colspan="3">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Low Subjects</th>
                                        <th>Avg Low %</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $studentRiskList = $highRiskStudentsByClass[(int)$classId] ?? [];
                                    if (!empty($studentRiskList)):
                                        foreach ($studentRiskList as $studentId => $info):
                                            $avgLow = 0.0;
                                            if (!empty($info['scores'])) {
                                                $avgLow = array_sum($info['scores']) / count($info['scores']);
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo (int)$studentId; ?></td>
                                                <td><?php echo h((string)($info['student_name'] ?? ('Student #' . (int)$studentId))); ?></td>
                                                <td><?php echo h(implode(', ', array_keys($info['subjects'] ?? []))); ?></td>
                                                <td><?php echo h(number_format($avgLow, 1)); ?>%</td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr><td colspan="4" class="text-center text-muted">No high-risk students found for this class.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($classRisk) === 0): ?>
                    <tr><td colspan="3" class="text-center text-muted">No high-risk records found in approved results.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add KPI Entry</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="add_kpi_entry" value="1">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control" required>
                                <option value="Operations">Operations</option>
                                <option value="Fee Recovery">Fee Recovery</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Growth">Growth</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Partnerships">Partnerships</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Indicator</label>
                            <input type="text" name="indicator" class="form-control" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Target</label>
                                <input type="number" step="0.01" name="target_value" class="form-control">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Actual</label>
                                <input type="number" step="0.01" name="actual_value" class="form-control">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Unit</label>
                                <input type="text" name="unit" class="form-control" placeholder="% / count">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="recorded_on" class="form-control" value="<?php echo h(date('Y-m-d')); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">Save KPI</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Other KPI Tracker</h6>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Indicator</th>
                            <th>Target</th>
                            <th>Actual</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($customEntries as $entry): ?>
                            <?php
                            $target = $entry['target_value'];
                            $actual = $entry['actual_value'];
                            $unit = trim((string)($entry['unit'] ?? ''));
                            $status = 'Manual';
                            if ($target !== null && $actual !== null) {
                                $status = ((float)$actual >= (float)$target) ? 'Met' : 'Not Met';
                            }
                            ?>
                            <tr>
                                <td><?php echo h((string)$entry['recorded_on']); ?></td>
                                <td><?php echo h((string)$entry['category']); ?></td>
                                <td><?php echo h((string)$entry['indicator']); ?></td>
                                <td><?php echo h($target !== null ? ((string)$target . ($unit !== '' ? ' ' . $unit : '')) : '-'); ?></td>
                                <td><?php echo h($actual !== null ? ((string)$actual . ($unit !== '' ? ' ' . $unit : '')) : '-'); ?></td>
                                <td><?php echo h($status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($customEntries) === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted">No custom KPI entries yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script>
(function () {
    const btn = document.getElementById('exportTeacherKpiPdf');
    if (!btn || !window.jspdf) {
        return;
    }
    const rows = <?php echo json_encode($teacherScoreRows, JSON_UNESCAPED_UNICODE); ?>;
    btn.addEventListener('click', function () {
        const doc = new window.jspdf.jsPDF({orientation: 'landscape', unit: 'pt', format: 'a4'});
        doc.setFontSize(13);
        doc.text('Teacher Evaluation KPI', 40, 36);
        const body = rows.map(function (r) {
            const assigned = Array.isArray(r.assigned) && r.assigned.length ? r.assigned.join('; ') : 'Not Assigned';
            const classTeacher = Array.isArray(r.class_teacher_of) && r.class_teacher_of.length ? ('Yes - ' + r.class_teacher_of.join(', ')) : 'No';
            return [
                String(r.name || ''),
                String(r.email || ''),
                String(assigned),
                String(classTeacher),
                String(r.assignments || 0),
                String(r.marked_days || 0),
                String((Number(r.attendance_pct || 0)).toFixed(1)) + '%',
                String(r.lesson_count || 0),
                String(r.weekly_count || 0),
                String((Number(r.avg_result || 0)).toFixed(1)) + '%',
                String((Number(r.score || 0)).toFixed(1))
            ];
        });
        doc.autoTable({
            startY: 48,
            head: [['Teacher', 'Email', 'Assigned Class/Subject', 'Class Teacher Status', 'Assignments', 'Marked Days', 'Attendance %', 'Lesson Plans', 'Weekly Tests', 'Avg Result %', 'KPI Score']],
            body: body,
            styles: {fontSize: 8, cellPadding: 4},
            headStyles: {fillColor: [78, 115, 223]}
        });
        doc.save('teacher_kpi_' + new Date().toISOString().slice(0, 10) + '.pdf');
    });
})();

function toggleRiskRow(rowId) {
    var row = document.getElementById(rowId);
    if (!row) return;
    row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
}
</script>
<?php include './partials/footer.php'; ?>
