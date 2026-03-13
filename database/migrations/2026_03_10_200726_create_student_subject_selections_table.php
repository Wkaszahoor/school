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
        Schema::create('student_subject_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('subject_group_id')->constrained('subject_groups')->onDelete('cascade');
            $table->enum('subject_type', ['compulsory', 'optional']); // Type from group
            $table->timestamp('selected_at')->useCurrent();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id']);
            $table->index('student_id');
            $table->index('subject_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_subject_selections');
    }
};
