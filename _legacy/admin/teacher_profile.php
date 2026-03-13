<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teachers', 'view', 'index.php');

include "db.php";

$canEditTeacherProfile = auth_can('teachers', 'edit');
$flashType = '';
$flashMessage = '';
$teacherId = (int)($_GET['id'] ?? 0);

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

// Keep profile data in a dedicated table so existing teachers table remains stable.
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

if ($teacherId <= 0) {
    $flashType = 'danger';
    $flashMessage = 'Invalid teacher id.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_teacher_profile'])) {
    if (!$canEditTeacherProfile) {
        auth_require_permission('teachers', 'edit', 'index.php');
    }

    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $gender = teacher_normalize_gender(trim($_POST['gender'] ?? ''));
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $cnic = trim($_POST['cnic'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $highestQualification = trim($_POST['highest_qualification'] ?? '');
    $instituteName = trim($_POST['institute_name'] ?? '');
    $passingYear = trim($_POST['passing_year'] ?? '');
    $certifications = trim($_POST['certifications'] ?? '');
    $experienceYears = trim($_POST['experience_years'] ?? '');
    $previousSchool = trim($_POST['previous_school'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $achievements = trim($_POST['achievements'] ?? '');

    if ($teacherId <= 0) {
        $flashType = 'danger';
        $flashMessage = 'Invalid teacher id.';
    } else {
        $experienceYearsValue = null;
        if ($experienceYears !== '') {
            $experienceYearsValue = (float)$experienceYears;
        }
        $dobValue = $dateOfBirth !== '' ? $dateOfBirth : null;
        $experienceSqlValue = $experienceYearsValue;

        $stmt = $conn->prepare("
            INSERT INTO teacher_profiles
            (teacher_id, gender, date_of_birth, cnic, address, highest_qualification, institute_name, passing_year, certifications, experience_years, previous_school, specialization, achievements)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                gender = VALUES(gender),
                date_of_birth = VALUES(date_of_birth),
                cnic = VALUES(cnic),
                address = VALUES(address),
                highest_qualification = VALUES(highest_qualification),
                institute_name = VALUES(institute_name),
                passing_year = VALUES(passing_year),
                certifications = VALUES(certifications),
                experience_years = VALUES(experience_years),
                previous_school = VALUES(previous_school),
                specialization = VALUES(specialization),
                achievements = VALUES(achievements)
        ");

        if ($stmt) {
            $stmt->bind_param(
                "issssssssisss",
                $teacherId,
                $gender,
                $dobValue,
                $cnic,
                $address,
                $highestQualification,
                $instituteName,
                $passingYear,
                $certifications,
                $experienceSqlValue,
                $previousSchool,
                $specialization,
                $achievements
            );

            if ($stmt->execute()) {
                $flashType = 'success';
                $flashMessage = 'Teacher profile saved successfully.';
            } else {
                $flashType = 'danger';
                $flashMessage = 'Failed to save teacher profile.';
            }
            $stmt->close();
        } else {
            $flashType = 'danger';
            $flashMessage = 'Failed to prepare profile query.';
        }
    }
}

$teacher = null;
$stmtTeacher = $conn->prepare("SELECT id, name, email, subject, phone, class_assigned FROM teachers WHERE id = ? LIMIT 1");
if ($stmtTeacher && $teacherId > 0) {
    $stmtTeacher->bind_param("i", $teacherId);
    $stmtTeacher->execute();
    $teacher = $stmtTeacher->get_result()->fetch_assoc();
    $stmtTeacher->close();
}

$profile = [
    'gender' => '',
    'date_of_birth' => '',
    'cnic' => '',
    'address' => '',
    'highest_qualification' => '',
    'institute_name' => '',
    'passing_year' => '',
    'certifications' => '',
    'experience_years' => '',
    'previous_school' => '',
    'specialization' => '',
    'achievements' => '',
];

if ($teacher) {
    $stmtProfile = $conn->prepare("
        SELECT gender, date_of_birth, cnic, address, highest_qualification, institute_name, passing_year, certifications, experience_years, previous_school, specialization, achievements
        FROM teacher_profiles
        WHERE teacher_id = ?
        LIMIT 1
    ");
    if ($stmtProfile) {
        $stmtProfile->bind_param("i", $teacherId);
        $stmtProfile->execute();
        $row = $stmtProfile->get_result()->fetch_assoc();
        if ($row) {
            $profile['gender'] = (string)($row['gender'] ?? '');
            $profile['date_of_birth'] = (string)($row['date_of_birth'] ?? '');
            $profile['cnic'] = (string)($row['cnic'] ?? '');
            $profile['address'] = (string)($row['address'] ?? '');
            $profile['highest_qualification'] = (string)($row['highest_qualification'] ?? '');
            $profile['institute_name'] = (string)($row['institute_name'] ?? '');
            $profile['passing_year'] = (string)($row['passing_year'] ?? '');
            $profile['certifications'] = (string)($row['certifications'] ?? '');
            $profile['experience_years'] = (string)($row['experience_years'] ?? '');
            $profile['previous_school'] = (string)($row['previous_school'] ?? '');
            $profile['specialization'] = (string)($row['specialization'] ?? '');
            $profile['achievements'] = (string)($row['achievements'] ?? '');
            $profile['gender'] = teacher_normalize_gender($profile['gender']);
        } else {
            $insertProfile = $conn->prepare("INSERT IGNORE INTO teacher_profiles (teacher_id) VALUES (?)");
            if ($insertProfile) {
                $insertProfile->bind_param("i", $teacherId);
                $insertProfile->execute();
                $insertProfile->close();
            }
        }
        $stmtProfile->close();
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 text-gray-800 mb-0">Teacher Profile</h1>
        <a class="btn btn-secondary btn-sm" href="teacher_detail.php">Back to Teacher List</a>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!$teacher): ?>
        <div class="alert alert-warning">Teacher not found.</div>
    <?php else: ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Basic Teacher Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2"><strong>Name:</strong> <?php echo htmlspecialchars((string)$teacher['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-4 mb-2"><strong>Email:</strong> <?php echo htmlspecialchars((string)$teacher['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-4 mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars((string)($teacher['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-4 mb-2"><strong>Subject:</strong> <?php echo htmlspecialchars((string)($teacher['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-4 mb-2"><strong>Assigned Class:</strong> <?php echo htmlspecialchars((string)($teacher['class_assigned'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="teacher_id" value="<?php echo (int)$teacherId; ?>">

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Personal Details</h6>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Gender</label>
                            <select class="form-control" name="gender" <?php echo $canEditTeacherProfile ? '' : 'disabled'; ?>>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (strcasecmp((string)$profile['gender'], 'Male') === 0) ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (strcasecmp((string)$profile['gender'], 'Female') === 0) ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars($profile['date_of_birth'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>>
                        </div>
                        <div class="form-group col-md-3">
                            <label>CNIC</label>
                            <input type="text" class="form-control" name="cnic" value="<?php echo htmlspecialchars($profile['cnic'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Experience (Years)</label>
                            <input type="number" step="0.1" min="0" class="form-control" name="experience_years" value="<?php echo htmlspecialchars($profile['experience_years'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Address</label>
                        <textarea class="form-control" name="address" rows="2" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>><?php echo htmlspecialchars($profile['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Academic Details</h6>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Highest Qualification</label>
                            <input type="text" class="form-control" name="highest_qualification" value="<?php echo htmlspecialchars($profile['highest_qualification'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Institute Name</label>
                            <input type="text" class="form-control" name="institute_name" value="<?php echo htmlspecialchars($profile['institute_name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Passing Year</label>
                            <input type="text" class="form-control" name="passing_year" value="<?php echo htmlspecialchars($profile['passing_year'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Certifications</label>
                        <textarea class="form-control" name="certifications" rows="2" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>><?php echo htmlspecialchars($profile['certifications'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Experience Details</h6>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Previous School/College</label>
                            <input type="text" class="form-control" name="previous_school" value="<?php echo htmlspecialchars($profile['previous_school'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Specialization</label>
                            <input type="text" class="form-control" name="specialization" value="<?php echo htmlspecialchars($profile['specialization'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Key Achievements</label>
                        <textarea class="form-control" name="achievements" rows="3" <?php echo $canEditTeacherProfile ? '' : 'readonly'; ?>><?php echo htmlspecialchars($profile['achievements'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>

            <?php if ($canEditTeacherProfile): ?>
                <button type="submit" name="save_teacher_profile" class="btn btn-primary">Save Profile</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<?php
include "./partials/footer.php";
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
