<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('attendance_reports', 'view', 'index.php');

include "db.php";

$flashType = '';
$flashMessage = '';

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedClassId = (int)($_GET['class_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_leave'])) {
    auth_require_permission('attendance_reports', 'edit', 'index.php');
    $studentId = (int)($_POST['student_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    $leaveDate = trim($_POST['leave_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($studentId <= 0 || $classId <= 0 || $leaveDate === '') {
        $flashType = 'danger';
        $flashMessage = 'Select class, student, and date.';
    } else {
        $check = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ? LIMIT 1");
        if ($check) {
            $check->bind_param("is", $studentId, $leaveDate);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();

            if ($existing) {
                $attendanceId = (int)$existing['id'];
                $update = $conn->prepare("UPDATE attendance SET class_id = ?, status = 'L', reason = ? WHERE id = ?");
                if ($update) {
                    $update->bind_param("isi", $classId, $reason, $attendanceId);
                    $update->execute();
                    $update->close();
                    $flashType = 'success';
                    $flashMessage = 'Leave record updated successfully.';
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Failed to update leave record.';
                }
            } else {
                $insert = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, reason) VALUES (?, ?, ?, 'L', ?)");
                if ($insert) {
                    $insert->bind_param("iiss", $studentId, $classId, $leaveDate, $reason);
                    $insert->execute();
                    $insert->close();
                    $flashType = 'success';
                    $flashMessage = 'Leave record added successfully.';
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Failed to add leave record.';
                }
            }
        }
    }
}

$classes = [];
$classRes = $conn->query("SELECT id, class FROM classes ORDER BY class ASC");
while ($row = $classRes->fetch_assoc()) {
    $classes[] = $row;
}

$students = [];
if ($selectedClassId > 0) {
    $studentStmt = $conn->prepare("
        SELECT s.id, s.StudentId, s.student_name
        FROM students s
        JOIN classes c ON c.class = s.class
        WHERE c.id = ?
        ORDER BY s.student_name ASC
    ");
    if ($studentStmt) {
        $studentStmt->bind_param("i", $selectedClassId);
        $studentStmt->execute();
        $studentRes = $studentStmt->get_result();
        while ($row = $studentRes->fetch_assoc()) {
            $students[] = $row;
        }
        $studentStmt->close();
    }
}

$leaveRows = [];
$leaveSql = "
    SELECT 
        a.id,
        a.attendance_date,
        a.reason,
        s.StudentId,
        s.student_name,
        c.class AS class_name
    FROM attendance a
    LEFT JOIN students s ON s.id = a.student_id
    LEFT JOIN classes c ON c.id = a.class_id
    WHERE a.status = 'L'
";
$types = '';
$params = [];
if ($selectedDate !== '') {
    $leaveSql .= " AND a.attendance_date = ?";
    $types .= 's';
    $params[] = $selectedDate;
}
if ($selectedClassId > 0) {
    $leaveSql .= " AND a.class_id = ?";
    $types .= 'i';
    $params[] = $selectedClassId;
}
$leaveSql .= " ORDER BY a.attendance_date DESC, s.student_name ASC";

$leaveStmt = $conn->prepare($leaveSql);
if ($leaveStmt) {
    if (!empty($params)) {
        $leaveStmt->bind_param($types, ...$params);
    }
    $leaveStmt->execute();
    $leaveRes = $leaveStmt->get_result();
    while ($row = $leaveRes->fetch_assoc()) {
        $leaveRows[] = $row;
    }
    $leaveStmt->close();
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Leave Record</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Mark Student Leave</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="form-row mb-3">
                <div class="form-group col-md-4">
                    <label>Class</label>
                    <select name="class_id" class="form-control" onchange="this.form.submit()">
                        <option value="0">Select class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo (int)$class['id']; ?>" <?php echo $selectedClassId === (int)$class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">Apply Filter</button>
                </div>
            </form>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Class</label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo (int)$class['id']; ?>" <?php echo $selectedClassId === (int)$class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Student</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Select student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo (int)$student['id']; ?>">
                                    <?php echo htmlspecialchars((string)$student['StudentId'], ENT_QUOTES, 'UTF-8'); ?>
                                    - <?php echo htmlspecialchars((string)$student['student_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Leave Date</label>
                        <input type="date" name="leave_date" class="form-control" value="<?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-10">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="Optional leave reason">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" name="mark_leave" class="btn btn-primary w-100">Save Leave</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Leave Records</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Class</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($leaveRows)): ?>
                            <?php foreach ($leaveRows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$row['attendance_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['StudentId'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No leave records found.</td>
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
