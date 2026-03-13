<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('subjects', 'view', 'index.php');

include "../db.php";

$flashType = '';
$flashMessage = '';

$conn->query("\n    CREATE TABLE IF NOT EXISTS subject_groups (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        group_name VARCHAR(120) NOT NULL,\n        group_slug VARCHAR(40) NOT NULL UNIQUE\n    )\n");

$conn->query("\n    CREATE TABLE IF NOT EXISTS subject_group_subjects (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        group_id INT NOT NULL,\n        subject_id INT NOT NULL,\n        subject_type VARCHAR(20) NOT NULL\n    )\n");

$conn->query("\n    CREATE TABLE IF NOT EXISTS class_subject_groups (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        class_id INT NOT NULL UNIQUE,\n        group_id INT NOT NULL,\n        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n        KEY idx_csg_group (group_id)\n    )\n");

$conn->query("\n    CREATE TABLE IF NOT EXISTS class_stream_subject_groups (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        class_id INT NOT NULL,\n        stream_key VARCHAR(40) NOT NULL,\n        group_id INT NOT NULL,\n        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n        UNIQUE KEY uniq_class_stream_group (class_id, stream_key),\n        KEY idx_cssg_group (group_id)\n    )\n");

$conn->query("\n    CREATE TABLE IF NOT EXISTS class_subjects (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        class_id INT NOT NULL,\n        subject_id INT NOT NULL,\n        UNIQUE KEY uniq_class_subject (class_id, subject_id)\n    )\n");

$uniqCheck = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'subject_group_subjects'
      AND index_name = 'uniq_group_subject_type'
");
if ($uniqCheck) {
    $uniqCheck->execute();
    $hasUniq = (int)($uniqCheck->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $uniqCheck->close();
    if (!$hasUniq) {
        // De-duplicate before adding unique key to avoid conflicts.
        $conn->query("
            DELETE t1 FROM subject_group_subjects t1
            JOIN subject_group_subjects t2
              ON t1.group_id = t2.group_id
             AND t1.subject_id = t2.subject_id
             AND t1.subject_type = t2.subject_type
             AND t1.id > t2.id
        ");
        $conn->query("ALTER TABLE subject_group_subjects ADD UNIQUE KEY uniq_group_subject_type (group_id, subject_id, subject_type)");
    }
}

// Backward compatibility: move old class-level group mapping into stream-aware table as "general".
$conn->query("
    INSERT INTO class_stream_subject_groups (class_id, stream_key, group_id)
    SELECT csg.class_id, 'general', csg.group_id
    FROM class_subject_groups csg
    WHERE NOT EXISTS (
        SELECT 1
        FROM class_stream_subject_groups cssg
        WHERE cssg.class_id = csg.class_id
          AND LOWER(cssg.stream_key) = 'general'
    )
");

$seedGroups = [
    'general' => [
        'name' => 'General',
        'compulsory' => [],
        'major' => [],
    ],
    'pre_engineering' => [
        'name' => 'Pre-Engineering',
        'compulsory' => ['English', 'Urdu', 'Islamiat', 'Pakistan Studies'],
        'major' => ['Mathematics', 'Physics', 'Chemistry'],
    ],
    'pre_medical' => [
        'name' => 'Pre-Medical',
        'compulsory' => ['English', 'Urdu', 'Islamiat', 'Pakistan Studies'],
        'major' => ['Biology', 'Physics', 'Chemistry'],
    ],
    'ics' => [
        'name' => 'ICS',
        'compulsory' => ['English', 'Urdu', 'Islamiat', 'Pakistan Studies'],
        'major' => ['Computer Science', 'Mathematics', 'Physics'],
    ],
];

$streamLabels = [
    'general' => 'General',
    'pre_medical' => 'Pre-Medical',
    'pre_engineering' => 'Pre-Engineering',
    'ics' => 'ICS',
];

function getSubjectId(mysqli $conn, string $name): int
{
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_name = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int)$row['id'];
        }
    }

    $insert = $conn->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
    if ($insert) {
        $insert->bind_param("s", $name);
        $insert->execute();
        $id = (int)$insert->insert_id;
        $insert->close();
        return $id;
    }

    return 0;
}

