<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('teacher_workspace', 'view', '../index.php');
include "../db.php"; 
require_once __DIR__ . '/../scripts/stream_subject_lib.php';
include "../students/partials/topbar.php";

function classIsGradeTwoToEight(string $className): bool
{
    $name = strtolower(trim($className));
    if ($name === '') {
        return false;
    }

    if (preg_match('/\b(\d{1,2})(st|nd|rd|th)?\b/', $name, $m)) {
        $num = (int)$m[1];
        return $num >= 2 && $num <= 8;
    }

    $wordToNum = [
        'two' => 2,
        'three' => 3,
        'four' => 4,
        'five' => 5,
        'six' => 6,
        'seven' => 7,
        'eight' => 8,
        'second' => 2,
        'third' => 3,
        'fourth' => 4,
        'fifth' => 5,
        'sixth' => 6,
        'seventh' => 7,
        'eighth' => 8,
    ];
    foreach ($wordToNum as $word => $num) {
        if (strpos($name, $word) !== false) {
            return $num >= 2 && $num <= 8;
        }
    }

    return false;
}

// Backward-compatible results storage used by this page.
$conn->query("
    CREATE TABLE IF NOT EXISTS results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        student_id INT NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        result_date DATE NOT NULL,
        result_type INT NOT NULL,
        obtained_marks INT NOT NULL,
        total_marks INT NOT NULL,
        percentage DECIMAL(6,2) NOT NULL,
        grade VARCHAR(10) NOT NULL,
        is_absent TINYINT(1) NOT NULL DEFAULT 0,
        approval_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        approved_by VARCHAR(120) NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_teacher_class_subject_date_type (teacher_id, class_id, subject_id, result_date, result_type),
        KEY idx_student (student_id)
    )
