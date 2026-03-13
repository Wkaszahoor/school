<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Result;
use App\Models\SchoolClass;

// Get Class 9-B
$class = SchoolClass::where('class', '9')->where('section', 'B')->first();
if (!$class) {
    echo "Class 9-B not found\n";
    exit(1);
}

echo "=== ALL RESULTS FOR CLASS 9-B (ANY STATUS) ===\n\n";

// Get ALL results for 9-B students (all statuses, all exams)
$results = Result::whereHas('student', function ($q) use ($class) {
    $q->where('class_id', $class->id);
})
    ->with(['student', 'subject'])
    ->orderBy('exam_type')
    ->orderBy('academic_year')
    ->orderBy('term')
    ->orderBy('approval_status')
    ->get();

echo "Total results (all statuses): " . $results->count() . "\n\n";

// Group by exam type, academic year, term
$grouped = $results->groupBy(function ($r) {
    return "{$r->exam_type} | {$r->academic_year} | {$r->term}";
});

foreach ($grouped as $examKey => $examResults) {
    echo "=== {$examKey} ===\n";

    $byStatus = $examResults->groupBy('approval_status');
    foreach ($byStatus as $status => $statusResults) {
        echo "\n  Status: {$status} (" . $statusResults->count() . " results)\n";

        $bySubject = $statusResults->groupBy(function ($r) {
            return $r->subject?->subject_name ?? 'Unknown';
        });

        foreach ($bySubject as $subjectName => $subjectResults) {
            echo "    📚 {$subjectName}: {$subjectResults->count()} student(s)\n";
            foreach ($subjectResults as $result) {
                echo "       • {$result->student->full_name}: {$result->obtained_marks}/{$result->total_marks}\n";
            }
        }
    }
    echo "\n";
}
