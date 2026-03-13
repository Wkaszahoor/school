<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('academic_year', 10);
            $table->string('exam_period', 50);
            $table->boolean('attendance_eligible')->default(true);
            $table->decimal('attendance_percent', 5, 2)->nullable();
            $table->enum('status', ['draft', 'issued'])->default('draft');
            $table->date('issued_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->string('pdf_path', 255)->nullable();
            $table->timestamps();

            // Index for quick lookup
            $table->index(['student_id', 'class_id'], 'idx_admission_cards_student_class');
            $table->index(['academic_year', 'exam_period'], 'idx_admission_cards_year_period');
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_cards');
    }
};
