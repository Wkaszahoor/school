<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('training_courses')->nullOnDelete();
            $table->string('certificate_number', 100)->unique();
            $table->string('certificate_name');
            $table->string('issuing_organization');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_renewable')->default(false);
            $table->string('certificate_file_path', 500)->nullable();
            $table->enum('certification_level', ['beginner', 'intermediate', 'advanced', 'expert', 'master'])->default('intermediate');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked', 'suspended'])->default('active');
            $table->dateTime('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('teacher_id');
            $table->index('course_id');
            $table->index('issue_date');
            $table->index('expiry_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
