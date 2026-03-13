<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('staff_users', 'view', 'index.php');
include 'db.php';
include './partials/topbar.php';

auth_sync_rbac_matrix($conn);

$flashType = '';
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_matrix'])) {
    auth_require_permission('staff_users', 'edit', 'index.php');
    $allowed = $_POST['allow'] ?? [];

    $roles = [];
    $resRoles = $conn->query("SELECT id, role_key FROM roles ORDER BY role_key ASC");
    while ($resRoles && ($r = $resRoles->fetch_assoc())) {
        $roles[(int)$r['id']] = (string)$r['role_key'];
    }

    $permissions = [];
    $resPerm = $conn->query("SELECT id FROM permissions");
    while ($resPerm && ($p = $resPerm->fetch_assoc())) {
        $permissions[(int)$p['id']] = true;
    }

    $conn->query("DELETE FROM role_permissions");
    $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id, is_allowed) VALUES (?, ?, 1)");
    if ($stmt) {
        foreach ($allowed as $roleIdStr => $permIds) {
            $roleId = (int)$roleIdStr;
            if (!isset($roles[$roleId]) || !is_array($permIds)) {
                continue;
            }
            foreach ($permIds as $permIdStr) {
                $permId = (int)$permIdStr;
                if (!isset($permissions[$permId])) {
                    continue;
                }
                $stmt->bind_param('ii', $roleId, $permId);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    auth_audit_log($conn, 'update', 'rbac_matrix', 'admin_matrix');
    $flashType = 'success';
    $flashMessage = 'Role-permission matrix updated successfully.';
}

$roles = [];
$resRoles = $conn->query("SELECT id, role_key, role_name FROM roles ORDER BY role_key ASC");
while ($resRoles && ($r = $resRoles->fetch_assoc())) {
    $roles[] = $r;
}

$permissionsByResource = [];
$resPerm = $conn->query("SELECT id, resource_key, action_key FROM permissions ORDER BY resource_key ASC, action_key ASC");
while ($resPerm && ($p = $resPerm->fetch_assoc())) {
    $resource = (string)$p['resource_key'];
    if (!isset($permissionsByResource[$resource])) {
        $permissionsByResource[$resource] = [];
    }
    $permissionsByResource[$resource][] = $p;
}

$allowedMap = [];
$resAllowed = $conn->query("SELECT role_id, permission_id, is_allowed FROM role_permissions");
while ($resAllowed && ($a = $resAllowed->fetch_assoc())) {
    $allowedMap[(int)$a['role_id']][(int)$a['permission_id']] = ((int)$a['is_allowed'] === 1);
}
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Central Role-Permission Matrix (RBAC)</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Manage Access Matrix</h6>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                        <tr>
                            <th style="min-width: 190px;">Resource / Action</th>
                            <?php foreach ($roles as $role): ?>
                                <th class="text-center"><?php echo htmlspecialchars((string)$role['role_key'], ENT_QUOTES, 'UTF-8'); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($permissionsByResource as $resource => $perms): ?>
                            <?php foreach ($perms as $idx => $perm): ?>
                                <tr>
                                    <td>
                                        <?php if ($idx === 0): ?>
                                            <strong><?php echo htmlspecialchars($resource, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        <?php endif; ?>
                                        <span class="text-muted"><?php echo htmlspecialchars((string)$perm['action_key'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <?php foreach ($roles as $role): ?>
                                        <?php
                                            $roleId = (int)$role['id'];
                                            $permId = (int)$perm['id'];
                                            $checked = (bool)($allowedMap[$roleId][$permId] ?? false);
                                        ?>
                                        <td class="text-center">
                                            <input type="checkbox" name="allow[<?php echo $roleId; ?>][]" value="<?php echo $permId; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="save_matrix" class="btn btn-primary">Save Matrix</button>
            </form>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>

