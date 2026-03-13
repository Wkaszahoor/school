<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('results_reports', 'export', '../index.php');
include "../db.php";

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $exists = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $stmt->close();
    return $exists;
}

function normalizeGroupKey(string $value): string
{
    $raw = strtolower(trim($value));
    if ($raw === '') {
        return '';
    }
    $flat = str_replace([' ', '-', '_'], '', $raw);
    if ($flat === 'premedical') {
        return 'pre_medical';
    }
    if ($flat === 'ics') {
        return 'ics';
    }
    if ($flat === 'general') {
        return 'general';
    }
    $normalized = preg_replace('/[^a-z0-9]+/', '_', $raw);
    return trim((string)$normalized, '_');
}

function groupComparableKey(string $value): string
{
    return str_replace('_', '', normalizeGroupKey($value));
}

$teacher_id = $_SESSION['teacher_id'];
$format = strtolower(trim((string)($_GET['format'] ?? 'csv')));
if (!in_array($format, ['csv', 'excel', 'pdf'], true)) {
    $format = 'csv';
}
$filter_class_id = trim((string)($_GET['class_id'] ?? ''));
$filter_subject_id = trim((string)($_GET['subject_id'] ?? ''));
$filter_result_date = trim((string)($_GET['result_date'] ?? ''));
$filter_result_type = trim((string)($_GET['result_type'] ?? ''));
$filter_student_id = trim((string)($_GET['student_id'] ?? ''));
$filter_group_stream = normalizeGroupKey((string)($_GET['group_stream'] ?? ''));

$classTeacherClassIds = [];
$stmtClassTeacher = $conn->prepare("SELECT id FROM classes WHERE class_teacher_id = ?");
if ($stmtClassTeacher) {
    $stmtClassTeacher->bind_param("i", $teacher_id);
    $stmtClassTeacher->execute();
    $classTeacherRes = $stmtClassTeacher->get_result();
    while ($ct = $classTeacherRes->fetch_assoc()) {
        $classTeacherClassIds[] = (int)$ct['id'];
    }
    $stmtClassTeacher->close();
}

$hasResultsTopic = columnExists($conn, 'results', 'test_topic');
$hasStudentsSection = columnExists($conn, 'students', 'section');
$hasStudentsGroup = columnExists($conn, 'students', 'group_stream');
$hasStudentsYear = columnExists($conn, 'students', 'academic_year');
$hasClassesSection = columnExists($conn, 'classes', 'section');

$topicExpr = $hasResultsTopic ? "COALESCE(NULLIF(r.test_topic, ''), s.subject_name)" : "s.subject_name";
$sectionExprParts = [];
if ($hasStudentsSection) {
    $sectionExprParts[] = "NULLIF(st.section, '')";
}
if ($hasClassesSection) {
    $sectionExprParts[] = "NULLIF(c.section, '')";
}
$sectionExpr = !empty($sectionExprParts) ? ("COALESCE(" . implode(", ", $sectionExprParts) . ", '')") : "''";
$sessionExprParts = [];
if ($hasStudentsYear) {
    $sessionExprParts[] = "NULLIF(st.academic_year, '')";
}
$sessionExprParts[] = "NULLIF(c.academic_year, '')";
$sessionExpr = "COALESCE(" . implode(", ", $sessionExprParts) . ", '')";
$groupExpr = $hasStudentsGroup ? "COALESCE(NULLIF(st.group_stream, ''), '')" : "''";

$sql = "
    SELECT
        COALESCE(NULLIF(s.subject_name, ''), '-') AS subject_name,
        {$topicExpr} AS topic,
        r.result_type,
        r.result_date,
        r.total_marks,
        r.obtained_marks,
        c.class AS class_name,
        {$sectionExpr} AS class_section,
        COALESCE(NULLIF(st.student_name, ''), r.student_name) AS student_name,
        {$sessionExpr} AS session_year,
        {$groupExpr} AS group_stream
    FROM results r
    LEFT JOIN classes c ON r.class_id = c.id
    LEFT JOIN subjects s ON r.subject_id = s.id
    LEFT JOIN students st ON st.id = r.student_id
    WHERE (
        r.teacher_id = ?
";
$params = [$teacher_id];
$types = 'i';
if (!empty($classTeacherClassIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($classTeacherClassIds), '?'));
    $sql .= " OR r.class_id IN ($inPlaceholders)";
    foreach ($classTeacherClassIds as $ctClassId) {
        $params[] = $ctClassId;
        $types .= 'i';
    }
}
$sql .= ")";
if ($filter_class_id !== '' && ctype_digit($filter_class_id)) {
    $sql .= " AND r.class_id = ?";
    $params[] = (int)$filter_class_id;
    $types .= 'i';
}
if ($filter_subject_id !== '' && ctype_digit($filter_subject_id)) {
    $sql .= " AND r.subject_id = ?";
    $params[] = (int)$filter_subject_id;
    $types .= 'i';
}
if ($filter_result_date !== '') {
    $sql .= " AND r.result_date = ?";
    $params[] = $filter_result_date;
    $types .= 's';
}
if ($filter_result_type !== '') {
    $sql .= " AND r.result_type = ?";
    $params[] = (int)$filter_result_type;
    $types .= 'i';
}
if ($filter_student_id !== '' && ctype_digit($filter_student_id)) {
    $sql .= " AND r.student_id = ?";
    $params[] = (int)$filter_student_id;
    $types .= 'i';
}
if ($filter_group_stream !== '' && $hasStudentsGroup) {
    $sql .= " AND LOWER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(st.group_stream, '')), '-', ''), '_', ''), ' ', '')) = ?";
    $params[] = groupComparableKey($filter_group_stream);
    $types .= 's';
}
$sql .= " ORDER BY r.result_date DESC, c.class, topic, student_name";

