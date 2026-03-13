<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClassStreamSubjectGroup;
use App\Models\SchoolClass;

// Find Class 9-B
$class = SchoolClass::where('class', '9')->where('section', 'B')->first();

if (!$class) {
    echo "Class 9-B not found\n";
    exit(1);
}

echo "=== Class 9-B (ID: {$class->id}) ===\n\n";

// Get all subject groups for this class
$groups = ClassStreamSubjectGroup::where('class_id', $class->id)
    ->with('group')
    ->get();

echo "Found " . $groups->count() . " subject groups:\n\n";

foreach ($groups as $mapping) {
    $stream = $mapping->stream_key;
    $subjectGroup = $mapping->group;
    
    echo "📚 Stream: {$stream} (Group ID: {$subjectGroup->id})\n";
    echo "   Group Name: {$subjectGroup->name}\n";
    echo "   Students in this group: " . $subjectGroup->students()->count() . "\n";
    echo "   Subjects:\n";

    $subjects = $subjectGroup->subjectGroupSubjects;
    if ($subjects->count() === 0) {
        echo "      ⚠️  NO SUBJECTS ASSIGNED\n";
    } else {
        foreach ($subjects as $sub) {
            echo "      - {$sub->subject->subject_name} ({$sub->subject_type})\n";
        }
    }
    echo "\n";
}
