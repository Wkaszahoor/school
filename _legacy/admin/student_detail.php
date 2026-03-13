<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('students', 'view', 'index.php');
require_once __DIR__ . '/../principal/admission_helpers.php';
require_once __DIR__ . '/../scripts/stream_subject_lib.php';

include "db.php";

$flashType = '';
$flashMessage = '';

$groupOptions = [
    'general' => 'General',
    'pre_engineering' => 'Pre-Engineering',
    'pre_medical' => 'Pre-Medical',
    'ics' => 'ICS',
    // Legacy values
    'computer' => 'Computer',
    'biology' => 'Biology',
];

$groupAliases = [
    'general' => 'general',
    'pre engineering' => 'pre_engineering',
    'pre-engineering' => 'pre_engineering',
    'preengineering' => 'pre_engineering',
    'pre medical' => 'pre_medical',
    'pre-medical' => 'pre_medical',
    'premedical' => 'pre_medical',
    'ics' => 'ics',
    'ics math' => 'ics',
    'ics (mathematics)' => 'ics',
    'ics_math' => 'ics',
    'computer' => 'computer',
    'biology' => 'biology',
    'computer science' => 'computer',
];

function isSeniorClassName(string $className): bool
{
    return preg_match('/\b(9|10|11|12)(th)?\b/i', $className) === 1;
}

