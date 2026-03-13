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
        Schema::table('teacher_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_profiles', 'date_joined')) {
                $table->date('date_joined')->nullable()->after('joining_date');
            }
            if (!Schema::hasColumn('teacher_profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('experience_years');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_profiles', 'date_joined')) {
                $table->dropColumn('date_joined');
            }
            if (Schema::hasColumn('teacher_profiles', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
