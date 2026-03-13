<?php
require_once __DIR__ . '/../auth.php';
auth_require_roles(['principal_helper'], 'index.php');
require_once __DIR__ . '/../db.php';

$pendingApprovals = 0;
$pendingLeaves = 0;
$pendingSick = 0;

$res = $conn->query("SELECT COUNT(*) AS c FROM results WHERE approval_status = 'Pending'");
if ($res) {
    $pendingApprovals = (int)($res->fetch_assoc()['c'] ?? 0);
}
$res = $conn->query("SELECT COUNT(*) AS c FROM leave_requests WHERE status = 'Pending'");
if ($res) {
    $pendingLeaves = (int)($res->fetch_assoc()['c'] ?? 0);
}
$res = $conn->query("SELECT COUNT(*) AS c FROM sick_records WHERE status = 'Pending'");
if ($res) {
    $pendingSick = (int)($res->fetch_assoc()['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Principal Helper Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">Principal Helper Dashboard</span>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small"><?php echo htmlspecialchars((string)($_SESSION['auth_name'] ?? 'Principal Helper'), ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="btn btn-outline-danger btn-sm" href="index.php?logout=1">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pending Result Rows</div>
                    <div class="h4 mb-0"><?php echo $pendingApprovals; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pending Leaves</div>
                    <div class="h4 mb-0"><?php echo $pendingLeaves; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pending Sick Referrals</div>
                    <div class="h4 mb-0"><?php echo $pendingSick; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Helper Workbench</div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php if (auth_can('students', 'view')): ?>
                    <a class="btn btn-outline-primary" href="../principal/all_students.php">Student Registry</a>
                <?php endif; ?>
                <?php if (auth_can('subjects', 'view')): ?>
                    <a class="btn btn-outline-primary" href="../admin/class_subjects.php">Class Subjects</a>
                <?php endif; ?>
                <?php if (auth_can('results_reports', 'view')): ?>
                    <a class="btn btn-outline-dark" href="../principal/results_center.php">Results Center</a>
                <?php endif; ?>
                <a class="btn btn-primary" href="../principal/approvals.php">Approvals Queue</a>
            </div>
        </div>
    </div>
</div>
<?php $noticesPopupApiPath = '../scripts/notices_api.php'; include __DIR__ . '/../scripts/notices_popup_snippet.php'; ?>
</body>
</html>
