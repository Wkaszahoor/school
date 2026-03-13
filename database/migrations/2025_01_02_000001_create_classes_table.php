<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('class', 20);
            $table->string('section', 20)->default('A');
            $table->string('academic_year', 9);
            $table->unsignedBigInteger('class_teacher_id')->nullable();
            $table->foreign('class_teacher_id')->references('id')->on('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['class', 'section', 'academic_year']);
        });
    }
    public function down(): void { Schema::dropIfExists('classes'); }
};
