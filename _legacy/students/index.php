<?php
ob_start();
require_once __DIR__ . '/../auth.php';
auth_require_permission('teacher_workspace', 'view', '../index.php');

include "../db.php";
require_once __DIR__ . '/../scripts/stream_subject_lib.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function tableExists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row["c"] ?? 0) > 0);
}

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row["c"] ?? 0) > 0);
}

function ensureColumn(mysqli $conn, string $table, string $column, string $definition): void
{
    if (!tableExists($conn, $table) || columnExists($conn, $table, $column)) {
        return;
    }
    $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}

$conn->query("CREATE TABLE IF NOT EXISTS homework_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    homework_date DATE NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    week_start DATE NOT NULL,
    lesson_plan TEXT NOT NULL,
    work_plan TEXT NULL,
    approval_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    principal_comment TEXT NULL,
    reviewed_by VARCHAR(120) NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS absent_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    student_id INT NOT NULL,
    report_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    class_teacher_id INT NULL,
    send_to_principal TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS behaviour_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    incident_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    notes TEXT NULL,
    action_taken VARCHAR(255) NULL,
    recorded_by VARCHAR(190) NOT NULL,
    report_to_class_teacher TINYINT(1) NOT NULL DEFAULT 1,
    report_to_principal TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_type VARCHAR(20) NOT NULL,
    teacher_id INT NULL,
    student_id INT NULL,
    class_id INT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    requested_by_role VARCHAR(40) NULL,
    requested_by_id INT NULL,
    approved_by VARCHAR(120) NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

ensureColumn($conn, "behaviour_records", "report_to_class_teacher", "TINYINT(1) NOT NULL DEFAULT 1");
ensureColumn($conn, "behaviour_records", "report_to_principal", "TINYINT(1) NOT NULL DEFAULT 1");
ensureColumn($conn, "students", "group_stream", "VARCHAR(30) NULL");
ensureColumn($conn, "lesson_plans", "approval_status", "VARCHAR(20) NOT NULL DEFAULT 'Pending'");
ensureColumn($conn, "lesson_plans", "principal_comment", "TEXT NULL");
ensureColumn($conn, "lesson_plans", "reviewed_by", "VARCHAR(120) NULL");
ensureColumn($conn, "lesson_plans", "reviewed_at", "DATETIME NULL");

$teacherEmail = (string)($_SESSION["id"] ?? "");
$teacherId = (int)($_SESSION["teacher_id"] ?? 0);
$teacherName = (string)($_SESSION["teacher_name"] ?? "Teacher");
$assignedClass = "N/A";

if ($teacherId > 0) {
    $stmt = $conn->prepare("SELECT id, name, email, class_assigned FROM teachers WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $teacherId = (int)$row["id"];
            $teacherEmail = (string)($row["email"] ?? $teacherEmail);
            $teacherName = (string)($row["name"] ?? $teacherName);
            $assignedClass = (string)($row["class_assigned"] ?? "N/A");
        }
    }
}

if ($teacherId <= 0 && $teacherEmail !== "") {
    $stmt = $conn->prepare("SELECT id, name, class_assigned FROM teachers WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $teacherEmail);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $teacherId = (int)$row["id"];
            $teacherName = (string)($row["name"] ?? $teacherName);
            $assignedClass = (string)($row["class_assigned"] ?? "N/A");
            $_SESSION["teacher_id"] = $teacherId;
            $_SESSION["teacher_name"] = $teacherName;
        }
    }
}

$assignments = [];
$assignmentById = [];
$studentsByAssignment = [];
$classTeacherClasses = [];
$classTeacherStudents = [];

$stmt = $conn->prepare("
    SELECT
        ta.id AS assignment_id,
        ta.class_id,
        ta.subject_id,
        c.class AS class_name,
        c.academic_year,
        c.class_teacher_id,
        s.subject_name
    FROM teacher_assignments ta
    JOIN classes c ON c.id = ta.class_id
    JOIN subjects s ON s.id = ta.subject_id
    WHERE ta.teacher_id = ?
    ORDER BY c.class ASC, s.subject_name ASC
");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
        $assignmentById[(int)$row["assignment_id"]] = $row;
    }
    $stmt->close();
}

foreach ($assignments as $assignment) {
    $aid = (int)$assignment["assignment_id"];
    $classId = (int)($assignment["class_id"] ?? 0);
    $subjectId = (int)($assignment["subject_id"] ?? 0);
    $className = (string)($assignment["class_name"] ?? "");
    $subjectName = (string)($assignment["subject_name"] ?? "");
    $streamRule = stream_get_subject_rule($conn, $classId, $subjectId, $subjectName);
    $studentsByAssignment[$aid] = [];

    $stmt = $conn->prepare("SELECT id, StudentId, student_name, class, group_stream FROM students WHERE class = ? ORDER BY student_name ASC");
    if ($stmt) {
        $stmt->bind_param("s", $className);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $streamRaw = (string)($row["group_stream"] ?? "");
            if (!stream_student_allowed($streamRaw, $streamRule)) {
                continue;
            }
            $studentsByAssignment[$aid][] = $row;
        }
        $stmt->close();
    }
}

// Class-teacher classes should also be visible on dashboard even without subject assignment.
$assignedClassIds = [];
foreach ($assignments as $assignment) {
    $assignedClassIds[(int)($assignment["class_id"] ?? 0)] = true;
}

