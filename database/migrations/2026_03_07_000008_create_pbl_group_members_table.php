<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pbl_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('pbl_student_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('role', ['leader', 'member', 'researcher', 'presenter', 'scribe'])->default('member');
            $table->enum('participation_status', ['active', 'inactive', 'dropped', 'transferred'])->default('active');
            $table->decimal('contribution_percentage', 5, 2)->default(50);
            $table->text('contribution_notes')->nullable();
            $table->dateTime('joined_at');
            $table->dateTime('left_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'student_id'], 'unique_group_student');
            $table->index('student_id');
            $table->index('participation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbl_group_members');
    }
};
