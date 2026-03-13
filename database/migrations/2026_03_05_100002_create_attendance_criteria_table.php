<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->cascadeOnDelete();
            $table->enum('criteria_type', ['class', 'subject'])->default('class');
            $table->unsignedTinyInteger('min_attendance_percent')->default(75);
            $table->unsignedTinyInteger('max_allowed_absences')->nullable();
            $table->string('academic_year', 10);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Prevent duplicate criteria for same class+subject+academic_year
            $table->unique(['class_id', 'subject_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_criteria');
    }
};
