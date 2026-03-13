<?php

namespace Database\Seeders;

use App\Models\{User, TeacherProfile, SchoolClass, Subject, TeacherAssignment};
use Illuminate\Database\Seeder;

class TeacherAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        // Get the demo teacher user
        $teacherUser = User::where('email', 'teacher@kort.org.uk')->first();

        if (!$teacherUser) {
            echo "Teacher user not found.\n";
            return;
        }

        // Get or create teacher profile
        $teacher = TeacherProfile::where('user_id', $teacherUser->id)->first();

        if (!$teacher) {
            $teacher = TeacherProfile::create([
                'user_id' => $teacherUser->id,
                'employee_id' => 'TEACHER001',
                'is_active' => true,
            ]);
            echo "Created teacher profile: {$teacher->id}\n";
        }

        // Get first active class
        $class = SchoolClass::where('is_active', 1)->first();
        if (!$class) {
            echo "No active classes found.\n";
            return;
        }

        // Get first active subject
        $subject = Subject::where('is_active', 1)->first();
        if (!$subject) {
            echo "No active subjects found.\n";
            return;
        }

        // Check if assignment already exists
        $exists = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->first();

        if ($exists) {
            echo "Assignment already exists.\n";
            return;
        }

        // Create assignment
        $assignment = TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year' => '2025-26',
            'assignment_type' => 'subject_teacher',
        ]);

        echo "✓ Created assignment #{$assignment->id}\n";
        echo "  Teacher: {$teacherUser->name}\n";
        echo "  Class: {$class->class}{$class->section}\n";
        echo "  Subject: {$subject->subject_name}\n";
        echo "  Year: 2025-26\n";
        echo "  Type: subject_teacher\n";
    }
}
