<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('medical_records', 'create', 'index.php');
include '../db.php';
include './partials/topbar.php';

$flashType = '';
$flashMessage = '';
$classFilter = trim((string)($_GET['class'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 20);
$allowedPerPage = [20, 50, 100];
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 20;
}

$conn->query("
    CREATE TABLE IF NOT EXISTS sick_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        sick_date DATE NOT NULL,
        days_off INT NOT NULL DEFAULT 1,
        reason VARCHAR(255) NOT NULL,
        doctor_note TEXT NULL,
        referred_by VARCHAR(120) NOT NULL DEFAULT 'Principal',
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        approved_by VARCHAR(120) NULL,
        approved_at DATETIME NULL,
        doctor_prescription TEXT NULL,
        doctor_suggestion TEXT NULL,
        examined_by VARCHAR(120) NULL,
        examined_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refer_sick'])) {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $sickDate = trim((string)($_POST['sick_date'] ?? ''));
    $daysOff = max(1, (int)($_POST['days_off'] ?? 1));
    $reason = trim((string)($_POST['reason'] ?? ''));
    $doctorNote = trim((string)($_POST['doctor_note'] ?? ''));
    $referredBy = (string)($_SESSION['auth_name'] ?? 'Principal');

    if ($studentId <= 0 || $sickDate === '' || $reason === '') {
        $flashType = 'danger';
        $flashMessage = 'Student, date and reason are required.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO sick_records (student_id, sick_date, days_off, reason, doctor_note, referred_by, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        if ($stmt) {
            $stmt->bind_param('isisss', $studentId, $sickDate, $daysOff, $reason, $doctorNote, $referredBy);
            $stmt->execute();
            $newId = (int)$stmt->insert_id;
            $stmt->close();
            auth_audit_log($conn, 'create', 'sick_referral', (string)$newId, null, json_encode(['student_id' => $studentId]));
            $flashType = 'success';
            $flashMessage = 'Student referred. Approve from Principal -> Approvals.';
        }
    }
}

$classes = [];
$c = $conn->query("SELECT DISTINCT class FROM students ORDER BY class ASC");
if ($c) {
    while ($row = $c->fetch_assoc()) {
        $classes[] = (string)($row['class'] ?? '');
    }
}

$students = [];
$studentSql = "SELECT id, StudentId, student_name, class FROM students";
$studentParams = [];
$studentTypes = '';
if ($classFilter !== '') {
    $studentSql .= " WHERE class = ?";
    $studentParams[] = $classFilter;
    $studentTypes .= 's';
}
$studentSql .= " ORDER BY class ASC, student_name ASC";
$studentStmt = $conn->prepare($studentSql);
if ($studentStmt) {
    if (!empty($studentParams)) {
        $studentStmt->bind_param($studentTypes, ...$studentParams);
    }
    $studentStmt->execute();
    $studentRes = $studentStmt->get_result();
    while ($row = $studentRes->fetch_assoc()) {
        $students[] = $row;
    }
    $studentStmt->close();
}

