<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('leave_requests', 'view', 'index.php');
include '../db.php';
include './partials/topbar.php';

$flashType = '';
$flashMessage = '';

$conn->query("
    CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_type VARCHAR(20) NOT NULL,
        teacher_id INT NULL,
        student_id INT NULL,
        class_id INT NULL,
        from_date DATE NOT NULL,
        to_date DATE NOT NULL,
        reason VARCHAR(255) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        requested_by_role VARCHAR(40) NULL,
        requested_by_id INT NULL,
        approved_by VARCHAR(120) NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS principal_leave_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        principal_user_id INT NOT NULL,
        leave_request_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_principal_leave_read (principal_user_id, leave_request_id)
    )
");
$principalUserId = (int)($_SESSION['auth_user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_leave_request'])) {
    auth_require_permission('leave_requests', 'create', 'index.php');
    $requestType = trim((string)($_POST['request_type'] ?? ''));
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $studentId = (int)($_POST['student_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $fromDate = trim((string)($_POST['from_date'] ?? ''));
    $toDate = trim((string)($_POST['to_date'] ?? ''));
    $reason = trim((string)($_POST['reason'] ?? ''));

    if (!in_array($requestType, ['teacher', 'student'], true) || $fromDate === '' || $toDate === '') {
        $flashType = 'danger';
        $flashMessage = 'Please provide valid request type and dates.';
    } elseif ($requestType === 'teacher' && $teacherId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Please select teacher.';
    } elseif ($requestType === 'student' && ($studentId <= 0 || $classId <= 0)) {
        $flashType = 'danger';
        $flashMessage = 'Please select class and student.';
    } else {
        $requestedByRole = (string)($_SESSION['auth_role'] ?? '');
        $requestedById = (int)($_SESSION['auth_user_id'] ?? 0);
        $stmt = $conn->prepare("
            INSERT INTO leave_requests
            (request_type, teacher_id, student_id, class_id, from_date, to_date, reason, status, requested_by_role, requested_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('siiissssi', $requestType, $teacherId, $studentId, $classId, $fromDate, $toDate, $reason, $requestedByRole, $requestedById);
            $stmt->execute();
            $newId = (int)$stmt->insert_id;
            $stmt->close();
            auth_audit_log($conn, 'create', 'leave_request', (string)$newId, null, json_encode(['type' => $requestType]));
            $flashType = 'success';
            $flashMessage = 'Leave request created.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_leave'])) {
    auth_require_permission('leave_requests', 'approve', 'index.php');
    $requestId = (int)($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $approver = (string)($_SESSION['auth_name'] ?? 'Principal');
        $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $approver, $requestId);
            $stmt->execute();
            $stmt->close();
        }

        // For student leave, reflect approval in attendance table.
        $reqStmt = $conn->prepare("SELECT request_type, student_id, class_id, from_date, to_date, reason FROM leave_requests WHERE id = ? LIMIT 1");
        if ($reqStmt) {
            $reqStmt->bind_param('i', $requestId);
            $reqStmt->execute();
            $req = $reqStmt->get_result()->fetch_assoc();
            $reqStmt->close();
            if ($req && (string)$req['request_type'] === 'student') {
                $studentId = (int)($req['student_id'] ?? 0);
                $classId = (int)($req['class_id'] ?? 0);
                $reason = (string)($req['reason'] ?? '');
                $start = strtotime((string)$req['from_date']);
                $end = strtotime((string)$req['to_date']);
                if ($studentId > 0 && $classId > 0 && $start && $end && $end >= $start) {
                    for ($d = $start; $d <= $end; $d += 86400) {
                        $date = date('Y-m-d', $d);
                        $check = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ? LIMIT 1");
                        $existing = 0;
                        if ($check) {
                            $check->bind_param('is', $studentId, $date);
                            $check->execute();
                            $existing = (int)($check->get_result()->fetch_assoc()['id'] ?? 0);
                            $check->close();
                        }
                        if ($existing > 0) {
                            $upd = $conn->prepare("UPDATE attendance SET class_id = ?, status = 'L', reason = ? WHERE id = ?");
                            if ($upd) {
                                $upd->bind_param('isi', $classId, $reason, $existing);
                                $upd->execute();
                                $upd->close();
                            }
                        } else {
                            $ins = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, reason) VALUES (?, ?, ?, 'L', ?)");
                            if ($ins) {
                                $ins->bind_param('iiss', $studentId, $classId, $date, $reason);
                                $ins->execute();
                                $ins->close();
                            }
                        }
                    }
                }
            }
        }

        auth_audit_log($conn, 'approve', 'leave_request', (string)$requestId);
        $flashType = 'success';
        $flashMessage = 'Leave request approved.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_leave'])) {
    auth_require_permission('leave_requests', 'approve', 'index.php');
    $requestId = (int)($_POST['request_id'] ?? 0);
    if ($requestId > 0) {
        $stmt = $conn->prepare("UPDATE leave_requests SET status = 'Rejected', approved_by = NULL, approved_at = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $requestId);
            $stmt->execute();
            $stmt->close();
        }
        auth_audit_log($conn, 'reject', 'leave_request', (string)$requestId);
        $flashType = 'warning';
        $flashMessage = 'Leave request rejected.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_leave_read'])) {
    $requestId = (int)($_POST['request_id'] ?? 0);
    if ($principalUserId > 0 && $requestId > 0) {
        $stmt = $conn->prepare("INSERT IGNORE INTO principal_leave_reads (principal_user_id, leave_request_id) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param('ii', $principalUserId, $requestId);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Leave request marked as read.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_leave_read']) && $principalUserId > 0) {
    $stmt = $conn->prepare("
        INSERT IGNORE INTO principal_leave_reads (principal_user_id, leave_request_id)
        SELECT ?, lr.id
        FROM leave_requests lr
        WHERE lr.status = 'Pending'
    ");
    if ($stmt) {
        $stmt->bind_param('i', $principalUserId);
        $stmt->execute();
        $stmt->close();
        $flashType = 'success';
        $flashMessage = 'All pending leave requests marked as read.';
    }
}

$teachers = [];
$res = $conn->query("SELECT id, name, email FROM teachers ORDER BY name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $teachers[] = $r;
    }
}
$classes = [];
$res = $conn->query("SELECT id, class, academic_year FROM classes ORDER BY academic_year DESC, class ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $classes[] = $r;
    }
}
$students = [];
$res = $conn->query("SELECT id, StudentId, student_name, class FROM students ORDER BY student_name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $students[] = $r;
    }
}

