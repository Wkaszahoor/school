<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'view', '../index.php');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admission_helpers.php';

admission_ensure_students_table($conn);
admission_ensure_datesheet_table($conn);

function admission_card_term_name(mysqli $conn): string
{
    $fromQuery = trim((string)($_GET['term'] ?? ''));
    if ($fromQuery !== '') {
        return $fromQuery;
    }

    $exists = $conn->query("SHOW TABLES LIKE 'exam_terms'");
    if (!$exists || $exists->num_rows === 0) {
        return 'Annual';
    }

    $stmt = $conn->prepare("SELECT term_name FROM exam_terms ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        $term = trim((string)($row['term_name'] ?? ''));
        if ($term !== '') {
            return $term;
        }
    }

    return 'Annual';
}

function admission_card_session_name(array $student): string
{
    $session = trim((string)($student['academic_year'] ?? ''));
    if ($session !== '') {
        return $session;
    }

    $admissionDate = trim((string)($student['admission_date'] ?? ''));
    $year = (int)date('Y');
    if ($admissionDate !== '') {
        $ts = strtotime($admissionDate);
        if ($ts !== false) {
            $year = (int)date('Y', $ts);
        }
    }
    $nextShort = substr((string)($year + 1), -2);
    return $year . '-' . $nextShort;
}

function admission_card_room_display(string $className, array $datesheetRows): string
{
    $roomValues = [];
    foreach ($datesheetRows as $dr) {
        $room = trim((string)($dr['room_no'] ?? ''));
        if ($room !== '') {
            $roomValues[$room] = true;
        }
    }
    if ($roomValues) {
        return implode(', ', array_keys($roomValues));
    }

    // Dynamic assignment: classes 7 to 12 go to Hall by default.
    $candidates = admission_datesheet_class_candidates($className);
    foreach ($candidates as $c) {
        if (preg_match('/^\d+$/', (string)$c) === 1) {
            $n = (int)$c;
            if ($n >= 7 && $n <= 12) {
                return 'Hall';
            }
        }
    }

    return 'Classroom';
}

function admission_card_attendance_summary(mysqli $conn, string $className): array
{
    $out = [
        'days_marked' => 0,
        'present_count' => 0,
        'absent_count' => 0,
        'leave_count' => 0,
        'last_date' => '',
    ];

    if ($className === '') {
        return $out;
    }

    $candidates = admission_datesheet_class_candidates($className);
    if (!$candidates) {
        $candidates = [$className];
    }

    $ph = implode(',', array_fill(0, count($candidates), '?'));
    $types = str_repeat('s', count($candidates));
    $classIds = [];

    $classStmt = $conn->prepare("SELECT id FROM classes WHERE class IN ($ph)");
    if ($classStmt) {
        $classStmt->bind_param($types, ...$candidates);
        $classStmt->execute();
        $res = $classStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $classIds[] = (int)$row['id'];
        }
        $classStmt->close();
    }
    if (!$classIds) {
        return $out;
    }

    $idPh = implode(',', array_fill(0, count($classIds), '?'));
    $idTypes = str_repeat('i', count($classIds));
    $sql = "
        SELECT
            COUNT(DISTINCT attendance_date) AS days_marked,
            SUM(CASE WHEN status='P' THEN 1 ELSE 0 END) AS present_count,
            SUM(CASE WHEN status='A' THEN 1 ELSE 0 END) AS absent_count,
            SUM(CASE WHEN status='L' THEN 1 ELSE 0 END) AS leave_count,
            MAX(attendance_date) AS last_date
        FROM attendance
        WHERE class_id IN ($idPh)
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($idTypes, ...$classIds);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        $out['days_marked'] = (int)($row['days_marked'] ?? 0);
        $out['present_count'] = (int)($row['present_count'] ?? 0);
        $out['absent_count'] = (int)($row['absent_count'] ?? 0);
        $out['leave_count'] = (int)($row['leave_count'] ?? 0);
        $out['last_date'] = (string)($row['last_date'] ?? '');
    }

    return $out;
}


