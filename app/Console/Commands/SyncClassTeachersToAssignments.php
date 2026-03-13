<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncClassTeachersToAssignments extends Command
{
    protected $signature = 'sync:class-teachers-to-assignments';
    protected $description = 'Sync class teachers from classes table to teacher_assignments table';

    public function handle()
    {
        // Get all classes with a class_teacher_id
        $classes = DB::table('classes')
            ->whereNotNull('class_teacher_id')
            ->get();

        $synced = 0;
        $skipped = 0;

        foreach ($classes as $class) {
            // Check if assignment already exists
            $exists = DB::table('teacher_assignments')
                ->where('teacher_id', $class->class_teacher_id)
                ->where('class_id', $class->id)
                ->where('assignment_type', 'class_teacher')
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Get the teacher's primary subject (first subject they teach)
            $subjectId = DB::table('teacher_assignments')
                ->where('teacher_id', $class->class_teacher_id)
                ->where('assignment_type', 'subject_teacher')
                ->orderBy('id')
                ->value('subject_id');

            // Check if this (teacher, class, subject) combo already exists
            // If it does, use a different subject to avoid unique constraint violation
            if ($subjectId) {
                $alreadyExists = DB::table('teacher_assignments')
                    ->where('teacher_id', $class->class_teacher_id)
                    ->where('class_id', $class->id)
                    ->where('subject_id', $subjectId)
                    ->exists();

                if ($alreadyExists) {
                    // Use Homeroom subject instead
                    $subjectId = DB::table('subjects')
                        ->where('subject_name', 'Homeroom')
                        ->value('id');

                    if (!$subjectId) {
                        $subjectId = DB::table('subjects')->insertGetId([
                            'subject_name' => 'Homeroom',
                            'subject_code' => 'HMR',
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // If still no subject, find English or any subject
            if (!$subjectId) {
                $subjectId = DB::table('subjects')
                    ->where('subject_name', 'English')
                    ->value('id');

                if (!$subjectId) {
                    $subjectId = DB::table('subjects')
                        ->where('is_active', true)
                        ->value('id');
                }

                if (!$subjectId) {
                    $subjectId = DB::table('subjects')->insertGetId([
                        'subject_name' => 'Homeroom',
                        'subject_code' => 'HMR',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Create the teacher assignment
            DB::table('teacher_assignments')->insert([
                'teacher_id' => $class->class_teacher_id,
                'class_id' => $class->id,
                'subject_id' => $subjectId,
                'assignment_type' => 'class_teacher',
                'academic_year' => $class->academic_year,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $className = $class->class . ($class->section ? "-{$class->section}" : '');
            $this->line("✓ Synced: {$className}");
            $synced++;
        }

        $this->info("\n=== Sync Complete ===");
        $this->info("Synced: {$synced}");
        $this->info("Skipped (already exists): {$skipped}");

        return 0;
    }
}
