<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // e.g., "Period 1", "09:00-10:00"
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->integer('period_number'); // 1, 2, 3, etc.
            $table->enum('slot_type', ['regular', 'break', 'lunch', 'assembly'])->default('regular');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['period_number']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
