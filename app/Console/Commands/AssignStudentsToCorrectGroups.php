<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\ClassStreamSubjectGroup;

class AssignStudentsToCorrectGroups extends Command
{
    protected $signature = 'assign:students-to-groups';
    protected $description = 'Assign students to subject groups based on their stream';

    public function handle()
    {
        $this->info('Assigning students to correct subject groups...');

        $students = Student::with('class')->get();
        $updated = 0;

        foreach ($students as $student) {
            if (!$student->stream || !$student->class_id) {
                continue;
            }

            // Find the subject group for this class and stream
            $streamKey = $this->streamToKey($student->stream);
            $cssGroup = ClassStreamSubjectGroup::where('class_id', $student->class_id)
                ->where('stream_key', $streamKey)
                ->first();

            if ($cssGroup && $student->subject_group_id !== $cssGroup->group_id) {
                $oldGroupId = $student->subject_group_id;
                $student->update(['subject_group_id' => $cssGroup->group_id]);
                $this->line("  {$student->full_name}: group {$oldGroupId} → {$cssGroup->group_id}");
                $updated++;
            }
        }

        $this->info("✓ Assigned {$updated} students to correct groups.");
    }

    private function streamToKey($stream)
    {
        $map = [
            'Science' => 'science',
            'Arts' => 'arts',
            'ICS' => 'ics',
            'Pre-Medical' => 'pre-medical',
            'Pre-Engineering' => 'pre-engineering',
            'FA' => 'fa',
            'General' => 'general',
        ];

        return $map[$stream] ?? strtolower($stream);
    }
}
