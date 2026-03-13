<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TeacherProfile;
use App\Models\SchoolClass;

echo "\n=== Testing Class Teacher Display Logic ===\n\n";

// Get some teachers with their class teacher assignments
$teachers = TeacherProfile::with('user')
    ->whereHas('user')
    ->limit(5)
    ->get();

foreach ($teachers as $teacher) {
    if (!$teacher->user) continue;

    $classTeacherClasses = SchoolClass::where('class_teacher_id', $teacher->user->id)->get(['id', 'class', 'section']);
    $classList = $classTeacherClasses->map(function($c) {
        return $c->class . ($c->section ? "-{$c->section}" : '');
    })->toArray();

    echo "Teacher: " . str_pad($teacher->user->name, 25);
    echo " | Class Teacher of: ";

    if (count($classList) > 0) {
        echo implode(', ', $classList) . "\n";
    } else {
        echo "None\n";
    }
}

echo "\n=== Summary ===\n";
$allTeachers = TeacherProfile::with('user')->whereHas('user')->count();
$classTeachers = TeacherProfile::whereHas('user')
    ->get()
    ->filter(function($t) {
        return SchoolClass::where('class_teacher_id', $t->user_id)->count() > 0;
    })
    ->count();

echo "Total teachers: " . $allTeachers . "\n";
echo "Teachers with class assignments: " . $classTeachers . "\n";
echo "Teachers without class assignments: " . ($allTeachers - $classTeachers) . "\n";

?>
