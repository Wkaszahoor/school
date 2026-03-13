<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Subject;

// Get Class 9-B
$class = SchoolClass::where('class', '9')->where('section', 'B')->first();
if (!$class) {
    echo "Class 9-B not found\n";
    exit(1);
}

echo "=== CHECKING RESULTS FOR CLASS 9-B (ID: {$class->id}) ===\n\n";

// Get all results for 9-B students with Annual exam, 2025-2026
$results = Result::whereHas('student', function ($q) use ($class) {
    $q->where('class_id', $class->id);
})
    ->where('exam_type', 'annual')
    ->where('academic_year', '2025-2026')
    ->where('term', 'Term 3')
    ->with(['student.class', 'subject', 'student.subjectGroup'])
    ->get();

echo "Found " . $results->count() . " results for Annual exam, 2025-26, Term 3\n\n";

// Group by subject
$bySubject = $results->groupBy(function ($r) {
    return $r->subject?->subject_name ?? 'Unknown';
});

foreach ($bySubject as $subjectName => $rows) {
    echo "📚 {$subjectName}:\n";
    echo "   Total entries: " . $rows->count() . "\n";
    echo "   Approval statuses:\n";

    $statuses = $rows->groupBy('approval_status');
    foreach ($statuses as $status => $statusRows) {
        echo "      - {$status}: " . $statusRows->count() . "\n";
        foreach ($statusRows as $row) {
            $groupName = $row->student->subjectGroup?->name ?? 'No group';
            echo "         • Student: {$row->student->full_name} (Group: {$groupName})\n";
        }
    }
    echo "\n";
}

echo "\n=== STUDENTS IN CLASS 9-B ===\n";
$students = $class->students()->with('subjectGroup')->get();
echo "Total students: " . $students->count() . "\n";

foreach ($students as $student) {
    $streamName = $student->subjectGroup?->name ?? 'No group';
    $streamKey = $student->stream ?? 'null';
    echo "- {$student->full_name}: Group={$streamName}, Stream={$streamKey}\n";
}

echo "\n=== SUMMARY ===\n";
echo "Subjects with approved results: " . $bySubject->map(function ($rows) {
    return $rows->where('approval_status', 'approved')->count() > 0 ? 1 : 0;
})->sum() . "\n";

echo "Subjects with any results: " . $bySubject->count() . "\n";
