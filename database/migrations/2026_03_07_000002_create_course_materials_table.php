<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
            $table->string('material_name');
            $table->text('description')->nullable();
            $table->enum('material_type', ['pdf', 'video', 'presentation', 'document', 'link', 'exercise'])->default('pdf');
            $table->string('file_path', 500)->nullable();
            $table->string('file_url', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->integer('sequence_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('course_id');
            $table->index('material_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
