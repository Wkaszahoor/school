<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('body');
            $table->enum('target_scope', ['all', 'role', 'teacher', 'class'])->default('all');
            $table->string('target_role', 40)->nullable();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('notices'); }
};
