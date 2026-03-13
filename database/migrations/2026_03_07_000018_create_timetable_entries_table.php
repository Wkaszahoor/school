<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('timetables')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('room_configurations')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])->default('Monday');
            $table->boolean('is_locked')->default(false); // locked entries cannot be auto-rescheduled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('timetable_id');
            $table->index('class_id');
            $table->index('teacher_id');
            $table->index('time_slot_id');
            $table->index('day_of_week');
            $table->unique(['timetable_id', 'class_id', 'time_slot_id', 'day_of_week'], 'entry_class_slot_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
    }
};
