<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\{Student, SchoolClass, AuditLog};

echo "🔄 Importing all students with complete information...\n\n";

$jsonData = json_decode(file_get_contents('complete_import.json'), true);

// Build class map
$classes = SchoolClass::where('is_active', true)->get();
$classMap = [];
foreach ($classes as $class) {
    $classMap[$class->class] = $class->id;
}

echo "📊 Total students to import: " . count($jsonData) . "\n";
echo "Available classes: " . count($classMap) . "\n\n";

$imported = 0;
$failed = 0;
$errors = [];

foreach ($jsonData as $index => $studentData) {
    try {
        // Get class_id from class_name
        $className = $studentData['class_name'] ?? null;
        if (!$className || !isset($classMap[$className])) {
            $failed++;
            $errors[] = "Student {$studentData['full_name']}: Class '{$className}' not found";
            continue;
        }

        // Prepare complete student data
        $data = [
            'full_name' => $studentData['full_name'] ?? '',
            'admission_no' => $studentData['admission_no'] ?? 'KRT' . rand(10000, 99999),
            'student_cnic' => $studentData['student_cnic'] ?? '',
            'father_name' => $studentData['father_name'] ?? '',
            'father_cnic' => $studentData['father_cnic'] ?? '',
            'mother_name' => $studentData['mother_name'] ?? '',
            'mother_cnic' => $studentData['mother_cnic'] ?? '',
            'guardian_name' => $studentData['guardian_name'] ?? '',
            'guardian_cnic' => $studentData['guardian_cnic'] ?? '',
            'guardian_address' => $studentData['guardian_address'] ?? '',
            'phone' => $studentData['phone'] ?? '',
            'dob' => $studentData['dob'] ?? null,
            'gender' => $studentData['gender'] ?? 'male',
            'class_id' => $classMap[$className],
            'group_stream' => $studentData['group_stream'] ?? '',
            'semester' => $studentData['semester'] ?? '',
            'join_date_kort' => $studentData['join_date_kort'] ?? null,
            'favorite_color' => $studentData['favorite_color'] ?? '',
            'favorite_food' => $studentData['favorite_food'] ?? '',
            'favorite_subject' => $studentData['favorite_subject'] ?? '',
            'ambition' => $studentData['ambition'] ?? '',
            'is_active' => $studentData['is_active'] ?? true,
            'reason_left_kort' => $studentData['reason_left_kort'] ?? '',
        ];

        // Check if student already exists
        if (Student::where('admission_no', $data['admission_no'])->exists()) {
            $failed++;
            continue;
        }

        // Create student
        Student::create($data);
        AuditLog::log('import_complete', 'Student', null, null, $data);
        $imported++;

        if ($imported % 50 == 0) {
            echo "✓ Imported $imported students...\n";
        }
    } catch (\Exception $e) {
        $failed++;
        $errors[] = "Student " . ($index + 1) . ": " . $e->getMessage();
    }
}

echo "\n✅ Import complete!\n\n";
echo "📊 Results:\n";
echo "  ✓ Imported: $imported students\n";
echo "  ❌ Failed: $failed students\n";

if (!empty($errors) && count($errors) > 0) {
    echo "\n⚠️  Sample errors (first 5):\n";
    foreach (array_slice($errors, 0, 5) as $error) {
        echo "  - $error\n";
    }
}

// Show distribution by class
echo "\n📚 Students by Class:\n";
$byClass = Student::with('class')
    ->selectRaw('class_id, COUNT(*) as count')
    ->groupBy('class_id')
    ->orderBy('class_id')
    ->get();

foreach ($byClass as $item) {
    $className = $item->class->class ?? 'Unknown';
    echo "  $className: {$item->count}\n";
}

$total = Student::count();
echo "\n  TOTAL: $total students\n";

// Clean up
unlink('complete_import.json');
