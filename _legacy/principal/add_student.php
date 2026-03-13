<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'create', '../index.php');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admission_helpers.php';
include './partials/topbar.php';

admission_ensure_students_table($conn);

$classes = [];
$cRes = $conn->query("SELECT DISTINCT class FROM students WHERE IFNULL(class,'') <> '' ORDER BY class ASC");
while ($cRes && $r = $cRes->fetch_assoc()) {
    $classes[] = (string)$r['class'];
}

$searchAdmissionNo = trim((string)($_GET['admission_no'] ?? ''));
$searchedStudent = null;
if ($searchAdmissionNo !== '') {
    $stmt = $conn->prepare("
        SELECT id, StudentId, admission_no, student_name, full_name, class, section, roll_no
        FROM students
        WHERE StudentId = ? OR admission_no = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('ss', $searchAdmissionNo, $searchAdmissionNo);
        $stmt->execute();
        $searchedStudent = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
}

$today = date('Y-m-d');
$admissionNo = admission_next_no($conn, (int)date('Y'));
$flashType = '';
$flashMsg = '';
$canViewTrustNotes = admission_can_view_trust_notes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $fatherName = trim((string)($_POST['father_name'] ?? ''));
    $motherName = trim((string)($_POST['mother_name'] ?? ''));
    $dob = trim((string)($_POST['dob'] ?? ''));
    $gender = trim((string)($_POST['gender'] ?? ''));
    $className = trim((string)($_POST['class'] ?? ''));
    $section = trim((string)($_POST['section'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $mobile = trim((string)($_POST['mobile'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $bloodGroup = trim((string)($_POST['blood_group'] ?? ''));
    $trustNotes = $canViewTrustNotes ? trim((string)($_POST['trust_notes'] ?? '')) : '';

    if ($fullName === '' || $fatherName === '' || $dob === '' || $gender === '' || $className === '') {
        $flashType = 'danger';
        $flashMsg = 'Full name, father name, DOB, gender, and class are required.';
    } else {
        [$okUpload, $photoPath] = admission_upload_photo($_FILES['photo'] ?? [], dirname(__DIR__));
        if (!$okUpload) {
            $flashType = 'danger';
            $flashMsg = $photoPath;
        } else {
            $admissionNo = admission_next_no($conn, (int)date('Y'));
            $rollNo = admission_next_roll_no($conn, $className);
            $admissionDate = $today;

            $stmt = $conn->prepare("
                INSERT INTO students (
                    StudentId, admission_no, student_name, full_name, guardian_name, father_name, mother_name,
                    dob, gender, class, section, roll_no, address, guardian_contact, mobile, phone, email,
                    blood_group, profile_image, photo, trust_notes, join_date_kort, admission_date, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            if ($stmt) {
                $stmt->bind_param(
                    'sssssssssssssssssssssss',
                    $admissionNo,
                    $admissionNo,
                    $fullName,
                    $fullName,
                    $fatherName,
                    $fatherName,
                    $motherName,
                    $dob,
                    $gender,
                    $className,
                    $section,
                    $rollNo,
                    $address,
                    $mobile,
                    $mobile,
                    $mobile,
                    $email,
                    $bloodGroup,
                    $photoPath,
                    $photoPath,
                    $trustNotes,
                    $admissionDate,
                    $admissionDate
                );
                $stmt->execute();
                $newId = (int)$stmt->insert_id;
                $stmt->close();

                auth_audit_log($conn, 'create', 'student', (string)$newId, null, json_encode([
                    'StudentId' => $admissionNo,
                    'class' => $className,
                ]));

                header('Location: admission_card.php?id=' . $newId . '&preview=1');
                exit();
            }

            $flashType = 'danger';
            $flashMsg = 'Unable to add student. Please try again.';
        }
    }
}
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Student Admission Form</h1>
        <a href="all_students.php" class="btn btn-secondary btn-sm">Back to Student List</a>
    </div>

    <?php if ($flashMsg !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Search by Admission No</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-row">
                <div class="form-group col-md-4">
                    <input type="text" class="form-control" name="admission_no" value="<?php echo htmlspecialchars($searchAdmissionNo, ENT_QUOTES, 'UTF-8'); ?>" placeholder="SCH-2026-001">
                </div>
                <div class="form-group col-md-2">
                    <button class="btn btn-outline-primary btn-block" type="submit">Search</button>
                </div>
            </form>
            <?php if ($searchAdmissionNo !== '' && !$searchedStudent): ?>
                <div class="text-danger small">No student found for this admission number.</div>
            <?php endif; ?>
            <?php if ($searchedStudent): ?>
                <div class="alert alert-info mb-0">
                    Found: <?php echo htmlspecialchars((string)($searchedStudent['student_name'] ?: $searchedStudent['full_name']), ENT_QUOTES, 'UTF-8'); ?>
                    (<?php echo htmlspecialchars((string)($searchedStudent['StudentId'] ?: $searchedStudent['admission_no']), ENT_QUOTES, 'UTF-8'); ?>)
                    <a class="btn btn-sm btn-primary ml-2" href="admission_card.php?id=<?php echo (int)$searchedStudent['id']; ?>&preview=1">Preview Card</a>
                    <a class="btn btn-sm btn-info ml-1" href="edit_student.php?id=<?php echo (int)$searchedStudent['id']; ?>">Edit</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Admission Details</h6>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="form-row">
                <div class="form-group col-md-3">
                    <label>Admission No</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($admissionNo, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="form-group col-md-3">
                    <label>Admission Date</label>
                    <input type="date" class="form-control" value="<?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="form-group col-md-6">
                    <label>Student Full Name *</label>
                    <input type="text" class="form-control" name="full_name" required>
                </div>

                <div class="form-group col-md-4">
                    <label>Father Name *</label>
                    <input type="text" class="form-control" name="father_name" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Mother Name</label>
                    <input type="text" class="form-control" name="mother_name">
                </div>
                <div class="form-group col-md-2">
                    <label>DOB *</label>
                    <input type="date" class="form-control" name="dob" required>
                </div>
                <div class="form-group col-md-2">
                    <label>Gender *</label>
                    <select name="gender" class="form-control" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <label>Class *</label>
                    <input list="class_list" name="class" class="form-control" required placeholder="e.g. 1, 2, Nursery, Prep">
                    <datalist id="class_list">
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group col-md-2">
                    <label>Section</label>
                    <input type="text" class="form-control" name="section" placeholder="A">
                </div>
                <div class="form-group col-md-3">
                    <label>Mobile</label>
                    <input type="text" class="form-control" name="mobile">
                </div>
                <div class="form-group col-md-4">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email">
                </div>

                <div class="form-group col-md-4">
                    <label>Blood Group</label>
                    <input type="text" class="form-control" name="blood_group" placeholder="A+">
                </div>
                <div class="form-group col-md-8">
                    <label>Address</label>
                    <textarea class="form-control" name="address" rows="2"></textarea>
                </div>
                <div class="form-group col-md-4">
                    <label>Photo</label>
                    <input type="file" class="form-control-file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/*">
                </div>
                <?php if ($canViewTrustNotes): ?>
                <div class="form-group col-md-8">
                    <label>Sensitive Trust Notes (Admin/Principal only)</label>
                    <textarea class="form-control" name="trust_notes" rows="2" placeholder="Confidential care/trust notes"></textarea>
                </div>
                <?php endif; ?>
                <div class="form-group col-md-12">
                    <button type="submit" class="btn btn-primary">Save and Generate Card</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
