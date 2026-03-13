<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teachers', 'assign', 'index.php');

include "db.php";

$flashType = '';
$flashMessage = '';

function getIndexColumns(mysqli $conn, string $table, string $indexName): array
{
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME
        FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
        ORDER BY seq_in_index ASC
    ");
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("ss", $table, $indexName);
    $stmt->execute();
    $result = $stmt->get_result();
    $cols = [];
    while ($row = $result->fetch_assoc()) {
        $cols[] = strtolower((string)($row['COLUMN_NAME'] ?? ''));
    }
    $stmt->close();
    return $cols;
}

function indexExists(mysqli $conn, string $table, string $indexName): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ss", $table, $indexName);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
    return $count > 0;
}

// Ensure class-subject mapping table exists.
$conn->query("
    CREATE TABLE IF NOT EXISTS class_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        UNIQUE KEY uniq_class_subject (class_id, subject_id)
    )
");

// Ensure legacy assignments uniqueness allows multiple subjects per teacher/class.
$oldCols = getIndexColumns($conn, 'assignments', 'unique_teacher_class');
if ($oldCols === ['teacher_id', 'class_id']) {
    $conn->query("ALTER TABLE assignments DROP INDEX unique_teacher_class");
}

if (!indexExists($conn, 'assignments', 'uniq_class_subject')) {
    // Remove duplicates before adding unique(class_id, subject).
    $conn->query("
        DELETE a1
        FROM assignments a1
        INNER JOIN assignments a2
            ON a1.class_id = a2.class_id
           AND a1.subject = a2.subject
           AND a1.id < a2.id
    ");
    $conn->query("ALTER TABLE assignments ADD UNIQUE KEY uniq_class_subject (class_id, subject)");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assignment'])) {
    auth_require_permission('teachers', 'assign', 'index.php');
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $assignmentType = trim($_POST['assignment_type'] ?? '');
    $subjectId = (int)($_POST['subject_id'] ?? 0);

    if ($teacherId <= 0 || $classId <= 0 || !in_array($assignmentType, ['class_teacher', 'subject_teacher'], true)) {
        $flashType = 'danger';
        $flashMessage = 'Please select valid teacher, class, and assignment type.';
    } else {
        $classStmt = $conn->prepare("SELECT class, academic_year, class_teacher_id FROM classes WHERE id = ? LIMIT 1");
        $classRow = null;
        if ($classStmt) {
            $classStmt->bind_param("i", $classId);
            $classStmt->execute();
            $classRow = $classStmt->get_result()->fetch_assoc();
            $classStmt->close();
        }

        if (!$classRow) {
            $flashType = 'danger';
            $flashMessage = 'Selected class not found.';
        } else {
            $className = (string)($classRow['class'] ?? '');
            $oldClassTeacherId = (int)($classRow['class_teacher_id'] ?? 0);

            if ($assignmentType === 'class_teacher') {
                // Ensure one primary class-teacher mapping per teacher.
                $clearOld = $conn->prepare("UPDATE classes SET class_teacher_id = NULL WHERE class_teacher_id = ? AND id <> ?");
                if ($clearOld) {
                    $clearOld->bind_param("ii", $teacherId, $classId);
                    $clearOld->execute();
                    $clearOld->close();
                }

                $setClassTeacher = $conn->prepare("UPDATE classes SET class_teacher_id = ? WHERE id = ?");
                if ($setClassTeacher) {
                    $setClassTeacher->bind_param("ii", $teacherId, $classId);
                    $setClassTeacher->execute();
                    $setClassTeacher->close();
                }

                // Keep existing app logic functional where class_assigned is used.
                $setTeacherClass = $conn->prepare("UPDATE teachers SET class_assigned = ? WHERE id = ?");
                if ($setTeacherClass) {
                    $setTeacherClass->bind_param("si", $className, $teacherId);
                    $setTeacherClass->execute();
                    $setTeacherClass->close();
                }

                $flashType = 'success';
                $flashMessage = 'Teacher set as Class Teacher successfully.';
                auth_audit_log_change(
                    $conn,
                    'assign',
                    'class_teacher',
                    (string)$classId,
                    ['teacher_id' => $oldClassTeacherId, 'class' => $className],
                    ['teacher_id' => $teacherId, 'class' => $className]
                );
            } else {
                if ($subjectId <= 0) {
                    $flashType = 'danger';
                    $flashMessage = 'Please select a subject for Subject Teacher assignment.';
                } else {
                    $csCheck = $conn->prepare("SELECT id FROM class_subjects WHERE class_id = ? AND subject_id = ? LIMIT 1");
                    $isSubjectMapped = false;
                    if ($csCheck) {
                        $csCheck->bind_param("ii", $classId, $subjectId);
                        $csCheck->execute();
                        $isSubjectMapped = (bool)$csCheck->get_result()->fetch_assoc();
                        $csCheck->close();
                    }

                    if (!$isSubjectMapped) {
                        $flashType = 'danger';
                        $flashMessage = 'Selected subject is not mapped to this class. Add it in Class Subjects first.';
                    } else {
                    $subjectStmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ? LIMIT 1");
                    $subjectRow = null;
                    if ($subjectStmt) {
                        $subjectStmt->bind_param("i", $subjectId);
                        $subjectStmt->execute();
                        $subjectRow = $subjectStmt->get_result()->fetch_assoc();
                        $subjectStmt->close();
                    }

                    if (!$subjectRow) {
                        $flashType = 'danger';
                        $flashMessage = 'Selected subject not found.';
                    } else {
                        $subjectName = (string)($subjectRow['subject_name'] ?? '');

                        $oldSubjectTeacherId = 0;
                        $oldTa = $conn->prepare("SELECT teacher_id FROM teacher_assignments WHERE class_id = ? AND subject_id = ? LIMIT 1");
                        if ($oldTa) {
                            $oldTa->bind_param("ii", $classId, $subjectId);
                            $oldTa->execute();
                            $oldSubjectTeacherId = (int)($oldTa->get_result()->fetch_assoc()['teacher_id'] ?? 0);
                            $oldTa->close();
                        }

                        // Upsert into teacher_assignments (normalized).
                        $taCheck = $conn->prepare("SELECT id FROM teacher_assignments WHERE class_id = ? AND subject_id = ? LIMIT 1");
                        $existingTaId = 0;
                        if ($taCheck) {
                            $taCheck->bind_param("ii", $classId, $subjectId);
                            $taCheck->execute();
                            $taRow = $taCheck->get_result()->fetch_assoc();
                            $existingTaId = (int)($taRow['id'] ?? 0);
                            $taCheck->close();
                        }

                        if ($existingTaId > 0) {
                            $taUpdate = $conn->prepare("UPDATE teacher_assignments SET teacher_id = ? WHERE id = ?");
                            if ($taUpdate) {
                                $taUpdate->bind_param("ii", $teacherId, $existingTaId);
                                $taUpdate->execute();
                                $taUpdate->close();
                            }
                        } else {
                            $taInsert = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, class_id, subject_id) VALUES (?, ?, ?)");
                            if ($taInsert) {
                                $taInsert->bind_param("iii", $teacherId, $classId, $subjectId);
                                $taInsert->execute();
                                $taInsert->close();
                            }
                        }

                        // Upsert into assignments (used by upload/view teacher panel).
                        $aCheck = $conn->prepare("SELECT id FROM assignments WHERE class_id = ? AND subject = ? LIMIT 1");
                        $existingAId = 0;
                        if ($aCheck) {
                            $aCheck->bind_param("is", $classId, $subjectName);
                            $aCheck->execute();
                            $aRow = $aCheck->get_result()->fetch_assoc();
                            $existingAId = (int)($aRow['id'] ?? 0);
                            $aCheck->close();
                        }

                        if ($existingAId > 0) {
                            $aUpdate = $conn->prepare("UPDATE assignments SET teacher_id = ? WHERE id = ?");
                            if ($aUpdate) {
                                $aUpdate->bind_param("ii", $teacherId, $existingAId);
                                $aUpdate->execute();
                                $aUpdate->close();
                            }
                        } else {
                            $aInsert = $conn->prepare("INSERT INTO assignments (teacher_id, class_id, subject) VALUES (?, ?, ?)");
                            if ($aInsert) {
                                $aInsert->bind_param("iis", $teacherId, $classId, $subjectName);
                                try {
                                    $aInsert->execute();
                                } catch (mysqli_sql_exception $e) {
                                    if ((int)$e->getCode() === 1062) {
                                        // If duplicate exists under any unique key, update assignment owner.
                                        $fallback = $conn->prepare("UPDATE assignments SET teacher_id = ? WHERE class_id = ? AND subject = ?");
                                        if ($fallback) {
                                            $fallback->bind_param("iis", $teacherId, $classId, $subjectName);
                                            $fallback->execute();
                                            $fallback->close();
                                        }
                                    } else {
                                        throw $e;
                                    }
                                }
                                $aInsert->close();
                            }
                        }

                        $flashType = 'success';
                        $flashMessage = 'Teacher set as Subject Teacher successfully.';
                        auth_audit_log_change(
                            $conn,
                            'assign',
                            'subject_teacher',
                            (string)$classId,
                            ['teacher_id' => $oldSubjectTeacherId, 'subject_id' => $subjectId, 'subject' => $subjectName],
                            ['teacher_id' => $teacherId, 'subject_id' => $subjectId, 'subject' => $subjectName]
                        );
                    }
                }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_class_teacher'])) {
    auth_require_permission('teachers', 'assign', 'index.php');
    $classId = (int)($_POST['class_id'] ?? 0);

    if ($classId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid class selected.';
    } else {
        $fetch = $conn->prepare("SELECT class, class_teacher_id FROM classes WHERE id = ? LIMIT 1");
        $className = '';
        $teacherId = 0;
        if ($fetch) {
            $fetch->bind_param("i", $classId);
            $fetch->execute();
            $row = $fetch->get_result()->fetch_assoc() ?: [];
            $className = (string)($row['class'] ?? '');
            $teacherId = (int)($row['class_teacher_id'] ?? 0);
            $fetch->close();
        }

        $stmt = $conn->prepare("UPDATE classes SET class_teacher_id = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $stmt->close();
        }

        if ($teacherId > 0 && $className !== '') {
            $clr = $conn->prepare("UPDATE teachers SET class_assigned = '' WHERE id = ? AND class_assigned = ?");
            if ($clr) {
                $clr->bind_param("is", $teacherId, $className);
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
    auth_require_permission('teachers', 'assign', 'index.php');
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
            $info->bind_param("i", $assignmentId);
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
                $delTA->bind_param("i", $assignmentId);
                $delTA->execute();
                $delTA->close();
            }

            $delA = $conn->prepare("DELETE FROM assignments WHERE class_id = ? AND subject = ?");
            if ($delA) {
                $delA->bind_param("is", $classId, $subjectName);
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
$teachersStmt = $conn->prepare("SELECT id, name, email, subject, class_assigned FROM teachers ORDER BY name ASC");
if ($teachersStmt) {
    $teachersStmt->execute();
    $res = $teachersStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $teachers[] = $row;
    }
    $teachersStmt->close();
}

$classes = [];
$classesStmt = $conn->prepare("SELECT id, class, academic_year, class_teacher_id FROM classes ORDER BY academic_year DESC, class ASC");
if ($classesStmt) {
    $classesStmt->execute();
    $res = $classesStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $classes[] = $row;
    }
    $classesStmt->close();
}

$subjects = [];
$subjectsStmt = $conn->prepare("SELECT id, subject_name FROM subjects ORDER BY subject_name ASC");
if ($subjectsStmt) {
    $subjectsStmt->execute();
    $res = $subjectsStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row;
    }
    $subjectsStmt->close();
}

$classSubjects = [];
$classSubjectsStmt = $conn->query("
    SELECT cs.class_id, s.id AS subject_id, s.subject_name
    FROM class_subjects cs
    JOIN subjects s ON s.id = cs.subject_id
    ORDER BY s.subject_name ASC
");
if ($classSubjectsStmt) {
    while ($row = $classSubjectsStmt->fetch_assoc()) {
        $cid = (int)$row['class_id'];
        if (!isset($classSubjects[$cid])) {
            $classSubjects[$cid] = [];
        }
        $classSubjects[$cid][] = [
            'id' => (int)$row['subject_id'],
            'name' => (string)$row['subject_name']
        ];
    }
}

$classTeacherAssignments = [];
$qClassTeachers = $conn->query("
    SELECT c.id AS class_id, c.class, c.academic_year, t.id AS teacher_id, t.name AS teacher_name, t.email AS teacher_email
    FROM classes c
    LEFT JOIN teachers t ON t.id = c.class_teacher_id
    ORDER BY c.academic_year DESC, c.class ASC
");
while ($row = $qClassTeachers->fetch_assoc()) {
    $classTeacherAssignments[] = $row;
}

$subjectTeacherAssignments = [];
$qSubjectTeachers = $conn->query("
    SELECT ta.id, c.class, c.academic_year, s.subject_name, t.name AS teacher_name, t.email AS teacher_email
    FROM teacher_assignments ta
    JOIN classes c ON c.id = ta.class_id
    JOIN subjects s ON s.id = ta.subject_id
    JOIN teachers t ON t.id = ta.teacher_id
    ORDER BY c.academic_year DESC, c.class ASC, s.subject_name ASC
");
while ($row = $qSubjectTeachers->fetch_assoc()) {
    $subjectTeacherAssignments[] = $row;
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
foreach ($subjectTeacherAssignments as $row) {
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
    $subjectTeacherAssignments = array_values(array_filter($subjectTeacherAssignments, function (array $row) use ($stSearch): bool {
        $haystack = implode(' ', [
            (string)($row['class'] ?? ''),
            (string)($row['academic_year'] ?? ''),
            (string)($row['subject_name'] ?? ''),
            (string)($row['teacher_name'] ?? ''),
            (string)($row['teacher_email'] ?? ''),
        ]);
        return stripos($haystack, $stSearch) !== false;
    }));
}

if ($stTeacherFilter !== '') {
    $subjectTeacherAssignments = array_values(array_filter($subjectTeacherAssignments, function (array $row) use ($stTeacherFilter): bool {
        return strcasecmp((string)($row['teacher_name'] ?? ''), $stTeacherFilter) === 0;
    }));
}

$perPage = 15;
$ctPage = max(1, (int)($_GET['ct_page'] ?? 1));
$stPage = max(1, (int)($_GET['st_page'] ?? 1));

$ctTotal = count($classTeacherAssignments);
$stTotal = count($subjectTeacherAssignments);
$ctPages = max(1, (int)ceil($ctTotal / $perPage));
$stPages = max(1, (int)ceil($stTotal / $perPage));
$ctPage = min($ctPage, $ctPages);
$stPage = min($stPage, $stPages);

$classTeacherAssignmentsPage = array_slice($classTeacherAssignments, ($ctPage - 1) * $perPage, $perPage);
$subjectTeacherAssignmentsPage = array_slice($subjectTeacherAssignments, ($stPage - 1) * $perPage, $perPage);

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 text-gray-800 mb-0">Teacher Role Assignment</h1>
        <a class="btn btn-secondary btn-sm" href="teacher_detail.php">Back to Teachers</a>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Set Teacher as Class Teacher or Subject Teacher</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Teacher</label>
                        <select name="teacher_id" class="form-control" required>
                            <option value="">Select teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo (int)$teacher['id']; ?>">
                                    <?php echo htmlspecialchars((string)$teacher['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo htmlspecialchars((string)$teacher['email'], ENT_QUOTES, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Class</label>
                        <select name="class_id" class="form-control" id="class_id_select" required>
                            <option value="">Select class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo (int)$class['id']; ?>">
                                    <?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo htmlspecialchars((string)($class['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Assignment Type</label>
                        <select name="assignment_type" id="assignment_type" class="form-control" required>
                            <option value="class_teacher">Class Teacher</option>
                            <option value="subject_teacher">Subject Teacher</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4" id="subject_block" style="display:none;">
                        <label>Subject (required for Subject Teacher)</label>
                        <select name="subject_id" class="form-control" id="subject_id_select">
                            <option value="">Select subject</option>
                        </select>
                        <small class="text-muted d-block mt-1" id="subject_help" style="display:none;">
                            No subjects mapped for this class. Please add in Class Subjects.
                        </small>
                    </div>
                </div>
                <button type="submit" name="save_assignment" class="btn btn-primary">Save Assignment</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Current Class Teacher Assignments</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
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
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Academic Year</th>
                            <th>Class Teacher</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($classTeacherAssignmentsPage)): ?>
                            <?php foreach ($classTeacherAssignmentsPage as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$row['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['teacher_name'] ?? 'Not Assigned'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['teacher_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ((int)($row['teacher_id'] ?? 0) > 0): ?>
                                            <form method="POST" class="m-0" onsubmit="return confirm('Remove class teacher assignment?');">
                                                <input type="hidden" name="class_id" value="<?php echo (int)$row['class_id']; ?>">
                                                <button type="submit" name="remove_class_teacher" class="btn btn-sm btn-danger">Remove</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
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
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Current Subject Teacher Assignments</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
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
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Academic Year</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subjectTeacherAssignmentsPage)): ?>
                            <?php foreach ($subjectTeacherAssignmentsPage as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$row['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['teacher_email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" class="m-0" onsubmit="return confirm('Remove subject teacher assignment?');">
                                            <input type="hidden" name="assignment_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="remove_subject_assignment" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No subject-teacher assignments found.</td></tr>
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
</div>

<script>
(function () {
    var typeSelect = document.getElementById('assignment_type');
    var subjectBlock = document.getElementById('subject_block');
    var classSelect = document.getElementById('class_id_select');
    var subjectSelect = document.getElementById('subject_id_select');
    var subjectHelp = document.getElementById('subject_help');
    var map = <?php echo json_encode($classSubjects); ?>;

    if (!typeSelect || !subjectBlock || !classSelect || !subjectSelect) return;

    function fillSubjects() {
        var classId = classSelect.value;
        var list = map[classId] || [];
        subjectSelect.innerHTML = '<option value="">Select subject</option>';
        if (list.length === 0) {
            if (subjectHelp) subjectHelp.style.display = 'block';
            return;
        }
        if (subjectHelp) subjectHelp.style.display = 'none';
        list.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            subjectSelect.appendChild(opt);
        });
    }

    function toggleSubjectBlock() {
        var show = typeSelect.value === 'subject_teacher';
        subjectBlock.style.display = show ? 'block' : 'none';
        if (show) {
            fillSubjects();
        }
    }

    typeSelect.addEventListener('change', toggleSubjectBlock);
    classSelect.addEventListener('change', fillSubjects);
    toggleSubjectBlock();

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
})();
</script>

<?php
include "./partials/footer.php";
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
