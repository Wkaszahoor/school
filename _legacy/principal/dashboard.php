<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('admin_dashboard', 'view', 'index.php');
include '../db.php';

$selectedDate = trim((string)($_GET['date'] ?? date('Y-m-d')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

function dashboard_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row['c'] ?? 0)) > 0;
}

function dashboard_column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row['c'] ?? 0)) > 0;
}

$studentTotal = 0;
$studentPresent = 0;
$studentAbsent = 0;
$studentLeave = 0;
$teacherTotal = 0;
$teacherPresent = 0;
$teacherLeave = 0;
$teacherAbsent = 0;

if (dashboard_table_exists($conn, 'students')) {
    $res = $conn->query("SELECT COUNT(*) AS c FROM students");
    $studentTotal = (int)($res ? ($res->fetch_assoc()['c'] ?? 0) : 0);
}

if (dashboard_table_exists($conn, 'attendance')) {
    $stmt = $conn->prepare("
        SELECT
            SUM(CASE WHEN status = 'P' THEN 1 ELSE 0 END) AS present_count,
            SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) AS absent_count,
            SUM(CASE WHEN status = 'L' THEN 1 ELSE 0 END) AS leave_count
        FROM attendance
        WHERE attendance_date = ?
    ");
    if ($stmt) {
        $stmt->bind_param("s", $selectedDate);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $studentPresent = (int)($row['present_count'] ?? 0);
        $studentAbsent = (int)($row['absent_count'] ?? 0);
        $studentLeave = (int)($row['leave_count'] ?? 0);
        $stmt->close();
    }
}

if (dashboard_table_exists($conn, 'teachers')) {
    $res = $conn->query("SELECT COUNT(*) AS c FROM teachers");
    $teacherTotal = (int)($res ? ($res->fetch_assoc()['c'] ?? 0) : 0);
}

if (dashboard_table_exists($conn, 'attendance') && dashboard_column_exists($conn, 'attendance', 'marked_by')) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT marked_by) AS c
        FROM attendance
        WHERE attendance_date = ?
          AND marked_by IS NOT NULL
          AND marked_by > 0
    ");
    if ($stmt) {
        $stmt->bind_param("s", $selectedDate);
        $stmt->execute();
        $teacherPresent = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();
    }
}

if (dashboard_table_exists($conn, 'leave_requests')) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM leave_requests
        WHERE request_type = 'teacher'
          AND status = 'Approved'
          AND ? BETWEEN from_date AND to_date
    ");
    if ($stmt) {
        $stmt->bind_param("s", $selectedDate);
        $stmt->execute();
        $teacherLeave = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();
    }
}

$teacherAbsent = max(0, $teacherTotal - $teacherPresent - $teacherLeave);
$studentAttendanceMarked = $studentPresent + $studentAbsent + $studentLeave;
$studentPresentPct = $studentAttendanceMarked > 0 ? round(($studentPresent / $studentAttendanceMarked) * 100, 1) : 0;
$studentAbsentPct = $studentAttendanceMarked > 0 ? round(($studentAbsent / $studentAttendanceMarked) * 100, 1) : 0;
$studentLeavePct = $studentAttendanceMarked > 0 ? round(($studentLeave / $studentAttendanceMarked) * 100, 1) : 0;