// Ensure schema supports class-year mapping for students (older MySQL compatible).
function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition): void
{
    $dbRes = $conn->query("SELECT DATABASE() AS dbname");
    $dbRow = $dbRes ? $dbRes->fetch_assoc() : null;
    $dbName = $dbRow['dbname'] ?? '';
    if ($dbName === '') {
        return;
    }

    $check = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.columns
        WHERE table_schema = ? AND table_name = ? AND column_name = ?
    ");
    if (!$check) {
        return;
    }
    $check->bind_param("sss", $dbName, $table, $column);
    $check->execute();
    $exists = (int)($check->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $check->close();

    if (!$exists) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

ensureColumnExists($conn, 'students', 'academic_year', 'VARCHAR(20) NULL AFTER `class`');
ensureColumnExists($conn, 'students', 'group_stream', 'VARCHAR(30) NULL AFTER `academic_year`');
ensureColumnExists($conn, 'students', 'dob', 'DATE NULL AFTER `group_stream`');
ensureColumnExists($conn, 'students', 'gender', 'VARCHAR(20) NULL AFTER `dob`');
ensureColumnExists($conn, 'students', 'guardian_name', 'VARCHAR(120) NULL AFTER `gender`');
ensureColumnExists($conn, 'students', 'guardian_contact', 'VARCHAR(30) NULL AFTER `guardian_name`');
ensureColumnExists($conn, 'students', 'address', 'VARCHAR(255) NULL AFTER `guardian_contact`');
ensureColumnExists($conn, 'students', 'join_date_kort', 'DATE NULL AFTER `address`');
ensureColumnExists($conn, 'students', 'orphan_status', 'VARCHAR(20) NULL AFTER `join_date_kort`');
ensureColumnExists($conn, 'students', 'blood_group', 'VARCHAR(10) NULL AFTER `orphan_status`');
ensureColumnExists($conn, 'students', 'profile_image', 'VARCHAR(255) NULL AFTER `blood_group`');
ensureColumnExists($conn, 'students', 'medical_notes', 'VARCHAR(255) NULL AFTER `profile_image`');
ensureColumnExists($conn, 'students', 'trust_notes', 'TEXT NULL AFTER `medical_notes`');

$classOptions = [];
$classRes = $conn->query("SELECT id, class, academic_year FROM classes ORDER BY academic_year DESC, class ASC");
while ($classRow = $classRes->fetch_assoc()) {
    $classOptions[] = $classRow;
}
$classMapById = [];
$classMapByKey = [];
foreach ($classOptions as $opt) {
    $cid = (int)$opt['id'];
    $classMapById[$cid] = $opt;
    $key = strtolower(trim((string)$opt['class'])) . '|' . strtolower(trim((string)($opt['academic_year'] ?? '')));
    $classMapByKey[$key] = $opt;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['populate_students'])) {
        auth_require_permission('students', 'populate', 'index.php');
        $academicYear = trim((string)($_POST['populate_academic_year'] ?? ''));
        if ($academicYear === '') {
            $academicYear = date('Y') . '-' . (date('Y') + 1);
        }
        $targetStudentsPerClass = 50;

        $groupsForSenior = ['ics', 'pre_medical', 'pre_engineering'];
        $firstNames = [
            'Ahmed', 'Ali', 'Hassan', 'Hussain', 'Bilal', 'Usman', 'Umar', 'Hamza', 'Zain',
            'Ayaan', 'Saad', 'Farhan', 'Ibrahim', 'Yousaf', 'Daniyal', 'Talha', 'Rayyan',
            'Areeb', 'Anas', 'Abdullah', 'Fatima', 'Ayesha', 'Zainab', 'Maryam', 'Hira',
            'Iqra', 'Sana', 'Noor', 'Eman', 'Maham', 'Laiba', 'Anaya', 'Minal', 'Khadija'
        ];
        $lastNames = [
            'Khan', 'Ahmed', 'Ali', 'Hussain', 'Raza', 'Malik', 'Butt', 'Sheikh', 'Qureshi',
            'Farooq', 'Siddiqui', 'Nawaz', 'Javed', 'Shah', 'Mirza', 'Chaudhry', 'Ansari',
            'Aslam', 'Younas', 'Mehmood', 'Bukhari', 'Arshad', 'Naseem', 'Akhtar'
        ];
        $inserted = 0;
        $skipped = 0;

        $conn->query("UPDATE students SET group_stream = 'general' WHERE (group_stream IS NULL OR group_stream = '') AND class IN ('Class 1','Class 2','Class 3','Class 4','Class 5','Class 6','Class 7','Class 8')");

        for ($grade = 1; $grade <= 12; $grade++) {
            $className = 'Class ' . $grade;
            $classId = 0;

            $getClass = $conn->prepare("SELECT id FROM classes WHERE class = ? AND academic_year = ? LIMIT 1");
            if ($getClass) {
                $getClass->bind_param("ss", $className, $academicYear);
                $getClass->execute();
                $classId = (int)($getClass->get_result()->fetch_assoc()['id'] ?? 0);
                $getClass->close();
            }
            if ($classId <= 0) {
                // Backward compatibility: some schemas still keep class name unique.
                $getClassByName = $conn->prepare("SELECT id FROM classes WHERE class = ? LIMIT 1");
                if ($getClassByName) {
                    $getClassByName->bind_param("s", $className);
                    $getClassByName->execute();
                    $classId = (int)($getClassByName->get_result()->fetch_assoc()['id'] ?? 0);
                    $getClassByName->close();
                }
            }

            if ($classId <= 0) {
                $addClass = $conn->prepare("INSERT INTO classes (class, academic_year) VALUES (?, ?)");
                if ($addClass) {
                    try {
                        $addClass->bind_param("ss", $className, $academicYear);
                        $addClass->execute();
                        $classId = (int)$addClass->insert_id;
                    } catch (mysqli_sql_exception $e) {
                        if ((int)$e->getCode() === 1062) {
                            $fallbackClass = $conn->prepare("SELECT id FROM classes WHERE class = ? LIMIT 1");
                            if ($fallbackClass) {
                                $fallbackClass->bind_param("s", $className);
                                $fallbackClass->execute();
                                $classId = (int)($fallbackClass->get_result()->fetch_assoc()['id'] ?? 0);
                                $fallbackClass->close();
                            }
                        } else {
                            throw $e;
                        }
                    }
                    $addClass->close();
                }
            }

            $currentCount = 0;
            $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM students WHERE class = ? AND IFNULL(academic_year, '') = IFNULL(?, '')");
            if ($countStmt) {
                $countStmt->bind_param("ss", $className, $academicYear);
                $countStmt->execute();
                $currentCount = (int)($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
                $countStmt->close();
            }

            if ($currentCount >= $targetStudentsPerClass) {
                continue;
            }

            $insertStmt = $conn->prepare("
                INSERT INTO students
                (StudentId, student_name, class, academic_year, group_stream, email, phone, dob, gender, guardian_name, guardian_contact, address, join_date_kort, orphan_status, blood_group, profile_image, medical_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$insertStmt) {
                continue;
            }

            for ($n = $currentCount + 1; $n <= $targetStudentsPerClass; $n++) {
                $studentId = sprintf("KORT%s%02d%03d", substr($academicYear, 2, 2), $grade, $n);
                $fn = $firstNames[($grade * 31 + $n) % count($firstNames)];
                $ln = $lastNames[($grade * 17 + $n) % count($lastNames)];
                $studentName = $fn . ' ' . $ln;
                if ($grade >= 9) {
                    $groupStream = $groupsForSenior[($n - 1) % count($groupsForSenior)];
                } else {
                    $groupStream = 'general';
                }
                $email = strtolower(str_replace(' ', '', $studentId)) . "@kort.edu";
                $phone = "03" . str_pad((string)random_int(0, 999999999), 9, "0", STR_PAD_LEFT);
                $gender = ($n % 2 === 0) ? 'Female' : 'Male';
                $dob = date('Y-m-d', strtotime('-' . (5 + $grade) . ' years +' . ($n % 250) . ' days'));
                $guardianName = "Guardian " . $grade . "-" . $n;
                $guardianContact = "03" . str_pad((string)random_int(0, 999999999), 9, "0", STR_PAD_LEFT);
                $address = "KORT Campus Residence, Block " . (($n % 5) + 1);
                $joinDateKort = date('Y-m-d', strtotime('-' . max(1, min(6, $grade - 1)) . ' years +' . ($n % 120) . ' days'));
                $orphanStatus = 'Yes';
                $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                $bloodGroup = $bloodGroups[$n % count($bloodGroups)];
                $profileImage = '';
                $medicalNotes = '';

                try {
                    $insertStmt->bind_param(
                        "sssssssssssssssss",
                        $studentId,
                        $studentName,
                        $className,
                        $academicYear,
                        $groupStream,
                        $email,
                        $phone,
                        $dob,
                        $gender,
                        $guardianName,
                        $guardianContact,
                        $address,
                        $joinDateKort,
                        $orphanStatus,
                        $bloodGroup,
                        $profileImage,
                        $medicalNotes
                    );
                    $insertStmt->execute();
                    $inserted++;
                } catch (mysqli_sql_exception $e) {
                    if ((int)$e->getCode() === 1062) {
                        $skipped++;
                    }
                }
            }
            $insertStmt->close();

            if ($grade >= 9 && $classId > 0) {
                stream_ensure_stream_subjects_for_class($conn, $classId, 'ics');
                stream_ensure_stream_subjects_for_class($conn, $classId, 'pre_medical');
            }
        }

        $flashType = 'success';
        $flashMessage = "Population complete for classes 1-12 (target {$targetStudentsPerClass} each). Inserted: {$inserted}, Skipped: {$skipped}.";
        auth_audit_log($conn, 'populate', 'students', $academicYear, null, json_encode(['inserted' => $inserted, 'skipped' => $skipped]));
    }

    if (isset($_POST['add_student'])) {
        auth_require_permission('students', 'create', 'index.php');
        $studentId = trim($_POST['StudentId'] ?? '');
        $studentName = trim($_POST['student_name'] ?? '');
        $classId = (int)($_POST['class_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $guardianName = trim($_POST['guardian_name'] ?? '');
        $guardianContact = trim($_POST['guardian_contact'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $joinDateKort = trim($_POST['join_date_kort'] ?? '');
        $orphanStatus = trim($_POST['orphan_status'] ?? '');
        $bloodGroup = trim($_POST['blood_group'] ?? '');
        $profileImage = '';
        $medicalNotes = trim($_POST['medical_notes'] ?? '');
        $trustNotes = admission_can_view_trust_notes() ? trim($_POST['trust_notes'] ?? '') : '';
        if (isset($_FILES['profile_image_file']) && is_array($_FILES['profile_image_file']) && (int)($_FILES['profile_image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$okPhoto, $photoPath] = admission_upload_photo($_FILES['profile_image_file'], dirname(__DIR__));
            if ($okPhoto) {
                $profileImage = $photoPath;
            } else {
                $flashType = 'danger';
                $flashMessage = $photoPath;
            }
        }

        if ($flashMessage === '' && ($studentId === '' || $studentName === '' || $classId <= 0)) {
            $flashType = 'danger';
            $flashMessage = 'Student ID, name, and class-year are required.';
        } else {
            $stmt = null;
            $classStmt = $conn->prepare("SELECT class, academic_year FROM classes WHERE id = ? LIMIT 1");
            $classData = null;
            if ($classStmt) {
                $classStmt->bind_param("i", $classId);
                $classStmt->execute();
                $classData = $classStmt->get_result()->fetch_assoc();
                $classStmt->close();
            }

            if (!$classData) {
                $flashType = 'danger';
                $flashMessage = 'Selected class-year not found.';
            } else {
                $class = (string)($classData['class'] ?? '');
                $academicYear = (string)($classData['academic_year'] ?? '');
                $groupStream = strtolower(trim($_POST['group_stream'] ?? ''));
                if (!array_key_exists($groupStream, $groupOptions)) {
                    $groupStream = '';
                }

                // Stream is required only for senior classes 9-12.
                $isSeniorClass = isSeniorClassName($class);
                if ($isSeniorClass && $groupStream === '') {
                    $flashType = 'danger';
                    $flashMessage = 'Please select group for class 9-12.';
                    $stmt = null;
                } else {
                    if (!$isSeniorClass && $groupStream === '') {
                        $groupStream = 'general';
                    }
                    $stmt = $conn->prepare("
                        INSERT INTO students
                        (StudentId, student_name, class, academic_year, group_stream, email, phone, dob, gender, guardian_name, guardian_contact, address, join_date_kort, orphan_status, blood_group, profile_image, medical_notes, trust_notes)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                }
            }

            if ($flashMessage === '' && $stmt) {
                $stmt->bind_param(
                    "ssssssssssssssssss",
                    $studentId,
                    $studentName,
                    $class,
                    $academicYear,
                    $groupStream,
                    $email,
                    $phone,
                    $dob,
                    $gender,
                    $guardianName,
                    $guardianContact,
                    $address,
                    $joinDateKort,
                    $orphanStatus,
                    $bloodGroup,
                    $profileImage,
                    $medicalNotes,
                    $trustNotes
                );
                try {
                    $stmt->execute();
                    $flashType = 'success';
                    $flashMessage = 'Student added successfully.';
                    stream_auto_assign_for_class($conn, $class, $academicYear, $groupStream);
                    auth_audit_log($conn, 'create', 'student', (string)$stmt->insert_id, null, json_encode(['StudentId' => $studentId, 'name' => $studentName, 'class' => $class, 'year' => $academicYear, 'group' => $groupStream]));
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() === 1062) {
                        $flashType = 'danger';
                        $flashMessage = 'Student ID already exists.';
                    } else {
                        $flashType = 'danger';
                        $flashMessage = 'Failed to add student.';
                    }
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['delete_student'])) {
        auth_require_permission('students', 'delete', 'index.php');
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $studentBefore = null;
            $oldStmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
            if ($oldStmt) {
                $oldStmt->bind_param("i", $id);
                $oldStmt->execute();
                $studentBefore = $oldStmt->get_result()->fetch_assoc() ?: null;
                $oldStmt->close();
            }
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $flashType = 'success';
                    $flashMessage = 'Student removed successfully.';
                    auth_audit_log_change($conn, 'delete', 'student', (string)$id, $studentBefore, ['deleted' => true]);
                } else {
                    $flashType = 'warning';
                    $flashMessage = 'Student not found.';
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['bulk_assign_group'])) {
        auth_require_permission('students', 'assign_group', 'index.php');
        $groupStream = strtolower(trim($_POST['bulk_group_stream'] ?? ''));
        $studentIds = $_POST['student_ids'] ?? [];
        if (!array_key_exists($groupStream, $groupOptions)) {
            $flashType = 'danger';
            $flashMessage = 'Please select a valid group for assignment.';
        } elseif (!is_array($studentIds) || count($studentIds) === 0) {
            $flashType = 'warning';
            $flashMessage = 'Please select at least one student.';
        } else {
            $validIds = [];
            foreach ($studentIds as $sid) {
                $sid = (int)$sid;
                if ($sid > 0) {
                    $validIds[] = $sid;
                }
            }

            if (empty($validIds)) {
                $flashType = 'warning';
                $flashMessage = 'No valid students selected.';
            } else {
                $placeholders = implode(',', array_fill(0, count($validIds), '?'));
                $types = str_repeat('i', count($validIds));
                $sql = "UPDATE students SET group_stream = ? WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $params = array_merge([$groupStream], $validIds);
                    $bindTypes = 's' . $types;
                    $refs = [];
                    foreach ($params as $k => $v) {
                        $refs[$k] = &$params[$k];
                    }
                    $stmt->bind_param($bindTypes, ...$refs);
                    $stmt->execute();
                    $stmt->close();
                    $normalizedBulk = stream_normalize_key($groupStream);
                    if (stream_is_auto_assign_supported($normalizedBulk)) {
                        $idsCsv = implode(',', array_map('intval', $validIds));
                        if ($idsCsv !== '') {
                            $classRes = $conn->query("SELECT DISTINCT class, IFNULL(academic_year,'') AS academic_year FROM students WHERE id IN ($idsCsv)");
                            while ($classRes && $classRow = $classRes->fetch_assoc()) {
                                stream_auto_assign_for_class(
                                    $conn,
                                    (string)($classRow['class'] ?? ''),
                                    (string)($classRow['academic_year'] ?? ''),
                                    $normalizedBulk
                                );
                            }
                        }
                    }
                    $flashType = 'success';
                    $flashMessage = 'Group assigned to selected students.';
                    auth_audit_log($conn, 'bulk_assign_group', 'students', (string)count($validIds), null, json_encode(['group' => $groupStream]));
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Failed to update selected students.';
                }
            }
        }
    }

    if (isset($_POST['import_students'])) {
        auth_require_permission('students', 'import', 'index.php');
        $inserted = 0;
        $skipped = 0;
        $failed = 0;
        if (!isset($_FILES['students_file']) || $_FILES['students_file']['error'] !== UPLOAD_ERR_OK) {
            $flashType = 'danger';
            $flashMessage = 'Please upload a valid CSV file.';
        } else {
            $tmpPath = $_FILES['students_file']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['students_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $flashType = 'danger';
                $flashMessage = 'Only CSV files are supported.';
            } else {
                if (($handle = fopen($tmpPath, 'r')) !== false) {
                    $header = fgetcsv($handle);
                    if (!$header) {
                        $flashType = 'danger';
                        $flashMessage = 'CSV is empty.';
                    } else {
                        $map = [];
                        foreach ($header as $idx => $col) {
                            $key = strtolower(trim((string)$col));
                            $map[$key] = $idx;
                        }

                        $get = function ($row, $keys) use ($map) {
                            foreach ($keys as $k) {
                                if (array_key_exists($k, $map)) {
                                    return $row[$map[$k]] ?? '';
                                }
                            }
                            return '';
                        };

                        $stmtInsert = $conn->prepare("INSERT INTO students (StudentId, student_name, class, academic_year, group_stream, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if (!$stmtInsert) {
                            $flashType = 'danger';
                            $flashMessage = 'Failed to prepare import.';
                        } else {
                            $autoStreamClassMap = [];
                            while (($row = fgetcsv($handle)) !== false) {
                                $studentId = trim((string)$get($row, ['studentid', 'student_id', 'id']));
                                $studentName = trim((string)$get($row, ['student_name', 'name']));
                                $className = trim((string)$get($row, ['class']));
                                $academicYear = trim((string)$get($row, ['academic_year', 'year']));
                                $classIdRaw = trim((string)$get($row, ['class_id']));
                                $email = trim((string)$get($row, ['email']));
                                $phone = trim((string)$get($row, ['phone']));
                                $groupRaw = strtolower(trim((string)$get($row, ['group', 'group_stream', 'stream'])));

                                if ($groupRaw !== '' && isset($groupAliases[$groupRaw])) {
                                    $groupRaw = $groupAliases[$groupRaw];
                                }
                                if (!array_key_exists($groupRaw, $groupOptions)) {
                                    $groupRaw = '';
                                }

                                if ($studentId === '' || $studentName === '') {
                                    $failed++;
                                    continue;
                                }

                                $class = '';
                                $year = '';
                                if ($classIdRaw !== '' && is_numeric($classIdRaw) && isset($classMapById[(int)$classIdRaw])) {
                                    $class = (string)$classMapById[(int)$classIdRaw]['class'];
                                    $year = (string)($classMapById[(int)$classIdRaw]['academic_year'] ?? '');
                                } elseif ($className !== '') {
                                    $key = strtolower($className) . '|' . strtolower($academicYear);
                                    if (isset($classMapByKey[$key])) {
                                        $class = (string)$classMapByKey[$key]['class'];
                                        $year = (string)($classMapByKey[$key]['academic_year'] ?? '');
                                    } else {
                                        $failed++;
                                        continue;
                                    }
                                } else {
                                    $failed++;
                                    continue;
                                }
                                if (!isSeniorClassName($class) && $groupRaw === '') {
                                    $groupRaw = 'general';
                                }

                                try {
                                    $stmtInsert->bind_param("sssssss", $studentId, $studentName, $class, $year, $groupRaw, $email, $phone);
                                    $stmtInsert->execute();
                                    $inserted++;
                                    $normalized = stream_normalize_key($groupRaw);
                                    if (stream_is_auto_assign_supported($normalized)) {
                                        $key = $normalized . '|' . strtolower($class) . '|' . strtolower($year);
                                        $autoStreamClassMap[$key] = [$normalized, $class, $year];
                                    }
                                } catch (mysqli_sql_exception $e) {
                                    if ($e->getCode() === 1062) {
                                        $skipped++;
                                    } else {
                                        $failed++;
                                    }
                                }
                            }
                            $stmtInsert->close();
                            foreach ($autoStreamClassMap as $pair) {
                                stream_auto_assign_for_class(
                                    $conn,
                                    (string)($pair[1] ?? ''),
                                    (string)($pair[2] ?? ''),
                                    (string)($pair[0] ?? '')
                                );
                            }

                            $flashType = 'success';
                            $flashMessage = "Import completed. Added: $inserted, Duplicates skipped: $skipped, Failed: $failed.";
                            auth_audit_log($conn, 'import', 'students', (string)$academicYear, null, json_encode(['inserted' => $inserted, 'skipped' => $skipped, 'failed' => $failed]));
                        }
                    }
                    fclose($handle);
                } else {
                    $flashType = 'danger';
                    $flashMessage = 'Unable to read uploaded file.';
                }
            }
        }
    }
}

$filterStream = strtolower(trim((string)($_GET['filter_stream'] ?? 'all')));
if ($filterStream !== 'all' && $filterStream !== 'unassigned' && !array_key_exists($filterStream, $groupOptions)) {
    $filterStream = 'all';
}

$students = [];
$sql = "SELECT id, StudentId, student_name, class, academic_year, group_stream, email, phone, dob, gender, guardian_name, guardian_contact, address, join_date_kort, orphan_status, blood_group, profile_image, medical_notes FROM students";
$where = [];
$params = [];
$types = '';

if ($filterStream !== 'all') {
    if ($filterStream === 'unassigned') {
        $where[] = "(group_stream IS NULL OR group_stream = '')";
    } else {
        $where[] = "group_stream = ?";
        $params[] = $filterStream;
        $types .= 's';
    }
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    auth_require_permission('students', 'export', 'index.php');
    $fileSuffix = $filterStream === 'all' ? 'all' : $filterStream;
    $filename = 'students_' . $fileSuffix . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    if ($out) {
        fputcsv($out, ['ID', 'Student ID', 'Name', 'Class', 'Academic Year', 'Stream', 'Email', 'Phone', 'DOB', 'Gender', 'Guardian Name', 'Guardian Contact', 'Address', 'Join Date KORT', 'Orphan Status', 'Blood Group', 'Profile Image', 'Medical Notes'], ',', '"', '\\');
        foreach ($students as $student) {
            $stream = (string)($student['group_stream'] ?? '');
            $streamLabel = $groupOptions[$stream] ?? ($stream === '' ? 'Unassigned' : $stream);
            fputcsv($out, [
                (int)$student['id'],
                (string)$student['StudentId'],
                (string)$student['student_name'],
                (string)$student['class'],
                (string)($student['academic_year'] ?? ''),
                (string)$streamLabel,
                (string)($student['email'] ?? ''),
                (string)($student['phone'] ?? ''),
                (string)($student['dob'] ?? ''),
                (string)($student['gender'] ?? ''),
                (string)($student['guardian_name'] ?? ''),
                (string)($student['guardian_contact'] ?? ''),
                (string)($student['address'] ?? ''),
                (string)($student['join_date_kort'] ?? ''),
                (string)($student['orphan_status'] ?? ''),
                (string)($student['blood_group'] ?? ''),
                (string)($student['profile_image'] ?? ''),
                (string)($student['medical_notes'] ?? ''),
            ], ',', '"', '\\');
        }
        fclose($out);
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
    exit();
}

include "./partials/topbar.php";
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Student Details</h1>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
            <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Add Student</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Academic Year for Auto Populate</label>
                        <input type="text" class="form-control" name="populate_academic_year" value="<?php echo htmlspecialchars(date('Y') . '-' . (date('Y') + 1), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <button type="submit" name="populate_students" class="btn btn-warning">Populate 50 Students Per Class (1-12)</button>
                    </div>
                </div>
            </form>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Student ID</label>
                        <input type="text" class="form-control" name="StudentId" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Name</label>
                        <input type="text" class="form-control" name="student_name" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Class & Academic Year</label>
                        <select class="form-control" name="class_id" id="class_id_select" required>
                            <option value="">Select class-year</option>
                            <?php foreach ($classOptions as $opt): ?>
                                <option value="<?php echo (int)$opt['id']; ?>" data-class-name="<?php echo htmlspecialchars((string)$opt['class'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string)$opt['class'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo htmlspecialchars((string)($opt['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="form-group col-md-3" id="group_stream_wrap" style="display:none;">
                        <label>Group (9-12)</label>
                        <select class="form-control" name="group_stream" id="group_stream_select">
                            <option value="">N/A</option>
                            <?php foreach ($groupOptions as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Date of Birth</label>
                        <input type="date" class="form-control" name="dob">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Gender</label>
                        <select class="form-control" name="gender">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Guardian Name</label>
                        <input type="text" class="form-control" name="guardian_name">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Guardian Contact</label>
                        <input type="text" class="form-control" name="guardian_contact">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Join Date (KORT)</label>
                        <input type="date" class="form-control" name="join_date_kort">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Orphan Status</label>
                        <select class="form-control" name="orphan_status">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Blood Group</label>
                        <input type="text" class="form-control" name="blood_group">
                    </div>
                    <div class="form-group col-md-5">
                        <label>Address</label>
                        <input type="text" class="form-control" name="address">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Profile Image Upload</label>
                        <input type="file" class="form-control-file" name="profile_image_file" accept=".jpg,.jpeg,.png,.webp,image/*">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Medical Notes</label>
                        <input type="text" class="form-control" name="medical_notes" placeholder="Allergy, medication, special care notes">
                    </div>
                </div>
                <?php if (admission_can_view_trust_notes()): ?>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label>Sensitive Trust Notes (Admin/Principal only)</label>
                        <textarea class="form-control" name="trust_notes" rows="2" placeholder="Confidential trust notes"></textarea>
                    </div>
                </div>
                <?php endif; ?>
                <button type="submit" name="add_student" class="btn btn-success">Add Student</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Import Students (CSV)</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>CSV File</label>
                        <input type="file" name="students_file" class="form-control" accept=".csv" required>
                        <small class="text-muted d-block mt-1">
                            Columns supported: `StudentId`, `student_name`, `class`, `academic_year`, `group_stream`, `email`, `phone`
                        </small>
                    </div>
                </div>
                <button type="submit" name="import_students" class="btn btn-primary">Import</button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Students</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label>Filter by Stream</label>
                        <select class="form-control" name="filter_stream">
                            <option value="all" <?php echo $filterStream === 'all' ? 'selected' : ''; ?>>All Streams</option>
                            <?php foreach ($groupOptions as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filterStream === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="unassigned" <?php echo $filterStream === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-info">Apply Filter</button>
                    </div>
                    <div class="form-group col-md-3">
                        <a class="btn btn-success" href="?filter_stream=<?php echo urlencode($filterStream); ?>&export=excel">Export Excel</a>
                    </div>
                </div>
            </form>

            <form method="POST" action="" class="mb-3" id="bulk_group_form">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label>Bulk Assign Group (Selected Students)</label>
                        <select class="form-control" name="bulk_group_stream" required>
                            <option value="">Select group</option>
                            <?php foreach ($groupOptions as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" name="bulk_assign_group" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="select_all_students"></th>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Academic Year</th>
                            <th>Stream</th>
                            <th>Join Date</th>
                            <th>Guardian</th>
                            <th>Profile Image</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="student_ids[]" form="bulk_group_form" value="<?php echo (int)$student['id']; ?>">
                                    </td>
                                    <td><?php echo (int)$student['id']; ?></td>
                                    <td><?php echo htmlspecialchars((string)$student['StudentId'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$student['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$student['class'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($student['academic_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php
                                        $stream = (string)($student['group_stream'] ?? '');
                                        echo htmlspecialchars($groupOptions[$stream] ?? $stream, ENT_QUOTES, 'UTF-8');
                                    ?></td>
                                    <td><?php echo htmlspecialchars((string)($student['join_date_kort'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($student['guardian_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($student['profile_image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($student['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($student['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Delete this student?');">
                                            <input type="hidden" name="id" value="<?php echo (int)$student['id']; ?>">
                                            <button type="submit" name="delete_student" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var classSelect = document.getElementById('class_id_select');
    var streamWrap = document.getElementById('group_stream_wrap');
    var streamSelect = document.getElementById('group_stream_select');
    var selectAll = document.getElementById('select_all_students');
    var bulkForm = document.getElementById('bulk_group_form');
    if (!classSelect || !streamWrap || !streamSelect) return;

    function isSeniorClass(className) {
        return /\b(9|10|11|12)(th)?\b/i.test(className || '');
    }

    function toggleStreamField() {
        var selectedOption = classSelect.options[classSelect.selectedIndex];
        var className = selectedOption ? (selectedOption.getAttribute('data-class-name') || '') : '';
        var show = isSeniorClass(className);
        streamWrap.style.display = show ? 'block' : 'none';
        streamSelect.required = show;
        if (!show) {
            streamSelect.value = '';
        }
    }

    classSelect.addEventListener('change', toggleStreamField);
    toggleStreamField();

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var boxes = document.querySelectorAll('input[type="checkbox"][name="student_ids[]"][form="bulk_group_form"]');
            boxes.forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }
})();
</script>

<?php
include "./partials/footer.php";
if (isset($conn) && $conn) {
    $conn->close();
}
ob_end_flush();
?>
