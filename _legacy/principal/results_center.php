<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('results_reports', 'view', 'index.php');
include '../db.php';

function bindParamsDynamic(mysqli_stmt $stmt, string $types, array &$params): bool
{
    if ($types === '') {
        return true;
    }
    $bind = [];
    $bind[] = &$types;
    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }
    return call_user_func_array([$stmt, 'bind_param'], $bind);
}

function fetchAllPrepared(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types !== '') {
        if (!bindParamsDynamic($stmt, $types, $params)) {
            $stmt->close();
            return [];
        }
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $stmt->close();
    return $rows;
}

function safeDate(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}

function formatGroupStreamLabel(string $stream): string
{
    $raw = strtolower(trim($stream));
    if ($raw === '') {
        return '-';
    }
    $flat = str_replace([' ', '-', '_'], '', $raw);
    if ($flat === 'ics') {
        return 'ICS';
    }
    if ($flat === 'premedical') {
        return 'Pre-Medical';
    }
    if ($flat === 'general') {
        return 'General';
    }
    return ucwords(str_replace('_', ' ', $raw));
}

function dbTableExists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row['c'] ?? 0) > 0);
}

function dbColumnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row['c'] ?? 0) > 0);
}

$classes = fetchAllPrepared($conn, "SELECT id, class FROM classes ORDER BY class ASC");
$subjects = fetchAllPrepared($conn, "SELECT id, subject_name FROM subjects ORDER BY subject_name ASC");
$teachers = fetchAllPrepared($conn, "SELECT id, name FROM teachers ORDER BY name ASC");

$filterClassId = (int)($_GET['class_id'] ?? 0);
$filterSubjectId = (int)($_GET['subject_id'] ?? 0);
$filterTeacherId = (int)($_GET['teacher_id'] ?? 0);
$filterResultType = isset($_GET['result_type']) ? (int)$_GET['result_type'] : -1;
$filterGroup = strtolower(trim((string)($_GET['group_stream'] ?? '')));
$filterGroup = str_replace([' ', '-'], '_', $filterGroup);
$allowedGroups = ['all', 'general', 'ics', 'pre_medical'];
if (!in_array($filterGroup, $allowedGroups, true)) {
    $filterGroup = 'all';
}
$filterDateFrom = safeDate((string)($_GET['date_from'] ?? ''));
$filterDateTo = safeDate((string)($_GET['date_to'] ?? ''));

$where = ['1=1'];
$types = '';
$params = [];

if ($filterClassId > 0) {
    $where[] = 'r.class_id = ?';
    $types .= 'i';
    $params[] = $filterClassId;
}
if ($filterSubjectId > 0) {
    $where[] = 'r.subject_id = ?';
    $types .= 'i';
    $params[] = $filterSubjectId;
}
if ($filterTeacherId > 0) {
    $where[] = 'r.teacher_id = ?';
    $types .= 'i';
    $params[] = $filterTeacherId;
}
if ($filterResultType >= 0 && $filterResultType <= 3) {
    $where[] = 'r.result_type = ?';
    $types .= 'i';
    $params[] = $filterResultType;
}
if ($filterDateFrom !== '') {
    $where[] = 'r.result_date >= ?';
    $types .= 's';
    $params[] = $filterDateFrom;
}
if ($filterDateTo !== '') {
    $where[] = 'r.result_date <= ?';
    $types .= 's';
    $params[] = $filterDateTo;
}
if ($filterGroup !== 'all') {
    if ($filterGroup === 'general') {
        $where[] = "
            EXISTS (
                SELECT 1
                FROM students stf
                WHERE stf.id = r.student_id
                  AND (
                    TRIM(COALESCE(stf.group_stream, '')) = ''
                    OR LOWER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(stf.group_stream, '')), ' ', ''), '-', ''), '_', '')) = 'general'
                  )
            )
        ";
    } else {
        $normalizedGroup = str_replace('_', '', $filterGroup);
        $where[] = "
            EXISTS (
                SELECT 1
                FROM students stf
                WHERE stf.id = r.student_id
                  AND LOWER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(stf.group_stream, '')), ' ', ''), '-', ''), '_', '')) = ?
            )
        ";
        $types .= 's';
        $params[] = $normalizedGroup;
    }
}

$whereSql = implode(' AND ', $where);

