<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // e.g., "2026 Spring Timetable"
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('academic_year', 20); // e.g., "2026-2027"
            $table->enum('term', ['spring', 'summer', 'autumn'])->default('spring');
            $table->enum('status', ['draft', 'generating', 'generated', 'published', 'archived'])->default('draft');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('total_classes')->default(0); // number of classes to schedule
            $table->integer('total_teachers')->default(0); // number of teachers
            $table->integer('total_rooms')->default(0); // number of rooms
            $table->integer('total_time_slots')->default(0); // number of periods per day
            $table->integer('total_days')->default(5); // days per week
            $table->json('generation_config')->nullable(); // JSON config used for generation
            $table->text('notes')->nullable();
            $table->integer('conflict_count')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('academic_year');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
