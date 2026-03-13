<?php

namespace App\Console\Commands;

use App\Models\{TeacherAssignment, User, Subject, SchoolClass};
use Illuminate\Console\Command;

class AddMissingSubjectAssignments extends Command
{
    protected $signature = 'assign:missing-subjects {--class=9} {--year=2025-26}';
    protected $description = 'Add missing subject assignments for class teacher';

    public function handle()
    {
        $className = $this->option('class');
        $academicYear = $this->option('year');
        
        $class = SchoolClass::where('class', $className)->first();
        
        if (!$class) {
            $this->error("Class $className not found");
            return;
        }

        $classTeacher = User::find($class->class_teacher_id);
        if (!$classTeacher) {
            $this->error("Class teacher not found for Class $className");
            return;
        }

        $subjectNames = ['Islamiat', 'Mathematics', 'Physics'];
        $subjects = Subject::whereIn('subject_name', $subjectNames)->get();

        $added = 0;
        foreach ($subjects as $subject) {
            $exists = TeacherAssignment::where('teacher_id', $classTeacher->id)
                ->where('class_id', $class->id)
                ->where('subject_id', $subject->id)
                ->exists();

            if (!$exists) {
                TeacherAssignment::create([
                    'teacher_id' => $classTeacher->id,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'academic_year' => $academicYear,
                ]);
                $this->line("✓ Added {$subject->subject_name} to {$classTeacher->name} for Class {$className}");
                $added++;
            }
        }

        $this->info("\n✅ Added $added missing subject assignments!");
    }
}
