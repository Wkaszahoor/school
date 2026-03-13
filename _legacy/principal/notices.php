<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('admin_dashboard', 'view', 'index.php');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../scripts/notice_lib.php';
include './partials/topbar.php';

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = mysqli_connect('localhost', 'root', 'mysql', 'db_school_kort');
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection failed in principal/notices.php');
}

notices_ensure_tables($conn);

$flashType = '';
$flashMessage = '';

$teachers = [];
$res = $conn->query("SELECT id, name, email FROM teachers ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $teachers[] = $row;
    }
}

$classes = [];
$res = $conn->query("SELECT id, class, academic_year FROM classes ORDER BY academic_year DESC, class ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $classes[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_notice'])) {
    $title = trim((string)($_POST['title'] ?? ''));
    $body = trim((string)($_POST['body'] ?? ''));
    $scope = trim((string)($_POST['target_scope'] ?? 'all'));
    $targetRole = trim((string)($_POST['target_role'] ?? ''));
    $targetTeacherId = (int)($_POST['target_teacher_id'] ?? 0);
    $targetClassId = (int)($_POST['target_class_id'] ?? 0);

    $allowedScopes = ['all', 'role', 'teacher', 'class'];
    $allowedRoles = ['teacher', 'admin', 'principal', 'receptionist', 'principal_helper', 'inventory_manager', 'doctor'];

    if (!in_array($scope, $allowedScopes, true)) {
        $scope = 'all';
    }
    if (!in_array($targetRole, $allowedRoles, true)) {
        $targetRole = '';
    }

    if ($scope === 'role' && $targetRole === '') {
        $flashType = 'danger';
        $flashMessage = 'Select a target role.';
    } elseif ($scope === 'teacher' && $targetTeacherId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Select a teacher.';
    } elseif ($scope === 'class' && $targetClassId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Select a class/session.';
    } elseif ($title === '' || $body === '') {
        $flashType = 'danger';
        $flashMessage = 'Title and message are required.';
    } else {
        $createdBy = (int)($_SESSION['auth_user_id'] ?? 0);
        $createdRole = (string)($_SESSION['auth_role'] ?? '');
        $stmt = $conn->prepare("
            INSERT INTO notices (title, body, target_scope, target_role, target_teacher_id, target_class_id, is_active, created_by, created_by_role)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('ssssiiis', $title, $body, $scope, $targetRole, $targetTeacherId, $targetClassId, $createdBy, $createdRole);
            $stmt->execute();
            $newId = (int)$stmt->insert_id;
            $stmt->close();
            auth_audit_log($conn, 'publish', 'notice', (string)$newId, null, json_encode([
                'scope' => $scope,
                'target_role' => $targetRole,
                'target_teacher_id' => $targetTeacherId,
                'target_class_id' => $targetClassId,
            ]));
            $flashType = 'success';
            $flashMessage = 'Notice published.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_notice'])) {
    $noticeId = (int)($_POST['notice_id'] ?? 0);
    if ($noticeId > 0) {
        $stmt = $conn->prepare("UPDATE notices SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $noticeId);
            $stmt->execute();
            $stmt->close();
            auth_audit_log($conn, 'toggle', 'notice', (string)$noticeId);
            $flashType = 'success';
            $flashMessage = 'Notice status updated.';
        }
    }
}

$teacherMap = [];
foreach ($teachers as $t) {
    $teacherMap[(int)$t['id']] = (string)$t['name'];
}
$classMap = [];
foreach ($classes as $c) {
    $classMap[(int)$c['id']] = (string)$c['class'] . ' (' . (string)($c['academic_year'] ?? '') . ')';
}

