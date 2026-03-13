<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

const EXCEL_PATH = 'C:/Users/waqas/OneDrive/Desktop/Teachers_List_with_Filters.xlsx';
const DEFAULT_PASSWORD = 'Teacher@123';
const EMAIL_DOMAIN = 'kort.edu.uk';

function colToIndex(string $letters): int
{
    $letters = strtoupper($letters);
    $n = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $n = $n * 26 + (ord($letters[$i]) - 64);
    }
    return $n;
}

function normalizeKey(string $value): string
{
    $value = strtolower(trim($value));
    return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
}

function safeEmailLocal(string $name): string
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9]+/', '.', $name) ?? '';
    $name = trim($name, '.');
    return $name !== '' ? $name : 'teacher';
}

function parseCsvTokens(string $value): array
{
    $parts = array_map('trim', explode(',', $value));
    $parts = array_values(array_filter($parts, static fn($v) => $v !== ''));
    return $parts;
}

function readTeacherRowsFromXlsx(string $path): array
{
    if (!file_exists($path)) {
        throw new RuntimeException("Excel file not found: $path");
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException("Failed to open xlsx: $path");
    }

    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $ss = simplexml_load_string($ssXml);
        if ($ss !== false) {
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } else {
                    $txt = '';
                    foreach ($si->r as $r) {
                        $txt .= (string)$r->t;
                    }
                    $sharedStrings[] = $txt;
                }
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('Worksheet sheet1.xml not found');
    }
    $sheet = simplexml_load_string($sheetXml);
    if ($sheet === false) {
        throw new RuntimeException('Invalid worksheet XML');
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $rowNum = (int)$row['r'];
        if ($rowNum === 1) {
            continue;
        }
        $cells = [];
        foreach ($row->c as $c) {
            if (!preg_match('/([A-Z]+)(\d+)/', (string)$c['r'], $m)) {
                continue;
            }
            $col = colToIndex($m[1]);
            $type = (string)$c['t'];
            $value = isset($c->v) ? (string)$c->v : '';
            if ($type === 's') {
                $value = $sharedStrings[(int)$value] ?? '';
            }
            $cells[$col] = trim($value);
        }

        $name = trim((string)($cells[1] ?? ''));
        $subjects = trim((string)($cells[2] ?? ''));
        $classes = trim((string)($cells[3] ?? ''));
        if ($name === '') {
            continue;
        }
        $rows[] = [
            'name' => $name,
            'subjects_raw' => $subjects,
            'classes_raw' => $classes,
            'subjects' => parseCsvTokens($subjects),
            'classes' => parseCsvTokens($classes),
        ];
    }

    return $rows;
}

function fetchClassMap(mysqli $conn): array
{
    $res = $conn->query('SELECT id, class FROM classes');
    $map = [];
    while ($res && ($row = $res->fetch_assoc())) {
        $map[normalizeKey((string)$row['class'])] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['class'],
        ];
    }
    return $map;
}

function fetchSubjectMap(mysqli $conn): array
{
    $res = $conn->query('SELECT id, subject_name FROM subjects');
    $map = [];
    while ($res && ($row = $res->fetch_assoc())) {
        $map[normalizeKey((string)$row['subject_name'])] = [
            'id' => (int)$row['id'],
            'name' => (string)$row['subject_name'],
        ];
    }
    return $map;
}

function findOrCreateSubjectId(mysqli $conn, string $token, array &$subjectMap): int
{
    $subjectAlias = [
        'math' => 'Mathematics',
        'pakstudy' => 'Pakistan Studies',
        'computer' => 'Computer Science',
        'physics' => 'Physics',
        'pe' => 'P.E',
        'sst' => 'S.S.T',
        'fq' => 'F/Q',
        'tq' => 'T/Q',
        'hg' => 'H/G',
    ];
    $key = normalizeKey($token);
    $canonical = $subjectAlias[$key] ?? trim($token);
    $canonicalKey = normalizeKey($canonical);

    if (isset($subjectMap[$canonicalKey])) {
        return (int)$subjectMap[$canonicalKey]['id'];
    }

    $stmt = $conn->prepare('INSERT INTO subjects (subject_name) VALUES (?)');
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $canonical);
    if (!$stmt->execute()) {
        $stmt->close();
        $subjectMap = fetchSubjectMap($conn);
        return (int)($subjectMap[$canonicalKey]['id'] ?? 0);
    }
    $newId = (int)$stmt->insert_id;
    $stmt->close();
    $subjectMap[$canonicalKey] = ['id' => $newId, 'name' => $canonical];
    return $newId;
}

function findClassId(string $token, array $classMap): int
{
    $classAlias = [
        'pg' => 'P.g',
        'nursery' => 'Nursery',
        'prep' => 'Prep',
        'one' => 'One',
        'two' => 'Two',
        '3a' => 'Three-A',
        '3b' => 'Three-B',
        '4a' => 'Four-A',
        '4b' => 'Four-B',
        '5' => 'Five',
        '6a' => 'Six-A',
        '6b' => 'Six-B',
        '7a' => 'Seven-A',
        '7b' => 'Seven-B',
        '8' => 'Eight',
        '9a' => 'Nine-A',
        '9b' => 'Nine-B',
        '10th' => 'Ten',
        '1styear' => '1st Year',
        '2ndyear' => '2nd Year',
    ];
    $key = normalizeKey($token);
    $canonical = $classAlias[$key] ?? $token;
    $canonicalKey = normalizeKey($canonical);
    return (int)($classMap[$canonicalKey]['id'] ?? 0);
}