include './partials/topbar.php';
?>
<style>
.principal-hero {
    border: 0;
    border-radius: 14px;
    background: linear-gradient(120deg, #1f3d7a 0%, #2a5298 55%, #3f7ac9 100%);
    color: #fff;
}
.principal-hero .muted {
    color: rgba(255, 255, 255, 0.85);
}
.dashboard-panel {
    border: 0;
    border-radius: 12px;
}
.filter-card {
    border: 1px solid #e8eef7;
    border-radius: 12px;
}
.summary-card {
    border: 0;
    border-radius: 14px;
}
.summary-card .card-title {
    font-size: 1rem;
    font-weight: 700;
}
.metric-chip {
    border-radius: 12px;
    padding: 10px 12px;
    background: #f8f9fc;
    border: 1px solid #eaecf4;
    height: 100%;
}
.metric-chip .label {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .02em;
    color: #6c757d;
    margin-bottom: 4px;
}
.metric-chip .value {
    font-size: 1.15rem;
    font-weight: 700;
    color: #2e2e2e;
}
.metric-chip.success { border-color: #d4f4e6; background: #f1fcf6; }
.metric-chip.success .value { color: #17a673; }
.metric-chip.danger { border-color: #f7d6d9; background: #fff5f6; }
.metric-chip.danger .value { color: #d63346; }
.metric-chip.warning { border-color: #fbe8c3; background: #fffbf1; }
.metric-chip.warning .value { color: #dd9b11; }
.chart-card .card-header {
    background: #fff;
    border-bottom: 1px solid #eef1f8;
}
.chart-wrap {
    position: relative;
    width: 100%;
    min-height: 240px;
    height: 260px;
}
.chart-stat-list {
    border-left: 1px dashed #e3e6f0;
    padding-left: 14px;
}
.chart-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.chart-stat .name {
    font-size: .85rem;
    color: #6c757d;
}
.chart-stat .num {
    font-weight: 700;
    color: #2d3748;
}
.text-present { color: #17a673 !important; }
.text-absent { color: #d63346 !important; }
.text-leave { color: #dd9b11 !important; }
.feature-card {
    border: 0;
    border-radius: 12px;
    transition: transform .15s ease, box-shadow .15s ease;
}
.feature-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1.25rem rgba(58, 59, 69, .12);
}
</style>
<div class="container-fluid">
    <div class="card principal-hero shadow mb-4">
        <div class="card-body py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1 text-white">Principal Dashboard</h1>
                    <div class="muted">Welcome, <?php echo htmlspecialchars((string)($_SESSION['auth_name'] ?? 'Principal'), ENT_QUOTES, 'UTF-8'); ?>.</div>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge badge-light px-3 py-2">Date: <?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card filter-card shadow-sm mb-4">
        <div class="card-body pb-2">
            <div class="row">
                <div class="col-lg-6">
                    <form method="get" class="form-row align-items-end">
                        <div class="form-group col-md-5 mb-2">
                            <label class="mb-1">Attendance Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group col-md-7 mb-2 d-flex align-items-center">
                            <button class="btn btn-primary mr-2" type="submit">Apply Date</button>
                            <a class="btn btn-outline-secondary" href="dashboard.php">Today</a>
                        </div>
                    </form>
                </div>
                <div class="col-lg-6">
                    <form method="get" action="search.php" class="form-row align-items-end">
                        <div class="form-group col-md-9 mb-2">
                            <label class="mb-1">Global Search</label>
                            <input type="text" name="q" class="form-control" placeholder="Student, teacher, class..." value="<?php echo htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group col-md-3 mb-2">
                            <button class="btn btn-info btn-block" type="submit">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6 mb-3">
            <div class="card summary-card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="card-title mb-3">Students</div>
                    <div class="row">
                        <div class="col-6 col-lg-3 mb-2"><div class="metric-chip"><div class="label">Total</div><div class="value"><?php echo (int)$studentTotal; ?></div></div></div>
                        <div class="col-6 col-lg-3 mb-2"><div class="metric-chip success"><div class="label">Present</div><div class="value"><?php echo (int)$studentPresent; ?></div></div></div>
                        <div class="col-6 col-lg-3 mb-2"><div class="metric-chip danger"><div class="label">Absent</div><div class="value"><?php echo (int)$studentAbsent; ?></div></div></div>
                        <div class="col-6 col-lg-3 mb-2"><div class="metric-chip warning"><div class="label">Leave</div><div class="value"><?php echo (int)$studentLeave; ?></div></div></div>
                    </div>
                    <small class="text-muted">Attendance marked records: <?php echo (int)$studentAttendanceMarked; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card summary-card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="card-title mb-3">Teachers</div>
                    <div class="row">
                        <div class="col-6 col-lg-3 mb-2"><div class="metric-chip"><div class="label">Total</div><div class="value"><?php echo (int)$teacherTotal; ?></div></div></div>
                        <div class="col-6 col-lg-3 mb-2"><div class="metric-chip success"><div class="label">Present</div><div class="value"><?php echo (int)$teacherPresent; ?></div></div></div>
                        <div class="col-6 col-lg-3 mb-2"><div class="metric-chip danger"><div class="label">Absent</div><div class="value"><?php echo (int)$teacherAbsent; ?></div></div></div>
                        <div class="col-6 col-lg-3 mb-2"><div class="metric-chip warning"><div class="label">Leave</div><div class="value"><?php echo (int)$teacherLeave; ?></div></div></div>
                    </div>
                    <small class="text-muted">Teacher present is based on attendance marked by teacher on selected date.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card chart-card dashboard-panel shadow h-100">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Student Attendance Percentage</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="chart-wrap">
                                <canvas id="studentAttendanceChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4 chart-stat-list mt-3 mt-md-0">
                            <div class="chart-stat"><span class="name">Present</span><span class="num text-present"><?php echo $studentPresentPct; ?>%</span></div>
                            <div class="chart-stat"><span class="name">Absent</span><span class="num text-absent"><?php echo $studentAbsentPct; ?>%</span></div>
                            <div class="chart-stat"><span class="name">Leave</span><span class="num text-leave"><?php echo $studentLeavePct; ?>%</span></div>
                            <div class="chart-stat"><span class="name">Marked</span><span class="num"><?php echo (int)$studentAttendanceMarked; ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card chart-card dashboard-panel shadow h-100">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success">Teacher Attendance / Leave</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="chart-wrap">
                                <canvas id="teacherAttendanceChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4 chart-stat-list mt-3 mt-md-0">
                            <div class="chart-stat"><span class="name">Present</span><span class="num text-present"><?php echo (int)$teacherPresent; ?></span></div>
                            <div class="chart-stat"><span class="name">Absent</span><span class="num text-absent"><?php echo (int)$teacherAbsent; ?></span></div>
                            <div class="chart-stat"><span class="name">Leave</span><span class="num text-leave"><?php echo (int)$teacherLeave; ?></span></div>
                            <div class="chart-stat"><span class="name">Total</span><span class="num"><?php echo (int)$teacherTotal; ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card feature-card shadow h-100">
                <div class="card-body">
                    <h5>Approvals</h5>
                    <p class="mb-3">Approve marks, leave and sick referrals.</p>
                    <a class="btn btn-primary btn-sm" href="approvals.php">Open Approvals</a>
                    <a class="btn btn-outline-primary btn-sm" href="leave_approvals.php">Leave Approvals</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card feature-card shadow h-100">
                <div class="card-body">
                    <h5>Operations</h5>
                    <p class="mb-3">Assign teachers and refer sick students to doctor.</p>
                    <a class="btn btn-primary btn-sm" href="assign.php">Assign Teachers</a>
                    <a class="btn btn-outline-primary btn-sm" href="sick_referral.php">Sick Referral</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card feature-card shadow h-100">
                <div class="card-body">
                    <h5>Reports</h5>
                    <p class="mb-3">Use principal reports without leaving this panel.</p>
                    <a class="btn btn-outline-secondary btn-sm" href="class_year.php">Class-Year</a>
                    <a class="btn btn-outline-secondary btn-sm" href="teachers_by_class.php">Teachers By Class</a>
                    <a class="btn btn-outline-secondary btn-sm" href="kpi.php">KPI Dashboard</a>
                    <a class="btn btn-outline-secondary btn-sm" href="audit_logs.php">Audit Logs</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const studentCtx = document.getElementById('studentAttendanceChart');
    const teacherCtx = document.getElementById('teacherAttendanceChart');
    if (studentCtx) {
        new Chart(studentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Leave'],
                datasets: [{
                    data: [<?php echo (int)$studentPresent; ?>, <?php echo (int)$studentAbsent; ?>, <?php echo (int)$studentLeave; ?>],
                    backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
    if (teacherCtx) {
        new Chart(teacherCtx, {
            type: 'bar',
            data: {
                labels: ['Present', 'Absent', 'Leave'],
                datasets: [{
                    label: 'Teachers',
                    data: [<?php echo (int)$teacherPresent; ?>, <?php echo (int)$teacherAbsent; ?>, <?php echo (int)$teacherLeave; ?>],
                    backgroundColor: ['#36b9cc', '#e74a3b', '#f6c23e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
})();
</script>
<?php $noticesPopupApiPath = '../scripts/notices_api.php'; include __DIR__ . '/../scripts/notices_popup_snippet.php'; ?>
<?php include './partials/footer.php'; ?>
