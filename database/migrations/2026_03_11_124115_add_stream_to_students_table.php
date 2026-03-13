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
            $table->enum('stream', ['Science', 'Arts', 'ICS', 'Pre-Medical', 'Pre-Engineering', 'FA'])->nullable()->after('class_id')->comment('Student stream for classes 9-12');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('stream');
        });
    }
};
