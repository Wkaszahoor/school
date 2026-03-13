<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('attendance_reports', 'export', 'index.php');
include 'db.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

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
        s.id AS student_db_id,
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
$rows = [];
$totalPresent = 0;
$totalAbsent = 0;
$totalLeave = 0;
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
        $status = (string)($row['status'] ?? '');
        if ($status === 'P') {
            $totalPresent++;
        } elseif ($status === 'A') {
            $totalAbsent++;
        } elseif ($status === 'L') {
            $totalLeave++;
        }
    }
    $stmt->close();
}

$scopeLabel = 'All Classes';
if ($selectedClass !== '' && $selectedClass !== 'all_classes') {
    $scopeLabel = 'Class: ' . $selectedClass;
}
if ($selectedStudentId > 0) {
    $scopeLabel .= ' | Student DB ID: ' . $selectedStudentId;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance PDF Template</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        body { font-family: Arial, sans-serif; color: #111; margin: 0; }
        .page { border: 1px solid #ddd; padding: 12px; }
        .head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .title { font-size: 20px; font-weight: 700; }
        .meta { font-size: 12px; color: #444; line-height: 1.5; }
        .summary { display: flex; gap: 10px; margin: 10px 0 14px; }
        .chip { border: 1px solid #ccc; border-radius: 6px; padding: 8px 10px; font-size: 12px; min-width: 110px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f6f6f6; }
        .center { text-align: center; }
        .small { font-size: 11px; color: #555; }
        .no-print { margin-top: 12px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="page">
    <div class="head">
        <div>
            <div class="title">KORT Attendance Report</div>
            <div class="meta"><?php echo h($scopeLabel); ?></div>
            <div class="meta">Date: <?php echo h($selectedDate); ?></div>
        </div>
        <div class="small">Generated: <?php echo h(date('Y-m-d H:i')); ?></div>
    </div>

    <div class="summary">
        <div class="chip"><strong>Total Rows:</strong><br><?php echo (int)count($rows); ?></div>
        <div class="chip"><strong>Present:</strong><br><?php echo (int)$totalPresent; ?></div>
        <div class="chip"><strong>Absent:</strong><br><?php echo (int)$totalAbsent; ?></div>
        <div class="chip"><strong>Leave:</strong><br><?php echo (int)$totalLeave; ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Class</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th class="center">Status</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $row): ?>
                <tr>
                    <td><?php echo (int)($i + 1); ?></td>
                    <td><?php echo h($row['class_id'] ?? ''); ?></td>
                    <td><?php echo h($row['StudentId'] ?? ''); ?></td>
                    <td><?php echo h($row['student_name'] ?? ''); ?></td>
                    <td class="center"><?php echo h($row['status'] ?? ''); ?></td>
                    <td><?php echo h($row['teacher_email'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="center">No attendance records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="no-print">
        <button onclick="window.print()">Print / Save PDF</button>
    </div>
</div>
</body>
</html>

