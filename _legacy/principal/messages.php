<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('inbox_messages', 'view', 'index.php');
include '../db.php';

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
$flashType = '';
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    auth_require_permission('inbox_messages', 'create', 'index.php');
    $teacherId = (int)($_POST['recipient_teacher_id'] ?? 0);
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message_body'] ?? ''));

    if ($teacherId <= 0 || $subject === '' || $message === '') {
        $flashType = 'danger';
        $flashMessage = 'Please select teacher, subject, and message.';
    } else {
        $ins = $conn->prepare("INSERT INTO inbox_messages (sender_role, sender_id, recipient_role, recipient_id, subject, message_body) VALUES ('principal', ?, 'teacher', ?, ?, ?)");
        if ($ins) {
            $ins->bind_param('iiss', $principalUserId, $teacherId, $subject, $message);
            if ($ins->execute()) {
                $flashType = 'success';
                $flashMessage = 'Message sent to teacher.';
                auth_audit_log($conn, 'create', 'inbox_message', (string)$ins->insert_id, null, json_encode(['to_teacher_id' => $teacherId]));
            } else {
                $flashType = 'danger';
                $flashMessage = 'Unable to send message.';
            }
            $ins->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    auth_require_permission('inbox_messages', 'edit', 'index.php');
    $messageId = (int)($_POST['message_id'] ?? 0);
    if ($messageId > 0) {
        $upd = $conn->prepare("UPDATE inbox_messages SET is_read = 1, read_at = NOW() WHERE id = ? AND recipient_role = 'principal' AND recipient_id = ?");
        if ($upd) {
            $upd->bind_param('ii', $messageId, $principalUserId);
            $upd->execute();
            if ($upd->affected_rows > 0) {
                $flashType = 'success';
                $flashMessage = 'Message marked as read.';
            }
            $upd->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    auth_require_permission('inbox_messages', 'edit', 'index.php');
    $upd = $conn->prepare("UPDATE inbox_messages SET is_read = 1, read_at = NOW() WHERE recipient_role = 'principal' AND recipient_id = ? AND is_read = 0");
    if ($upd) {
        $upd->bind_param('i', $principalUserId);
        $upd->execute();
        $upd->close();
        $flashType = 'success';
        $flashMessage = 'All inbox messages marked as read.';
    }
}

$teachers = [];
$teacherStmt = $conn->prepare("SELECT id, name, email, subject, class_assigned FROM teachers ORDER BY name ASC");
if ($teacherStmt) {
    $teacherStmt->execute();
    $res = $teacherStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $teachers[] = $row;
    }
    $teacherStmt->close();
}

$inboxRows = [];
$inboxStmt = $conn->prepare("SELECT m.id, m.subject, m.message_body, m.created_at, m.is_read, m.read_at, t.name AS teacher_name, t.email AS teacher_email
    FROM inbox_messages m
    LEFT JOIN teachers t ON t.id = m.sender_id AND m.sender_role = 'teacher'
    WHERE m.recipient_role = 'principal' AND m.recipient_id = ?
    ORDER BY m.id DESC
    LIMIT 300");
if ($inboxStmt) {
    $inboxStmt->bind_param('i', $principalUserId);
    $inboxStmt->execute();
    $res = $inboxStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $inboxRows[] = $row;
    }
    $inboxStmt->close();
}

$sentRows = [];
$sentStmt = $conn->prepare("SELECT m.id, m.subject, m.message_body, m.created_at, m.is_read, m.read_at, t.name AS teacher_name, t.email AS teacher_email
    FROM inbox_messages m
    LEFT JOIN teachers t ON t.id = m.recipient_id AND m.recipient_role = 'teacher'
    WHERE m.sender_role = 'principal' AND m.sender_id = ?
    ORDER BY m.id DESC
    LIMIT 300");
if ($sentStmt) {
    $sentStmt->bind_param('i', $principalUserId);
    $sentStmt->execute();
    $res = $sentStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $sentRows[] = $row;
    }
    $sentStmt->close();
}

include './partials/topbar.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Inbox</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Send Message to Teacher</h6>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Teacher</label>
                        <select class="form-control" name="recipient_teacher_id" required>
                            <option value="">-- Select Teacher --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo (int)$teacher['id']; ?>">
                                    <?php echo htmlspecialchars((string)$teacher['name'] . ' (' . (string)$teacher['email'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-8">
                        <label>Subject</label>
                        <input type="text" class="form-control" name="subject" maxlength="190" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea class="form-control" name="message_body" rows="4" required></textarea>
                </div>
                <button type="submit" name="send_message" class="btn btn-primary">Send</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-danger">Received Messages</h6>
            <form method="post" class="m-0">
                <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-danger">Mark All Read</button>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>From Teacher</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Sent At</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($inboxRows)): foreach ($inboxRows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)($row['teacher_name'] ?? 'Teacher') . ' (' . (string)($row['teacher_email'] ?? '') . ')', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo nl2br(htmlspecialchars((string)$row['message_body'], ENT_QUOTES, 'UTF-8')); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((int)$row['is_read'] === 1): ?>
                                <span class="badge badge-success">Read</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Unread</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$row['is_read'] === 0): ?>
                                <form method="post" class="m-0">
                                    <input type="hidden" name="message_id" value="<?php echo (int)$row['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-sm btn-outline-primary">Mark Read</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center">No messages yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-info">Sent Messages</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>To Teacher</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Sent At</th>
                    <th>Read By Teacher</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($sentRows)): foreach ($sentRows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)($row['teacher_name'] ?? 'Teacher') . ' (' . (string)($row['teacher_email'] ?? '') . ')', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo nl2br(htmlspecialchars((string)$row['message_body'], ENT_QUOTES, 'UTF-8')); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ((int)$row['is_read'] === 1): ?>
                                <span class="badge badge-success">Read</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center">No sent messages yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