$where = [];
$params = [];
$types = '';
if ($classFilter !== '') {
    $where[] = "s.class = ?";
    $params[] = $classFilter;
    $types .= 's';
}
if ($statusFilter !== '' && in_array($statusFilter, ['Pending', 'Approved', 'Rejected'], true)) {
    $where[] = "sr.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}
$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "
    SELECT COUNT(*) AS total
    FROM sick_records sr
    JOIN students s ON s.id = sr.student_id
    $whereSql
";
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

$records = [];
$listSql = "
    SELECT sr.id, sr.sick_date, sr.days_off, sr.reason, sr.status, sr.referred_by, sr.approved_by, sr.approved_at,
           s.StudentId, s.student_name, s.class
    FROM sick_records sr
    JOIN students s ON s.id = sr.student_id
    $whereSql
    ORDER BY sr.id DESC
    LIMIT ? OFFSET ?
";
$listStmt = $conn->prepare($listSql);
if ($listStmt) {
    $runParams = $params;
    $runParams[] = $perPage;
    $runParams[] = $offset;
    $runTypes = $types . 'ii';
    $listStmt->bind_param($runTypes, ...$runParams);
    $listStmt->execute();
    $r = $listStmt->get_result();
    while ($row = $r->fetch_assoc()) {
        $records[] = $row;
    }
    $listStmt->close();
}

$baseState = [
    'class' => $classFilter,
    'status' => $statusFilter,
    'per_page' => $perPage,
];
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Sick Referral</h1>
    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Refer Student to Doctor</h6></div>
        <div class="card-body">
            <form method="get" class="form-row mb-3">
                <div class="form-group col-md-4">
                    <label>Filter by Class</label>
                    <select name="class" class="form-control">
                        <option value="">All classes</option>
                        <?php foreach ($classes as $cl): ?>
                            <option value="<?php echo htmlspecialchars($cl, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $classFilter === $cl ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cl, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Records Status</label>
                    <select name="status" class="form-control">
                        <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>All</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Per Page</label>
                    <select name="per_page" class="form-control">
                        <?php foreach ($allowedPerPage as $n): ?>
                            <option value="<?php echo (int)$n; ?>" <?php echo $perPage === (int)$n ? 'selected' : ''; ?>><?php echo (int)$n; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-info mr-2">Apply Filters</button>
                    <a href="sick_referral.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>

            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Student</label>
                        <input type="text" id="student_search_box" class="form-control mb-2" placeholder="Search by Student ID or Name">
                        <small id="student_search_hint" class="form-text text-muted mb-2">Type first letter of name or Student ID.</small>
                        <select name="student_id" class="form-control" required>
                            <option value="">Select student</option>
                            <?php foreach ($students as $st): ?>
                                <option value="<?php echo (int)$st['id']; ?>">
                                    <?php echo htmlspecialchars((string)$st['StudentId'] . ' - ' . (string)$st['student_name'] . ' (' . (string)$st['class'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Date</label>
                        <input type="date" name="sick_date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Days Off</label>
                        <input type="number" name="days_off" min="1" value="1" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-10">
                        <label>Doctor Note (optional)</label>
                        <input type="text" name="doctor_note" class="form-control">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" name="refer_sick" class="btn btn-primary w-100">Refer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Referral Records (<?php echo (int)$totalRecords; ?>)
                - Page <?php echo (int)$page; ?> of <?php echo (int)$totalPages; ?>
            </h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Student</th><th>Class</th><th>Date</th><th>Days</th><th>Reason</th><th>Status</th><th>Approved By</th></tr></thead>
                <tbody>
                <?php if (!empty($records)): foreach ($records as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$row['StudentId'] . ' - ' . (string)$row['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['sick_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$row['days_off']; ?></td>
                        <td><?php echo htmlspecialchars((string)$row['reason'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($row['approved_by'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center">No referrals yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Referral pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $prevPage = max(1, $page - 1);
                        $nextPage = min($totalPages, $page + 1);
                        $prevLink = 'sick_referral.php?' . http_build_query(array_merge($baseState, ['page' => $prevPage]));
                        $nextLink = 'sick_referral.php?' . http_build_query(array_merge($baseState, ['page' => $nextPage]));
                        ?>
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo htmlspecialchars($prevLink, ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
                        </li>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($p = $startPage; $p <= $endPage; $p++):
                            $pageLink = 'sick_referral.php?' . http_build_query(array_merge($baseState, ['page' => $p]));
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
<?php include './partials/footer.php'; ?>
<script>
(function () {
    var searchBox = document.getElementById('student_search_box');
    var hint = document.getElementById('student_search_hint');
    var select = document.querySelector('select[name="student_id"]');
    if (!searchBox || !select) return;

    var allOptions = Array.prototype.slice.call(select.options).slice(1).map(function (opt) {
        return { value: opt.value, text: opt.text };
    });
    var placeholder = select.options[0] ? select.options[0].text : 'Select student';

    function rebuildOptions(list) {
        var current = select.value;
        while (select.options.length > 0) {
            select.remove(0);
        }
        select.add(new Option(placeholder, ''));
        list.forEach(function (item) {
            var option = new Option(item.text, item.value);
            select.add(option);
        });
        if (current) {
            select.value = current;
        }
    }

    searchBox.addEventListener('input', function () {
        var q = (searchBox.value || '').toLowerCase().trim();
        var filtered = allOptions.filter(function (item) {
            return q === '' || item.text.toLowerCase().indexOf(q) !== -1;
        });
        rebuildOptions(filtered);
        if (hint) {
            hint.textContent = filtered.length + ' student(s) found';
        }
    });
})();
</script>
