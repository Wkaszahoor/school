<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('admin_dashboard', 'view', 'index.php');
include 'db.php';
include './partials/topbar.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Database connection is not available.</div></div>';
    include './partials/footer.php';
    exit();
}

auth_ensure_audit_logs_table($conn);

$q = trim((string)($_GET['q'] ?? ''));
$action = trim((string)($_GET['action'] ?? ''));
$resource = trim((string)($_GET['resource'] ?? ''));
$role = trim((string)($_GET['role'] ?? ''));
$dateFrom = trim((string)($_GET['from'] ?? ''));
$dateTo = trim((string)($_GET['to'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 50);
$allowedPerPage = [25, 50, 100, 200];
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 50;
}
$offset = ($page - 1) * $perPage;

$where = [];
$types = '';
$params = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = "(user_name LIKE ? OR action LIKE ? OR resource LIKE ? OR reference_id LIKE ? OR old_value LIKE ? OR new_value LIKE ?)";
    $types .= 'ssssss';
    array_push($params, $like, $like, $like, $like, $like, $like);
}
if ($action !== '') {
    $where[] = "action = ?";
    $types .= 's';
    $params[] = $action;
}
if ($resource !== '') {
    $where[] = "resource = ?";
    $types .= 's';
    $params[] = $resource;
}
if ($role !== '') {
    $where[] = "user_role = ?";
    $types .= 's';
    $params[] = $role;
}
if ($dateFrom !== '') {
    $where[] = "DATE(created_at) >= ?";
    $types .= 's';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = "DATE(created_at) <= ?";
    $types .= 's';
    $params[] = $dateTo;
}
$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) AS total FROM audit_logs" . $whereSql;
$countStmt = $conn->prepare($countSql);
$totalRows = 0;
if ($countStmt) {
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRows = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();
}
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$rows = [];
$sql = "
    SELECT id, user_role, user_name, action, resource, reference_id, old_value, new_value, ip_address, created_at
    FROM audit_logs
    {$whereSql}
    ORDER BY id DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $rowTypes = $types . 'ii';
    $rowParams = $params;
    $rowParams[] = $perPage;
    $rowParams[] = $offset;
    $stmt->bind_param($rowTypes, ...$rowParams);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}

$actions = [];
$res = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $actions[] = (string)$row['action'];
    }
}
$resources = [];
$res = $conn->query("SELECT DISTINCT resource FROM audit_logs ORDER BY resource ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $resources[] = (string)$row['resource'];
    }
}
$roles = [];
$res = $conn->query("SELECT DISTINCT user_role FROM audit_logs ORDER BY user_role ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $roles[] = (string)$row['user_role'];
    }
}

$base = [
    'q' => $q,
    'action' => $action,
    'resource' => $resource,
    'role' => $role,
    'from' => $dateFrom,
    'to' => $dateTo,
    'per_page' => $perPage,
];
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Audit Logs</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" class="form-row">
                <div class="form-group col-md-3">
                    <label>Search</label>
                    <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="User, action, resource, old/new value">
                </div>
                <div class="form-group col-md-2">
                    <label>Action</label>
                    <select name="action" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($actions as $it): ?>
                            <option value="<?php echo htmlspecialchars($it, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $it === $action ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($it, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Resource</label>
                    <select name="resource" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($resources as $it): ?>
                            <option value="<?php echo htmlspecialchars($it, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $it === $resource ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($it, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($roles as $it): ?>
                            <option value="<?php echo htmlspecialchars($it, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $it === $role ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($it, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-1">
                    <label>From</label>
                    <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-1">
                    <label>To</label>
                    <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-1">
                    <label>Rows</label>
                    <select name="per_page" class="form-control">
                        <?php foreach ($allowedPerPage as $n): ?>
                            <option value="<?php echo $n; ?>" <?php echo $n === $perPage ? 'selected' : ''; ?>><?php echo $n; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-12">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a class="btn btn-secondary" href="audit_logs.php">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time</th>
                        <th>Role</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Resource</th>
                        <th>Reference</th>
                        <th>IP</th>
                        <th>Change Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td><?php echo htmlspecialchars((string)$r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['user_role'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['action'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['resource'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['reference_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="min-width:280px;">
                            <details>
                                <summary>Old Value</summary>
                                <pre class="mb-2"><?php echo htmlspecialchars((string)($r['old_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></pre>
                            </details>
                            <details>
                                <summary>New Value</summary>
                                <pre class="mb-0"><?php echo htmlspecialchars((string)($r['new_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></pre>
                            </details>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="9" class="text-center">No audit logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center">
                    <div>Total: <?php echo (int)$totalRows; ?> logs</div>
                    <div>
                        <?php
                        $prev = max(1, $page - 1);
                        $next = min($totalPages, $page + 1);
                        $prevLink = 'audit_logs.php?' . http_build_query(array_merge($base, ['page' => $prev]));
                        $nextLink = 'audit_logs.php?' . http_build_query(array_merge($base, ['page' => $next]));
                        ?>
                        <a class="btn btn-sm btn-outline-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars($prevLink, ENT_QUOTES, 'UTF-8'); ?>">Prev</a>
                        <span class="mx-2">Page <?php echo (int)$page; ?> / <?php echo (int)$totalPages; ?></span>
                        <a class="btn btn-sm btn-outline-secondary <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo htmlspecialchars($nextLink, ENT_QUOTES, 'UTF-8'); ?>">Next</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>

