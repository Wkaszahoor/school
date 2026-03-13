<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('attendance_reports', 'view', 'index.php');

include "db.php";

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedTeacher = trim($_GET['teacher'] ?? '');

$faculty = [];
$totalFaculty = 0;
$loggedInOnDate = 0;
$neverLoggedIn = 0;

$countSql = "SELECT 
    COUNT(*) AS total_faculty,
    SUM(CASE WHEN DATE(last_login) = ? THEN 1 ELSE 0 END) AS logged_in_on_date,
    SUM(CASE WHEN last_login IS NULL THEN 1 ELSE 0 END) AS never_logged_in
FROM teachers";
$countStmt = $conn->prepare($countSql);
if ($countStmt) {
    $countStmt->bind_param("s", $selectedDate);
    $countStmt->execute();
    $row = $countStmt->get_result()->fetch_assoc();
    $totalFaculty = (int)($row['total_faculty'] ?? 0);
    $loggedInOnDate = (int)($row['logged_in_on_date'] ?? 0);
    $neverLoggedIn = (int)($row['never_logged_in'] ?? 0);
    $countStmt->close();
}

$sql = "SELECT id, name, email, subject, phone, class_assigned, last_login, updated_at
        FROM teachers
        WHERE (? = '' OR name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%'))
        ORDER BY name ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("sss", $selectedTeacher, $selectedTeacher, $selectedTeacher);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $faculty[] = $r;
    }
    $stmt->close();
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Faculty Attendance</h1>

    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Faculty</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalFaculty; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Logged In on <?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $loggedInOnDate; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Never Logged In</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $neverLoggedIn; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="form-row">
                <div class="form-group col-md-4">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Teacher Name / Email</label>
                    <input type="text" name="teacher" class="form-control" value="<?php echo htmlspecialchars($selectedTeacher, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search teacher">
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Faculty Records</h6>
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
                            <th>Assigned Class</th>
                            <th>Last Login</th>
                            <th>Status on Selected Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($faculty)): ?>
                            <?php foreach ($faculty as $f): ?>
                                <?php
                                    $lastLoginRaw = $f['last_login'] ?? null;
                                    $lastLogin = $lastLoginRaw ? date('Y-m-d H:i:s', strtotime($lastLoginRaw)) : 'Never';
                                    $presentOnDate = ($lastLoginRaw && date('Y-m-d', strtotime($lastLoginRaw)) === $selectedDate) ? 'Present' : 'Absent';
                                    $statusClass = $presentOnDate === 'Present' ? 'text-success font-weight-bold' : 'text-danger font-weight-bold';
                                ?>
                                <tr>
                                    <td><?php echo (int)$f['id']; ?></td>
                                    <td><?php echo htmlspecialchars((string)($f['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($f['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($f['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($f['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($f['class_assigned'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($lastLogin, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="<?php echo $statusClass; ?>"><?php echo $presentOnDate; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No faculty records found.</td>
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