$stmt_export = $conn->prepare($sql);

$rows = [];
$headers = ['Subject', 'Topic', 'Test Type', 'Date', 'Total Marks', 'Obtained Marks', 'Class', 'Section', 'Student Name', 'Session', 'Group'];
$resultTypeMap = [0 => 'Weekly', 1 => 'Monthly', 2 => 'Mid Term', 3 => 'Annual'];

if ($stmt_export === false) {
    $rows[] = ["Error: Could not retrieve data for export."];
} else {
    $stmt_export->bind_param($types, ...$params);
    $stmt_export->execute();
    $results_to_export = $stmt_export->get_result();

    if ($results_to_export->num_rows > 0) {
        while ($row = $results_to_export->fetch_assoc()) {
            $isWeekly = ((int)$row['result_type'] === 0);
            $rows[] = array(
                $row['subject_name'] !== '' ? $row['subject_name'] : '-',
                $isWeekly ? $row['topic'] : '-',
                $isWeekly ? ($resultTypeMap[(int)$row['result_type']] ?? 'Weekly') : '-',
                $row['result_date'],
                $row['total_marks'],
                $row['obtained_marks'],
                $row['class_name'],
                $row['class_section'] !== '' ? $row['class_section'] : '-',
                $row['student_name'],
                $row['session_year'] !== '' ? $row['session_year'] : '-',
                $row['group_stream'] !== '' ? $row['group_stream'] : '-'
            );
        }
    } else {
        $rows[] = ["No results found for export."];
    }
    $stmt_export->close();
}

$fileBase = 'teacher_results_' . date('Y-m-d_His');

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileBase . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fputcsv($output, $headers, "\t");
    foreach ($rows as $row) {
        fputcsv($output, $row, "\t");
    }
    fclose($output);
    $conn->close();
    exit();
}

if ($format === 'pdf') {
    $pdfEscape = static function (string $text): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    };

    $lines = [];
    $lines[] = 'KORT - Teacher Test Results';
    $lines[] = 'Generated: ' . date('Y-m-d H:i:s');
    $lines[] = str_repeat('-', 110);
    $lines[] = 'Subject | Topic | Type | Date | Total | Obtained | Class | Section | Student | Session | Group';
    $lines[] = str_repeat('-', 110);
    foreach ($rows as $r) {
        $line = implode(' | ', array_map(static function ($v): string {
            return trim((string)$v);
        }, $r));
        while (strlen($line) > 120) {
            $lines[] = substr($line, 0, 120);
            $line = substr($line, 120);
        }
        $lines[] = $line;
    }

    $linesPerPage = 50;
    $pages = array_chunk($lines, $linesPerPage);
    if (empty($pages)) {
        $pages = [['No results found.']];
    }

    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $kids = [];
    $objNum = 3;
    $pageObjNums = [];
    $contentObjNums = [];

    foreach ($pages as $pageLines) {
        $pageObjNums[] = $objNum++;
        $contentObjNums[] = $objNum++;
    }

    $kidsRefs = [];
    foreach ($pageObjNums as $n) {
        $kidsRefs[] = $n . " 0 R";
    }
    $objects[2] = "<< /Type /Pages /Kids [" . implode(' ', $kidsRefs) . "] /Count " . count($pageObjNums) . " >>";
    $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

    for ($i = 0; $i < count($pageObjNums); $i++) {
        $pObj = $pageObjNums[$i];
        $cObj = $contentObjNums[$i];
        $textParts = ["BT", "/F1 10 Tf", "12 TL", "36 800 Td"];
        foreach ($pages[$i] as $line) {
            $textParts[] = "(" . $pdfEscape($line) . ") Tj";
            $textParts[] = "T*";
        }
        $textParts[] = "ET";
        $stream = implode("\n", $textParts) . "\n";
        $objects[$cObj] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        $objects[$pObj] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents " . $cObj . " 0 R >>";
    }

    ksort($objects);
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $n => $content) {
        $offsets[$n] = strlen($pdf);
        $pdf .= $n . " 0 obj\n" . $content . "\nendobj\n";
    }
    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (max(array_keys($objects)) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= max(array_keys($objects)); $i++) {
        $off = $offsets[$i] ?? 0;
        $pdf .= sprintf("%010d 00000 n \n", $off);
    }
    $pdf .= "trailer\n<< /Size " . (max(array_keys($objects)) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileBase . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    $conn->close();
    exit();
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $fileBase . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');
$output = fopen('php://output', 'w');
fputcsv($output, $headers);
foreach ($rows as $row) {
    fputcsv($output, $row);
}
fclose($output);
$conn->close();
exit();
?>
