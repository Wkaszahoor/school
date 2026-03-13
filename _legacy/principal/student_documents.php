<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'view', '../index.php');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admission_helpers.php';
include './partials/topbar.php';

admission_ensure_students_table($conn);
admission_ensure_student_documents_table($conn);

$studentId = (int)($_GET['student_id'] ?? 0);
$student = $studentId > 0 ? admission_find_student($conn, $studentId) : null;
if (!$student) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Student not found.</div></div>';
    include './partials/footer.php';
    exit();
}

$flashType = '';
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    auth_require_permission('students', 'edit', '../index.php');
    $docType = trim((string)($_POST['doc_type'] ?? ''));
    $docTitle = trim((string)($_POST['doc_title'] ?? ''));

    if ($docType === '' || $docTitle === '') {
        $flashType = 'danger';
        $flashMessage = 'Document type and title are required.';
    } else {
        [$ok, $err, $savedName, $savedPath, $mime, $size] = admission_upload_document($_FILES['doc_file'] ?? [], dirname(__DIR__), $studentId);
        if (!$ok) {
            $flashType = 'danger';
            $flashMessage = $err;
        } else {
            $version = 1;
            $stmtV = $conn->prepare("SELECT COALESCE(MAX(version_no), 0) + 1 AS v FROM student_documents WHERE student_id = ? AND doc_type = ? AND doc_title = ?");
            if ($stmtV) {
                $stmtV->bind_param('iss', $studentId, $docType, $docTitle);
                $stmtV->execute();
                $version = (int)($stmtV->get_result()->fetch_assoc()['v'] ?? 1);
                $stmtV->close();
            }
            $stmtOff = $conn->prepare("UPDATE student_documents SET is_active = 0 WHERE student_id = ? AND doc_type = ? AND doc_title = ?");
            if ($stmtOff) {
                $stmtOff->bind_param('iss', $studentId, $docType, $docTitle);
                $stmtOff->execute();
                $stmtOff->close();
            }

            $role = auth_current_role();
            $uid = (int)($_SESSION['auth_user_id'] ?? 0);
            $name = (string)($_SESSION['auth_name'] ?? '');
            $stmt = $conn->prepare("
                INSERT INTO student_documents
                (student_id, doc_type, doc_title, version_no, file_name, file_path, mime_type, file_size, is_active, uploaded_by_role, uploaded_by_id, uploaded_by_name)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param('ississsisis', $studentId, $docType, $docTitle, $version, $savedName, $savedPath, $mime, $size, $role, $uid, $name);
                $stmt->execute();
                $docId = (int)$stmt->insert_id;
                $stmt->close();
                auth_audit_log($conn, 'create', 'student_document', (string)$docId, null, json_encode(['student_id' => $studentId, 'type' => $docType, 'title' => $docTitle, 'version' => $version]));
                $flashType = 'success';
                $flashMessage = 'Document uploaded successfully (version ' . $version . ').';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_active_doc'])) {
    auth_require_permission('students', 'edit', '../index.php');
    $docId = (int)($_POST['doc_id'] ?? 0);
    if ($docId > 0) {
        $stmtDoc = $conn->prepare("SELECT doc_type, doc_title FROM student_documents WHERE id = ? AND student_id = ? LIMIT 1");
        if ($stmtDoc) {
            $stmtDoc->bind_param('ii', $docId, $studentId);
            $stmtDoc->execute();
            $row = $stmtDoc->get_result()->fetch_assoc();
            $stmtDoc->close();
            if ($row) {
                $docType = (string)$row['doc_type'];
                $docTitle = (string)$row['doc_title'];
                $stmtOff = $conn->prepare("UPDATE student_documents SET is_active = 0 WHERE student_id = ? AND doc_type = ? AND doc_title = ?");
                if ($stmtOff) {
                    $stmtOff->bind_param('iss', $studentId, $docType, $docTitle);
                    $stmtOff->execute();
                    $stmtOff->close();
                }
                $stmtOn = $conn->prepare("UPDATE student_documents SET is_active = 1 WHERE id = ? AND student_id = ?");
                if ($stmtOn) {
                    $stmtOn->bind_param('ii', $docId, $studentId);
                    $stmtOn->execute();
                    $stmtOn->close();
                }
                $flashType = 'success';
                $flashMessage = 'Active document version updated.';
            }
        }
    }
}

$docs = [];
$stmtDocs = $conn->prepare("
    SELECT id, doc_type, doc_title, version_no, file_name, file_path, mime_type, file_size, is_active, uploaded_by_role, uploaded_by_name, created_at
    FROM student_documents
    WHERE student_id = ?
    ORDER BY doc_type ASC, doc_title ASC, version_no DESC
");
if ($stmtDocs) {
    $stmtDocs->bind_param('i', $studentId);
    $stmtDocs->execute();
    $res = $stmtDocs->get_result();
    while ($row = $res->fetch_assoc()) {
        $docs[] = $row;
    }
    $stmtDocs->close();
}
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Student Document Manager</h1>
        <div>
            <a href="admission_card.php?id=<?php echo (int)$studentId; ?>&preview=1" class="btn btn-info btn-sm">Back to Card</a>
            <a href="all_students.php" class="btn btn-secondary btn-sm">All Students</a>
        </div>
    </div>

    <div class="alert alert-light border">
        <strong><?php echo htmlspecialchars(admission_v($student, 'student_name', 'full_name'), ENT_QUOTES, 'UTF-8'); ?></strong>
        (<?php echo htmlspecialchars(admission_v($student, 'StudentId', 'admission_no'), ENT_QUOTES, 'UTF-8'); ?>)
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Upload Document (Versioned)</h6></div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="form-row">
                <div class="form-group col-md-3">
                    <label>Document Type *</label>
                    <input type="text" class="form-control" name="doc_type" placeholder="CNIC / B-Form / Report Card" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Document Title *</label>
                    <input type="text" class="form-control" name="doc_title" placeholder="B-Form Front" required>
                </div>
                <div class="form-group col-md-5">
                    <label>File *</label>
                    <input type="file" class="form-control-file" name="doc_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    <small class="text-muted">Allowed: PDF, JPG, JPEG, PNG, DOC, DOCX. Max 10MB.</small>
                </div>
                <div class="form-group col-md-12">
                    <button type="submit" class="btn btn-primary" name="upload_document">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Documents</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm">
                <thead><tr><th>Type</th><th>Title</th><th>Version</th><th>Status</th><th>File</th><th>MIME</th><th>Size</th><th>Uploaded By</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (!empty($docs)): foreach ($docs as $d): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$d['doc_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$d['doc_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$d['version_no']; ?></td>
                        <td><?php echo ((int)$d['is_active'] === 1) ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Old</span>'; ?></td>
                        <td><a href="../<?php echo htmlspecialchars((string)$d['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo htmlspecialchars((string)$d['file_name'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                        <td><?php echo htmlspecialchars((string)$d['mime_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format(((int)$d['file_size']) / 1024, 1); ?> KB</td>
                        <td><?php echo htmlspecialchars((string)$d['uploaded_by_name'] . ' (' . (string)$d['uploaded_by_role'] . ')', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$d['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((int)$d['is_active'] !== 1): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="doc_id" value="<?php echo (int)$d['id']; ?>">
                                    <button class="btn btn-sm btn-outline-primary" type="submit" name="set_active_doc">Set Active</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" class="text-center">No documents uploaded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>

