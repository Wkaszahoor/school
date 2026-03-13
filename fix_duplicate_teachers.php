<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== FIXING DUPLICATE TEACHER ACCOUNTS ===\n\n";

// Map of old ID (double dots) => new ID (single dots)
$duplicates = [
    26 => 47,  // M. Ismail: m..ismail@kort.org.uk => m.ismail@kort.org.uk
    12 => 43,  // M. Junaid: m..junaid@kort.org.uk => m.junaid@kort.org.uk
    20 => 45,  // M. Rizwan: m..rizwan@kort.org.uk => m.rizwan@kort.org.uk
    19 => 44,  // M. Shahid: m..shahid@kort.org.uk => m.shahid@kort.org.uk
    22 => 46,  // M. Waqas: m..waqas@kort.org.uk => m.waqas@kort.org.uk
];

echo "Migrating data from old accounts to new ones...\n\n";

foreach ($duplicates as $oldId => $newId) {
    echo "Processing old ID {$oldId} → new ID {$newId}:\n";

    // Get teacher names
    $oldUser = DB::table('users')->where('id', $oldId)->first();
    $newUser = DB::table('users')->where('id', $newId)->first();

    if ($oldUser && $newUser) {
        echo "  Old: {$oldUser->name} ({$oldUser->email})\n";
        echo "  New: {$newUser->name} ({$newUser->email})\n";

        // Check for class teacher assignments
        $classTeacherCount = DB::table('classes')->where('class_teacher_id', $oldId)->count();
        if ($classTeacherCount > 0) {
            echo "  ⚠️ Migrating {$classTeacherCount} class teacher assignment(s)...\n";
            DB::table('classes')
                ->where('class_teacher_id', $oldId)
                ->update(['class_teacher_id' => $newId]);
        }

        // Delete old user account (which will cascade to teacher_profiles if no foreign key constraint)
        DB::table('users')->where('id', $oldId)->delete();
        echo "  ✓ Deleted old account\n";
    }
    echo "\n";
}

echo "\n=== VERIFICATION ===\n\n";

// Check remaining duplicates
$teachers = DB::table('users')
    ->where('role', 'teacher')
    ->orderBy('name')
    ->get();

$emailCount = [];
foreach ($teachers as $t) {
    $emailCount[$t->email] = ($emailCount[$t->email] ?? 0) + 1;
}

$hasDuplicates = false;
foreach ($emailCount as $email => $count) {
    if ($count > 1) {
        echo "❌ Still have duplicate: {$email} ({$count} accounts)\n";
        $hasDuplicates = true;
    }
}

if (!$hasDuplicates) {
    echo "✅ No duplicate emails found!\n";
}

// Verify all teacher_profile_id values
$missingProfile = DB::table('users')
    ->where('role', 'teacher')
    ->whereNull('teacher_profile_id')
    ->get();

if ($missingProfile->count() > 0) {
    echo "⚠️ Teachers missing teacher_profile_id:\n";
    foreach ($missingProfile as $t) {
        echo "  - {$t->name}\n";
    }
} else {
    echo "✅ All teachers have teacher_profile_id set!\n";
}

echo "\n=== FINAL SUMMARY ===\n";
echo "Total teachers remaining: " . DB::table('users')->where('role', 'teacher')->count() . "\n";

?>
