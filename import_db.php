<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$json = file_get_contents('storage/app/students_import.json');
$students = json_decode($json, true);

// Get class mapping (case-insensitive)
$classMap = [];
\App\Models\SchoolClass::all()->each(function($c) use (&$classMap) {
    $key = strtolower(trim($c->class));
    $classMap[$key] = $c->id;
});

// Class name variations mapping
$classVariations = [
    'university student' => 'university student',
    'one' => '1st year',
    'two' => '2nd year',
    '1st year' => '1st year',
    '1s year' => '1st year',
    '6h' => '6th',
    '2nd year ' => '2nd year',
    '3rd year' => '3rd year',
    '3rd' => '3rd',
    '6th' => '6th',
    'teaching art @ kort' => 'teaching @ kort',
    'prep' => 'prep',
    'nursery' => 'nursery',
];

$imported = 0;
$skipped = 0;
$errors = [];

foreach ($students as $idx => $student) {
    try {
        // Find matching class
        $className = strtolower(trim($student['class_name']));

        // Apply class name variations
        $normalizedClassName = $classVariations[$className] ?? $className;
        $classId = $classMap[$normalizedClassName] ?? null;

        if (!$classId) {
            $skipped++;
            continue;
        }

        // Truncate long fields to fit database column limits
        $student['guardian_name'] = substr($student['guardian_name'], 0, 120);
        $student['phone'] = substr($student['phone'], 0, 20);
        $student['guardian_address'] = substr($student['guardian_address'], 0, 255);
        $student['reason_left_kort'] = substr($student['reason_left_kort'], 0, 255);
        $student['group_stream'] = substr($student['group_stream'], 0, 100);
        // Truncate CNIC fields - take first valid CNIC (typically 15 chars or first until space/newline)
        $student['student_cnic'] = substr(preg_replace('/[\s\n].*$/', '', $student['student_cnic'] ?? ''), 0, 50);
        $student['father_cnic'] = substr(preg_replace('/[\s\n].*$/', '', $student['father_cnic'] ?? ''), 0, 50);
        $student['mother_cnic'] = substr(preg_replace('/[\s\n].*$/', '', $student['mother_cnic'] ?? ''), 0, 50);
        $student['guardian_cnic'] = substr(preg_replace('/[\s\n].*$/', '', $student['guardian_cnic'] ?? ''), 0, 100);

        $record = array_merge($student, ['class_id' => $classId]);
        unset($record['class_name']);

        // Update or create student
        \App\Models\Student::updateOrCreate(
            ['admission_no' => $record['admission_no']],
            $record
        );
        $imported++;

        if ($imported % 50 === 0) {
            echo '.';
            flush();
        }
    } catch (\Exception $e) {
        if (count($errors) < 10) {
            $errors[] = 'Row ' . ($idx + 1) . ': ' . $e->getMessage();
        }
    }
}

echo PHP_EOL;
echo '✓ Imported: ' . $imported . ' students' . PHP_EOL;
echo '✗ Skipped: ' . $skipped . ' students (class not found)' . PHP_EOL;
if (count($errors) > 0) {
    echo PHP_EOL . 'First 10 errors:' . PHP_EOL;
    foreach ($errors as $err) {
        echo '  - ' . $err . PHP_EOL;
    }
}
