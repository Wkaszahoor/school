<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teacher_profile', 'view', '../index.php');

include "../db.php";

function teacher_normalize_gender(string $value): string
{
    $v = strtolower(trim($value));
    if ($v === '') {
        return '';
    }
    if (in_array($v, ['m', 'male', 'man', 'boy'], true)) {
        return 'Male';
    }
    if (in_array($v, ['f', 'female', 'woman', 'girl', 'femla'], true)) {
        return 'Female';
    }
    return $value;
}

function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition): void
{
    $check = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    if (!$check) {
        return;
    }
    $check->bind_param("ss", $table, $column);
    $check->execute();
    $exists = (int)($check->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $check->close();
    if (!$exists) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

ensureColumnExists($conn, 'teachers', 'academic_details', "TEXT NULL AFTER `phone`");
ensureColumnExists($conn, 'teachers', 'experience_details', "TEXT NULL AFTER `academic_details`");
$conn->query("
    CREATE TABLE IF NOT EXISTS teacher_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL UNIQUE,
        gender VARCHAR(20) NULL,
        date_of_birth DATE NULL,
        cnic VARCHAR(30) NULL,
        address TEXT NULL,
        highest_qualification VARCHAR(120) NULL,
        institute_name VARCHAR(190) NULL,
        passing_year VARCHAR(10) NULL,
        certifications TEXT NULL,
        experience_years DECIMAL(4,1) NULL,
        previous_school VARCHAR(190) NULL,
        specialization VARCHAR(190) NULL,
        achievements TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

$flashType = '';
$flashMessage = '';

$teacherId = (int)($_SESSION['teacher_id'] ?? 0);
$teacherEmail = (string)($_SESSION['id'] ?? '');

if ($teacherId <= 0 && $teacherEmail !== '') {
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $teacherEmail);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $teacherId = (int)($row['id'] ?? 0);
        $stmt->close();
        if ($teacherId > 0) {
            $_SESSION['teacher_id'] = $teacherId;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $phone = trim((string)($_POST['phone'] ?? ''));
    $academic = trim((string)($_POST['academic_details'] ?? ''));
    $experience = trim((string)($_POST['experience_details'] ?? ''));
    $gender = teacher_normalize_gender(trim((string)($_POST['gender'] ?? '')));

    if ($teacherId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Teacher account not found.';
    } else {
        $stmt = $conn->prepare("UPDATE teachers SET phone = ?, academic_details = ?, experience_details = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssi", $phone, $academic, $experience, $teacherId);
            $stmt->execute();
            $stmt->close();
            $profileStmt = $conn->prepare("
                INSERT INTO teacher_profiles (teacher_id, gender)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE gender = VALUES(gender)
            ");
            if ($profileStmt) {
                $profileStmt->bind_param("is", $teacherId, $gender);
                $profileStmt->execute();
                $profileStmt->close();
                $flashType = 'success';
                $flashMessage = 'Profile updated successfully.';
            } else {
                $flashType = 'warning';
                $flashMessage = 'Profile updated, but gender was not saved.';
            }
        } else {
            $flashType = 'danger';
            $flashMessage = 'Failed to update profile.';
        }
    }
}

$teacher = [
    'name' => '',
    'email' => $teacherEmail,
    'phone' => '',
    'gender' => '',
    'academic_details' => '',
    'experience_details' => ''
];
if ($teacherId > 0) {
    $stmt = $conn->prepare("
        SELECT t.name, t.email, t.phone, t.academic_details, t.experience_details, tp.gender
        FROM teachers t
        LEFT JOIN teacher_profiles tp ON tp.teacher_id = t.id
        WHERE t.id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $teacher = $row;
            $teacher['gender'] = teacher_normalize_gender((string)($teacher['gender'] ?? ''));
        }
        $stmt->close();
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Update Profile</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Teacher Profile</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-light border mb-3">
                <strong>Current Gender:</strong>
                <?php echo htmlspecialchars((string)($teacher['gender'] !== '' ? $teacher['gender'] : 'Not set'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($teacher['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars((string)($teacher['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Contact Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars((string)($teacher['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (strcasecmp((string)($teacher['gender'] ?? ''), 'Male') === 0) ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (strcasecmp((string)($teacher['gender'] ?? ''), 'Female') === 0) ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Academic Details</label>
                        <textarea name="academic_details" class="form-control" rows="4" placeholder="e.g. M.Sc Mathematics, B.Ed"><?php echo htmlspecialchars((string)($teacher['academic_details'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Experience Details</label>
                        <textarea name="experience_details" class="form-control" rows="4" placeholder="e.g. 7 years teaching experience"><?php echo htmlspecialchars((string)($teacher['experience_details'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
                <button type="submit" name="save_profile" class="btn btn-primary">Save Profile</button>
            </form>
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
