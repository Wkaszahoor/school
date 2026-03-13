<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sick_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('sick_date');
            $table->unsignedSmallInteger('days_off')->default(1);
            $table->text('reason');
            $table->boolean('doctor_note')->default(false);
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('doctor_prescription')->nullable();
            $table->text('doctor_suggestion')->nullable();
            $table->foreignId('examined_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('examined_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sick_records'); }
};
