<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('homework_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->date('homework_date');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('homework_tasks'); }
};
