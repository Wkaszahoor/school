<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createClasses();
        $this->createSubjects();
        // $this->createStudents(); // Removed dummy student generation
        $this->createTeachers();
    }

    private function createClasses(): void
    {
        $classes = ['Nursery', 'Prep', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '1st Year', '2nd Year'];
        $academicYear = '2025-26';
        foreach ($classes as $class) {
            \App\Models\SchoolClass::updateOrCreate(
                ['class' => $class, 'academic_year' => $academicYear],
                ['is_active' => true]
            );
        }
        echo "✓ Created " . count($classes) . " classes\n";
    }

    private function createSubjects(): void
    {
        $subjects = [
            ['subject_name' => 'Mathematics', 'subject_code' => 'MATH'],
            ['subject_name' => 'English', 'subject_code' => 'ENG'],
            ['subject_name' => 'Science', 'subject_code' => 'SCI'],
            ['subject_name' => 'History', 'subject_code' => 'HIST'],
            ['subject_name' => 'Geography', 'subject_code' => 'GEO'],
            ['subject_name' => 'Computer Science', 'subject_code' => 'CS'],
        ];
        foreach ($subjects as $subject) {
            \App\Models\Subject::updateOrCreate(
                ['subject_code' => $subject['subject_code']],
                ['subject_name' => $subject['subject_name'], 'is_active' => true]
            );
        }
        echo "✓ Created " . count($subjects) . " subjects\n";
    }

    private function createStudents(): void
    {
        $count = 0;
        $classIds = \App\Models\SchoolClass::pluck('id')->toArray();

        for ($i = 1; $i <= 20; $i++) {
            \App\Models\Student::updateOrCreate(
                ['admission_no' => 'STU' . str_pad($i, 4, '0', STR_PAD_LEFT)],
                [
                    'full_name' => "Student $i",
                    'father_name' => "Father of Student $i",
                    'dob' => now()->subYears(14 + ($i % 4))->toDateString(),
                    'gender' => $i % 2 == 0 ? 'male' : 'female',
                    'class_id' => $classIds[array_rand($classIds)],
                    'blood_group' => ['O+', 'O-', 'A+', 'B+'][array_rand(['O+', 'O-', 'A+', 'B+'])],
                    'is_active' => true,
                ]
            );
            $count++;
        }
        echo "✓ Created " . $count . " students\n";
    }

    private function createTeachers(): void
    {
        $count = 0;
        $teacherUser = \App\Models\User::where('email', 'teacher@kort.org.uk')->first();

        if (!$teacherUser) {
            return;
        }

        $profile = \App\Models\TeacherProfile::updateOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'qualification' => 'Bachelor of Education',
                'specialization' => 'Mathematics',
                'joining_date' => now()->subYears(5)->toDateString(),
                'experience_years' => 5,
                'is_active' => true,
            ]
        );

        echo "✓ Created teacher profile\n";
    }
}
