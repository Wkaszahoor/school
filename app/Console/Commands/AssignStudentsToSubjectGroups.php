<?php

namespace App\Console\Commands;

use App\Models\{Student, SubjectGroup};
use Illuminate\Console\Command;

class AssignStudentsToSubjectGroups extends Command
{
    protected $signature = 'assign:students-to-groups';
    protected $description = 'Assign students to subject groups based on their stream';

    public function handle()
    {
        $this->info('Assigning students to subject groups...');

        $updated = 0;

        // Get all students with stream values
        $students = Student::where('is_active', true)
            ->whereIn('stream', ['ICS', 'Pre-Medical', 'ICS (Class 9) - SSC', 'Pre-Medical (Class 9) - SSC', 'ICS (Class 10) - SSC', 'Pre-Medical (Class 10) - SSC', 'ICS (Class 11) - HSSC', 'Pre-Medical (Class 11) - HSSC', 'ICS (Class 12) - HSSC', 'Pre-Medical (Class 12) - HSSC'])
            ->with('class:id,class')
            ->get();

        foreach ($students as $student) {
            // Find subject group that matches this stream
            $group = SubjectGroup::where('stream', 'LIKE', '%' . ($student->stream ?? '') . '%')
                ->orWhere('group_name', 'LIKE', '%' . ($student->stream ?? '') . '%')
                ->first();

            // If not found, try by extracting base stream
            if (!$group && str_contains($student->stream, 'ICS')) {
                $group = SubjectGroup::where('stream', 'ICS')->first();
            } elseif (!$group && str_contains($student->stream, 'Medical')) {
                $group = SubjectGroup::where('stream', 'Pre-Medical')->first();
            }

            if ($group && !$student->subject_group_id) {
                $student->update([
                    'subject_group_id' => $group->id,
                    'stream' => $group->stream,
                ]);
                $updated++;
                $this->line("✓ {$student->full_name} → {$group->group_name}");
            }
        }

        $this->info("\n✅ Assigned $updated students to subject groups!");
    }
}
