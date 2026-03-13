<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('course_enrollments')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->enum('grade', ['A', 'B', 'C', 'D', 'F', 'P'])->nullable();
            $table->integer('hours_attended')->default(0);
            $table->dateTime('completion_date');
            $table->boolean('is_certified')->default(false);
            $table->text('instructor_feedback')->nullable();
            $table->json('completion_metrics')->nullable();
            $table->timestamps();

            $table->unique('enrollment_id', 'unique_enrollment_completion');
            $table->index('teacher_id');
            $table->index('completion_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_completions');
    }
};
