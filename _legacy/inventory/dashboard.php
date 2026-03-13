<?php
require_once __DIR__ . '/../auth.php';
auth_require_roles(['inventory_manager'], 'index.php');
require_once __DIR__ . '/../db.php';

auth_require_permission('inventory', 'view', 'index.php');

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
$allowedTabs = ['all', 'categories', 'items', 'stock_entry', 'issues', 'current_stock', 'reports', 'ledger'];
$activeTab = (string)($_GET['tab'] ?? 'all');
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'all';
}
$showAll = $activeTab === 'all';
$showCategories = $showAll || $activeTab === 'categories';
$showItems = $showAll || $activeTab === 'items';
$showStockEntry = $showAll || $activeTab === 'stock_entry';
$showIssues = $showAll || $activeTab === 'issues';
$showCurrentStock = $showAll || $activeTab === 'current_stock';
$showReports = $showAll || $activeTab === 'reports';
$showLedger = $showAll || $activeTab === 'ledger';

$conn->query("
    CREATE TABLE IF NOT EXISTS inventory_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(120) NOT NULL UNIQUE,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS inventory_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(190) NOT NULL,
        category VARCHAR(120) NULL,
        category_id INT NULL,
        unit VARCHAR(40) NOT NULL DEFAULT 'pcs',
        reorder_level INT NOT NULL DEFAULT 5,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS inventory_transactions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        txn_type ENUM('in','out') NOT NULL,
        quantity INT NOT NULL,
        note VARCHAR(255) NULL,
        class_id INT NULL,
        teacher_id INT NULL,
        created_by INT NULL,
        created_by_name VARCHAR(120) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_inventory_txn_item_date (item_id, created_at)
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS inventory_issues (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        quantity INT NOT NULL,
        issued_to_type ENUM('teacher','class') NOT NULL,
        teacher_id INT NULL,
        class_id INT NULL,
        issue_date DATE NOT NULL,
        note VARCHAR(255) NULL,
        txn_id BIGINT NULL,
        issued_by INT NULL,
        issued_by_name VARCHAR(120) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_inventory_issue_item_date (item_id, issue_date)
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS inventory_low_stock_alerts (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        stock_level INT NOT NULL,
        reorder_level INT NOT NULL,
        alert_date DATE NOT NULL,
        message VARCHAR(255) NOT NULL,
        UNIQUE KEY uniq_item_alert_date (item_id, alert_date),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
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

$ensureColumn = function (string $table, string $column, string $definition) use ($conn): void {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $exists = ((int)($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0);
    $stmt->close();
    if (!$exists) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
};
$ensureColumn('inventory_items', 'category_id', "INT NULL AFTER category");

$flashType = '';
$flashMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    auth_require_permission('inventory', 'create', 'index.php');
    $categoryName = trim((string)($_POST['category_name'] ?? ''));
    if ($categoryName === '') {
        $flashType = 'danger';
        $flashMessage = 'Category name is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory_categories (category_name, is_active) VALUES (?, 1)");
        if ($stmt) {
            $stmt->bind_param('s', $categoryName);
            if ($stmt->execute()) {
                $flashType = 'success';
                $flashMessage = 'Category created.';
                auth_audit_log($conn, 'create', 'inventory_category', $categoryName);
            } else {
                $flashType = 'danger';
                $flashMessage = 'Category already exists or could not be created.';
            }
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    auth_require_permission('inventory', 'edit', 'index.php');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $categoryName = trim((string)($_POST['category_name'] ?? ''));
    if ($categoryId <= 0 || $categoryName === '') {
        $flashType = 'danger';
        $flashMessage = 'Valid category details are required.';
    } else {
        $stmt = $conn->prepare("UPDATE inventory_categories SET category_name = ? WHERE id = ? AND is_active = 1");
        if ($stmt) {
            $stmt->bind_param('si', $categoryName, $categoryId);
            $stmt->execute();
            $updated = $stmt->affected_rows > 0;
            $stmt->close();
            if ($updated) {
                $flashType = 'success';
                $flashMessage = 'Category updated.';
                auth_audit_log($conn, 'update', 'inventory_category', (string)$categoryId, null, json_encode(['category_name' => $categoryName]));
            } else {
                $flashType = 'warning';
                $flashMessage = 'Category was not updated.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    auth_require_permission('inventory', 'delete', 'index.php');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    if ($categoryId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid category selection.';
    } else {
        $check = $conn->prepare("SELECT COUNT(*) AS c FROM inventory_items WHERE category_id = ? AND is_active = 1");
        $activeItems = 0;
        if ($check) {
            $check->bind_param('i', $categoryId);
            $check->execute();
            $activeItems = (int)($check->get_result()->fetch_assoc()['c'] ?? 0);
            $check->close();
        }
        if ($activeItems > 0) {
            $flashType = 'danger';
            $flashMessage = 'Cannot delete category: active items exist in this category.';
        } else {
            $stmt = $conn->prepare("UPDATE inventory_categories SET is_active = 0 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $categoryId);
                $stmt->execute();
                $stmt->close();
                $flashType = 'success';
                $flashMessage = 'Category deleted.';
                auth_audit_log($conn, 'delete', 'inventory_category', (string)$categoryId);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_item'])) {
    auth_require_permission('inventory', 'create', 'index.php');
    $itemName = trim((string)($_POST['item_name'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $category = trim((string)($_POST['category'] ?? ''));
    $unit = trim((string)($_POST['unit'] ?? 'pcs'));
    $reorderLevel = (int)($_POST['reorder_level'] ?? 5);
    if ($categoryId > 0) {
        $stmtCategory = $conn->prepare("SELECT category_name FROM inventory_categories WHERE id = ? LIMIT 1");
        if ($stmtCategory) {
            $stmtCategory->bind_param('i', $categoryId);
            $stmtCategory->execute();
            $categoryRow = $stmtCategory->get_result()->fetch_assoc();
            $stmtCategory->close();
            $category = (string)($categoryRow['category_name'] ?? $category);
        }
    }

    if ($itemName === '') {
        $flashType = 'danger';
        $flashMessage = 'Item name is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO inventory_items (item_name, category, category_id, unit, reorder_level, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        if ($stmt) {
            $stmt->bind_param('ssisi', $itemName, $category, $categoryId, $unit, $reorderLevel);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Inventory item created.';
            auth_audit_log($conn, 'create', 'inventory_item', $itemName);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    auth_require_permission('inventory', 'edit', 'index.php');
    $itemId = (int)($_POST['item_id'] ?? 0);
    $itemName = trim((string)($_POST['item_name'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $category = trim((string)($_POST['category'] ?? ''));
    $unit = trim((string)($_POST['unit'] ?? 'pcs'));
    $reorderLevel = (int)($_POST['reorder_level'] ?? 5);
    if ($categoryId > 0) {
        $stmtCategory = $conn->prepare("SELECT category_name FROM inventory_categories WHERE id = ? LIMIT 1");
        if ($stmtCategory) {
            $stmtCategory->bind_param('i', $categoryId);
            $stmtCategory->execute();
            $categoryRow = $stmtCategory->get_result()->fetch_assoc();
            $stmtCategory->close();
            $category = (string)($categoryRow['category_name'] ?? $category);
        }
    }
    if ($itemId <= 0 || $itemName === '') {
        $flashType = 'danger';
        $flashMessage = 'Valid item details are required.';
    } else {
        $stmt = $conn->prepare("UPDATE inventory_items SET item_name = ?, category = ?, category_id = ?, unit = ?, reorder_level = ? WHERE id = ? AND is_active = 1");
        if ($stmt) {
            $stmt->bind_param('ssisii', $itemName, $category, $categoryId, $unit, $reorderLevel, $itemId);
            $stmt->execute();
            $updated = $stmt->affected_rows > 0;
            $stmt->close();
            if ($updated) {
                $flashType = 'success';
                $flashMessage = 'Item updated.';
                auth_audit_log($conn, 'update', 'inventory_item', (string)$itemId, null, json_encode(['item_name' => $itemName, 'category' => $category]));
            } else {
                $flashType = 'warning';
                $flashMessage = 'Item was not updated.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    auth_require_permission('inventory', 'delete', 'index.php');
    $itemId = (int)($_POST['item_id'] ?? 0);
    if ($itemId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid item selection.';
    } else {
        $stmt = $conn->prepare("UPDATE inventory_items SET is_active = 0 WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $itemId);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Item deleted.';
            auth_audit_log($conn, 'delete', 'inventory_item', (string)$itemId);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_txn'])) {
    auth_require_permission('inventory', 'edit', 'index.php');
    $itemId = (int)($_POST['item_id'] ?? 0);
    $txnType = (string)($_POST['txn_type'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $note = trim((string)($_POST['note'] ?? ''));
    $classId = (int)($_POST['class_id'] ?? 0);
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $classId = $classId > 0 ? $classId : null;
    $teacherId = $teacherId > 0 ? $teacherId : null;
    $createdBy = (int)($_SESSION['auth_user_id'] ?? 0);
    $createdByName = (string)($_SESSION['auth_name'] ?? 'Inventory Manager');

    if ($itemId <= 0 || !in_array($txnType, ['in', 'out'], true) || $quantity <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Please provide valid transaction values.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO inventory_transactions
            (item_id, txn_type, quantity, note, class_id, teacher_id, created_by, created_by_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('isisiiis', $itemId, $txnType, $quantity, $note, $classId, $teacherId, $createdBy, $createdByName);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Transaction recorded.';
            auth_audit_log($conn, 'update', 'inventory_transaction', (string)$itemId, null, json_encode(['type' => $txnType, 'quantity' => $quantity]));
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_item'])) {
    auth_require_permission('inventory', 'edit', 'index.php');
    $itemId = (int)($_POST['issue_item_id'] ?? 0);
    $quantity = (int)($_POST['issue_quantity'] ?? 0);
    $issuedToType = (string)($_POST['issued_to_type'] ?? '');
    $teacherId = (int)($_POST['issue_teacher_id'] ?? 0);
    $classId = (int)($_POST['issue_class_id'] ?? 0);
    $issueDate = trim((string)($_POST['issue_date'] ?? date('Y-m-d')));
    $note = trim((string)($_POST['issue_note'] ?? ''));
    $issuedBy = (int)($_SESSION['auth_user_id'] ?? 0);
    $issuedByName = (string)($_SESSION['auth_name'] ?? 'Inventory Manager');

    if ($itemId <= 0 || $quantity <= 0 || !in_array($issuedToType, ['teacher', 'class'], true)) {
        $flashType = 'danger';
        $flashMessage = 'Provide valid issue details.';
    } elseif ($issuedToType === 'teacher' && $teacherId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Select teacher for issue.';
    } elseif ($issuedToType === 'class' && $classId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Select class for issue.';
    } else {
        // check stock
        $stockStmt = $conn->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN txn_type='in' THEN quantity ELSE 0 END),0) -
                COALESCE(SUM(CASE WHEN txn_type='out' THEN quantity ELSE 0 END),0) AS stock
            FROM inventory_transactions WHERE item_id = ?
        ");
        $currentStock = 0;
        if ($stockStmt) {
            $stockStmt->bind_param('i', $itemId);
            $stockStmt->execute();
            $currentStock = (int)($stockStmt->get_result()->fetch_assoc()['stock'] ?? 0);
            $stockStmt->close();
        }
        if ($quantity > $currentStock) {
            $flashType = 'danger';
            $flashMessage = 'Issue quantity exceeds current stock.';
        } else {
            // ledger out txn
            $txnStmt = $conn->prepare("
                INSERT INTO inventory_transactions
                (item_id, txn_type, quantity, note, class_id, teacher_id, created_by, created_by_name)
                VALUES (?, 'out', ?, ?, ?, ?, ?, ?)
            ");
            $txnId = 0;
            if ($txnStmt) {
                $txnClassId = $issuedToType === 'class' ? $classId : null;
                $txnTeacherId = $issuedToType === 'teacher' ? $teacherId : null;
                $issueNote = '[ISSUE] ' . $note;
                $txnStmt->bind_param('iisiiis', $itemId, $quantity, $issueNote, $txnClassId, $txnTeacherId, $issuedBy, $issuedByName);
                $txnOk = $txnStmt->execute();
                $txnId = (int)$txnStmt->insert_id;
                $txnStmt->close();
                if ($txnOk) {
                    $issueStmt = $conn->prepare("
                        INSERT INTO inventory_issues
                        (item_id, quantity, issued_to_type, teacher_id, class_id, issue_date, note, txn_id, issued_by, issued_by_name)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if ($issueStmt) {
                        $issueTeacherId = $issuedToType === 'teacher' ? $teacherId : null;
                        $issueClassId = $issuedToType === 'class' ? $classId : null;
                        $issueStmt->bind_param('iisisssiis', $itemId, $quantity, $issuedToType, $issueTeacherId, $issueClassId, $issueDate, $note, $txnId, $issuedBy, $issuedByName);
                        $issueStmt->execute();
                        $issueStmt->close();
                    }
                    $flashType = 'success';
                    $flashMessage = 'Issue recorded successfully.';
                    auth_audit_log($conn, 'create', 'inventory_issue', (string)$itemId, null, json_encode(['qty' => $quantity, 'to' => $issuedToType]));
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Failed to create issue record.';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_dummy_data'])) {
    auth_require_permission('inventory', 'create', 'index.php');
    $createdBy = (int)($_SESSION['auth_user_id'] ?? 0);
    $createdByName = (string)($_SESSION['auth_name'] ?? 'Inventory Manager');
    $seededItems = 0;
    $seededTxns = 0;
    $seededIssues = 0;

    $dummyCategories = ['Stationery', 'Lab Equipment', 'Cleaning Supplies', 'Sports'];
    $dummyItems = [
        ['A4 Paper Ream', 'Stationery', 'ream', 20, 120],
        ['Blue Marker', 'Stationery', 'pcs', 30, 180],
        ['Whiteboard Eraser', 'Stationery', 'pcs', 10, 40],
        ['Chemistry Test Tube', 'Lab Equipment', 'pcs', 25, 80],
        ['Digital Weighing Scale', 'Lab Equipment', 'pcs', 3, 12],
        ['Floor Cleaner', 'Cleaning Supplies', 'ltr', 15, 55],
        ['Classroom Broom', 'Cleaning Supplies', 'pcs', 8, 30],
        ['Football', 'Sports', 'pcs', 5, 16],
    ];

    foreach ($dummyCategories as $catName) {
        $stmt = $conn->prepare("INSERT IGNORE INTO inventory_categories (category_name, is_active) VALUES (?, 1)");
        if ($stmt) {
            $stmt->bind_param('s', $catName);
            $stmt->execute();
            $stmt->close();
        }
    }

    $categoryMap = [];
    $resCat = $conn->query("SELECT id, category_name FROM inventory_categories WHERE is_active = 1");
    while ($resCat && $row = $resCat->fetch_assoc()) {
        $categoryMap[strtolower((string)$row['category_name'])] = (int)$row['id'];
    }

    $firstClassId = null;
    $firstTeacherId = null;
    $resClass = $conn->query("SELECT id FROM classes ORDER BY id ASC LIMIT 1");
    if ($resClass && ($r = $resClass->fetch_assoc())) {
        $firstClassId = (int)$r['id'];
    }
    $resTeacher = $conn->query("SELECT id FROM teachers ORDER BY id ASC LIMIT 1");
    if ($resTeacher && ($r = $resTeacher->fetch_assoc())) {
        $firstTeacherId = (int)$r['id'];
    }

    foreach ($dummyItems as $idx => $d) {
        [$itemName, $catName, $unit, $reorderLevel, $openingStock] = $d;
        $catId = (int)($categoryMap[strtolower($catName)] ?? 0);

        $itemId = 0;
        $check = $conn->prepare("SELECT id FROM inventory_items WHERE item_name = ? LIMIT 1");
        if ($check) {
            $check->bind_param('s', $itemName);
            $check->execute();
            $itemId = (int)($check->get_result()->fetch_assoc()['id'] ?? 0);
            $check->close();
        }
        if ($itemId <= 0) {
            $ins = $conn->prepare("INSERT INTO inventory_items (item_name, category, category_id, unit, reorder_level, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            if ($ins) {
                $ins->bind_param('ssisi', $itemName, $catName, $catId, $unit, $reorderLevel);
                if ($ins->execute()) {
                    $itemId = (int)$ins->insert_id;
                    $seededItems++;
                }
                $ins->close();
            }
        }
        if ($itemId <= 0) {
            continue;
        }

        $hasTxn = false;
        $checkTxn = $conn->prepare("SELECT id FROM inventory_transactions WHERE item_id = ? LIMIT 1");
        if ($checkTxn) {
            $checkTxn->bind_param('i', $itemId);
            $checkTxn->execute();
            $hasTxn = (bool)$checkTxn->get_result()->fetch_assoc();
            $checkTxn->close();
        }
        if (!$hasTxn) {
            $txnIn = $conn->prepare("INSERT INTO inventory_transactions (item_id, txn_type, quantity, note, class_id, teacher_id, created_by, created_by_name) VALUES (?, 'in', ?, ?, NULL, NULL, ?, ?)");
            if ($txnIn) {
                $note = 'Dummy opening stock';
                $txnIn->bind_param('iisis', $itemId, $openingStock, $note, $createdBy, $createdByName);
                $txnIn->execute();
                $txnIn->close();
                $seededTxns++;
            }
            if ($idx % 2 === 0) {
                $txnOut = $conn->prepare("INSERT INTO inventory_transactions (item_id, txn_type, quantity, note, class_id, teacher_id, created_by, created_by_name) VALUES (?, 'out', ?, ?, ?, ?, ?, ?)");
                if ($txnOut) {
                    $outQty = max(1, (int)round($openingStock * 0.1));
                    $note = 'Dummy consumption';
                    $classId = $firstClassId;
                    $teacherId = $firstTeacherId;
                    $txnOut->bind_param('iisiiis', $itemId, $outQty, $note, $classId, $teacherId, $createdBy, $createdByName);
                    $txnOut->execute();
                    $txnOut->close();
                    $seededTxns++;
                }
            }
        }

        if ($idx < 3 && $firstClassId !== null) {
            $hasIssue = false;
            $checkIssue = $conn->prepare("SELECT id FROM inventory_issues WHERE item_id = ? LIMIT 1");
            if ($checkIssue) {
                $checkIssue->bind_param('i', $itemId);
                $checkIssue->execute();
                $hasIssue = (bool)$checkIssue->get_result()->fetch_assoc();
                $checkIssue->close();
            }
            if (!$hasIssue) {
                $issueQty = max(1, (int)round($openingStock * 0.05));
                $issueDate = date('Y-m-d');
                $issuedToType = $firstTeacherId ? 'teacher' : 'class';
                $teacherId = $firstTeacherId;
                $classId = $firstClassId;
                $note = 'Dummy issued stock';
                $txnId = null;
                $insIssue = $conn->prepare("INSERT INTO inventory_issues (item_id, quantity, issued_to_type, teacher_id, class_id, issue_date, note, txn_id, issued_by, issued_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($insIssue) {
                    $insIssue->bind_param('iisiissiis', $itemId, $issueQty, $issuedToType, $teacherId, $classId, $issueDate, $note, $txnId, $createdBy, $createdByName);
                    $insIssue->execute();
                    $insIssue->close();
                    $seededIssues++;
                }
            }
        }
    }

    $flashType = 'success';
    $flashMessage = 'Dummy data added. Items: ' . $seededItems . ', transactions: ' . $seededTxns . ', issues: ' . $seededIssues . '.';
}

$categories = [];
$res = $conn->query("SELECT id, category_name FROM inventory_categories WHERE is_active = 1 ORDER BY category_name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $categories[] = $row;
    }
}

$items = [];
$itemsSql = "
    SELECT
        i.id,
        i.item_name,
        i.category,
        i.unit,
        i.reorder_level,
        COALESCE(SUM(CASE WHEN t.txn_type = 'in' THEN t.quantity ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN t.txn_type = 'out' THEN t.quantity ELSE 0 END), 0) AS current_stock
    FROM inventory_items i
    LEFT JOIN inventory_transactions t ON t.item_id = i.id
    WHERE i.is_active = 1
    GROUP BY i.id, i.item_name, i.category, i.unit, i.reorder_level
    ORDER BY i.item_name ASC
";
$res = $conn->query($itemsSql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}

// Low stock alerts + optional principal inbox notification.
$lowStockItems = [];
$todayDate = date('Y-m-d');
foreach ($items as $item) {
    $stock = (int)($item['current_stock'] ?? 0);
    $reorder = (int)($item['reorder_level'] ?? 0);
    if ($stock <= $reorder) {
        $lowStockItems[] = $item;
        $msg = 'Low stock: ' . (string)$item['item_name'] . ' (' . $stock . ' <= reorder ' . $reorder . ')';
        $alertStmt = $conn->prepare("
            INSERT INTO inventory_low_stock_alerts (item_id, stock_level, reorder_level, alert_date, message)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE stock_level=VALUES(stock_level), reorder_level=VALUES(reorder_level), message=VALUES(message)
        ");
        if ($alertStmt) {
            $itemId = (int)$item['id'];
            $alertStmt->bind_param('iiiss', $itemId, $stock, $reorder, $todayDate, $msg);
            $alertStmt->execute();
            $alertStmt->close();
        }
        $principalUsers = $conn->query("SELECT id FROM staff_users WHERE role='principal' AND is_active=1");
        if ($principalUsers) {
            while ($p = $principalUsers->fetch_assoc()) {
                $pid = (int)($p['id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                $subject = 'Inventory Low Stock Alert';
                $body = $msg . ' on ' . $todayDate;
                $existMsg = $conn->prepare("
                    SELECT id FROM inbox_messages
                    WHERE sender_role='system' AND sender_id=0
                      AND recipient_role='principal' AND recipient_id=?
                      AND subject=? AND message_body=?
                      AND DATE(created_at)=?
                    LIMIT 1
                ");
                $exists = false;
                if ($existMsg) {
                    $existMsg->bind_param('isss', $pid, $subject, $body, $todayDate);
                    $existMsg->execute();
                    $exists = (bool)$existMsg->get_result()->fetch_assoc();
                    $existMsg->close();
                }
                if (!$exists) {
                    $insMsg = $conn->prepare("
                        INSERT INTO inbox_messages (sender_role, sender_id, recipient_role, recipient_id, subject, message_body, is_read)
                        VALUES ('system', 0, 'principal', ?, ?, ?, 0)
                    ");
                    if ($insMsg) {
                        $insMsg->bind_param('iss', $pid, $subject, $body);
                        $insMsg->execute();
                        $insMsg->close();
                    }
                }
            }
        }
    }
}

$classes = [];
$res = $conn->query("SELECT id, class FROM classes ORDER BY class ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $classes[] = $row;
    }
}
$teachers = [];
$res = $conn->query("SELECT id, name FROM teachers ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $teachers[] = $row;
    }
}

$transactions = [];
$res = $conn->query("
    SELECT t.id, t.created_at, i.item_name, t.txn_type, t.quantity, t.note, c.class AS class_name, tr.name AS teacher_name
    FROM inventory_transactions t
    JOIN inventory_items i ON i.id = t.item_id
    LEFT JOIN classes c ON c.id = t.class_id
    LEFT JOIN teachers tr ON tr.id = t.teacher_id
    ORDER BY t.id DESC
    LIMIT 80
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $transactions[] = $row;
    }
}

$issues = [];
$res = $conn->query("
    SELECT
        iss.id, iss.issue_date, i.item_name, iss.quantity, iss.issued_to_type,
        c.class AS class_name, t.name AS teacher_name, iss.note, iss.created_at
    FROM inventory_issues iss
    JOIN inventory_items i ON i.id = iss.item_id
    LEFT JOIN classes c ON c.id = iss.class_id
    LEFT JOIN teachers t ON t.id = iss.teacher_id
    ORDER BY iss.id DESC
    LIMIT 120
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $issues[] = $row;
    }
}

$reportMonth = trim((string)($_GET['report_month'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
    $reportMonth = date('Y-m');
}
$monthStart = $reportMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

$monthlyRows = [];
$stmt = $conn->prepare("
    SELECT
        i.id,
        i.item_name,
        i.category,
        COALESCE(SUM(CASE WHEN t.txn_type='in' THEN t.quantity ELSE 0 END),0) AS total_in,
        COALESCE(SUM(CASE WHEN t.txn_type='out' THEN t.quantity ELSE 0 END),0) AS total_out
    FROM inventory_items i
    LEFT JOIN inventory_transactions t
      ON t.item_id = i.id
     AND DATE(t.created_at) BETWEEN ? AND ?
    WHERE i.is_active = 1
    GROUP BY i.id, i.item_name, i.category
    ORDER BY i.item_name ASC
");
if ($stmt) {
    $stmt->bind_param('ss', $monthStart, $monthEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $monthlyRows[] = $row;
    }
    $stmt->close();
}

$kpi = [
    'categories' => (int)count($categories),
    'items' => (int)count($items),
    'transactions' => (int)count($transactions),
    'issues' => (int)count($issues),
    'low_stock' => (int)count($lowStockItems),
];
$stockLabel = [];
$stockValue = [];
foreach ($items as $item) {
    $stockLabel[] = (string)$item['item_name'];
    $stockValue[] = (int)($item['current_stock'] ?? 0);
}

$issueAgg = [];
foreach ($issues as $iss) {
    $name = (string)($iss['item_name'] ?? '');
    if ($name === '') {
        continue;
    }
    if (!isset($issueAgg[$name])) {
        $issueAgg[$name] = 0;
    }
    $issueAgg[$name] += (int)($iss['quantity'] ?? 0);
}
$issueLabel = array_keys($issueAgg);
$issueValue = array_values($issueAgg);
?>
<?php include './partials/topbar.php'; ?>
<style>
    .inventory-hero {
        border: 0;
        border-radius: 14px;
        background: linear-gradient(120deg, #1f3d7a 0%, #2a5298 55%, #3f7ac9 100%);
        color: #fff;
    }
    .inventory-hero .muted {
        color: rgba(255, 255, 255, 0.86);
    }
    .form-select {
        display: block;
        width: 100%;
        height: calc(1.5em + .75rem + 2px);
        padding: .375rem 1.75rem .375rem .75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #6e707e;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='5'%3E%3Cpath fill='%236e707e' d='M0 0l5 5 5-5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right .75rem center;
        background-size: 10px 5px;
        border: 1px solid #d1d3e2;
        border-radius: .35rem;
        appearance: none;
    }
    .form-select:focus {
        color: #6e707e;
        background-color: #fff;
        border-color: #bac8f3;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    .table td, .table th {
        vertical-align: middle;
    }
    .table thead th {
        background: #eef4fb;
        color: #163a5f;
        font-weight: 700;
    }
    .inventory-kpi {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 .2rem .6rem rgba(58, 59, 69, .08) !important;
    }
    .inventory-kpi .kpi-label {
        color: #6b7280;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .inventory-kpi .kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.1;
    }
    .inventory-action-form {
        display: inline-flex;
        margin-left: 8px;
    }
    .fw-semibold {
        font-weight: 700 !important;
    }
    .row.g-2,
    .row.g-3 {
        margin-right: -0.5rem;
        margin-left: -0.5rem;
    }
    .row.g-2 > [class*="col-"],
    .row.g-3 > [class*="col-"] {
        padding-right: 0.5rem;
        padding-left: 0.5rem;
    }
    .inventory-searchable tbody tr.inventory-hidden,
    .inventory-searchable-list li.inventory-hidden {
        display: none;
    }
    .chart-wrap {
        min-height: 320px;
    }
</style>
<div class="container-fluid">
    <div class="card inventory-hero shadow mb-4">
        <div class="card-body py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1 text-white">Inventory Manager Dashboard</h1>
                    <div class="muted">Welcome, <?php echo htmlspecialchars((string)($_SESSION['auth_name'] ?? 'Inventory Manager'), ENT_QUOTES, 'UTF-8'); ?>.</div>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge badge-light px-3 py-2">Date: <?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <form method="post" class="inventory-action-form">
                        <button type="submit" name="seed_dummy_data" class="btn btn-warning btn-sm" onclick="return confirm('Add sample inventory data now?');">Add Dummy Data</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($showAll): ?>
    <div class="row mb-3">
        <div class="col-md-2 col-6 mb-3">
            <div class="card inventory-kpi">
                <div class="card-body">
                    <div class="kpi-label">Categories</div>
                    <div class="kpi-value"><?php echo (int)$kpi['categories']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card inventory-kpi">
                <div class="card-body">
                    <div class="kpi-label">Items</div>
                    <div class="kpi-value"><?php echo (int)$kpi['items']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card inventory-kpi">
                <div class="card-body">
                    <div class="kpi-label">Transactions</div>
                    <div class="kpi-value"><?php echo (int)$kpi['transactions']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card inventory-kpi">
                <div class="card-body">
                    <div class="kpi-label">Issues</div>
                    <div class="kpi-value"><?php echo (int)$kpi['issues']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card inventory-kpi">
                <div class="card-body">
                    <div class="kpi-label">Low Stock</div>
                    <div class="kpi-value"><?php echo (int)$kpi['low_stock']; ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($showAll): ?>
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Current Stock Graph</div>
                <div class="card-body chart-wrap">
                    <canvas id="stockChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Issue Quantity Graph</div>
                <div class="card-body chart-wrap">
                    <canvas id="issueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'categories' || $activeTab === 'items' || $activeTab === 'stock_entry'): ?>
    <div class="row g-3 mb-3" id="categories">
        <?php if ($activeTab === 'categories'): ?>
        <div class="<?php echo $showAll ? 'col-md-4' : 'col-md-12'; ?>">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Item Categories</div>
                <div class="card-body">
                    <form method="post" class="mb-3">
                        <div class="input-group">
                            <input class="form-control" name="category_name" placeholder="New category name" required>
                            <button class="btn btn-primary" type="submit" name="create_category">Add</button>
                        </div>
                    </form>
                    <div class="small text-muted">Available categories:</div>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered inventory-searchable">
                            <thead>
                                <tr><th>Category</th><th style="width: 190px;">Actions</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <form method="post" class="form-inline">
                                            <input type="hidden" name="category_id" value="<?php echo (int)$cat['id']; ?>">
                                            <input class="form-control form-control-sm mr-2" name="category_name" value="<?php echo h($cat['category_name']); ?>" required>
                                            <button class="btn btn-sm btn-primary" type="submit" name="update_category">Edit</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Delete this category?');">
                                            <input type="hidden" name="category_id" value="<?php echo (int)$cat['id']; ?>">
                                            <button class="btn btn-sm btn-danger" type="submit" name="delete_category">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="2" class="text-muted text-center">No categories yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'items'): ?>
        <div class="<?php echo $showAll ? 'col-md-6' : 'col-md-12'; ?>" id="items">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Create Inventory Item</div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input class="form-control" name="item_name" placeholder="Item Name" required>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="category_id">
                                    <option value="0">Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int)$cat['id']; ?>"><?php echo h($cat['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input class="form-control" name="category" placeholder="Or type category">
                            </div>
                            <div class="col-md-2">
                                <input class="form-control" name="unit" value="pcs" placeholder="Unit">
                            </div>
                            <div class="col-md-2">
                                <input class="form-control" type="number" name="reorder_level" min="0" value="5" placeholder="Reorder">
                            </div>
                        </div>
                        <button class="btn btn-primary mt-2" type="submit" name="create_item">Add Item</button>
                    </form>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-bordered inventory-searchable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Unit</th>
                                    <th>Reorder</th>
                                    <th style="width: 210px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <form method="post" class="form-inline">
                                            <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                            <input class="form-control form-control-sm mr-2 mb-1" name="item_name" value="<?php echo h($item['item_name']); ?>" required>
                                    </td>
                                    <td>
                                            <select class="form-select form-select-sm mr-2 mb-1" name="category_id">
                                                <option value="0">Category</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((string)$cat['category_name'] === (string)$item['category']) ? 'selected' : ''; ?>>
                                                        <?php echo h($cat['category_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="category" value="<?php echo h((string)$item['category']); ?>">
                                    </td>
                                    <td>
                                            <input class="form-control form-control-sm mr-2 mb-1" name="unit" value="<?php echo h($item['unit']); ?>" required>
                                    </td>
                                    <td>
                                            <input class="form-control form-control-sm mr-2 mb-1" type="number" name="reorder_level" min="0" value="<?php echo (int)$item['reorder_level']; ?>" required>
                                    </td>
                                    <td>
                                            <button class="btn btn-sm btn-primary mr-1 mb-1" type="submit" name="update_item">Edit</button>
                                        </form>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this item?');">
                                            <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                            <button class="btn btn-sm btn-danger mb-1" type="submit" name="delete_item">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No items yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'stock_entry'): ?>
        <div class="<?php echo $showAll ? 'col-md-6' : 'col-md-12'; ?>">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Stock In / Stock Out</div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select class="form-select" name="item_id" required>
                                    <option value="">Item</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars((string)$item['item_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="txn_type" required>
                                    <option value="in">IN</option>
                                    <option value="out">OUT</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input class="form-control" type="number" name="quantity" min="1" required placeholder="Qty">
                            </div>
                            <div class="col-md-4">
                                <input class="form-control" name="note" placeholder="Note">
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" name="class_id">
                                    <option value="">Assign Class (optional)</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo (int)$class['id']; ?>"><?php echo htmlspecialchars((string)$class['class'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" name="teacher_id">
                                    <option value="">Assign Teacher (optional)</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo (int)$teacher['id']; ?>"><?php echo htmlspecialchars((string)$teacher['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-success mt-2" type="submit" name="record_txn">Record</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'issues'): ?>
    <div class="row g-3 mb-3" id="stock-entry">
        <div class="<?php echo $showAll ? 'col-md-7' : 'col-md-12'; ?>" id="issues">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Issue Item to Teacher / Class</div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select class="form-select" name="issue_item_id" required>
                                    <option value="">Item</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo (int)$item['id']; ?>"><?php echo h($item['item_name']); ?> (Stock: <?php echo (int)$item['current_stock']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input class="form-control" type="number" min="1" name="issue_quantity" placeholder="Qty" required>
                            </div>
                            <div class="col-md-3">
                                <input class="form-control" type="date" name="issue_date" value="<?php echo h(date('Y-m-d')); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="issued_to_type" id="issued_to_type" required>
                                    <option value="">Issue To</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="class">Class</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" name="issue_teacher_id" id="issue_teacher_id">
                                    <option value="">Select Teacher</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo (int)$teacher['id']; ?>"><?php echo h($teacher['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" name="issue_class_id" id="issue_class_id">
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo (int)$class['id']; ?>"><?php echo h($class['class']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <input class="form-control" name="issue_note" placeholder="Issue note (optional)">
                            </div>
                        </div>
                        <button class="btn btn-warning mt-2" type="submit" name="issue_item">Issue Item</button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($showAll): ?>
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Low Stock Alerts</div>
                <div class="card-body">
                    <?php if (!empty($lowStockItems)): ?>
                        <ul class="mb-0 inventory-searchable-list">
                            <?php foreach ($lowStockItems as $item): ?>
                                <li>
                                    <strong><?php echo h($item['item_name']); ?></strong>:
                                    Stock <?php echo (int)$item['current_stock']; ?> (Reorder <?php echo (int)$item['reorder_level']; ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="small text-muted mt-2">Principal inbox has been notified for daily low-stock alerts.</div>
                    <?php else: ?>
                        <div class="text-success">All items are above reorder level.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'current_stock'): ?>
    <div class="card border-0 shadow-sm mb-3" id="current-stock">
        <div class="card-header bg-white fw-semibold">Current Stock</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm inventory-searchable">
                <thead>
                    <tr><th>Item</th><th>Category</th><th>Unit</th><th>Reorder Level</th><th>Current Stock</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($items)): foreach ($items as $item): ?>
                    <?php
                        $stock = (int)$item['current_stock'];
                        $reorder = (int)$item['reorder_level'];
                        $low = $stock <= $reorder;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$item['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$item['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$item['unit'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $reorder; ?></td>
                        <td><?php echo $stock; ?></td>
                        <td><?php echo $low ? '<span class="badge badge-danger">Low Stock</span>' : '<span class="badge badge-success">OK</span>'; ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center">No inventory items yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'issues'): ?>
    <div class="card border-0 shadow-sm mb-3" id="issue-records">
        <div class="card-header bg-white fw-semibold">Issue Records (Teacher / Class)</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm inventory-searchable">
                <thead>
                    <tr><th>Date</th><th>Item</th><th>Qty</th><th>Issued To</th><th>Class</th><th>Teacher</th><th>Note</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($issues)): foreach ($issues as $iss): ?>
                    <tr>
                        <td><?php echo h($iss['issue_date']); ?></td>
                        <td><?php echo h($iss['item_name']); ?></td>
                        <td><?php echo (int)$iss['quantity']; ?></td>
                        <td><?php echo strtoupper(h($iss['issued_to_type'])); ?></td>
                        <td><?php echo h((string)($iss['class_name'] ?? '')); ?></td>
                        <td><?php echo h((string)($iss['teacher_name'] ?? '')); ?></td>
                        <td><?php echo h((string)($iss['note'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center">No issue records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'reports'): ?>
    <div class="card border-0 shadow-sm mb-3" id="reports">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span>Monthly Inventory Report</span>
            <form method="get" class="form-inline">
                <input type="hidden" name="tab" value="reports">
                <input type="month" name="report_month" value="<?php echo h($reportMonth); ?>" class="form-control form-control-sm mr-2">
                <button class="btn btn-sm btn-outline-primary">Load</button>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm inventory-searchable">
                <thead>
                    <tr><th>Item</th><th>Category</th><th>Total In</th><th>Total Out</th><th>Net Movement</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($monthlyRows)): foreach ($monthlyRows as $m): ?>
                    <?php $net = (int)$m['total_in'] - (int)$m['total_out']; ?>
                    <tr>
                        <td><?php echo h($m['item_name']); ?></td>
                        <td><?php echo h($m['category']); ?></td>
                        <td><?php echo (int)$m['total_in']; ?></td>
                        <td><?php echo (int)$m['total_out']; ?></td>
                        <td><?php echo $net; ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center">No monthly data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="small text-muted">Reporting window: <?php echo h($monthStart); ?> to <?php echo h($monthEnd); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'ledger'): ?>
    <div class="card border-0 shadow-sm" id="ledger">
        <div class="card-header bg-white fw-semibold">Stock In/Out Ledger (Recent Transactions)</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm inventory-searchable">
                <thead>
                    <tr><th>Date</th><th>Item</th><th>Type</th><th>Qty</th><th>Class</th><th>Teacher</th><th>Note</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($transactions)): foreach ($transactions as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)$t['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$t['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(strtoupper((string)$t['txn_type']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$t['quantity']; ?></td>
                        <td><?php echo htmlspecialchars((string)($t['class_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($t['teacher_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)($t['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center">No transactions yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const type = document.getElementById('issued_to_type');
        const teacher = document.getElementById('issue_teacher_id');
        const klass = document.getElementById('issue_class_id');
        function toggleTargets() {
            if (!type || !teacher || !klass) return;
            const v = type.value;
            teacher.disabled = v !== 'teacher';
            klass.disabled = v !== 'class';
            if (v !== 'teacher') teacher.value = '';
            if (v !== 'class') klass.value = '';
        }
        if (type) {
            type.addEventListener('change', toggleTargets);
            toggleTargets();
        }
    })();

    (function () {
        const input = document.getElementById('inventoryGlobalSearch');
        const button = document.getElementById('inventoryGlobalSearchBtn');
        if (!input) return;

        const applyFilter = function () {
            const term = input.value.trim().toLowerCase();
            document.querySelectorAll('.inventory-searchable tbody tr').forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.classList.toggle('inventory-hidden', term !== '' && text.indexOf(term) === -1);
            });
            document.querySelectorAll('.inventory-searchable-list li').forEach(function (item) {
                const text = item.textContent.toLowerCase();
                item.classList.toggle('inventory-hidden', term !== '' && text.indexOf(term) === -1);
            });
        };

        input.addEventListener('input', applyFilter);
        if (button) {
            button.addEventListener('click', applyFilter);
        }
    })();

    (function () {
        const stockCanvas = document.getElementById('stockChart');
        const issueCanvas = document.getElementById('issueChart');
        const stockLabels = <?php echo json_encode($stockLabel, JSON_UNESCAPED_UNICODE); ?>;
        const stockValues = <?php echo json_encode($stockValue, JSON_UNESCAPED_UNICODE); ?>;
        const issueLabels = <?php echo json_encode($issueLabel, JSON_UNESCAPED_UNICODE); ?>;
        const issueValues = <?php echo json_encode($issueValue, JSON_UNESCAPED_UNICODE); ?>;

        if (stockCanvas && window.Chart) {
            new Chart(stockCanvas, {
                type: 'bar',
                data: {
                    labels: stockLabels,
                    datasets: [{
                        label: 'Current Stock',
                        data: stockValues,
                        backgroundColor: '#3f7ac9'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        if (issueCanvas && window.Chart) {
            new Chart(issueCanvas, {
                type: 'line',
                data: {
                    labels: issueLabels,
                    datasets: [{
                        label: 'Issued Quantity',
                        data: issueValues,
                        borderColor: '#f6c23e',
                        backgroundColor: 'rgba(246,194,62,0.2)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    })();
</script>
<?php $noticesPopupApiPath = '../scripts/notices_api.php'; include __DIR__ . '/../scripts/notices_popup_snippet.php'; ?>
<?php include './partials/footer.php'; ?>
