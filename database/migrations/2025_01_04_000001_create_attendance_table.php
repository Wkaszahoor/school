<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['P', 'A', 'L'])->default('P');
            $table->foreignId('marked_by')->constrained('users')->cascadeOnDelete();
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
            $table->index(['student_id', 'attendance_date']);
            $table->index(['class_id', 'attendance_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('attendance'); }
};