$id = (int)($_GET['id'] ?? 0);
$preview = (int)($_GET['preview'] ?? 0) === 1;
$showQr = ((int)($_GET['qr'] ?? 1) === 1);
$student = $id > 0 ? admission_find_student($conn, $id) : null;

if (!$student) {
    http_response_code(404);
    echo 'Student not found.';
    exit();
}

$admissionNo = admission_v($student, 'StudentId', 'admission_no');
$fullName = admission_v($student, 'student_name', 'full_name');
$className = admission_v($student, 'class');
$studentStream = (string)($student['group_stream'] ?? '');
$section = admission_v($student, 'section');
$rollNo = admission_v($student, 'roll_no');
if ($rollNo === '') {
    $rollNo = admission_v($student, 'roll');
}
if ($rollNo === '') {
    $rollNo = admission_v($student, 'roll_number');
}
if ($rollNo === '') {
    $rollNo = admission_v($student, 'rollNo');
}
$photo = admission_v($student, 'profile_image', 'photo');
$datesheetRows = admission_datesheet_for_student($conn, $className, $studentStream);
$termName = admission_card_term_name($conn);
$sessionName = admission_card_session_name($student);
$roomDisplay = admission_card_room_display($className, $datesheetRows);
$attendanceSummary = admission_card_attendance_summary($conn, $className);
$photoSrc = '';
if ($photo !== '') {
    if (preg_match('~^https?://~i', $photo) === 1 || str_starts_with($photo, '/')) {
        $photoSrc = $photo;
    } else {
        $photoSrc = '../' . ltrim($photo, '/');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admission Card - <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            --brand: #0d4f8b;
            --text: #1f2937;
            --muted: #6b7280;
            --line: #d1d5db;
            --paper: #ffffff;
            --bg: #f4f7fb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
            padding: 18px;
        }
        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .btn {
            border: 0;
            background: var(--brand);
            color: #fff;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            display: inline-block;
        }
        .btn.secondary { background: #6b7280; }
        .a4 {
            max-width: 1120px;
            margin: 0 auto;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        .a4 + .a4 {
            margin-top: 8px;
        }
        .header {
            display: grid;
            grid-template-columns: 90px 1fr;
            gap: 14px;
            align-items: center;
            border-bottom: 2px solid var(--line);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .logo {
            width: 90px;
            height: 90px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            object-fit: contain;
            background: #fff;
            padding: 8px;
        }
        .school-name {
            margin: 0;
            font-size: 28px;
            color: var(--brand);
            line-height: 1.1;
        }
        .school-address {
            margin: 4px 0 0;
            color: var(--muted);
        }
        .title {
            font-size: 24px;
            margin: 0;
            color: var(--brand);
            font-weight: 700;
            letter-spacing: 0.4px;
        }
        .title-row {
            display: block;
            margin: 14px 0 18px;
            text-align: center;
        }
        .term-line {
            text-align: center;
            margin-top: -10px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #374151;
        }
        .card-body {
            display: grid;
            grid-template-columns: 1fr 170px;
            gap: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 18px;
        }
        .field {
            border-bottom: 1px dashed var(--line);
            padding-bottom: 6px;
        }
        .label {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 3px;
        }
        .value {
            font-size: 15px;
            font-weight: 600;
        }
        .photo-wrap {
            display: flex;
            flex-direction: column;
            gap: 14px;
            align-items: center;
        }
        .photo {
            width: 150px;
            height: 170px;
            border: 1px solid var(--line);
            object-fit: cover;
            border-radius: 8px;
            background: #f8fafc;
            display: block;
        }
        .photo.placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 12px;
        }
        .qrcode {
            width: 110px;
            height: 110px;
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .qrcode img,
        .qrcode canvas {
            max-width: 100% !important;
            max-height: 100% !important;
            width: 100% !important;
            height: 100% !important;
            display: block;
        }
        .title-qrcode {
            display: none;
            width: 56px;
            height: 56px;
            padding: 3px;
        }
        .room-line {
            margin-top: 14px;
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #f8fafc;
            font-size: 14px;
        }
        .datesheet {
            margin-top: 16px;
        }
        .datesheet table {
            width: 100%;
            border-collapse: collapse;
        }
        .datesheet th, .datesheet td {
            border: 1px solid var(--line);
            padding: 6px;
            font-size: 12px;
            text-align: left;
        }
        .datesheet .sig-col {
            width: 180px;
        }
        .datesheet .sig-line {
            display: inline-block;
            width: 150px;
            border-bottom: 1px solid #9ca3af;
            height: 12px;
        }
        .datesheet th {
            background: #f9fafb;
            color: #111827;
        }
        .datesheet-title-row th {
            font-weight: 700;
            text-align: left;
        }
        .attendance-note {
            margin-top: 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            border-radius: 8px;
            padding: 6px 8px;
            font-size: 11px;
            text-align: center;
            font-weight: 600;
        }
        .digital-note {
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
            text-align: center;
            font-weight: 600;
        }
        .print-only {
            display: none;
        }
        @media (max-width: 820px) {
            .card-body {
                grid-template-columns: 1fr;
            }
            .grid {
                grid-template-columns: 1fr;
            }
        }
        @media print {
            body {
                background: #fff;
                padding: 0 !important;
                font-size: 14px;
            }
            .toolbar { display: none !important; }
            body {
                display: flex;
                flex-direction: column;
                gap: 2mm;
            }
            .a4 {
                width: 100%;
                max-width: 100%;
                height: 95mm;
                min-height: 95mm;
                max-height: 95mm;
                border: none;
                border-radius: 0;
                box-shadow: none;
                padding: 3mm 5mm;
                margin: 0;
                overflow: hidden;
            }
            .a4 + .a4 {
                margin-top: 2mm;
                border-top: 1px dashed #374151;
                padding-top: 2mm;
            }
            .title-row {
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin: 1px 0 3px;
                text-align: left;
            }
            .title { margin: 0; font-size: 14px; }
            .title-qrcode {
                display: flex !important;
                width: 42px;
                height: 42px;
                padding: 2px;
            }
            .school-name { font-size: 16px; }
            .school-address { margin-top: 1px; font-size: 10px; }
            .logo { width: 56px; height: 56px; }
            .header { margin-bottom: 8px; padding-bottom: 6px; }
            .term-line { margin-top: 0 !important; margin-bottom: 4px !important; font-size: 10px !important; }
            .field { padding-bottom: 1px; }
            .label { font-size: 9px; margin-bottom: 1px; }
            .value { font-size: 11px; line-height: 1.1; }
            .card-body { display: block; gap: 0; }
            .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 3px 8px; }
            .photo { width: 105px; height: 120px; }
            .qrcode { width: 72px; height: 72px; padding: 4px; }
            .photo { display: none !important; }
            .photo-wrap .qrcode { display: none !important; }
            .photo-wrap { display: none !important; }
            .qrcode { margin-left: auto; margin-right: 0; }
            .room-line { margin-top: 8px; padding: 5px 8px; font-size: 11px; }
            .room-line { margin-top: 2px; padding: 2px 4px; font-size: 8px; }
            .datesheet { margin-top: 2px; display: block !important; }
            .datesheet th, .datesheet td { padding: 1px 2px; font-size: 7px; line-height: 1.05; }
            .datesheet-title-row th { font-size: 8px; padding: 1px 2px; }
            .datesheet .sig-col { width: 70px; }
            .datesheet .sig-line { width: 58px; height: 5px; }
            .attendance-note { display: none !important; }
            .digital-note { display: none !important; }
            .print-only { display: block !important; }
            @page {
                size: A4 landscape;
                margin: 4mm;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <?php if ($preview): ?>
            <a href="all_students.php" class="btn secondary">Back to Students</a>
            <a href="edit_student.php?id=<?php echo (int)$student['id']; ?>" class="btn secondary">Edit Student</a>
        <?php endif; ?>
        <button class="btn" onclick="window.print()">Print Card</button>
        <a class="btn" href="student_documents.php?student_id=<?php echo (int)$id; ?>">Manage Documents</a>
    </div>

    <?php for ($copyIndex = 1; $copyIndex <= 2; $copyIndex++): ?>
    <div class="a4 <?php echo $copyIndex === 2 ? 'print-only' : ''; ?>">
        <div class="header">
            <img class="logo" src="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" alt="KORT Logo">
            <div>
                <h1 class="school-name">KORT School and College of Excellence</h1>
                <p class="school-address">Akthar ABad, Mirpur Azad Kashmir, Pakistan</p>
            </div>
        </div>

        <div class="title-row">
            <div class="title">STUDENT ADMISSION CARD</div>
            <?php if ($showQr): ?>
                <div class="qrcode title-qrcode" data-code="<?php echo htmlspecialchars($admissionNo, ENT_QUOTES, 'UTF-8'); ?>"></div>
            <?php endif; ?>
        </div>
        <div class="term-line">
            <strong>Term:</strong> <?php echo htmlspecialchars($termName, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="term-line" style="margin-top:-6px;">
            <strong>Class:</strong> <?php echo htmlspecialchars($className !== '' ? $className : '-', ENT_QUOTES, 'UTF-8'); ?> |
            <strong>Section:</strong> <?php echo htmlspecialchars($section !== '' ? $section : '-', ENT_QUOTES, 'UTF-8'); ?> |
            <strong>Roll No:</strong> <?php echo htmlspecialchars($rollNo !== '' ? $rollNo : '-', ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div class="card-body">
            <div class="grid">
                <div class="field"><span class="label">Admission No</span><span class="value"><?php echo htmlspecialchars($admissionNo, ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div class="field"><span class="label">Student Name</span><span class="value"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div class="field"><span class="label">Class</span><span class="value"><?php echo htmlspecialchars($className, ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div class="field"><span class="label">Section</span><span class="value"><?php echo htmlspecialchars($section, ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div class="field"><span class="label">Roll No</span><span class="value"><?php echo htmlspecialchars($rollNo !== '' ? $rollNo : '-', ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div class="field"><span class="label">Session</span><span class="value"><?php echo htmlspecialchars($sessionName, ENT_QUOTES, 'UTF-8'); ?></span></div>
                <div class="field"><span class="label">Type of Examination</span><span class="value">Final Term</span></div>
            </div>

            <div class="photo-wrap">
                <?php if ($showQr): ?>
                    <div class="qrcode" data-code="<?php echo htmlspecialchars($admissionNo, ENT_QUOTES, 'UTF-8'); ?>"></div>
                <?php endif; ?>
                <?php if ($photoSrc !== ''): ?>
                    <img class="photo" src="<?php echo htmlspecialchars($photoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Student Photo">
                <?php else: ?>
                    <div class="photo placeholder">No Photo</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="room-line"><strong>Room:</strong> <?php echo htmlspecialchars($roomDisplay !== '' ? $roomDisplay : 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="datesheet">
            <table>
                <thead>
                    <tr class="datesheet-title-row"><th colspan="3">Auto-filled Date Sheet (Class: <?php echo htmlspecialchars($className, ENT_QUOTES, 'UTF-8'); ?>)</th></tr>
                    <tr>
                        <th>Subject</th>
                        <th>Exam Date</th>
                        <th class="sig-col">Signature</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$datesheetRows): ?>
                    <tr><td colspan="3">No datesheet entries found for this class.</td></tr>
                <?php else: ?>
                    <?php foreach ($datesheetRows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['exam_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="sig-line"></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="attendance-note">
            Attendance: Days <?php echo (int)$attendanceSummary['days_marked']; ?> |
            P <?php echo (int)$attendanceSummary['present_count']; ?> |
            A <?php echo (int)$attendanceSummary['absent_count']; ?> |
            L <?php echo (int)$attendanceSummary['leave_count']; ?> |
            Last <?php echo htmlspecialchars($attendanceSummary['last_date'] !== '' ? $attendanceSummary['last_date'] : 'N/A', ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="digital-note">This is a digitally generated admission card by KORT School Management System.</div>
    </div>
    <?php endfor; ?>

    <?php if ($showQr): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
            document.querySelectorAll('.qrcode').forEach(function(el) {
                var size = Math.max(56, Math.min(el.clientWidth - 8, el.clientHeight - 8, 96));
                new QRCode(el, {
                    text: el.getAttribute('data-code') || '',
                    width: size,
                    height: size
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>
