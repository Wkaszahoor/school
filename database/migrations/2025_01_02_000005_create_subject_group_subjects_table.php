<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subject_group_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('subject_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->enum('subject_type', ['compulsory', 'major'])->default('compulsory');
            $table->timestamps();
            $table->unique(['group_id', 'subject_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('subject_group_subjects'); }
};
