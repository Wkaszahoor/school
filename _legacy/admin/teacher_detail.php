<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teachers', 'view', 'index.php');

include "db.php";

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
    if (isset($_POST['add_teacher'])) {
        auth_require_permission('teachers', 'create', 'index.php');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $classAssigned = trim($_POST['class_assigned'] ?? '');

        if ($name === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flashType = 'danger';
            $flashMessage = 'Enter valid name, email, and password.';
        } else {
            $stmt = $conn->prepare("INSERT INTO teachers (name, email, password, subject, phone, class_assigned) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssssss", $name, $email, $password, $subject, $phone, $classAssigned);
                if ($stmt->execute()) {
                    $flashType = 'success';
                    $flashMessage = 'Teacher added successfully.';
                } else {
                    if ($conn->errno === 1062) {
                        $flashType = 'danger';
                        $flashMessage = 'Email already exists.';
                    } else {
                        $flashType = 'danger';
                        $flashMessage = 'Failed to add teacher.';
                    }
                }
                $stmt->close();
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to prepare add query.';
            }
        }
    }

    if (isset($_POST['delete_teacher'])) {
        auth_require_permission('teachers', 'delete', 'index.php');
        $teacherId = (int)($_POST['teacher_id'] ?? 0);
        if ($teacherId > 0) {
            $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
            if ($stmt) {
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
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to prepare delete query.';
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
        <h1 class="h3 text-gray-800 mb-0">Teacher Details</h1>
        <a class="btn btn-primary btn-sm" href="assign.php">Assign/Change Class</a>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Add Teacher</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Password</label>
                        <input type="text" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Subject (optional)</label>
                        <input type="text" class="form-control" name="subject">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Phone (optional)</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Class Assigned (optional)</label>
                        <input type="text" class="form-control" name="class_assigned" placeholder="e.g. 10th Grade A">
                    </div>
                </div>
                <button type="submit" name="add_teacher" class="btn btn-success">Add Teacher</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Teachers</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
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
                                        <form method="POST" action="" onsubmit="return confirm('Delete this teacher?');">
                                            <input type="hidden" name="teacher_id" value="<?php echo (int)$teacher['id']; ?>">
                                            <button type="submit" name="delete_teacher" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
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
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