$stmt = $conn->prepare("
    SELECT c.id AS class_id, c.class AS class_name, c.academic_year
    FROM classes c
    WHERE c.class_teacher_id = ?
    ORDER BY c.class ASC
");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $classId = (int)($row["class_id"] ?? 0);
        if ($classId <= 0 || isset($assignedClassIds[$classId])) {
            continue;
        }
        $classTeacherClasses[] = $row;
        $className = (string)($row["class_name"] ?? "");
        $classTeacherStudents[$classId] = [];

        $studentsStmt = $conn->prepare("SELECT id, StudentId, student_name, class, group_stream FROM students WHERE class = ? ORDER BY student_name ASC");
        if ($studentsStmt) {
            $studentsStmt->bind_param("s", $className);
            $studentsStmt->execute();
            $studentsRes = $studentsStmt->get_result();
            while ($srow = $studentsRes->fetch_assoc()) {
                $classTeacherStudents[$classId][] = $srow;
            }
            $studentsStmt->close();
        }
    }
    $stmt->close();
}

if (($assignedClass === "N/A" || trim($assignedClass) === "") && !empty($classTeacherClasses)) {
    $assignedClass = (string)($classTeacherClasses[0]["class_name"] ?? "N/A");
}

$flashType = "";
$flashMessage = "";
$today = date("Y-m-d");
$currentDate = (string)($_POST["attendance_date"] ?? $_GET["attendance_date"] ?? $today);
$selectedAssignmentId = (int)($_POST["selected_assignment_id"] ?? $_GET["selected_assignment_id"] ?? 0);
if ($selectedAssignmentId === 0 && !empty($assignments)) {
    $selectedAssignmentId = (int)$assignments[0]["assignment_id"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = (string)($_POST["action"] ?? "");

    if ($action === "save_attendance") {
        $aid = (int)($_POST["selected_assignment_id"] ?? 0);
        $date = trim((string)($_POST["attendance_date"] ?? ""));
        $reportAbsent = isset($_POST["report_absent"]);
        $reason = trim((string)($_POST["absent_reason"] ?? ""));

        if ($aid <= 0 || !isset($assignmentById[$aid]) || $date === "") {
            $flashType = "danger";
            $flashMessage = "Please choose assignment and date.";
        } else {
            $assignment = $assignmentById[$aid];
            $classId = (int)$assignment["class_id"];
            $subjectId = (int)$assignment["subject_id"];
            $classTeacherId = (int)($assignment["class_teacher_id"] ?? 0);
            $statuses = $_POST["attendance_status"] ?? [];
            $absentStudentIds = [];
            $attendanceBefore = [];
            $attendanceAfter = [];
            $attendanceInserted = 0;
            $attendanceUpdated = 0;

            $hasMarkedBy = columnExists($conn, "attendance", "marked_by");
            $hasReason = columnExists($conn, "attendance", "reason");

            foreach (($studentsByAssignment[$aid] ?? []) as $student) {
                $studentId = (int)$student["id"];
                $status = strtoupper(trim((string)($statuses[$studentId] ?? "P")));
                if (!in_array($status, ["P", "A", "L"], true)) {
                    $status = "P";
                }

                $existingId = 0;
                $oldStatus = '';
                $oldClassId = 0;
                $oldReason = '';
                $check = $conn->prepare("SELECT id, class_id, status, reason FROM attendance WHERE student_id = ? AND attendance_date = ? LIMIT 1");
                if ($check) {
                    $check->bind_param("is", $studentId, $date);
                    $check->execute();
                    $row = $check->get_result()->fetch_assoc();
                    $existingId = (int)($row["id"] ?? 0);
                    $oldClassId = (int)($row["class_id"] ?? 0);
                    $oldStatus = (string)($row["status"] ?? '');
                    $oldReason = (string)($row["reason"] ?? '');
                    $check->close();
                }

                $savedReason = ($status === "A" && $reason !== "") ? $reason : null;
                if ($existingId > 0) {
                    $attendanceBefore[] = [
                        'student_id' => $studentId,
                        'class_id' => $oldClassId,
                        'status' => $oldStatus,
                        'reason' => $oldReason,
                    ];
                }

                if ($existingId > 0) {
                    if ($hasMarkedBy && $hasReason) {
                        $stmt = $conn->prepare("UPDATE attendance SET class_id = ?, status = ?, marked_by = ?, reason = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("isisi", $classId, $status, $teacherId, $savedReason, $existingId);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $attendanceUpdated++;
                            }
                            $stmt->close();
                        }
                    } elseif ($hasMarkedBy) {
                        $stmt = $conn->prepare("UPDATE attendance SET class_id = ?, status = ?, marked_by = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("isii", $classId, $status, $teacherId, $existingId);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $attendanceUpdated++;
                            }
                            $stmt->close();
                        }
                    } elseif ($hasReason) {
                        $stmt = $conn->prepare("UPDATE attendance SET class_id = ?, status = ?, reason = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("issi", $classId, $status, $savedReason, $existingId);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $attendanceUpdated++;
                            }
                            $stmt->close();
                        }
                    } else {
                        $stmt = $conn->prepare("UPDATE attendance SET class_id = ?, status = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("isi", $classId, $status, $existingId);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $attendanceUpdated++;
                            }
                            $stmt->close();
                        }
                    }
                } else {
                    if ($hasMarkedBy && $hasReason) {
                        $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by, reason) VALUES (?, ?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("iissis", $studentId, $classId, $date, $status, $teacherId, $savedReason);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $attendanceInserted++;
                            }
                            $stmt->close();
                        }
                    } elseif ($hasMarkedBy) {
                        $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("iissi", $studentId, $classId, $date, $status, $teacherId);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $attendanceInserted++;
                            }
                            $stmt->close();
                        }
                    } elseif ($hasReason) {
                        $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, reason) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("iisss", $studentId, $classId, $date, $status, $savedReason);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $attendanceInserted++;
                            }
                            $stmt->close();
                        }
                    } else {
                        $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status) VALUES (?, ?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("iiss", $studentId, $classId, $date, $status);
                            $stmt->execute();
                            if ($stmt->affected_rows > 0) {
                                $attendanceInserted++;
                            }
                            $stmt->close();
                        }
                    }
                }
                $attendanceAfter[] = [
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'status' => $status,
                    'reason' => (string)$savedReason,
                ];

                if ($status === "A") {
                    $absentStudentIds[] = $studentId;
                }
            }

            if ($reportAbsent && !empty($absentStudentIds)) {
                $stmt = $conn->prepare("INSERT INTO absent_reports (teacher_id, class_id, subject_id, student_id, report_date, reason, class_teacher_id, send_to_principal) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                if ($stmt) {
                    foreach ($absentStudentIds as $studentId) {
                        $stmt->bind_param("iiiissi", $teacherId, $classId, $subjectId, $studentId, $date, $reason, $classTeacherId);
                        $stmt->execute();
                    }
                    $stmt->close();
                }
            }

            $flashType = "success";
            $flashMessage = "Attendance saved successfully.";
            if ($reportAbsent && !empty($absentStudentIds)) {
                $flashMessage .= " Missing report has been sent.";
            }
            auth_audit_log_change(
                $conn,
                'edit',
                'attendance',
                $aid . ':' . $date,
                [
                    'class_id' => $classId,
                    'assignment_id' => $aid,
                    'before_rows' => array_slice($attendanceBefore, 0, 40),
                ],
                [
                    'class_id' => $classId,
                    'assignment_id' => $aid,
                    'inserted' => $attendanceInserted,
                    'updated' => $attendanceUpdated,
                    'absent_count' => count($absentStudentIds),
                    'report_absent' => $reportAbsent ? 1 : 0,
                    'after_rows' => array_slice($attendanceAfter, 0, 40),
                ]
            );
            $currentDate = $date;
            $selectedAssignmentId = $aid;
        }
    }

    if ($action === "add_homework") {
        $aid = (int)($_POST["homework_assignment_id"] ?? 0);
        $date = trim((string)($_POST["homework_date"] ?? ""));
        $title = trim((string)($_POST["homework_title"] ?? ""));
        $description = trim((string)($_POST["homework_description"] ?? ""));
        if ($aid > 0 && isset($assignmentById[$aid]) && $date !== "" && $title !== "") {
            $assignment = $assignmentById[$aid];
            $stmt = $conn->prepare("INSERT INTO homework_tasks (teacher_id, class_id, subject_id, homework_date, title, description) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iiisss", $teacherId, $assignment["class_id"], $assignment["subject_id"], $date, $title, $description);
                $stmt->execute();
                $stmt->close();
                $flashType = "success";
                $flashMessage = "Homework added.";
            }
        }
    }

    if ($action === "add_lesson_plan") {
        $aid = (int)($_POST["plan_assignment_id"] ?? 0);
        $weekStart = trim((string)($_POST["week_start"] ?? ""));
        $lessonPlan = trim((string)($_POST["lesson_plan"] ?? ""));
        $workPlan = trim((string)($_POST["work_plan"] ?? ""));
        if ($aid > 0 && isset($assignmentById[$aid]) && $weekStart !== "" && $lessonPlan !== "") {
            $assignment = $assignmentById[$aid];
            $stmt = $conn->prepare("INSERT INTO lesson_plans (teacher_id, class_id, subject_id, week_start, lesson_plan, work_plan) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iiisss", $teacherId, $assignment["class_id"], $assignment["subject_id"], $weekStart, $lessonPlan, $workPlan);
                $stmt->execute();
                $stmt->close();
                $flashType = "success";
                $flashMessage = "Lesson plan saved.";
            }
        }
    }

    if ($action === "add_behaviour_report") {
        $aid = (int)($_POST["behaviour_assignment_id"] ?? 0);
        $studentId = (int)($_POST["behaviour_student_id"] ?? 0);
        $incidentDate = trim((string)($_POST["incident_date"] ?? ""));
        $category = trim((string)($_POST["category"] ?? ""));
        $notes = trim((string)($_POST["notes"] ?? ""));
        $actionTaken = trim((string)($_POST["action_taken"] ?? ""));
        $toClassTeacher = isset($_POST["to_class_teacher"]) ? 1 : 0;
        $toPrincipal = isset($_POST["to_principal"]) ? 1 : 0;
        if ($aid > 0 && isset($assignmentById[$aid]) && $studentId > 0 && $incidentDate !== "" && $category !== "") {
            $assignment = $assignmentById[$aid];
            $stmt = $conn->prepare("INSERT INTO behaviour_records (student_id, class_id, incident_date, category, notes, action_taken, recorded_by, report_to_class_teacher, report_to_principal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iisssssii", $studentId, $assignment["class_id"], $incidentDate, $category, $notes, $actionTaken, $teacherEmail, $toClassTeacher, $toPrincipal);
                $stmt->execute();
                $stmt->close();
                $flashType = "success";
                $flashMessage = "Behaviour report submitted.";
            }
        }
    }

    if ($action === "submit_teacher_leave_request") {
        auth_require_permission('leave_requests', 'create', '../index.php');
        $fromDate = trim((string)($_POST["leave_from_date"] ?? ""));
        $toDate = trim((string)($_POST["leave_to_date"] ?? ""));
        $reason = trim((string)($_POST["leave_reason"] ?? ""));
        if ($teacherId <= 0 || $fromDate === '' || $toDate === '') {
            $flashType = "danger";
            $flashMessage = "Please provide leave dates.";
        } elseif ($fromDate > $toDate) {
            $flashType = "danger";
            $flashMessage = "From date cannot be after to date.";
        } else {
            $dup = $conn->prepare("
                SELECT id
                FROM leave_requests
                WHERE request_type = 'teacher'
                  AND teacher_id = ?
                  AND status IN ('Pending', 'Approved')
                  AND NOT (to_date < ? OR from_date > ?)
                LIMIT 1
            ");
            $duplicateExists = false;
            if ($dup) {
                $dup->bind_param("iss", $teacherId, $fromDate, $toDate);
                $dup->execute();
                $duplicateExists = (bool)$dup->get_result()->fetch_assoc();
                $dup->close();
            }

            if ($duplicateExists) {
                $flashType = "danger";
                $flashMessage = "Leave request already exists for the selected date(s).";
            } else {
                $requestedByRole = (string)($_SESSION['auth_role'] ?? 'teacher');
                $requestedById = (int)($_SESSION['auth_user_id'] ?? $teacherId);
                $stmt = $conn->prepare("
                    INSERT INTO leave_requests
                    (request_type, teacher_id, from_date, to_date, reason, status, requested_by_role, requested_by_id)
                    VALUES ('teacher', ?, ?, ?, ?, 'Pending', ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("issssi", $teacherId, $fromDate, $toDate, $reason, $requestedByRole, $requestedById);
                    $stmt->execute();
                    $newId = (int)$stmt->insert_id;
                    $stmt->close();
                    auth_audit_log($conn, 'create', 'leave_request', (string)$newId, null, json_encode(['type' => 'teacher', 'teacher_id' => $teacherId]));
                    $flashType = "success";
                    $flashMessage = "Leave request submitted to principal for approval.";
                }
            }
        }
    }
}

$selectedAssignment = null;
if ($selectedAssignmentId > 0 && isset($assignmentById[$selectedAssignmentId])) {
    $selectedAssignment = $assignmentById[$selectedAssignmentId];
}

$attendanceByStudent = [];
if ($selectedAssignment) {
    $stmt = $conn->prepare("SELECT student_id, status FROM attendance WHERE class_id = ? AND attendance_date = ?");
    if ($stmt) {
        $stmt->bind_param("is", $selectedAssignment["class_id"], $currentDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $attendanceByStudent[(int)$row["student_id"]] = (string)$row["status"];
        }
        $stmt->close();
    }
}

$totalStudents = 0;
$totalPresent = 0;
$totalAbsent = 0;
if ($selectedAssignment) {
    $totalStudents = count($studentsByAssignment[$selectedAssignmentId] ?? []);
    foreach ($attendanceByStudent as $status) {
        if ($status === "P") {
            $totalPresent++;
        } elseif ($status === "A") {
            $totalAbsent++;
        }
    }
}
$recentHomework = [];
$stmt = $conn->prepare("
    SELECT h.homework_date, h.title, c.class, s.subject_name
    FROM homework_tasks h
    JOIN classes c ON c.id = h.class_id
    JOIN subjects s ON s.id = h.subject_id
    WHERE h.teacher_id = ?
    ORDER BY h.homework_date DESC, h.id DESC
    LIMIT 6
");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentHomework[] = $row;
    }
    $stmt->close();
}

