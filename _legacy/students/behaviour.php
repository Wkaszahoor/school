<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('discipline', 'view', '../index.php');
include '../db.php';
require_once __DIR__ . '/../scripts/discipline_lib.php';
require_once __DIR__ . '/../scripts/stream_subject_lib.php';

discipline_ensure_tables($conn);
include './partials/topbar.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$teacherEmail = (string)($_SESSION['id'] ?? '');
$teacherId = (int)($_SESSION['teacher_id'] ?? 0);
if ($teacherId <= 0 && $teacherEmail !== '') {
    $q = $conn->prepare("SELECT id FROM teachers WHERE email = ? LIMIT 1");
    if ($q) {
        $q->bind_param('s', $teacherEmail);
        $q->execute();
        $row = $q->get_result()->fetch_assoc();
        $q->close();
        $teacherId = (int)($row['id'] ?? 0);
        if ($teacherId > 0) {
            $_SESSION['teacher_id'] = $teacherId;
        }
    }
}
$teacherName = (string)($_SESSION['teacher_name'] ?? $teacherEmail);

$assignments = [];
$studentsByAssignment = [];
$stmt = $conn->prepare("
    SELECT ta.id AS assignment_id, ta.class_id, ta.subject_id, c.class AS class_name, s.subject_name
    FROM teacher_assignments ta
    JOIN classes c ON c.id = ta.class_id
    JOIN subjects s ON s.id = ta.subject_id
    WHERE ta.teacher_id = ?
    ORDER BY c.class ASC, s.subject_name ASC
");
if ($stmt) {
    $stmt->bind_param('i', $teacherId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $assignments[] = $row;
        $aid = (int)$row['assignment_id'];
        $studentsByAssignment[$aid] = [];
        $streamRule = stream_get_subject_rule(
            $conn,
            (int)($row['class_id'] ?? 0),
            (int)($row['subject_id'] ?? 0),
            (string)($row['subject_name'] ?? '')
        );
        $s = $conn->prepare("SELECT id, StudentId, student_name, class, group_stream FROM students WHERE class = ? ORDER BY student_name ASC");
        if ($s) {
            $className = (string)$row['class_name'];
            $s->bind_param('s', $className);
            $s->execute();
            $sr = $s->get_result();
            while ($st = $sr->fetch_assoc()) {
                if (!stream_student_allowed((string)($st['group_stream'] ?? ''), $streamRule)) {
                    continue;
                }
                $studentsByAssignment[$aid][] = $st;
            }
            $s->close();
        }
    }
    $stmt->close();
}

$flashType = '';
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_discipline']) && auth_can('discipline', 'create')) {
    $aid = (int)($_POST['assignment_id'] ?? 0);
    $studentId = (int)($_POST['student_id'] ?? 0);
    $incidentDate = trim((string)($_POST['incident_date'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'warning'));
    $severity = trim((string)($_POST['severity'] ?? 'medium'));
    $title = trim((string)($_POST['title'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));

    if ($aid <= 0 || $studentId <= 0 || $incidentDate === '' || $title === '') {
        $flashType = 'danger';
        $flashMessage = 'Please complete all required fields.';
    } else {
        $classId = 0;
        foreach ($assignments as $a) {
            if ((int)$a['assignment_id'] === $aid) {
                $classId = (int)$a['class_id'];
                break;
            }
        }
        if ($classId <= 0) {
            $flashType = 'danger';
            $flashMessage = 'Invalid assignment selected.';
        } else {
            $ins = $conn->prepare("
                INSERT INTO discipline_records
                (student_id, class_id, category, severity, incident_date, title, notes, status, recorded_by_role, recorded_by_id, recorded_by_name, report_to_principal)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'open', 'teacher', ?, ?, 1)
            ");
            if ($ins) {
                $ins->bind_param('iisssssis', $studentId, $classId, $category, $severity, $incidentDate, $title, $notes, $teacherId, $teacherName);
                $ok = $ins->execute();
                $newId = (int)$ins->insert_id;
                $ins->close();
                if ($ok) {
                    auth_audit_log($conn, 'create', 'discipline', (string)$newId, null, json_encode([
                        'category' => $category,
                        'severity' => $severity,
                        'title' => $title
                    ]));
                    $flashType = 'success';
                    $flashMessage = 'Discipline report submitted to Principal.';
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Failed to save discipline report.';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_my_action']) && auth_can('discipline', 'edit')) {
    $disciplineId = (int)($_POST['discipline_id'] ?? 0);
    $actionDate = trim((string)($_POST['action_date'] ?? date('Y-m-d')));
    $actionText = trim((string)($_POST['action_text'] ?? ''));
    if ($disciplineId > 0 && $actionText !== '') {
        $ownership = $conn->prepare("SELECT id FROM discipline_records WHERE id = ? AND recorded_by_role = 'teacher' AND recorded_by_id = ? LIMIT 1");
        if ($ownership) {
            $ownership->bind_param('ii', $disciplineId, $teacherId);
            $ownership->execute();
            $okOwner = (bool)$ownership->get_result()->fetch_assoc();
            $ownership->close();
            if ($okOwner) {
                $ins = $conn->prepare("
                    INSERT INTO discipline_actions (discipline_id, action_date, action_text, action_by_role, action_by_id, action_by_name)
                    VALUES (?, ?, ?, 'teacher', ?, ?)
                ");
                if ($ins) {
                    $ins->bind_param('issis', $disciplineId, $actionDate, $actionText, $teacherId, $teacherName);
                    $ins->execute();
                    $ins->close();
                    auth_audit_log($conn, 'edit', 'discipline', (string)$disciplineId, null, json_encode(['action' => $actionText]));
                    $flashType = 'success';
                    $flashMessage = 'Action history updated.';
                }
            } else {
                $flashType = 'danger';
                $flashMessage = 'You can only update your own discipline records.';
            }
        }
    }
}

$recent = [];
$query = $conn->prepare("
    SELECT d.*, s.student_name, s.StudentId, c.class AS class_name
    FROM discipline_records d
    JOIN students s ON s.id = d.student_id
    JOIN classes c ON c.id = d.class_id
    WHERE d.recorded_by_role = 'teacher' AND d.recorded_by_id = ?
    ORDER BY d.id DESC
    LIMIT 60
");
if ($query) {
    $query->bind_param('i', $teacherId);
    $query->execute();
    $rs = $query->get_result();
    while ($row = $rs->fetch_assoc()) {
        $recent[] = $row;
    }
    $query->close();
}

$actionsMap = [];
if (!empty($recent)) {
    $ids = [];
    foreach ($recent as $r) {
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

$studentsJsonMap = [];
foreach ($studentsByAssignment as $aid => $list) {
    $studentsJsonMap[$aid] = [];
    foreach ($list as $s) {
        $studentsJsonMap[$aid][] = [
            'id' => (int)$s['id'],
            'text' => (string)$s['StudentId'] . ' - ' . (string)$s['student_name']
        ];
    }
}
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Discipline Report</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo h($flashType); ?>"><?php echo h($flashMessage); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Submit Warning / Achievement to Principal</h6></div>
        <div class="card-body">
            <?php if (empty($assignments)): ?>
                <div class="alert alert-warning mb-0">No class/subject assignment found.</div>
            <?php else: ?>
                <form method="post">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Assignment</label>
                            <select class="form-control" name="assignment_id" id="assignment_id" required>
                                <option value="">Select</option>
                                <?php foreach ($assignments as $a): ?>
                                    <option value="<?php echo (int)$a['assignment_id']; ?>"><?php echo h($a['class_name'] . ' - ' . $a['subject_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Student</label>
                            <select class="form-control" name="student_id" id="student_id" required>
                                <option value="">Select student</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Date</label>
                            <input type="date" class="form-control" name="incident_date" value="<?php echo h(date('Y-m-d')); ?>" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Category</label>
                            <select class="form-control" name="category" required>
                                <option value="warning">Warning</option>
                                <option value="achievement">Achievement</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Severity</label>
                            <select class="form-control" name="severity">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Notes</label>
                            <input type="text" class="form-control" name="notes">
                        </div>
                    </div>
                    <button class="btn btn-danger" type="submit" name="submit_discipline">Submit to Principal</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">My Discipline Reports</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Category</th>
                        <th>Title / Notes</th>
                        <th>Status</th>
                        <th>Action History</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?php echo h($row['incident_date']); ?></td>
                            <td><?php echo h(($row['StudentId'] ?? '') . ' - ' . ($row['student_name'] ?? '')); ?></td>
                            <td><?php echo h($row['class_name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['category'] === 'achievement' ? 'success' : 'warning'; ?>">
                                    <?php echo h(ucfirst((string)$row['category'])); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo h($row['title']); ?></strong>
                                <div class="small text-muted"><?php echo h($row['notes']); ?></div>
                            </td>
                            <td><?php echo h(ucfirst((string)$row['status'])); ?></td>
                            <td style="min-width: 280px;">
                                <form method="post" class="mb-2">
                                    <input type="hidden" name="discipline_id" value="<?php echo (int)$row['id']; ?>">
                                    <div class="input-group input-group-sm mb-1">
                                        <input type="date" name="action_date" value="<?php echo h(date('Y-m-d')); ?>" class="form-control" required>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" name="action_text" placeholder="Add follow-up action" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-success" name="add_my_action" type="submit">Add</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if (!empty($actionsMap[(int)$row['id']])): ?>
                                    <div class="small">
                                        <?php foreach ($actionsMap[(int)$row['id']] as $a): ?>
                                            <div><strong><?php echo h($a['action_date']); ?></strong>: <?php echo h($a['action_text']); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent)): ?>
                        <tr><td colspan="7" class="text-center">No discipline reports yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const disciplineStudentsByAssignment = <?php echo json_encode($studentsJsonMap); ?>;
    (function () {
        const assignment = document.getElementById('assignment_id');
        const student = document.getElementById('student_id');
        if (!assignment || !student) return;
        function renderStudents() {
            const list = disciplineStudentsByAssignment[assignment.value] || [];
            student.innerHTML = '<option value="">Select student</option>';
            list.forEach(function (item) {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.text;
                student.appendChild(opt);
            });
        }
        assignment.addEventListener('change', renderStudents);
        renderStudents();
    })();
</script>

<?php include './partials/footer.php'; ?>
