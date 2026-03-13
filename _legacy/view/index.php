<?php
require_once __DIR__ . '/../auth.php';
auth_require_permission('results_reports', 'view', '../index.php');
include "../db.php";

// New PHP function to calculate GPA based on the provided logic
function calculateGPA($grade) {
    switch ($grade) {
        case 'A+': return 6.0;
        case 'A': return 5.0;
        case 'B': return 4.0;
        case 'C': return 3.0;
        case 'D': return 2.0;
        case 'E': return 1.0;
        case 'F': return 0.0;
        default: return 'N/A';
    }
}

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
    ");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $exists = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0) > 0;
    $stmt->close();
    return $exists;
}

function normalizeGroupKey(string $value): string
{
    $raw = strtolower(trim($value));
    if ($raw === '') {
        return '';
    }
    $flat = str_replace([' ', '-', '_'], '', $raw);
    if ($flat === 'premedical') {
        return 'pre_medical';
    }
    if ($flat === 'ics') {
        return 'ics';
    }
    if ($flat === 'general') {
        return 'general';
    }
    $normalized = preg_replace('/[^a-z0-9]+/', '_', $raw);
    return trim((string)$normalized, '_');
}

function groupLabelFromKey(string $key): string
{
    $map = [
        'general' => 'General',
        'ics' => 'ICS',
        'pre_medical' => 'Pre-Medical'
    ];
    if (isset($map[$key])) {
        return $map[$key];
    }
    return ucwords(str_replace('_', ' ', $key));
}

function groupComparableKey(string $value): string
{
    return str_replace('_', '', normalizeGroupKey($value));
}

include "../students/partials/topbar.php";

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

$results_message = "";
$results_data = null;

$request = array_merge($_GET, $_POST);
$filter_class_id = trim((string)($request['filter_class_id'] ?? ''));
$filter_subject_id = trim((string)($request['filter_subject_id'] ?? ''));
$filter_result_date = trim((string)($request['filter_result_date'] ?? ''));
$filter_result_type = trim((string)($request['filter_result_type'] ?? ''));
$filter_student_id = trim((string)($request['filter_student_id'] ?? ''));
$filter_group_stream = normalizeGroupKey((string)($request['filter_group_stream'] ?? ''));

$per_page_options = [10, 25, 50, 100];
$per_page = isset($request['per_page']) ? (int)$request['per_page'] : 25;
if (!in_array($per_page, $per_page_options, true)) {
    $per_page = 25;
}
$current_page = isset($request['page']) ? (int)$request['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$stmt_assignments = $conn->prepare("
    SELECT ta.id, ta.class_id, ta.subject_id, c.class, s.subject_name
    FROM teacher_assignments ta
    JOIN classes c ON ta.class_id = c.id
    JOIN subjects s ON ta.subject_id = s.id
    WHERE ta.teacher_id = ?
    ORDER BY c.class, s.subject_name
");
$stmt_assignments->bind_param("i", $teacher_id);
$stmt_assignments->execute();
$assignments_result = $stmt_assignments->get_result();
$assigned_combinations = [];
$assignedMap = [];
while ($row = $assignments_result->fetch_assoc()) {
    $row['can_upload'] = 1;
    $row['scope'] = 'subject_teacher';
    $assigned_combinations[] = $row;
    $assignedMap[(int)$row['class_id'] . '_' . (int)$row['subject_id']] = $row;
}
$stmt_assignments->close();

$classTeacherClassIds = [];
$stmtClassTeacher = $conn->prepare("SELECT id FROM classes WHERE class_teacher_id = ?");
if ($stmtClassTeacher) {
    $stmtClassTeacher->bind_param("i", $teacher_id);
    $stmtClassTeacher->execute();
    $classTeacherRes = $stmtClassTeacher->get_result();
    while ($ct = $classTeacherRes->fetch_assoc()) {
        $classTeacherClassIds[] = (int)$ct['id'];
    }
    $stmtClassTeacher->close();
}

if (!empty($classTeacherClassIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($classTeacherClassIds), '?'));
    $sqlClassTeacherSubjects = "
        SELECT cs.class_id, c.class, cs.subject_id, s.subject_name
        FROM class_subjects cs
        JOIN classes c ON c.id = cs.class_id
        JOIN subjects s ON s.id = cs.subject_id
        WHERE cs.class_id IN ($inPlaceholders)
        ORDER BY c.class, s.subject_name
    ";
    $stmtClassTeacherSubjects = $conn->prepare($sqlClassTeacherSubjects);
    if ($stmtClassTeacherSubjects) {
        $typesCt = str_repeat('i', count($classTeacherClassIds));
        $stmtClassTeacherSubjects->bind_param($typesCt, ...$classTeacherClassIds);
        $stmtClassTeacherSubjects->execute();
        $ctRes = $stmtClassTeacherSubjects->get_result();
        while ($row = $ctRes->fetch_assoc()) {
            $key = (int)$row['class_id'] . '_' . (int)$row['subject_id'];
            if (!isset($assignedMap[$key])) {
                $row['can_upload'] = 0;
                $row['scope'] = 'class_teacher_readonly';
                $assignedMap[$key] = $row;
            }
        }
        $stmtClassTeacherSubjects->close();
    }
}