$recentPlans = [];
$stmt = $conn->prepare("
    SELECT l.week_start, l.lesson_plan, l.approval_status, l.principal_comment, l.reviewed_by, l.reviewed_at, c.class, s.subject_name
    FROM lesson_plans l
    JOIN classes c ON c.id = l.class_id
    JOIN subjects s ON s.id = l.subject_id
    WHERE l.teacher_id = ?
    ORDER BY l.week_start DESC, l.id DESC
    LIMIT 6
");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentPlans[] = $row;
    }
    $stmt->close();
}

$recentBehaviour = [];
$stmt = $conn->prepare("
    SELECT b.incident_date, b.category, s.student_name, c.class, b.report_to_class_teacher, b.report_to_principal
    FROM behaviour_records b
    JOIN students s ON s.id = b.student_id
    JOIN classes c ON c.id = b.class_id
    WHERE b.recorded_by = ?
    ORDER BY b.incident_date DESC, b.id DESC
    LIMIT 6
");
if ($stmt) {
    $stmt->bind_param("s", $teacherEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentBehaviour[] = $row;
    }
    $stmt->close();
}

$recentLeaveRequests = [];
$stmt = $conn->prepare("
    SELECT from_date, to_date, reason, status, approved_by, approved_at
    FROM leave_requests
    WHERE request_type = 'teacher' AND teacher_id = ?
    ORDER BY id DESC
    LIMIT 6
");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentLeaveRequests[] = $row;
    }
    $stmt->close();
}
$teacherLeaveSummary = [
    'total_count' => 0,
    'pending_count' => 0,
    'approved_count' => 0,
    'rejected_count' => 0,
];
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_count,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count
    FROM leave_requests
    WHERE request_type = 'teacher' AND teacher_id = ?
