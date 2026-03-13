<?php

namespace App\Console\Commands;

use App\Models\TeacherAssignment;
use App\Models\Subject;
use App\Models\SchoolClass;
use Illuminate\Console\Command;

class CleanupOldSubjectAssignments extends Command
{
    protected $signature = 'cleanup:old-assignments {--class=9 : Class number to clean up}';
    protected $description = 'Remove old Science and Physical Education assignments, and duplicate Mathematics assignments';

    public function handle()
    {
        $classNumber = $this->option('class');
        $class = SchoolClass::where('class', $classNumber)->first();

        if (!$class) {
            $this->error("Class {$classNumber} not found");
            return 1;
        }

        // Get subjects to remove
        $oldSubjects = Subject::whereIn('subject_name', ['Science', 'Physical Education'])->get();
        $oldSubjectIds = $oldSubjects->pluck('id')->toArray();

        // Remove old Science and Physical Education assignments
        $deletedOld = TeacherAssignment::where('class_id', $class->id)
            ->whereIn('subject_id', $oldSubjectIds)
            ->delete();

        if ($deletedOld > 0) {
            $this->info("✓ Deleted {$deletedOld} old Science/Physical Education assignments");
        }

        // Find and remove duplicate Mathematics assignments
        // Keep only one Mathematics assignment per teacher per class
        $mathSubject = Subject::where('subject_name', 'Mathematics')->first();
        if ($mathSubject) {
            $mathAssignments = TeacherAssignment::where('class_id', $class->id)
                ->where('subject_id', $mathSubject->id)
                ->with('teacher')
                ->get()
                ->groupBy('teacher_id');

            $deletedDuplicates = 0;
            foreach ($mathAssignments as $teacherId => $assignments) {
                if ($assignments->count() > 1) {
                    // Keep the first (oldest), delete the rest
                    $toDelete = $assignments->skip(1);
                    foreach ($toDelete as $assignment) {
                        $assignment->delete();
                        $deletedDuplicates++;
                    }
                    $teacher = $assignments->first()->teacher;
                    $this->line("✓ Removed {$toDelete->count()} duplicate Mathematics assignments for {$teacher->name}");
                }
            }

            if ($deletedDuplicates > 0) {
                $this->info("✓ Deleted {$deletedDuplicates} duplicate Mathematics assignments total");
            }
        }

        $this->info("\n✅ Cleanup complete for Class {$classNumber}!");

        // Show remaining assignments
        $remainingAssignments = TeacherAssignment::where('class_id', $class->id)
            ->with(['teacher', 'subject'])
            ->get()
            ->groupBy('teacher_id');

        $this->line("\n📋 Current Assignments for Class {$classNumber}:");
        foreach ($remainingAssignments as $teacherId => $assignments) {
            $teacher = $assignments->first()->teacher;
            $subjects = $assignments->pluck('subject.subject_name')->unique()->join(', ');
            $this->line("  • {$teacher->name}: {$subjects}");
        }

        return 0;
    }
}
