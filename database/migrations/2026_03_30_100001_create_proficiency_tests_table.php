<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proficiency_tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->integer('passing_score')->default(60); // percentage
            $table->integer('total_marks')->default(0);
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('proficiency_test_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('proficiency_tests')->cascadeOnDelete();
            $table->string('name'); // Reading, Writing, Grammar, Vocabulary, Listening
            $table->text('instructions')->nullable();
            $table->text('passage')->nullable(); // reading passage or audio transcript
            $table->integer('order')->default(0);
            $table->integer('marks')->default(0);
            $table->timestamps();
        });

        Schema::create('proficiency_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('proficiency_test_sections')->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('question_type', ['mcq', 'true_false', 'fill_blank', 'essay']);
            $table->json('options')->nullable();        // for mcq: [{label,text}]
            $table->string('correct_answer')->nullable(); // for auto-graded types
            $table->integer('marks')->default(1);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('proficiency_test_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('proficiency_tests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'expired'])->default('pending');
            $table->timestamps();

            $table->unique(['test_id', 'user_id']);
        });

        Schema::create('proficiency_test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('proficiency_test_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_score')->default(0);
            $table->integer('max_score')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('band_score', 3, 1)->default(0);
            $table->enum('status', ['in_progress', 'completed', 'grading'])->default('in_progress');
            $table->json('section_scores')->nullable(); // per-section breakdown
            $table->timestamps();
        });

        Schema::create('proficiency_test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('proficiency_test_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('proficiency_test_questions')->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->boolean('is_correct')->nullable(); // null = not graded (essay)
            $table->integer('marks_awarded')->default(0);
            $table->text('feedback')->nullable(); // for essay grading
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proficiency_test_answers');
        Schema::dropIfExists('proficiency_test_attempts');
        Schema::dropIfExists('proficiency_test_assignments');
        Schema::dropIfExists('proficiency_test_questions');
        Schema::dropIfExists('proficiency_test_sections');
        Schema::dropIfExists('proficiency_tests');
    }
};
