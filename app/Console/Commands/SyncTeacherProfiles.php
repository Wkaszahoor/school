<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\TeacherProfile;
use Illuminate\Console\Command;

class SyncTeacherProfiles extends Command
{
    protected $signature = 'sync:teacher-profiles';
    protected $description = 'Create missing teacher profiles and link users to profiles';

    public function handle()
    {
        $teachers = User::where('role', 'teacher')->get();
        $created = 0;
        $linked = 0;
        $skipped = 0;

        foreach ($teachers as $teacher) {
            // Check if teacher profile exists
            $profile = TeacherProfile::where('user_id', $teacher->id)->first();

            if (!$profile) {
                // Create teacher profile
                $profile = TeacherProfile::create([
                    'user_id' => $teacher->id,
                    'is_active' => true,
                ]);
                $this->line("✓ Created teacher profile: {$teacher->name}");
                $created++;
            }

            // Link user to profile if not already linked
            if ($teacher->teacher_profile_id !== $profile->id) {
                $teacher->update(['teacher_profile_id' => $profile->id]);
                $this->line("✓ Linked user to profile: {$teacher->name} (Profile ID: {$profile->id})");
                $linked++;
            } else {
                $this->line("✓ Already linked: {$teacher->name}");
                $skipped++;
            }
        }

        $this->info("\n=== Sync Complete ===");
        $this->info("Created profiles: {$created}");
        $this->info("Linked users: {$linked}");
        $this->info("Already linked: {$skipped}");
        $this->info("Total teachers: {$teachers->count()}");

        return 0;
    }
}
