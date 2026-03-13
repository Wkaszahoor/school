<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pbl_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('pbl_assignments')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('pbl_student_groups')->cascadeOnDelete();
            $table->enum('submission_type', ['document', 'presentation', 'prototype', 'video', 'code', 'mixed'])->default('document');
            $table->text('description')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_url', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->dateTime('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->integer('days_late')->default(0);
            $table->enum('plagiarism_status', ['pending', 'checked', 'flagged', 'approved'])->default('pending');
            $table->decimal('plagiarism_score', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['assignment_id', 'group_id'], 'unique_assignment_group_submission');
            $table->index('submission_type');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbl_submissions');
    }
};
