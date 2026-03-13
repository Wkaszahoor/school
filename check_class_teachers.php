<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$classes = DB::table('classes')
    ->whereNotNull('class_teacher_id')
    ->orderBy('class')
    ->get();

echo "\n=== Classes with Assigned Class Teachers ===\n\n";
foreach ($classes as $class) {
    $teacher = DB::table('users')
        ->where('id', $class->class_teacher_id)
        ->first();
    $name = $teacher ? $teacher->name : 'Not Found';
    $classStr = $class->class . ($class->section ? '-' . $class->section : '');
    echo str_pad($classStr, 20) . " => " . $name . "\n";
}

echo "\n=== Classes WITHOUT Class Teachers ===\n\n";
$withoutTeachers = DB::table('classes')
    ->whereNull('class_teacher_id')
    ->orderBy('class')
    ->get();

if ($withoutTeachers->count() > 0) {
    foreach ($withoutTeachers as $class) {
        $classStr = $class->class . ($class->section ? '-' . $class->section : '');
        echo $classStr . "\n";
    }
} else {
    echo "All classes have assigned class teachers!\n";
}

echo "\n=== Total Summary ===\n";
echo "Classes with teachers: " . $classes->count() . "\n";
echo "Classes without teachers: " . $withoutTeachers->count() . "\n";
?>
