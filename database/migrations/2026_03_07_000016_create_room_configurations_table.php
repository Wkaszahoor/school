<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('room_name', 100); // e.g., "Class 1-A", "Lab 1", "Auditorium"
            $table->enum('room_type', ['classroom', 'lab', 'auditorium', 'sports', 'art', 'music', 'library'])->default('classroom');
            $table->integer('capacity'); // student capacity
            $table->string('block', 50)->nullable(); // e.g., "A", "B", "C"
            $table->string('floor', 50)->nullable(); // e.g., "Ground", "First", "Second"
            $table->boolean('has_projector')->default(false);
            $table->boolean('has_lab_equipment')->default(false);
            $table->boolean('has_ac')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('room_name');
            $table->index('room_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_configurations');
    }
};
