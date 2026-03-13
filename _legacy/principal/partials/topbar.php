<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function principal_active(string $page, string $currentPage): string
{
    return $page === $currentPage ? 'active' : '';
}
$principalAvatar = trim((string)($_SESSION['auth_avatar'] ?? ''));
if ($principalAvatar === '') {
    $principalAvatar = 'https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg';
}
require_once __DIR__ . '/../../scripts/discipline_lib.php';

$principalUnreadBehaviourCount = 0;
$principalUnreadLeaveCount = 0;
$principalUnreadApprovalsCount = 0;
$principalUnreadInboxCount = 0;
if (isset($conn) && $conn instanceof mysqli) {
    discipline_ensure_tables($conn);
    $conn->query("CREATE TABLE IF NOT EXISTS principal_discipline_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        principal_user_id INT NOT NULL,
        discipline_record_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_principal_discipline_read (principal_user_id, discipline_record_id)
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS principal_leave_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        principal_user_id INT NOT NULL,
        leave_request_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_principal_leave_read (principal_user_id, leave_request_id)
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS principal_approval_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        principal_user_id INT NOT NULL,
        approval_type VARCHAR(40) NOT NULL,
        reference_key VARCHAR(190) NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_principal_approval_read (principal_user_id, approval_type, reference_key)
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS inbox_messages (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        sender_role VARCHAR(20) NOT NULL,
        sender_id INT NOT NULL,
        recipient_role VARCHAR(20) NOT NULL,
        recipient_id INT NOT NULL,
        subject VARCHAR(190) NOT NULL,
        message_body TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        read_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_inbox_recipient (recipient_role, recipient_id, is_read, id),
        INDEX idx_inbox_sender (sender_role, sender_id, id)
    )");
    $principalUserId = (int)($_SESSION['auth_user_id'] ?? 0);
    if ($principalUserId > 0) {
        $stmtUnread = $conn->prepare("
            SELECT COUNT(*) AS c
            FROM discipline_records b
            LEFT JOIN principal_discipline_reads pbr
                ON pbr.discipline_record_id = b.id
                AND pbr.principal_user_id = ?
            WHERE b.report_to_principal = 1
              AND b.status <> 'resolved'
              AND pbr.id IS NULL
        ");
        if ($stmtUnread) {
            $stmtUnread->bind_param("i", $principalUserId);
            $stmtUnread->execute();
            $principalUnreadBehaviourCount = (int)($stmtUnread->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtUnread->close();
        }

        $stmtUnreadLeave = $conn->prepare("
            SELECT COUNT(*) AS c
            FROM leave_requests lr
            LEFT JOIN principal_leave_reads plr
                ON plr.leave_request_id = lr.id
                AND plr.principal_user_id = ?
            WHERE lr.status = 'Pending'
              AND plr.id IS NULL
        ");
        if ($stmtUnreadLeave) {
            $stmtUnreadLeave->bind_param("i", $principalUserId);
            $stmtUnreadLeave->execute();
            $principalUnreadLeaveCount = (int)($stmtUnreadLeave->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtUnreadLeave->close();
        }

        $stmtUnreadResultApprovals = $conn->prepare("
            SELECT COUNT(*) AS c
            FROM (
                SELECT CONCAT(r.teacher_id, ':', r.class_id, ':', r.subject_id, ':', r.result_date, ':', r.result_type) AS ref_key
                FROM results r
                WHERE r.approval_status = 'Pending'
                GROUP BY r.teacher_id, r.class_id, r.subject_id, r.result_date, r.result_type
            ) x
            LEFT JOIN principal_approval_reads par
                ON par.principal_user_id = ?
                AND par.approval_type = 'result_group'
                AND par.reference_key = x.ref_key
            WHERE par.id IS NULL
        ");
        $resultUnread = 0;
        if ($stmtUnreadResultApprovals) {
            $stmtUnreadResultApprovals->bind_param("i", $principalUserId);
            $stmtUnreadResultApprovals->execute();
            $resultUnread = (int)($stmtUnreadResultApprovals->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtUnreadResultApprovals->close();
        }
        $stmtUnreadSickApprovals = $conn->prepare("
            SELECT COUNT(*) AS c
            FROM sick_records sr
            LEFT JOIN principal_approval_reads par
                ON par.principal_user_id = ?
                AND par.approval_type = 'sick_referral'
                AND par.reference_key = CAST(sr.id AS CHAR)
            WHERE sr.status = 'Pending'
              AND par.id IS NULL
        ");
        $sickUnread = 0;
        if ($stmtUnreadSickApprovals) {
            $stmtUnreadSickApprovals->bind_param("i", $principalUserId);
            $stmtUnreadSickApprovals->execute();
            $sickUnread = (int)($stmtUnreadSickApprovals->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtUnreadSickApprovals->close();
        }
        $principalUnreadApprovalsCount = $resultUnread + $sickUnread;

        $stmtUnreadInbox = $conn->prepare("
            SELECT COUNT(*) AS c
            FROM inbox_messages
            WHERE recipient_role = 'principal'
              AND recipient_id = ?
              AND is_read = 0
        ");
        if ($stmtUnreadInbox) {
            $stmtUnreadInbox->bind_param("i", $principalUserId);
            $stmtUnreadInbox->execute();
            $principalUnreadInboxCount = (int)($stmtUnreadInbox->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtUnreadInbox->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <link rel="icon" type="image/svg+xml" href="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" />
    <title>KORT - Principal Panel</title>
    <link href="../admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="../admin/css/sb-admin-2.css" rel="stylesheet">
    <style>
        .topbar .navbar-search.global-search {
            width: 100%;
            max-width: 640px;
        }
        .topbar .navbar-search.global-search .input-group {
            border: 1px solid #d8dee9;
            border-radius: 999px;
            background: #f8f9fc;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }
        .topbar .navbar-search.global-search .form-control {
            border: 0;
            background: transparent !important;
            font-size: 0.9rem;
            height: 40px;
            padding-left: 0.95rem;
        }
        .topbar .navbar-search.global-search .form-control:focus {
            box-shadow: none;
        }
        .topbar .navbar-search.global-search .btn {
            border: 0;
            border-left: 1px solid #d8dee9;
            background: #2e59d9;
            min-width: 44px;
            color: #fff;
        }
        .topbar .navbar-search.global-search .btn:hover {
            background: #2653d4;
            color: #fff;
        }
        @media (max-width: 991.98px) {
            .topbar .navbar-search.global-search {
                max-width: 460px;
            }
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <ul class="navbar-nav babar sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
            <div class="sidebar-brand-icon" style="transform: rotate(0deg);">
                <img src="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" alt="KORT Logo" style="width: 63px; height: auto;">
            </div>
            <div class="sidebar-brand-text mx-3">Principal</div>
        </a>
        <hr class="sidebar-divider my-0">
        <li class="nav-item <?php echo principal_active('dashboard.php', $currentPage); ?>">
            <a class="nav-link" href="dashboard.php"><i class="fas fa-fw fa-home"></i><span>Dashboard</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('approvals.php', $currentPage); ?>">
            <a class="nav-link d-flex align-items-center justify-content-between" href="approvals.php">
                <span><i class="fas fa-check-circle"></i><span class="ml-1">Approvals</span></span>
                <?php if ($principalUnreadApprovalsCount > 0): ?>
                    <span class="badge badge-danger"><?php echo (int)$principalUnreadApprovalsCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item <?php echo principal_active('leave_approvals.php', $currentPage); ?>">
            <a class="nav-link d-flex align-items-center justify-content-between" href="leave_approvals.php">
                <span><i class="fas fa-calendar-check"></i><span class="ml-1">Leave Approvals</span></span>
                <?php if ($principalUnreadLeaveCount > 0): ?>
                    <span class="badge badge-danger"><?php echo (int)$principalUnreadLeaveCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item <?php echo principal_active('sick_referral.php', $currentPage); ?>">
            <a class="nav-link" href="sick_referral.php"><i class="fas fa-notes-medical"></i><span>Sick Referral</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('notices.php', $currentPage); ?>">
            <a class="nav-link" href="notices.php"><i class="fas fa-bullhorn"></i><span>Notices</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('messages.php', $currentPage); ?>">
            <a class="nav-link d-flex align-items-center justify-content-between" href="messages.php">
                <span><i class="fas fa-inbox"></i><span class="ml-1">Inbox</span></span>
                <?php if ($principalUnreadInboxCount > 0): ?>
                    <span class="badge badge-danger"><?php echo (int)$principalUnreadInboxCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item <?php echo principal_active('class_year.php', $currentPage); ?>">
            <a class="nav-link" href="class_year.php"><i class="fas fa-school"></i><span>Class-Year Report</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('subject_groups.php', $currentPage); ?>">
            <a class="nav-link" href="subject_groups.php"><i class="fas fa-layer-group"></i><span>Subject Groups</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('print_top_nosh_class.php', $currentPage); ?>">
            <a class="nav-link" href="print_top_nosh_class.php"><i class="fas fa-print"></i><span>Top Nosh Print</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('all_students.php', $currentPage); ?>">
            <a class="nav-link" href="all_students.php"><i class="fas fa-user-graduate"></i><span>All Students</span></a>
        </li>
        <li class="nav-item <?php echo in_array($currentPage, ['assign.php', 'teacher_detail.php', 'teacher_profile.php', 'create_teacher.php'], true) ? 'active' : ''; ?>">
            <a class="nav-link" href="assign.php"><i class="fas fa-user-plus"></i><span>Teacher Management</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('kpi.php', $currentPage); ?>">
            <a class="nav-link" href="kpi.php"><i class="fas fa-chart-line"></i><span>KPI Dashboard</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('results_center.php', $currentPage); ?>">
            <a class="nav-link" href="results_center.php"><i class="fas fa-poll"></i><span>Results Center</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('lesson_plan_reviews.php', $currentPage); ?>">
            <a class="nav-link" href="lesson_plan_reviews.php"><i class="fas fa-book-reader"></i><span>Lesson Plan Reviews</span></a>
        </li>
        <li class="nav-item <?php echo in_array($currentPage, ['document_center.php', 'certificate.php', 'admission_card.php', 'class_wise_cards.php'], true) ? 'active' : ''; ?>">
            <a class="nav-link" href="document_center.php"><i class="fas fa-id-card"></i><span>Document Printing</span></a>
        </li>
        <li class="nav-item <?php echo principal_active('audit_logs.php', $currentPage); ?>">
            <a class="nav-link" href="audit_logs.php"><i class="fas fa-clipboard-list"></i><span>Audit Logs</span></a>
        </li>
        <li class="nav-item <?php echo in_array($currentPage, ['behaviour.php', 'discipline.php'], true) ? 'active' : ''; ?>">
            <a class="nav-link d-flex align-items-center justify-content-between" href="discipline.php">
                <span><i class="fas fa-exclamation-triangle"></i><span class="ml-1">Discipline</span></span>
                <?php if ($principalUnreadBehaviourCount > 0): ?>
                    <span class="badge badge-danger"><?php echo (int)$principalUnreadBehaviourCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item <?php echo principal_active('profile.php', $currentPage); ?>">
            <a class="nav-link" href="./profile.php"><i class="fas fa-user-circle"></i><span>Profile</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="./index.php?logout=1"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </li>
    </ul>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>
                <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search global-search" action="search.php" method="get">
                    <div class="input-group">
                        <input type="text" class="form-control border-0 small" placeholder="Search students, teachers, classes..." aria-label="Global search" name="q" value="<?php echo htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="input-group-append">
                            <button class="btn" type="submit">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="./profile.php" title="Profile">
                            <img src="<?php echo htmlspecialchars($principalAvatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Principal Avatar" style="width:34px;height:34px;object-fit:cover;border-radius:50%;border:1px solid #d1d3e2;">
                            <span class="text-gray-700 ml-2"><?php echo htmlspecialchars((string)($_SESSION['auth_name'] ?? 'Principal'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>
                </ul>
            </nav>

