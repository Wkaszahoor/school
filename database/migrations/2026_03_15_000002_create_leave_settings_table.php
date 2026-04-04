<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_settings', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year')->comment('e.g. 2025-2026');
            $table->enum('leave_type', ['casual', 'annual', 'emergency', 'paid_total'])->comment('Type of leave');
            $table->integer('max_days')->comment('Maximum days allowed');
            $table->boolean('is_paid')->default(true)->comment('Is this a paid leave type?');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['academic_year', 'leave_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_settings');
    }
};
