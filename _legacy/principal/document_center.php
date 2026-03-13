<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'view', '../index.php');
require __DIR__ . '/../db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = mysqli_connect('localhost', 'root', 'mysql', 'db_school_kort');
    if (!$conn) {
        die('Database connection failed in document_center.php: ' . mysqli_connect_error());
    }
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$students = [];
$res = $conn->query("SELECT id, StudentId, student_name, class FROM students ORDER BY student_name ASC LIMIT 2000");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
}

include './partials/topbar.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Document Printing Center</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Generate Student Documents</h6></div>
        <div class="card-body">
            <form method="get" action="certificate.php" class="mb-4">
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label>Student</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Select student</option>
                            <?php foreach ($students as $st): ?>
                                <option value="<?php echo (int)$st['id']; ?>">
                                    <?php echo h(($st['StudentId'] ?? '') . ' - ' . ($st['student_name'] ?? '') . ' (' . ($st['class'] ?? '') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Document Type</label>
                        <select name="type" class="form-control" required>
                            <option value="bonafide">Bonafide Certificate</option>
                            <option value="leaving">School Leaving Certificate</option>
                            <option value="character">Character Certificate</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>QR Code</label>
                        <select name="qr" class="form-control">
                            <option value="1">Embed QR</option>
                            <option value="0">No QR</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block">Open</button>
                    </div>
                </div>
            </form>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h6 class="font-weight-bold">Student ID Card</h6>
                        <p class="text-muted mb-2">Single student ID/admission card with optional QR embedding.</p>
                        <a href="all_students.php" class="btn btn-outline-primary btn-sm">Open Student List</a>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h6 class="font-weight-bold">Class-wise ID Cards</h6>
                        <p class="text-muted mb-2">Bulk print class cards on A4 from class filter.</p>
                        <a href="class_wise_cards.php" class="btn btn-outline-primary btn-sm">Open Class-wise Cards</a>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h6 class="font-weight-bold">Result Card PDF</h6>
                        <p class="text-muted mb-2">Printable result card (save as PDF from browser print).</p>
                        <a href="results_center.php" class="btn btn-outline-primary btn-sm">Open Results Center</a>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h6 class="font-weight-bold">Certificates</h6>
                        <p class="text-muted mb-2">Bonafide, School Leaving, and Character templates.</p>
                        <span class="badge badge-info">QR optional</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include './partials/footer.php'; ?>
