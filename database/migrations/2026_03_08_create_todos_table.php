<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('category', ['academic', 'administrative', 'event', 'maintenance', 'other'])->default('academic');
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
