<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teaching_resources', function (Blueprint $table) {
            $table->id();
            $table->string('resource_name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->enum('resource_type', ['lesson_plan', 'worksheet', 'assessment', 'presentation', 'video', 'interactive', 'template', 'guide'])->default('lesson_plan');
            $table->enum('grade_level', ['primary', 'secondary', 'senior', 'university'])->default('secondary');
            $table->string('file_path', 500)->nullable();
            $table->string('file_url', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('download_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('rating_count')->default(0);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_by');
            $table->index('subject_id');
            $table->index('resource_type');
            $table->index('is_public');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_resources');
    }
};
