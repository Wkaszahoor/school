<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_datesheets', function (Blueprint $table) {
            $table->id();
            $table->string('class_name', 30);
            $table->string('subject_name', 120);
            $table->date('exam_date');
            $table->string('exam_time', 30)->nullable();
            $table->string('room_no', 30)->nullable();
            $table->unsignedSmallInteger('total_marks')->default(100);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('student_datesheets'); }
};
