<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('discipline', 'view', '../index.php');
include '../db.php';
require_once __DIR__ . '/../scripts/discipline_lib.php';

discipline_ensure_tables($conn);

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$principalUserId = (int)($_SESSION['auth_user_id'] ?? 0);
$principalName = (string)($_SESSION['auth_name'] ?? 'Principal');
$flashType = '';
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_record']) && auth_can('discipline', 'create')) {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $category = trim((string)($_POST['category'] ?? 'warning'));
    $severity = trim((string)($_POST['severity'] ?? 'medium'));
    $incidentDate = trim((string)($_POST['incident_date'] ?? date('Y-m-d')));
    $title = trim((string)($_POST['title'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));
    if ($studentId <= 0 || $classId <= 0 || $title === '') {
        $flashType = 'danger';
        $flashMessage = 'Student, class and title are required.';
    } else {
        $isValidStudentClass = false;
        $studentClassCheck = $conn->prepare("
            SELECT s.id
            FROM students s
            JOIN classes c ON c.class = s.class
            WHERE s.id = ? AND c.id = ?
            LIMIT 1
        ");
        if ($studentClassCheck) {
            $studentClassCheck->bind_param('ii', $studentId, $classId);
            $studentClassCheck->execute();
            $isValidStudentClass = (bool)$studentClassCheck->get_result()->fetch_assoc();
            $studentClassCheck->close();
        }
        if (!$isValidStudentClass) {
            $flashType = 'danger';
            $flashMessage = 'Selected student does not belong to the selected class.';
        } else {
        $stmt = $conn->prepare("
            INSERT INTO discipline_records
            (student_id, class_id, category, severity, incident_date, title, notes, status, recorded_by_role, recorded_by_id, recorded_by_name, report_to_principal)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open', 'principal', ?, ?, 1)
        ");
        if ($stmt) {
            $stmt->bind_param('iisssssis', $studentId, $classId, $category, $severity, $incidentDate, $title, $notes, $principalUserId, $principalName);
            if ($stmt->execute()) {
                auth_audit_log($conn, 'create', 'discipline', (string)$stmt->insert_id, null, json_encode([
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'category' => $category,
                    'severity' => $severity,
                    'title' => $title
                ]));
                $flashType = 'success';
                $flashMessage = 'Discipline record created.';
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to create record.';
            }
            $stmt->close();
        }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_action']) && auth_can('discipline', 'edit')) {
    $disciplineId = (int)($_POST['discipline_id'] ?? 0);
    $actionDate = trim((string)($_POST['action_date'] ?? date('Y-m-d')));
    $actionText = trim((string)($_POST['action_text'] ?? ''));
    if ($disciplineId > 0 && $actionText !== '') {
        $stmt = $conn->prepare("
            INSERT INTO discipline_actions (discipline_id, action_date, action_text, action_by_role, action_by_id, action_by_name)
            VALUES (?, ?, ?, 'principal', ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('issis', $disciplineId, $actionDate, $actionText, $principalUserId, $principalName);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                auth_audit_log($conn, 'edit', 'discipline', (string)$disciplineId, null, json_encode(['action' => $actionText]));
                $flashType = 'success';
                $flashMessage = 'Action history updated.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && auth_can('discipline', 'edit')) {
    $disciplineId = (int)($_POST['discipline_id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'open'));
    if ($disciplineId > 0) {
        $stmt = $conn->prepare("UPDATE discipline_records SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $status, $disciplineId);
            $stmt->execute();
            $stmt->close();
            auth_audit_log($conn, 'edit', 'discipline', (string)$disciplineId, null, json_encode(['status' => $status]));
            $flashType = 'success';
            $flashMessage = 'Status updated.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_parent_meeting']) && auth_can('discipline', 'meeting')) {
    $disciplineId = (int)($_POST['discipline_id'] ?? 0);
    $studentId = (int)($_POST['meeting_student_id'] ?? 0);
    $classId = (int)($_POST['meeting_class_id'] ?? 0);
    $meetingDate = trim((string)($_POST['meeting_date'] ?? date('Y-m-d')));
    $meetingTitle = trim((string)($_POST['meeting_title'] ?? 'Parent Meeting'));
    $attendees = trim((string)($_POST['attendees'] ?? ''));
    $notes = trim((string)($_POST['meeting_notes'] ?? ''));
    $outcome = trim((string)($_POST['meeting_outcome'] ?? ''));
    if ($studentId > 0 && $classId > 0) {
        $isValidStudentClass = false;
        $studentClassCheck = $conn->prepare("
            SELECT s.id
            FROM students s
            JOIN classes c ON c.class = s.class
            WHERE s.id = ? AND c.id = ?
            LIMIT 1
        ");
        if ($studentClassCheck) {
            $studentClassCheck->bind_param('ii', $studentId, $classId);
            $studentClassCheck->execute();
            $isValidStudentClass = (bool)$studentClassCheck->get_result()->fetch_assoc();
            $studentClassCheck->close();
        }
        if (!$isValidStudentClass) {
            $flashType = 'danger';
            $flashMessage = 'Invalid class/student for parent meeting.';
        } else {
        $stmt = $conn->prepare("
            INSERT INTO parent_meetings
            (discipline_id, student_id, class_id, meeting_date, meeting_title, attendees, notes, outcome, created_by_role, created_by_id, created_by_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'principal', ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('iiisssssis', $disciplineId, $studentId, $classId, $meetingDate, $meetingTitle, $attendees, $notes, $outcome, $principalUserId, $principalName);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                auth_audit_log($conn, 'create', 'discipline', (string)$disciplineId, null, json_encode(['meeting' => $meetingTitle, 'date' => $meetingDate]));
                $flashType = 'success';
                $flashMessage = 'Parent meeting logged.';
            }
        }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record']) && auth_can('discipline', 'delete')) {
    $disciplineId = (int)($_POST['discipline_id'] ?? 0);
    if ($disciplineId > 0) {
        $conn->query("DELETE FROM discipline_actions WHERE discipline_id = " . $disciplineId);
        $conn->query("DELETE FROM parent_meetings WHERE discipline_id = " . $disciplineId);
        $conn->query("DELETE FROM discipline_records WHERE id = " . $disciplineId);
        auth_audit_log($conn, 'delete', 'discipline', (string)$disciplineId, null, null);
        $flashType = 'success';
        $flashMessage = 'Record deleted.';
    }
}

$students = [];
$classes = [];
$records = [];
$actionsMap = [];
$meetingLogs = [];

$sres = $conn->query("
    SELECT s.id, s.StudentId, s.student_name, s.class, c.id AS class_id
    FROM students s
    LEFT JOIN classes c ON c.class = s.class
    ORDER BY s.student_name ASC
");
if ($sres) {
    while ($row = $sres->fetch_assoc()) {
        $students[] = $row;
    }
}

$cres = $conn->query("SELECT id, class FROM classes ORDER BY class ASC");
if ($cres) {
    while ($row = $cres->fetch_assoc()) {
        $classes[] = $row;
    }
}

$studentsByClass = [];
foreach ($students as $st) {
    $cid = (int)($st['class_id'] ?? 0);
    if ($cid <= 0) {
        continue;
    }
    if (!isset($studentsByClass[$cid])) {
        $studentsByClass[$cid] = [];
    }
    $studentsByClass[$cid][] = [
        'id' => (int)$st['id'],
        'text' => trim((string)($st['StudentId'] ?? '')) . ' - ' . trim((string)($st['student_name'] ?? ''))
    ];
}

$recordsSql = "
    SELECT d.*, s.student_name, s.StudentId, c.class AS class_name
    FROM discipline_records d
    LEFT JOIN students s ON s.id = d.student_id
    LEFT JOIN classes c ON c.id = d.class_id
    ORDER BY d.id DESC
    LIMIT 200
";
$rres = $conn->query($recordsSql);
if ($rres) {
    while ($row = $rres->fetch_assoc()) {
        $records[] = $row;
    }
}

if (!empty($records)) {
    $ids = [];
    foreach ($records as $r) {
        $ids[] = (int)$r['id'];
    }
    $idCsv = implode(',', $ids);
    $ares = $conn->query("SELECT * FROM discipline_actions WHERE discipline_id IN ($idCsv) ORDER BY id DESC");
    if ($ares) {
        while ($row = $ares->fetch_assoc()) {
            $did = (int)$row['discipline_id'];
            if (!isset($actionsMap[$did])) {
                $actionsMap[$did] = [];
            }
            $actionsMap[$did][] = $row;
        }
    }
}

$mres = $conn->query("
    SELECT pm.*, s.student_name, s.StudentId, c.class AS class_name
    FROM parent_meetings pm
    LEFT JOIN students s ON s.id = pm.student_id
    LEFT JOIN classes c ON c.id = pm.class_id
    ORDER BY pm.id DESC
    LIMIT 100
");
if ($mres) {
    while ($row = $mres->fetch_assoc()) {
        $meetingLogs[] = $row;
    }
}

include './partials/topbar.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Discipline Module</h1>
    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo h($flashType); ?>"><?php echo h($flashMessage); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Create Discipline Record</h6></div>
        <div class="card-body">
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label>Class</label>
                        <select name="class_id" id="discipline_class_id" class="form-control" required>
                            <option value="">Select</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?php echo (int)$cl['id']; ?>"><?php echo h($cl['class']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Student</label>
                        <select name="student_id" id="discipline_student_id" class="form-control" required>
                            <option value="">Select class first</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="warning">Warning</option>
                            <option value="achievement">Achievement</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Severity</label>
                        <select name="severity" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date</label>
                        <input type="date" name="incident_date" value="<?php echo h(date('Y-m-d')); ?>" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group col-md-8">
                        <label>Notes</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                </div>
                <button type="submit" name="create_record" class="btn btn-primary">Create Record</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-danger">Discipline Cases</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Category</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td>#<?php echo (int)$row['id']; ?></td>
                            <td><?php echo h($row['incident_date']); ?></td>
                            <td><?php echo h(($row['StudentId'] ?? '') . ' - ' . ($row['student_name'] ?? '')); ?></td>
                            <td><?php echo h($row['class_name'] ?? ''); ?></td>
                            <td><span class="badge badge-<?php echo $row['category'] === 'achievement' ? 'success' : 'warning'; ?>"><?php echo h(ucfirst((string)$row['category'])); ?></span></td>
                            <td><?php echo h($row['title']); ?><div class="small text-muted"><?php echo h($row['notes']); ?></div></td>
                            <td><?php echo h(ucfirst((string)$row['status'])); ?></td>
                            <td style="min-width: 320px;">
                                <form method="post" class="mb-2">
                                    <input type="hidden" name="discipline_id" value="<?php echo (int)$row['id']; ?>">
                                    <div class="input-group input-group-sm">
                                        <select name="status" class="form-control">
                                            <option value="open" <?php echo $row['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                            <option value="monitoring" <?php echo $row['status'] === 'monitoring' ? 'selected' : ''; ?>>Monitoring</option>
                                            <option value="resolved" <?php echo $row['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        </select>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-primary" name="update_status" type="submit">Update</button>
                                        </div>
                                    </div>
                                </form>
                                <form method="post" class="mb-2">
                                    <input type="hidden" name="discipline_id" value="<?php echo (int)$row['id']; ?>">
                                    <div class="input-group input-group-sm mb-1">
                                        <input type="date" name="action_date" value="<?php echo h(date('Y-m-d')); ?>" class="form-control" required>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="action_text" class="form-control" placeholder="Add action history" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-success" name="add_action" type="submit">Add Action</button>
                                        </div>
                                    </div>
                                </form>
                                <form method="post" class="mb-2">
                                    <input type="hidden" name="discipline_id" value="<?php echo (int)$row['id']; ?>">
                                    <input type="hidden" name="meeting_student_id" value="<?php echo (int)$row['student_id']; ?>">
                                    <input type="hidden" name="meeting_class_id" value="<?php echo (int)$row['class_id']; ?>">
                                    <div class="input-group input-group-sm mb-1">
                                        <input type="date" name="meeting_date" value="<?php echo h(date('Y-m-d')); ?>" class="form-control" required>
                                        <input type="text" name="meeting_title" class="form-control" placeholder="Parent meeting title" required>
                                    </div>
                                    <input type="text" name="attendees" class="form-control form-control-sm mb-1" placeholder="Attendees (Parent, Principal, Teacher)">
                                    <input type="text" name="meeting_notes" class="form-control form-control-sm mb-1" placeholder="Discussion notes">
                                    <input type="text" name="meeting_outcome" class="form-control form-control-sm mb-1" placeholder="Outcome / commitment">
                                    <button class="btn btn-outline-info btn-sm" name="add_parent_meeting" type="submit">Log Parent Meeting</button>
                                </form>
                                <form method="post" onsubmit="return confirm('Delete this discipline case?');">
                                    <input type="hidden" name="discipline_id" value="<?php echo (int)$row['id']; ?>">
                                    <button class="btn btn-outline-danger btn-sm" name="delete_record" type="submit">Delete</button>
                                </form>
                                <?php if (!empty($actionsMap[(int)$row['id']])): ?>
                                    <div class="mt-2 small">
                                        <?php foreach ($actionsMap[(int)$row['id']] as $a): ?>
                                            <div><strong><?php echo h($a['action_date']); ?></strong>: <?php echo h($a['action_text']); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="8" class="text-center">No discipline records yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Parent Meeting Log</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Title</th>
                        <th>Attendees</th>
                        <th>Notes</th>
                        <th>Outcome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meetingLogs as $row): ?>
                        <tr>
                            <td><?php echo h($row['meeting_date']); ?></td>
                            <td><?php echo h(($row['StudentId'] ?? '') . ' - ' . ($row['student_name'] ?? '')); ?></td>
                            <td><?php echo h($row['class_name'] ?? ''); ?></td>
                            <td><?php echo h($row['meeting_title'] ?? ''); ?></td>
                            <td><?php echo h($row['attendees'] ?? ''); ?></td>
                            <td><?php echo h($row['notes'] ?? ''); ?></td>
                            <td><?php echo h($row['outcome'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($meetingLogs)): ?>
                        <tr><td colspan="7" class="text-center">No parent meetings logged yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    const disciplineStudentsByClass = <?php echo json_encode($studentsByClass); ?>;
    (function () {
        const classSelect = document.getElementById('discipline_class_id');
        const studentSelect = document.getElementById('discipline_student_id');
        if (!classSelect || !studentSelect) {
            return;
        }
        function renderStudentOptions() {
            const classId = classSelect.value;
            const list = disciplineStudentsByClass[classId] || [];
            studentSelect.innerHTML = '';
            if (!classId) {
                studentSelect.innerHTML = '<option value="">Select class first</option>';
                return;
            }
            studentSelect.innerHTML = '<option value="">Select student</option>';
            list.forEach(function (item) {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.text;
                studentSelect.appendChild(opt);
            });
            if (list.length === 0) {
                studentSelect.innerHTML = '<option value="">No students in this class</option>';
            }
        }
        classSelect.addEventListener('change', renderStudentOptions);
        renderStudentOptions();
    })();
</script>
<?php include './partials/footer.php'; ?>
