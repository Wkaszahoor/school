<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('results_reports', 'view', 'index.php');
include '../db.php';

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    if (!$stmt) return false;
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['c'] ?? 0) > 0;
}

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    if (!$stmt) return false;
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['c'] ?? 0) > 0;
}

function overall_grade(float $avg): string
{
    if ($avg >= 90) return 'Excellent';
    if ($avg >= 75) return 'Very Good';
    if ($avg >= 60) return 'Good';
    return 'Needs Improvement';
}

$classId = (int)($_GET['class_id'] ?? 0);
$termOptions = [
    'weekly' => ['label' => 'Weekly Test', 'result_type' => 0],
    'mid' => ['label' => 'Mid Term', 'result_type' => 2],
    'annual' => ['label' => 'Annual', 'result_type' => 3],
];
$termKey = strtolower(trim((string)($_GET['term'] ?? 'weekly')));
if (!isset($termOptions[$termKey])) {
    // Backward compatible mapping for older text values.
    if (strpos($termKey, 'mid') !== false) {
        $termKey = 'mid';
    } elseif (strpos($termKey, 'annual') !== false) {
        $termKey = 'annual';
    } else {
        $termKey = 'weekly';
    }
}
$term = $termOptions[$termKey]['label'];
$selectedResultType = (int)$termOptions[$termKey]['result_type'];
$printYear = trim((string)($_GET['year'] ?? ''));
$streamFilter = strtolower(trim((string)($_GET['stream'] ?? 'all')));
$streamLabels = [
    'all' => 'All Streams',
    'ics' => 'ICS',
    'pre_medical' => 'Pre-Medical',
    'pre_engineering' => 'Pre-Engineering',
    'general' => 'General',
    'unassigned' => 'Unassigned',
];
if (!array_key_exists($streamFilter, $streamLabels)) {
    $streamFilter = 'all';
}

// Branding configuration (adjust these to match your official brand kit).
$brandSchoolName = 'KORT School & College of Excellence';
$brandPrimary = '#1f3d7a';
$brandAccent = '#2f5da8';
$brandSoft = '#eef4ff';
$brandLogoPath = '../assets/school-logo.png';
if (!file_exists(__DIR__ . '/../assets/school-logo.png')) {
    $brandLogoPath = 'https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg';
}
$brandWatermarkOpacity = 0.06;

$classes = [];
$classesRes = $conn->query("SELECT id, class, academic_year FROM classes ORDER BY academic_year DESC, class ASC");
if ($classesRes) {
    while ($row = $classesRes->fetch_assoc()) {
        $classes[] = $row;
    }
}

$classInfo = null;
if ($classId > 0) {
    $cStmt = $conn->prepare("SELECT id, class, academic_year FROM classes WHERE id = ? LIMIT 1");
    if ($cStmt) {
        $cStmt->bind_param('i', $classId);
        $cStmt->execute();
        $classInfo = $cStmt->get_result()->fetch_assoc();
        $cStmt->close();
    }
}
if ($classInfo && $printYear === '') {
    $printYear = (string)($classInfo['academic_year'] ?? '');
}

$students = [];
if ($classInfo) {
    $studentsHasClassId = column_exists($conn, 'students', 'class_id');
    $studentSql = $studentsHasClassId
        ? "SELECT id, student_name, StudentId, class, academic_year, group_stream, roll_no, admission_no FROM students WHERE class_id = ?"
        : "SELECT id, student_name, StudentId, class, academic_year, group_stream, roll_no, admission_no FROM students WHERE class = ? AND IFNULL(academic_year,'') = IFNULL(?, '')";

    // Handle optional columns gracefully.
    if (!column_exists($conn, 'students', 'group_stream')) {
        $studentSql = str_replace(', group_stream', ", '' AS group_stream", $studentSql);
    }
    if (!column_exists($conn, 'students', 'roll_no')) {
        $studentSql = str_replace(', roll_no', ", '' AS roll_no", $studentSql);
    }
    if (!column_exists($conn, 'students', 'admission_no')) {
        $studentSql = str_replace(', admission_no', ", '' AS admission_no", $studentSql);
    }
    if ($streamFilter === 'unassigned') {
        $studentSql .= " AND (group_stream IS NULL OR group_stream = '')";
    } elseif ($streamFilter !== 'all') {
        $studentSql .= " AND group_stream = ?";
    }
    $studentSql .= " ORDER BY student_name ASC";

    $sStmt = $conn->prepare($studentSql);
    if ($sStmt) {
        if ($studentsHasClassId) {
            if ($streamFilter !== 'all' && $streamFilter !== 'unassigned') {
                $sStmt->bind_param('is', $classId, $streamFilter);
            } else {
                $sStmt->bind_param('i', $classId);
            }
        } else {
            $className = (string)$classInfo['class'];
            $classYear = (string)($classInfo['academic_year'] ?? '');
            if ($streamFilter !== 'all' && $streamFilter !== 'unassigned') {
                $sStmt->bind_param('sss', $className, $classYear, $streamFilter);
            } else {
                $sStmt->bind_param('ss', $className, $classYear);
            }
        }
        $sStmt->execute();
        $res = $sStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
        $sStmt->close();
    }
}

