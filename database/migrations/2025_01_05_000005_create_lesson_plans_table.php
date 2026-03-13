<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->date('week_start');
            $table->text('lesson_plan');
            $table->text('work_plan')->nullable();
            $table->enum('approval_status', ['Pending', 'Approved', 'Returned'])->default('Pending');
            $table->text('principal_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['teacher_id', 'class_id', 'subject_id', 'week_start'], 'unique_lesson_plan');
        });
    }
    public function down(): void { Schema::dropIfExists('lesson_plans'); }
};
