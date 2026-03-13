<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('subjects', 'view', 'index.php');

include "db.php";

$flashType = '';
$flashMessage = '';

$conn->query("\n    CREATE TABLE IF NOT EXISTS subjects (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        subject_name VARCHAR(120) NOT NULL\n    )\n");

$editId = (int)($_GET['edit_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    auth_require_permission('subjects', 'create', 'index.php');
    $name = trim($_POST['subject_name'] ?? '');
    if ($name === '') {
        $flashType = 'danger';
        $flashMessage = 'Subject name is required.';
    } else {
        $check = $conn->prepare("SELECT id FROM subjects WHERE subject_name = ? LIMIT 1");
        $exists = false;
        if ($check) {
            $check->bind_param("s", $name);
            $check->execute();
            $exists = (bool)$check->get_result()->fetch_assoc();
            $check->close();
        }
        if ($exists) {
            $flashType = 'warning';
            $flashMessage = 'Subject already exists.';
        } else {
            $ins = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
            if ($ins) {
                $ins->bind_param("s", $name);
                $ins->execute();
                $ins->close();
                $flashType = 'success';
                $flashMessage = 'Subject added.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subject'])) {
    auth_require_permission('subjects', 'edit', 'index.php');
    $id = (int)($_POST['subject_id'] ?? 0);
    $name = trim($_POST['subject_name'] ?? '');
    if ($id <= 0 || $name === '') {
        $flashType = 'danger';
        $flashMessage = 'Subject name is required.';
    } else {
        $check = $conn->prepare("SELECT id FROM subjects WHERE subject_name = ? AND id <> ? LIMIT 1");
        $exists = false;
        if ($check) {
            $check->bind_param("si", $name, $id);
            $check->execute();
            $exists = (bool)$check->get_result()->fetch_assoc();
            $check->close();
        }
        if ($exists) {
            $flashType = 'warning';
            $flashMessage = 'Another subject with this name already exists.';
        } else {
            $upd = $conn->prepare("UPDATE subjects SET subject_name = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param("si", $name, $id);
                $upd->execute();
                $upd->close();
                $flashType = 'success';
                $flashMessage = 'Subject updated.';
                $editId = 0;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    auth_require_permission('subjects', 'delete', 'index.php');
    $id = (int)($_POST['subject_id'] ?? 0);
    if ($id > 0) {
        $del = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        if ($del) {
            $del->bind_param("i", $id);
            $del->execute();
            $del->close();
            $flashType = 'success';
            $flashMessage = 'Subject deleted.';
        }
    }
}

$editRecord = null;
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT id, subject_name FROM subjects WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $editRecord = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$subjects = [];
$res = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row;
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 text-gray-800 mb-0">Subjects</h1>
        <a class="btn btn-secondary btn-sm" href="class_subjects.php">Back to Class Subjects</a>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo $editRecord ? 'Edit Subject' : 'Add Subject'; ?></h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php if ($editRecord): ?>
                            <input type="hidden" name="subject_id" value="<?php echo (int)$editRecord['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" value="<?php echo htmlspecialchars((string)($editRecord['subject_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <?php if ($editRecord): ?>
                            <button type="submit" name="update_subject" class="btn btn-warning">Update</button>
                            <a class="btn btn-light" href="subjects.php">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_subject" class="btn btn-primary">Add</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Subjects</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th style="width:160px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)$subject['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <a class="btn btn-info btn-sm" href="subjects.php?edit_id=<?php echo (int)$subject['id']; ?>">Edit</a>
                                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this subject?');">
                                                    <input type="hidden" name="subject_id" value="<?php echo (int)$subject['id']; ?>">
                                                    <button type="submit" name="delete_subject" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center">No subjects yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
