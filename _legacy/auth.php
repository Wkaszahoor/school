<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function auth_roles(): array
{
    return [
        'admin',
        'principal',
        'teacher',
        'receptionist',
        'principal_helper',
        'inventory_manager',
        'doctor',
    ];
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_login_user(string $role, int $id, string $email, string $name): void
{
    session_regenerate_id(true);
    $_SESSION['auth_role'] = $role;
    $_SESSION['auth_user_id'] = $id;
    $_SESSION['auth_email'] = $email;
    $_SESSION['auth_name'] = $name;

    // Backward compatibility with existing code paths.
    if ($role === 'admin') {
        $_SESSION['id'] = $email;
    } elseif ($role === 'teacher') {
        $_SESSION['id'] = $email;
        $_SESSION['teacher_id'] = $id;
        $_SESSION['teacher_email'] = $email;
        $_SESSION['teacher_name'] = $name;
    } elseif ($role === 'doctor') {
        $_SESSION['doctor_id'] = $id;
        $_SESSION['doctor_email'] = $email;
        $_SESSION['doctor_name'] = $name;
    }
}

function auth_current_role(): string
{
    return (string)($_SESSION['auth_role'] ?? '');
}

function auth_is_logged_in(): bool
{
    return auth_current_role() !== '' && (int)($_SESSION['auth_user_id'] ?? 0) > 0;
}

function auth_require_roles(array $roles, string $loginPath): void
{
    $role = auth_current_role();
    if ($role === '' || !in_array($role, $roles, true)) {
        header('Location: ' . $loginPath);
        exit();
    }
}

function auth_permissions(): array
{
    return [
        'class_year' => [
            'view' => ['admin', 'principal'],
            'create' => ['admin'],
            'edit' => ['admin'],
            'delete' => ['admin'],
        ],
        'students' => [
            'view' => ['admin', 'principal', 'receptionist', 'principal_helper'],
            'create' => ['admin', 'principal', 'receptionist', 'principal_helper'],
            'edit' => ['admin', 'principal', 'receptionist', 'principal_helper'],
            'delete' => ['admin', 'principal'],
            'import' => ['admin', 'receptionist'],
            'export' => ['admin', 'principal', 'receptionist', 'principal_helper'],
            'assign_group' => ['admin', 'principal', 'principal_helper'],
            'populate' => ['admin'],
        ],
        'teachers' => [
            'view' => ['admin', 'principal'],
            'create' => ['admin', 'principal'],
            'edit' => ['admin'],
            'delete' => ['admin'],
            'assign' => ['admin', 'principal'],
        ],
        'subjects' => [
            'view' => ['admin', 'principal', 'principal_helper'],
            'create' => ['admin', 'principal'],
            'edit' => ['admin', 'principal'],
            'delete' => ['admin', 'principal'],
            'seed' => ['admin', 'principal'],
        ],
        'attendance_reports' => [
            'view' => ['admin', 'principal', 'teacher'],
            'create' => ['teacher'],
            'edit' => ['teacher', 'admin'],
            'approve' => ['principal'],
            'export' => ['admin', 'principal'],
        ],
        'results_reports' => [
            'view' => ['admin', 'principal', 'teacher'],
            'create' => ['teacher'],
            'edit' => ['teacher', 'admin'],
            'approve' => ['principal'],
            'export' => ['admin', 'principal', 'teacher'],
        ],
        'staff_users' => [
            'view' => ['admin'],
            'create' => ['admin'],
            'edit' => ['admin'],
            'delete' => ['admin'],
        ],
        'admin_dashboard' => [
            'view' => ['admin', 'principal'],
        ],
        'medical_records' => [
            'view' => ['admin', 'principal', 'doctor'],
            'create' => ['admin', 'principal'],
            'edit' => ['admin', 'doctor'],
            'approve' => ['principal'],
        ],
        'leave_requests' => [
            'view' => ['admin', 'principal', 'teacher'],
            'create' => ['admin', 'principal', 'teacher'],
            'approve' => ['principal'],
        ],
        'teacher_profile' => [
            'view' => ['teacher'],
            'edit' => ['teacher'],
            'change_password' => ['teacher'],
        ],
        'teacher_workspace' => [
            'view' => ['teacher'],
            'create' => ['teacher'],
            'edit' => ['teacher'],
            'export' => ['teacher'],
        ],
        'inbox_messages' => [
            'view' => ['principal', 'teacher'],
            'create' => ['principal', 'teacher'],
            'edit' => ['principal', 'teacher'],
        ],
        'doctor_records' => [
            'view' => ['doctor', 'admin', 'principal'],
            'approve' => ['principal'],
            'examine' => ['doctor'],
        ],
        'inventory' => [
            'view' => ['inventory_manager', 'admin', 'principal'],
            'create' => ['inventory_manager', 'admin'],
            'edit' => ['inventory_manager', 'admin'],
            'delete' => ['inventory_manager', 'admin'],
            'export' => ['inventory_manager', 'admin', 'principal'],
        ],
        'lesson_plans' => [
            'view' => ['admin', 'principal', 'teacher'],
            'create' => ['teacher'],
            'approve' => ['principal', 'admin'],
            'edit' => ['teacher', 'principal', 'admin'],
            'export' => ['principal', 'admin'],
        ],
        'discipline' => [
            'view' => ['admin', 'principal', 'teacher'],
            'create' => ['admin', 'principal', 'teacher'],
            'edit' => ['admin', 'principal', 'teacher'],
            'delete' => ['admin', 'principal'],
            'meeting' => ['admin', 'principal'],
        ],
    ];
}

function auth_ensure_rbac_tables(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_key VARCHAR(40) NOT NULL UNIQUE,
            role_name VARCHAR(120) NOT NULL,
            is_system TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    $conn->query("
        CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            resource_key VARCHAR(80) NOT NULL,
            action_key VARCHAR(40) NOT NULL,
            permission_name VARCHAR(190) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_permission (resource_key, action_key)
        )
    ");
    $conn->query("
        CREATE TABLE IF NOT EXISTS role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            is_allowed TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_role_permission (role_id, permission_id),
            KEY idx_role_perm_role (role_id),
            KEY idx_role_perm_perm (permission_id)
        )
    ");
    $conn->query("
        CREATE TABLE IF NOT EXISTS user_roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_source VARCHAR(30) NOT NULL,
            user_ref_id INT NOT NULL,
            role_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_role (user_source, user_ref_id, role_id),
            KEY idx_user_roles_role (role_id)
        )
    ");
}

function auth_sync_rbac_matrix(mysqli $conn): void
{
    auth_ensure_rbac_tables($conn);

    $roleStmt = $conn->prepare("INSERT INTO roles (role_key, role_name, is_system) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE role_name = VALUES(role_name)");
    if ($roleStmt) {
        foreach (auth_roles() as $role) {
            $roleName = ucwords(str_replace('_', ' ', $role));
            $roleStmt->bind_param('ss', $role, $roleName);
            $roleStmt->execute();
        }
        $roleStmt->close();
    }

    $permStmt = $conn->prepare("INSERT INTO permissions (resource_key, action_key, permission_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE permission_name = VALUES(permission_name)");
    if ($permStmt) {
        foreach (auth_permissions() as $resource => $actions) {
            foreach ($actions as $action => $roles) {
                $permissionName = ucfirst(str_replace('_', ' ', $resource)) . ' / ' . ucfirst($action);
                $permStmt->bind_param('sss', $resource, $action, $permissionName);
                $permStmt->execute();
            }
        }
        $permStmt->close();
    }

    $matrix = auth_permissions();
    foreach ($matrix as $resource => $actions) {
        foreach ($actions as $action => $roles) {
            foreach ($roles as $roleKey) {
                $roleEsc = $conn->real_escape_string($roleKey);
                $resourceEsc = $conn->real_escape_string($resource);
                $actionEsc = $conn->real_escape_string($action);
                $conn->query("
                    INSERT INTO role_permissions (role_id, permission_id, is_allowed)
                    SELECT r.id, p.id, 1
                    FROM roles r
                    JOIN permissions p ON p.resource_key = '{$resourceEsc}' AND p.action_key = '{$actionEsc}'
                    WHERE r.role_key = '{$roleEsc}'
                    ON DUPLICATE KEY UPDATE is_allowed = is_allowed
                ");
            }
        }
    }
}

function auth_db_permission_matrix(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    $dbFile = __DIR__ . '/db.php';
    if (!file_exists($dbFile)) {
        return $cache;
    }
    $conn = null;
    include $dbFile;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return $cache;
    }
    auth_sync_rbac_matrix($conn);
    $sql = "
        SELECT r.role_key, p.resource_key, p.action_key, rp.is_allowed
        FROM role_permissions rp
        JOIN roles r ON r.id = rp.role_id
        JOIN permissions p ON p.id = rp.permission_id
    ";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cache[(string)$row['role_key']][(string)$row['resource_key']][(string)$row['action_key']] = ((int)$row['is_allowed'] === 1);
        }
    }
    return $cache;
}

