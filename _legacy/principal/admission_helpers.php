<?php

function admission_students_table_exists(mysqli $conn): bool
{
    $sql = "SELECT COUNT(*) AS c
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = 'students'";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : null;
    return (int)($row['c'] ?? 0) > 0;
}

function admission_ensure_students_table(mysqli $conn): void
{
    if (!admission_students_table_exists($conn)) {
        $conn->query("
            CREATE TABLE students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admission_no VARCHAR(30) UNIQUE,
                full_name VARCHAR(100),
                father_name VARCHAR(100),
                mother_name VARCHAR(100),
                dob DATE,
                gender VARCHAR(10),
                class VARCHAR(20),
                section VARCHAR(10),
                roll_no VARCHAR(20),
                address TEXT,
                mobile VARCHAR(15),
                email VARCHAR(100),
                blood_group VARCHAR(5),
                photo VARCHAR(255),
                admission_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    $required = [
        'admission_no' => "VARCHAR(30) NULL",
        'full_name' => "VARCHAR(100) NULL",
        'father_name' => "VARCHAR(100) NULL",
        'mother_name' => "VARCHAR(100) NULL",
        'section' => "VARCHAR(10) NULL",
        'roll_no' => "VARCHAR(20) NULL",
        'mobile' => "VARCHAR(15) NULL",
        'photo' => "VARCHAR(255) NULL",
        'admission_date' => "DATE NULL",

        // Compatibility with existing module/table fields.
        'StudentId' => "VARCHAR(50) NULL",
        'student_name' => "VARCHAR(100) NULL",
        'guardian_name' => "VARCHAR(120) NULL",
        'guardian_contact' => "VARCHAR(30) NULL",
        'profile_image' => "VARCHAR(255) NULL",
        'join_date_kort' => "DATE NULL",
        'phone' => "VARCHAR(20) NULL",
        'trust_notes' => "TEXT NULL",
    ];

    $existing = admission_student_columns($conn);
    foreach ($required as $column => $definition) {
        if (!isset($existing[$column])) {
            $conn->query("ALTER TABLE students ADD COLUMN `$column` $definition");
        }
    }
}

function admission_student_columns(mysqli $conn): array
{
    $out = [];
    $res = $conn->query("SHOW COLUMNS FROM students");
    while ($res && $row = $res->fetch_assoc()) {
        $out[(string)$row['Field']] = true;
    }
    return $out;
}

function admission_next_no(mysqli $conn, int $year): string
{
    $prefix = 'SCH-' . $year . '-';
    $pattern = $prefix . '%';
    $stmt = $conn->prepare("
        SELECT MAX(CAST(SUBSTRING_INDEX(COALESCE(StudentId, admission_no), '-', -1) AS UNSIGNED)) AS max_no
        FROM students
        WHERE COALESCE(StudentId, admission_no) LIKE ?
    ");
    $maxNo = 0;
    if ($stmt) {
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $maxNo = (int)($row['max_no'] ?? 0);
        $stmt->close();
    }
    return $prefix . str_pad((string)($maxNo + 1), 3, '0', STR_PAD_LEFT);
}

function admission_next_roll_no(mysqli $conn, string $className): string
{
    $stmt = $conn->prepare("
        SELECT MAX(CAST(roll_no AS UNSIGNED)) AS max_roll
        FROM students
        WHERE class = ?
          AND roll_no REGEXP '^[0-9]+$'
    ");
    $maxNo = 0;
    if ($stmt) {
        $stmt->bind_param('s', $className);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $maxNo = (int)($row['max_roll'] ?? 0);
        $stmt->close();
    }
    return (string)($maxNo + 1);
}

function admission_upload_photo(array $file, string $projectRoot): array
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return [true, ''];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return [false, 'Photo upload failed.'];
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    $name = (string)($file['name'] ?? '');
    $size = (int)($file['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        return [false, 'Photo must be less than 5MB.'];
    }
    if (!in_array($ext, $allowed, true)) {
        return [false, 'Allowed photo types: JPG, JPEG, PNG, WEBP.'];
    }
    if (!is_uploaded_file($tmp)) {
        return [false, 'Invalid photo upload.'];
    }
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string)@finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        @finfo_close($finfo);
    }
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    if ($mime === '' || !in_array($mime, $allowedMime, true)) {
        return [false, 'Invalid photo file type.'];
    }
    if (@getimagesize($tmp) === false) {
        return [false, 'Uploaded photo is not a valid image.'];
    }

    $dir = $projectRoot . '/uploads/students';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return [false, 'Upload directory is not writable.'];
    }

    $safe = 'admission_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . '/' . $safe;
    if (!move_uploaded_file($tmp, $target)) {
        return [false, 'Could not save uploaded photo.'];
    }

    return [true, 'uploads/students/' . $safe];
}

function admission_can_view_trust_notes(): bool
{
    $role = auth_current_role();
    return in_array($role, ['admin', 'principal'], true);
}

function admission_ensure_student_documents_table(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS student_documents (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            doc_type VARCHAR(80) NOT NULL,
            doc_title VARCHAR(190) NOT NULL,
            version_no INT NOT NULL DEFAULT 1,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) NOT NULL,
            file_size INT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            uploaded_by_role VARCHAR(40) NULL,
            uploaded_by_id INT NULL,
            uploaded_by_name VARCHAR(120) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_student_doc (student_id, doc_type, doc_title, version_no),
            INDEX idx_student_doc_active (student_id, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function admission_upload_document(array $file, string $projectRoot, int $studentId): array
{
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        return [false, 'Document upload failed.', '', '', '', 0];
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    $name = (string)($file['name'] ?? '');
    $size = (int)($file['size'] ?? 0);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $allowedMime = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    if ($size <= 0 || $size > 10 * 1024 * 1024) {
        return [false, 'Document must be less than 10MB.', '', '', '', 0];
    }
    if (!in_array($ext, $allowedExt, true)) {
        return [false, 'Allowed document types: PDF, JPG, JPEG, PNG, DOC, DOCX.', '', '', '', 0];
    }
    if (!is_uploaded_file($tmp)) {
        return [false, 'Invalid uploaded document.', '', '', '', 0];
    }
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string)@finfo_file($finfo, $tmp) : 'application/octet-stream';
    if ($finfo) {
        @finfo_close($finfo);
    }
    if (!in_array($mime, $allowedMime, true)) {
        return [false, 'Invalid document file type.', '', '', '', 0];
    }

    $dir = $projectRoot . '/uploads/student_documents/' . max(1, $studentId);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return [false, 'Document upload directory is not writable.', '', '', '', 0];
    }

    $safeName = 'doc_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . '/' . $safeName;
    if (!move_uploaded_file($tmp, $target)) {
        return [false, 'Could not save uploaded document.', '', '', '', 0];
    }

    return [true, '', $safeName, 'uploads/student_documents/' . max(1, $studentId) . '/' . $safeName, $mime, $size];
}

function admission_find_student(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("
        SELECT
            id,
            StudentId,
            admission_no,
            student_name,
            full_name,
            guardian_name,
            father_name,
            mother_name,
            dob,
            gender,
            class,
            section,
            roll_no,
            academic_year,
            group_stream,
            address,
            guardian_contact,
            mobile,
            phone,
            email,
            blood_group,
            profile_image,
            photo,
            join_date_kort,
            admission_date
        FROM students
        WHERE id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function admission_v(array $row, string $key, string $fallbackKey = ''): string
{
    $value = trim((string)($row[$key] ?? ''));
    if ($value !== '') {
        return $value;
    }
    if ($fallbackKey !== '') {
        return trim((string)($row[$fallbackKey] ?? ''));
    }
    return '';
}

function admission_ensure_datesheet_table(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS student_datesheets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_name VARCHAR(40) NOT NULL,
            subject_name VARCHAR(120) NOT NULL,
            exam_date DATE NOT NULL,
            exam_time VARCHAR(40) DEFAULT NULL,
            room_no VARCHAR(40) DEFAULT NULL,
            total_marks VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_class_date (class_name, exam_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function admission_datesheet_for_class(mysqli $conn, string $className): array
{
    $rows = [];
    if ($className === '') {
        return $rows;
    }

    $candidates = admission_datesheet_class_candidates($className);
    if (!$candidates) {
        return $rows;
    }

    $placeholders = implode(',', array_fill(0, count($candidates), '?'));
    $sql = "
        SELECT id, class_name, subject_name, exam_date, exam_time, room_no, total_marks
        FROM student_datesheets
        WHERE class_name IN ($placeholders)
        ORDER BY exam_date ASC, subject_name ASC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $types = str_repeat('s', count($candidates));
        $stmt->bind_param($types, ...$candidates);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    }
    return $rows;
}

function admission_normalize_stream_key(string $stream): string
{
    $s = strtolower(trim($stream));
    $s = str_replace(['-', ' '], '_', $s);
    if ($s === 'premedical') {
        $s = 'pre_medical';
    }
    return $s;
}

function admission_normalize_subject_text(string $subject): string
{
    $s = str_replace(["\r", "\n", "\t"], ' ', $subject);
    $s = strtolower(trim($s));
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    return $s;
}

function admission_datesheet_filter_rows(array $rows, string $className, string $streamKey = ''): array
{
    $filtered = [];
    $classNorm = strtolower(trim($className));
    $isSecondYear = str_contains($classNorm, '2nd year') || str_contains($classNorm, 'second year');
    $stream = admission_normalize_stream_key($streamKey);

    foreach ($rows as $row) {
        $subjectRaw = (string)($row['subject_name'] ?? '');
        $subject = admission_normalize_subject_text($subjectRaw);
        if ($subject === '') {
            continue;
        }

        // Business rule: 2nd Year should not include Islamiat in datesheet cards.
        if ($isSecondYear && (str_contains($subject, 'islamiat') || str_contains($subject, 'islamiyat'))) {
            continue;
        }

        // Stream-specific filtering for 1st/2nd year cards.
        $isBioComputerCombo = str_contains($subject, 'bio') && str_contains($subject, 'computer');
        if ($stream === 'ics') {
            if (str_contains($subject, 'chemistry')) {
                continue;
            }
            if ($isBioComputerCombo) {
                $row['subject_name'] = 'Computer (PBA)';
            } elseif (str_contains($subject, 'biology')) {
                continue;
            }
        } elseif ($stream === 'pre_medical') {
            if ($isBioComputerCombo) {
                $row['subject_name'] = 'Biology (PBA)';
            } elseif (str_contains($subject, 'math') || str_contains($subject, 'mathematics') || str_contains($subject, 'computer')) {
                continue;
            }
        }

        $filtered[] = $row;
    }

    return $filtered;
}

function admission_datesheet_for_student(mysqli $conn, string $className, string $streamKey = ''): array
{
    $rows = admission_datesheet_for_class($conn, $className);
    return admission_datesheet_filter_rows($rows, $className, $streamKey);
}

function admission_datesheet_class_candidates(string $className): array
{
    $raw = trim($className);
    if ($raw === '') {
        return [];
    }

    $norm = strtolower($raw);
    $norm = str_replace(['-', '_', '.', ','], ' ', $norm);
    $norm = preg_replace('/\s+/', ' ', $norm) ?? $norm;
    $norm = trim($norm);

    $candidates = [$raw];
    $isFirstYear = str_contains($norm, '1st year') || str_contains($norm, 'first year');
    $isSecondYear = str_contains($norm, '2nd year') || str_contains($norm, 'second year');
    $isYearClass = $isFirstYear || $isSecondYear;

    $numToWord = [
        '1' => 'One',
        '2' => 'Two',
        '3' => 'Three',
        '4' => 'Four',
        '5' => 'Five',
        '6' => 'Six',
        '7' => 'Seven',
        '8' => 'Eight',
        '9' => 'Nine',
        '10' => 'Ten',
    ];

    // Direct numeric extraction: "Nine-A", "Class 10", "6th", etc.
    if (!$isYearClass && preg_match('/\d+/', $norm, $m) === 1) {
        $num = (string)((int)$m[0]);
        $candidates[] = $num;
        if (isset($numToWord[$num])) {
            $candidates[] = $numToWord[$num];
        }
    }

    // Word-to-number mapping for existing student class names.
    $wordMap = [
        'one' => '1',
        'two' => '2',
        'three' => '3',
        'four' => '4',
        'five' => '5',
        'six' => '6',
        'seven' => '7',
        'eight' => '8',
        'nine' => '9',
        'ten' => '10',
    ];
    if (!$isYearClass) {
        foreach ($wordMap as $word => $num) {
            if (str_contains($norm, $word)) {
                $candidates[] = $num;
                if (isset($numToWord[$num])) {
                    $candidates[] = $numToWord[$num];
                }
                break;
            }
        }
    }

    // Year aliases used in many school records.
    // Fallback to Ten when 1st/2nd year datesheet is not separately maintained.
    if ($isFirstYear) {
        $candidates[] = '1st Year';
        $candidates[] = 'First Year';
        $candidates[] = '11';
        $candidates[] = 'Eleven';
        $candidates[] = 'Ten';
    }
    if ($isSecondYear) {
        $candidates[] = '2nd Year';
        $candidates[] = 'Second Year';
        $candidates[] = '12';
        $candidates[] = 'Twelve';
        $candidates[] = 'Ten';
    }

    // Keep unique, non-empty values.
    $out = [];
    foreach ($candidates as $c) {
        $v = trim((string)$c);
        if ($v !== '') {
            $out[$v] = true;
        }
    }
    return array_keys($out);
}

function admission_excel_serial_to_date($serial): ?string
{
    if ($serial === null || $serial === '') {
        return null;
    }
    if (!is_numeric($serial)) {
        $ts = strtotime((string)$serial);
        return $ts ? date('Y-m-d', $ts) : null;
    }
    // Excel 1900 date system.
    $days = (int)floor((float)$serial);
    if ($days <= 0) {
        return null;
    }
    $base = new DateTime('1899-12-30');
    $base->modify('+' . $days . ' day');
    return $base->format('Y-m-d');
}

function admission_parse_xlsx_shared_strings(ZipArchive $zip): array
{
    $shared = [];
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) {
        return $shared;
    }
    $sx = simplexml_load_string($xml);
    if (!$sx || !isset($sx->si)) {
        return $shared;
    }
    foreach ($sx->si as $si) {
        $text = '';
        if (isset($si->t)) {
            $text = (string)$si->t;
        } elseif (isset($si->r)) {
            foreach ($si->r as $r) {
                $text .= (string)$r->t;
            }
        }
        $shared[] = trim($text);
    }
    return $shared;
}

function admission_xlsx_cell_value(SimpleXMLElement $cell, array $shared): string
{
    $v = isset($cell->v) ? trim((string)$cell->v) : '';
    $type = (string)($cell['t'] ?? '');
    if ($type === 's') {
        $idx = (int)$v;
        return trim((string)($shared[$idx] ?? ''));
    }
    return $v;
}

function admission_expand_class_header(string $header): array
{
    $h = strtolower(trim($header));
    if ($h === '') {
        return [];
    }

    // Keep only numeric class references from labels like "class 2,3&4" / "class 6th&7th".
    preg_match_all('/\d+/', $h, $m);
    $nums = array_unique($m[0] ?? []);
    $out = [];
    foreach ($nums as $n) {
        $out[] = (string)((int)$n);
    }
    return $out;
}

function admission_import_datesheet_from_xlsx(mysqli $conn, string $xlsxPath, bool $replaceForTouchedClasses = true): array
{
    $result = [
        'ok' => false,
        'inserted' => 0,
        'deleted' => 0,
        'skipped' => 0,
        'classes' => [],
        'message' => '',
    ];

    if (!is_file($xlsxPath)) {
        $result['message'] = 'Excel file not found.';
        return $result;
    }

    $zip = new ZipArchive();
    if ($zip->open($xlsxPath) !== true) {
        $result['message'] = 'Could not open Excel file.';
        return $result;
    }

    $shared = admission_parse_xlsx_shared_strings($zip);

    // Read workbook relations to locate all worksheet XML files.
    $wbXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($wbXml === false || $relsXml === false) {
        $zip->close();
        $result['message'] = 'Invalid workbook structure.';
        return $result;
    }

    $wb = simplexml_load_string($wbXml);
    $rels = simplexml_load_string($relsXml);
    if (!$wb || !$rels) {
        $zip->close();
        $result['message'] = 'Invalid workbook XML.';
        return $result;
    }

    $ridToTarget = [];
    foreach ($rels->Relationship as $r) {
        $ridToTarget[(string)$r['Id']] = (string)$r['Target'];
    }

    $allRows = [];
    foreach ($wb->sheets->sheet as $sheet) {
        $rid = (string)$sheet->attributes('r', true)['id'];
        $target = $ridToTarget[$rid] ?? '';
        if ($target === '') {
            continue;
        }
        $sheetPath = 'xl/' . ltrim($target, '/');
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            continue;
        }
        $sx = simplexml_load_string($sheetXml);
        if (!$sx || !isset($sx->sheetData->row)) {
            continue;
        }

        $headerMap = [];
        $headerFound = false;
        foreach ($sx->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = (string)$cell['r'];
                if (preg_match('/[A-Z]+/', $ref, $m) !== 1) {
                    continue;
                }
                $col = $m[0];
                $cells[$col] = admission_xlsx_cell_value($cell, $shared);
            }
            if (!$cells) {
                continue;
            }

            // Identify header row containing Date and class columns.
            if (!$headerFound) {
                $hasDate = false;
                $classCols = 0;
                foreach ($cells as $col => $val) {
                    $txt = strtolower(trim((string)$val));
                    if ($txt === 'date') {
                        $hasDate = true;
                    }
                    if (str_contains($txt, 'class')) {
                        $classCols++;
                    }
                }
                if ($hasDate && $classCols > 0) {
                    foreach ($cells as $col => $val) {
                        $headerMap[$col] = trim((string)$val);
                    }
                    $headerFound = true;
                }
                continue;
            }

            $dateRaw = $cells['B'] ?? '';
            $examDate = admission_excel_serial_to_date($dateRaw);
            if (!$examDate) {
                continue;
            }

            foreach ($headerMap as $col => $headerText) {
                $classes = admission_expand_class_header((string)$headerText);
                if (!$classes) {
                    continue;
                }
                $subject = trim((string)($cells[$col] ?? ''));
                if ($subject === '' || $subject === '-') {
                    continue;
                }
                foreach ($classes as $className) {
                    $allRows[] = [
                        'class_name' => $className,
                        'subject_name' => $subject,
                        'exam_date' => $examDate,
                        'exam_time' => null,
                        'room_no' => null,
                        'total_marks' => null,
                    ];
                }
            }
        }
    }

    $zip->close();

    if (!$allRows) {
        $result['message'] = 'No valid datesheet rows found in Excel.';
        return $result;
    }

    // De-duplicate rows.
    $unique = [];
    foreach ($allRows as $row) {
        $k = $row['class_name'] . '|' . $row['subject_name'] . '|' . $row['exam_date'];
        $unique[$k] = $row;
    }
    $rows = array_values($unique);

    $touchedClasses = array_values(array_unique(array_map(static fn($r) => (string)$r['class_name'], $rows)));
    $result['classes'] = $touchedClasses;

    if ($replaceForTouchedClasses && $touchedClasses) {
        $del = $conn->prepare("DELETE FROM student_datesheets WHERE class_name = ?");
        if ($del) {
            foreach ($touchedClasses as $className) {
                $del->bind_param('s', $className);
                $del->execute();
                $result['deleted'] += (int)$del->affected_rows;
            }
            $del->close();
        }
    }

    $ins = $conn->prepare("
        INSERT INTO student_datesheets (class_name, subject_name, exam_date, exam_time, room_no, total_marks)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$ins) {
        $result['message'] = 'Could not prepare insert statement.';
        return $result;
    }

    foreach ($rows as $row) {
        $className = (string)$row['class_name'];
        $subject = (string)$row['subject_name'];
        $examDate = (string)$row['exam_date'];
        $examTime = (string)($row['exam_time'] ?? '');
        $roomNo = (string)($row['room_no'] ?? '');
        $totalMarks = (string)($row['total_marks'] ?? '');
        $ins->bind_param('ssssss', $className, $subject, $examDate, $examTime, $roomNo, $totalMarks);
        if ($ins->execute()) {
            $result['inserted']++;
        } else {
            $result['skipped']++;
        }
    }
    $ins->close();

    $result['ok'] = true;
    $result['message'] = 'Datesheet imported successfully.';
    return $result;
}
