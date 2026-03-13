<?php
if (!function_exists('stream_normalize_key')) {
    function stream_normalize_key(string $value): string
    {
        $v = strtolower(trim($value));
        if ($v === '') {
            return 'general';
        }

        $v = str_replace(['-', '/'], ' ', $v);
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;

        if (in_array($v, ['general', 'gen', 'all'], true)) {
            return 'general';
        }

        if (in_array($v, ['pre medical', 'medical', 'bio', 'biology', 'pre_medical'], true)) {
            return 'pre_medical';
        }

        if (in_array($v, ['pre engineering', 'engineering', 'pre_engineering'], true)) {
            return 'pre_engineering';
        }

        if (in_array($v, ['ics', 'ics math', 'ics_math', 'computer', 'computer science', 'cs', 'ict'], true)) {
            return 'ics';
        }

        return str_replace(' ', '_', $v);
    }
}

if (!function_exists('stream_legacy_allowed_streams')) {
    function stream_legacy_allowed_streams(string $subjectName): array
    {
        $subject = strtolower(trim($subjectName));
        if ($subject === '') {
            return [];
        }

        if (strpos($subject, 'computer') !== false || strpos($subject, 'ict') !== false) {
            return ['ics'];
        }

        if (strpos($subject, 'biology') !== false) {
            return ['pre_medical'];
        }

        return [];
    }
}

if (!function_exists('stream_class_has_stream_map')) {
    function stream_class_has_stream_map(mysqli $conn, int $classId): bool
    {
        static $cache = [];
        if ($classId <= 0) {
            return false;
        }

        if (array_key_exists($classId, $cache)) {
            return (bool)$cache[$classId];
        }

        $hasMap = false;
        $stmt = $conn->prepare("SELECT 1 FROM class_stream_subject_groups WHERE class_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $hasMap = (bool)$row;
        }

        $cache[$classId] = $hasMap;
        return $hasMap;
    }
}

if (!function_exists('stream_get_subject_rule')) {
    function stream_get_subject_rule(mysqli $conn, int $classId, int $subjectId, string $subjectName = ''): array
    {
        static $cache = [];

        $cacheKey = $classId . ':' . $subjectId . ':' . $subjectName;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        if ($classId <= 0 || $subjectId <= 0 || !stream_class_has_stream_map($conn, $classId)) {
            $rule = [
                'mode' => 'legacy',
                'allowed' => stream_legacy_allowed_streams($subjectName),
            ];
            $cache[$cacheKey] = $rule;
            return $rule;
        }

        $allowed = [];
        $stmt = $conn->prepare("
            SELECT DISTINCT cssg.stream_key
            FROM class_stream_subject_groups cssg
            LEFT JOIN subject_group_subjects sgs
              ON sgs.group_id = cssg.group_id
             AND sgs.subject_id = ?
            WHERE cssg.class_id = ?
              AND (LOWER(cssg.stream_key) = 'general' OR sgs.subject_id IS NOT NULL)
        ");
        if ($stmt) {
            $stmt->bind_param("ii", $subjectId, $classId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $allowed[] = stream_normalize_key((string)($row['stream_key'] ?? ''));
            }
            $stmt->close();
        }

        $allowed = array_values(array_unique(array_filter($allowed, static function ($v): bool {
            return $v !== '';
        })));

        $rule = [
            'mode' => 'mapped',
            'allowed' => $allowed,
        ];
        $cache[$cacheKey] = $rule;
        return $rule;
    }
}

if (!function_exists('stream_student_allowed')) {
    function stream_student_allowed(string $studentStream, array $rule): bool
    {
        $mode = (string)($rule['mode'] ?? 'legacy');
        $allowed = (array)($rule['allowed'] ?? []);
        $studentKey = stream_normalize_key($studentStream);

        if ($mode === 'mapped') {
            if (empty($allowed)) {
                return false;
            }
            return in_array($studentKey, $allowed, true);
        }

        if (empty($allowed)) {
            return true;
        }

        return in_array($studentKey, $allowed, true);
    }
}

if (!function_exists('stream_ensure_mapping_tables')) {
    function stream_ensure_mapping_tables(mysqli $conn): void
    {
        $conn->query("
            CREATE TABLE IF NOT EXISTS subject_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_name VARCHAR(120) NOT NULL,
                group_slug VARCHAR(40) NOT NULL UNIQUE
            )
        ");
        $conn->query("
            CREATE TABLE IF NOT EXISTS subject_group_subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id INT NOT NULL,
                subject_id INT NOT NULL,
                subject_type VARCHAR(20) NOT NULL
            )
        ");
        $conn->query("
            CREATE TABLE IF NOT EXISTS class_stream_subject_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                class_id INT NOT NULL,
                stream_key VARCHAR(40) NOT NULL,
                group_id INT NOT NULL,
                assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        $conn->query("
            CREATE TABLE IF NOT EXISTS class_subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                class_id INT NOT NULL,
                subject_id INT NOT NULL
            )
        ");
    }
}

if (!function_exists('stream_find_or_create_subject_id')) {
    function stream_find_or_create_subject_id(mysqli $conn, string $subjectName): int
    {
        $name = trim($subjectName);
        if ($name === '') {
            return 0;
        }

        $stmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(subject_name) = LOWER(?) LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return (int)$row['id'];
            }
        }

        $ins = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        if ($ins) {
            $ins->bind_param("s", $name);
            $ok = $ins->execute();
            $id = (int)$ins->insert_id;
            $ins->close();
            if ($ok && $id > 0) {
                return $id;
            }
        }

        $stmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(subject_name) = LOWER(?) LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return (int)$row['id'];
            }
        }

        return 0;
    }
}

