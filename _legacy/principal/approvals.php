<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('admin_dashboard', 'view', 'index.php');
include '../db.php';
include './partials/topbar.php';

$flashType = '';
$flashMessage = '';

$conn->query("
    CREATE TABLE IF NOT EXISTS sick_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        sick_date DATE NOT NULL,
        days_off INT NOT NULL DEFAULT 1,
        reason VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        approved_by VARCHAR(120) NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
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
    CREATE TABLE IF NOT EXISTS principal_approval_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        principal_user_id INT NOT NULL,
        approval_type VARCHAR(40) NOT NULL,
        reference_key VARCHAR(190) NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_principal_approval_read (principal_user_id, approval_type, reference_key)
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS result_lock_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        result_date DATE NOT NULL,
        result_type INT NOT NULL,
        is_locked TINYINT(1) NOT NULL DEFAULT 1,
        lock_note VARCHAR(255) NULL,
        locked_by VARCHAR(120) NULL,
        locked_at DATETIME NULL,
        unlocked_by VARCHAR(120) NULL,
        unlocked_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_result_lock_group (teacher_id, class_id, subject_id, result_date, result_type),
        KEY idx_result_lock_lookup (class_id, subject_id, result_date, result_type, is_locked)
    )
");
$principalUserId = (int)($_SESSION['auth_user_id'] ?? 0);

$ensureCol = function (string $table, string $column, string $definition) use ($conn): void {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $exists = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $stmt->close();
    if (!$exists) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
};

$ensureCol('results', 'approval_status', "VARCHAR(20) NOT NULL DEFAULT 'Pending' AFTER is_absent");
$ensureCol('results', 'approved_by', "VARCHAR(120) NULL AFTER approval_status");
$ensureCol('results', 'approved_at', "DATETIME NULL AFTER approved_by");
$ensureCol('sick_records', 'approved_by', "VARCHAR(120) NULL AFTER status");
$ensureCol('sick_records', 'approved_at', "DATETIME NULL AFTER approved_by");

