<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('discipline_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->enum('category', ['warning', 'achievement', 'suspension', 'other'])->default('warning');
            $table->enum('severity', ['low', 'medium', 'high'])->default('low');
            $table->date('incident_date');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'resolved', 'escalated'])->default('open');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('report_to_principal')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('discipline_records'); }
};
