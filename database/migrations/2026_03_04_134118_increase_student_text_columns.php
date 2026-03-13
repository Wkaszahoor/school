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
        Schema::table('students', function (Blueprint $table) {
            $table->text('guardian_name')->nullable()->change();
            $table->text('phone')->nullable()->change();
            $table->string('group_stream', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('guardian_name', 120)->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
            $table->string('group_stream', 20)->nullable()->change();
        });
    }
};
