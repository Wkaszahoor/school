<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('subjects', 'view', 'index.php');

include "db.php";

$flashType = '';
$flashMessage = '';

$conn->query("\n    CREATE TABLE IF NOT EXISTS class_subjects (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        class_id INT NOT NULL,\n        subject_id INT NOT NULL,\n        UNIQUE KEY uniq_class_subject (class_id, subject_id)\n    )\n");

$selectedClassId = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    auth_require_permission('subjects', 'edit', 'index.php');
    $classId = (int)($_POST['class_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);

    if ($classId <= 0 || $subjectId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Please select both class and subject.';
    } else {
        $check = $conn->prepare("SELECT id FROM class_subjects WHERE class_id = ? AND subject_id = ? LIMIT 1");
        $exists = false;
        if ($check) {
            $check->bind_param("ii", $classId, $subjectId);
            $check->execute();
            $exists = (bool)$check->get_result()->fetch_assoc();
            $check->close();
        }

        if ($exists) {
            $flashType = 'warning';
            $flashMessage = 'This subject is already mapped to the class.';
        } else {
            $insert = $conn->prepare("INSERT INTO class_subjects (class_id, subject_id) VALUES (?, ?)");
            if ($insert) {
                $insert->bind_param("ii", $classId, $subjectId);
                $insert->execute();
                $insert->close();
                $flashType = 'success';
                $flashMessage = 'Subject added to class.';
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to add subject.';
            }
        }
    }

    $selectedClassId = $classId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_subject'])) {
    auth_require_permission('subjects', 'edit', 'index.php');
    $mapId = (int)($_POST['map_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);

    if ($mapId > 0) {
        $del = $conn->prepare("DELETE FROM class_subjects WHERE id = ?");
        if ($del) {
            $del->bind_param("i", $mapId);
            $del->execute();
            $del->close();
            $flashType = 'success';
            $flashMessage = 'Subject removed from class.';
        } else {
            $flashType = 'danger';
            $flashMessage = 'Failed to remove subject.';
        }
    }

    $selectedClassId = $classId;
}

$classes = [];
$classesStmt = $conn->prepare("SELECT id, class, academic_year FROM classes ORDER BY academic_year DESC, class ASC");
if ($classesStmt) {
    $classesStmt->execute();
    $res = $classesStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $classes[] = $row;
    }
    $classesStmt->close();
}

if ($selectedClassId === 0 && !empty($classes)) {
    $selectedClassId = (int)$classes[0]['id'];
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

$mappedSubjects = [];
$mapStmt = $conn->prepare("\n    SELECT cs.id, s.subject_name\n    FROM class_subjects cs\n    JOIN subjects s ON s.id = cs.subject_id\n    WHERE cs.class_id = ?\n    ORDER BY s.subject_name ASC\n");
if ($mapStmt) {
    $mapStmt->bind_param("i", $selectedClassId);
    $mapStmt->execute();
    $res = $mapStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $mappedSubjects[] = $row;
    }
    $mapStmt->close();
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 text-gray-800 mb-0">Class Subjects</h1>
        <div>
            <a class="btn btn-secondary btn-sm" href="subjects.php">Subjects</a>
            <a class="btn btn-secondary btn-sm" href="class_year.php">Back to Class & Year</a>
        </div>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Add Subject to Class</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Class</label>
                        <select name="class_id" class="form-control" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo (int)$class['id']; ?>" <?php echo ($selectedClassId === (int)$class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo htmlspecialchars((string)($class['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Subject</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">Select subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo (int)$subject['id']; ?>">
                                    <?php echo htmlspecialchars((string)$subject['subject_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Subjects Mapped to Selected Class</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="mb-3">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>View Class</label>
                        <select name="class_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo (int)$class['id']; ?>" <?php echo ($selectedClassId === (int)$class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo htmlspecialchars((string)($class['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th style="width:140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mappedSubjects)): ?>
                            <?php foreach ($mappedSubjects as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Remove this subject from class?');" style="display:inline;">
                                            <input type="hidden" name="map_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="hidden" name="class_id" value="<?php echo (int)$selectedClassId; ?>">
                                            <button type="submit" name="remove_subject" class="btn btn-danger btn-sm">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="text-center">No subjects mapped for this class.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include "./partials/footer.php";
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