$requests = [];
$res = $conn->query("
    SELECT lr.*, t.name AS teacher_name, s.StudentId, s.student_name, c.class AS class_name, c.academic_year
    FROM leave_requests lr
    LEFT JOIN teachers t ON t.id = lr.teacher_id
    LEFT JOIN students s ON s.id = lr.student_id
    LEFT JOIN classes c ON c.id = lr.class_id
    ORDER BY lr.id DESC
    LIMIT 500
");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $requests[] = $r;
    }
}

$pastApprovals = [];
foreach ($requests as $r) {
    if ((string)($r['status'] ?? '') !== 'Pending') {
        $pastApprovals[] = $r;
    }
}

$unreadPendingRequests = [];
$stmtUnread = $conn->prepare("
    SELECT lr.*, t.name AS teacher_name, s.StudentId, s.student_name, c.class AS class_name, c.academic_year
    FROM leave_requests lr
    LEFT JOIN teachers t ON t.id = lr.teacher_id
    LEFT JOIN students s ON s.id = lr.student_id
    LEFT JOIN classes c ON c.id = lr.class_id
    LEFT JOIN principal_leave_reads plr
      ON plr.leave_request_id = lr.id
     AND plr.principal_user_id = ?
    WHERE lr.status = 'Pending'
      AND plr.id IS NULL
    ORDER BY lr.id DESC
    LIMIT 300
");
if ($stmtUnread) {
    $stmtUnread->bind_param('i', $principalUserId);
    $stmtUnread->execute();
    $resUnread = $stmtUnread->get_result();
    while ($r = $resUnread->fetch_assoc()) {
        $unreadPendingRequests[] = $r;
    }
    $stmtUnread->close();
}
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Leave Approvals</h1>
    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-danger">Unread Pending Leave Requests</h6>
            <form method="post" class="m-0">
                <button type="submit" name="mark_all_leave_read" class="btn btn-sm btn-outline-danger">Mark All Read</button>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th><th>Type</th><th>Teacher/Student</th><th>Class</th><th>From</th><th>To</th><th>Reason</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($unreadPendingRequests)): foreach ($unreadPendingRequests as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td><?php echo htmlspecialchars((string)$r['request_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((string)$r['request_type'] === 'teacher'): ?>
                                <?php echo htmlspecialchars((string)($r['teacher_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars((string)($r['StudentId'] ?? '') . ' - ' . (string)($r['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars((string)($r['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string)($r['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['from_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['to_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                                <button class="btn btn-success btn-sm" type="submit" name="approve_leave">Approve</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                                <button class="btn btn-danger btn-sm" type="submit" name="reject_leave">Reject</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit" name="mark_leave_read">Mark Read</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" class="text-center">No unread pending leave requests.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Create Leave Request (Teacher or Student)</h6></div>
        <div class="card-body">
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Request Type</label>
                        <select name="request_type" id="lr_type" class="form-control" required>
                            <option value="teacher">Teacher Leave</option>
                            <option value="student">Student Leave</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3" id="teacher_wrap">
                        <label>Teacher</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">Select teacher</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)$t['email'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3 d-none" id="class_wrap">
                        <label>Class</label>
                        <select name="class_id" class="form-control">
                            <option value="">Select class</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars((string)$c['class'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)$c['academic_year'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3 d-none" id="student_wrap">
                        <label>Student</label>
                        <select name="student_id" class="form-control">
                            <option value="">Select student</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars((string)$s['StudentId'] . ' - ' . (string)$s['student_name'] . ' (' . (string)$s['class'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>From</label>
                        <input type="date" name="from_date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>To</label>
                        <input type="date" name="to_date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" name="create_leave_request" class="btn btn-primary w-100">Create</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Leave Requests</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th><th>Type</th><th>Teacher/Student</th><th>Class</th><th>From</th><th>To</th><th>Reason</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($requests)): foreach ($requests as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td><?php echo htmlspecialchars((string)$r['request_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((string)$r['request_type'] === 'teacher'): ?>
                                <?php echo htmlspecialchars((string)($r['teacher_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars((string)($r['StudentId'] ?? '') . ' - ' . (string)($r['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars((string)($r['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string)($r['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['from_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['to_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((string)$r['status'] === 'Pending'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                                    <button class="btn btn-success btn-sm" type="submit" name="approve_leave">Approve</button>
                                </form>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                                    <button class="btn btn-danger btn-sm" type="submit" name="reject_leave">Reject</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">No action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="9" class="text-center">No leave requests yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success">Past Approval History</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th><th>Type</th><th>Teacher/Student</th><th>Class</th><th>From</th><th>To</th><th>Reason</th><th>Status</th><th>Approved By</th><th>Approved At</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($pastApprovals)): foreach ($pastApprovals as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td><?php echo htmlspecialchars((string)$r['request_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((string)$r['request_type'] === 'teacher'): ?>
                                <?php echo htmlspecialchars((string)($r['teacher_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars((string)($r['StudentId'] ?? '') . ' - ' . (string)($r['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars((string)($r['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string)($r['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['from_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['to_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['approved_by'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['approved_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center">No past approvals found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
var typeSel = document.getElementById('lr_type');
var tw = document.getElementById('teacher_wrap');
var cw = document.getElementById('class_wrap');
var sw = document.getElementById('student_wrap');
function toggleLeaveForm() {
    var isTeacher = (typeSel.value === 'teacher');
    tw.classList.toggle('d-none', !isTeacher);
    cw.classList.toggle('d-none', isTeacher);
    sw.classList.toggle('d-none', isTeacher);
}
typeSel.addEventListener('change', toggleLeaveForm);
toggleLeaveForm();
</script>
<?php include './partials/footer.php'; ?>
