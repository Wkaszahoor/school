<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Eid ul-Fitr", "Independence Day"
            $table->date('holiday_date');
            $table->foreignId('holiday_type_id')->constrained('holiday_types')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->integer('duration')->default(1); // Number of days
            $table->string('academic_year', 20)->nullable(); // e.g., "2026-2027"
            $table->boolean('is_gazetted')->default(false); // Is it a government declared holiday?
            $table->timestamps();
            $table->softDeletes();

            $table->index('holiday_date');
            $table->index('academic_year');
            $table->index('holiday_type_id');
            $table->unique(['holiday_date', 'holiday_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
