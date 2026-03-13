<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'view', '../index.php');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admission_helpers.php';

admission_ensure_students_table($conn);
admission_ensure_datesheet_table($conn);

function class_wise_session_name(array $student): string
{
    $session = trim((string)($student['academic_year'] ?? ''));
    if ($session !== '') {
        return $session;
    }

    $admissionDate = trim((string)($student['admission_date'] ?? ''));
    if ($admissionDate === '') {
        $admissionDate = trim((string)($student['join_date_kort'] ?? ''));
    }

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

$classFilter = trim((string)($_GET['class'] ?? ''));

$classes = [];
$cRes = $conn->query("SELECT DISTINCT class FROM students WHERE IFNULL(class,'') <> '' ORDER BY class ASC");
while ($cRes && $r = $cRes->fetch_assoc()) {
    $classes[] = (string)$r['class'];
}

$students = [];
if ($classFilter !== '') {
    $stmt = $conn->prepare("
        SELECT id, StudentId, admission_no, student_name, full_name, guardian_name, father_name, class, section, roll_no, dob, guardian_contact, mobile, blood_group, profile_image, photo, admission_date, join_date_kort, academic_year, group_stream
        FROM students
        WHERE class = ?
        ORDER BY student_name ASC, id ASC
    ");
    if ($stmt) {
        $stmt->bind_param('s', $classFilter);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }
}

$datesheetRowsBase = admission_datesheet_for_class($conn, $classFilter);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Class Wise Admission Cards</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 16px; background: #f4f7fb; }
        .toolbar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; align-items: end; }
        .btn { border: 0; background: #0d4f8b; color: #fff; text-decoration: none; padding: 10px 14px; border-radius: 6px; font-size: 14px; cursor: pointer; display: inline-block; }
        .btn.gray { background: #6b7280; }
        .card-wrap { max-width: 1000px; margin: 0 auto; }
        .admission-card {
            background: #fff; border: 1px solid #d1d5db; border-radius: 8px; margin-bottom: 14px; padding: 16px;
            page-break-inside: avoid;
        }
        .head { display: flex; justify-content: space-between; gap: 10px; border-bottom: 1px solid #d1d5db; margin-bottom: 10px; padding-bottom: 8px; }
        .name { font-size: 18px; font-weight: 700; color: #0d4f8b; }
        .small { font-size: 13px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; font-size: 12px; text-align: left; }
        th { background: #f8fafc; }
        .no-print { margin-bottom: 8px; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .box { border: 1px solid #e5e7eb; padding: 8px; border-radius: 6px; background: #fcfdff; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .admission-card { border: none; margin: 0 0 10mm 0; border-radius: 0; }
            @page { size: A4 portrait; margin: 8mm; }
        }
    </style>
</head>
<body>
<div class="card-wrap">
    <div class="toolbar no-print">
        <a href="all_students.php" class="btn gray">Back to Students</a>
        <a href="datesheet.php" class="btn gray">Manage Datesheet</a>
        <form method="get" style="display:flex;gap:8px;align-items:end;">
            <div>
                <label class="small">Class</label>
                <input list="class_list" name="class" value="<?php echo htmlspecialchars($classFilter, ENT_QUOTES, 'UTF-8'); ?>" style="height:38px;padding:0 8px;">
                <datalist id="class_list">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <button type="submit" class="btn">Load Class</button>
            <?php if ($classFilter !== ''): ?>
                <button type="button" class="btn" onclick="window.print()">Print Class Cards</button>
                <button type="button" class="btn gray" onclick="saveCardsPdf()">Save as PDF</button>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($classFilter === ''): ?>
        <div class="admission-card">Select a class to preview and print cards class-wise.</div>
    <?php elseif (!$students): ?>
        <div class="admission-card">No students found in class <?php echo htmlspecialchars($classFilter, ENT_QUOTES, 'UTF-8'); ?>.</div>
    <?php else: ?>
        <?php foreach ($students as $st): ?>
            <?php
            $admissionNo = admission_v($st, 'StudentId', 'admission_no');
            $fullName = admission_v($st, 'student_name', 'full_name');
            $session = class_wise_session_name($st);
            $streamKey = (string)($st['group_stream'] ?? '');
            $datesheetRows = admission_datesheet_filter_rows($datesheetRowsBase, $classFilter, $streamKey);
            ?>
            <div class="admission-card">
                <div class="head">
                    <div>
                        <div class="name">KORT School - Student Admission Card</div>
                        <div class="small">Class: <?php echo htmlspecialchars(admission_v($st, 'class'), ENT_QUOTES, 'UTF-8'); ?> | Section: <?php echo htmlspecialchars(admission_v($st, 'section'), ENT_QUOTES, 'UTF-8'); ?> | Roll No: <?php echo htmlspecialchars(admission_v($st, 'roll_no'), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="small">Admission No: <?php echo htmlspecialchars($admissionNo, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="grid">
                    <div class="box"><strong>Name:</strong> <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="box"><strong>Session:</strong> <?php echo htmlspecialchars($session, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="box"><strong>Reg No:</strong> <?php echo htmlspecialchars($admissionNo, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th colspan="5">Auto-filled Date Sheet (Class: <?php echo htmlspecialchars($classFilter, ENT_QUOTES, 'UTF-8'); ?>)</th>
                        </tr>
                        <tr>
                            <th>Subject</th>
                            <th>Exam Date</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$datesheetRows): ?>
                        <tr><td colspan="5">No datesheet found for this class.</td></tr>
                    <?php else: ?>
                        <?php foreach ($datesheetRows as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$d['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$d['exam_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($d['exam_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($d['room_no'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>&nbsp;</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportCardsPdfNow() {
    var source = document.querySelector('.card-wrap');
    if (!source) {
        return;
    }
    var printArea = source.cloneNode(true);
    var toolbar = printArea.querySelector('.no-print');
    if (toolbar) {
        toolbar.remove();
    }
    var fileName = 'class-wise-cards-<?php echo htmlspecialchars($classFilter !== '' ? preg_replace('/[^A-Za-z0-9_-]+/', '-', $classFilter) : 'all', ENT_QUOTES, 'UTF-8'); ?>.pdf';
    html2pdf()
        .set({
            margin: [8, 8, 8, 8],
            filename: fileName,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['css', 'legacy'] }
        })
        .from(printArea)
        .save();
}

function saveCardsPdf() {
    if (typeof html2pdf !== 'undefined') {
        exportCardsPdfNow();
        return;
    }

    var fallbackScript = document.createElement('script');
    fallbackScript.src = 'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js';
    fallbackScript.onload = function () {
        if (typeof html2pdf !== 'undefined') {
            exportCardsPdfNow();
        } else {
            alert('PDF export library could not be loaded.');
        }
    };
    fallbackScript.onerror = function () {
        alert('PDF export library could not be loaded.');
    };
    document.head.appendChild(fallbackScript);
}
</script>
</body>
</html>
