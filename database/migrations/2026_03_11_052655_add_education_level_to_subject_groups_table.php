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
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->enum('education_level', ['SSC', 'HSSC'])->nullable()->comment('SSC: Classes 9-10, HSSC: Classes 11-12');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->dropColumn('education_level');
        });
    }
};
