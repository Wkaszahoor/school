<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/notice_lib.php';

header('Content-Type: application/json; charset=utf-8');

if (!auth_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = mysqli_connect('localhost', 'root', 'mysql', 'db_school_kort');
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Database unavailable']);
    exit();
}

notices_ensure_tables($conn);

$role = (string)($_SESSION['auth_role'] ?? '');
$userId = (int)($_SESSION['auth_user_id'] ?? 0);
$action = (string)($_REQUEST['action'] ?? 'list');

if ($action === 'mark_read') {
    $noticeId = (int)($_POST['notice_id'] ?? 0);
    $ok = notices_mark_read($conn, $noticeId, $role, $userId);
    echo json_encode(['ok' => $ok]);
    exit();
}

if ($action === 'mark_all') {
    $count = notices_mark_all_read($conn, $role, $userId);
    echo json_encode(['ok' => true, 'marked' => $count]);
    exit();
}

$limit = (int)($_GET['limit'] ?? 6);
$limit = max(1, min($limit, 30));
$onlyUnread = ((int)($_GET['unread'] ?? 1) === 1);

$rows = notices_fetch_for_user($conn, $role, $userId, $onlyUnread, $limit);
$out = [];
foreach ($rows as $r) {
    $out[] = [
        'id' => (int)$r['id'],
        'title' => (string)($r['title'] ?? ''),
        'body' => (string)($r['body'] ?? ''),
        'created_at' => (string)($r['created_at'] ?? ''),
        'is_read' => (int)($r['is_read'] ?? 0),
        'target_scope' => (string)($r['target_scope'] ?? 'all'),
    ];
}

echo json_encode([
    'ok' => true,
    'count' => count($out),
    'notices' => $out,
]);