$notices = [];
$res = $conn->query("
    SELECT id, title, body, target_scope, target_role, target_teacher_id, target_class_id, is_active, created_at
    FROM notices
    ORDER BY id DESC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $noticeId = (int)$row['id'];
        $readRes = $conn->query("SELECT COUNT(*) AS c FROM notice_reads WHERE notice_id = {$noticeId}");
        $readCount = (int)($readRes ? ($readRes->fetch_assoc()['c'] ?? 0) : 0);
        $targetTotal = notices_target_total($conn, $row);
        $row['read_count'] = $readCount;
        $row['unread_count'] = $targetTotal > 0 ? max(0, $targetTotal - $readCount) : null;
        $notices[] = $row;
    }
}
?>
<div class="container-fluid">
    <h4 class="mb-3">Notices & Broadcast</h4>
    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo h($flashType); ?>"><?php echo h($flashMessage); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">Publish Notice</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Broadcast Scope</label>
                        <select name="target_scope" id="target_scope" class="form-control">
                            <option value="all">All</option>
                            <option value="role">Specific Role</option>
                            <option value="teacher">Specific Teacher</option>
                            <option value="class">Specific Class/Session</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="target_role_wrap" style="display:none;">
                        <label class="form-label">Target Role</label>
                        <select name="target_role" class="form-control">
                            <option value="">Select role</option>
                            <option value="teacher">Teachers</option>
                            <option value="admin">Admin</option>
                            <option value="principal">Principal</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="principal_helper">Principal Helper</option>
                            <option value="inventory_manager">Inventory Manager</option>
                            <option value="doctor">Doctor</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="target_teacher_wrap" style="display:none;">
                        <label class="form-label">Target Teacher</label>
                        <select name="target_teacher_id" class="form-control">
                            <option value="0">Select teacher</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>">
                                    <?php echo h($t['name']); ?> (<?php echo h($t['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4" id="target_class_wrap" style="display:none;">
                        <label class="form-label">Target Class / Session</label>
                        <select name="target_class_id" class="form-control">
                            <option value="0">Select class/session</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo (int)$c['id']; ?>">
                                    <?php echo h($c['class']); ?> (<?php echo h($c['academic_year']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Message</label>
                        <textarea name="body" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" type="submit" name="publish_notice">Publish</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Notice History (Read/Unread Tracking)</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>ID</th><th>Title</th><th>Scope</th><th>Target</th><th>Status</th><th>Read</th><th>Unread</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($notices)): foreach ($notices as $n): ?>
                    <?php
                        $scope = (string)$n['target_scope'];
                        $target = 'All';
                        if ($scope === 'role') {
                            $target = (string)($n['target_role'] ?? '-');
                        } elseif ($scope === 'teacher') {
                            $target = $teacherMap[(int)$n['target_teacher_id']] ?? ('Teacher #' . (int)$n['target_teacher_id']);
                        } elseif ($scope === 'class') {
                            $target = $classMap[(int)$n['target_class_id']] ?? ('Class #' . (int)$n['target_class_id']);
                        }
                    ?>
                    <tr>
                        <td><?php echo (int)$n['id']; ?></td>
                        <td><?php echo h($n['title']); ?></td>
                        <td><?php echo h($scope); ?></td>
                        <td><?php echo h($target); ?></td>
                        <td><?php echo ((int)$n['is_active'] === 1) ? 'Active' : 'Inactive'; ?></td>
                        <td><?php echo (int)$n['read_count']; ?></td>
                        <td><?php echo $n['unread_count'] === null ? '-' : (int)$n['unread_count']; ?></td>
                        <td><?php echo h($n['created_at']); ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="notice_id" value="<?php echo (int)$n['id']; ?>">
                                <button class="btn btn-sm btn-warning" type="submit" name="toggle_notice"><?php echo ((int)$n['is_active'] === 1) ? 'Disable' : 'Enable'; ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="9" class="text-center">No notices found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
(function () {
    var scope = document.getElementById('target_scope');
    var roleWrap = document.getElementById('target_role_wrap');
    var teacherWrap = document.getElementById('target_teacher_wrap');
    var classWrap = document.getElementById('target_class_wrap');
    function sync() {
        var v = scope ? scope.value : 'all';
        if (roleWrap) roleWrap.style.display = (v === 'role') ? '' : 'none';
        if (teacherWrap) teacherWrap.style.display = (v === 'teacher') ? '' : 'none';
        if (classWrap) classWrap.style.display = (v === 'class') ? '' : 'none';
    }
    if (scope) {
        scope.addEventListener('change', sync);
    }
    sync();
})();
</script>
<?php include './partials/footer.php'; ?>
