<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('lesson_plans', 'view', '../index.php');
include '../db.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$principalUserId = (int)($_SESSION['auth_user_id'] ?? 0);

$conn->query("CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    week_start DATE NOT NULL,
    lesson_plan TEXT NOT NULL,
    work_plan TEXT NULL,
    approval_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    principal_comment TEXT NULL,
    reviewed_by VARCHAR(120) NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS inbox_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sender_role VARCHAR(20) NOT NULL,
    sender_id INT NOT NULL,
    recipient_role VARCHAR(20) NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(190) NOT NULL,
    message_body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inbox_recipient (recipient_role, recipient_id, is_read, id),
    INDEX idx_inbox_sender (sender_role, sender_id, id)
)");

$ensureColumn = function (string $column, string $definition) use ($conn): void {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'lesson_plans' AND column_name = ?");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('s', $column);
    $stmt->execute();
    $exists = ((int)($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0);
    $stmt->close();
    if (!$exists) {
        $conn->query("ALTER TABLE `lesson_plans` ADD COLUMN `$column` $definition");
    }
};
$ensureColumn('approval_status', "VARCHAR(20) NOT NULL DEFAULT 'Pending'");
$ensureColumn('principal_comment', "TEXT NULL");
$ensureColumn('reviewed_by', "VARCHAR(120) NULL");
$ensureColumn('reviewed_at', "DATETIME NULL");

$flashType = '';
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_lesson_plan'])) {
    auth_require_permission('lesson_plans', 'approve', '../index.php');
    $lessonPlanId = (int)($_POST['lesson_plan_id'] ?? 0);
    $reviewAction = (string)($_POST['review_action'] ?? '');
    $principalComment = trim((string)($_POST['principal_comment'] ?? ''));
    $reviewer = (string)($_SESSION['auth_name'] ?? 'Principal');
    $newStatus = 'Pending';
    if ($reviewAction === 'approve') {
        $newStatus = 'Approved';
    } elseif ($reviewAction === 'return') {
        $newStatus = 'Returned';
    } else {
        $newStatus = 'Pending';
    }

    if ($lessonPlanId > 0) {
        $stmt = $conn->prepare("
            UPDATE lesson_plans
            SET approval_status = ?, principal_comment = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('sssi', $newStatus, $principalComment, $reviewer, $lessonPlanId);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                auth_audit_log($conn, 'approve', 'lesson_plan', (string)$lessonPlanId, null, json_encode([
                    'status' => $newStatus,
                    'comment' => $principalComment
                ]));
                // Notify teacher about review decision.
                $fetchTeacher = $conn->prepare("SELECT teacher_id, week_start FROM lesson_plans WHERE id = ? LIMIT 1");
                if ($fetchTeacher) {
                    $fetchTeacher->bind_param('i', $lessonPlanId);
                    $fetchTeacher->execute();
                    $lpRow = $fetchTeacher->get_result()->fetch_assoc();
                    $fetchTeacher->close();
                    $targetTeacherId = (int)($lpRow['teacher_id'] ?? 0);
                    $weekStart = (string)($lpRow['week_start'] ?? '');
                    if ($targetTeacherId > 0) {
                        $subject = 'Lesson Plan Review: ' . $newStatus;
                        $body = 'Week: ' . $weekStart . '. Status: ' . $newStatus . '.';
                        if ($principalComment !== '') {
                            $body .= ' Comment: ' . $principalComment;
                        }
                        $msgStmt = $conn->prepare("
                            INSERT INTO inbox_messages (sender_role, sender_id, recipient_role, recipient_id, subject, message_body, is_read)
                            VALUES ('principal', ?, 'teacher', ?, ?, ?, 0)
                        ");
                        if ($msgStmt) {
                            $msgStmt->bind_param('iiss', $principalUserId, $targetTeacherId, $subject, $body);
                            $msgStmt->execute();
                            $msgStmt->close();
                        }
                    }
                }
                $flashType = 'success';
                $flashMessage = ($newStatus === 'Approved') ? 'Lesson plan approved.' : 'Lesson plan returned for edit.';
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to update lesson plan review.';
            }
        }
    }
}

