<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])->default('Monday');
            $table->foreignId('time_slot_id')->nullable()->constrained('time_slots')->setOnDelete('set null');
            $table->enum('availability_type', ['available', 'unavailable', 'preferred'])->default('available');
            $table->text('notes')->nullable();
            $table->integer('max_periods_per_day')->nullable(); // e.g., max 6 periods
            $table->integer('min_free_periods')->default(1); // minimum free periods required
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('teacher_id');
            $table->index('day_of_week');
            $table->unique(['teacher_id', 'day_of_week', 'time_slot_id'], 'avail_teacher_day_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_availabilities');
    }
};
