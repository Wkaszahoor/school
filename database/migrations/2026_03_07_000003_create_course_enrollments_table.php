<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->enum('enrollment_status', ['pending', 'enrolled', 'completed', 'withdrawn', 'rejected'])->default('pending');
            $table->dateTime('enrolled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('attendance_count')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'teacher_id'], 'unique_course_teacher');
            $table->index('teacher_id');
            $table->index('enrollment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