$summaryRows = fetchAllPrepared(
    $conn,
    "
    SELECT
        COUNT(*) AS total_rows,
        COUNT(DISTINCT r.student_id) AS students_count,
        COUNT(DISTINCT CONCAT(r.class_id, '-', r.subject_id, '-', r.result_date, '-', r.result_type)) AS batches_count,
        ROUND(AVG(r.percentage), 2) AS avg_percentage,
        SUM(CASE WHEN r.approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved_rows,
        SUM(CASE WHEN r.approval_status = 'Pending' THEN 1 ELSE 0 END) AS pending_rows
    FROM results r
    WHERE $whereSql
    ",
    $types,
    $params
);
$summary = $summaryRows[0] ?? ['total_rows' => 0, 'students_count' => 0, 'batches_count' => 0, 'avg_percentage' => 0, 'approved_rows' => 0, 'pending_rows' => 0];

$trendRows = fetchAllPrepared(
    $conn,
    "
    SELECT r.result_date, ROUND(AVG(r.percentage), 2) AS avg_percentage, COUNT(*) AS row_count
    FROM results r
    WHERE $whereSql
    GROUP BY r.result_date
    ORDER BY r.result_date ASC
    ",
    $types,
    $params
);

$classComparisonRows = fetchAllPrepared(
    $conn,
    "
    SELECT c.class AS class_name, ROUND(AVG(r.percentage), 2) AS avg_percentage, COUNT(*) AS row_count
    FROM results r
    LEFT JOIN classes c ON c.id = r.class_id
    WHERE $whereSql
    GROUP BY r.class_id, c.class
    ORDER BY avg_percentage DESC
    ",
    $types,
    $params
);

$subjectComparisonRows = fetchAllPrepared(
    $conn,
    "
    SELECT s.subject_name, ROUND(AVG(r.percentage), 2) AS avg_percentage, COUNT(*) AS row_count
    FROM results r
    LEFT JOIN subjects s ON s.id = r.subject_id
    WHERE $whereSql
    GROUP BY r.subject_id, s.subject_name
    ORDER BY avg_percentage DESC
    ",
    $types,
    $params
);

$gazetteRows = fetchAllPrepared(
    $conn,
    "
    SELECT
        r.student_id,
        COALESCE(st.StudentId, '') AS student_code,
        r.student_name,
        COALESCE(c.class, '') AS class_name,
        COALESCE(st.group_stream, '') AS group_stream,
        GROUP_CONCAT(DISTINCT COALESCE(NULLIF(s.subject_name, ''), CONCAT('Subject #', r.subject_id)) ORDER BY s.subject_name ASC SEPARATOR ', ') AS subject_names,
        GROUP_CONCAT(
            CONCAT(
                COALESCE(NULLIF(s.subject_name, ''), CONCAT('Subject #', r.subject_id)),
                ' (',
                CAST(r.obtained_marks AS CHAR),
                '/',
                CAST(r.total_marks AS CHAR),
                ')'
            )
            ORDER BY s.subject_name ASC
            SEPARATOR '||'
        ) AS subject_marks_list,
        COUNT(*) AS subjects_count,
        SUM(r.obtained_marks) AS total_obtained,
        SUM(r.total_marks) AS total_marks,
        ROUND((SUM(r.obtained_marks) / NULLIF(SUM(r.total_marks), 0)) * 100, 2) AS overall_percentage,
        SUM(CASE WHEN r.approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved_rows,
        SUM(CASE WHEN r.approval_status = 'Pending' THEN 1 ELSE 0 END) AS pending_rows,
        SUM(CASE WHEN r.approval_status = 'Returned' THEN 1 ELSE 0 END) AS returned_rows
    FROM results r
    LEFT JOIN students st ON st.id = r.student_id
    LEFT JOIN classes c ON c.id = r.class_id
    LEFT JOIN subjects s ON s.id = r.subject_id
    WHERE $whereSql
    GROUP BY r.student_id, st.StudentId, r.student_name, c.class, st.group_stream
    ORDER BY overall_percentage DESC, total_obtained DESC, r.student_name ASC
    ",
    $types,
    $params
);

$topPositions = array_slice($gazetteRows, 0, 10);

$lockSummaryRows = fetchAllPrepared(
    $conn,
    "
    SELECT
        SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) AS locked_groups,
        SUM(CASE WHEN is_locked = 0 THEN 1 ELSE 0 END) AS unlocked_groups
    FROM result_lock_groups
    "
);
$lockSummary = $lockSummaryRows[0] ?? ['locked_groups' => 0, 'unlocked_groups' => 0];

$resultTypeLabelMap = [0 => 'Weekly', 1 => 'Monthly', 2 => 'Mid Term', 3 => 'Annual'];

if (isset($_GET['export']) && $_GET['export'] === 'gazette_excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="gazette_' . date('Ymd_His') . '.xls"');
    echo "<table border='1'>";
    echo "<tr><th>Position</th><th>Student ID</th><th>Student Name</th><th>Class</th><th>Group</th><th>Subjects & Marks</th><th>Obtained</th><th>Total</th><th>Percentage</th><th>Approval Status</th></tr>";
    $pos = 1;
    foreach ($gazetteRows as $row) {
        $approvalStatus = 'Pending';
        $subjectsCount = (int)($row['subjects_count'] ?? 0);
        $approvedRows = (int)($row['approved_rows'] ?? 0);
        $pendingRows = (int)($row['pending_rows'] ?? 0);
        $returnedRows = (int)($row['returned_rows'] ?? 0);
        if ($returnedRows > 0) {
            $approvalStatus = 'Returned';
        } elseif ($subjectsCount > 0 && $approvedRows === $subjectsCount) {
            $approvalStatus = 'Approved';
        } elseif ($approvedRows > 0 && $pendingRows > 0) {
            $approvalStatus = 'Partially Approved';
        }
        echo "<tr>";
        echo "<td>" . $pos++ . "</td>";
        echo "<td>" . htmlspecialchars((string)$row['student_code']) . "</td>";
        echo "<td>" . htmlspecialchars((string)$row['student_name']) . "</td>";
        echo "<td>" . htmlspecialchars((string)$row['class_name']) . "</td>";
        echo "<td>" . htmlspecialchars(formatGroupStreamLabel((string)($row['group_stream'] ?? ''))) . "</td>";
        $subjectMarksExport = str_replace('||', '; ', (string)($row['subject_marks_list'] ?? ''));
        echo "<td>" . htmlspecialchars($subjectMarksExport !== '' ? $subjectMarksExport : (string)($row['subject_names'] ?? '-')) . "</td>";
        echo "<td>" . (int)$row['total_obtained'] . "</td>";
        echo "<td>" . (int)$row['total_marks'] . "</td>";
        echo "<td>" . htmlspecialchars((string)$row['overall_percentage']) . "%</td>";
        echo "<td>" . htmlspecialchars($approvalStatus) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

if (isset($_GET['view']) && $_GET['view'] === 'card') {
    $studentId = (int)($_GET['student_id'] ?? 0);
    $showQr = ((int)($_GET['qr'] ?? 1) === 1);
    $resultTypeText = ($filterResultType >= 0 && isset($resultTypeLabelMap[$filterResultType]))
        ? $resultTypeLabelMap[$filterResultType]
        : 'All Terms';
    $cardLogoPath = '../assets/school-logo.png';
    if (!file_exists(__DIR__ . '/../assets/school-logo.png')) {
        $cardLogoPath = 'https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg';
    }
    $studentHeadRows = fetchAllPrepared(
        $conn,
        "
        SELECT
            st.id,
            COALESCE(st.StudentId, '') AS student_code,
            COALESCE(NULLIF(st.student_name, ''), NULLIF(st.full_name, ''), '-') AS student_name,
            COALESCE(st.class, '') AS class_name,
            COALESCE(NULLIF(st.academic_year, ''), 'N/A') AS session_year,
            COALESCE(NULLIF(st.group_stream, ''), '') AS group_stream
        FROM students st
        WHERE st.id = ?
        LIMIT 1
        ",
        'i',
        [$studentId]
    );
    $studentHead = $studentHeadRows[0] ?? null;
    $cardRows = [];
    if ($studentHead) {
        $cardRows = fetchAllPrepared(
            $conn,
            "
            SELECT
                COALESCE(NULLIF(s.subject_name, ''), '-') AS subject_name,
                r.result_date,
                r.obtained_marks,
                r.total_marks,
                r.percentage,
                r.grade,
                r.approval_status,
                COALESCE(NULLIF(t.name, ''), CONCAT('Teacher #', r.teacher_id)) AS teacher_name
            FROM results r
            LEFT JOIN subjects s ON s.id = r.subject_id
            LEFT JOIN teachers t ON t.id = r.teacher_id
            WHERE r.student_id = ? AND $whereSql
            ORDER BY r.result_date DESC, s.subject_name ASC
            ",
            'i' . $types,
            array_merge([$studentId], $params)
        );
    }
    $teacherRemarks = '';
    $teacherRemarkDate = '';
    if ($studentHead && dbTableExists($conn, 'remarks') && dbColumnExists($conn, 'remarks', 'student_id')) {
        $remarkTextCols = ['teacher_remark', 'remarks', 'remark', 'comment', 'note'];
        $remarkDateCols = ['remark_date', 'created_at', 'date'];
        $pickedTextCol = '';
        $pickedDateCol = '';
        foreach ($remarkTextCols as $col) {
            if (dbColumnExists($conn, 'remarks', $col)) {
                $pickedTextCol = $col;
                break;
            }
        }
        foreach ($remarkDateCols as $col) {
            if (dbColumnExists($conn, 'remarks', $col)) {
                $pickedDateCol = $col;
                break;
            }
        }
        if ($pickedTextCol !== '') {
            $orderCol = dbColumnExists($conn, 'remarks', 'id') ? 'id' : $pickedTextCol;
            $remarkSelectDate = ($pickedDateCol !== '') ? ", `$pickedDateCol` AS remark_date" : "";
            $remarkRows = fetchAllPrepared(
                $conn,
                "SELECT `$pickedTextCol` AS remark_text{$remarkSelectDate} FROM remarks WHERE student_id = ? ORDER BY `$orderCol` DESC LIMIT 1",
                'i',
                [$studentId]
            );
            $remarkRow = $remarkRows[0] ?? [];
            $teacherRemarks = trim((string)($remarkRow['remark_text'] ?? ''));
            $teacherRemarkDate = trim((string)($remarkRow['remark_date'] ?? ''));
        }
    }
    if ($teacherRemarks === '') {
        $teacherRemarks = 'No teacher remarks available in records.';
    }
    $totalObtained = 0;
    $totalMarks = 0;
    $teacherNames = [];
    foreach ($cardRows as $row) {
        $totalObtained += (float)($row['obtained_marks'] ?? 0);
        $totalMarks += (float)($row['total_marks'] ?? 0);
        $tn = trim((string)($row['teacher_name'] ?? ''));
        if ($tn !== '') {
            $teacherNames[$tn] = true;
        }
    }
    $overallPercentage = ($totalMarks > 0) ? round(($totalObtained / $totalMarks) * 100, 2) : 0;
    $teacherListText = !empty($teacherNames) ? implode(', ', array_keys($teacherNames)) : 'N/A';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Result Card</title>
        <link href="../admin/css/sb-admin-2.css" rel="stylesheet">
        <style>
            body { background: #f4f7fb; }
            .card-wrap {
                max-width: 920px;
                margin: 18px auto;
                border: 1px solid #dbe4ef;
                border-radius: 12px;
                padding: 20px;
                background: #fff;
                box-shadow: 0 3px 14px rgba(15, 23, 42, 0.06);
            }
            .school-head {
                position: relative;
                text-align: center;
                border-bottom: 2px solid #e5edf6;
                padding-bottom: 12px;
                margin-bottom: 16px;
            }
            .school-logo {
                width: 78px;
                height: 78px;
                object-fit: contain;
                margin: 0 auto 8px;
                display: block;
            }
            .school-name {
                margin: 0;
                font-size: 26px;
                font-weight: 700;
                color: #0f2f57;
            }
            .card-title {
                margin: 2px 0 0;
                font-size: 15px;
                letter-spacing: 0.08em;
                color: #526377;
                text-transform: uppercase;
                font-weight: 600;
            }
            .result-type-label {
                margin: 5px 0 0;
                font-size: 13px;
                color: #334155;
                font-weight: 600;
            }
            .qrbox {
                width: 88px;
                height: 88px;
                border: 1px solid #d7e2ee;
                border-radius: 10px;
                padding: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                position: absolute;
                right: 0;
                top: 0;
            }
            .no-print-actions {
                display: flex;
                gap: 8px;
                justify-content: flex-end;
                margin-bottom: 10px;
            }
            .student-meta {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 14px;
            }
            .meta-item {
                border: 1px solid #e5edf6;
                border-radius: 8px;
                padding: 9px 10px;
                background: #fafcff;
            }
            .meta-item .label {
                font-size: 11px;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                margin-bottom: 3px;
            }
            .meta-item .value {
                font-weight: 700;
                color: #0f172a;
            }
            .section-title {
                margin: 14px 0 8px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #1f3d67;
                font-weight: 700;
            }
            .marks-table th {
                background: #eef4fb;
                color: #163a5f;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }
            .marks-table td {
                font-size: 13px;
                vertical-align: middle;
            }
            .summary-row {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 10px;
            }
            .summary-pill {
                border: 1px solid #dbe4ef;
                background: #f7fbff;
                border-radius: 999px;
                padding: 7px 12px;
                font-size: 12px;
                color: #1f2937;
                font-weight: 600;
            }
            .remarks-box {
                border: 1px solid #dbe4ef;
                border-radius: 8px;
                min-height: 88px;
                padding: 10px;
                background: #fbfdff;
                white-space: pre-wrap;
                color: #111827;
            }
            .remarks-meta {
                margin-top: 6px;
                font-size: 12px;
                color: #64748b;
            }
            .digital-note {
                margin-top: 14px;
                font-size: 11px;
                color: #4b5563;
                border-top: 1px dashed #cfd9e5;
                padding-top: 10px;
                text-align: center;
            }
            @media (max-width: 768px) {
                .student-meta { grid-template-columns: 1fr 1fr; }
                .qrbox { position: static; margin: 8px auto 0; }
                .school-head { padding-right: 0; }
            }
            @media print {
                .no-print { display: none !important; }
                body { background: #fff; }
                .card-wrap {
                    border: none;
                    box-shadow: none;
                    margin: 0;
                    max-width: 100%;
                    padding: 0;
                }
                .remarks-box { min-height: 72px; }
            }
        </style>
    </head>
    <body>
        <div class="card-wrap">
            <div class="no-print no-print-actions">
                <a href="results_center.php" class="btn btn-secondary btn-sm">Back</a>
                <button class="btn btn-primary btn-sm" onclick="window.print()">Print / Save PDF</button>
            </div>

            <?php if (!$studentHead): ?>
                <div class="alert alert-warning">Student not found.</div>
            <?php else: ?>
                <?php $studentGroupLabel = trim((string)($studentHead['group_stream'] ?? '')) !== '' ? formatGroupStreamLabel((string)$studentHead['group_stream']) : 'N/A'; ?>
                <div class="school-head">
                    <?php if ($showQr): ?>
                        <div id="qrbox" class="qrbox"></div>
                    <?php endif; ?>
                    <img class="school-logo" src="<?php echo htmlspecialchars($cardLogoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="KORT Logo">
                    <h1 class="school-name">KORT School and College of Excellence</h1>
                    <div class="card-title">Student Result Card</div>
                    <div class="result-type-label">Result Type: <?php echo htmlspecialchars((string)$resultTypeText, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="student-meta">
                    <div class="meta-item">
                        <div class="label">Student Name</div>
                        <div class="value"><?php echo htmlspecialchars((string)$studentHead['student_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="label">Class</div>
                        <div class="value"><?php echo htmlspecialchars((string)$studentHead['class_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="label">Session</div>
                        <div class="value"><?php echo htmlspecialchars((string)$studentHead['session_year'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="label">Student ID</div>
                        <div class="value"><?php echo htmlspecialchars((string)$studentHead['student_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="label">Group</div>
                        <div class="value"><?php echo htmlspecialchars((string)$studentGroupLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>

                <div class="section-title">Subject Marks Detail</div>
                <table class="table table-bordered marks-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Obtained</th>
                            <th>Total</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($cardRows)): foreach ($cardRows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)($row['subject_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['result_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int)$row['obtained_marks']; ?></td>
                            <td><?php echo (int)$row['total_marks']; ?></td>
                            <td><?php echo htmlspecialchars((string)$row['percentage'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['grade'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['approval_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center">No result rows found for selected filters.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <div class="summary-row">
                    <div class="summary-pill">Overall Obtained: <?php echo htmlspecialchars((string)round($totalObtained, 2), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="summary-pill">Overall Total: <?php echo htmlspecialchars((string)round($totalMarks, 2), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="summary-pill">Overall Percentage: <?php echo htmlspecialchars((string)$overallPercentage, ENT_QUOTES, 'UTF-8'); ?>%</div>
                </div>

                <div class="section-title">Teacher Remarks</div>
                <div class="remarks-box"><?php echo htmlspecialchars((string)$teacherRemarks, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="remarks-meta">
                    <strong>Teacher(s):</strong> <?php echo htmlspecialchars((string)$teacherListText, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($teacherRemarkDate !== ''): ?>
                        | <strong>Remark Date:</strong> <?php echo htmlspecialchars((string)$teacherRemarkDate, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>
                </div>

                <div class="digital-note">This is a digitally created result card by KORT School Management System.</div>
            <?php endif; ?>
        </div>
        <?php if ($showQr && $studentHead): ?>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
                new QRCode(document.getElementById('qrbox'), {
                    text: "<?php echo htmlspecialchars((string)$studentHead['student_code'], ENT_QUOTES, 'UTF-8'); ?>",
                    width: 72,
                    height: 72
                });
            </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

include './partials/topbar.php';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Results Center</h4>
        <a href="approvals.php" class="btn btn-outline-primary btn-sm">Go to Approval & Locking</a>
    </div>

    <form method="get" class="card mb-3">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-2">
                    <label>Class</label>
                    <select class="form-control" name="class_id">
                        <option value="0">All</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo (int)$class['id']; ?>" <?php echo ((int)$class['id'] === $filterClassId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Subject</label>
                    <select class="form-control" name="subject_id">
                        <option value="0">All</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo (int)$subject['id']; ?>" <?php echo ((int)$subject['id'] === $filterSubjectId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$subject['subject_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Teacher</label>
                    <select class="form-control" name="teacher_id">
                        <option value="0">All</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo (int)$teacher['id']; ?>" <?php echo ((int)$teacher['id'] === $filterTeacherId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$teacher['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Type</label>
                    <select class="form-control" name="result_type">
                        <option value="-1">All</option>
                        <?php foreach ($resultTypeLabelMap as $k => $label): ?>
                            <option value="<?php echo (int)$k; ?>" <?php echo ($filterResultType === (int)$k) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Group</label>
                    <select class="form-control" name="group_stream">
                        <option value="all" <?php echo ($filterGroup === 'all') ? 'selected' : ''; ?>>All</option>
                        <option value="general" <?php echo ($filterGroup === 'general') ? 'selected' : ''; ?>>General</option>
                        <option value="ics" <?php echo ($filterGroup === 'ics') ? 'selected' : ''; ?>>ICS</option>
                        <option value="pre_medical" <?php echo ($filterGroup === 'pre_medical') ? 'selected' : ''; ?>>Pre-Medical</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Date From</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filterDateFrom, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label>Date To</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filterDateTo, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <button class="btn btn-primary btn-sm" type="submit">Apply Filters</button>
            <a class="btn btn-light btn-sm" href="results_center.php">Reset</a>
            <a class="btn btn-success btn-sm" href="results_center.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'gazette_excel'])); ?>">Gazette Excel</a>
        </div>
    </form>

    <div class="row">
        <div class="col-md-2 mb-3"><div class="card"><div class="card-body"><div class="text-muted small">Rows</div><div class="h5"><?php echo (int)$summary['total_rows']; ?></div></div></div></div>
        <div class="col-md-2 mb-3"><div class="card"><div class="card-body"><div class="text-muted small">Students</div><div class="h5"><?php echo (int)$summary['students_count']; ?></div></div></div></div>
        <div class="col-md-2 mb-3"><div class="card"><div class="card-body"><div class="text-muted small">Batches</div><div class="h5"><?php echo (int)$summary['batches_count']; ?></div></div></div></div>
        <div class="col-md-2 mb-3"><div class="card"><div class="card-body"><div class="text-muted small">Average %</div><div class="h5"><?php echo htmlspecialchars((string)$summary['avg_percentage'], ENT_QUOTES, 'UTF-8'); ?></div></div></div></div>
        <div class="col-md-2 mb-3"><div class="card"><div class="card-body"><div class="text-muted small">Approved</div><div class="h5"><?php echo (int)$summary['approved_rows']; ?></div></div></div></div>
        <div class="col-md-2 mb-3"><div class="card"><div class="card-body"><div class="text-muted small">Pending</div><div class="h5"><?php echo (int)$summary['pending_rows']; ?></div></div></div></div>
    </div>

    <div class="row">
        <div class="col-md-8 mb-3">
            <div class="card">
                <div class="card-header">Trend Analytics (Average % by Date)</div>
                <div class="card-body"><canvas id="trendChart" height="100"></canvas></div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-header">Result Locking Snapshot</div>
                <div class="card-body">
                    <div>Locked groups: <strong><?php echo (int)$lockSummary['locked_groups']; ?></strong></div>
                    <div>Unlocked groups: <strong><?php echo (int)$lockSummary['unlocked_groups']; ?></strong></div>
                    <a href="approvals.php" class="btn btn-sm btn-outline-primary mt-2">Manage lock + approvals</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">Class Comparison</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Class</th><th>Avg %</th><th>Rows</th></tr></thead>
                        <tbody>
                        <?php if (!empty($classComparisonRows)): foreach ($classComparisonRows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$row['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['avg_percentage'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int)$row['row_count']; ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" class="text-center">No data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">Subject Comparison</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead><tr><th>Subject</th><th>Avg %</th><th>Rows</th></tr></thead>
                        <tbody>
                        <?php if (!empty($subjectComparisonRows)): foreach ($subjectComparisonRows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['avg_percentage'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int)$row['row_count']; ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" class="text-center">No data.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Top Positions</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Position</th><th>Student ID</th><th>Name</th><th>Class</th><th>Percentage</th></tr></thead>
                <tbody>
                <?php if (!empty($topPositions)): $position = 1; foreach ($topPositions as $row): ?>
                    <tr>
                        <td><?php echo $position++; ?></td>
                        <td><?php echo htmlspecialchars((string)$row['student_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['overall_percentage'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center">No data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Gazette (with Result Card links)</span>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print / Save PDF</button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Pos</th><th>Student ID</th><th>Name</th><th>Class</th><th>Group</th><th>Subjects & Marks</th><th>Obtained</th><th>Total</th><th>%</th><th>Status</th><th>Result Card</th></tr></thead>
                <tbody>
                <?php if (!empty($gazetteRows)): $position = 1; foreach ($gazetteRows as $row): ?>
                    <?php
                    $subjectsCount = (int)($row['subjects_count'] ?? 0);
                    $approvedRows = (int)($row['approved_rows'] ?? 0);
                    $pendingRows = (int)($row['pending_rows'] ?? 0);
                    $returnedRows = (int)($row['returned_rows'] ?? 0);
                    $approvalStatus = 'Pending';
                    $statusClass = 'badge-warning';
                    if ($returnedRows > 0) {
                        $approvalStatus = 'Returned';
                        $statusClass = 'badge-danger';
                    } elseif ($subjectsCount > 0 && $approvedRows === $subjectsCount) {
                        $approvalStatus = 'Approved';
                        $statusClass = 'badge-success';
                    } elseif ($approvedRows > 0 && $pendingRows > 0) {
                        $approvalStatus = 'Partially Approved';
                        $statusClass = 'badge-info';
                    }
                    ?>
                    <?php
                    $subjectMarkLines = array_values(array_filter(explode('||', (string)($row['subject_marks_list'] ?? ''))));
                    ?>
                    <tr>
                        <td><?php echo $position++; ?></td>
                        <td><?php echo htmlspecialchars((string)$row['student_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(formatGroupStreamLabel((string)($row['group_stream'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if (!empty($subjectMarkLines)): ?>
                                <div class="small mb-0">
                                    <?php foreach ($subjectMarkLines as $line): ?>
                                        <div><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <?php echo htmlspecialchars((string)($row['subject_names'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$row['total_obtained']; ?></td>
                        <td><?php echo (int)$row['total_marks']; ?></td>
                        <td><?php echo htmlspecialchars((string)$row['overall_percentage'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($approvalStatus, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="results_center.php?<?php echo http_build_query(array_merge($_GET, ['view' => 'card', 'student_id' => (int)$row['student_id'], 'qr' => 1])); ?>">Card / PDF</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="11" class="text-center">No results found for selected filters.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const trendLabels = <?php echo json_encode(array_column($trendRows, 'result_date')); ?>;
const trendValues = <?php echo json_encode(array_map(static function ($row) { return (float)($row['avg_percentage'] ?? 0); }, $trendRows)); ?>;
const trendCtx = document.getElementById('trendChart');
if (trendCtx) {
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Average Percentage',
                data: trendValues,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.15)',
                fill: true,
                tension: 0.25
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, suggestedMax: 100 } }
        }
    });
}
</script>
<?php include './partials/footer.php'; ?>
