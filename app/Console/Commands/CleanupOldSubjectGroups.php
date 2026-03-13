<?php

namespace App\Console\Commands;

use App\Models\SubjectGroup;
use Illuminate\Console\Command;

class CleanupOldSubjectGroups extends Command
{
    protected $signature = 'cleanup:subject-groups {--fix : Apply fixes to invalid stream values}';
    protected $description = 'List and optionally clean up subject groups with invalid or old stream values';

    protected $validStreams = ['Science', 'Arts', 'ICS', 'Pre-Medical', 'Pre-Engineering', 'FA', null];

    public function handle()
    {
        $this->info('Checking subject groups for invalid stream values...\n');

        $groups = SubjectGroup::all();
        $invalidGroups = $groups->filter(function ($group) {
            return !in_array($group->stream, $this->validStreams);
        });

        if ($invalidGroups->isEmpty()) {
            $this->info('✓ All subject groups have valid stream values!');
            return 0;
        }

        $this->warn("Found {$invalidGroups->count()} groups with invalid streams:\n");

        foreach ($invalidGroups as $group) {
            $studentCount = $group->students()->count();
            $this->line("  [{$group->id}] {$group->group_name} (Stream: '{$group->stream}') - {$studentCount} students");
        }

        if ($this->option('fix')) {
            $this->performFix($invalidGroups);
        } else {
            $this->warn("\nOptions:");
            $this->line("1. Update to valid stream (Science, Arts, ICS, Pre-Medical, Pre-Engineering, FA)");
            $this->line("2. Set stream to null");
            $this->line("3. Move students from old groups to new Pakistani education system groups");
            $this->line("\nRun: php artisan cleanup:subject-groups --fix");
        }

        return 0;
    }

    private function performFix($invalidGroups)
    {
        $this->info("\nApplying fixes...\n");

        foreach ($invalidGroups as $group) {
            $oldStream = $group->stream;

            // Fix "Premedical" -> "Pre-Medical"
            if (str_contains($group->stream, 'Premedical')) {
                $group->update(['stream' => 'Pre-Medical']);
                $this->line("✓ [{$group->id}] {$group->group_name}: '{$oldStream}' → 'Pre-Medical'");
            }
            // Set "General" and other invalid streams to null (not applicable to classes 9-12)
            else {
                $group->update(['stream' => null]);
                $this->line("✓ [{$group->id}] {$group->group_name}: '{$oldStream}' → null");
            }
        }

        $this->info("\nRunning student stream sync...");
        $this->call('sync:student-streams');
    }
}