function ensureUniqueEmail(string $baseLocal, array &$usedEmails): string
{
    $i = 0;
    while (true) {
        $candidateLocal = $i === 0 ? $baseLocal : ($baseLocal . $i);
        $email = $candidateLocal . '@' . EMAIL_DOMAIN;
        $k = strtolower($email);
        if (!isset($usedEmails[$k])) {
            $usedEmails[$k] = true;
            return $email;
        }
        $i++;
    }
}

try {
    $rows = readTeacherRowsFromXlsx(EXCEL_PATH);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$classMap = fetchClassMap($conn);
$subjectMap = fetchSubjectMap($conn);

$existingTeachers = [];
$usedEmails = [];
$resTeachers = $conn->query('SELECT id, name, email FROM teachers');
while ($resTeachers && ($t = $resTeachers->fetch_assoc())) {
    $existingTeachers[normalizeKey((string)$t['name'])] = [
        'id' => (int)$t['id'],
        'name' => (string)$t['name'],
    ];
    $usedEmails[strtolower((string)$t['email'])] = true;
}

$teacherIdsTouched = [];
$assignmentTuples = [];
$missingClassTokens = [];
$createdTeachers = 0;
$updatedTeachers = 0;

$insertTeacher = $conn->prepare('INSERT INTO teachers (name, email, password, subject, phone, class_assigned) VALUES (?, ?, ?, ?, NULL, ?)');
$updateTeacher = $conn->prepare('UPDATE teachers SET email = ?, subject = ?, class_assigned = ? WHERE id = ?');
if (!$insertTeacher || !$updateTeacher) {
    fwrite(STDERR, "ERROR: prepare teacher statements failed\n");
    exit(1);
}

$defaultPasswordHash = password_hash(DEFAULT_PASSWORD, PASSWORD_DEFAULT);

foreach ($rows as $row) {
    $name = trim((string)$row['name']);
    $subjects = $row['subjects'];
    $classes = $row['classes'];
    $nameKey = normalizeKey($name);
    if ($nameKey === '') {
        continue;
    }

    $subjectText = implode(', ', $subjects);
    $classText = implode(', ', $classes);
    $emailLocal = safeEmailLocal($name);

    $teacherId = 0;
    $desiredEmail = ensureUniqueEmail($emailLocal, $usedEmails);
    if (isset($existingTeachers[$nameKey])) {
        $teacherId = (int)$existingTeachers[$nameKey]['id'];
        $updateTeacher->bind_param('sssi', $desiredEmail, $subjectText, $classText, $teacherId);
        $updateTeacher->execute();
        $updatedTeachers++;
    } else {
        $insertTeacher->bind_param('sssss', $name, $desiredEmail, $defaultPasswordHash, $subjectText, $classText);
        if ($insertTeacher->execute()) {
            $teacherId = (int)$insertTeacher->insert_id;
            $existingTeachers[$nameKey] = ['id' => $teacherId, 'name' => $name];
            $updatedTeachers++;
            $createdTeachers++;
        } else {
            continue;
        }
    }

    if ($teacherId <= 0) {
        continue;
    }
    $teacherIdsTouched[$teacherId] = true;

    foreach ($subjects as $subjectToken) {
        $subjectId = findOrCreateSubjectId($conn, $subjectToken, $subjectMap);
        if ($subjectId <= 0) {
            continue;
        }
        foreach ($classes as $classToken) {
            $classId = findClassId($classToken, $classMap);
            if ($classId <= 0) {
                $missingClassTokens[$classToken] = true;
                continue;
            }
            $tupleKey = $teacherId . ':' . $classId . ':' . $subjectId;
            $assignmentTuples[$tupleKey] = [$teacherId, $classId, $subjectId];
        }
    }
}

$insertedAssignments = 0;
if (!empty($teacherIdsTouched)) {
    $idList = implode(',', array_map('intval', array_keys($teacherIdsTouched)));
    $conn->query("DELETE FROM teacher_assignments WHERE teacher_id IN ($idList)");
}
$insertAssignment = $conn->prepare('INSERT INTO teacher_assignments (teacher_id, class_id, subject_id) VALUES (?, ?, ?)');
if ($insertAssignment) {
    foreach ($assignmentTuples as [$teacherId, $classId, $subjectId]) {
        $insertAssignment->bind_param('iii', $teacherId, $classId, $subjectId);
        if ($insertAssignment->execute()) {
            $insertedAssignments++;
        }
    }
    $insertAssignment->close();
}

$insertTeacher->close();
$updateTeacher->close();

echo "IMPORT_OK\n";
echo "Teachers from Excel: " . count($rows) . "\n";
echo "Teachers created: $createdTeachers\n";
echo "Teachers updated: $updatedTeachers\n";
echo "Assignments refreshed: $insertedAssignments\n";
echo "Default password for new teachers: " . DEFAULT_PASSWORD . "\n";
if (!empty($missingClassTokens)) {
    echo "Unmapped class tokens: " . implode(', ', array_keys($missingClassTokens)) . "\n";
}

