<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendance_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->date('alert_date');
            $table->decimal('attendance_pct', 5, 2);
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('attendance_alerts'); }
};