$assigned_combinations = array_values($assignedMap);
$accessibleClassIds = [];
foreach ($assigned_combinations as $comboRow) {
    $accessibleClassIds[] = (int)($comboRow['class_id'] ?? 0);
}
foreach ($classTeacherClassIds as $ctClassId) {
    $accessibleClassIds[] = (int)$ctClassId;
}
$accessibleClassIds = array_values(array_filter(array_unique($accessibleClassIds)));
$class_filter_options = [];
foreach ($assigned_combinations as $comboRow) {
    $cid = (int)($comboRow['class_id'] ?? 0);
    $cname = trim((string)($comboRow['class'] ?? ''));
    if ($cid > 0 && $cname !== '' && !isset($class_filter_options[$cid])) {
        $class_filter_options[$cid] = $cname;
    }
}
asort($class_filter_options, SORT_NATURAL | SORT_FLAG_CASE);
$selectedComboCanUpload = true;
$hasSelectedCombo = ($filter_class_id !== '' && $filter_subject_id !== '');
if ($filter_class_id !== '' && $filter_subject_id !== '') {
    $selectedKey = (int)$filter_class_id . '_' . (int)$filter_subject_id;
    if (isset($assignedMap[$selectedKey])) {
        $selectedComboCanUpload = ((int)($assignedMap[$selectedKey]['can_upload'] ?? 0) === 1);
    }
}

$result_type_map = [
    '0' => 'Weekly',
    '1' => 'Monthly',
    '2' => 'Mid Term',
    '3' => 'Annual'
];

$hasResultsTopic = columnExists($conn, 'results', 'test_topic');
$hasStudentsSection = columnExists($conn, 'students', 'section');
$hasStudentsGroup = columnExists($conn, 'students', 'group_stream');
$hasStudentsYear = columnExists($conn, 'students', 'academic_year');
$hasClassesSection = columnExists($conn, 'classes', 'section');

$topicExpr = $hasResultsTopic ? "COALESCE(NULLIF(r.test_topic, ''), s.subject_name)" : "s.subject_name";
$sectionExprParts = [];
if ($hasStudentsSection) {
    $sectionExprParts[] = "NULLIF(st.section, '')";
}
if ($hasClassesSection) {
    $sectionExprParts[] = "NULLIF(c.section, '')";
}
$sectionExpr = !empty($sectionExprParts) ? ("COALESCE(" . implode(", ", $sectionExprParts) . ", '')") : "''";

$sessionExprParts = [];
if ($hasStudentsYear) {
    $sessionExprParts[] = "NULLIF(st.academic_year, '')";
}
$sessionExprParts[] = "NULLIF(c.academic_year, '')";
$sessionExpr = "COALESCE(" . implode(", ", $sessionExprParts) . ", '')";
$groupExpr = $hasStudentsGroup ? "COALESCE(NULLIF(st.group_stream, ''), '')" : "''";

