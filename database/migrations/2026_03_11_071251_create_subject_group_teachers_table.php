<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('subject_group_teachers')) {
            return;
        }

        Schema::create('subject_group_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['class_teacher', 'subject_teacher'])->default('subject_teacher')->comment('class_teacher: oversees group, subject_teacher: teaches subjects');
            $table->foreignId('subject_id')->nullable()->comment('Which subject this teacher teaches (if subject_teacher)');
            $table->timestamps();

            $table->unique(['subject_group_id', 'user_id', 'subject_id'], 'sgt_group_user_subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_group_teachers');
    }
};
