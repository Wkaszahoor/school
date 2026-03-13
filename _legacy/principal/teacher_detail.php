<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teachers', 'view', 'index.php');
include '../db.php';

$canCreateTeacher = auth_can('teachers', 'create');
$canDeleteTeacher = auth_can('teachers', 'delete');
$canResetTeacherPassword = auth_can('teachers', 'create');

$flashType = '';
$flashMessage = '';

$conn->query("
    CREATE TABLE IF NOT EXISTS teacher_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL UNIQUE,
        gender VARCHAR(20) NULL,
        date_of_birth DATE NULL,
        cnic VARCHAR(30) NULL,
        address TEXT NULL,
        highest_qualification VARCHAR(120) NULL,
        institute_name VARCHAR(190) NULL,
        passing_year VARCHAR(10) NULL,
        certifications TEXT NULL,
        experience_years DECIMAL(4,1) NULL,
        previous_school VARCHAR(190) NULL,
        specialization VARCHAR(190) NULL,
        achievements TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['reset_teacher_password'])) {
        auth_require_permission('teachers', 'create', 'index.php');
        $teacherId = (int)($_POST['teacher_id'] ?? 0);
        $newPassword = (string)($_POST['new_password'] ?? '');
        if ($teacherId <= 0 || strlen($newPassword) < 6) {
            $flashType = 'danger';
            $flashMessage = 'Enter a valid new password (minimum 6 characters).';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $newHash, $teacherId);
                $stmt->execute();
                if ($stmt->affected_rows >= 0) {
                    $flashType = 'success';
                    $flashMessage = 'Teacher password reset successfully.';
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Unable to reset password.';
                }
                $stmt->close();
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to prepare password reset query.';
            }
        }
    }

    if (isset($_POST['delete_teacher'])) {
        auth_require_permission('teachers', 'delete', 'index.php');
        $teacherId = (int)($_POST['teacher_id'] ?? 0);
        if ($teacherId > 0) {
            $conn->begin_transaction();
            try {
                $profileDelete = $conn->prepare("DELETE FROM teacher_profiles WHERE teacher_id = ?");
                if ($profileDelete) {
                    $profileDelete->bind_param("i", $teacherId);
                    $profileDelete->execute();
                    $profileDelete->close();
                }

                $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
                if (!$stmt) {
                    throw new Exception('Failed to prepare delete query.');
                }
                $stmt->bind_param("i", $teacherId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $flashType = 'success';
                    $flashMessage = 'Teacher removed successfully.';
                } else {
                    $flashType = 'warning';
                    $flashMessage = 'Teacher not found.';
                }
                $stmt->close();
                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                $flashType = 'danger';
                $flashMessage = 'Failed to delete teacher.';
            }
        }
    }
}

