<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 50)->unique();
            $table->string('course_name');
            $table->text('description')->nullable();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('course_type', ['workshop', 'certification', 'seminar', 'online', 'conference'])->default('workshop');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->text('objectives')->nullable();
            $table->integer('duration_hours')->default(0);
            $table->integer('max_participants')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location', 255)->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->decimal('cost', 10, 2)->default(0);
            $table->json('metadata')->nullable(); // Tags, certificate requirements, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index('course_type');
            $table->index('status');
            $table->index('instructor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_courses');
    }
};