function auth_can(string $resource, string $action): bool
{
    $role = auth_current_role();
    if ($role === '') {
        return false;
    }

    // Admin keeps emergency full access for now.
    if ($role === 'admin') {
        return true;
    }

    $dbMatrix = auth_db_permission_matrix();
    if (isset($dbMatrix[$role]) && isset($dbMatrix[$role][$resource]) && array_key_exists($action, $dbMatrix[$role][$resource])) {
        return (bool)$dbMatrix[$role][$resource][$action];
    }

    $permissions = auth_permissions();
    $actions = $permissions[$resource] ?? null;
    if (!is_array($actions)) {
        return false;
    }

    $roles = $actions[$action] ?? [];
    return in_array($role, $roles, true);
}

function auth_require_permission(string $resource, string $action, string $loginPath): void
{
    if (!auth_is_logged_in()) {
        header('Location: ' . $loginPath);
        exit();
    }
    if (!auth_can($resource, $action)) {
        header('Location: ' . $loginPath . (str_contains($loginPath, '?') ? '&' : '?') . 'error=forbidden');
        exit();
    }
}

function auth_verify_password(string $stored, string $plain): bool
{
    $isBcrypt = password_get_info($stored)['algo'] !== null;
    $isMd5Hex = preg_match('/^[a-f0-9]{32}$/i', $stored) === 1;
    return hash_equals($stored, $plain)
        || ($isBcrypt && password_verify($plain, $stored))
        || ($isMd5Hex && hash_equals(strtolower($stored), md5($plain)));
}

