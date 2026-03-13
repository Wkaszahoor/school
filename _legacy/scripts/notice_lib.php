<?php

function notices_ensure_tables(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            body TEXT NOT NULL,
            target_scope VARCHAR(20) NOT NULL DEFAULT 'all',
            target_role VARCHAR(40) NULL,
            target_teacher_id INT NULL,
            target_class_id INT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by INT NULL,
            created_by_role VARCHAR(40) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_notice_scope_active (target_scope, is_active, id),
            KEY idx_notice_teacher (target_teacher_id),
            KEY idx_notice_class (target_class_id),
            KEY idx_notice_role (target_role)
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS notice_reads (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            notice_id INT NOT NULL,
            reader_role VARCHAR(40) NOT NULL,
            reader_user_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_notice_reader (notice_id, reader_role, reader_user_id),
            KEY idx_reader_lookup (reader_role, reader_user_id, notice_id)
        )
    ");

    $columnMap = [
        'target_scope' => "VARCHAR(20) NOT NULL DEFAULT 'all'",
        'target_role' => "VARCHAR(40) NULL",
        'target_teacher_id' => "INT NULL",
        'target_class_id' => "INT NULL",
        'created_by' => "INT NULL",
        'created_by_role' => "VARCHAR(40) NULL",
    ];

    foreach ($columnMap as $col => $def) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'notices' AND column_name = ?");
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('s', $col);
        $stmt->execute();
        $exists = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
        $stmt->close();
        if (!$exists) {
            $conn->query("ALTER TABLE notices ADD COLUMN `$col` $def");
        }
    }
}

function notices_teacher_context(mysqli $conn, int $userId): array
{
    $teacherId = (int)($_SESSION['teacher_id'] ?? 0);
    if ($teacherId <= 0) {
        $teacherId = $userId;
    }
    if ($teacherId <= 0) {
        $email = (string)($_SESSION['auth_email'] ?? ($_SESSION['id'] ?? ''));
        if ($email !== '') {
            $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $teacherId = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0);
                $stmt->close();
            }
        }
    }

    $classIds = [];
    if ($teacherId > 0) {
        $stmt1 = $conn->prepare("SELECT id FROM classes WHERE class_teacher_id = ?");
        if ($stmt1) {
            $stmt1->bind_param('i', $teacherId);
            $stmt1->execute();
            $res = $stmt1->get_result();
            while ($row = $res->fetch_assoc()) {
                $classIds[(int)$row['id']] = true;
            }
            $stmt1->close();
        }
        $stmt2 = $conn->prepare("SELECT DISTINCT class_id FROM teacher_assignments WHERE teacher_id = ?");
        if ($stmt2) {
            $stmt2->bind_param('i', $teacherId);
            $stmt2->execute();
            $res = $stmt2->get_result();
            while ($row = $res->fetch_assoc()) {
                $classIds[(int)$row['class_id']] = true;
            }
            $stmt2->close();
        }
    }

    return [
        'teacher_id' => $teacherId,
        'class_ids' => array_keys($classIds),
    ];
}

function notices_fetch_for_user(mysqli $conn, string $role, int $userId, bool $onlyUnread = false, int $limit = 8): array
{
    notices_ensure_tables($conn);

    $teacherCtx = ['teacher_id' => 0, 'class_ids' => []];
    if ($role === 'teacher') {
        $teacherCtx = notices_teacher_context($conn, $userId);
    }

    $sql = "
        SELECT n.id, n.title, n.body, n.target_scope, n.target_role, n.target_teacher_id, n.target_class_id, n.created_at,
               nr.id AS read_id, nr.read_at
        FROM notices n
        LEFT JOIN notice_reads nr
          ON nr.notice_id = n.id
         AND nr.reader_role = ?
         AND nr.reader_user_id = ?
        WHERE n.is_active = 1
          AND (
              n.target_scope = 'all'
              OR (n.target_scope = 'role' AND n.target_role = ?)
              OR (n.target_scope = 'teacher' AND ? = 'teacher' AND n.target_teacher_id = ?)
    ";

    $params = [$role, $userId, $role, $role, $teacherCtx['teacher_id']];
    $types = 'sissi';

    if ($role === 'teacher' && !empty($teacherCtx['class_ids'])) {
        $in = implode(',', array_fill(0, count($teacherCtx['class_ids']), '?'));
        $sql .= " OR (n.target_scope = 'class' AND n.target_class_id IN ($in)) ";
        foreach ($teacherCtx['class_ids'] as $cid) {
            $params[] = (int)$cid;
            $types .= 'i';
        }
    }

    $sql .= " ) ";
    if ($onlyUnread) {
        $sql .= " AND nr.id IS NULL ";
    }
    $sql .= " ORDER BY n.id DESC LIMIT ? ";
    $params[] = max(1, $limit);
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['is_read'] = ((int)($row['read_id'] ?? 0)) > 0 ? 1 : 0;
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function notices_mark_read(mysqli $conn, int $noticeId, string $role, int $userId): bool
{
    if ($noticeId <= 0 || $role === '' || $userId <= 0) {
        return false;
    }
    $stmt = $conn->prepare("INSERT IGNORE INTO notice_reads (notice_id, reader_role, reader_user_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('isi', $noticeId, $role, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function notices_mark_all_read(mysqli $conn, string $role, int $userId): int
{
    $rows = notices_fetch_for_user($conn, $role, $userId, true, 200);
    $count = 0;
    foreach ($rows as $r) {
        if (notices_mark_read($conn, (int)$r['id'], $role, $userId)) {
            $count++;
        }
    }
    return $count;
}

function notices_target_total(mysqli $conn, array $notice): int
{
    $scope = (string)($notice['target_scope'] ?? 'all');
    if ($scope === 'teacher') {
        return ((int)($notice['target_teacher_id'] ?? 0) > 0) ? 1 : 0;
    }
    if ($scope === 'class') {
        $classId = (int)($notice['target_class_id'] ?? 0);
        if ($classId <= 0) {
            return 0;
        }
        $count = 0;
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT teacher_id) AS c
            FROM (
                SELECT class_teacher_id AS teacher_id FROM classes WHERE id = ? AND class_teacher_id IS NOT NULL
                UNION ALL
                SELECT teacher_id FROM teacher_assignments WHERE class_id = ?
            ) x
            WHERE teacher_id IS NOT NULL
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $classId, $classId);
            $stmt->execute();
            $count = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
        }
        return $count;
    }
    if ($scope === 'role') {
        $role = (string)($notice['target_role'] ?? '');
        if ($role === 'teacher') {
            $res = $conn->query("SELECT COUNT(*) AS c FROM teachers");
            return (int)($res ? ($res->fetch_assoc()['c'] ?? 0) : 0);
        }
        if ($role === 'admin') {
            $res = $conn->query("SELECT COUNT(*) AS c FROM admin");
            return (int)($res ? ($res->fetch_assoc()['c'] ?? 0) : 1);
        }
        if ($role === 'principal') {
            return 1;
        }
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM staff_users WHERE role = ? AND is_active = 1");
        if ($stmt) {
            $stmt->bind_param('s', $role);
            $stmt->execute();
            $count = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
            return $count;
        }
        return 0;
    }
    return 0;
}

