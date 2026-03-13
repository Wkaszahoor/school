<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('request_type', ['teacher', 'student'])->default('teacher');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->text('reason');
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('leave_requests'); }
};