if (!function_exists('stream_find_or_create_group_id')) {
    function stream_find_or_create_group_id(mysqli $conn, string $groupSlug, string $groupName): int
    {
        $slug = strtolower(trim($groupSlug));
        if ($slug === '') {
            return 0;
        }

        $stmt = $conn->prepare("SELECT id FROM subject_groups WHERE group_slug = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return (int)$row['id'];
            }
        }

        $name = trim($groupName) === '' ? strtoupper($slug) : trim($groupName);
        $ins = $conn->prepare("INSERT INTO subject_groups (group_name, group_slug) VALUES (?, ?)");
        if ($ins) {
            $ins->bind_param("ss", $name, $slug);
            $ok = $ins->execute();
            $id = (int)$ins->insert_id;
            $ins->close();
            if ($ok && $id > 0) {
                return $id;
            }
        }

        $stmt = $conn->prepare("SELECT id FROM subject_groups WHERE group_slug = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return (int)$row['id'];
            }
        }

        return 0;
    }
}

if (!function_exists('stream_ensure_group_subject_link')) {
    function stream_ensure_group_subject_link(mysqli $conn, int $groupId, int $subjectId, string $subjectType): void
    {
        if ($groupId <= 0 || $subjectId <= 0) {
            return;
        }
        $type = strtolower(trim($subjectType));
        if ($type !== 'major') {
            $type = 'compulsory';
        }

        $check = $conn->prepare("
            SELECT id
            FROM subject_group_subjects
            WHERE group_id = ? AND subject_id = ? AND subject_type = ?
            LIMIT 1
        ");
        if ($check) {
            $check->bind_param("iis", $groupId, $subjectId, $type);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();
            $check->close();
            if ($row) {
                return;
            }
        }

        $ins = $conn->prepare("
            INSERT INTO subject_group_subjects (group_id, subject_id, subject_type)
            VALUES (?, ?, ?)
        ");
        if ($ins) {
            $ins->bind_param("iis", $groupId, $subjectId, $type);
            $ins->execute();
            $ins->close();
        }
    }
}

if (!function_exists('stream_resolve_class_id')) {
    function stream_resolve_class_id(mysqli $conn, string $className, string $academicYear = ''): int
    {
        $class = trim($className);
        $year = trim($academicYear);
        if ($class === '') {
            return 0;
        }

        if ($year !== '') {
            $stmt = $conn->prepare("SELECT id FROM classes WHERE class = ? AND IFNULL(academic_year,'') = IFNULL(?, '') ORDER BY id DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $class, $year);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    return (int)$row['id'];
                }
            }
        }

        $stmt = $conn->prepare("SELECT id FROM classes WHERE class = ? ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $class);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return (int)$row['id'];
            }
        }

        return 0;
    }
}