$fromSql = "
    FROM results r
    LEFT JOIN classes c ON r.class_id = c.id
    LEFT JOIN subjects s ON r.subject_id = s.id
    LEFT JOIN students st ON st.id = r.student_id
    LEFT JOIN teachers t ON t.id = r.teacher_id
    WHERE (
        r.teacher_id = ?
";

$params = [$teacher_id];
$types = "i";
if (!empty($classTeacherClassIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($classTeacherClassIds), '?'));
    $fromSql .= " OR r.class_id IN ($inPlaceholders)";
    foreach ($classTeacherClassIds as $ctClassId) {
        $params[] = $ctClassId;
        $types .= "i";
    }
}
$fromSql .= ")";

if ($filter_class_id !== '' && ctype_digit($filter_class_id)) {
    $fromSql .= " AND r.class_id = ?";
    $params[] = (int)$filter_class_id;
    $types .= "i";
}
if ($filter_subject_id !== '' && ctype_digit($filter_subject_id)) {
    $fromSql .= " AND r.subject_id = ?";
    $params[] = (int)$filter_subject_id;
    $types .= "i";
}
if (!empty($filter_result_date)) {
    $fromSql .= " AND r.result_date = ?";
    $params[] = $filter_result_date;
    $types .= "s";
}
if ($filter_result_type !== '') {
    $fromSql .= " AND r.result_type = ?";
    $params[] = (int)$filter_result_type;
    $types .= "i";
}
if ($filter_student_id !== '' && ctype_digit((string)$filter_student_id)) {
    $fromSql .= " AND r.student_id = ?";
    $params[] = (int)$filter_student_id;
    $types .= "i";
}
if ($filter_group_stream !== '' && $hasStudentsGroup) {
    $fromSql .= " AND LOWER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(st.group_stream, '')), '-', ''), '_', ''), ' ', '')) = ?";
    $params[] = groupComparableKey($filter_group_stream);
    $types .= "s";
}

$orderBySql = " ORDER BY r.result_date DESC, c.class, topic, student_name";
$results_total_rows = 0;
$total_pages = 1;
$show_from = 0;
$show_to = 0;

$countSql = "SELECT COUNT(*) AS total_rows " . $fromSql;
$stmtCount = $conn->prepare($countSql);
if ($stmtCount === false) {
    error_log("Count prepare failed: " . $conn->error);
    $results_message = "An unexpected error occurred while counting data. (Error Code: " . $conn->errno . ")";
} else {
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    if (!$stmtCount->execute()) {
        error_log("Count execute failed: " . $stmtCount->error);
        $results_message = "An unexpected error occurred while counting data. (Error Code: " . $stmtCount->errno . ")";
    } else {
        $countRow = $stmtCount->get_result()->fetch_assoc();
        $results_total_rows = (int)($countRow['total_rows'] ?? 0);
    }
    $stmtCount->close();
}

if ($results_total_rows > 0) {
    $total_pages = (int)ceil($results_total_rows / $per_page);
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }
} else {
    $current_page = 1;
}
$offset = ($current_page - 1) * $per_page;

$sql = "
    SELECT
        r.student_id,
        COALESCE(NULLIF(st.student_name, ''), r.student_name) AS student_name,
        r.obtained_marks,
        r.total_marks,
        c.class AS class_name,
        COALESCE(NULLIF(s.subject_name, ''), '-') AS subject_name,
        {$topicExpr} AS topic,
        r.result_date,
        r.result_type,
        {$sectionExpr} AS class_section,
        {$sessionExpr} AS session_year,
        {$groupExpr} AS group_stream,
        COALESCE(t.name, CONCAT('Teacher #', r.teacher_id)) AS entered_by
" . $fromSql . $orderBySql . " LIMIT ? OFFSET ?";

