<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // Extend the approval_status enum to include the class_teacher_approved stage
            // Using raw DB statement for MySQL safety
            DB::statement("ALTER TABLE results MODIFY COLUMN approval_status ENUM('pending','class_teacher_approved','approved','rejected') NOT NULL DEFAULT 'pending'");

            // Class teacher review stage fields
            $table->foreignId('class_teacher_reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('approved_at');

            $table->timestamp('class_teacher_reviewed_at')
                  ->nullable()
                  ->after('class_teacher_reviewed_by');

            $table->text('class_teacher_remarks')
                  ->nullable()
                  ->after('class_teacher_reviewed_at');

            // Principal remarks (separate from the existing approval fields)
            $table->text('principal_remarks')
                  ->nullable()
                  ->after('class_teacher_remarks');

            // Rejection reason (used at either stage to provide feedback)
            $table->text('rejection_reason')
                  ->nullable()
                  ->after('principal_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // Restore enum to original values
            DB::statement("ALTER TABLE results MODIFY COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");

            $table->dropColumn([
                'class_teacher_reviewed_by',
                'class_teacher_reviewed_at',
                'class_teacher_remarks',
                'principal_remarks',
                'rejection_reason',
            ]);
        });
    }
};
