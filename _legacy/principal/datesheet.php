<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'edit', '../index.php');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admission_helpers.php';
include './partials/topbar.php';

admission_ensure_students_table($conn);
admission_ensure_datesheet_table($conn);

$flashType = '';
$flashMsg = '';
$editId = (int)($_GET['edit_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_excel'])) {
        $replaceExisting = isset($_POST['replace_existing']) && (string)$_POST['replace_existing'] === '1';
        $serverPath = trim((string)($_POST['server_file_path'] ?? ''));
        $importPath = '';

        if (isset($_FILES['datesheet_file']) && (int)($_FILES['datesheet_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmp = (string)($_FILES['datesheet_file']['tmp_name'] ?? '');
            $name = (string)($_FILES['datesheet_file']['name'] ?? '');
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== 'xlsx') {
                $flashType = 'danger';
                $flashMsg = 'Only .xlsx files are supported for import.';
            } elseif (!is_uploaded_file($tmp)) {
                $flashType = 'danger';
                $flashMsg = 'Invalid uploaded file.';
            } else {
                $tempDir = __DIR__ . '/../uploads/tmp';
                if (!is_dir($tempDir)) {
                    @mkdir($tempDir, 0775, true);
                }
                $importPath = $tempDir . '/datesheet_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.xlsx';
                if (!move_uploaded_file($tmp, $importPath)) {
                    $flashType = 'danger';
                    $flashMsg = 'Failed to store uploaded file.';
                    $importPath = '';
                }
            }
        } elseif ($serverPath !== '') {
            $importPath = $serverPath;
        } else {
            $flashType = 'danger';
            $flashMsg = 'Choose an Excel file or provide server file path.';
        }

        if ($importPath !== '' && $flashMsg === '') {
            $importRes = admission_import_datesheet_from_xlsx($conn, $importPath, $replaceExisting);
            if (($importRes['ok'] ?? false) === true) {
                $flashType = 'success';
                $flashMsg = 'Imported rows: ' . (int)$importRes['inserted'] . ', Deleted old rows: ' . (int)$importRes['deleted']
                    . ', Classes: ' . htmlspecialchars(implode(', ', (array)$importRes['classes']), ENT_QUOTES, 'UTF-8');
            } else {
                $flashType = 'danger';
                $flashMsg = (string)($importRes['message'] ?? 'Import failed.');
            }
            // Remove temp uploaded file.
            if (str_contains(str_replace('\\', '/', $importPath), '/uploads/tmp/') && is_file($importPath)) {
                @unlink($importPath);
            }
        }
    } elseif (isset($_POST['delete_row'])) {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM student_datesheets WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMsg = 'Datesheet row deleted.';
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $className = trim((string)($_POST['class_name'] ?? ''));
        $subject = trim((string)($_POST['subject_name'] ?? ''));
        $examDate = trim((string)($_POST['exam_date'] ?? ''));
        $examTime = trim((string)($_POST['exam_time'] ?? ''));
        $roomNo = trim((string)($_POST['room_no'] ?? ''));
        $totalMarks = trim((string)($_POST['total_marks'] ?? ''));

        if ($className === '' || $subject === '' || $examDate === '') {
            $flashType = 'danger';
            $flashMsg = 'Class, subject and exam date are required.';
        } elseif ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE student_datesheets
                SET class_name = ?, subject_name = ?, exam_date = ?, exam_time = ?, room_no = ?, total_marks = ?
                WHERE id = ?
            ");
            if ($stmt) {
                $stmt->bind_param('ssssssi', $className, $subject, $examDate, $examTime, $roomNo, $totalMarks, $id);
                $stmt->execute();
                $stmt->close();
                $flashType = 'success';
                $flashMsg = 'Datesheet row updated.';
            }
        } else {
            $stmt = $conn->prepare("
                INSERT INTO student_datesheets (class_name, subject_name, exam_date, exam_time, room_no, total_marks)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param('ssssss', $className, $subject, $examDate, $examTime, $roomNo, $totalMarks);
                $stmt->execute();
                $stmt->close();
                $flashType = 'success';
                $flashMsg = 'Datesheet row added.';
            }
        }
    }
}

$classes = [];
$cRes = $conn->query("SELECT DISTINCT class FROM students WHERE IFNULL(class,'') <> '' ORDER BY class ASC");
while ($cRes && $r = $cRes->fetch_assoc()) {
    $classes[] = (string)$r['class'];
}

$rows = [];
$res = $conn->query("SELECT id, class_name, subject_name, exam_date, exam_time, room_no, total_marks FROM student_datesheets ORDER BY class_name, exam_date, subject_name");
while ($res && $row = $res->fetch_assoc()) {
    $rows[] = $row;
}

$editRow = null;
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT id, class_name, subject_name, exam_date, exam_time, room_no, total_marks FROM student_datesheets WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $editRow = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
}
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Datesheet Module</h1>
        <a href="all_students.php" class="btn btn-secondary btn-sm">Back to Students</a>
    </div>

    <?php if ($flashMsg !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Import Datesheet From Excel (.xlsx)</h6>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="form-row">
                <div class="form-group col-md-4">
                    <label>Upload Excel File</label>
                    <input type="file" name="datesheet_file" class="form-control-file" accept=".xlsx">
                </div>
                <div class="form-group col-md-5">
                    <label>Or Server File Path</label>
                    <input type="text" name="server_file_path" class="form-control" placeholder="C:\Users\waqas\OneDrive\Desktop\Annual & Pre-Board Exam 2025-26.xlsx">
                </div>
                <div class="form-group col-md-2">
                    <label>Replace Existing</label>
                    <select name="replace_existing" class="form-control">
                        <option value="1" selected>Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <button type="submit" name="import_excel" class="btn btn-success btn-block">Import</button>
                </div>
            </form>
            <div class="small text-muted">
                Expected format: row with Date and class columns (e.g. class 2,3&4 / class 5 / class 6th&7th / class 8th / class 9th / class 10th).
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo $editRow ? 'Edit Datesheet Row' : 'Add Datesheet Row'; ?></h6>
        </div>
        <div class="card-body">
            <form method="post" class="form-row">
                <input type="hidden" name="id" value="<?php echo (int)($editRow['id'] ?? 0); ?>">
                <div class="form-group col-md-2">
                    <label>Class *</label>
                    <input list="class_list" name="class_name" class="form-control" required value="<?php echo htmlspecialchars((string)($editRow['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <datalist id="class_list">
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group col-md-3">
                    <label>Subject *</label>
                    <input type="text" name="subject_name" class="form-control" required value="<?php echo htmlspecialchars((string)($editRow['subject_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label>Exam Date *</label>
                    <input type="date" name="exam_date" class="form-control" required value="<?php echo htmlspecialchars((string)($editRow['exam_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label>Exam Time</label>
                    <input type="text" name="exam_time" class="form-control" placeholder="09:00 AM - 12:00 PM" value="<?php echo htmlspecialchars((string)($editRow['exam_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-1">
                    <label>Room</label>
                    <input type="text" name="room_no" class="form-control" value="<?php echo htmlspecialchars((string)($editRow['room_no'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-1">
                    <label>Marks</label>
                    <input type="text" name="total_marks" class="form-control" value="<?php echo htmlspecialchars((string)($editRow['total_marks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block"><?php echo $editRow ? 'Update' : 'Add'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Datesheet Rows</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Marks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="7" class="text-center">No datesheet entries added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$row['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['exam_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['exam_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['room_no'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($row['total_marks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-nowrap">
                                <a href="datesheet.php?edit_id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this row?');">
                                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                    <button type="submit" name="delete_row" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