$stmt_results = $conn->prepare($sql);
if ($stmt_results === false) {
    error_log("Data prepare failed: " . $conn->error);
    $results_message = "An unexpected error occurred while fetching data. (Error Code: " . $conn->errno . ")";
} else {
    $dataParams = $params;
    $dataParams[] = $per_page;
    $dataParams[] = $offset;
    $dataTypes = $types . "ii";
    $stmt_results->bind_param($dataTypes, ...$dataParams);

    if (!$stmt_results->execute()) {
        error_log("Data execute failed: " . $stmt_results->error);
        $results_message = "An unexpected error occurred during data retrieval. (Error Code: " . $stmt_results->errno . ")";
    } else {
        $results_data = $stmt_results->get_result();
        if ($results_total_rows > 0) {
            $show_from = $offset + 1;
            $show_to = min($offset + $results_data->num_rows, $results_total_rows);
        }
    }
    $stmt_results->close();
}

$student_filter_options = [];
$studentSql = "
    SELECT DISTINCT
        r.student_id,
        COALESCE(NULLIF(st.student_name, ''), r.student_name) AS student_name
    FROM results r
    LEFT JOIN students st ON st.id = r.student_id
    WHERE (
        r.teacher_id = ?
";
$studentParams = [$teacher_id];
$studentTypes = "i";
if (!empty($classTeacherClassIds)) {
    $studentInPlaceholders = implode(',', array_fill(0, count($classTeacherClassIds), '?'));
    $studentSql .= " OR r.class_id IN ($studentInPlaceholders)";
    foreach ($classTeacherClassIds as $ctClassId) {
        $studentParams[] = $ctClassId;
        $studentTypes .= "i";
    }
}
$studentSql .= ")";
if ($filter_class_id !== '' && ctype_digit($filter_class_id)) {
    $studentSql .= " AND r.class_id = ?";
    $studentParams[] = (int)$filter_class_id;
    $studentTypes .= "i";
}
if ($filter_subject_id !== '' && ctype_digit($filter_subject_id)) {
    $studentSql .= " AND r.subject_id = ?";
    $studentParams[] = (int)$filter_subject_id;
    $studentTypes .= "i";
}
if ($filter_group_stream !== '' && $hasStudentsGroup) {
    $studentSql .= " AND LOWER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(st.group_stream, '')), '-', ''), '_', ''), ' ', '')) = ?";
    $studentParams[] = groupComparableKey($filter_group_stream);
    $studentTypes .= "s";
}
$studentSql .= " ORDER BY student_name ASC";

$stmtStudents = $conn->prepare($studentSql);
if ($stmtStudents) {
    $stmtStudents->bind_param($studentTypes, ...$studentParams);
    if ($stmtStudents->execute()) {
        $studentsResult = $stmtStudents->get_result();
        while ($sr = $studentsResult->fetch_assoc()) {
            $student_filter_options[] = $sr;
        }
    }
    $stmtStudents->close();
}

$group_filter_options = [];
if ($hasStudentsGroup) {
    $classNameById = [];
    $lookupClassIds = $accessibleClassIds;
    if ($filter_class_id !== '' && ctype_digit($filter_class_id)) {
        $lookupClassIds[] = (int)$filter_class_id;
    }
    $lookupClassIds = array_values(array_filter(array_unique($lookupClassIds)));

    if (!empty($lookupClassIds)) {
        $classPlaceholders = implode(',', array_fill(0, count($lookupClassIds), '?'));
        $classLookupSql = "SELECT id, class FROM classes WHERE id IN ($classPlaceholders)";
        $stmtClassNames = $conn->prepare($classLookupSql);
        if ($stmtClassNames) {
            $classTypes = str_repeat('i', count($lookupClassIds));
            $stmtClassNames->bind_param($classTypes, ...$lookupClassIds);
            if ($stmtClassNames->execute()) {
                $classRes = $stmtClassNames->get_result();
                while ($cr = $classRes->fetch_assoc()) {
                    $classNameById[(int)$cr['id']] = trim((string)$cr['class']);
                }
            }
            $stmtClassNames->close();
        }
    }

    $groupSql = "SELECT DISTINCT TRIM(group_stream) AS group_stream FROM students WHERE NULLIF(TRIM(group_stream), '') IS NOT NULL";
    $groupParams = [];
    $groupTypes = '';

    if ($filter_class_id !== '' && ctype_digit($filter_class_id) && isset($classNameById[(int)$filter_class_id])) {
        $groupSql .= " AND class = ?";
        $groupParams[] = $classNameById[(int)$filter_class_id];
        $groupTypes .= 's';
    } else {
        $allowedClassNames = array_values(array_filter(array_unique(array_values($classNameById))));
        if (!empty($allowedClassNames)) {
            $classNamePlaceholders = implode(',', array_fill(0, count($allowedClassNames), '?'));
            $groupSql .= " AND class IN ($classNamePlaceholders)";
            foreach ($allowedClassNames as $className) {
                $groupParams[] = $className;
                $groupTypes .= 's';
            }
        }
    }

    $groupSql .= " ORDER BY group_stream ASC";
    $stmtGroups = $conn->prepare($groupSql);
    if ($stmtGroups) {
        if ($groupTypes !== '') {
            $stmtGroups->bind_param($groupTypes, ...$groupParams);
        }
        if ($stmtGroups->execute()) {
            $groupResult = $stmtGroups->get_result();
            while ($gr = $groupResult->fetch_assoc()) {
                $key = normalizeGroupKey((string)($gr['group_stream'] ?? ''));
                if ($key !== '' && !isset($group_filter_options[$key])) {
                    $group_filter_options[$key] = groupLabelFromKey($key);
                }
            }
        }
        $stmtGroups->close();
    }
}
if ($filter_group_stream !== '' && !isset($group_filter_options[$filter_group_stream])) {
    $group_filter_options[$filter_group_stream] = groupLabelFromKey($filter_group_stream);
}
if (!empty($group_filter_options)) {
    asort($group_filter_options, SORT_NATURAL | SORT_FLAG_CASE);
}

$export_params = http_build_query([
    'class_id' => $filter_class_id,
    'subject_id' => $filter_subject_id,
    'result_date' => $filter_result_date,
    'result_type' => $filter_result_type,
    'student_id' => $filter_student_id,
    'group_stream' => $filter_group_stream
]);
$export_link = "export_results.php?" . $export_params;
$pagination_base_params = [
    'filter_class_id' => $filter_class_id,
    'filter_subject_id' => $filter_subject_id,
    'filter_result_date' => $filter_result_date,
    'filter_result_type' => $filter_result_type,
    'filter_student_id' => $filter_student_id,
    'filter_group_stream' => $filter_group_stream,
    'per_page' => $per_page
];
$pagination_base_params = array_filter($pagination_base_params, static function ($value) {
    return $value !== '' && $value !== null;
});
$build_page_url = static function ($page) use ($pagination_base_params) {
    $params = $pagination_base_params;
    $params['page'] = max(1, (int)$page);
    return 'index.php?' . http_build_query($params);
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results - Teacher Panel</title>
    <style>
        .export-button-container {
            text-align: right;
            margin-bottom: 20px;
        }
        .export-button {
            background-color: #008CBA;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .export-button:hover {
            background-color: #005f6b;
        }
        .export-button + .export-button {
            margin-left: 8px;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
            font-size: 2.5em;
        }
        .filter-form {
            background-color: #f0f8ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: space-between;
            align-items: flex-end;
        }
        .filter-form .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 0;
        }
        .filter-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 0.9em;
        }
        .filter-form select,
        .filter-form input[type="date"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.9em;
            box-sizing: border-box;
        }
        .filter-form button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            align-self: flex-end;
        }
        .filter-form button:hover {
            background-color: #0056b3;
        }
        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            min-width: 190px;
        }
        .clear-button {
            display: inline-block;
            background-color: #6c757d;
            color: #fff;
            padding: 10px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
            line-height: 1;
            transition: background-color 0.3s ease;
        }
        .clear-button:hover {
            background-color: #545b62;
            color: #fff;
        }
        .access-legend {
            margin: 10px 0 18px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .live-search-wrap {
            margin: 0 0 14px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }
        .live-search-input {
            width: 100%;
            max-width: 440px;
            padding: 9px 12px;
            border: 1px solid #c7d2e0;
            border-radius: 6px;
            font-size: 0.95em;
        }
        .live-search-meta {
            font-size: 0.88em;
            color: #666;
        }
        .access-pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.82em;
            font-weight: 700;
            border: 1px solid transparent;
        }
        .access-pill.editable {
            background: #e8f8ee;
            color: #1f6f37;
            border-color: #b7e4c7;
        }
        .access-pill.readonly {
            background: #fff3cd;
            color: #7a5d00;
            border-color: #ffe69c;
        }
        .access-status {
            margin-bottom: 12px;
            font-size: 0.95em;
            color: #333;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fdfdfd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        table th {
            background-color: #e0e0e0;
            color: #444;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        table tr:nth-child(even) {
            background-color: #f6f6f6;
        }
        table tr:hover {
            background-color: #eef;
        }
        .pagination-meta {
            margin-top: 10px;
            font-size: 0.92em;
            color: #555;
            text-align: right;
        }
        .pagination {
            margin-top: 14px;
            display: flex;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span {
            display: inline-block;
            min-width: 34px;
            padding: 7px 10px;
            border: 1px solid #ccd6e0;
            border-radius: 4px;
            text-align: center;
            text-decoration: none;
            color: #1f3b57;
            background: #fff;
            font-size: 0.9em;
        }
        .pagination a:hover {
            background: #eef4ff;
            border-color: #9fb7d8;
        }
        .pagination .active {
            background: #007bff;
            border-color: #007bff;
            color: #fff;
            font-weight: 700;
        }
        .pagination .ellipsis {
            border: none;
            background: transparent;
            color: #6b7280;
            min-width: auto;
            padding-left: 2px;
            padding-right: 2px;
        }
        .no-results-message {
            text-align: center;
            padding: 30px;
            background-color: #f0f0f0;
            border-radius: 8px;
            color: #555;
            font-style: italic;
        }
        footer {
            margin-top: 40px;
            text-align: center;
            font-size: 0.9em;
            color: #777;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Test Results</h1>
        </header>

        <form method="get" class="filter-form">
            <div class="form-group">
                <label for="filter_class_subject">Class & Subject:</label>
                <select name="filter_class_id" id="filter_class_subject">
                    <option value="">All Classes & Subjects</option>
                    <?php foreach ($class_filter_options as $classId => $className): ?>
                        <option value="<?php echo (int)$classId; ?>" data-subject="" <?php echo ((string)$filter_class_id === (string)$classId && $filter_subject_id === '') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($className . ' - All Subjects'); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php
                    foreach ($assigned_combinations as $combo) {
                        $selected = '';
                        if ($filter_class_id == $combo['class_id'] && $filter_subject_id == $combo['subject_id']) {
                            $selected = 'selected';
                        }
                        $accessTag = ((int)($combo['can_upload'] ?? 0) === 0) ? ' [Read Only]' : ' [Editable]';
                        echo "<option value='{$combo['class_id']}' data-subject='{$combo['subject_id']}' {$selected}>"
                            . htmlspecialchars("{$combo['class']} - {$combo['subject_name']}{$accessTag}") . "</option>";
                    }
                    ?>
                </select>
                <input type="hidden" name="filter_subject_id" id="hidden_filter_subject_id" value="<?php echo htmlspecialchars($filter_subject_id); ?>">
            </div>

            <div class="form-group">
                <label for="filter_result_date">Date:</label>
                <input type="date" name="filter_result_date" id="filter_result_date" value="<?php echo htmlspecialchars($filter_result_date); ?>">
            </div>

            <div class="form-group">
                <label for="filter_result_type">Result Type:</label>
                <select name="filter_result_type" id="filter_result_type">
                    <option value="">All Result Types</option>
                    <?php
                    foreach ($result_type_map as $numeric_value => $display_name) {
                        $selected = ($filter_result_type !== '' && (int)$filter_result_type === (int)$numeric_value) ? 'selected' : '';
                        echo "<option value=\"".htmlspecialchars($numeric_value)."\" {$selected}>".htmlspecialchars($display_name)."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filter_student_id">Individual Student:</label>
                <select name="filter_student_id" id="filter_student_id">
                    <option value="">All Students</option>
                    <?php foreach ($student_filter_options as $opt): ?>
                        <option value="<?php echo (int)$opt['student_id']; ?>" <?php echo ((string)$filter_student_id === (string)$opt['student_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($opt['student_name'] . ' (ID: ' . $opt['student_id'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filter_group_stream">Group:</label>
                <select name="filter_group_stream" id="filter_group_stream">
                    <option value="">All Groups</option>
                    <?php foreach ($group_filter_options as $groupKey => $groupLabel): ?>
                        <option value="<?php echo htmlspecialchars($groupKey); ?>" <?php echo ($filter_group_stream === $groupKey) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($groupLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="per_page">Rows Per Page:</label>
                <select name="per_page" id="per_page">
                    <?php foreach ($per_page_options as $optPerPage): ?>
                        <option value="<?php echo (int)$optPerPage; ?>" <?php echo ($per_page === (int)$optPerPage) ? 'selected' : ''; ?>>
                            <?php echo (int)$optPerPage; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="page" value="1">
            <div class="form-group filter-actions">
                <button type="submit">Apply Filters</button>
                <a href="index.php" class="clear-button">Clear</a>
            </div>
        </form>
        <div class="access-legend">
            <span class="access-pill editable">Editable = Subject Teacher Access</span>
            <span class="access-pill readonly">Read Only = Class Teacher View</span>
        </div>
        <?php if ($hasSelectedCombo): ?>
            <div class="access-status">
                Selected Access:
                <span class="access-pill <?php echo $selectedComboCanUpload ? 'editable' : 'readonly'; ?>">
                    <?php echo $selectedComboCanUpload ? 'Editable' : 'Read Only'; ?>
                </span>
            </div>
        <?php endif; ?>
        <?php if ($hasSelectedCombo && !$selectedComboCanUpload): ?>
            <div class="alert alert-info">
                You are viewing this class-subject as <strong>Class Teacher (Read Only)</strong>. Marks edit/upload is restricted to assigned subject teacher.
            </div>
        <?php endif; ?>

        <div class="export-button-container">
            <a href="<?php echo htmlspecialchars($export_link . '&format=excel'); ?>" class="export-button">Export Excel</a>
            <a href="<?php echo htmlspecialchars($export_link . '&format=pdf'); ?>" class="export-button">Export PDF</a>
        </div>

        <?php if (!empty($results_message)): ?>
            <div class='no-results-message' style='color: red; font-weight: bold;'><?php echo htmlspecialchars($results_message); ?></div>
        <?php elseif ($results_data && $results_data->num_rows > 0): ?>
            <div class="live-search-wrap">
                <input type="text" id="live_table_search" class="live-search-input" placeholder="Live search: student, subject, class, group, date...">
                <span id="live_search_meta" class="live-search-meta"></span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Topic</th>
                            <th>Test Type</th>
                            <th>Date</th>
                            <th>Total Marks</th>
                            <th>Obtained Marks</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Student Name</th>
                            <th>Session</th>
                            <th>Group</th>
                            <th>Entered By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $results_data->data_seek(0);
                        while ($row = $results_data->fetch_assoc()):
                            $isWeekly = ((int)$row['result_type'] === 0);
                            $rowTopic = $isWeekly ? (string)$row['topic'] : '-';
                            $rowType = $isWeekly ? (string)($result_type_map[$row['result_type']] ?? 'Weekly') : '-';
                        ?>
                            <tr class="result-row">
                                <td><?php echo htmlspecialchars($row['subject_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($rowTopic); ?></td>
                                <td><?php echo htmlspecialchars($rowType); ?></td>
                                <td><?php echo htmlspecialchars($row['result_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['total_marks']); ?></td>
                                <td><?php echo htmlspecialchars($row['obtained_marks']); ?></td>
                                <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['class_section'] !== '' ? $row['class_section'] : '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['session_year'] !== '' ? $row['session_year'] : '-'); ?></td>
                                <?php $rowGroupKey = normalizeGroupKey((string)($row['group_stream'] ?? '')); ?>
                                <td><?php echo htmlspecialchars($rowGroupKey !== '' ? groupLabelFromKey($rowGroupKey) : '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['entered_by']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination-meta">
                Showing <?php echo (int)$show_from; ?>-<?php echo (int)$show_to; ?> of <?php echo (int)$results_total_rows; ?> results
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="<?php echo htmlspecialchars($build_page_url($current_page - 1)); ?>">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php
                    $window = 2;
                    $start_page = max(1, $current_page - $window);
                    $end_page = min($total_pages, $current_page + $window);
                    if ($start_page > 1):
                    ?>
                        <a href="<?php echo htmlspecialchars($build_page_url(1)); ?>">1</a>
                        <?php if ($start_page > 2): ?><span class="ellipsis">...</span><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                        <?php if ($p === $current_page): ?>
                            <span class="active"><?php echo (int)$p; ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($build_page_url($p)); ?>"><?php echo (int)$p; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?><span class="ellipsis">...</span><?php endif; ?>
                        <a href="<?php echo htmlspecialchars($build_page_url($total_pages)); ?>"><?php echo (int)$total_pages; ?></a>
                    <?php endif; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?php echo htmlspecialchars($build_page_url($current_page + 1)); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results-message">
                <p>No results found matching your criteria.</p>
                <p>Click <a href="../upload">Upload Marks</a> to add new results.</p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterClassSubjectSelect = document.getElementById('filter_class_subject');
            const hiddenFilterSubjectIdInput = document.getElementById('hidden_filter_subject_id');
            const liveSearchInput = document.getElementById('live_table_search');
            const liveSearchMeta = document.getElementById('live_search_meta');

            function updateHiddenSubjectId() {
                const selectedOption = filterClassSubjectSelect.options[filterClassSubjectSelect.selectedIndex];
                const subjectId = selectedOption ? selectedOption.getAttribute('data-subject') : '';
                hiddenFilterSubjectIdInput.value = subjectId;
            }

            updateHiddenSubjectId();

            filterClassSubjectSelect.addEventListener('change', updateHiddenSubjectId);

            if (liveSearchInput) {
                const rows = Array.from(document.querySelectorAll('tr.result-row'));

                const runLiveSearch = function() {
                    const q = liveSearchInput.value.trim().toLowerCase();
                    let visibleCount = 0;

                    rows.forEach(function(row) {
                        const text = row.textContent.toLowerCase();
                        const matched = (q === '') || text.indexOf(q) !== -1;
                        row.style.display = matched ? '' : 'none';
                        if (matched) {
                            visibleCount++;
                        }
                    });

                    if (liveSearchMeta) {
                        if (q === '') {
                            liveSearchMeta.textContent = 'Showing all ' + rows.length + ' rows on this page';
                        } else {
                            liveSearchMeta.textContent = 'Showing ' + visibleCount + ' of ' + rows.length + ' rows on this page';
                        }
                    }
                };

                liveSearchInput.addEventListener('input', runLiveSearch);
                runLiveSearch();
            }
        });
    </script>
    <?php
include "../students/partials/footer.php";
?>
</body>
</html>

<?php
if ($conn) {
    $conn->close();
}
?>
