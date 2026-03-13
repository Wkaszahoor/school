<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('medical_records', 'view', 'index.php');

include "db.php";
include "./partials/topbar.php";

$records = [];
$res = $conn->query("
    SELECT sr.sick_date, sr.days_off, sr.reason, sr.doctor_note, sr.referred_by, sr.approved_by, sr.approved_at,
           s.StudentId, s.student_name, s.class
    FROM sick_records sr
    JOIN students s ON s.id = sr.student_id
    WHERE sr.status = 'Approved'
    ORDER BY sr.sick_date DESC, sr.id DESC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $records[] = $row;
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Doctor Profile - Approved Sick Records</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Approved Records</h6>
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
                            <th>Doctor Note</th>
                            <th>Referred By</th>
                            <th>Approved By</th>
                            <th>Approved At</th>
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
                                    <td><?php echo htmlspecialchars((string)($row['approved_by'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['approved_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center">No approved records yet.</td></tr>
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
