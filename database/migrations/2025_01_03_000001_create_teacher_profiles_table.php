<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->date('joining_date')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('cnic', 20)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('dob')->nullable();
            $table->text('certifications')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->string('previous_school')->nullable();
            $table->text('achievements')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('teacher_profiles'); }
};
