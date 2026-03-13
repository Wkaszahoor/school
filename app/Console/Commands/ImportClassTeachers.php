<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportClassTeachers extends Command
{
    protected $signature = 'import:class-teachers';
    protected $description = 'Import class teachers from Book2.csv';

    public function handle()
    {
        $filePath = base_path('Book2.csv');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);

        $assigned = 0;
        $failed = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 4 || empty($row[0])) {
                continue;
            }

            $teacherName = trim($row[0]);
            $className = trim($row[3]); // class teacher column

            // Skip if no class teacher assignment
            if (empty($className) || $className === '�') {
                continue;
            }

            // Find teacher by name
            $teacher = DB::table('users')
                ->where('name', 'LIKE', "%{$teacherName}%")
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                $errors[] = "Teacher not found: {$teacherName}";
                $failed++;
                continue;
            }

            // Parse the class name (e.g., "9-A", "P.G", "Nursery")
            $classRecord = $this->findClass($className);

            if (!$classRecord) {
                $errors[] = "Class not found: {$className} (for teacher {$teacherName})";
                $failed++;
                continue;
            }

            // Update the class with the teacher
            DB::table('classes')
                ->where('id', $classRecord->id)
                ->update(['class_teacher_id' => $teacher->id]);

            $this->line("✓ {$teacherName} → {$className}");
            $assigned++;
        }

        fclose($file);

        $this->info("\n=== Import Complete ===");
        $this->info("Assigned: {$assigned}");
        $this->error("Failed: {$failed}");

        if (!empty($errors)) {
            $this->error("\nErrors:");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return 0;
    }

    private function findClass($className)
    {
        $className = trim($className);

        // Map common variations to database names
        $mappings = [
            '10TH' => ['class' => '10', 'section' => 'A'],
            '9-A' => ['class' => '9', 'section' => 'A'],
            '9-B' => ['class' => '9', 'section' => 'B'],
            '9A' => ['class' => '9', 'section' => 'A'],
            '9B' => ['class' => '9', 'section' => 'B'],
            '8' => ['class' => '8', 'section' => 'A'],
            '8-A' => ['class' => '8', 'section' => 'A'],
            '7-A' => ['class' => '7', 'section' => 'A'],
            '7-B' => ['class' => '7', 'section' => 'B'],
            '7A' => ['class' => '7', 'section' => 'A'],
            '7B' => ['class' => '7', 'section' => 'B'],
            '6-A' => ['class' => '6', 'section' => 'A'],
            '6-B' => ['class' => '6', 'section' => 'B'],
            '6A' => ['class' => '6', 'section' => 'A'],
            '6B' => ['class' => '6', 'section' => 'B'],
            '5' => ['class' => '5', 'section' => 'A'],
            '5-A' => ['class' => '5', 'section' => 'A'],
            '4-A' => ['class' => '4', 'section' => 'A'],
            '4-B' => ['class' => '4', 'section' => 'B'],
            '4A' => ['class' => '4', 'section' => 'A'],
            '4B' => ['class' => '4', 'section' => 'B'],
            '3-A' => ['class' => '3', 'section' => 'A'],
            '3-B' => ['class' => '3', 'section' => 'B'],
            '3A' => ['class' => '3', 'section' => 'A'],
            '3B' => ['class' => '3', 'section' => 'B'],
            '2' => ['class' => '2', 'section' => 'A'],
            '2-A' => ['class' => '2', 'section' => 'A'],
            '2-B' => ['class' => '2', 'section' => 'B'],
            'TWO' => ['class' => '2', 'section' => 'A'],
            'one' => ['class' => '1', 'section' => 'A'],
            'ONE' => ['class' => '1', 'section' => 'A'],
            'two' => ['class' => '2', 'section' => 'A'],
            'Prep' => ['class' => 'Prep', 'section' => 'A'],
            'PREP' => ['class' => 'Prep', 'section' => 'A'],
            'Nursery' => ['class' => 'Nursery', 'section' => 'A'],
            'NURSERY' => ['class' => 'Nursery', 'section' => 'A'],
            'P.G' => ['class' => 'P.G', 'section' => 'A'],
            'PG' => ['class' => 'P.G', 'section' => 'A'],
            '1ST YEAR' => ['class' => '1st Year', 'section' => 'A'],
            '1st Year' => ['class' => '1st Year', 'section' => 'A'],
            '2ND YEAR' => ['class' => '2nd Year', 'section' => 'A'],
            '2nd Year' => ['class' => '2nd Year', 'section' => 'A'],
        ];

        if (isset($mappings[$className])) {
            $map = $mappings[$className];
            return DB::table('classes')
                ->where('class', $map['class'])
                ->where('section', $map['section'])
                ->first();
        }

        return null;
    }
}
