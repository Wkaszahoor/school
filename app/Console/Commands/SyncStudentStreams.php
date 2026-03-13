<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\ClassStreamSubjectGroup;

class SyncStudentStreams extends Command
{
    protected $signature = 'sync:student-streams';
    protected $description = 'Sync student stream values with their subject group streams';

    public function handle()
    {
        $this->info('Starting student stream synchronization...');

        $students = Student::with('subjectGroup', 'class')->get();
        $updated = 0;

        foreach ($students as $student) {
            if (!$student->subjectGroup || !$student->class_id) {
                continue;
            }

            // Get the stream from the ClassStreamSubjectGroup
            $cssGroup = ClassStreamSubjectGroup::where('group_id', $student->subject_group_id)
                ->where('class_id', $student->class_id)
                ->first();

            if ($cssGroup) {
                $streamName = $this->formatStreamName($cssGroup->stream_key);

                if ($student->stream !== $streamName) {
                    $old = $student->stream;
                    $student->update(['stream' => $streamName]);
                    $this->line("  {$student->full_name}: '{$old}' → '{$streamName}'");
                    $updated++;
                }
            }
        }

        $this->info("✓ Synchronized {$updated} students.");
    }

    private function formatStreamName($key)
    {
        $map = [
            'science' => 'Science',
            'arts' => 'Arts',
            'ics' => 'ICS',
            'pre-medical' => 'Pre-Medical',
            'pre-engineering' => 'Pre-Engineering',
            'fa' => 'FA',
            'general' => 'General',
        ];

        return $map[$key] ?? ucfirst($key);
    }
}