if (!function_exists('stream_sync_class_subjects_from_stream_groups')) {
    function stream_sync_class_subjects_from_stream_groups(mysqli $conn, int $classId): void
    {
        if ($classId <= 0) {
            return;
        }

        $clearOld = $conn->prepare("
            DELETE cs
            FROM class_subjects cs
            JOIN subject_group_subjects sgs ON sgs.subject_id = cs.subject_id
            WHERE cs.class_id = ?
        ");
        if ($clearOld) {
            $clearOld->bind_param("i", $classId);
            $clearOld->execute();
            $clearOld->close();
        }

        $addMapped = $conn->prepare("
            INSERT INTO class_subjects (class_id, subject_id)
            SELECT DISTINCT ?, sgs.subject_id
            FROM class_stream_subject_groups cssg
            JOIN subject_group_subjects sgs ON sgs.group_id = cssg.group_id
            LEFT JOIN class_subjects cs ON cs.class_id = ? AND cs.subject_id = sgs.subject_id
            WHERE cssg.class_id = ?
              AND cs.id IS NULL
        ");
        if ($addMapped) {
            $addMapped->bind_param("iii", $classId, $classId, $classId);
            $addMapped->execute();
            $addMapped->close();
        }

        $hasGeneral = false;
        $checkGeneral = $conn->prepare("
            SELECT 1
            FROM class_stream_subject_groups
            WHERE class_id = ? AND LOWER(stream_key) = 'general'
            LIMIT 1
        ");
        if ($checkGeneral) {
            $checkGeneral->bind_param("i", $classId);
            $checkGeneral->execute();
            $hasGeneral = (bool)$checkGeneral->get_result()->fetch_assoc();
            $checkGeneral->close();
        }

        if ($hasGeneral) {
            $addAll = $conn->prepare("
                INSERT INTO class_subjects (class_id, subject_id)
                SELECT ?, s.id
                FROM subjects s
                LEFT JOIN class_subjects cs ON cs.class_id = ? AND cs.subject_id = s.id
                WHERE cs.id IS NULL
            ");
            if ($addAll) {
                $addAll->bind_param("ii", $classId, $classId);
                $addAll->execute();
                $addAll->close();
            }
        }
    }
}

if (!function_exists('stream_assign_group_to_class_stream')) {
    function stream_assign_group_to_class_stream(mysqli $conn, int $classId, string $streamKey, int $groupId): void
    {
        if ($classId <= 0 || $groupId <= 0) {
            return;
        }
        $stream = stream_normalize_key($streamKey);

        $existingIds = [];
        $stmt = $conn->prepare("
            SELECT id
            FROM class_stream_subject_groups
            WHERE class_id = ? AND LOWER(stream_key) = LOWER(?)
            ORDER BY id ASC
        ");
        if ($stmt) {
            $stmt->bind_param("is", $classId, $stream);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $existingIds[] = (int)$row['id'];
            }
            $stmt->close();
        }

        if (!empty($existingIds)) {
            $keepId = (int)$existingIds[0];
            $upd = $conn->prepare("UPDATE class_stream_subject_groups SET group_id = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param("ii", $groupId, $keepId);
                $upd->execute();
                $upd->close();
            }
            if (count($existingIds) > 1) {
                for ($i = 1; $i < count($existingIds); $i++) {
                    $dropId = (int)$existingIds[$i];
                    $del = $conn->prepare("DELETE FROM class_stream_subject_groups WHERE id = ?");
                    if ($del) {
                        $del->bind_param("i", $dropId);
                        $del->execute();
                        $del->close();
                    }
                }
            }
            return;
        }

        $ins = $conn->prepare("
            INSERT INTO class_stream_subject_groups (class_id, stream_key, group_id)
            VALUES (?, ?, ?)
        ");
        if ($ins) {
            $ins->bind_param("isi", $classId, $stream, $groupId);
            $ins->execute();
            $ins->close();
        }
    }
}

if (!function_exists('stream_subject_profile')) {
    function stream_subject_profile(string $streamKey): array
    {
        $stream = stream_normalize_key($streamKey);
        $profiles = [
            'ics' => [
                'group_slug' => 'ics',
                'group_name' => 'ICS',
                'compulsory' => ['English', 'Islamiat', 'Pakistan Studies', 'Urdu'],
                'major' => ['Computer Science', 'Mathematics', 'Physics'],
            ],
            'pre_medical' => [
                'group_slug' => 'pre_medical',
                'group_name' => 'Pre-Medical',
                'compulsory' => ['English', 'Islamiat', 'Pakistan Studies', 'Urdu'],
                'major' => ['Biology', 'Chemistry', 'Physics'],
            ],
        ];

        return $profiles[$stream] ?? [];
    }
}

if (!function_exists('stream_is_auto_assign_supported')) {
    function stream_is_auto_assign_supported(string $streamKey): bool
    {
        return !empty(stream_subject_profile($streamKey));
    }
}

if (!function_exists('stream_ensure_stream_subjects_for_class')) {
    function stream_ensure_stream_subjects_for_class(mysqli $conn, int $classId, string $streamKey): bool
    {
        if ($classId <= 0) {
            return false;
        }

        $profile = stream_subject_profile($streamKey);
        if (empty($profile)) {
            return false;
        }

        stream_ensure_mapping_tables($conn);

        $groupId = stream_find_or_create_group_id(
            $conn,
            (string)($profile['group_slug'] ?? ''),
            (string)($profile['group_name'] ?? '')
        );
        if ($groupId <= 0) {
            return false;
        }

        $compulsory = (array)($profile['compulsory'] ?? []);
        $major = (array)($profile['major'] ?? []);
        $normalizedStream = stream_normalize_key($streamKey);

        foreach ($compulsory as $subjectName) {
            $sid = stream_find_or_create_subject_id($conn, $subjectName);
            stream_ensure_group_subject_link($conn, $groupId, $sid, 'compulsory');
        }
        foreach ($major as $subjectName) {
            $sid = stream_find_or_create_subject_id($conn, $subjectName);
            stream_ensure_group_subject_link($conn, $groupId, $sid, 'major');
        }

        stream_assign_group_to_class_stream($conn, $classId, $normalizedStream, $groupId);
        stream_sync_class_subjects_from_stream_groups($conn, $classId);
        return true;
    }
}

if (!function_exists('stream_ensure_ics_subjects_for_class')) {
    function stream_ensure_ics_subjects_for_class(mysqli $conn, int $classId): bool
    {
        return stream_ensure_stream_subjects_for_class($conn, $classId, 'ics');
    }
}

if (!function_exists('stream_ensure_pre_medical_subjects_for_class')) {
    function stream_ensure_pre_medical_subjects_for_class(mysqli $conn, int $classId): bool
    {
        return stream_ensure_stream_subjects_for_class($conn, $classId, 'pre_medical');
    }
}

if (!function_exists('stream_auto_assign_for_class')) {
    function stream_auto_assign_for_class(mysqli $conn, string $className, string $academicYear, string $streamKey): bool
    {
        $stream = stream_normalize_key($streamKey);
        if (!stream_is_auto_assign_supported($stream)) {
            return false;
        }

        $classId = stream_resolve_class_id($conn, $className, $academicYear);
        if ($classId <= 0) {
            return false;
        }

        return stream_ensure_stream_subjects_for_class($conn, $classId, $stream);
    }
}

if (!function_exists('stream_auto_assign_for_student_id')) {
    function stream_auto_assign_for_student_id(mysqli $conn, int $studentId, string $streamKey): bool
    {
        if ($studentId <= 0) {
            return false;
        }
        $stream = stream_normalize_key($streamKey);
        if (!stream_is_auto_assign_supported($stream)) {
            return false;
        }

        $stmt = $conn->prepare("SELECT class, academic_year FROM students WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return false;
        }

        $className = (string)($row['class'] ?? '');
        $academicYear = (string)($row['academic_year'] ?? '');
        return stream_auto_assign_for_class($conn, $className, $academicYear, $stream);
    }
}