");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $teacherLeaveSummary = [
            'total_count' => (int)($row['total_count'] ?? 0),
            'pending_count' => (int)($row['pending_count'] ?? 0),
            'approved_count' => (int)($row['approved_count'] ?? 0),
            'rejected_count' => (int)($row['rejected_count'] ?? 0),
        ];
    }
    $stmt->close();
}

$studentsJsonMap = [];
$assignmentStudentsDetailMap = [];
foreach ($studentsByAssignment as $assignmentId => $students) {
    $studentsJsonMap[$assignmentId] = [];
    $assignmentStudentsDetailMap[$assignmentId] = [];
    foreach ($students as $student) {
        $studentsJsonMap[$assignmentId][] = [
            "id" => (int)$student["id"],
            "text" => (string)$student["StudentId"] . " - " . (string)$student["student_name"]
        ];
        $assignmentStudentsDetailMap[$assignmentId][] = [
            "student_id" => (string)($student["StudentId"] ?? ""),
            "student_name" => (string)($student["student_name"] ?? ""),
            "stream" => (string)($student["group_stream"] ?? "")
        ];
    }
}
foreach ($classTeacherClasses as $ctClass) {
    $classId = (int)($ctClass["class_id"] ?? 0);
    if ($classId <= 0) {
        continue;
    }
    $ctKey = "ct_" . $classId;
    $assignmentStudentsDetailMap[$ctKey] = [];
    foreach (($classTeacherStudents[$classId] ?? []) as $student) {
        $assignmentStudentsDetailMap[$ctKey][] = [
            "student_id" => (string)($student["StudentId"] ?? ""),
            "student_name" => (string)($student["student_name"] ?? ""),
            "stream" => (string)($student["group_stream"] ?? "")
        ];
    }
}

