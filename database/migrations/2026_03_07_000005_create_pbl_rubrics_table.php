<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pbl_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('rubric_name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->integer('total_points')->default(100);
            $table->enum('rubric_type', ['project', 'presentation', 'collaboration', 'innovation', 'problem-solving'])->default('project');
            $table->json('criteria')->nullable(); // Array of {name, description, max_points, levels}
            $table->boolean('is_template')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_by');
            $table->index('rubric_type');
            $table->index('is_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbl_rubrics');
    }
};
