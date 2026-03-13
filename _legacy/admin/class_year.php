<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('class_year', 'view', 'index.php');

include "db.php";

$flashType = '';
$flashMessage = '';
$selectedClassId = (int)($_GET['class_id'] ?? 0);
$editClassId = (int)($_GET['edit_id'] ?? 0);
$groupLabels = [
    'general' => 'General',
    'pre_engineering' => 'Pre-Engineering',
    'pre_medical' => 'Pre-Medical',
    'ics' => 'ICS',
    'computer' => 'Computer',
    'biology' => 'Biology',
];

// Ensure required schema for academic year support (compatible with older MySQL/MariaDB).
function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition): void
{
    $dbRes = $conn->query("SELECT DATABASE() AS dbname");
    $dbRow = $dbRes ? $dbRes->fetch_assoc() : null;
    $dbName = $dbRow['dbname'] ?? '';
    if ($dbName === '') {
        return;
    }

    $check = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.columns
        WHERE table_schema = ? AND table_name = ? AND column_name = ?
    ");
    if (!$check) {
        return;
    }
    $check->bind_param("sss", $dbName, $table, $column);
    $check->execute();
    $exists = (int)($check->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $check->close();

    if (!$exists) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

ensureColumnExists($conn, 'classes', 'academic_year', 'VARCHAR(20) NULL AFTER `class`');
ensureColumnExists($conn, 'students', 'academic_year', 'VARCHAR(20) NULL AFTER `class`');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_class'])) {
    auth_require_permission('class_year', 'create', 'index.php');
    $className = trim($_POST['class'] ?? '');
    $academicYear = trim($_POST['academic_year'] ?? '');

    if ($className === '' || $academicYear === '') {
        $flashType = 'danger';
        $flashMessage = 'Class name and academic year are required.';
    } else {
        $check = $conn->prepare("SELECT id FROM classes WHERE class = ? AND academic_year = ? LIMIT 1");
        if ($check) {
            $check->bind_param("ss", $className, $academicYear);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if ($exists) {
                $flashType = 'warning';
                $flashMessage = 'This class already exists for the selected academic year.';
            } else {
                $insert = $conn->prepare("INSERT INTO classes (class, academic_year) VALUES (?, ?)");
                if ($insert) {
                    $insert->bind_param("ss", $className, $academicYear);
                    $insert->execute();
                    $newId = (int)$insert->insert_id;
                    $insert->close();
                    auth_audit_log($conn, 'create', 'class_year', (string)$newId, null, json_encode(['class' => $className, 'academic_year' => $academicYear]));
                    $flashType = 'success';
                    $flashMessage = 'Class created successfully.';
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Failed to create class.';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_class'])) {
    auth_require_permission('class_year', 'edit', 'index.php');
    $classId = (int)($_POST['class_id'] ?? 0);
    $newClassName = trim($_POST['class'] ?? '');
    $newAcademicYear = trim($_POST['academic_year'] ?? '');

    if ($classId <= 0 || $newClassName === '' || $newAcademicYear === '') {
        $flashType = 'danger';
        $flashMessage = 'Class name and academic year are required for edit.';
    } else {
        $oldStmt = $conn->prepare("SELECT class, academic_year FROM classes WHERE id = ? LIMIT 1");
        if ($oldStmt) {
            $oldStmt->bind_param("i", $classId);
            $oldStmt->execute();
            $old = $oldStmt->get_result()->fetch_assoc();
            $oldStmt->close();

            if (!$old) {
                $flashType = 'warning';
                $flashMessage = 'Class record not found.';
            } else {
                $check = $conn->prepare("SELECT id FROM classes WHERE class = ? AND academic_year = ? AND id <> ? LIMIT 1");
                if ($check) {
                    $check->bind_param("ssi", $newClassName, $newAcademicYear, $classId);
                    $check->execute();
                    $duplicate = $check->get_result()->fetch_assoc();
                    $check->close();
                } else {
                    $duplicate = null;
                }

                if ($duplicate) {
                    $flashType = 'warning';
                    $flashMessage = 'Another class-year with same values already exists.';
                } else {
                    $upd = $conn->prepare("UPDATE classes SET class = ?, academic_year = ? WHERE id = ?");
                    if ($upd) {
                        $upd->bind_param("ssi", $newClassName, $newAcademicYear, $classId);
                        $upd->execute();
                        $upd->close();
                    }
                    auth_audit_log(
                        $conn,
                        'edit',
                        'class_year',
                        (string)$classId,
                        json_encode(['class' => (string)$old['class'], 'academic_year' => (string)($old['academic_year'] ?? '')]),
                        json_encode(['class' => $newClassName, 'academic_year' => $newAcademicYear])
                    );

                    // Keep mapped student records in sync with renamed class-year.
                    $updStudents = $conn->prepare("
                        UPDATE students
                        SET class = ?, academic_year = ?
                        WHERE class = ? AND IFNULL(academic_year, '') = IFNULL(?, '')
                    ");
                    if ($updStudents) {
                        $oldClass = (string)$old['class'];
                        $oldYear = (string)($old['academic_year'] ?? '');
                        $updStudents->bind_param("ssss", $newClassName, $newAcademicYear, $oldClass, $oldYear);
                        $updStudents->execute();
                        $updStudents->close();
                    }

                    // Teacher assignment stores class only, so rename class text for assigned teachers.
                    $updTeachers = $conn->prepare("UPDATE teachers SET class_assigned = ? WHERE class_assigned = ?");
                    if ($updTeachers) {
                        $oldClass = (string)$old['class'];
                        $updTeachers->bind_param("ss", $newClassName, $oldClass);
                        $updTeachers->execute();
                        $updTeachers->close();
                    }

                    $flashType = 'success';
                    $flashMessage = 'Class-year updated successfully.';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
    auth_require_permission('class_year', 'delete', 'index.php');
    $classId = (int)($_POST['class_id'] ?? 0);
    $forceDelete = (int)($_POST['force_delete'] ?? 0) === 1;

    if ($classId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid class selected for delete.';
    } else {
        $classStmt = $conn->prepare("SELECT class, academic_year FROM classes WHERE id = ? LIMIT 1");
        if ($classStmt) {
            $classStmt->bind_param("i", $classId);
            $classStmt->execute();
            $classRow = $classStmt->get_result()->fetch_assoc();
            $classStmt->close();
        } else {
            $classRow = null;
        }

        if (!$classRow) {
            $flashType = 'warning';
            $flashMessage = 'Class record not found.';
        } else {
            $teacherCount = 0;
            $dependentCounts = [
                'teacher_assignments' => 0,
                'assignments' => 0,
                'attendance' => 0,
                'exam_results' => 0,
                'class_subjects' => 0,
            ];
            $tcStmt = $conn->prepare("SELECT COUNT(*) AS c FROM teachers WHERE class_assigned = ?");
            if ($tcStmt) {
                $className = (string)$classRow['class'];
                $tcStmt->bind_param("s", $className);
                $tcStmt->execute();
                $teacherCount = (int)($tcStmt->get_result()->fetch_assoc()['c'] ?? 0);
                $tcStmt->close();
            }

            foreach (array_keys($dependentCounts) as $depTable) {
                $depStmt = $conn->prepare("SELECT COUNT(*) AS c FROM `$depTable` WHERE class_id = ?");
                if ($depStmt) {
                    $depStmt->bind_param("i", $classId);
                    $depStmt->execute();
                    $dependentCounts[$depTable] = (int)($depStmt->get_result()->fetch_assoc()['c'] ?? 0);
                    $depStmt->close();
                }
            }
            $hasDependencies = $teacherCount > 0;
            foreach ($dependentCounts as $depCount) {
                if ($depCount > 0) {
                    $hasDependencies = true;
                    break;
                }
            }

            if ($teacherCount > 0 && !$forceDelete) {
                $flashType = 'warning';
                $flashMessage = 'This class has linked records (teachers/assignments/attendance/results). Confirm delete from the alert to continue.';
            } else {
                $conn->begin_transaction();
                try {
                    if ($teacherCount > 0) {
                        $clearTeachers = $conn->prepare("UPDATE teachers SET class_assigned = NULL WHERE class_assigned = ?");
                        if (!$clearTeachers) {
                            throw new Exception('Failed to prepare teacher unassign query: ' . $conn->error);
                        }
                        $className = (string)$classRow['class'];
                        $clearTeachers->bind_param("s", $className);
                        if (!$clearTeachers->execute()) {
                            throw new Exception('Failed to unassign teachers: ' . $clearTeachers->error);
                        }
                        $clearTeachers->close();
                    }

                    // Remove dependent rows referencing this class id first (FK-safe order).
                    foreach (['class_subjects', 'teacher_assignments', 'assignments', 'attendance', 'exam_results'] as $depTable) {
                        $depDel = $conn->prepare("DELETE FROM `$depTable` WHERE class_id = ?");
                        if (!$depDel) {
                            throw new Exception("Failed to prepare delete for {$depTable}: " . $conn->error);
                        }
                        $depDel->bind_param("i", $classId);
                        if (!$depDel->execute()) {
                            throw new Exception("Failed to delete from {$depTable}: " . $depDel->error);
                        }
                        $depDel->close();
                    }

                    $delStmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
                    if (!$delStmt) {
                        throw new Exception('Failed to prepare class delete query: ' . $conn->error);
                    }
                    $delStmt->bind_param("i", $classId);
                    if (!$delStmt->execute()) {
                        throw new Exception('Failed to delete class-year: ' . $delStmt->error);
                    }
                    $affected = (int)$delStmt->affected_rows;
                    $delStmt->close();

                    if ($affected <= 0) {
                        throw new Exception('Class-year not deleted (record may already be removed).');
                    }

                    $conn->commit();
                    auth_audit_log_change(
                        $conn,
                        'delete',
                        'class_year',
                        (string)$classId,
                        array_merge($classRow, ['dependencies' => $dependentCounts, 'teacher_count' => $teacherCount]),
                        ['deleted' => true]
                    );
                    $flashType = 'success';
                    $flashMessage = 'Class-year deleted successfully.';
                } catch (Throwable $e) {
                    $conn->rollback();
                    $flashType = 'danger';
                    $flashMessage = 'Delete failed: ' . $e->getMessage();
                }
            }
        }
    }
}

$editRecord = null;
if ($editClassId > 0) {
    $editStmt = $conn->prepare("SELECT id, class, academic_year FROM classes WHERE id = ? LIMIT 1");
    if ($editStmt) {
        $editStmt->bind_param("i", $editClassId);
        $editStmt->execute();
        $editRecord = $editStmt->get_result()->fetch_assoc();
        $editStmt->close();
    }
}

$classes = [];
$qClasses = $conn->query("
    SELECT c.id, c.class, c.academic_year,
           COUNT(DISTINCT s.id) AS student_count,
           COUNT(DISTINCT t.id) AS teacher_assigned_count
    FROM classes c
    LEFT JOIN students s
        ON s.class = c.class
        AND IFNULL(s.academic_year, '') = IFNULL(c.academic_year, '')
    LEFT JOIN teachers t
        ON t.class_assigned = c.class
    GROUP BY c.id, c.class, c.academic_year
    ORDER BY c.academic_year DESC, c.class ASC
");
while ($row = $qClasses->fetch_assoc()) {
    $classes[] = $row;
}

$students = [];
$selectedClassLabel = '';
if ($selectedClassId > 0) {
    $selectedStmt = $conn->prepare("SELECT class, academic_year FROM classes WHERE id = ? LIMIT 1");
    if ($selectedStmt) {
        $selectedStmt->bind_param("i", $selectedClassId);
        $selectedStmt->execute();
        $selected = $selectedStmt->get_result()->fetch_assoc();
        $selectedStmt->close();

        if ($selected) {
            $selectedClassLabel = $selected['class'] . ' (' . ($selected['academic_year'] ?? '') . ')';

            $studentsStmt = $conn->prepare("
                SELECT id, StudentId, student_name, class, academic_year, group_stream, email, phone, created_at
                FROM students
                WHERE class = ? AND IFNULL(academic_year, '') = IFNULL(?, '')
                ORDER BY student_name ASC
            ");
            if ($studentsStmt) {
                $studentsStmt->bind_param("ss", $selected['class'], $selected['academic_year']);
                $studentsStmt->execute();
                $resStudents = $studentsStmt->get_result();
                while ($row = $resStudents->fetch_assoc()) {
                    $students[] = $row;
                }
                $studentsStmt->close();
            }
        }
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Class & Academic Year</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Create Class for Academic Year</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="form-row">
                <div class="form-group col-md-5">
                    <label>Class Name</label>
                    <input type="text" name="class" class="form-control" placeholder="e.g. Grade 10 A" required>
                </div>
                <div class="form-group col-md-5">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025-2026" required>
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="submit" name="create_class" class="btn btn-primary w-100">Create</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editRecord): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Class-Year</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="form-row">
                    <input type="hidden" name="class_id" value="<?php echo (int)$editRecord['id']; ?>">
                    <div class="form-group col-md-5">
                        <label>Class Name</label>
                        <input type="text" name="class" class="form-control" value="<?php echo htmlspecialchars((string)$editRecord['class'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="form-group col-md-5">
                        <label>Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars((string)($editRecord['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" name="edit_class" class="btn btn-warning w-100">Update</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Class-Year Records</h6>
        </div>
        <div class="card-body">
            <div class="form-row mb-3">
                <div class="form-group col-md-4">
                    <label for="class_year_search_class">Search by Class</label>
                    <input type="text" id="class_year_search_class" class="form-control" placeholder="Type class name">
                </div>
                <div class="form-group col-md-4">
                    <label for="class_year_search_year">Search by Academic Year</label>
                    <input type="text" id="class_year_search_year" class="form-control" placeholder="Type academic year">
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="button" id="class_year_search_clear" class="btn btn-secondary w-100">Clear</button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="class_year_table" class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Class</th>
                            <th>Academic Year</th>
                            <th>Students</th>
                            <th>Assigned Teachers</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($classes)): ?>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><?php echo (int)$class['id']; ?></td>
                                    <td><?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($class['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int)$class['student_count']; ?></td>
                                    <td><?php echo (int)$class['teacher_assigned_count']; ?></td>
                                    <td>
                                        <a class="btn btn-info btn-sm" href="class_year.php?class_id=<?php echo (int)$class['id']; ?>">View Students</a>
                                        <a class="btn btn-secondary btn-sm" href="class_subjects.php?class_id=<?php echo (int)$class['id']; ?>">Subjects</a>
                                        <a class="btn btn-warning btn-sm" href="class_year.php?edit_id=<?php echo (int)$class['id']; ?>">Edit</a>
                                        <form method="POST" action="" class="d-inline delete-class-form" data-class-name="<?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?>" data-teacher-count="<?php echo (int)$class['teacher_assigned_count']; ?>">
                                            <input type="hidden" name="class_id" value="<?php echo (int)$class['id']; ?>">
                                            <input type="hidden" name="force_delete" value="0">
                                            <button type="submit" name="delete_class" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="no-data-row">
                                <td colspan="6" class="text-center">No classes found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Students in: <?php echo $selectedClassLabel !== '' ? htmlspecialchars($selectedClassLabel, ENT_QUOTES, 'UTF-8') : 'Select a class above'; ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="form-row mb-3">
                <div class="form-group col-md-4">
                    <label for="student_search_name">Search by Student Name</label>
                    <input type="text" id="student_search_name" class="form-control" placeholder="Type student name">
                </div>
                <div class="form-group col-md-4">
                    <label for="student_search_class">Search by Class</label>
                    <input type="text" id="student_search_class" class="form-control" placeholder="Type class name">
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="button" id="student_search_clear" class="btn btn-secondary w-100">Clear</button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="class_students_table" class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Name</th>
                          <th>Class</th>
                          <th>Academic Year</th>
                          <th>Group</th>
                          <th>Email</th>
                          <th>Phone</th>
                          <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo (int)$student['id']; ?></td>
                                    <td><?php echo htmlspecialchars((string)$student['StudentId'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$student['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                      <td><?php echo htmlspecialchars((string)$student['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                                      <td><?php echo htmlspecialchars((string)($student['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                      <td><?php
                                          $stream = (string)($student['group_stream'] ?? '');
                                          echo htmlspecialchars($groupLabels[$stream] ?? $stream, ENT_QUOTES, 'UTF-8');
                                      ?></td>
                                      <td><?php echo htmlspecialchars((string)($student['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                      <td><?php echo htmlspecialchars((string)($student['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                      <td><?php echo htmlspecialchars((string)($student['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="no-data-row">
                                <td colspan="9" class="text-center">No students found for this class-year.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.delete-class-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var teacherCount = parseInt(form.getAttribute('data-teacher-count') || '0', 10);
        var className = form.getAttribute('data-class-name') || 'this class';
        var forceDeleteInput = form.querySelector('input[name="force_delete"]');

        var title = 'Delete class-year?';
        var text = 'This action cannot be undone.';
        var icon = 'warning';

        if (teacherCount > 0) {
            title = 'Class assigned to teacher(s)';
            text = className + ' is assigned to ' + teacherCount + ' teacher(s). Only admin can continue with delete.';
            icon = 'warning';
        }

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                if (teacherCount > 0 && forceDeleteInput) {
                    forceDeleteInput.value = '1';
                }
                form.submit();
            }
        });
    });
});

function makeTableSortable(tableId, skipColumns) {
    var table = document.getElementById(tableId);
    if (!table) return;
    var headers = table.querySelectorAll('thead th');
    var state = {};

    headers.forEach(function (th, index) {
        if (skipColumns.indexOf(index) !== -1) return;
        th.style.cursor = 'pointer';
        th.title = 'Click to sort';
        th.addEventListener('click', function () {
            var tbody = table.querySelector('tbody');
            if (!tbody) return;
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (row) {
                return !row.classList.contains('no-data-row');
            });
            if (rows.length === 0) return;

            var direction = state[index] === 'asc' ? 'desc' : 'asc';
            state = {};
            state[index] = direction;

            rows.sort(function (rowA, rowB) {
                var a = (rowA.children[index] ? rowA.children[index].innerText : '').trim();
                var b = (rowB.children[index] ? rowB.children[index].innerText : '').trim();

                var numA = parseFloat(a.replace(/[^0-9.\-]/g, ''));
                var numB = parseFloat(b.replace(/[^0-9.\-]/g, ''));
                var bothNumeric = !isNaN(numA) && !isNaN(numB);
                if (bothNumeric) {
                    return numA - numB;
                }

                var dateA = Date.parse(a);
                var dateB = Date.parse(b);
                var bothDates = !isNaN(dateA) && !isNaN(dateB);
                if (bothDates) {
                    return dateA - dateB;
                }

                return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
            });

            if (direction === 'desc') {
                rows.reverse();
            }

            rows.forEach(function (row) { tbody.appendChild(row); });
        });
    });
}

function bindStudentSearch() {
    var table = document.getElementById('class_students_table');
    var nameInput = document.getElementById('student_search_name');
    var classInput = document.getElementById('student_search_class');
    var clearBtn = document.getElementById('student_search_clear');
    if (!table || !nameInput || !classInput || !clearBtn) return;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;
    var noDataRow = tbody.querySelector('tr.no-data-row');

    function applyFilter() {
        var nameQuery = nameInput.value.toLowerCase().trim();
        var classQuery = classInput.value.toLowerCase().trim();
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var visible = 0;

        rows.forEach(function (row) {
            if (row.classList.contains('no-data-row')) return;
            var studentName = (row.children[2] ? row.children[2].innerText : '').toLowerCase();
            var className = (row.children[3] ? row.children[3].innerText : '').toLowerCase();
            var show = studentName.indexOf(nameQuery) !== -1 && className.indexOf(classQuery) !== -1;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noDataRow) {
            noDataRow.style.display = visible === 0 ? '' : 'none';
            if (visible === 0) {
                noDataRow.children[0].innerText = 'No students match your search.';
            }
        }
    }

    nameInput.addEventListener('input', applyFilter);
    classInput.addEventListener('input', applyFilter);
    clearBtn.addEventListener('click', function () {
        nameInput.value = '';
        classInput.value = '';
        applyFilter();
    });
}

function bindClassYearSearch() {
    var table = document.getElementById('class_year_table');
    var classInput = document.getElementById('class_year_search_class');
    var yearInput = document.getElementById('class_year_search_year');
    var clearBtn = document.getElementById('class_year_search_clear');
    if (!table || !classInput || !yearInput || !clearBtn) return;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;
    var noDataRow = tbody.querySelector('tr.no-data-row');

    function applyFilter() {
        var classQuery = classInput.value.toLowerCase().trim();
        var yearQuery = yearInput.value.toLowerCase().trim();
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var visible = 0;

        rows.forEach(function (row) {
            if (row.classList.contains('no-data-row')) return;
            var className = (row.children[1] ? row.children[1].innerText : '').toLowerCase();
            var yearValue = (row.children[2] ? row.children[2].innerText : '').toLowerCase();
            var show = className.indexOf(classQuery) !== -1 && yearValue.indexOf(yearQuery) !== -1;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noDataRow) {
            noDataRow.style.display = visible === 0 ? '' : 'none';
            if (visible === 0) {
                noDataRow.children[0].innerText = 'No class-year record matches your search.';
            }
        }
    }

    classInput.addEventListener('input', applyFilter);
    yearInput.addEventListener('input', applyFilter);
    clearBtn.addEventListener('click', function () {
        classInput.value = '';
        yearInput.value = '';
        applyFilter();
    });
}

makeTableSortable('class_year_table', [5]);
makeTableSortable('class_students_table', []);
bindClassYearSearch();
bindStudentSearch();
</script>

<?php
include "./partials/footer.php";
if ($conn) {
    $conn->close();
}
ob_end_flush();
?>