$filterTeacher = (int)($_GET['teacher_id'] ?? 0);
$filterClass = (int)($_GET['class_id'] ?? 0);
$filterStatus = trim((string)($_GET['status'] ?? ''));

$teachers = [];
$teacherRes = $conn->query("SELECT id, name, email FROM teachers ORDER BY name ASC");
if ($teacherRes) {
    while ($row = $teacherRes->fetch_assoc()) {
        $teachers[] = $row;
    }
}

$classes = [];
$classRes = $conn->query("SELECT id, class FROM classes ORDER BY class ASC");
if ($classRes) {
    while ($row = $classRes->fetch_assoc()) {
        $classes[] = $row;
    }
}

$sql = "
    SELECT
        l.id,
        l.week_start,
        l.lesson_plan,
        l.work_plan,
        l.approval_status,
        l.principal_comment,
        l.reviewed_by,
        l.reviewed_at,
        l.created_at,
        t.id AS teacher_id,
        t.name AS teacher_name,
        c.id AS class_id,
        c.class AS class_name,
        s.subject_name
    FROM lesson_plans l
    LEFT JOIN teachers t ON t.id = l.teacher_id
    LEFT JOIN classes c ON c.id = l.class_id
    LEFT JOIN subjects s ON s.id = l.subject_id
    WHERE 1=1