$notices = [];
$conn->query("
    CREATE TABLE IF NOT EXISTS notices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        body TEXT NOT NULL,
        target_role VARCHAR(40) NOT NULL DEFAULT 'all',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by INT NULL,
        created_by_role VARCHAR(40) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$noticeStmt = $conn->prepare("
    SELECT id, title, body, created_at
    FROM notices
    WHERE is_active = 1 AND (target_role = 'all' OR target_role = 'teacher')
    ORDER BY id DESC
    LIMIT 5
");
if ($noticeStmt) {
    $noticeStmt->execute();
    $result = $noticeStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notices[] = $row;
    }
    $noticeStmt->close();
}

include "./partials/topbar.php";
?>
<style>
    #content { background: #f8f9fa; }
    .stat-card { border-left: .25rem solid; border-radius: .5rem; }
    .stat-primary { border-left-color: #4e73df; }
    .stat-info { border-left-color: #36b9cc; }
    .stat-success { border-left-color: #1cc88a; }
    .stat-danger { border-left-color: #e74a3b; }
    .attendance-table td { vertical-align: middle; }
    .attendance-table .status-select { min-width: 120px; }

    @media (max-width: 767.98px) {
        .mobile-stack .form-group { margin-bottom: .75rem; }
        .mobile-stack .btn { width: 100%; }
        .attendance-table thead { display: none; }
        .attendance-table tr {
            display: block;
            margin-bottom: .75rem;
            border: 1px solid #dee2e6;
            border-radius: .35rem;
            background: #fff;
        }
        .attendance-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 0 !important;
            border-bottom: 1px solid #f1f3f5 !important;
            padding: .6rem .75rem !important;
            white-space: normal !important;
            text-align: right;
        }
        .attendance-table td:last-child { border-bottom: 0 !important; }
        .attendance-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #4b5563;
            margin-right: .75rem;
            text-align: left;
        }
        .attendance-table td .status-select { width: 120px; }
    }
</style>

<div class="container-fluid mt-4">
    <h1 class="h3 mb-3 text-gray-800">Teacher Dashboard</h1>
    <p class="text-muted mb-4">Welcome, <?php echo h($teacherName); ?>.</p>

    <?php if ($flashMessage !== ""): ?>
        <div class="alert alert-<?php echo h($flashType !== "" ? $flashType : "info"); ?>">
            <?php echo h($flashMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($notices)): ?>
        <div class="card shadow mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="cursor:pointer;" data-toggle="collapse" data-target="#principalNoticesCollapse" aria-expanded="true" aria-controls="principalNoticesCollapse">
                <strong>Principal Notices</strong>
                <i class="fas fa-chevron-up" id="principalNoticesChevron"></i>
            </div>
            <div class="collapse show" id="principalNoticesCollapse">
            <div class="card-body">
                <?php foreach ($notices as $n): ?>
                    <div class="mb-2 pb-2 border-bottom">
                        <div class="font-weight-bold"><?php echo h($n['title']); ?></div>
                        <div class="small text-muted"><?php echo h($n['created_at']); ?></div>
                        <div><?php echo nl2br(h($n['body'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($assignments)): ?>
        <div class="card shadow mb-3">
            <div class="card-body py-3">
                <form method="get" class="form-row align-items-end mb-0">
                    <div class="form-group col-md-6 mb-0">
                        <label class="mb-1">Assigned Class and Subject</label>
                        <select name="selected_assignment_id" class="form-control">
                            <?php foreach ($assignments as $assignment): ?>
                                <?php $aid = (int)$assignment["assignment_id"]; ?>
                                <option value="<?php echo $aid; ?>" <?php echo ($selectedAssignmentId === $aid) ? "selected" : ""; ?>>
                                    <?php echo h($assignment["class_name"] . " - " . $assignment["subject_name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3 mb-0">
                        <label class="mb-1">Date</label>
                        <input type="date" name="attendance_date" class="form-control" value="<?php echo h($currentDate); ?>">
                    </div>
                    <div class="form-group col-md-3 mb-0">
                        <button type="submit" class="btn btn-primary btn-block">Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-primary shadow h-100">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-primary">Class</div>
                    <div class="h5 mb-0"><?php echo h($selectedAssignment["class_name"] ?? $assignedClass); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-info shadow h-100">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-info">Students</div>
                    <div class="h5 mb-0"><?php echo (int)$totalStudents; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-success shadow h-100">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-success">Present (<?php echo h($currentDate); ?>)</div>
                    <div class="h5 mb-0"><?php echo (int)$totalPresent; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-danger shadow h-100">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-danger">Absent (<?php echo h($currentDate); ?>)</div>
                    <div class="h5 mb-0"><?php echo (int)$totalAbsent; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4 function-card" id="attendance" data-section="attendance" style="display:none;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Attendance and Missing Report</h6>
        </div>
        <div class="card-body">
            <?php if (empty($assignments)): ?>
                <div class="alert alert-warning mb-0">No class/subject is assigned to this teacher yet.</div>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="action" value="save_attendance">
                    <div class="form-row mobile-stack">
                        <div class="form-group col-md-5">
                            <label>Assigned Class and Subject</label>
                            <select name="selected_assignment_id" class="form-control" required>
                                <?php foreach ($assignments as $assignment): ?>
                                    <?php $aid = (int)$assignment["assignment_id"]; ?>
                                    <option value="<?php echo $aid; ?>" <?php echo ($selectedAssignmentId === $aid) ? "selected" : ""; ?>>
                                        <?php echo h($assignment["class_name"] . " - " . $assignment["subject_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Date</label>
                            <input type="date" name="attendance_date" class="form-control" value="<?php echo h($currentDate); ?>" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label>&nbsp;</label>
                            <button class="btn btn-success btn-block">Save Attendance</button>
                        </div>
                        <div class="form-group col-md-2">
                            <label>&nbsp;</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="report_absent" id="report_absent">
                                <label class="form-check-label" for="report_absent">Report Missing</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="absent_reason" placeholder="Reason for absent report (optional)">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered attendance-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Stream</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($studentsByAssignment[$selectedAssignmentId] ?? []) as $student): ?>
                                    <?php
                                        $sid = (int)$student["id"];
                                        $status = (string)($attendanceByStudent[$sid] ?? "P");
                                    ?>
                                    <tr>
                                        <td data-label="Student"><?php echo h($student["StudentId"] . " - " . $student["student_name"]); ?></td>
                                        <td data-label="Stream"><?php echo h($student["group_stream"] ?? ""); ?></td>
                                        <td data-label="Status" style="max-width:150px;">
                                            <select class="form-control form-control-sm status-select" name="attendance_status[<?php echo $sid; ?>]">
                                                <option value="P" <?php echo ($status === "P") ? "selected" : ""; ?>>Present</option>
                                                <option value="A" <?php echo ($status === "A") ? "selected" : ""; ?>>Absent</option>
                                                <option value="L" <?php echo ($status === "L") ? "selected" : ""; ?>>Leave</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4 function-card" id="lesson-plan" data-section="lesson-plan" style="display:none;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Weekly Lesson Plan</h6>
        </div>
        <div class="card-body">
            <?php if (empty($assignments)): ?>
                <div class="alert alert-warning mb-0">No class/subject is assigned to this teacher yet.</div>
            <?php else: ?>
                <form method="post" class="mb-4">
                    <input type="hidden" name="action" value="add_lesson_plan">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Assigned Class and Subject</label>
                            <select name="plan_assignment_id" class="form-control" required>
                                <option value="">Select assignment</option>
                                <?php foreach ($assignments as $assignment): ?>
                                    <?php $aid = (int)$assignment["assignment_id"]; ?>
                                    <option value="<?php echo $aid; ?>">
                                        <?php echo h($assignment["class_name"] . " - " . $assignment["subject_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Week Start</label>
                            <input type="date" name="week_start" class="form-control" value="<?php echo h(date('Y-m-d')); ?>" required>
                        </div>
                        <div class="form-group col-md-5">
                            <label>Lesson Plan</label>
                            <input type="text" name="lesson_plan" class="form-control" placeholder="Topic and teaching plan" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Weekly Work Plan</label>
                        <textarea name="work_plan" class="form-control" rows="2" placeholder="Homework, activities, assessment, etc."></textarea>
                    </div>
                    <button class="btn btn-primary">Save Lesson Plan</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Week Start</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Lesson Plan</th>
                                <th>Status</th>
                                <th>Principal Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPlans as $plan): ?>
                                <tr>
                                    <td><?php echo h($plan["week_start"]); ?></td>
                                    <td><?php echo h($plan["class"]); ?></td>
                                    <td><?php echo h($plan["subject_name"]); ?></td>
                                    <td><?php echo h($plan["lesson_plan"]); ?></td>
                                    <td>
                                        <?php
                                            $status = (string)($plan["approval_status"] ?? "Pending");
                                            $badgeClass = "secondary";
                                            if ($status === "Approved") { $badgeClass = "success"; }
                                            elseif ($status === "Returned") { $badgeClass = "warning"; }
                                            elseif ($status === "Rejected") { $badgeClass = "danger"; }
                                        ?>
                                        <span class="badge badge-<?php echo h($badgeClass); ?>"><?php echo h($status); ?></span>
                                        <?php if (!empty($plan["reviewed_at"])): ?>
                                            <div class="small text-muted"><?php echo h($plan["reviewed_at"]); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo h($plan["principal_comment"] ?? ""); ?>
                                        <?php if (!empty($plan["reviewed_by"])): ?>
                                            <div class="small text-muted">By: <?php echo h($plan["reviewed_by"]); ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentPlans)): ?>
                                <tr><td colspan="6" class="text-center">No lesson plans submitted yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 function-card" data-section="leave-request" style="display:none;">
            <div class="card shadow mb-4" id="leave-request">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Teacher Leave Request</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge badge-light mr-1">Total: <?php echo (int)$teacherLeaveSummary['total_count']; ?></span>
                        <span class="badge badge-warning mr-1">Pending: <?php echo (int)$teacherLeaveSummary['pending_count']; ?></span>
                        <span class="badge badge-success mr-1">Approved: <?php echo (int)$teacherLeaveSummary['approved_count']; ?></span>
                        <span class="badge badge-danger">Rejected: <?php echo (int)$teacherLeaveSummary['rejected_count']; ?></span>
                    </div>
                    <form method="post">
                        <input type="hidden" name="action" value="submit_teacher_leave_request">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>From</label>
                                <input type="date" name="leave_from_date" class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>To</label>
                                <input type="date" name="leave_to_date" class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Reason</label>
                                <input type="text" name="leave_reason" class="form-control" placeholder="Optional reason">
                            </div>
                        </div>
                        <button class="btn btn-warning">Submit to Principal</button>
                    </form>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Approved By</th>
                                    <th>Approved At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLeaveRequests as $row): ?>
                                    <tr>
                                        <td><?php echo h($row["from_date"]); ?></td>
                                        <td><?php echo h($row["to_date"]); ?></td>
                                        <td><?php echo h($row["reason"] ?? ""); ?></td>
                                        <td>
                                            <?php echo h($row["status"] ?? ""); ?>
                                        </td>
                                        <td><?php echo h($row["approved_by"] ?? "-"); ?></td>
                                        <td><?php echo h($row["approved_at"] ?? "-"); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentLeaveRequests)): ?>
                                    <tr><td colspan="6" class="text-center">No leave requests yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4 function-card" id="assigned-classes" data-section="assigned-classes">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Assigned Classes</h6></div>
        <div class="card-body">
                <div class="form-row">
                    <?php if (!empty($assignments) || !empty($classTeacherClasses)): ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php $aid = (int)$assignment["assignment_id"]; ?>
                            <?php $studentCount = count($studentsByAssignment[$aid] ?? []); ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border-left-primary shadow-sm h-100">
                                    <div class="card-body py-3">
                                        <div class="text-xs text-uppercase text-primary mb-1">Class</div>
                                        <div class="font-weight-bold"><?php echo h($assignment["class_name"]); ?></div>
                                        <div class="small text-muted"><?php echo h($assignment["subject_name"]); ?></div>
                                        <div class="small text-muted"><?php echo h($assignment["academic_year"] ?? ""); ?></div>
                                        <div class="mt-2"><span class="badge badge-info"><?php echo (int)$studentCount; ?> Students</span></div>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary mt-2 js-view-assigned-students"
                                            data-assignment-id="<?php echo (int)$aid; ?>"
                                            data-assignment-title="<?php echo h($assignment["class_name"] . " - " . $assignment["subject_name"]); ?>"
                                        >
                                            View Students
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($classTeacherClasses as $ctClass): ?>
                            <?php
                                $ctClassId = (int)($ctClass["class_id"] ?? 0);
                                if ($ctClassId <= 0) {
                                    continue;
                                }
                                $ctKey = "ct_" . $ctClassId;
                                $ctStudentCount = count($classTeacherStudents[$ctClassId] ?? []);
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border-left-warning shadow-sm h-100">
                                    <div class="card-body py-3">
                                        <div class="text-xs text-uppercase text-warning mb-1">Class Teacher</div>
                                        <div class="font-weight-bold"><?php echo h($ctClass["class_name"] ?? ""); ?></div>
                                        <div class="small text-muted">Class Teacher Access</div>
                                        <div class="small text-muted"><?php echo h($ctClass["academic_year"] ?? ""); ?></div>
                                        <div class="mt-2"><span class="badge badge-info"><?php echo (int)$ctStudentCount; ?> Students</span></div>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-warning mt-2 js-view-assigned-students"
                                            data-assignment-id="<?php echo h($ctKey); ?>"
                                            data-assignment-title="<?php echo h(($ctClass["class_name"] ?? "") . " - Class Teacher"); ?>"
                                        >
                                            View Students
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">No class/subject is assigned to this teacher yet.</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="assigned-students-panel" class="card border-left-info mt-2 d-none">
                    <div class="card-body">
                        <h6 class="font-weight-bold text-info mb-3" id="assigned-students-title">Assigned Students</h6>
                        <div class="form-row mb-2">
                            <div class="col-md-6">
                                <input type="text" id="assigned-students-search" class="form-control form-control-sm" placeholder="Search by student ID or name">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Stream</th>
                                    </tr>
                                </thead>
                                <tbody id="assigned-students-body"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted" id="assigned-students-meta">0 students</small>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Student list pagination">
                                <button type="button" class="btn btn-outline-secondary" id="assigned-students-prev">Previous</button>
                                <button type="button" class="btn btn-outline-secondary" id="assigned-students-next">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const assignmentStudentsDetailMap = <?php echo json_encode($assignmentStudentsDetailMap); ?>;

    (function () {
        const buttons = document.querySelectorAll(".js-view-assigned-students");
        const panel = document.getElementById("assigned-students-panel");
        const title = document.getElementById("assigned-students-title");
        const tbody = document.getElementById("assigned-students-body");
        const searchInput = document.getElementById("assigned-students-search");
        const meta = document.getElementById("assigned-students-meta");
        const prevBtn = document.getElementById("assigned-students-prev");
        const nextBtn = document.getElementById("assigned-students-next");
        if (!buttons.length || !panel || !title || !tbody || !searchInput || !meta || !prevBtn || !nextBtn) {
            return;
        }

        const PAGE_SIZE = 10;
        let activeList = [];
        let filteredList = [];
        let currentPage = 1;

        function escapeHtml(value) {
            return String(value || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function renderRows(list) {
            tbody.innerHTML = "";
            if (!Array.isArray(list) || list.length === 0) {
                const tr = document.createElement("tr");
                tr.innerHTML = '<td colspan="3" class="text-center">No students found for this assignment.</td>';
                tbody.appendChild(tr);
                meta.textContent = "0 students";
                prevBtn.disabled = true;
                nextBtn.disabled = true;
                return;
            }

            const total = list.length;
            const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }
            if (currentPage < 1) {
                currentPage = 1;
            }

            const startIndex = (currentPage - 1) * PAGE_SIZE;
            const pageItems = list.slice(startIndex, startIndex + PAGE_SIZE);

            pageItems.forEach(function (row) {
                const tr = document.createElement("tr");
                const sid = escapeHtml(row.student_id);
                const name = escapeHtml(row.student_name);
                const stream = escapeHtml(row.stream);
                tr.innerHTML = "<td>" + sid + "</td><td>" + name + "</td><td>" + stream + "</td>";
                tbody.appendChild(tr);
            });

            meta.textContent = "Showing " + (startIndex + 1) + "-" + (startIndex + pageItems.length) + " of " + total + " students (Page " + currentPage + " of " + totalPages + ")";
            prevBtn.disabled = (currentPage <= 1);
            nextBtn.disabled = (currentPage >= totalPages);
        }

        function applyFilter(resetPage) {
            const query = searchInput.value.trim().toLowerCase();
            filteredList = activeList.filter(function (row) {
                const sid = String(row.student_id || "").toLowerCase();
                const name = String(row.student_name || "").toLowerCase();
                return sid.indexOf(query) !== -1 || name.indexOf(query) !== -1;
            });
            if (resetPage) {
                currentPage = 1;
            }
            renderRows(filteredList);
        }

        buttons.forEach(function (btn) {
            btn.addEventListener("click", function () {
                const aid = btn.getAttribute("data-assignment-id");
                const assignmentTitle = btn.getAttribute("data-assignment-title") || "Assigned Students";
                const list = assignmentStudentsDetailMap[aid] || [];
                title.textContent = "Students: " + assignmentTitle;
                activeList = Array.isArray(list) ? list : [];
                searchInput.value = "";
                applyFilter(true);
                panel.classList.remove("d-none");
                panel.scrollIntoView({ behavior: "smooth", block: "start" });
            });
        });

        searchInput.addEventListener("input", function () {
            applyFilter(true);
        });

        prevBtn.addEventListener("click", function () {
            currentPage -= 1;
            renderRows(filteredList);
        });

        nextBtn.addEventListener("click", function () {
            currentPage += 1;
            renderRows(filteredList);
        });
    })();

    (function () {
        const cards = document.querySelectorAll(".function-card[data-section]");
        const validSections = ["attendance", "lesson-plan", "leave-request", "assigned-classes"];

        function applySectionFromHash() {
            const current = (window.location.hash || "").replace("#", "");
            const active = validSections.includes(current) ? current : "dashboard";

            cards.forEach(function (card) {
                const section = card.getAttribute("data-section");
                if (active === "dashboard") {
                    card.style.display = (section === "attendance" || section === "lesson-plan" || section === "leave-request") ? "none" : "";
                } else {
                    card.style.display = (section === active) ? "" : "none";
                }
            });
        }

        window.addEventListener("hashchange", applySectionFromHash);
        applySectionFromHash();
    })();

    (function () {
        const collapse = document.getElementById("principalNoticesCollapse");
        const icon = document.getElementById("principalNoticesChevron");
        if (!collapse || !icon) {
            return;
        }

        collapse.addEventListener("show.bs.collapse", function () {
            icon.classList.remove("fa-chevron-down");
            icon.classList.add("fa-chevron-up");
        });
        collapse.addEventListener("hide.bs.collapse", function () {
            icon.classList.remove("fa-chevron-up");
            icon.classList.add("fa-chevron-down");
        });
    })();
</script>
<?php $noticesPopupApiPath = '../scripts/notices_api.php'; include __DIR__ . '/../scripts/notices_popup_snippet.php'; ?>
<?php
include "./partials/footer.php";
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
ob_end_flush();
?>
