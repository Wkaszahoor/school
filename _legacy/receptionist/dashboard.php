<?php
require_once __DIR__ . '/../auth.php';
auth_require_roles(['receptionist'], 'index.php');
require_once __DIR__ . '/../db.php';

$totalStudents = 0;
$todayAdmissions = 0;
$totalClasses = 0;

$res = $conn->query("SELECT COUNT(*) AS c FROM students");
if ($res) {
    $totalStudents = (int)($res->fetch_assoc()['c'] ?? 0);
}
$res = $conn->query("SELECT COUNT(*) AS c FROM students WHERE DATE(created_at) = CURDATE()");
if ($res) {
    $todayAdmissions = (int)($res->fetch_assoc()['c'] ?? 0);
}
$res = $conn->query("SELECT COUNT(*) AS c FROM classes");
if ($res) {
    $totalClasses = (int)($res->fetch_assoc()['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receptionist Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">Receptionist Dashboard</span>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small"><?php echo htmlspecialchars((string)($_SESSION['auth_name'] ?? 'Receptionist'), ENT_QUOTES, 'UTF-8'); ?></span>
            <a class="btn btn-outline-danger btn-sm" href="index.php?logout=1">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Students</div>
                    <div class="h4 mb-0"><?php echo $totalStudents; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Admissions Today</div>
                    <div class="h4 mb-0"><?php echo $todayAdmissions; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Classes</div>
                    <div class="h4 mb-0"><?php echo $totalClasses; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Quick Actions</div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php if (auth_can('students', 'create')): ?>
                    <a class="btn btn-primary" href="../principal/add_student.php">Add Student</a>
                <?php endif; ?>
                <?php if (auth_can('students', 'view')): ?>
                    <a class="btn btn-outline-primary" href="../principal/all_students.php">All Students</a>
                <?php endif; ?>
                <?php if (auth_can('students', 'edit')): ?>
                    <a class="btn btn-outline-secondary" href="../principal/admission_card.php">Admission Card</a>
                <?php endif; ?>
                <?php if (auth_can('students', 'export')): ?>
                    <a class="btn btn-outline-dark" href="../principal/class_wise_cards.php">Class-wise Cards</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $noticesPopupApiPath = '../scripts/notices_api.php'; include __DIR__ . '/../scripts/notices_popup_snippet.php'; ?>
</body>
</html>
