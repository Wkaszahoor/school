<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Verifying Class Teacher Assignments ===\n\n";

// Get all classes with class_teacher_id
$classesWithTeachers = DB::table('classes')
    ->whereNotNull('class_teacher_id')
    ->get();

echo "Total classes with assigned teachers: " . $classesWithTeachers->count() . "\n\n";

foreach ($classesWithTeachers as $class) {
    $user = DB::table('users')->where('id', $class->class_teacher_id)->first();
    $profile = DB::table('teacher_profiles')->where('user_id', $class->class_teacher_id)->first();

    $className = $class->class . ($class->section ? '-' . $class->section : '');
    $userName = $user ? $user->name : 'USER NOT FOUND';
    $hasProfile = $profile ? 'YES' : 'NO';

    echo str_pad($className, 20) . " => " . str_pad($userName, 25) . " | Has Profile: " . $hasProfile . "\n";
}

echo "\n=== Issues Found ===\n\n";

$issues = [];

foreach ($classesWithTeachers as $class) {
    $user = DB::table('users')->where('id', $class->class_teacher_id)->first();
    if (!$user) {
        $issues[] = "Class {$class->class}-{$class->section}: User ID {$class->class_teacher_id} doesn't exist";
    } else {
        $profile = DB::table('teacher_profiles')->where('user_id', $class->class_teacher_id)->first();
        if (!$profile) {
            $issues[] = "Teacher {$user->name}: No teacher profile found (User ID: {$class->class_teacher_id})";
        }
    }
}

if (count($issues) > 0) {
    foreach ($issues as $issue) {
        echo "⚠️  " . $issue . "\n";
    }
} else {
    echo "✅ All class teachers have valid user accounts and teacher profiles!\n";
}

?>
