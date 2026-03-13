<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teachers', 'view', 'index.php');
include '../db.php';
include './partials/topbar.php';

$classes = [];
$res = $conn->query("SELECT id, class, academic_year FROM classes ORDER BY academic_year DESC, class ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $classes[] = $row;
    }
}

$selectedClassId = (int)($_GET['class_id'] ?? 0);
if ($selectedClassId <= 0 && !empty($classes)) {
    $selectedClassId = (int)$classes[0]['id'];
}

$classInfo = null;
$classTeacher = null;
$subjectTeachers = [];
$subjectFilterOptions = [];

if ($selectedClassId > 0) {
    $stmt = $conn->prepare("
        SELECT c.id, c.class, c.academic_year, c.class_teacher_id
        FROM classes c
        WHERE c.id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $selectedClassId);
        $stmt->execute();
        $classInfo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if ($classInfo && (int)($classInfo['class_teacher_id'] ?? 0) > 0) {
        $ctId = (int)$classInfo['class_teacher_id'];
        $stmt = $conn->prepare("SELECT id, name, email, phone FROM teachers WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $ctId);
            $stmt->execute();
            $classTeacher = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    $stmt = $conn->prepare("
        SELECT ta.id, t.id AS teacher_id, t.name AS teacher_name, t.email AS teacher_email, t.phone AS teacher_phone, s.subject_name
        FROM teacher_assignments ta
        JOIN teachers t ON t.id = ta.teacher_id
        JOIN subjects s ON s.id = ta.subject_id
        WHERE ta.class_id = ?
        ORDER BY s.subject_name ASC, t.name ASC
    ");
    if ($stmt) {
        $stmt->bind_param('i', $selectedClassId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $subjectTeachers[] = $row;
            $subjectName = (string)($row['subject_name'] ?? '');
            if ($subjectName !== '') {
                $subjectFilterOptions[$subjectName] = $subjectName;
            }
        }
        $stmt->close();
    }
}
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Teachers By Class</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Select Class</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-inline">
                <label class="mr-2" for="class_id">Class-Year</label>
                <select name="class_id" id="class_id" class="form-control mr-2">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$c['id'] === $selectedClassId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)$c['class'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)($c['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Show</button>
            </form>
        </div>
    </div>

    <?php if ($classInfo): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Class Teacher: <?php echo htmlspecialchars((string)$classInfo['class'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars((string)($classInfo['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)
                </h6>
            </div>
            <div class="card-body">
                <?php if ($classTeacher): ?>
                    <div><strong>Name:</strong> <?php echo htmlspecialchars((string)$classTeacher['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars((string)($classTeacher['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><strong>Phone:</strong> <?php echo htmlspecialchars((string)($classTeacher['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php else: ?>
                    <div class="text-muted">No class teacher assigned.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Subject Teachers</h6>
            </div>
            <div class="card-body table-responsive">
                <div class="form-row mb-3">
                    <div class="form-group col-md-4">
                        <label for="subject_filter">Filter by Subject</label>
                        <select id="subject_filter" class="form-control">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjectFilterOptions as $subjectLabel): ?>
                                <option value="<?php echo htmlspecialchars((string)$subjectLabel, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string)$subjectLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="teacher_filter">Search by Teacher Name</label>
                        <input type="text" id="teacher_filter" class="form-control" placeholder="Type teacher name">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="button" id="clear_filters" class="btn btn-secondary w-100">Clear</button>
                    </div>
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Email</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($subjectTeachers)): ?>
                        <?php foreach ($subjectTeachers as $row): ?>
                            <tr class="subject-teacher-row">
                                <td><?php echo htmlspecialchars((string)$row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($row['teacher_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($row['teacher_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No subject teachers assigned to this class.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
function bindSubjectTeacherFilters() {
    var subjectFilter = document.getElementById('subject_filter');
    var teacherFilter = document.getElementById('teacher_filter');
    var clearBtn = document.getElementById('clear_filters');
    if (!subjectFilter || !teacherFilter || !clearBtn) return;

    var rows = Array.prototype.slice.call(document.querySelectorAll('.subject-teacher-row'));
    function applyFilters() {
        var subjectQuery = subjectFilter.value.toLowerCase().trim();
        var teacherQuery = teacherFilter.value.toLowerCase().trim();
        rows.forEach(function (row) {
            var subjectText = (row.children[0] ? row.children[0].innerText : '').toLowerCase();
            var teacherText = (row.children[1] ? row.children[1].innerText : '').toLowerCase();
            var subjectOk = subjectQuery === '' || subjectText === subjectQuery;
            var teacherOk = teacherQuery === '' || teacherText.indexOf(teacherQuery) !== -1;
            row.style.display = (subjectOk && teacherOk) ? '' : 'none';
        });
    }

    subjectFilter.addEventListener('change', applyFilters);
    teacherFilter.addEventListener('input', applyFilters);
    clearBtn.addEventListener('click', function () {
        subjectFilter.value = '';
        teacherFilter.value = '';
        applyFilters();
    });
}
bindSubjectTeacherFilters();
</script>
<?php include './partials/footer.php'; ?>
