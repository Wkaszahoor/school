<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'edit', '../index.php');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admission_helpers.php';
include './partials/topbar.php';

admission_ensure_students_table($conn);

$id = (int)($_GET['id'] ?? 0);
$student = $id > 0 ? admission_find_student($conn, $id) : null;
if (!$student) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Student not found.</div></div>';
    include './partials/footer.php';
    exit();
}

$flashType = '';
$flashMsg = '';
$canViewTrustNotes = admission_can_view_trust_notes();
$studentBefore = is_array($student) ? $student : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_student'])) {
        auth_require_permission('students', 'delete', '../index.php');
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            auth_audit_log_change($conn, 'delete', 'student', (string)$id, $studentBefore, ['deleted' => true]);
            header('Location: all_students.php');
            exit();
        }
        $flashType = 'danger';
        $flashMsg = 'Could not delete student.';
    } else {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $fatherName = trim((string)($_POST['father_name'] ?? ''));
        $motherName = trim((string)($_POST['mother_name'] ?? ''));
        $dob = trim((string)($_POST['dob'] ?? ''));
        $gender = trim((string)($_POST['gender'] ?? ''));
        $className = trim((string)($_POST['class'] ?? ''));
        $section = trim((string)($_POST['section'] ?? ''));
        $rollNo = trim((string)($_POST['roll_no'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $mobile = trim((string)($_POST['mobile'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $blood = trim((string)($_POST['blood_group'] ?? ''));
        $trustNotes = $canViewTrustNotes ? trim((string)($_POST['trust_notes'] ?? '')) : admission_v($student, 'trust_notes');

        if ($fullName === '' || $fatherName === '' || $dob === '' || $gender === '' || $className === '') {
            $flashType = 'danger';
            $flashMsg = 'Required fields are missing.';
        } else {
            [$okUpload, $photoPath] = admission_upload_photo($_FILES['photo'] ?? [], dirname(__DIR__));
            if (!$okUpload) {
                $flashType = 'danger';
                $flashMsg = $photoPath;
            } else {
                if ($photoPath === '') {
                    $photoPath = admission_v($student, 'profile_image', 'photo');
                }
                $stmt = $conn->prepare("
                    UPDATE students
                    SET student_name = ?, full_name = ?, guardian_name = ?, father_name = ?, mother_name = ?,
                        dob = ?, gender = ?, class = ?, section = ?, roll_no = ?, address = ?,
                        guardian_contact = ?, mobile = ?, phone = ?, email = ?, blood_group = ?,
                        profile_image = ?, photo = ?, trust_notes = ?
                    WHERE id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param(
                        'sssssssssssssssssssi',
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
                        $blood,
                        $photoPath,
                        $photoPath,
                        $trustNotes,
                        $id
                    );
                    $stmt->execute();
                    $stmt->close();
                    auth_audit_log_change(
                        $conn,
                        'edit',
                        'student',
                        (string)$id,
                        $studentBefore,
                        [
                            'student_name' => $fullName,
                            'full_name' => $fullName,
                            'guardian_name' => $fatherName,
                            'father_name' => $fatherName,
                            'mother_name' => $motherName,
                            'dob' => $dob,
                            'gender' => $gender,
                            'class' => $className,
                            'section' => $section,
                            'roll_no' => $rollNo,
                            'address' => $address,
                            'guardian_contact' => $mobile,
                            'mobile' => $mobile,
                            'phone' => $mobile,
                            'email' => $email,
                            'blood_group' => $blood,
                            'profile_image' => $photoPath,
                            'photo' => $photoPath,
                            'trust_notes' => $trustNotes,
                        ]
                    );
                    header('Location: admission_card.php?id=' . $id . '&preview=1');
                    exit();
                }
                $flashType = 'danger';
                $flashMsg = 'Could not update student.';
            }
        }
    }
}

$student = admission_find_student($conn, $id) ?: $student;
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Student</h1>
        <div>
            <a href="admission_card.php?id=<?php echo (int)$id; ?>&preview=1" class="btn btn-info btn-sm">Preview Card</a>
            <a href="student_documents.php?student_id=<?php echo (int)$id; ?>" class="btn btn-outline-primary btn-sm">Documents</a>
            <a href="all_students.php" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    <?php if ($flashMsg !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="form-row">
                <div class="form-group col-md-3">
                    <label>Admission No</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(admission_v($student, 'StudentId', 'admission_no'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>
                <div class="form-group col-md-5">
                    <label>Student Name *</label>
                    <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars(admission_v($student, 'student_name', 'full_name'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Father Name *</label>
                    <input type="text" class="form-control" name="father_name" required value="<?php echo htmlspecialchars(admission_v($student, 'father_name', 'guardian_name'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group col-md-4">
                    <label>Mother Name</label>
                    <input type="text" class="form-control" name="mother_name" value="<?php echo htmlspecialchars(admission_v($student, 'mother_name'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label>DOB *</label>
                    <input type="date" class="form-control" name="dob" required value="<?php echo htmlspecialchars(admission_v($student, 'dob'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label>Gender *</label>
                    <select name="gender" class="form-control" required>
                        <?php $g = admission_v($student, 'gender'); ?>
                        <option value="">Select</option>
                        <option value="Male" <?php echo $g === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $g === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $g === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Class *</label>
                    <input type="text" class="form-control" name="class" required value="<?php echo htmlspecialchars(admission_v($student, 'class'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label>Section</label>
                    <input type="text" class="form-control" name="section" value="<?php echo htmlspecialchars(admission_v($student, 'section'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group col-md-2">
                    <label>Roll No</label>
                    <input type="text" class="form-control" name="roll_no" value="<?php echo htmlspecialchars(admission_v($student, 'roll_no'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Mobile</label>
                    <input type="text" class="form-control" name="mobile" value="<?php echo htmlspecialchars(admission_v($student, 'mobile', 'guardian_contact'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars(admission_v($student, 'email'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Blood Group</label>
                    <input type="text" class="form-control" name="blood_group" value="<?php echo htmlspecialchars(admission_v($student, 'blood_group'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group col-md-8">
                    <label>Address</label>
                    <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars(admission_v($student, 'address'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="form-group col-md-4">
                    <label>Photo</label>
                    <input type="file" class="form-control-file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/*">
                </div>
                <?php if ($canViewTrustNotes): ?>
                <div class="form-group col-md-8">
                    <label>Sensitive Trust Notes (Admin/Principal only)</label>
                    <textarea class="form-control" name="trust_notes" rows="2"><?php echo htmlspecialchars(admission_v($student, 'trust_notes'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <?php endif; ?>
                <div class="form-group col-md-12">
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
            <hr>
            <form method="post" onsubmit="return confirm('Delete this student? This cannot be undone.');">
                <button type="submit" name="delete_student" class="btn btn-danger">Delete Student</button>
            </form>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
