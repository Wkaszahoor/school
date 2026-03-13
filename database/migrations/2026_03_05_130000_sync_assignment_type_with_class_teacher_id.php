<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sync assignment_type with class_teacher_id
        // For any teacher who is a class_teacher_id in the classes table,
        // update their assignment_type to 'class_teacher' for that class
        DB::statement("
            UPDATE teacher_assignments ta
            INNER JOIN classes c ON ta.class_id = c.id
            SET ta.assignment_type = 'class_teacher'
            WHERE c.class_teacher_id = ta.teacher_id
            AND ta.assignment_type = 'subject_teacher'
        ");
    }

    public function down(): void
    {
        // We can't safely reverse this, so we just mark it as non-reversible
        // The default migration down() will do nothing
    }
};
