<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('behaviour_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('record_date');
            $table->enum('behaviour_type', ['positive', 'negative', 'neutral'])->default('neutral');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('behaviour_records'); }
};
