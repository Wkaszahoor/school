<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('doctor_records', 'view', 'index.php');

include "../db.php";

$conn->query("\n    CREATE TABLE IF NOT EXISTS sick_records (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        student_id INT NOT NULL,\n        sick_date DATE NOT NULL,\n        days_off INT NOT NULL DEFAULT 1,\n        reason VARCHAR(255) NOT NULL,\n        doctor_note TEXT NULL,\n        referred_by VARCHAR(120) NOT NULL DEFAULT 'Principal',\n        status VARCHAR(20) NOT NULL DEFAULT 'Pending',\n        approved_by VARCHAR(120) NULL,\n        approved_at DATETIME NULL,\n        doctor_prescription TEXT NULL,\n        doctor_suggestion TEXT NULL,\n        examined_by VARCHAR(120) NULL,\n        examined_at DATETIME NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n    )\n");

function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition): void
{
    $check = $conn->prepare("\n        SELECT COUNT(*) AS c\n        FROM information_schema.columns\n        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?\n    ");
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

ensureColumnExists($conn, 'sick_records', 'doctor_prescription', "TEXT NULL AFTER `doctor_note`");
ensureColumnExists($conn, 'sick_records', 'doctor_suggestion', "TEXT NULL AFTER `doctor_prescription`");
ensureColumnExists($conn, 'sick_records', 'examined_by', "VARCHAR(120) NULL AFTER `doctor_suggestion`");
ensureColumnExists($conn, 'sick_records', 'examined_at', "DATETIME NULL AFTER `examined_by`");

$flashType = '';
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_exam'])) {
    auth_require_permission('doctor_records', 'examine', 'index.php');
    $recordId = (int)($_POST['record_id'] ?? 0);
    $prescription = trim((string)($_POST['doctor_prescription'] ?? ''));
    $suggestion = trim((string)($_POST['doctor_suggestion'] ?? ''));
    if ($recordId > 0) {
        $examinedBy = (string)($_SESSION['doctor_name'] ?? $_SESSION['doctor_email'] ?? 'Doctor');
        $stmt = $conn->prepare("UPDATE sick_records SET doctor_prescription = ?, doctor_suggestion = ?, examined_by = ?, examined_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssi", $prescription, $suggestion, $examinedBy, $recordId);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Examination saved.';
        }
    }
}

$records = [];
$res = $conn->query("\n    SELECT sr.id, sr.sick_date, sr.days_off, sr.reason, sr.doctor_note, sr.referred_by,\n           sr.approved_by, sr.approved_at, sr.doctor_prescription, sr.doctor_suggestion,\n           sr.examined_by, sr.examined_at,\n           s.StudentId, s.student_name, s.class\n    FROM sick_records sr\n    JOIN students s ON s.id = sr.student_id\n    WHERE sr.status = 'Approved'\n    ORDER BY sr.sick_date DESC, sr.id DESC\n");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $records[] = $row;
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Doctor Profile</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Approved Sick Records</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Date</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Doctor Note</th>
                            <th>Referred By</th>
                            <th>Approved At</th>
                            <th>Prescription</th>
                            <th>Suggestion</th>
                            <th>Action</th>
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
                                    <td><?php echo htmlspecialchars((string)($row['doctor_note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['referred_by'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['approved_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="record_id" value="<?php echo (int)$row['id']; ?>">
                                            <textarea name="doctor_prescription" class="form-control" rows="2"><?php echo htmlspecialchars((string)($row['doctor_prescription'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </td>
                                    <td>
                                            <textarea name="doctor_suggestion" class="form-control" rows="2"><?php echo htmlspecialchars((string)($row['doctor_suggestion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </td>
                                    <td>
                                            <button type="submit" name="save_exam" class="btn btn-primary btn-sm">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center">No approved records.</td></tr>
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
