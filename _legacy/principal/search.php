<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('admin_dashboard', 'view', 'index.php');
include '../db.php';
include './partials/topbar.php';

$q = trim((string)($_GET['q'] ?? ''));

$students = [];
$teachers = [];
$classes = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    $s = $conn->prepare("
        SELECT id, StudentId, student_name, original_name, class, academic_year, group_stream
        FROM students
        WHERE StudentId LIKE ? OR student_name LIKE ? OR IFNULL(original_name,'') LIKE ? OR class LIKE ?
        ORDER BY student_name ASC
        LIMIT 100
    ");
    if ($s) {
        $s->bind_param('ssss', $like, $like, $like, $like);
        $s->execute();
        $r = $s->get_result();
        while ($row = $r->fetch_assoc()) {
            $students[] = $row;
        }
        $s->close();
    }

    $t = $conn->prepare("
        SELECT id, name, email, class_assigned
        FROM teachers
        WHERE name LIKE ? OR email LIKE ? OR IFNULL(class_assigned,'') LIKE ?
        ORDER BY name ASC
        LIMIT 100
    ");
    if ($t) {
        $t->bind_param('sss', $like, $like, $like);
        $t->execute();
        $r = $t->get_result();
        while ($row = $r->fetch_assoc()) {
            $teachers[] = $row;
        }
        $t->close();
    }

    $c = $conn->prepare("
        SELECT id, class, academic_year
        FROM classes
        WHERE class LIKE ? OR IFNULL(academic_year,'') LIKE ?
        ORDER BY academic_year DESC, class ASC
        LIMIT 100
    ");
    if ($c) {
        $c->bind_param('ss', $like, $like);
        $c->execute();
        $r = $c->get_result();
        while ($row = $r->fetch_assoc()) {
            $classes[] = $row;
        }
        $c->close();
    }
}
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Global Search</h1>
    <div class="alert alert-info">Search query: <strong><?php echo htmlspecialchars($q !== '' ? $q : '(empty)', ENT_QUOTES, 'UTF-8'); ?></strong></div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Students (<?php echo (int)count($students); ?>)</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                <tr>
                    <th>KORT ID</th>
                    <th>Name</th>
                    <th>Original Name</th>
                    <th>Class</th>
                    <th>Session</th>
                    <th>Stream</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($students)): foreach ($students as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['StudentId'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['group_stream'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="all_students.php?edit_id=<?php echo (int)$row['id']; ?>">More Info</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center">No students found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Teachers (<?php echo (int)count($teachers); ?>)</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Assigned Class</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($teachers)): foreach ($teachers as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['class_assigned'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="teacher_profile.php?id=<?php echo (int)$row['id']; ?>">More Info</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-center">No teachers found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Classes (<?php echo (int)count($classes); ?>)</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                <tr>
                    <th>Class</th>
                    <th>Academic Year</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($classes)): foreach ($classes as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="all_students.php?class=<?php echo urlencode((string)$row['class']); ?>">View Students</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3" class="text-center">No classes found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