$teachers = [];
$stmt = $conn->prepare("
    SELECT t.id, t.name, t.email, t.subject, t.phone, t.class_assigned, tp.gender
    FROM teachers t
    LEFT JOIN teacher_profiles tp ON tp.teacher_id = t.id
    ORDER BY t.id ASC
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
    $stmt->close();
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 text-gray-800 mb-0">Teacher Management</h1>
        <div>
            <a class="btn btn-primary btn-sm" href="assign.php">Assign/Change Class</a>
            <?php if ($canCreateTeacher): ?>
                <a class="btn btn-success btn-sm" href="create_teacher.php">Create Teacher</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Teacher Management Pages</h6>
        </div>
        <div class="card-body py-3">
            <a class="btn btn-primary btn-sm mr-2 mb-2" href="teacher_detail.php">Teacher Details</a>
            <a class="btn btn-outline-primary btn-sm mr-2 mb-2" href="assign.php">Assign Teacher</a>
            <?php if ($canCreateTeacher): ?>
                <a class="btn btn-outline-primary btn-sm mr-2 mb-2" href="create_teacher.php">Create Teacher</a>
            <?php endif; ?>
            <div class="small text-muted mt-2">
                Teacher profiles are available from the <strong>View Profile</strong> button in the teacher list below.
            </div>
        </div>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Teachers</h6>
        </div>
        <div class="card-body">
            <div class="teacher-search-panel border rounded p-3 mb-3 bg-light">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-10">
                        <label for="teacher_live_search" class="font-weight-bold d-block text-center mb-2">Global Live Search</label>
                        <input type="text" id="teacher_live_search" class="form-control form-control-lg text-center" placeholder="Type name, email, subject, class...">
                    </div>
                </div>
                <div class="row justify-content-center mt-3">
                    <div class="col-lg-3 col-md-4">
                        <label for="teacher_name_search">Search by Name</label>
                        <input type="text" id="teacher_name_search" class="form-control" placeholder="Type teacher name">
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label for="teacher_subject_search">Search by Subject</label>
                        <input type="text" id="teacher_subject_search" class="form-control" placeholder="Type subject">
                    </div>
                    <div class="col-lg-2 col-md-3 d-flex align-items-end">
                        <button type="button" id="teacher_search_clear" class="btn btn-secondary w-100">Clear</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table id="teachers_table" class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Assigned Class</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($teachers)): ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)($teacher['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($teacher['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($teacher['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($teacher['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($teacher['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($teacher['gender'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($teacher['class_assigned'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="teacher_profile.php?id=<?php echo (int)$teacher['id']; ?>" class="btn btn-info btn-sm mb-2">View Profile</a>
                                        <?php if ($canResetTeacherPassword): ?>
                                            <form method="POST" action="" class="mb-2">
                                                <input type="hidden" name="teacher_id" value="<?php echo (int)$teacher['id']; ?>">
                                                <div class="input-group input-group-sm mb-1">
                                                    <input type="text" class="form-control" name="new_password" minlength="6" placeholder="New password" required>
                                                    <div class="input-group-append">
                                                        <button type="submit" name="reset_teacher_password" class="btn btn-warning">Reset</button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($canDeleteTeacher): ?>
                                            <form method="POST" action="" onsubmit="return confirm('Delete this teacher?');">
                                                <input type="hidden" name="teacher_id" value="<?php echo (int)$teacher['id']; ?>">
                                                <button type="submit" name="delete_teacher" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No teachers found.</td>
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
?>
<style>
.teacher-search-panel label {
    color: #2c3e50;
}
</style>
<script>
(function () {
    var table = document.getElementById('teachers_table');
    var globalInput = document.getElementById('teacher_live_search');
    var nameInput = document.getElementById('teacher_name_search');
    var subjectInput = document.getElementById('teacher_subject_search');
    var clearBtn = document.getElementById('teacher_search_clear');
    if (!table || !globalInput || !nameInput || !subjectInput || !clearBtn) {
        return;
    }

    var tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }
    var noDataRow = tbody.querySelector('tr td[colspan="8"]') ? tbody.querySelector('tr') : null;

    function rowText(row) {
        return (row.innerText || row.textContent || '').toLowerCase();
    }

    function cellText(row, index) {
        return ((row.children[index] ? row.children[index].innerText : '') || '').toLowerCase();
    }

    function applyFilter() {
        var qGlobal = globalInput.value.toLowerCase().trim();
        var qName = nameInput.value.toLowerCase().trim();
        var qSubject = subjectInput.value.toLowerCase().trim();
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var visible = 0;

        rows.forEach(function (row) {
            if (noDataRow && row === noDataRow) {
                return;
            }
            var matchGlobal = qGlobal === '' || rowText(row).indexOf(qGlobal) !== -1;
            var matchName = qName === '' || cellText(row, 1).indexOf(qName) !== -1;
            var matchSubject = qSubject === '' || cellText(row, 3).indexOf(qSubject) !== -1;
            var show = matchGlobal && matchName && matchSubject;
            row.style.display = show ? '' : 'none';
            if (show) {
                visible++;
            }
        });

        if (noDataRow) {
            noDataRow.style.display = visible === 0 ? '' : 'none';
            if (visible === 0) {
                var cell = noDataRow.querySelector('td');
                if (cell) {
                    cell.innerText = 'No teachers match your search.';
                }
            }
        }
    }

    globalInput.addEventListener('input', applyFilter);
    nameInput.addEventListener('input', applyFilter);
    subjectInput.addEventListener('input', applyFilter);
    clearBtn.addEventListener('click', function () {
        globalInput.value = '';
        nameInput.value = '';
        subjectInput.value = '';
        applyFilter();
    });
})();
</script>
<?php
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
