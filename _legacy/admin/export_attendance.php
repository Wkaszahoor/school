<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('attendance_reports', 'export', 'index.php');
include 'db.php';

$selectedClass = (string)($_GET['class'] ?? '');
$selectedDate = (string)($_GET['date'] ?? '');
$selectedStudentId = (int)($_GET['student_id'] ?? 0);

if ($selectedDate === '') {
    $selectedDate = date('Y-m-d');
}

$sql = "
    SELECT
        a.class_id,
        a.attendance_date,
        s.id AS student_id,
        s.StudentId,
        s.student_name,
        a.status,
        a.teacher_email
    FROM attendance a
    JOIN students s ON s.id = a.student_id
    WHERE a.attendance_date = ?
";
$params = [$selectedDate];
$types = 's';

if ($selectedClass !== '' && $selectedClass !== 'all_classes') {
    $sql .= " AND a.class_id = ?";
    $params[] = $selectedClass;
    $types .= 's';
}
if ($selectedStudentId > 0) {
    $sql .= " AND s.id = ?";
    $params[] = $selectedStudentId;
    $types .= 'i';
}
$sql .= " ORDER BY a.class_id ASC, s.student_name ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo 'Failed to prepare export query.';
    exit();
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$filename = 'attendance_' . $selectedDate;
if ($selectedClass !== '' && $selectedClass !== 'all_classes') {
    $filename .= '_class_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $selectedClass);
}
if ($selectedStudentId > 0) {
    $filename .= '_student_' . $selectedStudentId;
}
$filename .= '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Class', 'Date', 'Student DB ID', 'Student ID', 'Student Name', 'Status', 'Recorded By']);
while ($row = $res->fetch_assoc()) {
    fputcsv($out, [
        (string)($row['class_id'] ?? ''),
        (string)($row['attendance_date'] ?? ''),
        (string)($row['student_id'] ?? ''),
        (string)($row['StudentId'] ?? ''),
        (string)($row['student_name'] ?? ''),
        (string)($row['status'] ?? ''),
        (string)($row['teacher_email'] ?? '')
    ]);
}
fclose($out);
$stmt->close();
exit();

