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
            // Increase CNIC column sizes to handle edge cases
            $table->string('student_cnic', 50)->nullable()->change();
            $table->string('father_cnic', 50)->nullable()->change();
            $table->string('mother_cnic', 50)->nullable()->change();
            $table->string('guardian_cnic', 100)->nullable()->change();
            // Change group_stream from enum to varchar to handle any value
            $table->string('group_stream', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_cnic', 20)->nullable()->change();
            $table->string('father_cnic', 20)->nullable()->change();
            $table->string('mother_cnic', 20)->nullable()->change();
            $table->string('guardian_cnic', 20)->nullable()->change();
            $table->enum('group_stream', ['pre_medical', 'pre_engineering', 'computer_science', 'arts', 'general'])->nullable()->change();
        });
    }
};
