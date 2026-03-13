<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('exam_type', 30);
            $table->string('academic_year', 9);
            $table->string('term', 20);
            $table->unsignedSmallInteger('total_marks')->default(100);
            $table->decimal('obtained_marks', 6, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('grade', 5)->default('F');
            $table->decimal('gpa_point', 3, 1)->default(0.0);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('lock_group_id')->nullable()->constrained('result_lock_groups')->nullOnDelete();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'class_id', 'exam_type', 'academic_year']);
        });
    }
    public function down(): void { Schema::dropIfExists('results'); }
};
