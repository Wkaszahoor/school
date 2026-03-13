<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('weekly_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('test_date');
            $table->unsignedSmallInteger('total_marks')->default(20);
            $table->decimal('obtained_marks', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('weekly_test_results'); }
};
