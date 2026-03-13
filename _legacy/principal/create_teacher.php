<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teachers', 'create', 'index.php');
include '../db.php';

$flashType = '';
$flashMessage = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $classAssigned = trim($_POST['class_assigned'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
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

    if (
        $name === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $gender === '' || $dateOfBirth === '' || $cnic === '' || $address === '' ||
        $highestQualification === '' || $instituteName === '' || $passingYear === '' ||
        $experienceYears === '' || $previousSchool === '' || $specialization === ''
    ) {
        $flashType = 'danger';
        $flashMessage = 'Please fill all required teacher and profile fields.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $experienceYearsValue = (float)$experienceYears;
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO teachers (name, email, password, subject, phone, class_assigned) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Failed to prepare teacher insert.');
            }
            $stmt->bind_param("ssssss", $name, $email, $passwordHash, $subject, $phone, $classAssigned);
            if (!$stmt->execute()) {
                throw new Exception('Failed to add teacher.');
            }
            $teacherId = (int)$stmt->insert_id;
            $stmt->close();

            $profileStmt = $conn->prepare("
                INSERT INTO teacher_profiles
                (teacher_id, gender, date_of_birth, cnic, address, highest_qualification, institute_name, passing_year, certifications, experience_years, previous_school, specialization, achievements)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$profileStmt) {
                throw new Exception('Failed to prepare teacher profile insert.');
            }
            $profileStmt->bind_param(
                "issssssssisss",
                $teacherId,
                $gender,
                $dateOfBirth,
                $cnic,
                $address,
                $highestQualification,
                $instituteName,
                $passingYear,
                $certifications,
                $experienceYearsValue,
                $previousSchool,
                $specialization,
                $achievements
            );
            if (!$profileStmt->execute()) {
                throw new Exception('Failed to save teacher profile.');
            }
            $profileStmt->close();

            $conn->commit();
            $flashType = 'success';
            $flashMessage = 'Teacher created successfully with complete profile.';
        } catch (Throwable $e) {
            $conn->rollback();
            if ($conn->errno === 1062) {
                $flashType = 'danger';
                $flashMessage = 'Email already exists.';
            } else {
                $flashType = 'danger';
                $flashMessage = $e->getMessage();
            }
        }
    }
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 text-gray-800 mb-0">Create Teacher</h1>
        <div>
            <a class="btn btn-primary btn-sm" href="assign.php">Assign/Change Class</a>
            <a class="btn btn-info btn-sm" href="teacher_detail.php">Teacher Details</a>
        </div>
    </div>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Create Teacher Account + Full Profile</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Password</label>
                        <input type="text" class="form-control" name="password" minlength="6" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Subject</label>
                        <input type="text" class="form-control" name="subject">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Class Assigned</label>
                        <input type="text" class="form-control" name="class_assigned" placeholder="e.g. 10th Grade A">
                    </div>
                </div>
                <hr>
                <h6 class="text-primary">Personal Details</h6>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Gender</label>
                        <select class="form-control" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date of Birth</label>
                        <input type="date" class="form-control" name="date_of_birth" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>CNIC</label>
                        <input type="text" class="form-control" name="cnic" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Experience (Years)</label>
                        <input type="number" step="0.1" min="0" class="form-control" name="experience_years" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea class="form-control" name="address" rows="2" required></textarea>
                </div>
                <h6 class="text-primary">Academic Details</h6>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Highest Qualification</label>
                        <input type="text" class="form-control" name="highest_qualification" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Institute Name</label>
                        <input type="text" class="form-control" name="institute_name" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Passing Year</label>
                        <input type="text" class="form-control" name="passing_year" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Certifications</label>
                    <textarea class="form-control" name="certifications" rows="2"></textarea>
                </div>
                <h6 class="text-primary">Experience Details</h6>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Previous School/College</label>
                        <input type="text" class="form-control" name="previous_school" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Specialization</label>
                        <input type="text" class="form-control" name="specialization" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Achievements</label>
                    <textarea class="form-control" name="achievements" rows="2"></textarea>
                </div>
                <button type="submit" name="add_teacher" class="btn btn-success">Add Teacher</button>
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
