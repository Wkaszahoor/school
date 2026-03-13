<?php

namespace App\Console\Commands;

use App\Models\{User, TeacherProfile, TeacherAssignment, Subject, SchoolClass};
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Hash;

class ImportTeachersFromExcel extends Command
{
    protected $signature = 'app:import-teachers-from-excel {file=sss.xlsx}';
    protected $description = 'Import teachers from Excel file with their subject and class assignments';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $this->info("=== TEACHER IMPORT STARTED ===\n");
            $this->info("Total rows: " . count($rows) . "\n");

            // Parse headers
            $headers = array_map('strtolower', $rows[0] ?? []);
            $nameCol = array_search('name', $headers);
            $subjectCol = array_search('teaching-subject', $headers);
            $classCol = array_search('teaching-class', $headers);
            $classTeacherCol = array_search('class teacher', $headers);

            if ($nameCol === false || $subjectCol === false || $classCol === false) {
                $this->error("Missing required columns. Expected: name, teaching-subject, teaching-class, class teacher");
                $this->line("Found columns: " . implode(", ", $headers));
                return 1;
            }

            $currentAcademicYear = config('school.current_academic_year', '2025-26');
            $created = 0;
            $updated = 0;
            $failed = 0;

            // Process each teacher
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                if (empty(array_filter($row))) {
                    continue;
                }

                $name = trim($row[$nameCol] ?? '');
                $subject = trim($row[$subjectCol] ?? '');
                $class = trim($row[$classCol] ?? '');
                $isClassTeacher = strtolower(trim($row[$classTeacherCol] ?? '')) === 'yes' || strtolower(trim($row[$classTeacherCol] ?? '')) === '1';

                if (empty($name) || empty($subject) || empty($class)) {
                    $this->warn("Row " . ($i + 1) . ": Missing required data. Skipped.");
                    $failed++;
                    continue;
                }

                try {
                    // Create or update teacher user
                    $email = strtolower(str_replace(' ', '.', $name)) . '@kort.org.uk';
                    $password = 'teacher123';

                    $user = User::where('email', $email)->first();

                    if (!$user) {
                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make($password),
                            'role' => 'teacher',
                        ]);
                        $created++;
                        $this->line("✓ Created user: $name ($email)");
                    } else {
                        $updated++;
                        $this->line("⟳ User already exists: $name ($email)");
                    }

                    // Create or update teacher profile
                    $profile = TeacherProfile::updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'employee_id' => 'EMP-' . $user->id,
                            'phone' => null,
                            'qualification' => 'Bachelor',
                            'specialisation' => $subject,
                            'date_joined' => now(),
                            'is_active' => true,
                        ]
                    );

                    // Get or create subject
                    $subjectModel = Subject::where('subject_name', $subject)->first();
                    if (!$subjectModel) {
                        $subjectModel = Subject::create([
                            'subject_name' => $subject,
                            'subject_code' => strtoupper(substr($subject, 0, 3)),
                            'is_active' => true,
                        ]);
                        $this->line("  → Created subject: $subject");
                    }

                    // Get or create class
                    $classModel = SchoolClass::where('class', $class)->first();
                    if (!$classModel) {
                        $classModel = SchoolClass::create([
                            'class' => $class,
                            'section' => null,
                            'academic_year' => $currentAcademicYear,
                            'is_active' => true,
                        ]);
                        $this->line("  → Created class: $class");
                    }

                    // Determine assignment type
                    $assignmentType = $isClassTeacher ? 'class_teacher' : 'subject_teacher';

                    // Create teacher assignment
                    $assignment = TeacherAssignment::updateOrCreate(
                        [
                            'teacher_id' => $user->id,
                            'class_id' => $classModel->id,
                            'subject_id' => $subjectModel->id,
                            'academic_year' => $currentAcademicYear,
                        ],
                        [
                            'assignment_type' => $assignmentType,
                        ]
                    );

                    // If class teacher, update class_teacher_id
                    if ($isClassTeacher) {
                        $classModel->update(['class_teacher_id' => $user->id]);
                        $this->line("  → Assigned as class teacher for: $class");
                    } else {
                        $this->line("  → Assigned as subject teacher for: $subject in $class");
                    }

                } catch (\Exception $e) {
                    $this->error("Row " . ($i + 1) . ": " . $e->getMessage());
                    $failed++;
                }
            }

            $this->info("\n=== IMPORT SUMMARY ===");
            $this->info("Users Created: $created");
            $this->info("Users Updated: $updated");
            $this->warn("Failed: $failed");
            $this->info("Academic Year: $currentAcademicYear");
            $this->line("\nDefault password for all teachers: teacher123");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
