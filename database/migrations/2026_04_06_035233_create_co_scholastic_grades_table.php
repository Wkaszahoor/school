<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('co_scholastic_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->string('exam_type', 30);
            $table->string('term', 20);
            $table->string('activity', 30); // uniform, activities, digital, written, speaking
            $table->enum('term1_grade', ['A', 'B', 'C'])->nullable();
            $table->enum('term2_grade', ['A', 'B', 'C'])->nullable();
            $table->foreignId('entered_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['student_id', 'academic_year', 'exam_type', 'term', 'activity'],
                'co_scholastic_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('co_scholastic_grades');
    }
};
