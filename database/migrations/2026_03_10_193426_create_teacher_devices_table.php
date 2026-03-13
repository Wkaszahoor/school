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
        Schema::create('teacher_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->enum('device_type', ['laptop', 'chromebook', 'tablet']);
            $table->string('serial_number')->unique();
            $table->string('model');
            $table->integer('made_year');
            $table->date('assigned_at');
            $table->date('unassigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('teacher_id');
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_devices');
    }
};
