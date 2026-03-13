<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('results_reports', 'view', 'index.php');

include "db.php";

$resultType = $_GET['type'] ?? 'exam';
$classId = (int)($_GET['class_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? 0);
$termId = (int)($_GET['term_id'] ?? 0);
$date = trim($_GET['date'] ?? '');

$classes = [];
$subjects = [];
$terms = [];
$rows = [];

$c = $conn->query("SELECT id, class FROM classes ORDER BY class");
while ($r = $c->fetch_assoc()) {
    $classes[] = $r;
}

$s = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");
while ($r = $s->fetch_assoc()) {
    $subjects[] = $r;
}

$t = $conn->query("SELECT id, term_name FROM exam_terms ORDER BY id");
while ($r = $t->fetch_assoc()) {
    $terms[] = $r;
}

if ($resultType === 'test') {
    $sql = "SELECT 
                tr.id,
                tr.test_date AS result_date,
                s.StudentId,
                s.student_name,
                s.class AS class_name,
                tr.subject AS subject_name,
                tr.marks_obtained AS obtained_marks,
                tr.total_marks,
                ROUND((tr.marks_obtained / NULLIF(tr.total_marks, 0)) * 100, 2) AS percentage,
                te.name AS teacher_name
            FROM test_results tr
            LEFT JOIN students s ON s.id = tr.student_id
            LEFT JOIN teachers te ON te.id = tr.teacher_id
            WHERE 1=1";

    $params = [];
    $types = "";

    if ($classId > 0) {
        $sql .= " AND EXISTS (SELECT 1 FROM classes c2 WHERE c2.id = ? AND c2.class = s.class)";
        $params[] = $classId;
        $types .= "i";
    }
    if ($date !== '') {
        $sql .= " AND tr.test_date = ?";
        $params[] = $date;
        $types .= "s";
    }
    if ($subjectId > 0) {
        $sql .= " AND tr.subject = (SELECT subject_name FROM subjects WHERE id = ?)";
        $params[] = $subjectId;
        $types .= "i";
    }

    $sql .= " ORDER BY tr.test_date DESC, s.student_name ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
    }
} else {
    $sql = "SELECT 
                er.id,
                er.exam_date AS result_date,
                st.StudentId,
                st.student_name,
                c.class AS class_name,
                sub.subject_name,
                et.term_name,
                er.obtained_marks,
                er.total_marks,
                er.percentage,
                er.grade,
                te.name AS teacher_name
            FROM exam_results er
            LEFT JOIN students st ON st.id = er.student_id
            LEFT JOIN classes c ON c.id = er.class_id
            LEFT JOIN subjects sub ON sub.id = er.subject_id
            LEFT JOIN exam_terms et ON et.id = er.term_id
            LEFT JOIN teachers te ON te.id = er.teacher_id
            WHERE 1=1";

    $params = [];
    $types = "";

    if ($classId > 0) {
        $sql .= " AND er.class_id = ?";
        $params[] = $classId;
        $types .= "i";
    }
    if ($subjectId > 0) {
        $sql .= " AND er.subject_id = ?";
        $params[] = $subjectId;
        $types .= "i";
    }
    if ($termId > 0) {
        $sql .= " AND er.term_id = ?";
        $params[] = $termId;
        $types .= "i";
    }
    if ($date !== '') {
        $sql .= " AND er.exam_date = ?";
        $params[] = $date;
        $types .= "s";
    }

    $sql .= " ORDER BY er.exam_date DESC, st.student_name ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">View Results</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="form-row">
                <div class="form-group col-md-2">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="exam" <?php echo $resultType === 'exam' ? 'selected' : ''; ?>>Exam Results</option>
                        <option value="test" <?php echo $resultType === 'test' ? 'selected' : ''; ?>>Test Results</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Class</label>
                    <select name="class_id" class="form-control">
                        <option value="0">All</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo (int)$class['id']; ?>" <?php echo $classId === (int)$class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Subject</label>
                    <select name="subject_id" class="form-control">
                        <option value="0">All</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo (int)$subject['id']; ?>" <?php echo $subjectId === (int)$subject['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$subject['subject_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Term (Exam)</label>
                    <select name="term_id" class="form-control">
                        <option value="0">All</option>
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo (int)$term['id']; ?>" <?php echo $termId === (int)$term['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$term['term_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo $resultType === 'test' ? 'Test Results' : 'Exam Results'; ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <?php if ($resultType === 'exam'): ?><th>Term</th><?php endif; ?>
                            <th>Obtained</th>
                            <th>Total</th>
                            <th>%</th>
                            <?php if ($resultType === 'exam'): ?><th>Grade</th><?php endif; ?>
                            <th>Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)($row['result_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['StudentId'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['subject_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php if ($resultType === 'exam'): ?>
                                        <td><?php echo htmlspecialchars((string)($row['term_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars((string)($row['obtained_marks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['total_marks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['percentage'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php if ($resultType === 'exam'): ?>
                                        <td><?php echo htmlspecialchars((string)($row['grade'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars((string)($row['teacher_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $resultType === 'exam' ? '11' : '9'; ?>" class="text-center">No results found for selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include "./partials/footer.php";
if ($conn) {
    $conn->close();
}
ob_end_flush();
?>
