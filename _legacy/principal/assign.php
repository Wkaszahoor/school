<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teachers', 'assign', 'index.php');
include '../db.php';
include './partials/topbar.php';

$flashType = '';
$flashMessage = '';

$conn->query("
    CREATE TABLE IF NOT EXISTS class_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        UNIQUE KEY uniq_class_subject (class_id, subject_id)
    )
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assignment'])) {
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $assignmentType = trim((string)($_POST['assignment_type'] ?? ''));
    $subjectId = (int)($_POST['subject_id'] ?? 0);

    if ($teacherId <= 0 || $classId <= 0 || !in_array($assignmentType, ['class_teacher', 'subject_teacher'], true)) {
        $flashType = 'danger';
        $flashMessage = 'Please select valid teacher, class, and assignment type.';
    } else {
        $classStmt = $conn->prepare("SELECT class, class_teacher_id FROM classes WHERE id = ? LIMIT 1");
        $classRow = null;
        if ($classStmt) {
            $classStmt->bind_param('i', $classId);
            $classStmt->execute();
            $classRow = $classStmt->get_result()->fetch_assoc();
            $classStmt->close();
        }

        if (!$classRow) {
            $flashType = 'danger';
            $flashMessage = 'Selected class not found.';
        } else {
            $className = (string)$classRow['class'];
            $oldClassTeacherId = (int)($classRow['class_teacher_id'] ?? 0);
            if ($assignmentType === 'class_teacher') {
                $clearOld = $conn->prepare("UPDATE classes SET class_teacher_id = NULL WHERE class_teacher_id = ? AND id <> ?");
                if ($clearOld) {
                    $clearOld->bind_param('ii', $teacherId, $classId);
                    $clearOld->execute();
                    $clearOld->close();
                }

                $setClassTeacher = $conn->prepare("UPDATE classes SET class_teacher_id = ? WHERE id = ?");
                if ($setClassTeacher) {
                    $setClassTeacher->bind_param('ii', $teacherId, $classId);
                    $setClassTeacher->execute();
                    $setClassTeacher->close();
                }

                $setTeacherClass = $conn->prepare("UPDATE teachers SET class_assigned = ? WHERE id = ?");
                if ($setTeacherClass) {
                    $setTeacherClass->bind_param('si', $className, $teacherId);
                    $setTeacherClass->execute();
                    $setTeacherClass->close();
                }

                auth_audit_log_change(
                    $conn,
                    'assign',
                    'class_teacher',
                    (string)$classId,
                    ['teacher_id' => $oldClassTeacherId, 'class' => $className],
                    ['teacher_id' => $teacherId, 'class' => $className]
                );
                $flashType = 'success';
                $flashMessage = 'Teacher assigned as Class Teacher.';
            } else {
                if ($subjectId <= 0) {
                    $flashType = 'danger';
                    $flashMessage = 'Please select a subject.';
                } else {
                    $csCheck = $conn->prepare("SELECT id FROM class_subjects WHERE class_id = ? AND subject_id = ? LIMIT 1");
                    $isMapped = false;
                    if ($csCheck) {
                        $csCheck->bind_param('ii', $classId, $subjectId);
                        $csCheck->execute();
                        $isMapped = (bool)$csCheck->get_result()->fetch_assoc();
                        $csCheck->close();
                    }
                    if (!$isMapped) {
                        $flashType = 'danger';
                        $flashMessage = 'Subject is not mapped to this class.';
                    } else {
                        $subjectName = '';
                        $s = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ? LIMIT 1");
                        if ($s) {
                            $s->bind_param('i', $subjectId);
                            $s->execute();
                            $subjectName = (string)($s->get_result()->fetch_assoc()['subject_name'] ?? '');
                            $s->close();
                        }
                        if ($subjectName === '') {
                            $flashType = 'danger';
                            $flashMessage = 'Subject not found.';
                        } else {
                            $oldSubjectTeacherId = 0;
                            $oldTa = $conn->prepare("SELECT teacher_id FROM teacher_assignments WHERE class_id = ? AND subject_id = ? LIMIT 1");
                            if ($oldTa) {
                                $oldTa->bind_param('ii', $classId, $subjectId);
                                $oldTa->execute();
                                $oldSubjectTeacherId = (int)($oldTa->get_result()->fetch_assoc()['teacher_id'] ?? 0);
                                $oldTa->close();
                            }

                            $upsertTA = $conn->prepare("
                                INSERT INTO teacher_assignments (teacher_id, class_id, subject_id)
                                VALUES (?, ?, ?)
                                ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)
                            ");
                            if ($upsertTA) {
                                $upsertTA->bind_param('iii', $teacherId, $classId, $subjectId);
                                $upsertTA->execute();
                                $upsertTA->close();
                            }

                            $existing = $conn->prepare("SELECT id FROM assignments WHERE class_id = ? AND subject = ? LIMIT 1");
                            $assignmentId = 0;
                            if ($existing) {
                                $existing->bind_param('is', $classId, $subjectName);
                                $existing->execute();
                                $assignmentId = (int)($existing->get_result()->fetch_assoc()['id'] ?? 0);
                                $existing->close();
                            }
                            if ($assignmentId > 0) {
                                $upd = $conn->prepare("UPDATE assignments SET teacher_id = ? WHERE id = ?");
                                if ($upd) {
                                    $upd->bind_param('ii', $teacherId, $assignmentId);
                                    $upd->execute();
                                    $upd->close();
                                }
                            } else {
                                $ins = $conn->prepare("INSERT INTO assignments (teacher_id, class_id, subject) VALUES (?, ?, ?)");
                                if ($ins) {
                                    $ins->bind_param('iis', $teacherId, $classId, $subjectName);
                                    $ins->execute();
                                    $ins->close();
                                }
                            }

                            auth_audit_log_change(
                                $conn,
                                'assign',
                                'subject_teacher',
                                (string)$classId,
                                ['teacher_id' => $oldSubjectTeacherId, 'subject_id' => $subjectId, 'subject' => $subjectName],
                                ['teacher_id' => $teacherId, 'subject_id' => $subjectId, 'subject' => $subjectName]
                            );
                            $flashType = 'success';
                            $flashMessage = 'Teacher assigned as Subject Teacher.';
                        }
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_class_teacher'])) {
    $classId = (int)($_POST['class_id'] ?? 0);
    if ($classId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid class selected.';
    } else {
        $fetch = $conn->prepare("SELECT class, class_teacher_id FROM classes WHERE id = ? LIMIT 1");
        $className = '';
        $teacherId = 0;
        if ($fetch) {
            $fetch->bind_param('i', $classId);
            $fetch->execute();
            $row = $fetch->get_result()->fetch_assoc() ?: [];
            $className = (string)($row['class'] ?? '');
            $teacherId = (int)($row['class_teacher_id'] ?? 0);
            $fetch->close();
        }

        $stmt = $conn->prepare("UPDATE classes SET class_teacher_id = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $classId);
            $stmt->execute();
            $stmt->close();
        }

        if ($teacherId > 0 && $className !== '') {
            $clr = $conn->prepare("UPDATE teachers SET class_assigned = '' WHERE id = ? AND class_assigned = ?");
            if ($clr) {
                $clr->bind_param('is', $teacherId, $className);
                $clr->execute();
                $clr->close();
            }
        }

        auth_audit_log_change(
            $conn,
            'assign',
            'remove_class_teacher',
            (string)$classId,
            ['teacher_id' => $teacherId, 'class' => $className],
            ['teacher_id' => null, 'class' => $className]
        );
        $flashType = 'success';
        $flashMessage = 'Class teacher assignment removed.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_subject_assignment'])) {
    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    if ($assignmentId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid subject assignment selected.';
    } else {
        $info = $conn->prepare("
            SELECT ta.id, ta.class_id, ta.teacher_id, s.subject_name
            FROM teacher_assignments ta
            JOIN subjects s ON s.id = ta.subject_id
            WHERE ta.id = ?
            LIMIT 1
        ");
        $row = null;
        if ($info) {
            $info->bind_param('i', $assignmentId);
            $info->execute();
            $row = $info->get_result()->fetch_assoc() ?: null;
            $info->close();
        }

        if (!$row) {
            $flashType = 'danger';
            $flashMessage = 'Subject assignment not found.';
        } else {
            $classId = (int)$row['class_id'];
            $teacherId = (int)$row['teacher_id'];
            $subjectName = (string)$row['subject_name'];

            $delTA = $conn->prepare("DELETE FROM teacher_assignments WHERE id = ?");
            if ($delTA) {
                $delTA->bind_param('i', $assignmentId);
                $delTA->execute();
                $delTA->close();
            }

            $delA = $conn->prepare("DELETE FROM assignments WHERE class_id = ? AND subject = ?");
            if ($delA) {
                $delA->bind_param('is', $classId, $subjectName);
                $delA->execute();
                $delA->close();
            }

            auth_audit_log_change(
                $conn,
                'assign',
                'remove_subject_teacher',
                (string)$classId,
                ['teacher_id' => $teacherId, 'subject' => $subjectName],
                ['teacher_id' => null, 'subject' => $subjectName]
            );
            $flashType = 'success';
            $flashMessage = 'Subject teacher assignment removed.';
        }
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
$subjects = [];
$res = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $subjects[] = $r;
    }
}
$classSubjects = [];
$res = $conn->query("SELECT cs.class_id, s.id AS subject_id, s.subject_name FROM class_subjects cs JOIN subjects s ON s.id = cs.subject_id ORDER BY s.subject_name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $cid = (int)$r['class_id'];
        if (!isset($classSubjects[$cid])) {
            $classSubjects[$cid] = [];
        }
        $classSubjects[$cid][] = ['id' => (int)$r['subject_id'], 'name' => (string)$r['subject_name']];
    }
}

$classTeacherAssignments = [];
$res = $conn->query("
    SELECT c.id AS class_id, c.class, c.academic_year, t.id AS teacher_id, t.name AS teacher_name, t.email AS teacher_email
    FROM classes c
    LEFT JOIN teachers t ON t.id = c.class_teacher_id
    ORDER BY c.academic_year DESC, c.class ASC
");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $classTeacherAssignments[] = $r;
    }
}

$subjectAssignments = [];
$res = $conn->query("
    SELECT ta.id, c.id AS class_id, c.class, c.academic_year, s.subject_name, t.name AS teacher_name
    FROM teacher_assignments ta
    JOIN classes c ON c.id = ta.class_id
    JOIN subjects s ON s.id = ta.subject_id
    JOIN teachers t ON t.id = ta.teacher_id
    ORDER BY c.academic_year DESC, c.class ASC, s.subject_name ASC
");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $subjectAssignments[] = $r;
    }
}


$ctSearch = trim((string)($_GET['ct_search'] ?? ''));
$stSearch = trim((string)($_GET['st_search'] ?? ''));
$ctTeacherFilter = trim((string)($_GET['ct_teacher'] ?? ''));
$stTeacherFilter = trim((string)($_GET['st_teacher'] ?? ''));

$ctTeacherOptions = [];
foreach ($classTeacherAssignments as $row) {
    $tn = trim((string)($row['teacher_name'] ?? ''));
    if ($tn !== '') {
        $ctTeacherOptions[$tn] = $tn;
    }
}
ksort($ctTeacherOptions);

$stTeacherOptions = [];
foreach ($subjectAssignments as $row) {
    $tn = trim((string)($row['teacher_name'] ?? ''));
    if ($tn !== '') {
        $stTeacherOptions[$tn] = $tn;
    }
}
ksort($stTeacherOptions);

if ($ctSearch !== '') {
    $classTeacherAssignments = array_values(array_filter($classTeacherAssignments, function (array $row) use ($ctSearch): bool {
        $haystack = implode(' ', [
            (string)($row['class'] ?? ''),
            (string)($row['academic_year'] ?? ''),
            (string)($row['teacher_name'] ?? ''),
            (string)($row['teacher_email'] ?? ''),
        ]);
        return stripos($haystack, $ctSearch) !== false;
    }));
}

if ($ctTeacherFilter !== '') {
    $classTeacherAssignments = array_values(array_filter($classTeacherAssignments, function (array $row) use ($ctTeacherFilter): bool {
        return strcasecmp((string)($row['teacher_name'] ?? ''), $ctTeacherFilter) === 0;
    }));
}

if ($stSearch !== '') {
    $subjectAssignments = array_values(array_filter($subjectAssignments, function (array $row) use ($stSearch): bool {
        $haystack = implode(' ', [
            (string)($row['class'] ?? ''),
            (string)($row['academic_year'] ?? ''),
            (string)($row['subject_name'] ?? ''),
            (string)($row['teacher_name'] ?? ''),
        ]);
        return stripos($haystack, $stSearch) !== false;
    }));
}

if ($stTeacherFilter !== '') {
    $subjectAssignments = array_values(array_filter($subjectAssignments, function (array $row) use ($stTeacherFilter): bool {
        return strcasecmp((string)($row['teacher_name'] ?? ''), $stTeacherFilter) === 0;
    }));
}

$perPage = 15;
$ctPage = max(1, (int)($_GET['ct_page'] ?? 1));
$stPage = max(1, (int)($_GET['st_page'] ?? 1));

$ctTotal = count($classTeacherAssignments);
$stTotal = count($subjectAssignments);
$ctPages = max(1, (int)ceil($ctTotal / $perPage));
$stPages = max(1, (int)ceil($stTotal / $perPage));
$ctPage = min($ctPage, $ctPages);
$stPage = min($stPage, $stPages);

$classTeacherAssignmentsPage = array_slice($classTeacherAssignments, ($ctPage - 1) * $perPage, $perPage);
$subjectAssignmentsPage = array_slice($subjectAssignments, ($stPage - 1) * $perPage, $perPage);
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Teacher Management - Assign Teachers</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Teacher Management Pages</h6>
        </div>
        <div class="card-body py-3">
            <a class="btn btn-primary btn-sm mr-2 mb-2" href="assign.php">Assign Teachers</a>
            <a class="btn btn-outline-primary btn-sm mr-2 mb-2" href="teacher_detail.php">Teacher Details</a>
            <a class="btn btn-outline-primary btn-sm mr-2 mb-2" href="create_teacher.php">Create Teacher</a>
            <div class="small text-muted mt-2">
                Teacher profiles are available from the <strong>View Profile</strong> button in Teacher Details.
            </div>
        </div>
    </div>
    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Set Class Teacher / Subject Teacher</h6></div>
        <div class="card-body">
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Teacher</label>
                        <select name="teacher_id" class="form-control" required>
                            <option value="">Select teacher</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)$t['email'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Class-Year</label>
                        <select name="class_id" class="form-control" id="class_id_select" required>
                            <option value="">Select class</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars((string)$c['class'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)($c['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Assignment Type</label>
                        <select name="assignment_type" class="form-control" id="assignment_type_select" required>
                            <option value="class_teacher">Class Teacher</option>
                            <option value="subject_teacher">Subject Teacher</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" id="subject_wrap">
                    <div class="form-group col-md-6">
                        <label>Subject</label>
                        <select name="subject_id" class="form-control" id="subject_id_select">
                            <option value="">Select subject</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <button class="btn btn-primary w-100" type="submit" name="save_assignment">Save Assignment</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Current Class Teacher Assignments</h6></div>
        <div class="card-body table-responsive">
            <form method="get" class="form-inline mb-2" id="ct_filter_form">
                <input type="hidden" name="st_page" value="<?php echo $stPage; ?>">
                <input type="hidden" name="st_search" value="<?php echo htmlspecialchars($stSearch, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="st_teacher" value="<?php echo htmlspecialchars($stTeacherFilter, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="ct_search" id="ct_search_input" class="form-control mr-2" placeholder="Search class teacher table" value="<?php echo htmlspecialchars($ctSearch, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="ct_teacher" id="ct_teacher_select" class="form-control mr-2" list="ct_teacher_options" placeholder="All Teachers" value="<?php echo htmlspecialchars($ctTeacherFilter, ENT_QUOTES, 'UTF-8'); ?>">
                <datalist id="ct_teacher_options">
                    <?php foreach ($ctTeacherOptions as $name): ?>
                        <option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endforeach; ?>
                </datalist>
                <button type="submit" class="btn btn-sm btn-primary mr-2">Search</button>
                <a href="?st_page=<?php echo $stPage; ?>&st_search=<?php echo urlencode($stSearch); ?>&st_teacher=<?php echo urlencode($stTeacherFilter); ?>" class="btn btn-sm btn-secondary">Clear</a>
            </form>
            <table class="table table-bordered">
                <thead><tr><th>Class</th><th>Year</th><th>Teacher</th><th>Email</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($classTeacherAssignmentsPage)): foreach ($classTeacherAssignmentsPage as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$r['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['academic_year'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['teacher_name'] ?? 'Not Assigned'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['teacher_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((int)($r['teacher_id'] ?? 0) > 0): ?>
                                <form method="post" onsubmit="return confirm('Remove class teacher assignment?');" class="m-0">
                                    <input type="hidden" name="class_id" value="<?php echo (int)$r['class_id']; ?>">
                                    <button type="submit" name="remove_class_teacher" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center">No classes found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($ctPages > 1): ?>
                <nav aria-label="Class teacher pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $ctPages; $p++): ?>
                            <li class="page-item <?php echo $p === $ctPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?ct_page=<?php echo $p; ?>&st_page=<?php echo $stPage; ?>&ct_search=<?php echo urlencode($ctSearch); ?>&st_search=<?php echo urlencode($stSearch); ?>&ct_teacher=<?php echo urlencode($ctTeacherFilter); ?>&st_teacher=<?php echo urlencode($stTeacherFilter); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Current Subject Assignments</h6></div>
        <div class="card-body table-responsive">
            <form method="get" class="form-inline mb-2" id="st_filter_form">
                <input type="hidden" name="ct_page" value="<?php echo $ctPage; ?>">
                <input type="hidden" name="ct_search" value="<?php echo htmlspecialchars($ctSearch, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="ct_teacher" value="<?php echo htmlspecialchars($ctTeacherFilter, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="st_search" id="st_search_input" class="form-control mr-2" placeholder="Search subject teacher table" value="<?php echo htmlspecialchars($stSearch, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" name="st_teacher" id="st_teacher_select" class="form-control mr-2" list="st_teacher_options" placeholder="All Teachers" value="<?php echo htmlspecialchars($stTeacherFilter, ENT_QUOTES, 'UTF-8'); ?>">
                <datalist id="st_teacher_options">
                    <?php foreach ($stTeacherOptions as $name): ?>
                        <option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endforeach; ?>
                </datalist>
                <button type="submit" class="btn btn-sm btn-primary mr-2">Search</button>
                <a href="?ct_page=<?php echo $ctPage; ?>&ct_search=<?php echo urlencode($ctSearch); ?>&ct_teacher=<?php echo urlencode($ctTeacherFilter); ?>" class="btn btn-sm btn-secondary">Clear</a>
            </form>
            <table class="table table-bordered">
                <thead><tr><th>Class</th><th>Year</th><th>Subject</th><th>Teacher</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($subjectAssignmentsPage)): foreach ($subjectAssignmentsPage as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$r['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['academic_year'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Remove subject teacher assignment?');" class="m-0">
                                <input type="hidden" name="assignment_id" value="<?php echo (int)$r['id']; ?>">
                                <button type="submit" name="remove_subject_assignment" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center">No subject assignments found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($stPages > 1): ?>
                <nav aria-label="Subject teacher pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $stPages; $p++): ?>
                            <li class="page-item <?php echo $p === $stPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?ct_page=<?php echo $ctPage; ?>&st_page=<?php echo $p; ?>&ct_search=<?php echo urlencode($ctSearch); ?>&st_search=<?php echo urlencode($stSearch); ?>&ct_teacher=<?php echo urlencode($ctTeacherFilter); ?>&st_teacher=<?php echo urlencode($stTeacherFilter); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
var classSubjects = <?php echo json_encode($classSubjects, JSON_UNESCAPED_UNICODE); ?>;
var classSelect = document.getElementById('class_id_select');
var typeSelect = document.getElementById('assignment_type_select');
var subjectWrap = document.getElementById('subject_wrap');
var subjectSelect = document.getElementById('subject_id_select');
function refreshSubjectOptions() {
    var classId = parseInt(classSelect.value || '0', 10);
    var opts = classSubjects[classId] || [];
    subjectSelect.innerHTML = '<option value="">Select subject</option>';
    opts.forEach(function (s) {
        var op = document.createElement('option');
        op.value = String(s.id);
        op.textContent = s.name;
        subjectSelect.appendChild(op);
    });
}
function refreshTypeView() {
    subjectWrap.style.display = (typeSelect.value === 'subject_teacher') ? '' : 'none';
}
classSelect.addEventListener('change', refreshSubjectOptions);
typeSelect.addEventListener('change', refreshTypeView);
refreshSubjectOptions();
refreshTypeView();

function bindLiveFilter(formId, inputId, selectId) {
    var form = document.getElementById(formId);
    var input = document.getElementById(inputId);
    var select = document.getElementById(selectId);
    if (!form) return;
    var timer = null;
    if (input) {
        input.addEventListener('input', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(function () {
                form.submit();
            }, 350);
        });
    }
    if (select) {
        select.addEventListener('change', function () {
            form.submit();
        });
    }
}

bindLiveFilter('ct_filter_form', 'ct_search_input', 'ct_teacher_select');
bindLiveFilter('st_filter_form', 'st_search_input', 'st_teacher_select');
</script>
<?php include './partials/footer.php'; ?>
