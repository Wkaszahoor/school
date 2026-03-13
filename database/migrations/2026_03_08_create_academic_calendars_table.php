<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('type', ['holiday', 'exam', 'term', 'event', 'semester', 'break', 'other'])->default('event');
            $table->string('color', 20)->default('#3B82F6'); // Calendar color
            $table->string('academic_year', 20);
            $table->boolean('is_all_day')->default(true);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('start_date');
            $table->index('academic_year');
            $table->index('type');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendars');
    }
};
