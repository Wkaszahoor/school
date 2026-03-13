<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('result_lock_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('exam_type', 30);
            $table->string('academic_year', 9);
            $table->string('term', 20);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('result_lock_groups'); }
};
