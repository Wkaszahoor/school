<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pbl_student_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('pbl_assignments')->cascadeOnDelete();
            $table->string('group_name');
            $table->foreignId('group_leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('project_proposal')->nullable();
            $table->enum('status', ['forming', 'active', 'submitted', 'evaluated', 'completed'])->default('forming');
            $table->integer('member_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('assignment_id');
            $table->index('group_leader_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbl_student_groups');
    }
};