";
$params = [];
$types = '';
if ($filterTeacher > 0) {
    $sql .= " AND l.teacher_id = ?";
    $params[] = $filterTeacher;
    $types .= 'i';
}
if ($filterClass > 0) {
    $sql .= " AND l.class_id = ?";
    $params[] = $filterClass;
    $types .= 'i';
}
if ($filterStatus !== '' && in_array($filterStatus, ['Pending', 'Approved', 'Returned'], true)) {
    $sql .= " AND l.approval_status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}
$sql .= " ORDER BY l.created_at DESC LIMIT 300";

$lessonPlans = [];
$stmtPlans = $conn->prepare($sql);
if ($stmtPlans) {
    if ($types !== '') {
        $stmtPlans->bind_param($types, ...$params);
    }
    $stmtPlans->execute();
    $res = $stmtPlans->get_result();
    while ($row = $res->fetch_assoc()) {
        $lessonPlans[] = $row;
    }
    $stmtPlans->close();
}

// Compliance tracking (current month).
$complianceRows = [];
$daysInMonth = (int)date('t');
$dayOfMonth = (int)date('j');
$expectedSubmissions = max(1, (int)ceil($dayOfMonth / 7));
$compSql = "
    SELECT
        t.id AS teacher_id,
        t.name AS teacher_name,
        COUNT(lp.id) AS total_submissions,
        SUM(CASE WHEN lp.approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved_submissions,
        SUM(CASE WHEN lp.approval_status = 'Returned' THEN 1 ELSE 0 END) AS returned_submissions,
        SUM(CASE WHEN lp.approval_status = 'Pending' THEN 1 ELSE 0 END) AS pending_submissions
    FROM teachers t
    LEFT JOIN lesson_plans lp
      ON lp.teacher_id = t.id
     AND YEAR(lp.week_start) = YEAR(CURDATE())
     AND MONTH(lp.week_start) = MONTH(CURDATE())
    GROUP BY t.id, t.name
    ORDER BY t.name ASC
";
$compRes = $conn->query($compSql);
if ($compRes) {
    while ($row = $compRes->fetch_assoc()) {
        $total = (int)($row['total_submissions'] ?? 0);
        $rate = min(100, round(($total / $expectedSubmissions) * 100, 1));
        $row['expected_submissions'] = $expectedSubmissions;
        $row['compliance_rate'] = $rate;
        $complianceRows[] = $row;
    }
}

include './partials/topbar.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Lesson Plan Reviews</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo h($flashType); ?>"><?php echo h($flashMessage); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Submission Compliance (Current Month)</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Expected</th>
                        <th>Submitted</th>
                        <th>Approved</th>
                        <th>Returned</th>
                        <th>Pending</th>
                        <th>Compliance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($complianceRows as $row): ?>
                        <tr>
                            <td><?php echo h($row['teacher_name'] ?? ''); ?></td>
                            <td><?php echo (int)$row['expected_submissions']; ?></td>
                            <td><?php echo (int)($row['total_submissions'] ?? 0); ?></td>
                            <td><?php echo (int)($row['approved_submissions'] ?? 0); ?></td>
                            <td><?php echo (int)($row['returned_submissions'] ?? 0); ?></td>
                            <td><?php echo (int)($row['pending_submissions'] ?? 0); ?></td>
                            <td>
                                <?php $rate = (float)($row['compliance_rate'] ?? 0); ?>
                                <span class="badge badge-<?php echo $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger'); ?>">
                                    <?php echo h(number_format($rate, 1)); ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($complianceRows)): ?>
                        <tr><td colspan="7" class="text-center">No teacher compliance data found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Principal Review Workflow</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-row">
                <div class="form-group col-md-4">
                    <label>Teacher</label>
                    <select name="teacher_id" class="form-control">
                        <option value="">All Teachers</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo (int)$t['id']; ?>" <?php echo $filterTeacher === (int)$t['id'] ? 'selected' : ''; ?>>
                                <?php echo h($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Class</label>
                    <select name="class_id" class="form-control">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>" <?php echo $filterClass === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?php echo h($c['class']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="Pending" <?php echo $filterStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $filterStatus === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Returned" <?php echo $filterStatus === 'Returned' ? 'selected' : ''; ?>>Returned</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-block">Apply Filter</button>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Week Start</th>
                        <th>Teacher</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Principal Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lessonPlans as $row): ?>
                        <tr>
                            <td><?php echo h($row['week_start']); ?></td>
                            <td><?php echo h($row['teacher_name'] ?? ''); ?></td>
                            <td><?php echo h($row['class_name'] ?? ''); ?></td>
                            <td><?php echo h($row['subject_name'] ?? ''); ?></td>
                            <td>
                                <div><strong>Lesson:</strong> <?php echo h($row['lesson_plan']); ?></div>
                                <div class="small text-muted"><strong>Work:</strong> <?php echo h($row['work_plan'] ?? ''); ?></div>
                            </td>
                            <td>
                                <?php
                                    $status = (string)($row['approval_status'] ?? 'Pending');
                                    $badge = 'secondary';
                                    if ($status === 'Approved') { $badge = 'success'; }
                                    elseif ($status === 'Returned') { $badge = 'warning'; }
                                ?>
                                <span class="badge badge-<?php echo h($badge); ?>"><?php echo h($status); ?></span>
                                <?php if (!empty($row['reviewed_by'])): ?>
                                    <div class="small text-muted">By: <?php echo h($row['reviewed_by']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="min-width: 280px;">
                                <form method="post">
                                    <input type="hidden" name="lesson_plan_id" value="<?php echo (int)$row['id']; ?>">
                                    <textarea name="principal_comment" class="form-control form-control-sm mb-2" rows="2" placeholder="Comment for teacher"><?php echo h($row['principal_comment'] ?? ''); ?></textarea>
                                    <div class="btn-group btn-group-sm">
                                        <button type="submit" name="review_lesson_plan" value="1" class="btn btn-success" onclick="this.form.review_action.value='approve';">Approve</button>
                                        <button type="submit" name="review_lesson_plan" value="1" class="btn btn-warning" onclick="this.form.review_action.value='return';">Return for Edit</button>
                                    </div>
                                    <input type="hidden" name="review_action" value="approve">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lessonPlans)): ?>
                        <tr><td colspan="7" class="text-center">No lesson plans found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include './partials/footer.php'; ?>
