<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('medical_records', 'view', 'index.php');

include "db.php";

$flashType = '';
$flashMessage = '';

$conn->query("
    CREATE TABLE IF NOT EXISTS sick_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        sick_date DATE NOT NULL,
        days_off INT NOT NULL DEFAULT 1,
        reason VARCHAR(255) NOT NULL,
        doctor_note TEXT NULL,
        referred_by VARCHAR(120) NOT NULL DEFAULT 'Principal',
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        approved_by VARCHAR(120) NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition): void
{
    $check = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
    ");
    if (!$check) {
        return;
    }
    $check->bind_param("ss", $table, $column);
    $check->execute();
    $exists = (int)($check->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $check->close();
    if (!$exists) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

ensureColumnExists($conn, 'sick_records', 'approved_by', "VARCHAR(120) NULL AFTER `status`");
ensureColumnExists($conn, 'sick_records', 'approved_at', "DATETIME NULL AFTER `approved_by`");
ensureColumnExists($conn, 'sick_records', 'doctor_prescription', "TEXT NULL AFTER `doctor_note`");
ensureColumnExists($conn, 'sick_records', 'doctor_suggestion', "TEXT NULL AFTER `doctor_prescription`");
ensureColumnExists($conn, 'sick_records', 'examined_by', "VARCHAR(120) NULL AFTER `doctor_suggestion`");
ensureColumnExists($conn, 'sick_records', 'examined_at', "DATETIME NULL AFTER `examined_by`");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sick'])) {
    auth_require_permission('medical_records', 'create', 'index.php');
    $studentId = (int)($_POST['student_id'] ?? 0);
    $sickDate = trim((string)($_POST['sick_date'] ?? ''));
    $daysOff = (int)($_POST['days_off'] ?? 1);
    $reason = trim((string)($_POST['reason'] ?? ''));
    $doctorNote = trim((string)($_POST['doctor_note'] ?? ''));
    $referredBy = trim((string)($_POST['referred_by'] ?? 'Principal'));
    $status = trim((string)($_POST['status'] ?? 'Pending'));

    if ($studentId <= 0 || $sickDate === '' || $reason === '') {
        $flashType = 'danger';
        $flashMessage = 'Student, date, and reason are required.';
    } else {
        if ($daysOff <= 0) {
            $daysOff = 1;
        }
        if ($referredBy === '') {
            $referredBy = 'Principal';
        }
        if (!in_array($status, ['Pending', 'Approved', 'Rejected'], true)) {
            $status = 'Pending';
        }

        $stmt = $conn->prepare("INSERT INTO sick_records (student_id, sick_date, days_off, reason, doctor_note, referred_by, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isissss", $studentId, $sickDate, $daysOff, $reason, $doctorNote, $referredBy, $status);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Sick record added.';
        } else {
            $flashType = 'danger';
            $flashMessage = 'Failed to save record.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    auth_require_permission('medical_records', 'approve', 'index.php');
    $recordId = (int)($_POST['record_id'] ?? 0);
    $newStatus = trim((string)($_POST['new_status'] ?? ''));
    if ($recordId > 0 && in_array($newStatus, ['Approved', 'Rejected'], true)) {
        $approvedBy = 'Principal';
        $stmt = $conn->prepare("UPDATE sick_records SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $newStatus, $approvedBy, $recordId);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = "Record $newStatus.";
        } else {
            $flashType = 'danger';
            $flashMessage = 'Failed to update status.';
        }
    }
}

$students = [];
$stmt = $conn->prepare("SELECT id, StudentId, student_name, class FROM students ORDER BY student_name ASC");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

$records = [];
$res = $conn->query("
    SELECT sr.id, sr.sick_date, sr.days_off, sr.reason, sr.referred_by, sr.status, sr.approved_by, sr.approved_at,
           s.StudentId, s.student_name, s.class
    FROM sick_records sr
    JOIN students s ON s.id = sr.student_id
    ORDER BY sr.sick_date DESC, sr.id DESC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $records[] = $row;
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Student Sick</h1>
    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Add Sick Record (Referred by Principal)</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Student</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Select student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo (int)$student['id']; ?>">
                                    <?php echo htmlspecialchars((string)$student['StudentId'], ENT_QUOTES, 'UTF-8'); ?> -
                                    <?php echo htmlspecialchars((string)$student['student_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo htmlspecialchars((string)$student['class'], ENT_QUOTES, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date</label>
                        <input type="date" name="sick_date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Days Off</label>
                        <input type="number" name="days_off" class="form-control" min="1" value="1">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Referred By</label>
                        <input type="text" name="referred_by" class="form-control" value="Principal" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Doctor Note (optional)</label>
                    <textarea name="doctor_note" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" name="add_sick" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Sick Records</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Referred By</th>
                            <th>Status</th>
                            <th>Approved By</th>
                            <th>Approved At</th>
                            <th style="width:160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$row['StudentId'], ENT_QUOTES, 'UTF-8'); ?> -
                                        <?php echo htmlspecialchars((string)$row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['sick_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int)$row['days_off']; ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['reason'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['referred_by'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['approved_by'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['approved_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="record_id" value="<?php echo (int)$row['id']; ?>">
                                                <input type="hidden" name="new_status" value="Approved">
                                                <button type="submit" name="update_status" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="record_id" value="<?php echo (int)$row['id']; ?>">
                                                <input type="hidden" name="new_status" value="Rejected">
                                                <button type="submit" name="update_status" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">No action</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No sick records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include "./partials/footer.php";
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
