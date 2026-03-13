<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'view', '../index.php');
require __DIR__ . '/../db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = mysqli_connect('localhost', 'root', 'mysql', 'db_school_kort');
    if (!$conn) {
        die('Database connection failed in certificate.php: ' . mysqli_connect_error());
    }
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$studentId = (int)($_GET['student_id'] ?? 0);
$type = strtolower(trim((string)($_GET['type'] ?? 'bonafide')));
$showQr = ((int)($_GET['qr'] ?? 1) === 1);

$allowedTypes = ['bonafide', 'leaving', 'character'];
if (!in_array($type, $allowedTypes, true)) {
    $type = 'bonafide';
}

$student = null;
if ($studentId > 0) {
    $stmt = $conn->prepare("
        SELECT id, StudentId, admission_no, student_name, full_name, father_name, guardian_name, class, section, roll_no, dob, admission_date
        FROM students
        WHERE id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
}

if (!$student) {
    http_response_code(404);
    echo 'Student not found.';
    exit();
}

$studentCode = trim((string)($student['StudentId'] ?? ''));
if ($studentCode === '') {
    $studentCode = trim((string)($student['admission_no'] ?? ''));
}
$name = trim((string)($student['student_name'] ?? $student['full_name'] ?? ''));
$father = trim((string)($student['father_name'] ?? $student['guardian_name'] ?? ''));
$className = trim((string)($student['class'] ?? ''));
$section = trim((string)($student['section'] ?? ''));
$rollNo = trim((string)($student['roll_no'] ?? ''));
$dob = trim((string)($student['dob'] ?? ''));
$admissionDate = trim((string)($student['admission_date'] ?? ''));
$today = date('Y-m-d');

$titleMap = [
    'bonafide' => 'Bonafide Certificate',
    'leaving' => 'School Leaving Certificate',
    'character' => 'Character Certificate',
];
$title = $titleMap[$type];

$body = '';
if ($type === 'bonafide') {
    $body = "This is to certify that <strong>" . h($name) . "</strong> (Student ID: <strong>" . h($studentCode) . "</strong>) is a bona fide student of KORT School & College of Excellence, studying in class <strong>" . h($className) . "</strong> section <strong>" . h($section) . "</strong>. This certificate is issued on request for official use.";
} elseif ($type === 'leaving') {
    $body = "This is to certify that <strong>" . h($name) . "</strong> S/O, D/O <strong>" . h($father) . "</strong> bearing Student ID <strong>" . h($studentCode) . "</strong>, class <strong>" . h($className) . "</strong>, has left the school. The leaving certificate is issued on request.";
} else {
    $body = "This is to certify that <strong>" . h($name) . "</strong> (Student ID: <strong>" . h($studentCode) . "</strong>) has remained a student of this institution and, to the best of our knowledge, has maintained satisfactory conduct during the period of study.";
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($title); ?></title>
    <style>
        @page { size: A4 portrait; margin: 16mm; }
        body { margin: 0; background: #f4f7fb; font-family: Arial, sans-serif; color: #1f2937; }
        .toolbar { margin: 14px auto; max-width: 900px; display: flex; gap: 8px; }
        .btn { border: 0; background: #0d4f8b; color: #fff; padding: 10px 14px; border-radius: 6px; text-decoration: none; }
        .btn.secondary { background: #6b7280; }
        .sheet { max-width: 900px; margin: 0 auto 16px; background: #fff; border: 1px solid #d1d5db; border-radius: 10px; padding: 22px; box-shadow: 0 10px 24px rgba(0,0,0,.08); }
        .head { display: grid; grid-template-columns: 90px 1fr 90px; gap: 10px; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 12px; }
        .logo { width: 84px; height: 84px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px; background: #fff; }
        .school { text-align: center; }
        .school h1 { margin: 0; font-size: 28px; color: #0d4f8b; }
        .school p { margin: 4px 0 0; color: #4b5563; }
        .qrbox { width: 84px; height: 84px; border: 1px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .title { text-align: center; margin: 14px 0 20px; font-size: 24px; color: #0d4f8b; font-weight: 700; }
        .meta { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 10px; margin-bottom: 16px; }
        .meta .item { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px; background: #f8fafc; }
        .item .k { font-size: 12px; color: #6b7280; }
        .item .v { font-size: 14px; font-weight: 700; margin-top: 3px; }
        .body { font-size: 16px; line-height: 1.7; min-height: 200px; }
        .footer { margin-top: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .sign { border-top: 1px solid #111827; padding-top: 6px; text-align: center; font-size: 13px; min-height: 45px; }
        .small { color: #6b7280; font-size: 12px; margin-top: 10px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet { margin: 0; border: none; border-radius: 0; box-shadow: none; max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn secondary" href="document_center.php">Back</a>
        <button class="btn" onclick="window.print()">Print / Save PDF</button>
    </div>
    <div class="sheet">
        <div class="head">
            <img class="logo" src="../uploads/logo.png" alt="Logo">
            <div class="school">
                <h1>KORT School & College of Excellence</h1>
                <p>Akthar ABad, Mirpur Azad Kashmir, Pakistan</p>
            </div>
            <div class="qrbox <?php echo $showQr ? '' : 'd-none'; ?>" id="qr"></div>
        </div>
        <div class="title"><?php echo h($title); ?></div>

        <div class="meta">
            <div class="item"><div class="k">Student Name</div><div class="v"><?php echo h($name); ?></div></div>
            <div class="item"><div class="k">Student ID</div><div class="v"><?php echo h($studentCode); ?></div></div>
            <div class="item"><div class="k">Class / Section</div><div class="v"><?php echo h($className . ' / ' . $section); ?></div></div>
            <div class="item"><div class="k">Father / Guardian</div><div class="v"><?php echo h($father); ?></div></div>
            <div class="item"><div class="k">Roll No</div><div class="v"><?php echo h($rollNo); ?></div></div>
            <div class="item"><div class="k">Issue Date</div><div class="v"><?php echo h($today); ?></div></div>
        </div>

        <div class="body">
            <?php echo $body; ?>
            <?php if ($type === 'leaving'): ?>
                <p>Date of Birth: <strong><?php echo h($dob); ?></strong> | Admission Date: <strong><?php echo h($admissionDate); ?></strong></p>
            <?php endif; ?>
        </div>

        <div class="footer">
            <div class="sign">Prepared By</div>
            <div class="sign">Principal</div>
        </div>
        <div class="small">This is a digitally generated certificate by KORT School Management System.</div>
    </div>

    <?php if ($showQr): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
            new QRCode(document.getElementById('qr'), {
                text: "<?php echo h($type . '|' . $studentCode . '|' . $name . '|' . $today); ?>",
                width: 72,
                height: 72
            });
        </script>
    <?php endif; ?>
</body>
</html>
