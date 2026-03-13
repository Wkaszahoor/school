<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timetable_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignId('entry_id')->nullable()->constrained('timetable_entries')->setOnDelete('set null');
            $table->enum('conflict_type', [
                'teacher_double_booking',
                'room_double_booking',
                'teacher_availability',
                'room_unavailable',
                'consecutive_classes',
                'free_period_violation',
                'unbalanced_workload'
            ])->default('teacher_double_booking');
            $table->enum('severity', ['hard', 'soft'])->default('hard');
            $table->text('description');
            $table->json('affected_entries')->nullable(); // IDs of entries involved in conflict
            $table->boolean('is_resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('timetable_id');
            $table->index('conflict_type');
            $table->index('severity');
            $table->index('is_resolved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_conflicts');
    }
};
