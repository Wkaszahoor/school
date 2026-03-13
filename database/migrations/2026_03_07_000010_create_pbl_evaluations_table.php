<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pbl_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('pbl_submissions')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('pbl_student_groups')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rubric_id')->nullable()->constrained('pbl_rubrics')->nullOnDelete();
            $table->enum('evaluation_type', ['teacher', 'peer', 'self', 'combined'])->default('teacher');
            $table->decimal('total_score', 5, 2)->nullable();
            $table->decimal('total_marks', 7, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->char('grade', 1)->nullable();
            $table->text('general_feedback')->nullable();
            $table->json('criteria_scores')->nullable(); // Score per rubric criterion
            $table->json('strength_areas')->nullable();
            $table->json('improvement_areas')->nullable();
            $table->enum('status', ['draft', 'submitted', 'finalized'])->default('draft');
            $table->dateTime('evaluated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('evaluator_id');
            $table->index('evaluation_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbl_evaluations');
    }
};
