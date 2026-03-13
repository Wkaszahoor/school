<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('medical_records', 'view', 'index.php');

include "db.php";
include "./partials/topbar.php";

$flashType = '';
$flashMessage = '';

$conn->query("\n    CREATE TABLE IF NOT EXISTS doctors (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        name VARCHAR(120) NOT NULL,\n        email VARCHAR(190) NOT NULL UNIQUE,\n        password VARCHAR(255) NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n    )\n");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    auth_require_permission('medical_records', 'create', 'index.php');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $flashType = 'danger';
        $flashMessage = 'Name, email and password are required.';
    } else {
        $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($emailValid === false) {
            $flashType = 'danger';
            $flashMessage = 'Invalid email format.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO doctors (name, email, password) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $name, $email, $hash);
                try {
                    $stmt->execute();
                    $flashType = 'success';
                    $flashMessage = 'Doctor added.';
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() === 1062) {
                        $flashType = 'danger';
                        $flashMessage = 'Email already exists.';
                    } else {
                        $flashType = 'danger';
                        $flashMessage = 'Failed to add doctor.';
                    }
                }
                $stmt->close();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doctor'])) {
    auth_require_permission('medical_records', 'edit', 'index.php');
    $id = (int)($_POST['doctor_id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $flashType = 'success';
            $flashMessage = 'Doctor removed.';
        }
    }
}

$doctors = [];
$res = $conn->query("SELECT id, name, email, created_at FROM doctors ORDER BY name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $doctors[] = $row;
    }
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Doctors</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Add Doctor</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="add_doctor" class="btn btn-primary">Add Doctor</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Doctor Accounts</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($doctors)): ?>
                            <?php foreach ($doctors as $doc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$doc['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$doc['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$doc['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Delete this doctor?');">
                                            <input type="hidden" name="doctor_id" value="<?php echo (int)$doc['id']; ?>">
                                            <button type="submit" name="delete_doctor" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No doctors found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include "./partials/footer.php";
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
