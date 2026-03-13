<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTeacherAssignments extends Command
{
    protected $signature = 'import:teacher-assignments';
    protected $description = 'Import teacher assignments (subject + classes) from Book2.csv';

    public function handle()
    {
        $filePath = base_path('Book2.csv');
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);

        $imported = 0;
        $failed = 0;
        $skipped = 0;

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 3 || empty($row[0])) continue;

            $teacherName = trim($row[0]);
            $subjectStr = trim($row[1]); // "English" or "Math, Science"
            $classesStr = trim($row[2]); // "10TH, 9-A, 9-B"

            // Find teacher
            $teacher = DB::table('users')
                ->where('name', 'LIKE', "%{$teacherName}%")
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                $this->line("⚠ Teacher not found: {$teacherName}");
                $failed++;
                continue;
            }

            // Parse subjects (comma-separated)
            $subjects = array_map('trim', explode(',', $subjectStr));

            // Parse classes (comma-separated)
            $classes = array_map('trim', explode(',', $classesStr));

            foreach ($subjects as $subjectName) {
                // Find subject
                $subject = DB::table('subjects')
                    ->where('subject_name', 'LIKE', "%{$subjectName}%")
                    ->first();

                if (!$subject) {
                    $this->line("⚠ Subject not found: {$subjectName} for {$teacherName}");
                    continue;
                }

                foreach ($classes as $className) {
                    $classRecord = $this->findClass($className);

                    if (!$classRecord) {
                        $this->line("⚠ Class not found: {$className}");
                        $failed++;
                        continue;
                    }

                    // Check if assignment exists
                    $exists = DB::table('teacher_assignments')
                        ->where('teacher_id', $teacher->id)
                        ->where('class_id', $classRecord->id)
                        ->where('subject_id', $subject->id)
                        ->where('assignment_type', 'subject_teacher')
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    // Create assignment using updateOrCreate to avoid duplicates
                    try {
                        DB::table('teacher_assignments')->insert([
                            'teacher_id' => $teacher->id,
                            'class_id' => $classRecord->id,
                            'subject_id' => $subject->id,
                            'assignment_type' => 'subject_teacher',
                            'academic_year' => '2025-26',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->line("✓ {$teacherName} → {$subjectName} in {$className}");
                        $imported++;
                    } catch (\Exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            $skipped++;
                        } else {
                            throw $e;
                        }
                    }
                }
            }
        }

        fclose($file);

        $this->info("\n=== Import Complete ===");
        $this->info("Imported: {$imported}");
        $this->info("Skipped: {$skipped}");
        $this->error("Failed: {$failed}");

        return 0;
    }

    private function findClass($className)
    {
        $mappings = [
            '10TH' => ['class' => '10', 'section' => 'A'],
            '9-A' => ['class' => '9', 'section' => 'A'],
            '9-B' => ['class' => '9', 'section' => 'B'],
            '8' => ['class' => '8', 'section' => 'A'],
            '7-A' => ['class' => '7', 'section' => 'A'],
            '7-B' => ['class' => '7', 'section' => 'B'],
            '6-A' => ['class' => '6', 'section' => 'A'],
            '6-B' => ['class' => '6', 'section' => 'B'],
            '5' => ['class' => '5', 'section' => 'A'],
            '4-A' => ['class' => '4', 'section' => 'A'],
            '4-B' => ['class' => '4', 'section' => 'B'],
            '3-A' => ['class' => '3', 'section' => 'A'],
            '3-B' => ['class' => '3', 'section' => 'B'],
            '2' => ['class' => '2', 'section' => 'A'],
            'one' => ['class' => '1', 'section' => 'A'],
            'two' => ['class' => '2', 'section' => 'A'],
            'Prep' => ['class' => 'Prep', 'section' => 'A'],
            'Nursery' => ['class' => 'Nursery', 'section' => 'A'],
            'P.G' => ['class' => 'P.G', 'section' => 'A'],
            '1ST YEAR' => ['class' => '1st Year', 'section' => 'A'],
            '2ND YEAR' => ['class' => '2nd Year', 'section' => 'A'],
        ];

        $className = trim($className);
        if (!isset($mappings[$className])) return null;

        $map = $mappings[$className];
        return DB::table('classes')
            ->where('class', $map['class'])
            ->where('section', $map['section'])
            ->first();
    }
}