function auth_dashboard_for_role(string $role): string
{
    $map = [
        'admin' => '/admin/index1.php',
        'principal' => '/principal/dashboard.php',
        'teacher' => '/students/index.php',
        'receptionist' => '/receptionist/dashboard.php',
        'principal_helper' => '/helper/dashboard.php',
        'inventory_manager' => '/inventory/dashboard.php',
        'doctor' => '/doctor/records.php',
    ];
    return $map[$role] ?? '/';
}

function auth_ensure_staff_users_table(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS staff_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(40) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    auth_sync_rbac_matrix($conn);
}

function auth_ensure_audit_logs_table(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            user_role VARCHAR(40) NOT NULL,
            user_name VARCHAR(120) NULL,
            action VARCHAR(80) NOT NULL,
            resource VARCHAR(80) NOT NULL,
            reference_id VARCHAR(120) NULL,
            old_value TEXT NULL,
            new_value TEXT NULL,
            ip_address VARCHAR(64) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function auth_audit_log(
    mysqli $conn,
    string $action,
    string $resource,
    string $referenceId = '',
    ?string $oldValue = null,
    ?string $newValue = null
): void {
    auth_ensure_audit_logs_table($conn);
    $role = auth_current_role();
    $userId = (int)($_SESSION['auth_user_id'] ?? 0);
    $userName = (string)($_SESSION['auth_name'] ?? '');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ref = trim($referenceId);

    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, user_role, user_name, action, resource, reference_id, old_value, new_value, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param(
        'issssssss',
        $userId,
        $role,
        $userName,
        $action,
        $resource,
        $ref,
        $oldValue,
        $newValue,
        $ip
    );
    $stmt->execute();
    $stmt->close();
}

function auth_audit_prepare_value($value): ?string
{
    if ($value === null) {
        return null;
    }
    if (is_string($value)) {
        return $value;
    }
    if (is_scalar($value)) {
        return (string)$value;
    }
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return null;
    }
    return $json;
}

function auth_audit_log_change(
    mysqli $conn,
    string $action,
    string $resource,
    string $referenceId = '',
    $oldValue = null,
    $newValue = null
): void {
    auth_audit_log(
        $conn,
        $action,
        $resource,
        $referenceId,
        auth_audit_prepare_value($oldValue),
        auth_audit_prepare_value($newValue)
    );
}
