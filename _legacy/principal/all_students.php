<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'view', 'index.php');
include '../db.php';
require_once __DIR__ . '/admission_helpers.php';
require_once __DIR__ . '/../scripts/stream_subject_lib.php';
include './partials/topbar.php';

function student_profile_image_src(string $img): string
{
    $img = trim($img);
    if ($img === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $img) === 1 || str_starts_with($img, 'data:') || str_starts_with($img, '/')) {
        return $img;
    }
    if (str_starts_with($img, '../')) {
        return $img;
    }
    return '../' . ltrim($img, '/');
}
?>
<style>
    .student-image-clickable {
        cursor: zoom-in;
        transition: transform 0.15s ease;
    }
    .student-image-clickable:hover {
        transform: scale(1.04);
    }
    .student-image-lightbox {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(15, 23, 42, 0.85);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .student-image-lightbox.open {
        display: flex;
    }
    .student-image-lightbox img {
        max-width: min(92vw, 980px);
        max-height: 88vh;
        border-radius: 10px;
        border: 2px solid #ffffff;
        background: #fff;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
        object-fit: contain;
    }
    .student-image-lightbox .close-btn {
        position: absolute;
        top: 14px;
        right: 18px;
        border: 0;
        border-radius: 999px;
        width: 38px;
        height: 38px;
        font-size: 22px;
        line-height: 1;
        color: #fff;
        background: rgba(17, 24, 39, 0.75);
        cursor: pointer;
    }
</style>
<?php

$flashType = '';
$flashMessage = '';
$canViewTrustNotes = admission_can_view_trust_notes();
admission_ensure_students_table($conn);
admission_ensure_student_documents_table($conn);

$profileImageExists = false;
$imgColCheck = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'students' AND column_name = 'profile_image'");
if ($imgColCheck) {
    $imgColCheck->execute();
    $profileImageExists = (int)($imgColCheck->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $imgColCheck->close();
}
if (!$profileImageExists) {
    $conn->query("ALTER TABLE students ADD COLUMN profile_image VARCHAR(255) NULL");
}

$groupOptions = [
    '' => 'Unassigned',
    'general' => 'General',
    'pre_engineering' => 'Pre-Engineering',
    'pre_medical' => 'Pre-Medical',
    'ics' => 'ICS',
    'computer' => 'Computer',
    'biology' => 'Biology',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_update_streams'])) {
        auth_require_permission('students', 'edit', 'index.php');
        $bulkStreams = $_POST['bulk_stream'] ?? [];
        $updatedCount = 0;
        $skippedCount = 0;

        if (!is_array($bulkStreams) || empty($bulkStreams)) {
            $flashType = 'warning';
            $flashMessage = 'No stream changes were submitted.';
        } else {
            $selectStmt = $conn->prepare("SELECT group_stream FROM students WHERE id = ? LIMIT 1");
            $updateStmt = $conn->prepare("UPDATE students SET group_stream = ? WHERE id = ?");
            if (!$selectStmt || !$updateStmt) {
                $flashType = 'danger';
                $flashMessage = 'Failed to prepare bulk update query.';
            } else {
                foreach ($bulkStreams as $studentIdRaw => $streamRaw) {
                    $studentId = (int)$studentIdRaw;
                    if ($studentId <= 0) {
                        $skippedCount++;
                        continue;
                    }

                    $stream = strtolower(trim((string)$streamRaw));
                    if (!array_key_exists($stream, $groupOptions)) {
                        $stream = '';
                    }

                    $oldStream = '';
                    $selectStmt->bind_param("i", $studentId);
                    $selectStmt->execute();
                    $oldStream = (string)($selectStmt->get_result()->fetch_assoc()['group_stream'] ?? '');

                    if ($oldStream === $stream) {
                        $skippedCount++;
                        continue;
                    }

                    $updateStmt->bind_param("si", $stream, $studentId);
                    $updateStmt->execute();
                    if ($updateStmt->affected_rows > 0) {
                        stream_auto_assign_for_student_id($conn, $studentId, $stream);
                        $updatedCount++;
                        auth_audit_log_change(
                            $conn,
                            'edit',
                            'student_stream',
                            (string)$studentId,
                            ['group_stream' => $oldStream],
                            ['group_stream' => $stream]
                        );
                    } else {
                        $skippedCount++;
                    }
                }
                $selectStmt->close();
                $updateStmt->close();

                if ($updatedCount > 0) {
                    $flashType = 'success';
                    $flashMessage = "Bulk stream update completed. Updated {$updatedCount} student(s).";
                    if ($skippedCount > 0) {
                        $flashMessage .= " Skipped {$skippedCount}.";
                    }
                } else {
                    $flashType = 'info';
                    $flashMessage = 'No stream values changed.';
                }
            }
        }
    }

    if (isset($_POST['update_stream'])) {
        auth_require_permission('students', 'edit', 'index.php');
        $studentId = (int)($_POST['student_id'] ?? 0);
        $stream = strtolower(trim((string)($_POST['group_stream'] ?? '')));
        if (!array_key_exists($stream, $groupOptions)) {
            $stream = '';
        }

        if ($studentId <= 0) {
            $flashType = 'danger';
            $flashMessage = 'Invalid student selected.';
        } else {
            $oldStream = '';
            $oldStmt = $conn->prepare("SELECT group_stream FROM students WHERE id = ? LIMIT 1");
            if ($oldStmt) {
                $oldStmt->bind_param("i", $studentId);
                $oldStmt->execute();
                $oldStream = (string)($oldStmt->get_result()->fetch_assoc()['group_stream'] ?? '');
                $oldStmt->close();
            }
            $stmt = $conn->prepare("UPDATE students SET group_stream = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $stream, $studentId);
                $stmt->execute();
                $stmt->close();
                stream_auto_assign_for_student_id($conn, $studentId, $stream);
                $flashType = 'success';
                $flashMessage = 'Student stream updated successfully.';
                auth_audit_log_change(
                    $conn,
                    'edit',
                    'student_stream',
                    (string)$studentId,
                    ['group_stream' => $oldStream],
                    ['group_stream' => $stream]
                );
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to update stream.';
            }
        }
    }

    if (isset($_POST['edit_student'])) {
        auth_require_permission('students', 'edit', 'index.php');
        $studentId = (int)($_POST['student_id'] ?? 0);
        $studentName = trim((string)($_POST['student_name'] ?? ''));
        $originalName = trim((string)($_POST['original_name'] ?? ''));
        $class = trim((string)($_POST['class'] ?? ''));
        $academicYear = trim((string)($_POST['academic_year'] ?? ''));
        $groupStream = strtolower(trim((string)($_POST['group_stream'] ?? '')));
        $guardianName = trim((string)($_POST['guardian_name'] ?? ''));
        $guardianContact = trim((string)($_POST['guardian_contact'] ?? ''));
        $trustNotes = $canViewTrustNotes ? trim((string)($_POST['trust_notes'] ?? '')) : '';
        $uploadImageError = '';
        $currentImg = '';
        $currentTrustNotes = '';
        $studentBefore = null;
        if ($studentId > 0) {
            $imgStmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
            if ($imgStmt) {
                $imgStmt->bind_param("i", $studentId);
                $imgStmt->execute();
                $row = $imgStmt->get_result()->fetch_assoc() ?: [];
                $studentBefore = $row ?: null;
                $currentImg = (string)($row['profile_image'] ?? '');
                $currentTrustNotes = (string)($row['trust_notes'] ?? '');
                $imgStmt->close();
            }
        }
        $profileImage = $currentImg;
        if (!$canViewTrustNotes) {
            $trustNotes = $currentTrustNotes;
        }
        if (isset($_FILES['profile_image_file']) && is_array($_FILES['profile_image_file']) && (int)($_FILES['profile_image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$okPhoto, $photoPath] = admission_upload_photo($_FILES['profile_image_file'], dirname(__DIR__));
            if ($okPhoto) {
                $profileImage = $photoPath;
            } else {
                $uploadImageError = $photoPath;
            }
        }

        if (!array_key_exists($groupStream, $groupOptions)) {
            $groupStream = '';
        }

        if ($uploadImageError !== '') {
            $flashType = 'danger';
            $flashMessage = $uploadImageError;
        } elseif ($studentId <= 0 || $studentName === '' || $class === '') {
            $flashType = 'danger';
            $flashMessage = 'Student name and class are required.';
        } else {
            $stmt = $conn->prepare("
                UPDATE students
                SET student_name = ?, original_name = ?, class = ?, academic_year = ?, group_stream = ?, guardian_name = ?, guardian_contact = ?, profile_image = ?, trust_notes = ?
                WHERE id = ?
            ");
            if ($stmt) {
                $stmt->bind_param(
                    "sssssssssi",
                    $studentName,
                    $originalName,
                    $class,
                    $academicYear,
                    $groupStream,
                    $guardianName,
                    $guardianContact,
                    $profileImage,
                    $trustNotes,
                    $studentId
                );
                $stmt->execute();
                $stmt->close();
                stream_auto_assign_for_class($conn, $class, $academicYear, $groupStream);
                $flashType = 'success';
                $flashMessage = 'Student profile updated successfully.';
                auth_audit_log_change(
                    $conn,
                    'edit',
                    'student',
                    (string)$studentId,
                    $studentBefore,
                    [
                        'student_name' => $studentName,
                        'original_name' => $originalName,
                        'class' => $class,
                        'academic_year' => $academicYear,
                        'group_stream' => $groupStream,
                        'guardian_name' => $guardianName,
                        'guardian_contact' => $guardianContact,
                        'profile_image' => $profileImage,
                        'trust_notes' => $trustNotes,
                    ]
                );
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to update student.';
            }
        }
    }

    if (isset($_POST['delete_student'])) {
        auth_require_permission('students', 'delete', 'index.php');
        $studentId = (int)($_POST['student_id'] ?? 0);
        if ($studentId <= 0) {
            $flashType = 'danger';
            $flashMessage = 'Invalid student selected.';
        } else {
            $studentBefore = null;
            $oldStmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
            if ($oldStmt) {
                $oldStmt->bind_param("i", $studentId);
                $oldStmt->execute();
                $studentBefore = $oldStmt->get_result()->fetch_assoc() ?: null;
                $oldStmt->close();
            }
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $studentId);
                $stmt->execute();
                $deleted = $stmt->affected_rows > 0;
                $stmt->close();
                if ($deleted) {
                    $flashType = 'success';
                    $flashMessage = 'Student deleted successfully.';
                    auth_audit_log_change($conn, 'delete', 'student', (string)$studentId, $studentBefore, ['deleted' => true]);
                } else {
                    $flashType = 'warning';
                    $flashMessage = 'Student not found.';
                }
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to delete student.';
            }
        }
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$classFilter = trim((string)($_GET['class'] ?? ''));
$streamFilter = trim((string)($_GET['stream'] ?? ''));
$editId = (int)($_GET['edit_id'] ?? 0);
$sortKey = strtolower(trim((string)($_GET['sort'] ?? 'class')));
$sortDir = strtolower(trim((string)($_GET['dir'] ?? 'asc')));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 25);
$allowedPerPage = [25, 50, 100];
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 25;
}

$sortMap = [
    'kort_id' => 'StudentId',
    'name' => 'student_name',
    'original_name' => 'original_name',
    'class' => 'class',
    'session' => 'academic_year',
    'stream' => 'group_stream',
    'guardian' => 'guardian_name',
    'contact' => 'guardian_contact',
];
if (!isset($sortMap[$sortKey])) {
    $sortKey = 'class';
}
$sortColumn = $sortMap[$sortKey];
if (!in_array($sortDir, ['asc', 'desc'], true)) {
    $sortDir = 'asc';
}

$classes = [];
$classRes = $conn->query("SELECT DISTINCT class FROM students ORDER BY class ASC");
while ($classRes && $row = $classRes->fetch_assoc()) {
    $classes[] = (string)($row['class'] ?? '');
}

$streams = [];
$streamRes = $conn->query("SELECT DISTINCT group_stream FROM students WHERE IFNULL(group_stream,'') <> '' ORDER BY group_stream ASC");
while ($streamRes && $row = $streamRes->fetch_assoc()) {
    $streams[] = (string)($row['group_stream'] ?? '');
}

$whereSql = "";
$params = [];
$types = '';

if ($q !== '') {
    $whereSql .= " AND (StudentId LIKE ? OR student_name LIKE ? OR original_name LIKE ? OR class LIKE ? OR academic_year LIKE ? OR group_stream LIKE ? OR guardian_name LIKE ? OR guardian_contact LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssssssss';
}
if ($classFilter !== '') {
    $whereSql .= " AND class = ?";
    $params[] = $classFilter;
    $types .= 's';
}
if ($streamFilter !== '') {
    $whereSql .= " AND group_stream = ?";
    $params[] = $streamFilter;
    $types .= 's';
}

$countSql = "SELECT COUNT(*) AS total FROM students WHERE 1=1" . $whereSql;
$totalRecords = 0;
$countStmt = $conn->prepare($countSql);
if ($countStmt) {
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRecords = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();
}

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT id, StudentId, student_name, original_name, class, academic_year, group_stream, guardian_name, guardian_contact, profile_image, trust_notes
    FROM students
    WHERE 1=1
    $whereSql
    ORDER BY $sortColumn " . strtoupper($sortDir) . ", id ASC
    LIMIT ? OFFSET ?
";

$students = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    $runParams = $params;
    $runParams[] = $perPage;
    $runParams[] = $offset;
    $runTypes = $types . 'ii';
    if (!empty($runParams)) {
        $stmt->bind_param($runTypes, ...$runParams);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

$baseState = [
    'q' => $q,
    'class' => $classFilter,
    'stream' => $streamFilter,
    'sort' => $sortKey,
    'dir' => $sortDir,
    'per_page' => $perPage,
];
$stateQuery = http_build_query($baseState);

function sort_link(array $baseState, string $key, string $currentSort, string $currentDir): string
{
    $nextDir = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
    $params = $baseState;
    $params['sort'] = $key;
    $params['dir'] = $nextDir;
    $params['page'] = 1;
    return 'all_students.php?' . http_build_query($params);
}

function sort_indicator(string $key, string $currentSort, string $currentDir): string
{
    if ($key !== $currentSort) {
        return '';
    }
    return $currentDir === 'asc' ? ' ▲' : ' ▼';
}

$editStudent = null;
if ($editId > 0) {
    $editStmt = $conn->prepare("
        SELECT id, StudentId, student_name, original_name, class, academic_year, group_stream, guardian_name, guardian_contact, profile_image
               , trust_notes
        FROM students WHERE id = ? LIMIT 1
    ");
    if ($editStmt) {
        $editStmt->bind_param("i", $editId);
        $editStmt->execute();
        $editStudent = $editStmt->get_result()->fetch_assoc() ?: null;
        $editStmt->close();
    }
}
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">All Students</h1>
        <div>
            <a href="datesheet.php" class="btn btn-outline-primary btn-sm">Datesheet</a>
            <a href="class_wise_cards.php" class="btn btn-outline-info btn-sm">Class-wise Print</a>
            <a href="add_student.php" class="btn btn-primary btn-sm">Add Admission</a>
        </div>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType ?: 'info', ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($editStudent): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Student: <?php echo htmlspecialchars((string)$editStudent['StudentId'], ENT_QUOTES, 'UTF-8'); ?></h6>
            </div>
            <div class="card-body">
                <form method="post" action="all_students.php?<?php echo htmlspecialchars($stateQuery . '&page=' . (int)$page, ENT_QUOTES, 'UTF-8'); ?>" class="form-row" enctype="multipart/form-data">
                    <input type="hidden" name="student_id" value="<?php echo (int)$editStudent['id']; ?>">
                    <div class="form-group col-md-3">
                        <label>Student Name</label>
                        <input type="text" class="form-control" name="student_name" value="<?php echo htmlspecialchars((string)$editStudent['student_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Original Name</label>
                        <input type="text" class="form-control" name="original_name" value="<?php echo htmlspecialchars((string)($editStudent['original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Class</label>
                        <input type="text" class="form-control" name="class" value="<?php echo htmlspecialchars((string)$editStudent['class'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Session</label>
                        <input type="text" class="form-control" name="academic_year" value="<?php echo htmlspecialchars((string)($editStudent['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Stream</label>
                        <select class="form-control" name="group_stream">
                            <?php foreach ($groupOptions as $streamValue => $streamLabel): ?>
                                <option value="<?php echo htmlspecialchars($streamValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string)($editStudent['group_stream'] ?? '') === $streamValue) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($streamLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Guardian Name</label>
                        <input type="text" class="form-control" name="guardian_name" value="<?php echo htmlspecialchars((string)($editStudent['guardian_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Guardian Contact</label>
                        <input type="text" class="form-control" name="guardian_contact" value="<?php echo htmlspecialchars((string)($editStudent['guardian_contact'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Or Upload Image</label>
                        <input type="file" class="form-control-file" name="profile_image_file" accept=".jpg,.jpeg,.png,.webp,image/*">
                        <?php
                        $editImg = student_profile_image_src((string)($editStudent['profile_image'] ?? ''));
                        if ($editImg !== ''):
                        ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($editImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Student Image" class="student-image-clickable js-student-image" data-src="<?php echo htmlspecialchars($editImg, ENT_QUOTES, 'UTF-8'); ?>" style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #d1d3e2;">
                                <div class="small text-muted">Current uploaded image</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($canViewTrustNotes): ?>
                    <div class="form-group col-md-8">
                        <label>Sensitive Trust Notes (Admin/Principal only)</label>
                        <textarea class="form-control" name="trust_notes" rows="2"><?php echo htmlspecialchars((string)($editStudent['trust_notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <?php endif; ?>
                    <div class="form-group col-md-4 d-flex align-items-end">
                        <button type="submit" name="edit_student" class="btn btn-primary mr-2">Update Student</button>
                        <a href="all_students.php?<?php echo htmlspecialchars($stateQuery . '&page=' . (int)$page, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Search and Filters</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-row">
                <div class="form-group col-md-5">
                    <label>Search</label>
                    <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="ID, name, original name, class, guardian, contact">
                </div>
                <div class="form-group col-md-3">
                    <label>Class</label>
                    <select class="form-control" name="class">
                        <option value="">All</option>
                        <?php foreach ($classes as $className): ?>
                            <option value="<?php echo htmlspecialchars($className, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $classFilter === $className ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($className, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Stream</label>
                    <select class="form-control" name="stream">
                        <option value="">All</option>
                        <?php foreach ($streams as $stream): ?>
                            <option value="<?php echo htmlspecialchars($stream, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $streamFilter === $stream ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($groupOptions[$stream] ?? $stream, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-1">
                    <label>Per Page</label>
                    <select class="form-control" name="per_page">
                        <?php foreach ($allowedPerPage as $n): ?>
                            <option value="<?php echo (int)$n; ?>" <?php echo $perPage === (int)$n ? 'selected' : ''; ?>><?php echo (int)$n; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortKey, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDir, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary mr-2" type="submit">Apply</button>
                    <a class="btn btn-secondary" href="all_students.php">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Student List (<?php echo (int)$totalRecords; ?>)
                - Page <?php echo (int)$page; ?> of <?php echo (int)$totalPages; ?>
            </h6>
        </div>
        <div class="card-body table-responsive">
            <form method="post" id="bulk_stream_form" action="all_students.php?<?php echo htmlspecialchars($stateQuery . '&page=' . (int)$page, ENT_QUOTES, 'UTF-8'); ?>" class="mb-3">
                <input type="hidden" name="bulk_update_streams" value="1">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="small text-muted">Change multiple stream values below, then click save once.</div>
                    <button class="btn btn-sm btn-primary" type="button" id="save_all_streams_btn">Save All Stream Changes</button>
                </div>
                <div id="bulk_stream_hidden_inputs"></div>
            </form>
            <table class="table table-bordered table-sm">
                <thead>
                <tr>
                    <th><a href="<?php echo htmlspecialchars(sort_link($baseState, 'kort_id', $sortKey, $sortDir), ENT_QUOTES, 'UTF-8'); ?>">KORT ID<?php echo sort_indicator('kort_id', $sortKey, $sortDir); ?></a></th>
                    <th><a href="<?php echo htmlspecialchars(sort_link($baseState, 'name', $sortKey, $sortDir), ENT_QUOTES, 'UTF-8'); ?>">Name<?php echo sort_indicator('name', $sortKey, $sortDir); ?></a></th>
                    <th><a href="<?php echo htmlspecialchars(sort_link($baseState, 'original_name', $sortKey, $sortDir), ENT_QUOTES, 'UTF-8'); ?>">Original Name<?php echo sort_indicator('original_name', $sortKey, $sortDir); ?></a></th>
                    <th><a href="<?php echo htmlspecialchars(sort_link($baseState, 'class', $sortKey, $sortDir), ENT_QUOTES, 'UTF-8'); ?>">Class<?php echo sort_indicator('class', $sortKey, $sortDir); ?></a></th>
                    <th><a href="<?php echo htmlspecialchars(sort_link($baseState, 'session', $sortKey, $sortDir), ENT_QUOTES, 'UTF-8'); ?>">Session<?php echo sort_indicator('session', $sortKey, $sortDir); ?></a></th>
                    <th><a href="<?php echo htmlspecialchars(sort_link($baseState, 'stream', $sortKey, $sortDir), ENT_QUOTES, 'UTF-8'); ?>">Stream<?php echo sort_indicator('stream', $sortKey, $sortDir); ?></a></th>
                    <th><a href="<?php echo htmlspecialchars(sort_link($baseState, 'guardian', $sortKey, $sortDir), ENT_QUOTES, 'UTF-8'); ?>">Guardian<?php echo sort_indicator('guardian', $sortKey, $sortDir); ?></a></th>
                    <th><a href="<?php echo htmlspecialchars(sort_link($baseState, 'contact', $sortKey, $sortDir), ENT_QUOTES, 'UTF-8'); ?>">Contact<?php echo sort_indicator('contact', $sortKey, $sortDir); ?></a></th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $st): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$st['StudentId'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$st['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($st['original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$st['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($st['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="post" action="all_students.php?<?php echo htmlspecialchars($stateQuery . '&page=' . (int)$page, ENT_QUOTES, 'UTF-8'); ?>" class="d-flex">
                                    <input type="hidden" name="student_id" value="<?php echo (int)$st['id']; ?>">
                                    <select class="form-control form-control-sm mr-2 js-stream-select" data-student-id="<?php echo (int)$st['id']; ?>" data-original-stream="<?php echo htmlspecialchars((string)($st['group_stream'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" name="group_stream">
                                        <?php foreach ($groupOptions as $streamValue => $streamLabel): ?>
                                            <option value="<?php echo htmlspecialchars($streamValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string)($st['group_stream'] ?? '') === $streamValue) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($streamLabel, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" type="submit" name="update_stream">Save</button>
                                </form>
                            </td>
                            <td><?php echo htmlspecialchars((string)($st['guardian_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($st['guardian_contact'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-center">
                                <?php $img = trim((string)($st['profile_image'] ?? '')); ?>
                                <?php $imgSrc = student_profile_image_src($img); ?>
                                <?php if ($imgSrc !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Student" class="student-image-clickable js-student-image" data-src="<?php echo htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%;border:1px solid #d1d3e2;">
                                <?php else: ?>
                                    <span class="text-muted small">No image</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-primary" href="admission_card.php?id=<?php echo (int)$st['id']; ?>&preview=1">Print Card</a>
                                <a class="btn btn-sm btn-outline-primary" href="student_documents.php?student_id=<?php echo (int)$st['id']; ?>">Documents</a>
                                <a class="btn btn-sm btn-info" href="all_students.php?<?php echo htmlspecialchars($stateQuery . '&page=' . (int)$page . '&edit_id=' . (int)$st['id'], ENT_QUOTES, 'UTF-8'); ?>">Edit</a>
                                <form method="post" action="all_students.php?<?php echo htmlspecialchars($stateQuery . '&page=' . (int)$page, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline" onsubmit="return confirm('Delete this student?');">
                                    <input type="hidden" name="student_id" value="<?php echo (int)$st['id']; ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" name="delete_student">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center">No students found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Student pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $prevPage = max(1, $page - 1);
                        $nextPage = min($totalPages, $page + 1);
                        $prevLink = 'all_students.php?' . http_build_query(array_merge($baseState, ['page' => $prevPage]));
                        $nextLink = 'all_students.php?' . http_build_query(array_merge($baseState, ['page' => $nextPage]));
                        ?>
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($prevLink, ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
                        </li>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($p = $startPage; $p <= $endPage; $p++):
                            $pageLink = 'all_students.php?' . http_build_query(array_merge($baseState, ['page' => $p]));
                        ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars($pageLink, ENT_QUOTES, 'UTF-8'); ?>"><?php echo (int)$p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($nextLink, ENT_QUOTES, 'UTF-8'); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<div id="studentImageLightbox" class="student-image-lightbox" aria-hidden="true">
    <button type="button" class="close-btn" id="studentImageLightboxClose" aria-label="Close">&times;</button>
    <img id="studentImageLightboxImg" src="" alt="Student Preview">
</div>
<script>
(function () {
    var saveAllBtn = document.getElementById('save_all_streams_btn');
    var bulkForm = document.getElementById('bulk_stream_form');
    var hiddenInputsWrap = document.getElementById('bulk_stream_hidden_inputs');
    if (!saveAllBtn || !bulkForm || !hiddenInputsWrap) {
        return;
    }

    saveAllBtn.addEventListener('click', function () {
        var selects = document.querySelectorAll('.js-stream-select');
        hiddenInputsWrap.innerHTML = '';
        var changed = 0;

        selects.forEach(function (select) {
            var studentId = select.getAttribute('data-student-id') || '';
            var original = (select.getAttribute('data-original-stream') || '').trim();
            var current = (select.value || '').trim();
            if (!studentId) {
                return;
            }
            if (current === original) {
                return;
            }
            changed++;

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'bulk_stream[' + studentId + ']';
            input.value = current;
            hiddenInputsWrap.appendChild(input);
        });

        if (changed === 0) {
            alert('No stream values changed.');
            return;
        }

        bulkForm.submit();
    });
})();

(function () {
    var lightbox = document.getElementById('studentImageLightbox');
    var lightboxImg = document.getElementById('studentImageLightboxImg');
    var closeBtn = document.getElementById('studentImageLightboxClose');
    if (!lightbox || !lightboxImg || !closeBtn) {
        return;
    }

    function closeLightbox() {
        lightbox.classList.remove('open');
        lightbox.setAttribute('aria-hidden', 'true');
        lightboxImg.setAttribute('src', '');
    }

    function openLightbox(src, altText) {
        if (!src) {
            return;
        }
        lightboxImg.setAttribute('src', src);
        lightboxImg.setAttribute('alt', altText || 'Student Preview');
        lightbox.classList.add('open');
        lightbox.setAttribute('aria-hidden', 'false');
    }

    document.querySelectorAll('.js-student-image').forEach(function (imgEl) {
        imgEl.addEventListener('click', function () {
            openLightbox(imgEl.getAttribute('data-src') || imgEl.getAttribute('src') || '', imgEl.getAttribute('alt') || 'Student Preview');
        });
    });

    closeBtn.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lightbox.classList.contains('open')) {
            closeLightbox();
        }
    });
})();
</script>
<?php include './partials/footer.php'; ?>
