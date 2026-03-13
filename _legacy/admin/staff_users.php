<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('staff_users', 'view', 'index.php');
include 'db.php';
include './partials/topbar.php';

auth_ensure_staff_users_table($conn);

$flashType = '';
$flashMessage = '';

$allowedRoles = [
    'principal' => 'Principal',
    'receptionist' => 'Receptionist',
    'principal_helper' => 'Principal Helper',
    'inventory_manager' => 'Inventory Manager',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    auth_require_permission('staff_users', 'create', 'index.php');
    $name = trim((string)($_POST['name'] ?? ''));
    $email = filter_var((string)($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    $role = (string)($_POST['role'] ?? '');

    if ($name === '' || $email === false || $password === '' || !isset($allowedRoles[$role])) {
        $flashType = 'danger';
        $flashMessage = 'Please provide valid name, email, password, and role.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO staff_users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
        if ($stmt) {
            try {
                $stmt->bind_param('ssss', $name, $email, $hash, $role);
                $stmt->execute();
                $flashType = 'success';
                $flashMessage = 'Staff user created successfully.';
            } catch (mysqli_sql_exception $e) {
                $flashType = 'danger';
                $flashMessage = ((int)$e->getCode() === 1062) ? 'Email already exists.' : 'Failed to create staff user.';
            }
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_staff'])) {
    auth_require_permission('staff_users', 'edit', 'index.php');
    $staffId = (int)($_POST['staff_id'] ?? 0);
    if ($staffId > 0) {
        $stmt = $conn->prepare("UPDATE staff_users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $staffId);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Staff user status updated.';
        }
    }
}

$rows = [];
$res = $conn->query("SELECT id, name, email, role, is_active, created_at FROM staff_users ORDER BY role ASC, name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
}
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Staff & Roles</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Create Staff Account</h6>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Password</label>
                        <input type="text" name="password" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Role</label>
                        <select name="role" class="form-control" required>
                            <option value="">Select role</option>
                            <?php foreach ($allowedRoles as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-1 d-flex align-items-end">
                        <button type="submit" name="create_staff" class="btn btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Existing Staff Accounts</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars((string)$row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$allowedRoles[(string)$row['role']] ?? (string)$row['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo ((int)$row['is_active'] === 1) ? 'Active' : 'Disabled'; ?></td>
                            <td><?php echo htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="staff_id" value="<?php echo (int)$row['id']; ?>">
                                    <button type="submit" name="toggle_staff" class="btn btn-sm btn-warning">
                                        <?php echo ((int)$row['is_active'] === 1) ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No staff users created yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