// Prepare statements once for per-student lookups.
$marksTableExists = table_exists($conn, 'marks');
$remarksTableExists = table_exists($conn, 'remarks');
$resultsTableExists = table_exists($conn, 'results');
$attendanceTableExists = table_exists($conn, 'attendance');

// Primary source: results table (teacher uploads from upload/index.php).
$resultsStmt = null;
if ($resultsTableExists
    && column_exists($conn, 'results', 'student_id')
    && column_exists($conn, 'results', 'class_id')
    && column_exists($conn, 'results', 'result_type')
    && column_exists($conn, 'results', 'percentage')
    && column_exists($conn, 'results', 'obtained_marks')
    && column_exists($conn, 'results', 'total_marks')) {
    $resultsStmt = $conn->prepare("
        SELECT COALESCE(s.subject_name, CONCAT('Subject #', r.subject_id)) AS subject,
               '-' AS participation,
               '-' AS understanding,
               '-' AS homework,
               ROUND(AVG(r.obtained_marks), 2) AS avg_obtained,
               ROUND(AVG(r.total_marks), 2) AS avg_total,
               ROUND(AVG(r.percentage), 2) AS avg_percentage
        FROM results r
        LEFT JOIN subjects s ON s.id = r.subject_id
        WHERE r.student_id = ?
          AND r.class_id = ?
          AND r.result_type = ?
        GROUP BY r.subject_id, s.subject_name
        ORDER BY subject ASC
    ");
}

// Fallback source for older installs.
$marksStmt = null;
$marksHasResultType = false;
$marksHasTerm = false;
if ($marksTableExists
    && column_exists($conn, 'marks', 'student_id')
    && column_exists($conn, 'marks', 'subject')
    && column_exists($conn, 'marks', 'participation')
    && column_exists($conn, 'marks', 'understanding')
    && column_exists($conn, 'marks', 'homework')
    && column_exists($conn, 'marks', 'test_score')) {
    $marksHasResultType = column_exists($conn, 'marks', 'result_type');
    $marksHasTerm = column_exists($conn, 'marks', 'term');
    if ($marksHasResultType) {
        $marksStmt = $conn->prepare("
            SELECT subject, participation, understanding, homework, test_score
            FROM marks
            WHERE student_id = ? AND result_type = ?
            ORDER BY subject ASC
        ");
    } elseif ($marksHasTerm) {
        $marksStmt = $conn->prepare("
            SELECT subject, participation, understanding, homework, test_score
            FROM marks
            WHERE student_id = ? AND LOWER(term) = ?
            ORDER BY subject ASC
        ");
    } else {
        $marksStmt = $conn->prepare("
            SELECT subject, participation, understanding, homework, test_score
            FROM marks
            WHERE student_id = ?
            ORDER BY subject ASC
        ");
    }
}

$attendanceStmt = null;
if ($attendanceTableExists && column_exists($conn, 'attendance', 'student_id') && column_exists($conn, 'attendance', 'status')) {
    $attendanceStmt = $conn->prepare("
        SELECT
            SUM(CASE WHEN status = 'P' THEN 1 ELSE 0 END) AS present_count,
            SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) AS absent_count,
            SUM(CASE WHEN status = 'L' THEN 1 ELSE 0 END) AS leave_count
        FROM attendance
        WHERE student_id = ?
    ");
}

$remarkTextExpr = "'' AS remark_text, '' AS remark_date";
if ($remarksTableExists && column_exists($conn, 'remarks', 'student_id')) {
    $textCols = ['remark', 'remarks', 'teacher_remark', 'comment', 'note'];
    $dateCols = ['remark_date', 'created_at', 'date'];
    $pickedText = '';
    $pickedDate = '';
    foreach ($textCols as $c) {
        if (column_exists($conn, 'remarks', $c)) {
            $pickedText = $c;
            break;
        }
    }
    foreach ($dateCols as $c) {
        if (column_exists($conn, 'remarks', $c)) {
            $pickedDate = $c;
            break;
        }
    }
    if ($pickedText !== '') {
        $remarkTextExpr = $pickedDate !== ''
            ? "$pickedText AS remark_text, $pickedDate AS remark_date"
            : "$pickedText AS remark_text, '' AS remark_date";
    }
}

$remarksStmt = null;
if ($remarksTableExists && column_exists($conn, 'remarks', 'student_id')) {
    $remarksStmt = $conn->prepare("
        SELECT $remarkTextExpr
        FROM remarks
        WHERE student_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Nosh Progress Report</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            background: radial-gradient(circle at top, <?php echo h($brandSoft); ?> 0%, #f6f9ff 45%, #f9fbff 100%);
        }
        .no-print {
            max-width: 980px;
            margin: 16px auto 8px;
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .no-print form {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn {
            border: 1px solid #2f5da8;
            background: #2f5da8;
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            font-size: 13px;
        }
        .btn.secondary {
            background: #fff;
            color: #2f5da8;
        }
        .report-page {
            width: 180mm;
            min-height: 267mm;
            margin: 10px auto;
            background: #fff;
            box-shadow: 0 10px 28px rgba(31,61,122,.12);
            padding: 12mm;
            box-sizing: border-box;
            border: 1px solid #dbe6ff;
            border-top: 6px solid <?php echo h($brandAccent); ?>;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }
        .watermark {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            pointer-events: none;
            z-index: 0;
        }
        .watermark img {
            width: 110mm;
            max-width: 70%;
            opacity: <?php echo h((string)$brandWatermarkOpacity); ?>;
            filter: grayscale(100%);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid <?php echo h($brandAccent); ?>;
            padding-bottom: 10px;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }
        .logo {
            width: 78px;
            height: 78px;
            object-fit: contain;
            margin-bottom: 6px;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,.15));
        }
        .title-wrap {
            text-align: center;
        }
        .school-name {
            font-size: 22px;
            font-weight: 700;
            color: <?php echo h($brandPrimary); ?>;
            margin-bottom: 4px;
            letter-spacing: .2px;
        }
        .report-title {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .8px;
            color: #0f172a;
        }
        .meta {
            font-size: 12px;
            margin-top: 4px;
            color: #4b5563;
        }
        .section-title {
            margin: 12px 0 7px;
            font-size: 12px;
            font-weight: 700;
            color: <?php echo h($brandPrimary); ?>;
            text-transform: uppercase;
            letter-spacing: .6px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px 14px;
            font-size: 13px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            text-align: left;
        }
        th {
            background: #edf3ff;
            color: #0f172a;
        }
        .summary {
            margin-top: 8px;
            border: 1px solid #dbe6ff;
            background: linear-gradient(120deg, #f7faff 0%, #eff5ff 100%);
            padding: 8px;
            font-size: 12px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px 10px;
            border-radius: 6px;
            position: relative;
            z-index: 1;
        }
        .remarks {
            margin-top: 8px;
            min-height: 48px;
            border: 1px solid #dbe6ff;
            padding: 8px;
            font-size: 12px;
            background: #fff;
            border-radius: 6px;
            position: relative;
            z-index: 1;
        }
        .signatures {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            text-align: center;
            font-size: 12px;
            position: relative;
            z-index: 1;
        }
        .sig-line {
            margin-top: 26px;
            border-top: 1px solid #6b7280;
            padding-top: 4px;
        }
        .page-break {
            page-break-after: always;
        }
        .empty-box {
            max-width: 980px;
            margin: 16px auto;
            background: #fff;
            padding: 14px;
            border: 1px solid #ddd;
        }
        @media print {
            body {
                background: #fff;
            }
            .no-print { display: none; }
            .report-page {
                margin: 0;
                width: auto;
                min-height: auto;
                box-shadow: none;
                padding: 0;
                border: none;
                border-top: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
<div class="no-print">
    <form method="get">
        <label for="class_id"><strong>Class:</strong></label>
        <select name="class_id" id="class_id" required>
            <option value="">Select class</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$c['id'] === $classId) ? 'selected' : ''; ?>>
                    <?php echo h($c['class']); ?> (<?php echo h($c['academic_year']); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <label for="term"><strong>Term:</strong></label>
        <select name="term" id="term">
            <?php foreach ($termOptions as $tk => $tv): ?>
                <option value="<?php echo h($tk); ?>" <?php echo $termKey === $tk ? 'selected' : ''; ?>>
                    <?php echo h($tv['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="stream"><strong>Stream:</strong></label>
        <select name="stream" id="stream">
            <?php foreach ($streamLabels as $streamKey => $streamText): ?>
                <option value="<?php echo h($streamKey); ?>" <?php echo $streamFilter === $streamKey ? 'selected' : ''; ?>>
                    <?php echo h($streamText); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Load</button>
    </form>
    <button class="btn" onclick="window.print()">Print</button>
    <button class="btn" type="button" onclick="exportTopNoshPdf()">Export PDF</button>
    <a href="class_year.php" class="btn secondary">Back</a>
</div>

<div id="report-container">
<?php if (!$classInfo): ?>
    <div class="empty-box">Please select a class to print Top Nosh Progress Reports.</div>
<?php elseif (empty($students)): ?>
    <div class="empty-box">No students found for selected class.</div>
<?php else: ?>
    <?php foreach ($students as $index => $st): ?>
        <?php
        $studentId = (int)$st['id'];
        $studentName = (string)($st['student_name'] ?? 'N/A');
        $rollNo = trim((string)($st['roll_no'] ?? ''));
        if ($rollNo === '') {
            $rollNo = (string)$studentId;
        }
        $admissionNo = trim((string)($st['admission_no'] ?? ''));
        if ($admissionNo === '') {
            $admissionNo = (string)($st['StudentId'] ?? '');
        }
        $streamKey = strtolower(trim((string)($st['group_stream'] ?? '')));
        $streamText = $streamLabels[$streamKey] ?? (($streamKey !== '') ? strtoupper(str_replace('_', ' ', $streamKey)) : 'Unassigned');

        $academicRows = [];
        if ($resultsStmt) {
            $resultsStmt->bind_param('iii', $studentId, $classId, $selectedResultType);
            $resultsStmt->execute();
            $rRes = $resultsStmt->get_result();
            while ($rRow = $rRes->fetch_assoc()) {
                $obt = (float)($rRow['avg_obtained'] ?? 0);
                $tot = (float)($rRow['avg_total'] ?? 0);
                $pct = (float)($rRow['avg_percentage'] ?? 0);
                $rRow['test_score'] = rtrim(rtrim(number_format($obt, 2, '.', ''), '0'), '.') . ' / '
                    . rtrim(rtrim(number_format($tot, 2, '.', ''), '0'), '.') . ' ('
                    . rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.') . '%)';
                $academicRows[] = $rRow;
            }
        }
        if (empty($academicRows) && $marksStmt) {
            if ($marksHasResultType) {
                $marksStmt->bind_param('ii', $studentId, $selectedResultType);
            } elseif ($marksHasTerm) {
                $marksStmt->bind_param('is', $studentId, $termKey);
            } else {
                $marksStmt->bind_param('i', $studentId);
            }
            $marksStmt->execute();
            $mRes = $marksStmt->get_result();
            while ($mRow = $mRes->fetch_assoc()) {
                $academicRows[] = $mRow;
            }
        }

        $present = 0; $absent = 0; $leave = 0;
        if ($attendanceStmt) {
            $attendanceStmt->bind_param('i', $studentId);
            $attendanceStmt->execute();
            $aRow = $attendanceStmt->get_result()->fetch_assoc();
            $present = (int)($aRow['present_count'] ?? 0);
            $absent = (int)($aRow['absent_count'] ?? 0);
            $leave = (int)($aRow['leave_count'] ?? 0);
        }
        $attendanceTotal = $present + $absent + $leave;
        $attendancePct = $attendanceTotal > 0 ? round(($present / $attendanceTotal) * 100, 1) : 0;

        $remarkText = 'No remarks available.';
        if ($remarksStmt) {
            $remarksStmt->bind_param('i', $studentId);
            $remarksStmt->execute();
            $re = $remarksStmt->get_result()->fetch_assoc();
            if ($re && trim((string)($re['remark_text'] ?? '')) !== '') {
                $remarkText = (string)$re['remark_text'];
            }
        }

        $testScores = [];
        foreach ($academicRows as $ar) {
            if (isset($ar['test_score']) && is_numeric($ar['test_score'])) {
                $testScores[] = (float)$ar['test_score'];
            }
        }
        $avgScore = !empty($testScores) ? round(array_sum($testScores) / count($testScores), 2) : 0;
        $grade = overall_grade($avgScore);
        ?>
        <div class="report-page <?php echo ($index < count($students) - 1) ? 'page-break' : ''; ?>">
            <div class="watermark">
                <img src="<?php echo h($brandLogoPath); ?>" alt="Watermark Logo">
            </div>
            <div class="header">
                <img class="logo" src="<?php echo h($brandLogoPath); ?>" alt="School Logo">
                <div class="title-wrap">
                    <div class="school-name"><?php echo h($brandSchoolName); ?></div>
                    <div class="report-title">PROGRESS REPORT</div>
                    <div class="meta">Academic Year: <?php echo h($printYear); ?> | Term: <?php echo h($term); ?></div>
                </div>
            </div>

            <div class="section-title">Student Information</div>
            <div class="info-grid">
                <div><strong>Name:</strong> <?php echo h($studentName); ?></div>
                <div><strong>Class:</strong> <?php echo h($classInfo['class']); ?></div>
                <div><strong>Roll No:</strong> <?php echo h($rollNo); ?></div>
                <div><strong>Admission No:</strong> <?php echo h($admissionNo); ?></div>
                <div><strong>Stream:</strong> <?php echo h($streamText); ?></div>
            </div>

            <div class="section-title">Academic Performance</div>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Participation</th>
                        <th>Understanding</th>
                        <th>Homework</th>
                        <th>Test Score</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($academicRows)): ?>
                    <?php foreach ($academicRows as $ar): ?>
                        <tr>
                            <td><?php echo h($ar['subject'] ?? 'N/A'); ?></td>
                            <td><?php echo h($ar['participation'] ?? '-'); ?></td>
                            <td><?php echo h($ar['understanding'] ?? '-'); ?></td>
                            <td><?php echo h($ar['homework'] ?? '-'); ?></td>
                            <td><?php echo h($ar['test_score'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">No academic marks found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <div class="summary">
                <div><strong>Average Test Score:</strong> <?php echo h($avgScore); ?></div>
                <div><strong>Overall Grade:</strong> <?php echo h($grade); ?></div>
                <div><strong>Attendance:</strong> P: <?php echo (int)$present; ?>, A: <?php echo (int)$absent; ?>, L: <?php echo (int)$leave; ?></div>
                <div><strong>Attendance Percentage:</strong> <?php echo h($attendancePct); ?>%</div>
            </div>

            <div class="section-title">Teacher Remarks</div>
            <div class="remarks"><?php echo h($remarkText); ?></div>

            <div class="signatures">
                <div><div class="sig-line">Class Teacher</div></div>
                <div><div class="sig-line">Principal</div></div>
                <div><div class="sig-line">Parent</div></div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportTopNoshPdf() {
    var container = document.getElementById('report-container');
    if (!container) return;
    var classId = "<?php echo (int)$classId; ?>";
    var term = "<?php echo h($term); ?>".replace(/\s+/g, '_');
    var year = "<?php echo h($printYear); ?>".replace(/\s+/g, '_');
    var fileName = 'Top_Nosh_Class_' + classId + '_' + year + '_' + term + '.pdf';

    var opt = {
        margin: [5, 5, 5, 5],
        filename: fileName,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(container).save();
}
</script>
</body>
</html>
