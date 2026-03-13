<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., National, Festival, Regional, Special
            $table->string('color', 20)->default('#FF6B6B'); // Color for calendar display
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_types');
    }
};
