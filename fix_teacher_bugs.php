<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== FIXING TEACHER DATA ISSUES ===\n\n";

// Fix 1: Email double dots
$emailFixes = [
    'm..ismail@kort.org.uk' => 'm.ismail@kort.org.uk',
    'm..junaid@kort.org.uk' => 'm.junaid@kort.org.uk',
    'm..rizwan@kort.org.uk' => 'm.rizwan@kort.org.uk',
    'm..shahid@kort.org.uk' => 'm.shahid@kort.org.uk',
    'm..waqas@kort.org.uk' => 'm.waqas@kort.org.uk',
];

echo "Fixing email addresses...\n";
foreach ($emailFixes as $oldEmail => $newEmail) {
    $updated = DB::table('users')
        ->where('email', $oldEmail)
        ->update(['email' => $newEmail]);

    if ($updated > 0) {
        $user = DB::table('users')->where('email', $newEmail)->first();
        echo "✓ {$user->name}: {$oldEmail} → {$newEmail}\n";
    }
}

// Fix 2: M. Waqas missing teacher_profile_id
echo "\nFixing teacher_profile_id for M. Waqas...\n";
$mWaqas = DB::table('users')->where('email', 'm.waqas@kort.org.uk')->first();
if ($mWaqas) {
    $profile = DB::table('teacher_profiles')->where('user_id', $mWaqas->id)->first();
    if ($profile && !$mWaqas->teacher_profile_id) {
        DB::table('users')
            ->where('id', $mWaqas->id)
            ->update(['teacher_profile_id' => $profile->id]);
        echo "✓ M. Waqas: Set teacher_profile_id to {$profile->id}\n";
    }
}

echo "\n=== VERIFICATION ===\n\n";

// Verify fixes
$stillIssues = DB::table('users')
    ->where('role', 'teacher')
    ->whereRaw("email LIKE '%..%'")
    ->get();

if ($stillIssues->count() > 0) {
    echo "❌ Email issues remaining:\n";
    foreach ($stillIssues as $t) {
        echo "  - " . $t->name . ": " . $t->email . "\n";
    }
} else {
    echo "✅ All email addresses fixed!\n";
}

// Verify teacher_profile_id
$profileIssues = DB::table('users')
    ->where('role', 'teacher')
    ->whereNull('teacher_profile_id')
    ->get();

if ($profileIssues->count() > 0) {
    echo "❌ teacher_profile_id issues remaining:\n";
    foreach ($profileIssues as $t) {
        echo "  - " . $t->name . "\n";
    }
} else {
    echo "✅ All teacher_profile_id values set!\n";
}

echo "\n";

?>