");
// Ensure optional topic exists for showing specific test topic in result reports.
$checkTopicCol = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'results' AND column_name = 'test_topic'
");
if ($checkTopicCol) {
    $checkTopicCol->execute();
    $hasTopicCol = (int)($checkTopicCol->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $checkTopicCol->close();
    if (!$hasTopicCol) {
        $conn->query("ALTER TABLE results ADD COLUMN test_topic VARCHAR(180) NULL AFTER student_name");
    }
}
$conn->query("
    CREATE TABLE IF NOT EXISTS weekly_test_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        student_id INT NOT NULL,
        student_name VARCHAR(100) NOT NULL,
        topic VARCHAR(180) NOT NULL,
        test_date DATE NOT NULL,
        total_marks INT NOT NULL,
        obtained_marks INT NOT NULL,
        class_name VARCHAR(80) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_weekly_student_test (teacher_id, class_id, subject_id, student_id, test_date),
        KEY idx_weekly_class_date (class_id, test_date)
    )
");
$conn->query("
    CREATE TABLE IF NOT EXISTS result_lock_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        result_date DATE NOT NULL,
        result_type INT NOT NULL,
        is_locked TINYINT(1) NOT NULL DEFAULT 1,
        lock_note VARCHAR(255) NULL,
        locked_by VARCHAR(120) NULL,
        locked_at DATETIME NULL,
        unlocked_by VARCHAR(120) NULL,
        unlocked_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_result_lock_group (teacher_id, class_id, subject_id, result_date, result_type),
        KEY idx_result_lock_lookup (class_id, subject_id, result_date, result_type, is_locked)
    )
");
// Ensure absence flag exists for older tables.
$checkAbsentCol = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'results' AND column_name = 'is_absent'
");
if ($checkAbsentCol) {
    $checkAbsentCol->execute();
    $hasAbsentCol = (int)($checkAbsentCol->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $checkAbsentCol->close();
    if (!$hasAbsentCol) {
        $conn->query("ALTER TABLE results ADD COLUMN is_absent TINYINT(1) NOT NULL DEFAULT 0 AFTER grade");
    }
}
// Ensure approval columns exist for principal workflow.
$checkApprovalStatus = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'results' AND column_name = 'approval_status'
");
if ($checkApprovalStatus) {
    $checkApprovalStatus->execute();
    $hasCol = (int)($checkApprovalStatus->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $checkApprovalStatus->close();
    if (!$hasCol) {
        $conn->query("ALTER TABLE results ADD COLUMN approval_status VARCHAR(20) NOT NULL DEFAULT 'Pending' AFTER is_absent");
    }
}
$checkApprovedBy = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'results' AND column_name = 'approved_by'
");
if ($checkApprovedBy) {
    $checkApprovedBy->execute();
    $hasCol = (int)($checkApprovedBy->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $checkApprovedBy->close();
    if (!$hasCol) {
        $conn->query("ALTER TABLE results ADD COLUMN approved_by VARCHAR(120) NULL AFTER approval_status");
    }
}
$checkApprovedAt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'results' AND column_name = 'approved_at'
");
if ($checkApprovedAt) {
    $checkApprovedAt->execute();
    $hasCol = (int)($checkApprovedAt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $checkApprovedAt->close();
    if (!$hasCol) {
        $conn->query("ALTER TABLE results ADD COLUMN approved_at DATETIME NULL AFTER approved_by");
    }
}

// Ensure stream/group exists for distinguishing Computer vs Biology students.
$dbRes = $conn->query("SELECT DATABASE() AS dbname");
$dbName = $dbRes ? ($dbRes->fetch_assoc()['dbname'] ?? '') : '';
if ($dbName !== '') {
    $checkCol = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.columns
        WHERE table_schema = ? AND table_name = 'students' AND column_name = 'group_stream'
    ");
    if ($checkCol) {
        $checkCol->bind_param("s", $dbName);
        $checkCol->execute();
        $exists = (int)($checkCol->get_result()->fetch_assoc()['c'] ?? 0) > 0;
        $checkCol->close();
        if (!$exists) {
            $conn->query("ALTER TABLE students ADD COLUMN group_stream VARCHAR(30) NULL AFTER academic_year");
        }
    }
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

$groupOptions = [
    'general' => 'General',
    'pre_engineering' => 'Pre-Engineering',
    'pre_medical' => 'Pre-Medical',
    'ics' => 'ICS',
    // Legacy values
    'computer' => 'Computer',
    'biology' => 'Biology',
];
$selected_group_filter_raw = strtolower(trim((string)($_POST['group_filter'] ?? 'all')));
if ($selected_group_filter_raw !== 'all' && !array_key_exists($selected_group_filter_raw, $groupOptions)) {
    $selected_group_filter_raw = 'all';
}
$selected_group_filter = $selected_group_filter_raw === 'all'
    ? 'all'
    : stream_normalize_key($selected_group_filter_raw);

$upload_message = "";
$upload_success = false;

$stmt_teacher = $conn->prepare("SELECT name FROM teachers WHERE id = ?");
if ($stmt_teacher) {
    $stmt_teacher->bind_param("i", $teacher_id);
    $stmt_teacher->execute();
    $result_teacher = $stmt_teacher->get_result();
    if ($result_teacher->num_rows > 0) {
        $teacher_data = $result_teacher->fetch_assoc();
        $teacher_name = htmlspecialchars($teacher_data['name']);
    }
    $stmt_teacher->close();
} else {
    error_log("Failed to prepare teacher name statement: " . $conn->error);
}

$stmt_assignments = $conn->prepare("
    SELECT ta.id, ta.class_id, ta.subject_id, c.class, s.subject_name
    FROM teacher_assignments ta
    JOIN classes c ON ta.class_id = c.id
    JOIN subjects s ON ta.subject_id = s.id
    WHERE ta.teacher_id = ?
");

$all_students = []; 
$student_stream_by_id = [];
$teacher_assigned_classes = []; 
$assignmentLookup = [];
if ($stmt_assignments) {
    $stmt_assignments->bind_param("i", $teacher_id);
    $stmt_assignments->execute();
    $assignments_result = $stmt_assignments->get_result();

    if ($assignments_result->num_rows > 0) {
        while ($row = $assignments_result->fetch_assoc()) {
            $row['access_type'] = 'subject_teacher';
            $teacher_assigned_classes[] = $row;
            $classId = (int)$row['class_id'];
            $subjectId = (int)$row['subject_id'];
            $assignmentLookup[$classId . '_' . $subjectId] = true;
            $className = (string)($row['class'] ?? '');
            $subjectName = (string)($row['subject_name'] ?? '');
            $assignmentKey = $classId . '_' . $subjectId;
            $streamRule = stream_get_subject_rule($conn, $classId, $subjectId, $subjectName);

            $all_students[$assignmentKey] = [];

            $stmt_students = $conn->prepare("SELECT id, StudentId, student_name, class, group_stream FROM students WHERE class = ? ORDER BY group_stream ASC, student_name ASC");
            if ($stmt_students) {
                $stmt_students->bind_param("s", $className);
                $stmt_students->execute();
                $students_result = $stmt_students->get_result();

                while ($student_row = $students_result->fetch_assoc()) {
                    $streamRaw = (string)($student_row['group_stream'] ?? '');
                    if (!stream_student_allowed($streamRaw, $streamRule)) {
                        continue;
                    }

                    $all_students[$assignmentKey][] = [
                        'id' => $student_row['id'],
                        'student_id_val' => htmlspecialchars($student_row['StudentId']),
                        'name' => htmlspecialchars($student_row['student_name']),
                        'class_name' => htmlspecialchars($student_row['class']),
                        'group_stream' => htmlspecialchars((string)($student_row['group_stream'] ?? ''))
                    ];
                    $student_stream_by_id[(int)$student_row['id']] = stream_normalize_key($streamRaw);
                }
                $stmt_students->close();
            } else {
                error_log("Failed to prepare student fetching statement for class $className: " . $conn->error);
            }
        }
    }
} else {
    error_log("Failed to prepare assignments statement: " . $conn->error);
}

// Allow class teachers of Grade 2 to Grade 8 to enter marks for all class subjects.
$stmtClassTeacher = $conn->prepare("
    SELECT id, class
    FROM classes
    WHERE class_teacher_id = ?
");
if ($stmtClassTeacher) {
    $stmtClassTeacher->bind_param("i", $teacher_id);
    $stmtClassTeacher->execute();
    $classTeacherRes = $stmtClassTeacher->get_result();
    while ($ctClass = $classTeacherRes->fetch_assoc()) {
        $ctClassId = (int)$ctClass['id'];
        $ctClassName = (string)($ctClass['class'] ?? '');
        if (!classIsGradeTwoToEight($ctClassName)) {
            continue;
        }

        $stmtClassSubjects = $conn->prepare("
            SELECT cs.subject_id, s.subject_name
            FROM class_subjects cs
            JOIN subjects s ON s.id = cs.subject_id
            WHERE cs.class_id = ?
        ");
        if (!$stmtClassSubjects) {
            error_log("Failed to prepare class_subjects query for class teacher access: " . $conn->error);
            continue;
        }

        $stmtClassSubjects->bind_param("i", $ctClassId);
        $stmtClassSubjects->execute();
        $classSubjectsRes = $stmtClassSubjects->get_result();
        while ($ctSub = $classSubjectsRes->fetch_assoc()) {
            $ctSubjectId = (int)$ctSub['subject_id'];
            $ctSubjectName = (string)($ctSub['subject_name'] ?? '');
            $lookupKey = $ctClassId . '_' . $ctSubjectId;
            if (isset($assignmentLookup[$lookupKey])) {
                continue;
            }

            $extraAssignment = [
                'id' => null,
                'class_id' => $ctClassId,
                'subject_id' => $ctSubjectId,
                'class' => $ctClassName,
                'subject_name' => $ctSubjectName,
                'access_type' => 'class_teacher'
            ];
            $teacher_assigned_classes[] = $extraAssignment;
            $assignmentLookup[$lookupKey] = true;

            $streamRule = stream_get_subject_rule($conn, $ctClassId, $ctSubjectId, $ctSubjectName);
            $all_students[$lookupKey] = [];
            $stmtStudents = $conn->prepare("SELECT id, StudentId, student_name, class, group_stream FROM students WHERE class = ? ORDER BY group_stream ASC, student_name ASC");
            if ($stmtStudents) {
                $stmtStudents->bind_param("s", $ctClassName);
                $stmtStudents->execute();
                $studentsRes = $stmtStudents->get_result();
                while ($student_row = $studentsRes->fetch_assoc()) {
                    $streamRaw = (string)($student_row['group_stream'] ?? '');
                    if (!stream_student_allowed($streamRaw, $streamRule)) {
                        continue;
                    }
                    $all_students[$lookupKey][] = [
                        'id' => $student_row['id'],
                        'student_id_val' => htmlspecialchars($student_row['StudentId']),
                        'name' => htmlspecialchars($student_row['student_name']),
                        'class_name' => htmlspecialchars($student_row['class']),
                        'group_stream' => htmlspecialchars((string)($student_row['group_stream'] ?? ''))
                    ];
                    $student_stream_by_id[(int)$student_row['id']] = stream_normalize_key($streamRaw);
                }
                $stmtStudents->close();
            }
        }
        $stmtClassSubjects->close();
    }
    $stmtClassTeacher->close();
}

function calculateGrade($percentage) {
    if ($percentage >= 90) return 'A+';
    else if ($percentage >= 80) return 'A';
    else if ($percentage >= 70) return 'B';
    else if ($percentage >= 60) return 'C';
    else if ($percentage >= 50) return 'D';
    else return 'Fail';
}

$result_type_map = [
    '0' => 'Weekly',
    '1' => 'Monthly',
    '2' => 'Mid Term',
    '3' => 'Annual'
];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['student_marks']) && is_array($_POST['student_marks'])) {
        auth_require_permission('results_reports', 'create', '../index.php');
    }
    $class_id = filter_var($_POST['class_id'], FILTER_VALIDATE_INT);
    $subject_id = filter_var($_POST['subject_id'], FILTER_VALIDATE_INT);
    $result_date = filter_var($_POST['result_date'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $test_topic = trim((string)($_POST['test_topic'] ?? ''));
    $result_type_numeric = filter_var($_POST['result_type'], FILTER_VALIDATE_INT);
    
    if ($result_type_numeric !== false && isset($result_type_map[$result_type_numeric]) && 
        $class_id !== false && $subject_id !== false && !empty($result_date)) {
        
        $is_assigned = false;
        foreach($teacher_assigned_classes as $assignment_row) {
            if ($assignment_row['class_id'] == $class_id && $assignment_row['subject_id'] == $subject_id) {
                $is_assigned = true;
                break;
            }
        }

        $selected_class_name = '';
        $selected_subject_name = '';
        foreach($teacher_assigned_classes as $assignment_row) {
            if ((int)$assignment_row['class_id'] === (int)$class_id && (int)$assignment_row['subject_id'] === (int)$subject_id) {
                $selected_class_name = (string)($assignment_row['class'] ?? '');
                $selected_subject_name = (string)($assignment_row['subject_name'] ?? '');
                break;
            }
        }
        $selected_group_filter_raw = strtolower(trim((string)($_POST['group_filter'] ?? 'all')));
        if ($selected_group_filter_raw !== 'all' && !array_key_exists($selected_group_filter_raw, $groupOptions)) {
            $selected_group_filter_raw = 'all';
        }
        $selected_group_filter = $selected_group_filter_raw === 'all'
            ? 'all'
            : stream_normalize_key($selected_group_filter_raw);

        if ($is_assigned && isset($_POST['student_marks']) && is_array($_POST['student_marks'])) {
            $isLocked = false;
            $resultGroupBefore = [
                'rows' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0
            ];
            $beforeStmt = $conn->prepare("
                SELECT
                    COUNT(*) AS total_rows,
                    SUM(CASE WHEN approval_status = 'Pending' THEN 1 ELSE 0 END) AS pending_rows,
                    SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved_rows,
                    SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_rows
                FROM results
                WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND result_date = ? AND result_type = ?
            ");
            if ($beforeStmt) {
                $beforeStmt->bind_param("iiisi", $teacher_id, $class_id, $subject_id, $result_date, $result_type_numeric);
                $beforeStmt->execute();
                $resultGroupBefore = $beforeStmt->get_result()->fetch_assoc() ?: $resultGroupBefore;
                $beforeStmt->close();
            }
            $stmt_lock = $conn->prepare("
                SELECT is_locked
                FROM result_lock_groups
                WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND result_date = ? AND result_type = ?
                LIMIT 1
            ");
            if ($stmt_lock) {
                $stmt_lock->bind_param("iiisi", $teacher_id, $class_id, $subject_id, $result_date, $result_type_numeric);
                $stmt_lock->execute();
                $lockRow = $stmt_lock->get_result()->fetch_assoc();
                $stmt_lock->close();
                $isLocked = ((int)($lockRow['is_locked'] ?? 0) === 1);
            }
            if ($isLocked) {
                $upload_message = "<div class='message error'>This result batch is locked by Principal. You cannot edit marks until it is unlocked.</div>";
                $upload_success = false;
                auth_audit_log_change(
                    $conn,
                    'submit_blocked',
                    'results',
                    $teacher_id . ':' . $class_id . ':' . $subject_id . ':' . $result_date . ':' . $result_type_numeric,
                    $resultGroupBefore,
                    ['blocked' => true, 'reason' => 'result_group_locked']
                );
            } else {
            $upload_success_flag = true;
            $rowsInserted = 0;
            $rowsUpdated = 0;
            $rowsProcessed = 0;

            foreach ($_POST['student_marks'] as $student_id_str => $marks_data) {
                $student_id = filter_var($student_id_str, FILTER_VALIDATE_INT);
                if ($student_id === false) {
                    continue;
                }
                if ($selected_group_filter !== 'all') {
                    $student_stream = stream_normalize_key((string)($student_stream_by_id[$student_id] ?? ''));
                    if ($student_stream !== $selected_group_filter) {
                        continue;
                    }
                }
                $rowsProcessed++;
                $is_absent = isset($marks_data['absent']) ? 1 : 0;
                $obtained_marks = filter_var($marks_data['obtained'] ?? null, FILTER_VALIDATE_INT);
                $total_marks = filter_var($marks_data['total'] ?? null, FILTER_VALIDATE_INT);

                $student_name = '';
                $stmt_get_student_name = $conn->prepare("SELECT student_name FROM students WHERE id = ?");
                if ($stmt_get_student_name) {
                    $stmt_get_student_name->bind_param("i", $student_id);
                    $stmt_get_student_name->execute();
                    $res_student_name = $stmt_get_student_name->get_result();
                    if ($res_student_name->num_rows > 0) {
                        $student_name = $res_student_name->fetch_assoc()['student_name'];
                    }
                    $stmt_get_student_name->close();
                }

                if (!empty($student_name)) {
                    if ($is_absent === 1) {
                        $total_marks = ($total_marks !== false && $total_marks > 0) ? $total_marks : 100;
                        $obtained_marks = 0;
                        $percentage = 0.00;
                        $grade = 'Absent';
                    } else {
                        if (!($obtained_marks !== false && $obtained_marks >= 0 &&
                              $total_marks !== false && $total_marks > 0 && $obtained_marks <= $total_marks)) {
                            continue;
                        }
                        $percentage = ($obtained_marks / $total_marks) * 100;
                        $percentage = round($percentage, 2);
                        $grade = calculateGrade($percentage);
                    }

                    $stmt_check_existing = $conn->prepare("SELECT id FROM results WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND student_id = ? AND result_date = ? AND result_type = ?");
                    if ($stmt_check_existing) {
                        $stmt_check_existing->bind_param("iiiisi", $teacher_id, $class_id, $subject_id, $student_id, $result_date, $result_type_numeric);
                        $stmt_check_existing->execute();
                        $existing_result = $stmt_check_existing->get_result();
                        if ($existing_result->num_rows > 0) {
                            $stmt_update = $conn->prepare("UPDATE results SET student_name = ?, test_topic = ?, obtained_marks = ?, total_marks = ?, percentage = ?, grade = ?, is_absent = ?, approval_status = 'Pending', approved_by = NULL, approved_at = NULL WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND student_id = ? AND result_date = ? AND result_type = ?");
                            if ($stmt_update) {
                                $stmt_update->bind_param("ssiidsiiiiisi", $student_name, $test_topic, $obtained_marks, $total_marks, $percentage, $grade, $is_absent, $teacher_id, $class_id, $subject_id, $student_id, $result_date, $result_type_numeric);
                                if (!$stmt_update->execute()) {
                                    $upload_success_flag = false;
                                    error_log("Error updating result: " . $stmt_update->error);
                                } else {
                                    if ((int)$stmt_update->affected_rows > 0) {
                                        $rowsUpdated++;
                                    }
                                }
                                $stmt_update->close();
                            } else {
                                $upload_success_flag = false;
                                error_log("Failed to prepare UPDATE statement: " . $conn->error);
                            }
                        } else {
                            $stmt_insert = $conn->prepare("INSERT INTO results (teacher_id, class_id, subject_id, student_id, student_name, test_topic, result_date, result_type, obtained_marks, total_marks, percentage, grade, is_absent, approval_status, approved_by, approved_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NULL, NULL)");
                            if ($stmt_insert) {
                                $stmt_insert->bind_param("iiiisssiiidsi", $teacher_id, $class_id, $subject_id, $student_id, $student_name, $test_topic, $result_date, $result_type_numeric, $obtained_marks, $total_marks, $percentage, $grade, $is_absent);
                                if (!$stmt_insert->execute()) {
                                    $upload_success_flag = false;
                                    error_log("Error inserting result: " . $stmt_insert->error);
                                } else {
                                    if ((int)$stmt_insert->affected_rows > 0) {
                                        $rowsInserted++;
                                    }
                                }
                                $stmt_insert->close();
                            } else {
                                $upload_success_flag = false;
                                error_log("Failed to prepare INSERT statement: " . $conn->error);
                            }
                        }
                        $stmt_check_existing->close();

                        if ((int)$result_type_numeric === 0) {
                            $weeklyTopic = $test_topic !== '' ? $test_topic : ($selected_subject_name !== '' ? $selected_subject_name : 'Weekly Test');

                            $stmt_check_weekly = $conn->prepare("
                                SELECT id
                                FROM weekly_test_results
                                WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND student_id = ? AND test_date = ?
                                LIMIT 1
                            ");
                            if ($stmt_check_weekly) {
                                $stmt_check_weekly->bind_param("iiiis", $teacher_id, $class_id, $subject_id, $student_id, $result_date);
                                $stmt_check_weekly->execute();
                                $existing_weekly = $stmt_check_weekly->get_result()->fetch_assoc();
                                $stmt_check_weekly->close();

                                if ($existing_weekly) {
                                    $weekly_id = (int)$existing_weekly['id'];
                                    $stmt_update_weekly = $conn->prepare("
                                        UPDATE weekly_test_results
                                        SET student_name = ?, topic = ?, total_marks = ?, obtained_marks = ?, class_name = ?
                                        WHERE id = ?
                                    ");
                                    if ($stmt_update_weekly) {
                                        $stmt_update_weekly->bind_param("ssiisi", $student_name, $weeklyTopic, $total_marks, $obtained_marks, $selected_class_name, $weekly_id);
                                        if (!$stmt_update_weekly->execute()) {
                                            $upload_success_flag = false;
                                            error_log("Error updating weekly_test_results: " . $stmt_update_weekly->error);
                                        }
                                        $stmt_update_weekly->close();
                                    }
                                } else {
                                    $stmt_insert_weekly = $conn->prepare("
                                        INSERT INTO weekly_test_results
                                        (teacher_id, class_id, subject_id, student_id, student_name, topic, test_date, total_marks, obtained_marks, class_name)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                    ");
                                    if ($stmt_insert_weekly) {
                                        $stmt_insert_weekly->bind_param("iiiisssiis", $teacher_id, $class_id, $subject_id, $student_id, $student_name, $weeklyTopic, $result_date, $total_marks, $obtained_marks, $selected_class_name);
                                        if (!$stmt_insert_weekly->execute()) {
                                            $upload_success_flag = false;
                                            error_log("Error inserting weekly_test_results: " . $stmt_insert_weekly->error);
                                        }
                                        $stmt_insert_weekly->close();
                                    }
                                }
                            }
                        }
                    } else {
                        $upload_success_flag = false;
                        error_log("Failed to prepare check existing statement: " . $conn->error);
                    }
                }
            }
            if ($upload_success_flag) {
                $upload_message = "<div class='message success'>Records are submitted successfully.</div>";
                $upload_success = true;
                $resultGroupAfter = $resultGroupBefore;
                $afterStmt = $conn->prepare("
                    SELECT
                        COUNT(*) AS total_rows,
                        SUM(CASE WHEN approval_status = 'Pending' THEN 1 ELSE 0 END) AS pending_rows,
                        SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) AS approved_rows,
                        SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_rows
                    FROM results
                    WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND result_date = ? AND result_type = ?
                ");
                if ($afterStmt) {
                    $afterStmt->bind_param("iiisi", $teacher_id, $class_id, $subject_id, $result_date, $result_type_numeric);
                    $afterStmt->execute();
                    $resultGroupAfter = $afterStmt->get_result()->fetch_assoc() ?: $resultGroupAfter;
                    $afterStmt->close();
                }
                auth_audit_log_change(
                    $conn,
                    'submit',
                    'results',
                    $teacher_id . ':' . $class_id . ':' . $subject_id . ':' . $result_date . ':' . $result_type_numeric,
                    [
                        'class_id' => $class_id,
                        'subject_id' => $subject_id,
                        'date' => $result_date,
                        'result_type' => $result_type_numeric,
                        'group_filter' => $selected_group_filter,
                        'summary' => $resultGroupBefore,
                    ],
                    [
                        'class_id' => $class_id,
                        'subject_id' => $subject_id,
                        'date' => $result_date,
                        'result_type' => $result_type_numeric,
                        'group_filter' => $selected_group_filter,
                        'rows_processed' => $rowsProcessed,
                        'rows_inserted' => $rowsInserted,
                        'rows_updated' => $rowsUpdated,
                        'summary' => $resultGroupAfter,
                    ]
                );
            } else {
                $upload_message = "<div class='message error'>An error occurred while submitting some records. Please check the logs.</div>";
            }
            }
        }
    }
}

$uploaded_marks_data = []; 
if (isset($_POST['class_id']) && isset($_POST['subject_id']) && isset($_POST['result_date']) && isset($_POST['result_type'])) {
    $selected_class_id_for_display = filter_var($_POST['class_id'], FILTER_VALIDATE_INT);
    $selected_subject_id_for_display = filter_var($_POST['subject_id'], FILTER_VALIDATE_INT);
    $selected_result_date = filter_var($_POST['result_date'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $selected_result_type_for_query = filter_var($_POST['result_type'], FILTER_VALIDATE_INT);

    if ($selected_class_id_for_display !== false && $selected_subject_id_for_display !== false &&
        !empty($selected_result_date) && $selected_result_type_for_query !== false) {

        $stmt_uploaded_marks = $conn->prepare("SELECT student_id, obtained_marks, total_marks, is_absent FROM results WHERE teacher_id = ? AND class_id = ? AND subject_id = ? AND result_date = ? AND result_type = ?");
        if ($stmt_uploaded_marks) {
            $stmt_uploaded_marks->bind_param("iiiis", $teacher_id, $selected_class_id_for_display, $selected_subject_id_for_display, $selected_result_date, $selected_result_type_for_query);
            $stmt_uploaded_marks->execute();
            $result_uploaded_marks = $stmt_uploaded_marks->get_result();
            while ($row = $result_uploaded_marks->fetch_assoc()) {
                $uploaded_marks_data[$row['student_id']] = [
                    'obtained_marks' => $row['obtained_marks'],
                    'total_marks' => $row['total_marks'],
                    'is_absent' => (int)($row['is_absent'] ?? 0)
                ];
            }
            $stmt_uploaded_marks->close();
        } else {
            error_log("Failed to prepare uploaded marks query: " . $conn->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Marks - Teacher Panel</title>
    <style>
        header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        header h1 {
            color: #007bff;
            font-size: 2.2em;
            margin: 0;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select {
            width: calc(100% - 22px);
            padding: 12px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }
        button[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            width: 100%;
            margin-top: 15px;
        }
        button[type="submit"]:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        footer {
            margin-top: 40px;
            text-align: center;
            font-size: 0.9em;
            color: #777;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        #student_selection_area {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        #student_table_container {
            max-height: 400px; 
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-top: 10px;
        }
        #student_list_table {
            width: 100%;
            border-collapse: collapse;
        }
        #student_list_table th, #student_list_table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }
        #student_list_table th {
            background-color: #e2e6ea;
            color: #333;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        #student_list_table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        #student_list_table tbody tr:hover {
            background-color: #cfe2ff;
        }
        #student_list_table input[type="number"],
        #student_list_table input[type="text"] {
            width: 80px; 
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9em;
            box-sizing: border-box;
            text-align: center;
        }
        #student_list_table span.calculated-value {
            display: block;
            padding: 8px;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            color: #495057;
            min-width: 50px;
        }
        .result-uploaded {
            background-color: #d1ecf1 !important;
            border-left: 5px solid #00a7d1;
        }
        .total-marks-control {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
        }
        .total-marks-control input {
            flex-grow: 1;
        }
        .total-marks-control button {
            width: auto;
            padding: 10px 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Enter Marks</h1>
        </header>

        <?php 
        if (!empty($upload_message)) {
            echo $upload_message;
        }
        ?>

        <form method="post">
            <div class="form-group">
                <label for="class_subject">Class & Subject:</label>
                <select name="class_id" id="class_subject" required>
                    <option value="">-- Select Class & Subject --</option>
                    <?php
                    if (!empty($teacher_assigned_classes)) {
                        foreach ($teacher_assigned_classes as $row) {
                            $selected = (isset($_POST['class_id']) && $_POST['class_id'] == $row['class_id'] &&
                                         isset($_POST['subject_id']) && $_POST['subject_id'] == $row['subject_id']) ? 'selected' : '';
                            $badge = (($row['access_type'] ?? '') === 'class_teacher') ? ' [Class Teacher Access]' : '';
                            echo "<option value='{$row['class_id']}' data-subject='{$row['subject_id']}' data-classname='" . htmlspecialchars($row['class'], ENT_QUOTES, 'UTF-8') . "' {$selected}>"
                               . htmlspecialchars("{$row['class']} - {$row['subject_name']}{$badge}") . "</option>";
                        }
                    } else {
                        echo "<option value=''>No assignments found.</option>";
                    }
                    ?>
                </select>
                <input type="hidden" name="subject_id" id="subject_id" value="<?php echo htmlspecialchars($_POST['subject_id'] ?? ''); ?>">
            </div>

            <div class="form-group" id="group_filter_block" style="display:none;">
                <label for="group_filter">Group (Class 9-12)</label>
                <select id="group_filter" name="group_filter" class="form-control">
                    <option value="all" <?php echo $selected_group_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <?php foreach ($groupOptions as $groupKey => $groupLabel): ?>
                        <option value="<?php echo htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_group_filter === stream_normalize_key((string)$groupKey) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="result_date">Date of Result:</label>
                <input type="date" id="result_date" name="result_date" required value="<?php echo htmlspecialchars($_POST['result_date'] ?? date('Y-m-d')); ?>">
            </div>

            <div class="form-group" id="test_topic_group">
                <label for="test_topic">Topic:</label>
                <input type="text" id="test_topic" name="test_topic" class="form-control" placeholder="e.g. Chapter 3 Algebra" value="<?php echo htmlspecialchars($_POST['test_topic'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="result_type">Test Type:</label>
                <select name="result_type" id="result_type" required>
                    <option value="">-- Select Test Type --</option>
                    <?php
                    foreach ($result_type_map as $numeric_value => $display_name) {
                        $selectedType = isset($_POST['result_type']) ? (string)$_POST['result_type'] : '0';
                        $selected = ((string)$numeric_value === $selectedType) ? 'selected' : '';
                        $label = ((string)$numeric_value === '0') ? 'Weekly Test' : $display_name;
                        echo "<option value=\"".htmlspecialchars($numeric_value)."\" {$selected}>".htmlspecialchars($label)."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Enter Marks for Students:</label>
                <div id="student_selection_area">
                    <p id="student_selection_placeholder">Please select a Class & Subject, Date, and Result Type to view students and enter marks.</p>
                    
                    <div id="total_marks_control" class="total-marks-control" style="display:none;">
                        <label for="total_marks_all">Total Marks:</label>
                        <input type="number" id="total_marks_all" min="1" value="100">
                        <button type="button" id="apply_total_marks_btn">Apply to All</button>
                    </div>

                    <div id="student_table_container">
                    </div>
                </div>
            </div>

            <button type="submit">Save All Marks</button>
        </form>
    </div>

    <script>
    const allStudentsData = <?php echo json_encode($all_students); ?>;
    const uploadedMarksData = <?php echo json_encode($uploaded_marks_data); ?>; 

    document.addEventListener('DOMContentLoaded', function() {
        const classSubjectSelect = document.querySelector("select[name='class_id']");
        const subjectIdInput = document.getElementById("subject_id");
        const resultDateInput = document.getElementById("result_date");
        const resultTypeSelect = document.getElementById("result_type");
        const testTopicGroup = document.getElementById("test_topic_group");
        const testTopicInput = document.getElementById("test_topic");
        const groupFilterBlock = document.getElementById("group_filter_block");
        const groupFilter = document.getElementById("group_filter");
        const studentTableContainer = document.getElementById("student_table_container");
        const studentSelectionPlaceholder = document.getElementById("student_selection_placeholder");
        const totalMarksControl = document.getElementById('total_marks_control');
        const totalMarksAllInput = document.getElementById('total_marks_all');
        const applyTotalMarksBtn = document.getElementById('apply_total_marks_btn');

        function calculateGrade(percentage) {
            if (percentage >= 90) return 'A+';
            else if (percentage >= 80) return 'A';
            else if (percentage >= 70) return 'B';
            else if (percentage >= 60) return 'C';
            else if (percentage >= 50) return 'D';
            else return 'Fail';
        }

        function updateStudentDisplayLogic() {
            const selectedOption = classSubjectSelect.options[classSubjectSelect.selectedIndex];
            const selectedClassId = selectedOption ? selectedOption.value : '';
            const selectedSubjectId = selectedOption ? selectedOption.getAttribute("data-subject") : '';
            const selectedClassName = selectedOption ? (selectedOption.getAttribute("data-classname") || '') : '';
            const selectedResultDate = resultDateInput.value;
            const selectedResultType = resultTypeSelect.value;
            const selectedGroup = groupFilter ? groupFilter.value : 'all';
            const isWeekly = (selectedResultType === '0');

            if (testTopicGroup) {
                testTopicGroup.style.display = isWeekly ? '' : 'none';
            }
            if (testTopicInput) {
                testTopicInput.required = isWeekly;
                if (!isWeekly) {
                    testTopicInput.value = '';
                }
            }

            subjectIdInput.value = selectedSubjectId;

            studentTableContainer.innerHTML = '';
            studentSelectionPlaceholder.style.display = 'block';
            totalMarksControl.style.display = 'none';

            if (!selectedClassId || !selectedSubjectId || !selectedResultDate || selectedResultType === '') {
                return;
            } else {
                studentSelectionPlaceholder.style.display = 'none';
            }

            const assignmentKey = selectedClassId + "_" + selectedSubjectId;
            const studentsInClassRaw = allStudentsData[assignmentKey] || [];
            const isSeniorClass = /\b(9|10|11|12)(th)?\b/i.test(selectedClassName);
            const hasAnyStreamInClass = (studentsInClassRaw || []).some(student => ((student.group_stream || '').trim() !== ''));
            const shouldShowGroupFilter = isSeniorClass || hasAnyStreamInClass;
            if (groupFilterBlock) {
                groupFilterBlock.style.display = shouldShowGroupFilter ? 'block' : 'none';
            }
            if (!shouldShowGroupFilter && groupFilter) {
                groupFilter.value = 'all';
            }

            const studentsInClass = (studentsInClassRaw || []).filter(student => {
                if (selectedGroup === 'all') return true;
                return ((student.group_stream || '').toLowerCase() === selectedGroup);
            });

            if (studentsInClass && studentsInClass.length > 0) {
                totalMarksControl.style.display = 'flex';
                let tableHtml = '<table id="student_list_table"><thead><tr><th>Student ID</th><th>Student Name</th><th>Stream</th><th>Absent</th><th>Obtained Marks</th><th>Total Marks</th><th>Percentage (%)</th><th>Grade</th></tr></thead><tbody>';
                studentsInClass.forEach(student => {
                    const studentId = parseInt(student.id);
                    const uploadedData = uploadedMarksData[studentId] || { obtained_marks: '', total_marks: '100', is_absent: 0 };
                    const isAbsent = parseInt(uploadedData.is_absent || 0, 10) === 1;

                    const isUploaded = uploadedMarksData.hasOwnProperty(studentId);
                    const rowClass = isUploaded ? 'result-uploaded' : '';

                    let initialPercentage = 0;
                    let initialGrade = 'N/A';

                    if (isAbsent) {
                        initialPercentage = '0.00';
                        initialGrade = 'Absent';
                    } else if (uploadedData.obtained_marks !== '' && uploadedData.total_marks > 0) {
                        initialPercentage = (uploadedData.obtained_marks / uploadedData.total_marks) * 100;
                        initialPercentage = initialPercentage.toFixed(2);
                        initialGrade = calculateGrade(parseFloat(initialPercentage));
                    }


                    tableHtml += `<tr data-student-id="${student.id}" class="${rowClass}">
                                    <td>${student.student_id_val}</td>
                                    <td>${student.name}</td>
                                    <td>${student.group_stream || 'N/A'}</td>
                                    <td>
                                        <input type="checkbox"
                                               name="student_marks[${student.id}][absent]"
                                               class="absent-checkbox"
                                               data-student-id="${student.id}"
                                               ${isAbsent ? 'checked' : ''}>
                                    </td>
                                    <td>
                                        <input type="text"
                                                name="student_marks[${student.id}][obtained]"
                                                class="obtained-marks-input"
                                                min="0"
                                                value="${uploadedData.obtained_marks}"
                                                data-student-id="${student.id}"
                                                ${isAbsent ? 'disabled' : ''}
                                                >
                                    </td>
                                    <td>
                                        <input type="number"
                                                name="student_marks[${student.id}][total]"
                                                class="total-marks-input"
                                                min="1"
                                                value="${uploadedData.total_marks}"
                                                data-student-id="${student.id}"
                                                ${isAbsent ? 'disabled' : ''}
                                                >
                                    </td>
                                    <td><span class="calculated-value percentage-display" id="percentage_${student.id}">${initialPercentage}%</span></td>
                                    <td><span class="calculated-value grade-display" id="grade_${student.id}">${initialGrade}</span></td>
                                </tr>`;
                });
                tableHtml += '</tbody></table>';
                studentTableContainer.innerHTML = tableHtml;

                const markInputs = studentTableContainer.querySelectorAll('.obtained-marks-input, .total-marks-input');
                const absentInputs = studentTableContainer.querySelectorAll('.absent-checkbox');
                markInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        const studentId = this.getAttribute('data-student-id');
                        const row = this.closest('tr'); 

                        const obtainedInput = row.querySelector('.obtained-marks-input');
                        const totalInput = row.querySelector('.total-marks-input');
                        const percentageSpan = row.querySelector('.percentage-display');
                        const gradeSpan = row.querySelector('.grade-display');

                        const obtained = parseInt(obtainedInput.value);
                        const total = parseInt(totalInput.value);

                        if (isNaN(total) || isNaN(obtained) || total <= 0 || obtained < 0 || obtained > total) {
                            percentageSpan.textContent = '0.00%';
                            gradeSpan.textContent = 'Invalid';
                            return;
                        }

                        const percentage = (obtained / total) * 100;
                        percentageSpan.textContent = percentage.toFixed(2) + '%';
                        gradeSpan.textContent = calculateGrade(percentage);
                    });
                });

                absentInputs.forEach(input => {
                    input.addEventListener('change', function () {
                        const row = this.closest('tr');
                        const obtainedInput = row.querySelector('.obtained-marks-input');
                        const totalInput = row.querySelector('.total-marks-input');
                        const percentageSpan = row.querySelector('.percentage-display');
                        const gradeSpan = row.querySelector('.grade-display');

                        if (this.checked) {
                            obtainedInput.value = '0';
                            if (!totalInput.value || parseInt(totalInput.value, 10) <= 0) {
                                totalInput.value = '100';
                            }
                            obtainedInput.disabled = true;
                            totalInput.disabled = true;
                            percentageSpan.textContent = '0.00%';
                            gradeSpan.textContent = 'Absent';
                        } else {
                            obtainedInput.disabled = false;
                            totalInput.disabled = false;
                            obtainedInput.dispatchEvent(new Event('input'));
                        }
                    });
                });
            } else {
                studentTableContainer.innerHTML = '<p>No students found for this class.</p>';
            }
        }

        // New function to apply total marks
        function applyTotalMarks() {
            const totalMarks = totalMarksAllInput.value;
            const totalMarksInputs = document.querySelectorAll('.total-marks-input');
            totalMarksInputs.forEach(input => {
                input.value = totalMarks;
                input.dispatchEvent(new Event('input')); // Trigger input event to recalculate percentage/grade
            });
        }
        
        classSubjectSelect.addEventListener("change", updateStudentDisplayLogic);
        resultDateInput.addEventListener("change", updateStudentDisplayLogic);
        resultTypeSelect.addEventListener("change", updateStudentDisplayLogic);
        if (groupFilter) {
            groupFilter.addEventListener("change", updateStudentDisplayLogic);
        }
        applyTotalMarksBtn.addEventListener("click", applyTotalMarks);

        if (classSubjectSelect.value !== '' && resultDateInput.value !== '' && resultTypeSelect.value !== '') {
            updateStudentDisplayLogic();
        }
    });
    </script>
    <?php
include "../students/partials/footer.php";
?>

<?php
if ($stmt_assignments) {
    $stmt_assignments->close();
}
if ($conn) {
    $conn->close();
}
?>
