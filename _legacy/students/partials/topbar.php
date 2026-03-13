<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$teacherProfile = [
    "id" => (int)($_SESSION["teacher_id"] ?? 0),
    "name" => (string)($_SESSION["teacher_name"] ?? "Teacher"),
    "email" => (string)($_SESSION["id"] ?? ""),
    "phone" => "",
    "academic_details" => "",
    "experience_details" => ""
];

$teacherFlash = (string)($_SESSION["teacher_flash"] ?? "");
$teacherFlashType = (string)($_SESSION["teacher_flash_type"] ?? "success");
unset($_SESSION["teacher_flash"], $_SESSION["teacher_flash_type"]);

$teacherLeaveTotal = 0;
$teacherLeavePending = 0;
$teacherLeaveApproved = 0;
$teacherLeaveRejected = 0;
$teacherUnreadInboxCount = 0;

if (isset($conn) && $conn instanceof mysqli) {
    $ensureColumn = function (string $column, string $definition) use ($conn): void {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'teachers' AND column_name = ?");
        if (!$stmt) {
            return;
        }
        $stmt->bind_param("s", $column);
        $stmt->execute();
        $exists = ((int)($stmt->get_result()->fetch_assoc()["c"] ?? 0) > 0);
        $stmt->close();
        if (!$exists) {
            $conn->query("ALTER TABLE `teachers` ADD COLUMN `$column` $definition");
        }
    };

    $ensureColumn("academic_details", "TEXT NULL AFTER `phone`");
    $ensureColumn("experience_details", "TEXT NULL AFTER `academic_details`");
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

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["topbar_action"])) {
        $action = (string)$_POST["topbar_action"];
        $backUrl = (string)($_POST["return_to"] ?? "");
        if ($backUrl === "" || !preg_match('/^[a-zA-Z0-9_\\-\\.\\/\\?=&%#]*$/', $backUrl)) {
            $backUrl = $_SERVER["PHP_SELF"] ?? "index.php";
        }

        $tid = (int)($_SESSION["teacher_id"] ?? 0);
        if ($tid <= 0 && isset($_SESSION["id"])) {
            $email = (string)$_SESSION["id"];
            $q = $conn->prepare("SELECT id FROM teachers WHERE email = ? LIMIT 1");
            if ($q) {
                $q->bind_param("s", $email);
                $q->execute();
                $r = $q->get_result()->fetch_assoc();
                $q->close();
                $tid = (int)($r["id"] ?? 0);
                if ($tid > 0) {
                    $_SESSION["teacher_id"] = $tid;
                }
            }
        }

        if ($tid <= 0) {
            $_SESSION["teacher_flash_type"] = "danger";
            $_SESSION["teacher_flash"] = "Teacher account not found.";
        } elseif ($action === "update_profile") {
            $phone = trim((string)($_POST["phone"] ?? ""));
            $academic = trim((string)($_POST["academic_details"] ?? ""));
            $experience = trim((string)($_POST["experience_details"] ?? ""));

            $u = $conn->prepare("UPDATE teachers SET phone = ?, academic_details = ?, experience_details = ? WHERE id = ?");
            if ($u) {
                $u->bind_param("sssi", $phone, $academic, $experience, $tid);
                $u->execute();
                $u->close();
                $_SESSION["teacher_flash_type"] = "success";
                $_SESSION["teacher_flash"] = "Profile updated successfully.";
            } else {
                $_SESSION["teacher_flash_type"] = "danger";
                $_SESSION["teacher_flash"] = "Profile update failed.";
            }
        } elseif ($action === "change_password") {
            $currentPassword = (string)($_POST["current_password"] ?? "");
            $newPassword = (string)($_POST["new_password"] ?? "");
            $confirmPassword = (string)($_POST["confirm_password"] ?? "");

            if ($newPassword === "" || strlen($newPassword) < 6) {
                $_SESSION["teacher_flash_type"] = "danger";
                $_SESSION["teacher_flash"] = "New password must be at least 6 characters.";
            } elseif (!hash_equals($newPassword, $confirmPassword)) {
                $_SESSION["teacher_flash_type"] = "danger";
                $_SESSION["teacher_flash"] = "New password and confirm password do not match.";
            } else {
                $q = $conn->prepare("SELECT password FROM teachers WHERE id = ? LIMIT 1");
                if ($q) {
                    $q->bind_param("i", $tid);
                    $q->execute();
                    $row = $q->get_result()->fetch_assoc();
                    $q->close();
                    $stored = (string)($row["password"] ?? "");
                    $ok = hash_equals($stored, $currentPassword) || password_verify($currentPassword, $stored);
                    if (!$ok) {
                        $_SESSION["teacher_flash_type"] = "danger";
                        $_SESSION["teacher_flash"] = "Current password is incorrect.";
                    } else {
                        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $u = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
                        if ($u) {
                            $u->bind_param("si", $newHash, $tid);
                            $u->execute();
                            $u->close();
                            $_SESSION["teacher_flash_type"] = "success";
                            $_SESSION["teacher_flash"] = "Password changed successfully.";
                        } else {
                            $_SESSION["teacher_flash_type"] = "danger";
                            $_SESSION["teacher_flash"] = "Password change failed.";
                        }
                    }
                } else {
                    $_SESSION["teacher_flash_type"] = "danger";
                    $_SESSION["teacher_flash"] = "Unable to verify current password.";
                }
            }
        }

        header("Location: " . $backUrl);
        exit();
    }

    $tid = (int)($_SESSION["teacher_id"] ?? 0);
    if ($tid > 0) {
        $stmt = $conn->prepare("SELECT id, name, email, phone, academic_details, experience_details FROM teachers WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $tid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $teacherProfile = [
                    "id" => (int)($row["id"] ?? 0),
                    "name" => (string)($row["name"] ?? $teacherProfile["name"]),
                    "email" => (string)($row["email"] ?? $teacherProfile["email"]),
                    "phone" => (string)($row["phone"] ?? ""),
                    "academic_details" => (string)($row["academic_details"] ?? ""),
                    "experience_details" => (string)($row["experience_details"] ?? "")
                ];
            }
        }

        $leaveStatsStmt = $conn->prepare("
            SELECT
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count
            FROM leave_requests
            WHERE request_type = 'teacher' AND teacher_id = ?
        ");
        if ($leaveStatsStmt) {
            $leaveStatsStmt->bind_param("i", $tid);
            $leaveStatsStmt->execute();
            $leaveStats = $leaveStatsStmt->get_result()->fetch_assoc();
            $leaveStatsStmt->close();
            $teacherLeaveTotal = (int)($leaveStats["total_count"] ?? 0);
            $teacherLeavePending = (int)($leaveStats["pending_count"] ?? 0);
            $teacherLeaveApproved = (int)($leaveStats["approved_count"] ?? 0);
            $teacherLeaveRejected = (int)($leaveStats["rejected_count"] ?? 0);
        }

        $inboxStmt = $conn->prepare("
            SELECT COUNT(*) AS c
            FROM inbox_messages
            WHERE recipient_role = 'teacher'
              AND recipient_id = ?
              AND is_read = 0
        ");
        if ($inboxStmt) {
            $inboxStmt->bind_param("i", $tid);
            $inboxStmt->execute();
            $teacherUnreadInboxCount = (int)($inboxStmt->get_result()->fetch_assoc()["c"] ?? 0);
            $inboxStmt->close();
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
    <meta name="description" content="KORT Teacher Dashboard">
    <meta name="author" content="KORT">
    <title>KORT - Teacher Panel</title>

    <link href="../admin/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="../admin/css/sb-admin-2.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav babar sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
                <div class="sidebar-brand-icon" style="transform: rotate(0deg);">
                    <img src="https://kort.org.uk/wp-content/themes/kort/_ui/media/primary-logo-sticky.svg" alt="KORT Logo" style="width: 63px; height: auto;">
                </div>
                <div class="sidebar-brand-text mx-3">Teacher</div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item active">
                <a class="nav-link" href="../students/index.php">
                    <i class="fas fa-fw fa-home"></i> <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="../upload/index.php" class="nav-link">
                    <i class="nav-icon fas fa-upload"></i> Upload Marks
                </a>
            </li>

            <li class="nav-item">
                <a href="../view/index.php" class="nav-link">
                    <i class="nav-icon fas fa-poll"></i> Test Results
                </a>
            </li>

            <li class="nav-item">
                <a href="../students/index.php#attendance" class="nav-link">
                    <i class="nav-icon fas fa-clipboard-check"></i> Attendance
                </a>
            </li>

            <li class="nav-item">
                <a href="../students/index.php#lesson-plan" class="nav-link">
                    <i class="nav-icon fas fa-book"></i> Lesson Plan
                </a>
            </li>

            <li class="nav-item">
                <a href="../students/index.php#leave-request" class="nav-link d-flex align-items-center justify-content-between">
                    <span><i class="nav-icon fas fa-calendar-times"></i> Leave Request</span>
                    <?php if ($teacherLeavePending > 0): ?>
                        <span class="badge badge-warning"><?php echo (int)$teacherLeavePending; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="../students/messages.php" class="nav-link d-flex align-items-center justify-content-between">
                    <span><i class="nav-icon fas fa-inbox"></i> Inbox</span>
                    <?php if ($teacherUnreadInboxCount > 0): ?>
                        <span class="badge badge-danger"><?php echo (int)$teacherUnreadInboxCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="../students/index.php#assigned-classes" class="nav-link">
                    <i class="nav-icon fas fa-chalkboard-teacher"></i> Assigned Classes
                </a>
            </li>

            <li class="nav-item">
                <a href="../students/behaviour.php" class="nav-link">
                    <i class="nav-icon fas fa-exclamation-triangle"></i> Discipline
                </a>
            </li>

            <li class="nav-item">
                <a href="../index.php?logout" class="nav-link">
                    <i class="nav-icon fas fa-sign-out-alt"></i> Logout
                </a>
            </li>

            <hr class="sidebar-divider">
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars((string)($teacherProfile["name"] ?? 'Teacher'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../admin/img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#teacherProfileModal">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Update Profile
                                </a>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#teacherPasswordModal">
                                    <i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Change Password
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../index.php?logout">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <?php if ($teacherFlash !== ""): ?>
                    <div class="container-fluid">
                        <div class="alert alert-<?php echo htmlspecialchars($teacherFlashType, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($teacherFlash, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="modal fade" id="teacherProfileModal" tabindex="-1" role="dialog" aria-labelledby="teacherProfileModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <form method="POST" action="">
                                <input type="hidden" name="topbar_action" value="update_profile">
                                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars((string)($_SERVER["REQUEST_URI"] ?? "students/index.php"), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="teacherProfileModalLabel">Update Profile</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacherProfile["name"], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Email</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($teacherProfile["email"], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Contact Number</label>
                                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($teacherProfile["phone"], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Academic Details</label>
                                            <textarea name="academic_details" class="form-control" rows="3"><?php echo htmlspecialchars($teacherProfile["academic_details"], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Experience Details</label>
                                        <textarea name="experience_details" class="form-control" rows="3"><?php echo htmlspecialchars($teacherProfile["experience_details"], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="teacherPasswordModal" tabindex="-1" role="dialog" aria-labelledby="teacherPasswordModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST" action="">
                                <input type="hidden" name="topbar_action" value="change_password">
                                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars((string)($_SERVER["REQUEST_URI"] ?? "students/index.php"), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="teacherPasswordModalLabel">Change Password</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label>Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

