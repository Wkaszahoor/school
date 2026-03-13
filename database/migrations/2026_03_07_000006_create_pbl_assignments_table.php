<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pbl_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('project_title');
            $table->text('description');
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('rubric_id')->nullable()->constrained('pbl_rubrics')->nullOnDelete();
            $table->enum('project_type', ['individual', 'group'])->default('group');
            $table->text('learning_objectives')->nullable();
            $table->text('requirements')->nullable();
            $table->integer('group_size')->nullable();
            $table->date('start_date');
            $table->date('due_date');
            $table->date('presentation_date')->nullable();
            $table->integer('total_marks')->default(100);
            $table->enum('status', ['draft', 'active', 'in-progress', 'evaluation', 'completed'])->default('draft');
            $table->json('resources')->nullable(); // Links, materials, references
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('teacher_id');
            $table->index('class_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbl_assignments');
    }
};