$resultGroupSnapshot = function (int $teacherId, int $classId, int $subjectId, string $resultDate, int $resultType) use ($conn): array {
    $snapshot = [
        'total_rows' => 0,
        'pending_rows' => 0,
        'approved_rows' => 0,
        'rejected_rows' => 0,
        'is_locked' => 0,
    ];

    $stmt = $conn->prepare("
        SELECT
            COUNT(*) AS total_rows,
            SUM(CASE WHEN approval_status = 'Pending' THEN 1 ELSE 0 END) AS pending_rows,
            SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved_rows,
            SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_rows
        FROM results
        WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND result_date = ? AND result_type = ?
    ");
    if ($stmt) {
        $stmt->bind_param('iiisi', $teacherId, $classId, $subjectId, $resultDate, $resultType);
        $stmt->execute();
        $snapshot = array_merge($snapshot, $stmt->get_result()->fetch_assoc() ?: []);
        $stmt->close();
    }

    $lockStmt = $conn->prepare("
        SELECT is_locked
        FROM result_lock_groups
        WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND result_date = ? AND result_type = ?
        LIMIT 1
    ");
    if ($lockStmt) {
        $lockStmt->bind_param('iiisi', $teacherId, $classId, $subjectId, $resultDate, $resultType);
        $lockStmt->execute();
        $snapshot['is_locked'] = (int)($lockStmt->get_result()->fetch_assoc()['is_locked'] ?? 0);
        $lockStmt->close();
    }

    return $snapshot;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_result_group'])) {
    auth_require_permission('results_reports', 'approve', 'index.php');
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $resultDate = trim((string)($_POST['result_date'] ?? ''));
    $resultType = (int)($_POST['result_type'] ?? -1);
    if ($teacherId > 0 && $classId > 0 && $subjectId > 0 && $resultDate !== '' && $resultType >= 0) {
        $beforeSnapshot = $resultGroupSnapshot($teacherId, $classId, $subjectId, $resultDate, $resultType);
        $approver = (string)($_SESSION['auth_name'] ?? 'Principal');
        $stmt = $conn->prepare("
            UPDATE results
            SET approval_status = 'Approved', approved_by = ?, approved_at = NOW()
            WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND result_date = ? AND result_type = ?
        ");
        if ($stmt) {
            $stmt->bind_param('siiisi', $approver, $teacherId, $classId, $subjectId, $resultDate, $resultType);
            $stmt->execute();
            $affected = (int)$stmt->affected_rows;
            $stmt->close();
            $afterSnapshot = $resultGroupSnapshot($teacherId, $classId, $subjectId, $resultDate, $resultType);
            auth_audit_log_change(
                $conn,
                'approve',
                'results_group',
                $teacherId . ':' . $classId . ':' . $subjectId . ':' . $resultDate . ':' . $resultType,
                $beforeSnapshot,
                ['affected' => $affected, 'snapshot' => $afterSnapshot]
            );
            $flashType = 'success';
            $flashMessage = 'Result group approved.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_result_group'])) {
    auth_require_permission('results_reports', 'approve', 'index.php');
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $resultDate = trim((string)($_POST['result_date'] ?? ''));
    $resultType = (int)($_POST['result_type'] ?? -1);
    if ($teacherId > 0 && $classId > 0 && $subjectId > 0 && $resultDate !== '' && $resultType >= 0) {
        $beforeSnapshot = $resultGroupSnapshot($teacherId, $classId, $subjectId, $resultDate, $resultType);
        $stmt = $conn->prepare("
            UPDATE results
            SET approval_status = 'Rejected', approved_by = NULL, approved_at = NULL
            WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND result_date = ? AND result_type = ?
        ");
        if ($stmt) {
            $stmt->bind_param('iiisi', $teacherId, $classId, $subjectId, $resultDate, $resultType);
            $stmt->execute();
            $stmt->close();
            $afterSnapshot = $resultGroupSnapshot($teacherId, $classId, $subjectId, $resultDate, $resultType);
            auth_audit_log_change(
                $conn,
                'reject',
                'results_group',
                $teacherId . ':' . $classId . ':' . $subjectId . ':' . $resultDate . ':' . $resultType,
                $beforeSnapshot,
                ['snapshot' => $afterSnapshot]
            );
            $flashType = 'warning';
            $flashMessage = 'Result group rejected.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_result_read'])) {
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $resultDate = trim((string)($_POST['result_date'] ?? ''));
    $resultType = (int)($_POST['result_type'] ?? -1);
    if ($principalUserId > 0 && $teacherId > 0 && $classId > 0 && $subjectId > 0 && $resultDate !== '' && $resultType >= 0) {
        $refKey = $teacherId . ':' . $classId . ':' . $subjectId . ':' . $resultDate . ':' . $resultType;
        $stmt = $conn->prepare("INSERT IGNORE INTO principal_approval_reads (principal_user_id, approval_type, reference_key) VALUES (?, 'result_group', ?)");
        if ($stmt) {
            $stmt->bind_param('is', $principalUserId, $refKey);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Result approval item marked as read.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lock_result_group'])) {
    auth_require_permission('results_reports', 'approve', 'index.php');
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $resultDate = trim((string)($_POST['result_date'] ?? ''));
    $resultType = (int)($_POST['result_type'] ?? -1);
    if ($teacherId > 0 && $classId > 0 && $subjectId > 0 && $resultDate !== '' && $resultType >= 0) {
        $beforeSnapshot = $resultGroupSnapshot($teacherId, $classId, $subjectId, $resultDate, $resultType);
        $actor = (string)($_SESSION['auth_name'] ?? 'Principal');
        $stmt = $conn->prepare("
            INSERT INTO result_lock_groups
                (teacher_id, class_id, subject_id, result_date, result_type, is_locked, lock_note, locked_by, locked_at, unlocked_by, unlocked_at)
            VALUES
                (?, ?, ?, ?, ?, 1, NULL, ?, NOW(), NULL, NULL)
            ON DUPLICATE KEY UPDATE
                is_locked = 1,
                locked_by = VALUES(locked_by),
                locked_at = NOW(),
                unlocked_by = NULL,
                unlocked_at = NULL
        ");
        if ($stmt) {
            $stmt->bind_param('iiisis', $teacherId, $classId, $subjectId, $resultDate, $resultType, $actor);
            $stmt->execute();
            $stmt->close();
            $afterSnapshot = $resultGroupSnapshot($teacherId, $classId, $subjectId, $resultDate, $resultType);
            auth_audit_log_change(
                $conn,
                'lock',
                'results_group',
                $teacherId . ':' . $classId . ':' . $subjectId . ':' . $resultDate . ':' . $resultType,
                $beforeSnapshot,
                ['actor' => $actor, 'snapshot' => $afterSnapshot]
            );
            $flashType = 'success';
            $flashMessage = 'Result group locked.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_result_group'])) {
    auth_require_permission('results_reports', 'approve', 'index.php');
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $resultDate = trim((string)($_POST['result_date'] ?? ''));
    $resultType = (int)($_POST['result_type'] ?? -1);
    if ($teacherId > 0 && $classId > 0 && $subjectId > 0 && $resultDate !== '' && $resultType >= 0) {
        $beforeSnapshot = $resultGroupSnapshot($teacherId, $classId, $subjectId, $resultDate, $resultType);
        $actor = (string)($_SESSION['auth_name'] ?? 'Principal');
        $stmt = $conn->prepare("
            INSERT INTO result_lock_groups
                (teacher_id, class_id, subject_id, result_date, result_type, is_locked, lock_note, locked_by, locked_at, unlocked_by, unlocked_at)
            VALUES
                (?, ?, ?, ?, ?, 0, NULL, NULL, NULL, ?, NOW())
            ON DUPLICATE KEY UPDATE
                is_locked = 0,
                unlocked_by = VALUES(unlocked_by),
                unlocked_at = NOW()
        ");
        if ($stmt) {
            $stmt->bind_param('iiisis', $teacherId, $classId, $subjectId, $resultDate, $resultType, $actor);
            $stmt->execute();
            $stmt->close();
            $afterSnapshot = $resultGroupSnapshot($teacherId, $classId, $subjectId, $resultDate, $resultType);
            auth_audit_log_change(
                $conn,
                'unlock',
                'results_group',
                $teacherId . ':' . $classId . ':' . $subjectId . ':' . $resultDate . ':' . $resultType,
                $beforeSnapshot,
                ['actor' => $actor, 'snapshot' => $afterSnapshot]
            );
            $flashType = 'success';
            $flashMessage = 'Result group unlocked.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_sick'])) {
    auth_require_permission('medical_records', 'approve', 'index.php');
    $recordId = (int)($_POST['record_id'] ?? 0);
    if ($recordId > 0) {
        $approver = (string)($_SESSION['auth_name'] ?? 'Principal');
        $stmt = $conn->prepare("UPDATE sick_records SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $approver, $recordId);
            $stmt->execute();
            $stmt->close();
            auth_audit_log($conn, 'approve', 'sick_record', (string)$recordId);
            $flashType = 'success';
            $flashMessage = 'Sick referral approved.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_sick_read'])) {
    $recordId = (int)($_POST['record_id'] ?? 0);
    if ($principalUserId > 0 && $recordId > 0) {
        $refKey = (string)$recordId;
        $stmt = $conn->prepare("INSERT IGNORE INTO principal_approval_reads (principal_user_id, approval_type, reference_key) VALUES (?, 'sick_referral', ?)");
        if ($stmt) {
            $stmt->bind_param('is', $principalUserId, $refKey);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Sick referral marked as read.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_approvals_read']) && $principalUserId > 0) {
    $stmtA = $conn->prepare("
        INSERT IGNORE INTO principal_approval_reads (principal_user_id, approval_type, reference_key)
        SELECT ?, 'result_group', CONCAT(r.teacher_id, ':', r.class_id, ':', r.subject_id, ':', r.result_date, ':', r.result_type)
        FROM results r
        WHERE r.approval_status = 'Pending'
        GROUP BY r.teacher_id, r.class_id, r.subject_id, r.result_date, r.result_type
    ");
    if ($stmtA) {
        $stmtA->bind_param('i', $principalUserId);
        $stmtA->execute();
        $stmtA->close();
    }
    $stmtB = $conn->prepare("
        INSERT IGNORE INTO principal_approval_reads (principal_user_id, approval_type, reference_key)
        SELECT ?, 'sick_referral', CAST(sr.id AS CHAR)
        FROM sick_records sr
        WHERE sr.status = 'Pending'
    ");
    if ($stmtB) {
        $stmtB->bind_param('i', $principalUserId);
        $stmtB->execute();
        $stmtB->close();
    }
    $flashType = 'success';
    $flashMessage = 'All approval items marked as read.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_sick'])) {
    auth_require_permission('medical_records', 'approve', 'index.php');
    $recordId = (int)($_POST['record_id'] ?? 0);
    if ($recordId > 0) {
        $stmt = $conn->prepare("UPDATE sick_records SET status = 'Rejected', approved_by = NULL, approved_at = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $recordId);
            $stmt->execute();
            $stmt->close();
            auth_audit_log($conn, 'reject', 'sick_record', (string)$recordId);
            $flashType = 'warning';
            $flashMessage = 'Sick referral rejected.';
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

$pendingResults = [];
$stmtPendingResults = $conn->prepare("
    SELECT x.teacher_id, x.teacher_name, x.class_id, x.class_name, x.subject_id, x.subject_name, x.result_date, x.result_type, x.total_rows,
           COALESCE(rlg.is_locked, 0) AS is_locked
    FROM (
        SELECT r.teacher_id, t.name AS teacher_name, r.class_id, c.class AS class_name, r.subject_id, s.subject_name,
               r.result_date, r.result_type, COUNT(*) AS total_rows,
               CONCAT(r.teacher_id, ':', r.class_id, ':', r.subject_id, ':', r.result_date, ':', r.result_type) AS ref_key
        FROM results r
        LEFT JOIN teachers t ON t.id = r.teacher_id
        LEFT JOIN classes c ON c.id = r.class_id
        LEFT JOIN subjects s ON s.id = r.subject_id
        WHERE r.approval_status = 'Pending'
        GROUP BY r.teacher_id, r.class_id, r.subject_id, r.result_date, r.result_type
    ) x
    LEFT JOIN result_lock_groups rlg
      ON rlg.teacher_id = x.teacher_id
     AND rlg.class_id = x.class_id
     AND rlg.subject_id = x.subject_id
     AND rlg.result_date = x.result_date
     AND rlg.result_type = x.result_type
    LEFT JOIN principal_approval_reads par
      ON par.principal_user_id = ?
     AND par.approval_type = 'result_group'
     AND par.reference_key = x.ref_key
    WHERE par.id IS NULL
    ORDER BY x.result_date DESC, x.teacher_id ASC
");
if ($stmtPendingResults) {
    $stmtPendingResults->bind_param('i', $principalUserId);
    $stmtPendingResults->execute();
    $res = $stmtPendingResults->get_result();
    while ($row = $res->fetch_assoc()) {
        $pendingResults[] = $row;
    }
    $stmtPendingResults->close();
}

$pendingSick = [];
$stmtPendingSick = $conn->prepare("
    SELECT sr.id, sr.sick_date, sr.reason, s.StudentId, s.student_name, s.class
    FROM sick_records sr
    JOIN students s ON s.id = sr.student_id
    LEFT JOIN principal_approval_reads par
      ON par.principal_user_id = ?
     AND par.approval_type = 'sick_referral'
     AND par.reference_key = CAST(sr.id AS CHAR)
    WHERE sr.status = 'Pending'
      AND par.id IS NULL
    ORDER BY sr.sick_date DESC, sr.id DESC
");
if ($stmtPendingSick) {
    $stmtPendingSick->bind_param('i', $principalUserId);
    $stmtPendingSick->execute();
    $res2 = $stmtPendingSick->get_result();
    while ($row = $res2->fetch_assoc()) {
        $pendingSick[] = $row;
    }
    $stmtPendingSick->close();
}

$pendingLeaves = [];
$res3 = $conn->query("
    SELECT lr.id, lr.request_type, lr.from_date, lr.to_date, lr.reason,
           t.name AS teacher_name,
           s.StudentId, s.student_name, c.class AS class_name
    FROM leave_requests lr
    LEFT JOIN teachers t ON t.id = lr.teacher_id
    LEFT JOIN students s ON s.id = lr.student_id
    LEFT JOIN classes c ON c.id = lr.class_id
    WHERE lr.status = 'Pending'
    ORDER BY lr.id DESC
");
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        $pendingLeaves[] = $row;
    }
}

$resultTypeLabels = [
    0 => 'Weekly Test',
    1 => 'Monthly Test',
    2 => 'Mid Term',
    3 => 'Annual',
];
?>
<div class="container-fluid">
    <h4 class="mb-3">Approvals</h4>
    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <form method="post" class="mb-3">
        <button class="btn btn-sm btn-outline-secondary" type="submit" name="mark_all_approvals_read">Mark All Approvals Read</button>
    </form>

    <div class="card mb-4">
        <div class="card-header">Pending Result Submissions</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Teacher</th><th>Class</th><th>Subject</th><th>Date</th><th title="Exam term type: Weekly, Monthly, Mid Term, or Annual">Type</th><th title="Number of student mark rows submitted in this batch">Rows</th><th>Lock</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($pendingResults)): foreach ($pendingResults as $r): ?>
                    <?php
                        $resultTypeCode = (int)$r['result_type'];
                        $resultTypeLabel = $resultTypeLabels[$resultTypeCode] ?? ('Type ' . $resultTypeCode);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)($r['teacher_name'] ?? ('Teacher #' . $r['teacher_id'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['result_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td title="<?php echo htmlspecialchars('Type code ' . $resultTypeCode . ' = ' . $resultTypeLabel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($resultTypeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td title="Rows = number of student records uploaded for this class/subject/date/type"><?php echo (int)$r['total_rows']; ?></td>
                        <td>
                            <?php if ((int)($r['is_locked'] ?? 0) === 1): ?>
                                <span class="badge badge-danger">Locked</span>
                            <?php else: ?>
                                <span class="badge badge-success">Open</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="teacher_id" value="<?php echo (int)$r['teacher_id']; ?>">
                                <input type="hidden" name="class_id" value="<?php echo (int)$r['class_id']; ?>">
                                <input type="hidden" name="subject_id" value="<?php echo (int)$r['subject_id']; ?>">
                                <input type="hidden" name="result_date" value="<?php echo htmlspecialchars((string)$r['result_date'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="result_type" value="<?php echo (int)$r['result_type']; ?>">
                                <button class="btn btn-success btn-sm" type="submit" name="approve_result_group">Approve</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="teacher_id" value="<?php echo (int)$r['teacher_id']; ?>">
                                <input type="hidden" name="class_id" value="<?php echo (int)$r['class_id']; ?>">
                                <input type="hidden" name="subject_id" value="<?php echo (int)$r['subject_id']; ?>">
                                <input type="hidden" name="result_date" value="<?php echo htmlspecialchars((string)$r['result_date'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="result_type" value="<?php echo (int)$r['result_type']; ?>">
                                <button class="btn btn-danger btn-sm" type="submit" name="reject_result_group">Reject</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="teacher_id" value="<?php echo (int)$r['teacher_id']; ?>">
                                <input type="hidden" name="class_id" value="<?php echo (int)$r['class_id']; ?>">
                                <input type="hidden" name="subject_id" value="<?php echo (int)$r['subject_id']; ?>">
                                <input type="hidden" name="result_date" value="<?php echo htmlspecialchars((string)$r['result_date'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="result_type" value="<?php echo (int)$r['result_type']; ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit" name="mark_result_read">Mark Read</button>
                            </form>
                            <?php if ((int)($r['is_locked'] ?? 0) === 1): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="teacher_id" value="<?php echo (int)$r['teacher_id']; ?>">
                                    <input type="hidden" name="class_id" value="<?php echo (int)$r['class_id']; ?>">
                                    <input type="hidden" name="subject_id" value="<?php echo (int)$r['subject_id']; ?>">
                                    <input type="hidden" name="result_date" value="<?php echo htmlspecialchars((string)$r['result_date'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="result_type" value="<?php echo (int)$r['result_type']; ?>">
                                    <button class="btn btn-warning btn-sm" type="submit" name="unlock_result_group">Unlock</button>
                                </form>
                            <?php else: ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="teacher_id" value="<?php echo (int)$r['teacher_id']; ?>">
                                    <input type="hidden" name="class_id" value="<?php echo (int)$r['class_id']; ?>">
                                    <input type="hidden" name="subject_id" value="<?php echo (int)$r['subject_id']; ?>">
                                    <input type="hidden" name="result_date" value="<?php echo htmlspecialchars((string)$r['result_date'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="result_type" value="<?php echo (int)$r['result_type']; ?>">
                                    <button class="btn btn-dark btn-sm" type="submit" name="lock_result_group">Lock</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" class="text-center">No pending result approvals.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Pending Sick Referrals</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Student</th><th>Class</th><th>Date</th><th>Reason</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($pendingSick)): foreach ($pendingSick as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$s['StudentId'] . ' - ' . (string)$s['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$s['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$s['sick_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$s['reason'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="record_id" value="<?php echo (int)$s['id']; ?>">
                                <button class="btn btn-success btn-sm" type="submit" name="approve_sick">Approve</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="record_id" value="<?php echo (int)$s['id']; ?>">
                                <button class="btn btn-danger btn-sm" type="submit" name="reject_sick">Reject</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="record_id" value="<?php echo (int)$s['id']; ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit" name="mark_sick_read">Mark Read</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center">No pending sick referrals.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Pending Leave Requests (Teacher & Student)</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Type</th><th>Teacher/Student</th><th>Class</th><th>From</th><th>To</th><th>Reason</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($pendingLeaves)): foreach ($pendingLeaves as $l): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$l['request_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((string)$l['request_type'] === 'teacher'): ?>
                                <?php echo htmlspecialchars((string)($l['teacher_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars((string)($l['StudentId'] ?? '') . ' - ' . (string)($l['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars((string)($l['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$l['from_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$l['to_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($l['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="request_id" value="<?php echo (int)$l['id']; ?>">
                                <button class="btn btn-success btn-sm" type="submit" name="approve_leave">Approve</button>
                            </form>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="request_id" value="<?php echo (int)$l['id']; ?>">
                                <button class="btn btn-danger btn-sm" type="submit" name="reject_leave">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center">No pending leave requests.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
