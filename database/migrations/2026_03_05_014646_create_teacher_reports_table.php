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
        Schema::create('teacher_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subject_teacher_id')->comment('Teacher being reported on');
            $table->unsignedBigInteger('class_teacher_id')->comment('Class teacher submitting report');
            $table->unsignedBigInteger('class_id')->comment('Class this report relates to');
            $table->string('report_type')->default('general'); // general, performance, conduct, attendance
            $table->text('notes')->comment('Detailed report notes');
            $table->string('status')->default('open'); // open, resolved, closed
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('subject_teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('class_teacher_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');

            $table->index('subject_teacher_id');
            $table->index('class_teacher_id');
            $table->index('class_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_reports');
    }
};