function isSeniorClassForGroups(string $className): bool
{
    $name = strtolower(trim($className));
    if ($name === '') {
        return false;
    }

    if (preg_match('/\b(9|10|11|12)(st|nd|rd|th)?\b/', $name)) {
        return true;
    }

    if (strpos($name, 'first year') !== false || strpos($name, '1st year') !== false) {
        return true;
    }
    if (strpos($name, 'second year') !== false || strpos($name, '2nd year') !== false) {
        return true;
    }

    return false;
}

function normalizeStreamKey(string $value): string
{
    $v = strtolower(trim($value));
    if ($v === '' || $v === 'general' || $v === 'gen' || $v === 'all') {
        return 'general';
    }
    if ($v === 'medical' || $v === 'pre medical' || $v === 'pre-medical') {
        return 'pre_medical';
    }
    if ($v === 'engineering' || $v === 'pre engineering' || $v === 'pre-engineering') {
        return 'pre_engineering';
    }
    if ($v === 'ics' || $v === 'computer') {
        return 'ics';
    }
    return $v;
}

function syncClassSubjectsFromStreamGroups(mysqli $conn, int $classId): void
{
    // Remove old group-derived rows first.
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

    // Insert union of all group subjects assigned to this class streams.
    $addSubjects = $conn->prepare("
        INSERT IGNORE INTO class_subjects (class_id, subject_id)
        SELECT ?, sgs.subject_id
        FROM class_stream_subject_groups cssg
        JOIN subject_group_subjects sgs ON sgs.group_id = cssg.group_id
        WHERE cssg.class_id = ?
    ");
    if ($addSubjects) {
        $addSubjects->bind_param("ii", $classId, $classId);
        $addSubjects->execute();
        $addSubjects->close();
    }

    // If general stream is assigned, keep every subject available for this class.
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
        $addAllSubjects = $conn->prepare("
            INSERT IGNORE INTO class_subjects (class_id, subject_id)
            SELECT ?, s.id
            FROM subjects s
        ");
        if ($addAllSubjects) {
            $addAllSubjects->bind_param("i", $classId);
            $addAllSubjects->execute();
            $addAllSubjects->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_groups'])) {
    auth_require_permission('subjects', 'seed', 'index.php');
    // Normalize old subject names to general ones.
    $normalizeMap = [
        'Islamiat (Part I)' => 'Islamiat',
        'Pakistan Studies (Part II)' => 'Pakistan Studies',
    ];

    $caseNormalize = [
        'physics' => 'Physics',
        'chemistry' => 'Chemistry',
        'biology' => 'Biology',
        'computer science' => 'Computer Science',
        'mathematics' => 'Mathematics',
        'english' => 'English',
        'urdu' => 'Urdu',
        'islamiat' => 'Islamiat',
        'pakistan studies' => 'Pakistan Studies',
    ];
    foreach ($caseNormalize as $oldName => $newName) {
        $newId = getSubjectId($conn, $newName);
        if ($newId <= 0) {
            continue;
        }
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(subject_name) = ? AND id <> ?");
        $oldIds = [];
        if ($stmt) {
            $stmt->bind_param("si", $oldName, $newId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $oldIds[] = (int)$row['id'];
            }
            $stmt->close();
        }
        foreach ($oldIds as $oldId) {
            $conn->query("UPDATE teacher_assignments SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE class_subjects SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE subject_group_subjects SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE results SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE homework_tasks SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE lesson_plans SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE absent_reports SET subject_id = $newId WHERE subject_id = $oldId");
            $del = $conn->prepare("DELETE FROM subjects WHERE id = ?");
            if ($del) {
                $del->bind_param("i", $oldId);
                $del->execute();
                $del->close();
            }
        }
    }

    foreach ($normalizeMap as $oldName => $newName) {
        $newId = getSubjectId($conn, $newName);
        if ($newId <= 0) {
            continue;
        }

        $oldIds = [];
        $stmt = $conn->prepare("SELECT id FROM subjects WHERE subject_name = ? AND id <> ?");
        if ($stmt) {
            $stmt->bind_param("si", $oldName, $newId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $oldIds[] = (int)$row['id'];
            }
            $stmt->close();
        }

        foreach ($oldIds as $oldId) {
            $conn->query("UPDATE teacher_assignments SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE class_subjects SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE subject_group_subjects SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE results SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE homework_tasks SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE lesson_plans SET subject_id = $newId WHERE subject_id = $oldId");
            $conn->query("UPDATE absent_reports SET subject_id = $newId WHERE subject_id = $oldId");

            $del = $conn->prepare("DELETE FROM subjects WHERE id = ?");
            if ($del) {
                $del->bind_param("i", $oldId);
                $del->execute();
                $del->close();
            }
        }

        $updAssignments = $conn->prepare("UPDATE assignments SET subject = ? WHERE subject = ?");
        if ($updAssignments) {
            $updAssignments->bind_param("ss", $newName, $oldName);
            $updAssignments->execute();
            $updAssignments->close();
        }
    }

    foreach ($seedGroups as $slug => $group) {
        $groupId = 0;
        $check = $conn->prepare("SELECT id FROM subject_groups WHERE group_slug = ? LIMIT 1");
        if ($check) {
            $check->bind_param("s", $slug);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();
            $groupId = (int)($row['id'] ?? 0);
            $check->close();
        }

        if ($groupId === 0) {
            $ins = $conn->prepare("INSERT INTO subject_groups (group_name, group_slug) VALUES (?, ?)");
            if ($ins) {
                $ins->bind_param("ss", $group['name'], $slug);
                $ins->execute();
                $groupId = (int)$ins->insert_id;
                $ins->close();
            }
        }

        if ($groupId > 0) {
            $conn->query("
                DELETE t1 FROM subject_group_subjects t1
                JOIN subject_group_subjects t2
                  ON t1.group_id = t2.group_id
                 AND t1.subject_id = t2.subject_id
                 AND t1.subject_type = t2.subject_type
                 AND t1.id > t2.id
            ");

            if ($slug === 'general') {
                $conn->query("DELETE FROM subject_group_subjects WHERE group_id = $groupId");
                $allSubjects = $conn->query("SELECT id FROM subjects");
                if ($allSubjects) {
                    while ($sub = $allSubjects->fetch_assoc()) {
                        $subjectId = (int)($sub['id'] ?? 0);
                        if ($subjectId > 0) {
                            $conn->query("INSERT IGNORE INTO subject_group_subjects (group_id, subject_id, subject_type) VALUES ($groupId, $subjectId, 'compulsory')");
                        }
                    }
                }
                continue;
            }

            foreach ($group['compulsory'] as $subjectName) {
                $subjectId = getSubjectId($conn, $subjectName);
                if ($subjectId > 0) {
                    $conn->query("INSERT IGNORE INTO subject_group_subjects (group_id, subject_id, subject_type) VALUES ($groupId, $subjectId, 'compulsory')");
                }
            }
            foreach ($group['major'] as $subjectName) {
                $subjectId = getSubjectId($conn, $subjectName);
                if ($subjectId > 0) {
                    $conn->query("INSERT IGNORE INTO subject_group_subjects (group_id, subject_id, subject_type) VALUES ($groupId, $subjectId, 'major')");
                }
            }
        }
    }

    $flashType = 'success';
    $flashMessage = 'Default subject groups created/updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_group_to_class'])) {
    auth_require_permission('subjects', 'edit', 'index.php');
    $classId = (int)($_POST['class_id'] ?? 0);
    $groupId = (int)($_POST['group_id'] ?? 0);
    $streamKey = normalizeStreamKey((string)($_POST['stream_key'] ?? ''));

    if ($classId <= 0 || $groupId <= 0 || !isset($streamLabels[$streamKey])) {
        $flashType = 'danger';
        $flashMessage = 'Please select class, stream, and group.';
    } else {
        $classStmt = $conn->prepare("SELECT id, class, academic_year FROM classes WHERE id = ? LIMIT 1");
        $groupStmt = $conn->prepare("SELECT id, group_name FROM subject_groups WHERE id = ? LIMIT 1");
        $classRow = null;
        $groupRow = null;
        if ($classStmt) {
            $classStmt->bind_param("i", $classId);
            $classStmt->execute();
            $classRow = $classStmt->get_result()->fetch_assoc();
            $classStmt->close();
        }
        if ($groupStmt) {
            $groupStmt->bind_param("i", $groupId);
            $groupStmt->execute();
            $groupRow = $groupStmt->get_result()->fetch_assoc();
            $groupStmt->close();
        }

        if (!$classRow || !$groupRow) {
            $flashType = 'danger';
            $flashMessage = 'Invalid class/group selection.';
        } elseif (!isSeniorClassForGroups((string)$classRow['class'])) {
            $flashType = 'warning';
            $flashMessage = 'Subject group assignment is only allowed for Classes 9 to 12.';
        } else {
            $conn->begin_transaction();
            try {
                $saveMap = $conn->prepare("
                    INSERT INTO class_stream_subject_groups (class_id, stream_key, group_id)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE group_id = VALUES(group_id)
                ");
                if (!$saveMap) {
                    throw new Exception('Failed to prepare class-stream-group assignment query.');
                }
                $saveMap->bind_param("isi", $classId, $streamKey, $groupId);
                if (!$saveMap->execute()) {
                    throw new Exception('Failed to save class-stream-group assignment: ' . $saveMap->error);
                }
                $saveMap->close();

                // Keep class subjects as union of all stream-group mappings for this class.
                syncClassSubjectsFromStreamGroups($conn, $classId);

                $conn->commit();
                $flashType = 'success';
                $flashMessage = 'Group assigned successfully for ' . ($streamLabels[$streamKey] ?? $streamKey) . '.';
            } catch (Throwable $e) {
                $conn->rollback();
                $flashType = 'danger';
                $flashMessage = 'Assignment failed: ' . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_group_assignment'])) {
    auth_require_permission('subjects', 'edit', 'index.php');
    $classId = (int)($_POST['class_id'] ?? 0);
    $streamKey = normalizeStreamKey((string)($_POST['stream_key'] ?? ''));
    if ($classId <= 0 || !isset($streamLabels[$streamKey])) {
        $flashType = 'danger';
        $flashMessage = 'Invalid class/stream selected.';
    } else {
        $del = $conn->prepare("DELETE FROM class_stream_subject_groups WHERE class_id = ? AND stream_key = ?");
        if ($del) {
            $del->bind_param("is", $classId, $streamKey);
            if ($del->execute()) {
                syncClassSubjectsFromStreamGroups($conn, $classId);
                $flashType = 'success';
                $flashMessage = 'Class stream group assignment removed.';
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to remove class group assignment.';
            }
            $del->close();
        } else {
            $flashType = 'danger';
            $flashMessage = 'Failed to prepare remove query.';
        }
    }
}

$groups = [];
$res = $conn->query("SELECT id, group_name, group_slug FROM subject_groups ORDER BY group_name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $groups[] = $row;
    }
}

$seniorClasses = [];
$resSenior = $conn->query("
    SELECT c.id, c.class, c.academic_year, c.class_teacher_id, t.name AS class_teacher_name
    FROM classes c
    LEFT JOIN teachers t ON t.id = c.class_teacher_id
    ORDER BY c.academic_year DESC, c.class ASC
");
if ($resSenior) {
    while ($row = $resSenior->fetch_assoc()) {
        if (isSeniorClassForGroups((string)($row['class'] ?? ''))) {
            $seniorClasses[] = $row;
        }
    }
}

$classGroupAssignments = [];
if (!empty($seniorClasses)) {
    $seniorIds = array_map(static function ($r): int {
        return (int)($r['id'] ?? 0);
    }, $seniorClasses);
    $seniorIds = array_values(array_filter($seniorIds, static function ($v): bool {
        return $v > 0;
    }));

    if (!empty($seniorIds)) {
        $placeholders = implode(',', array_fill(0, count($seniorIds), '?'));
        $sqlAssign = "
            SELECT c.id, c.class, c.academic_year, c.class_teacher_id,
                   cssg.stream_key,
                   t.name AS class_teacher_name,
                   sg.group_name, sg.group_slug,
                   (
                       SELECT GROUP_CONCAT(DISTINCT CONCAT(t2.id, '::', t2.name) ORDER BY t2.name ASC SEPARATOR '||')
                       FROM teacher_assignments ta2
                       JOIN teachers t2 ON t2.id = ta2.teacher_id
                       WHERE ta2.class_id = c.id
                   ) AS subject_teachers
            FROM classes c
            LEFT JOIN class_stream_subject_groups cssg ON cssg.class_id = c.id
            LEFT JOIN subject_groups sg ON sg.id = cssg.group_id
            LEFT JOIN teachers t ON t.id = c.class_teacher_id
            WHERE c.id IN ($placeholders)
            ORDER BY c.academic_year DESC, c.class ASC, cssg.stream_key ASC
        ";
        $stmtAssign = $conn->prepare($sqlAssign);
        if ($stmtAssign) {
            $types = str_repeat('i', count($seniorIds));
            $stmtAssign->bind_param($types, ...$seniorIds);
            $stmtAssign->execute();
            $resAssign = $stmtAssign->get_result();
            while ($row = $resAssign->fetch_assoc()) {
                $classGroupAssignments[] = $row;
            }
            $stmtAssign->close();
        }
    }
}

$groupSubjects = [];
$res = $conn->query("\n    SELECT sg.group_slug, sg.group_name, s.subject_name, sgs.subject_type\n    FROM subject_groups sg\n    JOIN subject_group_subjects sgs ON sgs.group_id = sg.id\n    JOIN subjects s ON s.id = sgs.subject_id\n    ORDER BY sg.group_name ASC, sgs.subject_type DESC, s.subject_name ASC\n");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $slug = (string)$row['group_slug'];
        if (!isset($groupSubjects[$slug])) {
            $groupSubjects[$slug] = [
                'name' => (string)$row['group_name'],
                'compulsory' => [],
                'major' => [],
            ];
        }
        if ($row['subject_type'] === 'compulsory') {
            $groupSubjects[$slug]['compulsory'][] = $row['subject_name'];
        } else {
            $groupSubjects[$slug]['major'][] = $row['subject_name'];
        }
    }
}

include "./partials/topbar.php";

$totalGroups = count($groups);
$totalSeniorClasses = count($seniorClasses);
$totalAssignedStreams = 0;
foreach ($classGroupAssignments as $assignedRow) {
    if (!empty($assignedRow['group_slug'])) {
        $totalAssignedStreams++;
    }
}
?>

<style>
    .subject-groups-page .page-head {
        gap: 12px;
    }
    .subject-groups-page .page-subtitle {
        color: #6c757d;
        margin-bottom: 0;
    }
    .subject-groups-page .stats-card {
        border: 0;
        border-left: 4px solid #4e73df;
    }
    .subject-groups-page .stats-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #7b8a8b;
        margin-bottom: 3px;
    }
    .subject-groups-page .stats-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: #2c3e50;
        line-height: 1.1;
    }
    .subject-groups-page .helper-text {
        font-size: 0.88rem;
        color: #6c757d;
    }
    .subject-groups-page .required-mark {
        color: #dc3545;
        font-weight: 600;
        margin-left: 2px;
    }
    .subject-groups-page .teacher-links a {
        display: inline-block;
        margin: 2px 8px 2px 0;
    }
    .subject-groups-page .group-card {
        border: 0;
    }
    .subject-groups-page .section-title {
        font-weight: 700;
        color: #4e73df;
        margin-bottom: 8px;
    }
    .subject-groups-page .subject-chip-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .subject-groups-page .subject-chip-list li {
        display: inline-block;
        margin: 0 8px 8px 0;
    }
    .subject-groups-page .subject-chip {
        display: inline-block;
        font-size: 0.82rem;
        font-weight: 600;
        border-radius: 999px;
        padding: 5px 11px;
        border: 1px solid transparent;
    }
    .subject-groups-page .subject-chip.compulsory {
        background: #eef4ff;
        border-color: #d6e3ff;
        color: #2f5ea7;
    }
    .subject-groups-page .subject-chip.major {
        background: #f2fcf8;
        border-color: #d6f4e8;
        color: #1d7b5f;
    }
    .subject-groups-page .table thead th {
        vertical-align: middle;
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        .subject-groups-page .page-head {
            flex-direction: column;
            align-items: flex-start !important;
        }
        .subject-groups-page .btn-toolbar {
            width: 100%;
        }
        .subject-groups-page .btn-toolbar .btn {
            margin-bottom: 8px;
        }
    }
</style>

<div class="container-fluid subject-groups-page">
    <div class="d-sm-flex justify-content-between align-items-center mb-4 page-head">
        <div>
            <h1 class="h3 text-gray-800 mb-1">Subject Groups</h1>
            <p class="page-subtitle">Manage stream-wise subject mapping for senior classes and teacher visibility.</p>
        </div>
        <div class="btn-toolbar">
            <a class="btn btn-secondary btn-sm mr-2" href="../admin/subjects.php">Subjects</a>
            <a class="btn btn-outline-secondary btn-sm" href="../admin/class_subjects.php">Class Subjects</a>
        </div>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?> shadow-sm" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm stats-card h-100">
                <div class="card-body">
                    <div class="stats-label">Total Subject Groups</div>
                    <div class="stats-value"><?php echo (int)$totalGroups; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm stats-card h-100" style="border-left-color:#1cc88a;">
                <div class="card-body">
                    <div class="stats-label">Senior Classes (9-12)</div>
                    <div class="stats-value"><?php echo (int)$totalSeniorClasses; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm stats-card h-100" style="border-left-color:#f6c23e;">
                <div class="card-body">
                    <div class="stats-label">Assigned Stream Mappings</div>
                    <div class="stats-value"><?php echo (int)$totalAssignedStreams; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Default Groups</h6>
        </div>
        <div class="card-body">
            <p class="helper-text mb-3">Create or refresh default subject groups for General, Pre-Engineering, Pre-Medical, and ICS.</p>
            <form method="POST" action="" class="d-flex flex-wrap align-items-center">
                <button type="submit" name="seed_groups" class="btn btn-primary">Create Default Groups</button>
                <span class="helper-text ml-3 mt-2 mt-sm-0">This updates group subjects and keeps mappings consistent.</span>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Assign Group to Classes (9 to 12 Only)</h6>
        </div>
        <div class="card-body">
            <p class="helper-text mb-3">Groups are assigned by <strong>Class + Student Stream</strong>, not to the whole class.</p>
            <form method="POST" action="" class="form-row">
                <div class="form-group col-md-4">
                    <label>Class<span class="required-mark">*</span></label>
                    <select name="class_id" class="form-control" required>
                        <option value="">Select class</option>
                        <?php foreach ($seniorClasses as $classRow): ?>
                            <option value="<?php echo (int)$classRow['id']; ?>">
                                <?php echo htmlspecialchars((string)$classRow['class'], ENT_QUOTES, 'UTF-8'); ?>
                                (<?php echo htmlspecialchars((string)($classRow['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Student Stream<span class="required-mark">*</span></label>
                    <select name="stream_key" class="form-control" required>
                        <option value="">Select stream</option>
                        <?php foreach ($streamLabels as $streamKey => $streamLabel): ?>
                            <option value="<?php echo htmlspecialchars($streamKey, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($streamLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Subject Group<span class="required-mark">*</span></label>
                    <select name="group_id" class="form-control" required>
                        <option value="">Select group</option>
                        <?php foreach ($groups as $groupRow): ?>
                            <option value="<?php echo (int)$groupRow['id']; ?>">
                                <?php echo htmlspecialchars((string)$groupRow['group_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="submit" name="assign_group_to_class" class="btn btn-success w-100">Assign</button>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Class</th>
                            <th>Academic Year</th>
                            <th>Student Stream</th>
                            <th>Assigned Group</th>
                            <th>Class Teacher</th>
                            <th>Subject Teachers</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($classGroupAssignments)): ?>
                            <?php foreach ($classGroupAssignments as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$row['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php
                                        $streamKey = (string)($row['stream_key'] ?? '');
                                        $streamLabel = (string)($streamLabels[$streamKey] ?? $streamKey);
                                        $streamBadgeClass = 'badge-secondary';
                                        if ($streamKey === 'general') {
                                            $streamBadgeClass = 'badge-dark';
                                        } elseif ($streamKey === 'pre_medical') {
                                            $streamBadgeClass = 'badge-danger';
                                        } elseif ($streamKey === 'pre_engineering') {
                                            $streamBadgeClass = 'badge-info';
                                        } elseif ($streamKey === 'ics') {
                                            $streamBadgeClass = 'badge-primary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $streamBadgeClass; ?>"><?php echo htmlspecialchars($streamLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['group_name'])): ?>
                                            <span class="font-weight-semibold"><?php echo htmlspecialchars((string)$row['group_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)($row['class_teacher_id'] ?? 0) > 0): ?>
                                            <a href="teacher_profile.php?id=<?php echo (int)$row['class_teacher_id']; ?>">
                                                <?php echo htmlspecialchars((string)($row['class_teacher_name'] ?? ('Teacher #' . (int)$row['class_teacher_id'])), ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $teacherTokens = array_values(array_filter(explode('||', (string)($row['subject_teachers'] ?? ''))));
                                        if (!empty($teacherTokens)):
                                            $links = [];
                                            foreach ($teacherTokens as $token) {
                                                $parts = explode('::', $token, 2);
                                                $tId = (int)($parts[0] ?? 0);
                                                $tName = (string)($parts[1] ?? '');
                                                if ($tId > 0) {
                                                    $label = $tName !== '' ? $tName : ('Teacher #' . $tId);
                                                    $links[] = '<a href="teacher_profile.php?id=' . $tId . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
                                                }
                                            }
                                            echo !empty($links) ? '<div class="teacher-links">' . implode('', $links) . '</div>' : '<span class="text-muted">Not Assigned</span>';
                                        else:
                                            ?>
                                            <span class="text-muted">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['group_slug'])): ?>
                                            <form method="POST" action="" onsubmit="return confirm('Remove group assignment for this class stream?');" style="display:inline;">
                                                <input type="hidden" name="class_id" value="<?php echo (int)$row['id']; ?>">
                                                <input type="hidden" name="stream_key" value="<?php echo htmlspecialchars((string)($row['stream_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" name="remove_group_assignment" class="btn btn-danger btn-sm btn-block">Remove</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No Classes 9 to 12 found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($groupSubjects)): ?>
            <?php foreach ($groupSubjects as $slug => $group): ?>
                <div class="col-xl-6 mb-4">
                    <div class="card shadow group-card h-100">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($group['name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                            <span class="badge badge-light border"><?php echo htmlspecialchars((string)$slug, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="section-title">Compulsory Subjects</div>
                            <?php if (!empty($group['compulsory'])): ?>
                                <ul class="subject-chip-list mb-3">
                                    <?php foreach ($group['compulsory'] as $subjectName): ?>
                                        <li><span class="subject-chip compulsory"><?php echo htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8'); ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted small mb-3">No compulsory subjects configured.</p>
                            <?php endif; ?>

                            <div class="section-title">Major Subjects</div>
                            <?php if (!empty($group['major'])): ?>
                                <ul class="subject-chip-list mb-0">
                                    <?php foreach ($group['major'] as $subjectName): ?>
                                        <li><span class="subject-chip major"><?php echo htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8'); ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted small mb-0">No major subjects configured.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning">No subject groups created yet.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include "./partials/footer.php";
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
