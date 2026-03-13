<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== COMPREHENSIVE TEACHER DATA AUDIT ===\n\n";

$teachers = DB::table('users')
    ->where('role', 'teacher')
    ->orderBy('name')
    ->get();

$issues = [];
$bugCount = 0;

echo "Checking " . $teachers->count() . " teachers...\n\n";

foreach ($teachers as $teacher) {
    $checks = [];

    // Check 1: Email format issues
    if (strpos($teacher->email, '..') !== false) {
        $checks[] = "⚠️ Email has double dots: {$teacher->email}";
        $bugCount++;
    }

    // Check 2: Teacher profile exists
    $profile = DB::table('teacher_profiles')->where('user_id', $teacher->id)->first();
    if (!$profile) {
        $checks[] = "❌ Missing teacher profile";
        $bugCount++;
    }

    // Check 3: teacher_profile_id in users table
    if (!$teacher->teacher_profile_id && $profile) {
        $checks[] = "❌ teacher_profile_id not set in users table (profile ID: {$profile->id})";
        $bugCount++;
    }

    // Check 4: Invalid teacher_profile_id
    if ($teacher->teacher_profile_id && $teacher->teacher_profile_id != $profile->id) {
        $checks[] = "❌ teacher_profile_id mismatch (user has: {$teacher->teacher_profile_id}, profile ID: {$profile->id})";
        $bugCount++;
    }

    // Check 5: Password issues
    if (!$teacher->password) {
        $checks[] = "⚠️ No password set";
        $bugCount++;
    }

    if (count($checks) > 0) {
        $issues[$teacher->name] = [
            'id' => $teacher->id,
            'email' => $teacher->email,
            'checks' => $checks
        ];
    }
}

if (count($issues) > 0) {
    echo "ISSUES FOUND:\n\n";
    foreach ($issues as $name => $data) {
        echo str_pad($name, 25) . " (ID: {$data['id']})\n";
        echo "Email: {$data['email']}\n";
        foreach ($data['checks'] as $check) {
            echo "  " . $check . "\n";
        }
        echo "\n";
    }
} else {
    echo "✅ No issues found! All teachers have valid data.\n\n";
}

echo "=== SUMMARY ===\n";
echo "Total teachers: " . $teachers->count() . "\n";
echo "Teachers with issues: " . count($issues) . "\n";
echo "Total issues found: " . $bugCount . "\n";

?>
